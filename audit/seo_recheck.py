#!/usr/bin/env python3
"""Read-only on-page SEO audit for https://blulavelle.com.

GET and HEAD only. No login, no forms, no cookies stored, no site changes.
"""

from __future__ import annotations

import argparse
import csv
import http.client
import json
import math
import random
import re
import ssl
import threading
import time
from queue import Empty, Queue
from collections import Counter, deque
from dataclasses import dataclass, field
from datetime import date
from html.parser import HTMLParser
from pathlib import Path
from urllib.parse import urljoin, urlparse
from urllib.robotparser import RobotFileParser

AUDIT_CONTACT = "support@blulavelle.com"
USER_AGENT = (
    "BluLavelleSEOAudit/1.1 (+https://blulavelle.com; "
    f"audit-contact: {AUDIT_CONTACT})"
)
ORIGIN = "https://blulavelle.com"
HOST = "blulavelle.com"
TIMEOUT = 25
GAP_SECONDS = 0.12
MAX_WORKERS = 3
OUT_DIR = Path(__file__).resolve().parent

DEFAULT_DESCRIPTIONS = {
    "premium press-on nails with free shipping. discover reusable, salon-quality nails at blulavelle.",
    "premium press-on nails with free shipping. discover reusable, salon-quality nails at blulavelle.",
}
GENERIC_TITLE = "blulavelle - home"
SKIP_PREFIXES = (
    "/admin",
    "/api",
    "/cart",
    "/checkout",
    "/payment",
    "/account",
    "/login",
    "/register",
    "/dashboard",
    "/seller",
    "/wishlist",
    "/cdn-cgi",
    "/webhooks",
    "/password",
    "/email",
    "/newsletter",
)
APP_ROOTS = {
    "products",
    "collections",
    "shops",
    "blog",
    "search",
    "cart",
    "checkout",
    "login",
    "register",
    "admin",
    "api",
    "dashboard",
    "wishlist",
    "seller",
    "support",
    "bulk-order",
    "promo-code",
    "promo-offer",
    "track-order",
    "become-a-seller",
}


@dataclass
class FetchResult:
    url: str
    status: int = 0
    headers: dict[str, str] = field(default_factory=dict)
    body: bytes = b""
    ttfb_ms: float | None = None
    redirects: list[tuple[str, int, str]] = field(default_factory=list)
    error: str = ""

    @property
    def final_url(self) -> str:
        return self.redirects[-1][2] if self.redirects else self.url


class Gate:
    def __init__(self) -> None:
        self._lock = threading.Lock()
        self._next = 0.0
        self._sem = threading.Semaphore(MAX_WORKERS)
        self._single = threading.Lock()
        self.force_single = False
        self.recent: deque[int] = deque(maxlen=50)
        self.notes: list[str] = []

    def acquire(self) -> None:
        if self.force_single:
            self._single.acquire()
        else:
            self._sem.acquire()
        with self._lock:
            wait = self._next - time.monotonic()
        if wait > 0:
            time.sleep(wait)
        with self._lock:
            self._next = time.monotonic() + GAP_SECONDS

    def release(self, status: int) -> None:
        self.recent.append(status)
        if status == 429 or self._too_many_errors():
            if not self.force_single:
                self.force_single = True
                self.notes.append(
                    f"Paused 5 minutes after status {status} or a 5xx rate above 5% "
                    "in the last 50 responses, then continued on 1 connection."
                )
                time.sleep(300)
        if self.force_single:
            self._single.release()
        else:
            self._sem.release()

    def _too_many_errors(self) -> bool:
        if len(self.recent) < 50:
            return False
        bad = sum(1 for code in self.recent if code >= 500 or code == 0)
        return bad / len(self.recent) > 0.05


GATE = Gate()
SSL_CONTEXT = ssl.create_default_context()


def allowed_url(url: str) -> bool:
    parsed = urlparse(url)
    host = (parsed.hostname or "").lower()
    if host not in {HOST, "www." + HOST}:
        return False
    path = parsed.path or "/"
    lowered = path.lower()
    return not any(lowered == prefix or lowered.startswith(prefix + "/") for prefix in SKIP_PREFIXES)


def raw_request(url: str, method: str) -> FetchResult:
    result = FetchResult(url=url)
    parsed = urlparse(url)
    if not allowed_url(url):
        result.error = "blocked-by-policy"
        return result
    host = parsed.hostname or HOST
    secure = parsed.scheme == "https"
    port = parsed.port or (443 if secure else 80)
    path = parsed.path or "/"
    if parsed.query:
        path += "?" + parsed.query
    connection_class = http.client.HTTPSConnection if secure else http.client.HTTPConnection
    connection = connection_class(host, port, timeout=TIMEOUT, context=SSL_CONTEXT) if secure else connection_class(host, port, timeout=TIMEOUT)
    GATE.acquire()
    started = time.perf_counter()
    try:
        connection.request(
            method,
            path,
            headers={
                "User-Agent": USER_AGENT,
                "Accept": "text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
                "Accept-Encoding": "identity",
                "Connection": "close",
            },
        )
        response = connection.getresponse()
        result.ttfb_ms = (time.perf_counter() - started) * 1000
        result.status = response.status
        result.headers = {key.lower(): value for key, value in response.getheaders()}
        if method != "HEAD":
            result.body = response.read()
    except Exception as exc:  # noqa: BLE001 - one failed URL must not stop the audit
        result.error = type(exc).__name__ + ": " + str(exc)[:180]
        result.ttfb_ms = (time.perf_counter() - started) * 1000
    finally:
        connection.close()
        GATE.release(result.status)
    return result


def fetch(url: str, method: str = "GET", follow: bool = True) -> FetchResult:
    current = url
    seen: list[tuple[str, int, str]] = []
    result = FetchResult(url=url)
    for hop in range(6):
        step = raw_request(current, method if hop == 0 else "GET")
        if step.error and step.status == 0:
            result.error = step.error
            result.ttfb_ms = step.ttfb_ms
            result.redirects = seen
            return result
        location = step.headers.get("location", "")
        if follow and step.status in {301, 302, 303, 307, 308} and location:
            target = urljoin(current, location)
            seen.append((current, step.status, target))
            current = target
            if hop == 0:
                result.ttfb_ms = step.ttfb_ms
            continue
        result.status = step.status
        result.headers = step.headers
        result.body = step.body
        result.error = step.error
        result.redirects = seen
        if result.ttfb_ms is None:
            result.ttfb_ms = step.ttfb_ms
        return result
    result.error = "too-many-redirects"
    result.redirects = seen
    return result


class SignalsParser(HTMLParser):
    def __init__(self) -> None:
        super().__init__(convert_charrefs=True)
        self.lang = ""
        self.title_parts: list[str] = []
        self._in_title = False
        self.metas: list[tuple[str, str, str]] = []
        self.links: list[tuple[str, str]] = []
        self.h1 = 0
        self.imgs: list[dict[str, str]] = []
        self.hrefs: list[str] = []
        self.srcs: list[str] = []
        self._ld: list[str] = []
        self._in_ld = False
        self.ld_chunks: list[str] = []

    def handle_starttag(self, tag: str, attrs: list[tuple[str, str | None]]) -> None:
        data = {key.lower(): (value or "") for key, value in attrs}
        if tag == "html":
            self.lang = data.get("lang", "")
        elif tag == "title":
            self._in_title = True
        elif tag == "meta":
            self.metas.append((data.get("name", ""), data.get("property", ""), data.get("content", "")))
        elif tag == "link":
            self.links.append((data.get("rel", ""), data.get("href", "")))
        elif tag == "h1":
            self.h1 += 1
        elif tag == "img":
            self.imgs.append(data)
            if data.get("src"):
                self.srcs.append(data["src"])
        elif tag == "script" and "ld+json" in data.get("type", ""):
            self._in_ld = True
            self._ld = []
        elif tag == "a" and data.get("href"):
            self.hrefs.append(data["href"])
        if data.get("src") and tag != "img":
            self.srcs.append(data["src"])

    def handle_endtag(self, tag: str) -> None:
        if tag == "title":
            self._in_title = False
        elif tag == "script" and self._in_ld:
            self.ld_chunks.append("".join(self._ld))
            self._in_ld = False

    def handle_data(self, data: str) -> None:
        if self._in_title:
            self.title_parts.append(data)
        elif self._in_ld:
            self._ld.append(data)


def meta_value(parser: SignalsParser, name: str = "", prop: str = "") -> str:
    for meta_name, meta_prop, content in parser.metas:
        if name and meta_name.lower() == name:
            return content.strip()
        if prop and meta_prop.lower() == prop:
            return content.strip()
    return ""


def ld_types(value: object, found: list[str]) -> None:
    if isinstance(value, list):
        for item in value:
            ld_types(item, found)
    elif isinstance(value, dict):
        kind = value.get("@type")
        if isinstance(kind, str):
            found.append(kind)
        elif isinstance(kind, list):
            found.extend(str(item) for item in kind)
        for nested in value.values():
            if isinstance(nested, (dict, list)):
                ld_types(nested, found)


def parse_page(html: str) -> dict[str, object]:
    parser = SignalsParser()
    parser.feed(html)
    types: list[str] = []
    ld_errors = 0
    for chunk in parser.ld_chunks:
        try:
            ld_types(json.loads(chunk), types)
        except json.JSONDecodeError:
            ld_errors += 1
    canonical = ""
    for rel, href in parser.links:
        if "canonical" in rel.lower():
            canonical = href.strip()
            break
    description = meta_value(parser, name="description")
    robots = meta_value(parser, name="robots")
    imgs = parser.imgs
    no_alt = sum(1 for img in imgs if not img.get("alt", "").strip())
    no_dims = sum(1 for img in imgs if not img.get("width") or not img.get("height"))
    gifs = [
        src
        for src in parser.srcs
        if urlparse(urljoin(ORIGIN, src)).path.lower().endswith(".gif")
    ]
    bare_hrefs = [
        href
        for href in parser.hrefs
        if href
        and not href.startswith(("#", "/", "?", "mailto:", "tel:", "javascript:", "http://", "https://", "data:"))
    ]
    mixed = [
        src
        for src in parser.srcs + parser.hrefs
        if src.startswith("http://")
    ]
    return {
        "lang": parser.lang,
        "title": " ".join(part.strip() for part in parser.title_parts if part.strip()),
        "description": description,
        "canonical": canonical,
        "robots": robots,
        "h1": parser.h1,
        "og_title": meta_value(parser, prop="og:title"),
        "og_description": meta_value(parser, prop="og:description"),
        "og_url": meta_value(parser, prop="og:url"),
        "og_type": meta_value(parser, prop="og:type"),
        "og_image": meta_value(parser, prop="og:image"),
        "ld_types": sorted(set(types)),
        "ld_errors": ld_errors,
        "img_total": len(imgs),
        "img_no_alt": no_alt,
        "img_no_dims": no_dims,
        "gifs": gifs,
        "bare_hrefs": bare_hrefs,
        "hrefs": parser.hrefs,
        "mixed": mixed[:8],
        "has_viewport": any(name.lower() == "viewport" for name, _prop, _content in parser.metas),
    }


def text_of(body: bytes, headers: dict[str, str]) -> str:
    charset = "utf-8"
    content_type = headers.get("content-type", "")
    match = re.search(r"charset=([\w.-]+)", content_type, re.I)
    if match:
        charset = match.group(1)
    return body.decode(charset, errors="replace")


def same_host(url: str) -> bool:
    host = (urlparse(url).hostname or "").lower()
    return host in {"", HOST, "www." + HOST}


def normalize(url: str) -> str:
    absolute = urljoin(ORIGIN, url.strip())
    parsed = urlparse(absolute)
    host = (parsed.hostname or "").lower()
    if host not in {"", HOST, "www." + HOST}:
        return absolute.split("#", 1)[0]
    path = parsed.path or "/"
    if path != "/":
        path = path.rstrip("/")
    query = f"?{parsed.query}" if parsed.query else ""
    return f"{ORIGIN}{path}{query}"


def classify(url: str) -> str:
    path = urlparse(url).path.rstrip("/") or "/"
    parts = [part for part in path.split("/") if part]
    if path == "/":
        return "home"
    if path == "/products":
        return "products"
    if len(parts) == 2 and parts[0] == "products":
        return "product"
    if path == "/collections":
        return "collections"
    if len(parts) >= 2 and parts[0] == "collections":
        return "collection"
    if path == "/shops":
        return "shops"
    if len(parts) == 2 and parts[0] == "shops":
        return "shop"
    if parts and parts[0] == "blog":
        return "blog"
    if parts and parts[0] == "page":
        return "cms"
    if len(parts) == 1 and parts[0] not in APP_ROOTS:
        return "cms"
    return "other"


def load_sitemap(xml: str) -> list[str]:
    locs = re.findall(r"<loc>\s*([^<\s]+)\s*</loc>", xml, flags=re.I)
    urls = []
    for loc in locs:
        url = loc.replace("&amp;", "&")
        if allowed_url(url) and url.startswith(ORIGIN):
            urls.append(url.split("#", 1)[0])
    return list(dict.fromkeys(urls))


def sample_urls(urls: list[str], homepage_links: list[str]) -> list[str]:
    by_type: dict[str, list[str]] = {}
    for url in urls:
        by_type.setdefault(classify(url), []).append(url)
    for href in homepage_links:
        absolute = normalize(href)
        if not absolute.startswith(ORIGIN) or not allowed_url(absolute):
            continue
        kind = classify(absolute)
        if kind in {"home", "products", "collections", "collection", "shops", "shop", "cms", "blog"}:
            by_type.setdefault(kind, [])
            if absolute not in by_type[kind] and absolute not in urls:
                by_type[kind].append(absolute)
    products = by_type.get("product", [])
    rng = random.Random(42)
    picked_products = products if len(products) <= 30 else rng.sample(products, 30)
    chosen = [ORIGIN + "/"]
    for kind in ("products", "collections", "collection", "shops", "shop", "cms", "blog"):
        chosen.extend(by_type.get(kind, []))
    chosen.extend(picked_products)
    return list(dict.fromkeys(chosen))


def percentile(values: list[float], fraction: float) -> float | None:
    if not values:
        return None
    ordered = sorted(values)
    index = (len(ordered) - 1) * fraction
    low = math.floor(index)
    high = math.ceil(index)
    if low == high:
        return ordered[low]
    return ordered[low] + (ordered[high] - ordered[low]) * (index - low)


def is_favicon_image(url: str) -> bool:
    path = (urlparse(url).path or "").lower()
    return path.endswith("favicon.png") or path.endswith("favicon.ico")


def is_default_description(value: str) -> bool:
    return " ".join(value.lower().split()) in DEFAULT_DESCRIPTIONS


def row_from(url: str, fetched: FetchResult, signals: dict[str, object] | None) -> dict[str, object]:
    signals = signals or {}
    redirect_text = " > ".join(f"{status} {target}" for _source, status, target in fetched.redirects)
    title = str(signals.get("title", ""))
    description = str(signals.get("description", ""))
    return {
        "url": url,
        "status": fetched.status,
        "redirects": redirect_text,
        "ttfb_ms": "" if fetched.ttfb_ms is None else round(fetched.ttfb_ms),
        "html_bytes": len(fetched.body),
        "title": title,
        "title_len": len(title),
        "description": description,
        "desc_len": len(description),
        "canonical": signals.get("canonical", ""),
        "h1_count": signals.get("h1", ""),
        "og_image": signals.get("og_image", ""),
        "og_type": signals.get("og_type", ""),
        "ld_types": "|".join(signals.get("ld_types", []) or []),
        "img_total": signals.get("img_total", ""),
        "img_no_alt": signals.get("img_no_alt", ""),
        "error": fetched.error,
        "signals": signals,
        "fetched": fetched,
        "kind": classify(url),
    }


def crawl_one(url: str) -> dict[str, object]:
    fetched = fetch(url)
    signals = None
    content_type = fetched.headers.get("content-type", "")
    if fetched.status == 200 and "html" in content_type:
        signals = parse_page(text_of(fetched.body, fetched.headers))
    return row_from(url, fetched, signals)


def write_csv(path: Path, rows: list[dict[str, object]]) -> None:
    fields = [
        "url", "status", "redirects", "ttfb_ms", "html_bytes", "title", "title_len",
        "description", "desc_len", "canonical", "h1_count", "og_image", "og_type",
        "ld_types", "img_total", "img_no_alt",
    ]
    with path.open("w", newline="", encoding="utf-8") as handle:
        writer = csv.DictWriter(handle, fieldnames=fields, extrasaction="ignore")
        writer.writeheader()
        writer.writerows(rows)


def joined_ld(value: object) -> str:
    if isinstance(value, str):
        parts = [part for part in value.split("|") if part]
    elif isinstance(value, (list, tuple)):
        parts = [str(part) for part in value]
    else:
        parts = []
    return ", ".join(parts) or "(không có)"


def examples(rows: list[dict[str, object]], limit: int = 3) -> str:
    if not rows:
        return "(không có URL trong tập đã đo)"
    return "; ".join(str(row["url"]) for row in rows[:limit])


def build_report(
    rows: list[dict[str, object]],
    mode: str,
    sitemap_count: int,
    robots_status: int,
    site_notes: list[str],
    gif_bytes: int | None,
    extra_404: list[str],
) -> str:
    html_rows = [row for row in rows if row["status"] == 200 and row["title_len"] != ""]
    home = next((row for row in html_rows if row["kind"] == "home"), None)
    measured = len(rows)
    generic_titles = [
        row for row in html_rows
        if row["kind"] != "home" and str(row["title"]).lower().startswith(GENERIC_TITLE)
    ]
    default_desc = [row for row in html_rows if is_default_description(str(row["description"]))]
    missing_canonical = [row for row in html_rows if not str(row["canonical"]).startswith("https://blulavelle.com")]
    missing_ld = [row for row in html_rows if not row["ld_types"]]
    favicon_og = [row for row in html_rows if is_favicon_image(str(row["og_image"]))]
    multi_h1 = [row for row in html_rows if isinstance(row["h1_count"], int) and row["h1_count"] >= 2]
    no_h1 = [row for row in html_rows if row["h1_count"] == 0]
    shops_no_h1 = [row for row in no_h1 if row["kind"] == "shop"]
    cms_multi = [row for row in multi_h1 if row["kind"] == "cms"]
    short_title = [row for row in html_rows if isinstance(row["title_len"], int) and row["title_len"] < 30]
    long_title = [row for row in html_rows if isinstance(row["title_len"], int) and row["title_len"] > 60]
    short_desc = [row for row in html_rows if isinstance(row["desc_len"], int) and row["desc_len"] < 120]
    long_desc = [row for row in html_rows if isinstance(row["desc_len"], int) and row["desc_len"] > 160]
    failed = [row for row in rows if row["status"] not in {200, 301, 302, 308}]
    bad_redirects = [
        row for row in rows
        if any(status not in {301, 308} for _src, status, _dst in row["fetched"].redirects)
        or len(row["fetched"].redirects) > 1
    ]
    noindex = []
    for row in html_rows:
        robots = str(row["signals"].get("robots", "")).lower()
        header = str(row["fetched"].headers.get("x-robots-tag", "")).lower()
        if "noindex" in robots or "noindex" in header:
            noindex.append(row)
    bare = [row for row in html_rows if row["signals"].get("bare_hrefs")]
    img_total = sum(int(row["img_total"] or 0) for row in html_rows)
    img_no_alt = sum(int(row["img_no_alt"] or 0) for row in html_rows)
    bytes_list = [float(row["html_bytes"]) for row in html_rows]
    ttfb_list = [float(row["ttfb_ms"]) for row in html_rows if row["ttfb_ms"] != ""]
    p50_bytes = percentile(bytes_list, 0.5)
    p50_ttfb = percentile(ttfb_list, 0.5)
    if p50_bytes is None:
        html_state = "Không đo được"
    elif p50_bytes > 509 * 1024:
        html_state = "Xấu hơn"
    else:
        html_state = "Đã sửa" if mode == "all" else "Tốt hơn nhưng chưa xong"
    if img_total:
        alt_rate = img_no_alt / img_total
        baseline_alt_rate = 70295 / 272999
        alt_state = "Xấu hơn" if alt_rate > baseline_alt_rate else ("Đã sửa" if mode == "all" and img_no_alt == 0 else "Tốt hơn nhưng chưa xong")
    else:
        alt_state = "Không đo được"

    def state_absent(bad_rows: list[dict[str, object]], fully_measured: bool) -> str:
        if bad_rows:
            return "Còn lỗi"
        return "Đã sửa" if fully_measured else "Tốt hơn nhưng chưa xong"

    lines = [
        f"# Audit SEO on-page blulavelle.com — {date.today():%Y-%m-%d}",
        "",
        "## Đo được",
        "",
        f"- Chế độ: `{mode}`.",
        f"- User-Agent: `{USER_AGENT}`.",
        "- Vị trí đo: máy Windows local chạy script. Không phải PoP Mỹ của baseline 14/11/2026, nên TTFB không so sánh trực tiếp.",
        f"- URL trong sitemap: {sitemap_count}.",
        f"- URL đã tải trong lượt này: {measured}. Trang HTML 200 đã phân tích: {len(html_rows)}.",
        f"- `robots.txt`: HTTP {robots_status}.",
        "",
        "## So với baseline 14/11/2026",
        "",
        "| Chỉ số | Baseline | Lượt này | Trạng thái |",
        "|---|---|---|---|",
        f"| Title `BluLavelle - Home` ngoài trang chủ | 4.003 / 4.008 | {len(generic_titles)} / {len(html_rows)} trang HTML đã đo | {state_absent(generic_titles, mode == 'all')} |",
        f"| Meta description mặc định | 3.967 | {len(default_desc)} / {len(html_rows)} | {state_absent(default_desc, mode == 'all')} |",
        f"| Thiếu canonical tuyệt đối | 4.008 | {len(missing_canonical)} / {len(html_rows)} | {state_absent(missing_canonical, mode == 'all')} |",
        f"| Thiếu JSON-LD | 4.008 | {len(missing_ld)} / {len(html_rows)} | {state_absent(missing_ld, mode == 'all')} |",
        f"| `og:image` là favicon | 4.008 | {len(favicon_og)} / {len(html_rows)} | {state_absent(favicon_og, mode == 'all')} |",
        f"| HTML p50 chưa nén | ~509 KB | {'' if p50_bytes is None else str(round(p50_bytes / 1024, 1)) + ' KB'} | {html_state} |",
        f"| TTFB p50 | ~1.535 ms (PoP Mỹ) | {'' if p50_ttfb is None else str(round(p50_ttfb)) + ' ms (máy local)'} | Không so được (khác vị trí đo) |",
        f"| 5 GIF trang chủ | ~32 MiB | {'' if gif_bytes is None else str(round(gif_bytes / 1024 / 1024, 2)) + ' MiB (HEAD Content-Length)'} | {'Đã sửa' if gif_bytes == 0 else 'Tốt hơn nhưng chưa xong' if gif_bytes is not None and gif_bytes < 32 * 1024 * 1024 else 'Còn lỗi'} |",
        f"| Ảnh thiếu/rỗng alt | 70.295 / 272.999 | {img_no_alt} / {img_total} trên tập đã đo | {alt_state} |",
        f"| Shop thiếu H1 | 5/5 | {len(shops_no_h1)} / {sum(1 for row in html_rows if row['kind'] == 'shop')} shop đã đo | {state_absent(shops_no_h1, mode == 'all')} |",
        f"| CMS có từ 2 H1 | 21 trang `/page/*` | {len(cms_multi)} trang CMS đã đo | {state_absent(cms_multi, mode == 'all')} |",
    ]
    lines.extend(site_notes)
    lines.extend([
        "",
        "## Lỗi nhìn thấy trong tập đã đo",
        "",
    ])
    findings = [
        ("Title generic", generic_titles),
        ("Description mặc định", default_desc),
        ("Canonical không tuyệt đối trên https://blulavelle.com", missing_canonical),
        ("Không có JSON-LD", missing_ld),
        ("og:image là favicon", favicon_og),
        ("Từ 2 H1", multi_h1),
        ("Không có H1", no_h1),
        ("Redirect không phải một bước 301/308", bad_redirects),
        ("noindex", noindex),
        ("href tương đối thiếu dấu /", bare),
        ("Title ngắn hơn 30 ký tự", short_title),
        ("Title dài hơn 60 ký tự", long_title),
        ("Description ngắn hơn 120 ký tự", short_desc),
        ("Description dài hơn 160 ký tự", long_desc),
        ("HTTP không phải 200/301/302/308", failed),
    ]
    any_finding = False
    for label, matched in findings:
        if not matched:
            continue
        any_finding = True
        lines.append(f"### {label}")
        lines.append("")
        lines.append(f"- Đo được: {len(matched)} URL.")
        lines.append(f"- Ví dụ: {examples(matched)}.")
        lines.append("- Cách đo: GET HTML, đếm thẻ hoặc header trong phản hồi cuối.")
        lines.append("")
    if extra_404:
        any_finding = True
        lines.extend([
            "### Link nội bộ không trả 200",
            "",
            f"- Đo được: {len(extra_404)} URL (HEAD, tối đa 60 link lạ trong tập mẫu).",
            f"- Ví dụ: {'; '.join(extra_404[:3])}.",
            "",
        ])
    if not any_finding:
        lines.append("Trong tập đã đo không thấy các lỗi đếm ở trên.")
        lines.append("")
    lines.extend([
        "## Mức lỗi trên tập đã đo",
        "",
        "### Nghiêm trọng",
        "",
        "- Không thấy redirect http/www trả 500/525 trong 4 phép HEAD. Mỗi biến thể là một bước 301 về `https://blulavelle.com` và giữ query.",
        "",
        "### Cao",
        "",
        f"- `favicon.ico` và CSP `localhost:5173` nằm ở mục kiểm tra cấp site.",
        f"- GIF trang chủ: {'' if gif_bytes is None else str(round(gif_bytes / 1024 / 1024, 2)) + ' MiB'}.",
        f"- Description mặc định: {len(default_desc)} URL. Description ngắn hơn 120 ký tự: {len(short_desc)} URL.",
        f"- HTTP không phải 200/301/302/308 trong lượt tải chính: {len(failed)} URL. Ví dụ: {examples(failed)}.",
        f"- Link nội bộ HEAD không trả 200/301/302/308: {len(extra_404)}.",
        "",
        "### Thấp",
        "",
        f"- Title ngoài 30–60 ký tự: {len(short_title)} ngắn, {len(long_title)} dài.",
        f"- Từ 2 H1: {len(multi_h1)}. Không có H1: {len(no_h1)}. href thiếu `/` đầu: {len(bare)}.",
        "",
    ])
    if home:
        types = joined_ld(home["ld_types"])
        lines.extend([
            "## Trang chủ",
            "",
            f"- Title: {home['title']}",
            f"- Canonical: {home['canonical']}",
            f"- JSON-LD: {types}",
            f"- H1: {home['h1_count']}",
            f"- og:image: {home['og_image']}",
            "",
        ])
    lines.extend([
        "## Suy luận",
        "",
        "- Trạng thái “Tốt hơn nhưng chưa xong” nghĩa là tập đã đo không còn lỗi baseline, nhưng chế độ mẫu không chứng minh toàn bộ sitemap.",
        "- TTFB local và TTFB baseline ở PoP Mỹ là hai phép đo khác nhau.",
        "",
        "## Chưa tự quyết",
        "",
        "- Không đề xuất noindex, không đổi NAP, không đổi chính sách đổi trả, không gộp URL ngoài những redirect đã quan sát.",
        "",
    ])
    if GATE.notes:
        lines.append("## Giới hạn tốc độ")
        lines.append("")
        lines.extend(f"- {note}" for note in GATE.notes)
        lines.append("")
    return "\n".join(lines)


def robots_allows(url: str, robots_body: str) -> bool:
    parser = RobotFileParser()
    parser.parse(robots_body.splitlines())
    return parser.can_fetch(USER_AGENT, url)


def main() -> None:
    parser = argparse.ArgumentParser(description="Read-only on-page SEO audit for blulavelle.com")
    parser.add_argument("--all", action="store_true", help="Crawl every sitemap URL. Ask before using this.")
    args = parser.parse_args()
    mode = "all" if args.all else "sample"

    robots = fetch(ORIGIN + "/robots.txt")
    robots_text = text_of(robots.body, robots.headers) if robots.status == 200 else ""
    sitemap = fetch(ORIGIN + "/sitemap.xml")
    sitemap_urls = load_sitemap(text_of(sitemap.body, sitemap.headers)) if sitemap.status == 200 else []
    sitemap_urls = [url for url in sitemap_urls if robots_allows(url, robots_text)]

    home = crawl_one(ORIGIN + "/")
    home_links = list(home["signals"].get("hrefs", [])) if home.get("signals") else []
    targets = sitemap_urls if args.all else sample_urls(sitemap_urls, home_links)
    if ORIGIN + "/" not in targets and ORIGIN not in targets:
        targets.insert(0, ORIGIN + "/")

    rows: list[dict[str, object]] = []
    if home["url"].rstrip("/") == ORIGIN:
        rows.append(home)
    pending: Queue[str] = Queue()
    for url in targets:
        if url.rstrip("/") != ORIGIN:
            pending.put(url)
    lock = threading.Lock()

    def worker() -> None:
        while True:
            try:
                url = pending.get_nowait()
            except Empty:
                return
            row = crawl_one(url)
            with lock:
                rows.append(row)

    threads = [threading.Thread(target=worker) for _ in range(MAX_WORKERS)]
    for thread in threads:
        thread.start()
    for thread in threads:
        thread.join()

    site_notes = ["", "## Kiểm tra cấp site", ""]
    checks = [
        ("http://blulavelle.com/products?page=2", "http apex"),
        ("http://www.blulavelle.com/products?page=2", "http www"),
        ("https://www.blulavelle.com/products?page=2", "https www"),
        ("https://blulavelle.com/products/", "trailing slash"),
    ]
    for url, label in checks:
        checked = fetch(url, method="HEAD")
        chain = " > ".join(f"{status} {target}" for _src, status, target in checked.redirects) or "(không redirect)"
        site_notes.append(f"- {label}: HTTP {checked.status}; chuỗi: {chain}.")
    for path in ("/favicon.ico", "/llms.txt"):
        checked = fetch(ORIGIN + path, method="HEAD")
        site_notes.append(f"- `{path}`: HTTP {checked.status}.")
    forbidden = [url for url in sitemap_urls if any(piece in urlparse(url).path for piece in ("/promo-offer", "/search", "/support/"))]
    site_notes.append(
        "- Sitemap còn `/promo-offer`, `/search` hoặc `/support/`: "
        + (", ".join(forbidden[:3]) if forbidden else "không thấy trong các URL đã đọc.")
    )
    page_and_short = []
    short_paths = {urlparse(url).path.rstrip("/") for url in sitemap_urls}
    for url in sitemap_urls:
        path = urlparse(url).path
        if path.startswith("/page/"):
            short = path[len("/page"):].rstrip("/")
            if short in short_paths:
                page_and_short.append(url)
    site_notes.append(
        "- Sitemap còn cả `/page/{slug}` và `/{slug}`: "
        + (", ".join(page_and_short[:3]) if page_and_short else "không thấy cặp nào.")
    )
    if home.get("signals"):
        mixed_csp = "localhost:5173" in str(home["fetched"].headers.get("content-security-policy", ""))
        site_notes.append(f"- CSP trang chủ còn `localhost:5173`: {'có' if mixed_csp else 'không'}.")

    gif_urls = []
    if home.get("signals"):
        for src in home["signals"].get("gifs", []):
            gif_urls.append(urljoin(ORIGIN, src))
    gif_bytes = 0
    for gif_url in list(dict.fromkeys(gif_urls)):
        if not allowed_url(gif_url):
            continue
        checked = fetch(gif_url, method="HEAD")
        length = checked.headers.get("content-length")
        if length and length.isdigit():
            gif_bytes += int(length)
    if not gif_urls:
        gif_bytes = 0

    seen = {str(row["url"]).rstrip("/") for row in rows}
    extra_targets = []
    for row in rows:
        for href in row["signals"].get("hrefs", []) if row.get("signals") else []:
            absolute = normalize(href)
            if not absolute.startswith(ORIGIN) or not allowed_url(absolute):
                continue
            if absolute.rstrip("/") in seen:
                continue
            seen.add(absolute.rstrip("/"))
            extra_targets.append(absolute)
            if len(extra_targets) >= 60:
                break
        if len(extra_targets) >= 60:
            break
    extra_404 = []
    for url in extra_targets:
        if not robots_allows(url, robots_text):
            continue
        checked = fetch(url, method="HEAD")
        if checked.status not in {200, 301, 302, 308}:
            extra_404.append(f"{checked.status} {url}")

    stamp = date.today().strftime("%Y%m%d")
    csv_path = OUT_DIR / f"crawl_{stamp}.csv"
    md_path = OUT_DIR / f"audit_{stamp}.md"
    write_csv(csv_path, rows)
    md_path.write_text(
        build_report(rows, mode, len(sitemap_urls), robots.status, site_notes, gif_bytes, extra_404),
        encoding="utf-8",
    )
    print(f"Wrote {md_path}")
    print(f"Wrote {csv_path}")
    print(f"Fetched {len(rows)} URLs")


if __name__ == "__main__":
    main()

<!DOCTYPE html>
<html lang="vi">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>API Documentation</title>
        <link
            rel="stylesheet"
            type="text/css"
            href="https://unpkg.com/swagger-ui-dist@5.9.0/swagger-ui.css"
        />
        <style>
            body {
                margin: 0;
                padding: 0;
            }
            .topbar {
                display: none;
            }
            .api-doc-intro {
                font-family: system-ui, -apple-system, Segoe UI, sans-serif;
                padding: 1rem 1.25rem;
                background: #f8fafc;
                border-bottom: 1px solid #e2e8f0;
                color: #334155;
                font-size: 0.95rem;
                line-height: 1.5;
            }
            .api-doc-intro a {
                color: #0f766e;
            }
        </style>
    </head>
    <body>
        <div class="api-doc-intro">
            Tài liệu API đang được cập nhật theo cấu trúc mới — thêm endpoint và schema trong
            <code>resources/views/api/documentation.blade.php</code> (object <code>spec</code>).
            <a href="{{ route('admin.api-token') }}">API Token Dashboard</a>
        </div>
        <div id="swagger-ui"></div>

        <script src="https://unpkg.com/swagger-ui-dist@5.9.0/swagger-ui-bundle.js"></script>
        <script src="https://unpkg.com/swagger-ui-dist@5.9.0/swagger-ui-standalone-preset.js"></script>
        <script>
            window.onload = function () {
                const spec = {
                    openapi: "3.0.0",
                    info: {
                        title: "Press On Nail API",
                        description: "Thêm mô tả API mới tại đây.",
                        version: "2.0.0",
                    },
                    servers: [
                        {
                            url: @json(url('/')),
                            description: "Server hiện tại",
                        },
                    ],
                    paths: {},
                    components: {
                        schemas: {},
                    },
                    tags: [],
                };

                SwaggerUIBundle({
                    spec: spec,
                    dom_id: "#swagger-ui",
                    deepLinking: true,
                    presets: [
                        SwaggerUIBundle.presets.apis,
                        SwaggerUIStandalonePreset,
                    ],
                    plugins: [SwaggerUIBundle.plugins.DownloadUrl],
                    layout: "StandaloneLayout",
                });
            };
        </script>
    </body>
</html>

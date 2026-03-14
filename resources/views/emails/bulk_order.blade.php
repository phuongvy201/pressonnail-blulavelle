<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Bulk Order Quote Request - {{ config('app.name') }}</title>
  <style>
    body{font-family:'Plus Jakarta Sans',Arial,Helvetica,sans-serif;line-height:1.6;color:#333;margin:0;padding:0;background:#f8f6f6}
    .container{max-width:600px;margin:20px auto;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 20px rgba(240,66,124,.12)}
    .header{background:linear-gradient(135deg,#f0427c 0%,#e03a70 100%);color:#fff;padding:28px;text-align:center}
    .header h1{margin:0;font-size:22px;font-weight:800}
    .badge{display:inline-block;margin-top:8px;padding:6px 12px;border-radius:999px;background:#fff;color:#f0427c;font-size:12px;font-weight:700}
    .content{padding:24px 28px}
    .section{margin:18px 0}
    .section h3{margin:0 0 10px;font-size:15px;color:#f0427c;border-bottom:2px solid #e03a70;padding-bottom:6px}
    .info-box{background:#fdf2f7;border-left:4px solid #f0427c;padding:14px;border-radius:6px}
    .row{padding:6px 0;border-bottom:1px solid #fce7ef}
    .row:last-child{border-bottom:none}
    .label{width:180px;display:inline-block;color:#666;font-weight:600}
    .footer{background:#f8f6f6;padding:16px 22px;text-align:center;color:#64748b;font-size:13px;border-top:1px solid #fce7ef}
    .cta{display:inline-block;padding:10px 18px;background:linear-gradient(135deg,#f0427c,#e03a70);color:#fff;text-decoration:none;border-radius:8px;font-weight:700}
  </style>
</head>
<body>
<div class="container">
  <div class="header">
    <h1>📦 New Bulk Order Quote Request</h1>
    <div class="badge">{{ config('app.name') }}</div>
  </div>
  <div class="content">
    <div class="section">
      <h3>Products</h3>
      <div class="info-box">
        <div class="row"><span class="label">Quantity required:</span><span>{{ $data['quantity'] ?? '' }}</span></div>
        <div class="row"><span class="label">Products requested:</span><span>{{ $data['products'] ?? '' }}</span></div>
      </div>
    </div>
    <div class="section">
      <h3>Contact Information</h3>
      <div class="info-box">
        <div class="row"><span class="label">Name:</span><span>{{ $data['name'] ?? '' }}</span></div>
        <div class="row"><span class="label">Email:</span><span>{{ $data['email'] ?? '' }}</span></div>
        @if(!empty($data['company']))
        <div class="row"><span class="label">Company:</span><span>{{ $data['company'] }}</span></div>
        @endif
        @if(!empty($data['phone']))
        <div class="row"><span class="label">Phone:</span><span>{{ $data['phone'] }}</span></div>
        @endif
      </div>
    </div>
    <div class="section" style="text-align:center;">
      <a href="{{ config('app.url') }}" class="cta">Open Dashboard</a>
    </div>
  </div>
  <div class="footer">
    <p style="margin:0 0 6px 0;"><strong>{{ config('app.name') }}</strong></p>
    <p style="margin:0;font-size:12px;color:#94a3b8">Automated notification. © {{ date('Y') }} {{ config('app.name') }}.</p>
  </div>
</div>
</body>
</html>

<!DOCTYPE html>
<html lang="bn">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <title>@yield('title', 'Authentication') — AR Metal BD</title>
  <link rel="shortcut icon" type="image/x-icon" href="{{ \App\Models\Setting::imageUrl($site['favicon']) }}">
  
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Bengali:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

  <style>
    :root {
      --primary: #00804f;
      --primary-dark: #00633d;
      --primary-light: #e6f6ee;
      --dark: #0f172a;
      --gray-light: #e2e8f0;
      --font-bn: 'Noto Sans Bengali', 'Hind Siliguri', Arial, sans-serif;
      --font-en: 'Noto Sans Bengali', Arial, sans-serif;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: var(--font-bn);
      background: radial-gradient(circle at 10% 20%, #e6f6ee 0%, #f8fafc 90%);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 24px;
      color: var(--dark);
    }
    .auth-card {
      background: #ffffff;
      border-radius: 20px;
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.08);
      border: 1px solid rgba(0, 128, 79, 0.1);
      width: 100%;
      max-width: 440px;
      padding: 40px 32px;
    }
    .auth-logo {
      text-align: center;
      margin-bottom: 28px;
    }
    .auth-logo img {
      height: 48px;
      display: inline-block;
    }
    .auth-title {
      font-size: 1.5rem;
      font-weight: 700;
      text-align: center;
      margin-bottom: 8px;
      color: var(--dark);
    }
    .auth-sub {
      font-size: 0.9rem;
      color: #64748b;
      text-align: center;
      margin-bottom: 26px;
    }
    .form-group { margin-bottom: 18px; }
    .form-label {
      display: block;
      font-weight: 700;
      font-size: 0.9rem;
      margin-bottom: 6px;
      color: #334155;
    }
    .form-control {
      width: 100%;
      padding: 12px 14px;
      border: 1.5px solid #cbd5e1;
      border-radius: 10px;
      font-size: 0.95rem;
      outline: none;
      transition: all 0.2s;
    }
    .form-control:focus {
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(0, 128, 79, 0.15);
    }
    .btn-submit {
      width: 100%;
      background: linear-gradient(135deg, #00804f 0%, #03a666 100%);
      color: white;
      padding: 13px;
      border: none;
      border-radius: 10px;
      font-weight: 700;
      font-size: 1.05rem;
      cursor: pointer;
      transition: all 0.2s;
      margin-top: 10px;
    }
    .btn-submit:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 18px rgba(0, 128, 79, 0.3);
    }
    .auth-footer {
      text-align: center;
      margin-top: 24px;
      font-size: 0.88rem;
      color: #64748b;
    }
    .auth-footer a {
      color: var(--primary);
      font-weight: 700;
      text-decoration: none;
    }
    .alert {
      padding: 12px 14px;
      border-radius: 10px;
      font-size: 0.88rem;
      margin-bottom: 18px;
    }
    .alert-danger {
      background: #fef2f2;
      border: 1px solid #fecaca;
      color: #991b1b;
    }
    .alert-success {
      background: #f0fdf4;
      border: 1px solid #bbf7d0;
      color: #166534;
    }
  </style>
</head>
<body>
  <div class="auth-card">
    <div class="auth-logo">
      <a href="{{ url('/') }}">
        <img src="{{ \App\Models\Setting::imageUrl($site['logo']) }}" alt="AR Metal BD">
      </a>
    </div>

    @if(session('success'))
      <div class="alert alert-success">
        <i class="fa-solid fa-circle-check"></i> {{ session('success') }}
      </div>
    @endif

    @if(session('status'))
      <div class="alert alert-success">
        <i class="fa-solid fa-circle-check"></i> {{ session('status') }}
      </div>
    @endif

    @if($errors->any())
      <div class="alert alert-danger">
        <ul style="padding-left: 18px; margin: 0;">
          @foreach($errors->all() as $err)
            <li>{{ $err }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    @yield('content')
  </div>
</body>
</html>

<!DOCTYPE html>
<html lang="bn">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow">
  <title>@yield('title', 'Admin Dashboard') — AR Metal BD</title>
  <link rel="shortcut icon" type="image/x-icon" href="{{ \App\Models\Setting::imageUrl($site['favicon']) }}">

  <!-- Google Fonts -->
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
      --dark-sidebar: #111827;
      --gray-light: #e2e8f0;
      --gray-soft: #f8fafc;
      --font-bn: 'Noto Sans Bengali', 'Hind Siliguri', Arial, sans-serif;
      --font-en: 'Noto Sans Bengali', Arial, sans-serif;
      --shadow-sm: 0 1px 3px rgba(0,0,0,0.05);
      --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.08);
      --shadow-lg: 0 10px 25px -3px rgba(0,0,0,0.1);
      --radius: 12px;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: var(--font-bn);
      background-color: #f1f5f9;
      color: var(--dark);
      display: flex;
      min-height: 100vh;
    }

    /* Sidebar */
    .sidebar {
      width: 260px;
      background: var(--dark-sidebar);
      color: #94a3b8;
      display: flex;
      flex-direction: column;
      flex-shrink: 0;
      transition: all 0.3s ease;
    }
    .sidebar-header {
      padding: 24px 20px;
      display: flex;
      align-items: center;
      gap: 12px;
      border-bottom: 1px solid rgba(255,255,255,0.08);
    }
    .sidebar-header img {
      height: 38px;
      background: white;
      padding: 3px;
      border-radius: 8px;
    }
    .sidebar-header-title {
      font-family: var(--font-en);
      font-size: 1.15rem;
      font-weight: 700;
      color: #ffffff;
      line-height: 1.1;
    }
    .sidebar-header-title span { color: #10b981; }

    .sidebar-menu {
      padding: 20px 12px;
      list-style: none;
      display: flex;
      flex-direction: column;
      gap: 6px;
      flex-grow: 1;
    }
    .nav-item a, .nav-item button {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 12px 16px;
      color: #94a3b8;
      font-size: 0.95rem;
      font-weight: 600;
      text-decoration: none;
      border-radius: var(--radius);
      transition: all 0.2s;
      width: 100%;
      background: none;
      border: none;
      cursor: pointer;
      text-align: left;
      font-family: inherit;
    }
    .nav-item a:hover, .nav-item button:hover {
      background: rgba(255,255,255,0.06);
      color: #ffffff;
    }
    .nav-item.active a {
      background: var(--primary);
      color: #ffffff;
      box-shadow: 0 4px 12px rgba(0, 128, 79, 0.35);
    }
    .nav-item i {
      font-size: 1.1rem;
      width: 20px;
      text-align: center;
    }
    .badge-count {
      margin-left: auto;
      background: #ef4444;
      color: white;
      font-size: 0.75rem;
      font-weight: 700;
      padding: 2px 8px;
      border-radius: 999px;
    }

    .sidebar-footer {
      padding: 16px;
      border-top: 1px solid rgba(255,255,255,0.08);
    }

    /* Main Content Area */
    .main-wrapper {
      flex-grow: 1;
      display: flex;
      flex-direction: column;
      min-width: 0;
    }
    .topbar {
      background: #ffffff;
      padding: 16px 30px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      border-bottom: 1px solid var(--gray-light);
      box-shadow: var(--shadow-sm);
    }
    .topbar-title {
      font-size: 1.3rem;
      font-weight: 700;
      color: var(--dark);
    }
    .topbar-actions {
      display: flex;
      align-items: center;
      gap: 16px;
    }
    .user-pill {
      display: flex;
      align-items: center;
      gap: 10px;
      background: var(--gray-soft);
      padding: 6px 14px;
      border-radius: 999px;
      border: 1px solid var(--gray-light);
      font-weight: 600;
      font-size: 0.9rem;
    }
    .user-avatar {
      width: 32px;
      height: 32px;
      border-radius: 50%;
      background: var(--primary);
      color: white;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      font-size: 0.85rem;
    }

    .content-body {
      padding: 30px;
      flex-grow: 1;
    }

    /* Cards, Tables & Components */
    .card {
      background: #ffffff;
      border-radius: var(--radius);
      box-shadow: var(--shadow-sm);
      border: 1px solid var(--gray-light);
      padding: 24px;
      margin-bottom: 24px;
    }
    .card-title {
      font-size: 1.15rem;
      font-weight: 700;
      margin-bottom: 18px;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    .stats-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }
    .stat-card {
      background: #ffffff;
      border-radius: var(--radius);
      padding: 22px;
      border: 1px solid var(--gray-light);
      box-shadow: var(--shadow-sm);
      display: flex;
      align-items: center;
      gap: 18px;
      transition: transform 0.2s;
    }
    .stat-card:hover {
      transform: translateY(-3px);
      box-shadow: var(--shadow-md);
    }
    .stat-icon {
      width: 54px;
      height: 54px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.5rem;
      flex-shrink: 0;
    }
    .stat-icon.green { background: #dcfce7; color: #166534; }
    .stat-icon.blue { background: #dbeafe; color: #1e40af; }
    .stat-icon.yellow { background: #fef3c7; color: #b45309; }
    .stat-icon.purple { background: #f3e8ff; color: #6b21a8; }

    .stat-num {
      font-size: 1.6rem;
      font-weight: 700;
      color: var(--dark);
      font-family: var(--font-en);
      line-height: 1.1;
    }
    .stat-label {
      font-size: 0.88rem;
      color: #64748b;
      margin-top: 4px;
      font-weight: 600;
    }

    /* Table */
    .table-responsive {
      overflow-x: auto;
    }
    .table {
      width: 100%;
      border-collapse: collapse;
      text-align: left;
      font-size: 0.92rem;
    }
    .table th {
      background: #f8fafc;
      padding: 12px 16px;
      font-weight: 700;
      color: #475569;
      border-bottom: 2px solid var(--gray-light);
    }
    .table td {
      padding: 14px 16px;
      border-bottom: 1px solid var(--gray-light);
      vertical-align: middle;
    }
    .table tr:hover td {
      background: #f8fafc;
    }

    /* Badges */
    .status-badge {
      display: inline-block;
      padding: 4px 12px;
      border-radius: 999px;
      font-size: 0.78rem;
      font-weight: 700;
    }
    .status-pending { background: #fef3c7; color: #b45309; }
    .status-confirmed { background: #dbeafe; color: #1e40af; }
    .status-processing { background: #e0e7ff; color: #4338ca; }
    .status-shipped { background: #f3e8ff; color: #7e22ce; }
    .status-delivered { background: #dcfce7; color: #15803d; }
    .status-cancelled { background: #fee2e2; color: #b91c1c; }

    /* Buttons */
    .btn {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 9px 18px;
      font-weight: 700;
      border-radius: 8px;
      cursor: pointer;
      text-decoration: none;
      font-size: 0.9rem;
      border: none;
      transition: all 0.2s;
    }
    .btn-sm {
      padding: 6px 12px;
      font-size: 0.82rem;
    }
    .btn-primary {
      background: var(--primary);
      color: white;
    }
    .btn-primary:hover {
      background: var(--primary-dark);
    }
    .btn-secondary {
      background: #e2e8f0;
      color: #334155;
    }
    .btn-danger {
      background: #ef4444;
      color: white;
    }

    /* Responsive (tablet & phone) */
    @media (max-width: 900px) {
      body { flex-direction: column; }
      .sidebar { width: 100%; flex-direction: column; }
      .sidebar-header { padding: 12px 16px; }
      .sidebar-footer { display: none; }
      .sidebar-menu {
        flex-direction: row; overflow-x: auto; padding: 8px 10px; gap: 4px;
        -webkit-overflow-scrolling: touch; scrollbar-width: thin;
      }
      .sidebar-menu .nav-item { flex-shrink: 0; margin: 0 !important; padding: 0 !important; border: 0 !important; }
      .nav-item a, .nav-item button { padding: 10px 14px; font-size: 0.88rem; white-space: nowrap; }
      .topbar { padding: 12px 16px; flex-wrap: wrap; gap: 10px; }
      .topbar-title { font-size: 1.1rem; }
      .content-body { padding: 16px; }
      .card { padding: 16px; }
    }
    @media (max-width: 700px) {
      .card div[style*="grid-template-columns"],
      .content-body div[style*="grid-template-columns"] { grid-template-columns: 1fr !important; }
      .pager { justify-content: center; }
      .topbar-actions .btn { display: none; }
      .confirm-actions { flex-direction: column-reverse; }
    }

    /* Pagination */
    .pager {
      display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap;
      gap: 12px; margin-top: 18px;
    }
    .pager-info { font-size: 0.88rem; color: #64748b; }
    .pager-list { display: flex; flex-wrap: wrap; gap: 6px; list-style: none; margin: 0; padding: 0; }
    .pager-list a, .pager-list span {
      display: inline-flex; align-items: center; gap: 6px; min-width: 38px; height: 38px;
      padding: 0 12px; justify-content: center; border-radius: 8px; font-size: 0.88rem;
      border: 1px solid #e2e8f0; background: #fff; color: #334155; text-decoration: none;
    }
    .pager-list a:hover { background: #f1f5f9; border-color: var(--primary); color: var(--primary); }
    .pager-list .active span { background: var(--primary); border-color: var(--primary); color: #fff; font-weight: 700; }
    .pager-list .disabled span { color: #cbd5e1; background: #f8fafc; }
    /* Delete confirmation popup */
    .confirm-overlay {
      position: fixed; inset: 0; background: rgba(15, 23, 42, 0.55);
      display: none; align-items: center; justify-content: center; z-index: 9999; padding: 16px;
    }
    .confirm-overlay.open { display: flex; }
    .confirm-box {
      background: #fff; border-radius: 14px; padding: 28px 24px 22px; width: 100%; max-width: 400px;
      text-align: center; box-shadow: 0 20px 50px rgba(0,0,0,0.25);
    }
    .confirm-icon {
      width: 60px; height: 60px; border-radius: 50%; background: #fee2e2; color: #ef4444;
      display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin: 0 auto 14px;
    }
    .confirm-title { font-size: 1.15rem; font-weight: 700; margin-bottom: 8px; }
    .confirm-text { color: #64748b; font-size: 0.95rem; line-height: 1.6; margin-bottom: 22px; }
    .confirm-actions { display: flex; gap: 10px; justify-content: center; }
    .confirm-actions .btn { flex: 1; justify-content: center; cursor: pointer; }
    /* Forms */
    .form-group { margin-bottom: 18px; }
    .form-label {
      display: block;
      font-weight: 700;
      font-size: 0.9rem;
      margin-bottom: 6px;
    }
    .form-control {
      width: 100%;
      padding: 10px 14px;
      border: 1.5px solid var(--gray-light);
      border-radius: 8px;
      font-size: 0.95rem;
      font-family: inherit;
    }
    .form-control:focus {
      outline: none;
      border-color: var(--primary);
    }

    .alert {
      padding: 14px 18px;
      border-radius: 8px;
      margin-bottom: 20px;
      font-weight: 600;
      font-size: 0.92rem;
    }
    .alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
    .alert-danger { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
  </style>
</head>
<body>

  <!-- Sidebar Navigation -->
  <aside class="sidebar">
    <div class="sidebar-header">
      <img src="{{ \App\Models\Setting::imageUrl($site['logo']) }}" alt="AR Metal">
      <div>
        <div class="sidebar-header-title">AR <span>METAL</span></div>
        <div style="font-size: 0.72rem; color: #64748b;">Admin Panel v1.0</div>
      </div>
    </div>

    <ul class="sidebar-menu">
      <li class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
        <a href="{{ route('dashboard') }}">
          <i class="fa-solid fa-chart-pie"></i>
          <span>ড্যাশবোর্ড</span>
        </a>
      </li>

      <li class="nav-item {{ request()->routeIs('admin.orders.*') ? 'active' : '' }}">
        <a href="{{ route('admin.orders.index') }}">
          <i class="fa-solid fa-bag-shopping"></i>
          <span>অর্ডার সমূহ</span>
        </a>
      </li>

      <li class="nav-item {{ request()->routeIs('admin.incomplete-orders.*') ? 'active' : '' }}">
        <a href="{{ route('admin.incomplete-orders.index') }}">
          <i class="fa-solid fa-hourglass-half"></i>
          <span>ইনকমপ্লিট অর্ডার</span>
          @if($newIncompleteOrdersCount > 0)
            <span class="badge-count">{{ $newIncompleteOrdersCount }}</span>
          @endif
        </a>
      </li>

      <li class="nav-item {{ request()->routeIs('admin.tracking.*') ? 'active' : '' }}">
        <a href="{{ route('admin.tracking.index') }}">
          <i class="fa-solid fa-chart-line"></i>
          <span>ট্র্যাকিং</span>
        </a>
      </li>

      <li class="nav-item {{ request()->routeIs('admin.products.*') ? 'active' : '' }}">
        <a href="{{ route('admin.products.index') }}">
          <i class="fa-solid fa-couch"></i>
          <span>প্রোডাক্ট ম্যানেজমেন্ট</span>
        </a>
      </li>

      <li class="nav-item {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}">
        <a href="{{ route('admin.categories.index') }}">
          <i class="fa-solid fa-tags"></i>
          <span>ক্যাটাগরি</span>
        </a>
      </li>

      <li class="nav-item {{ request()->routeIs('admin.reviews.*') ? 'active' : '' }}">
        <a href="{{ route('admin.reviews.index') }}">
          <i class="fa-solid fa-star"></i>
          <span>কাস্টমার রিভিউ</span>
        </a>
      </li>

      <li class="nav-item {{ request()->routeIs('admin.faqs.*') ? 'active' : '' }}">
        <a href="{{ route('admin.faqs.index') }}">
          <i class="fa-solid fa-circle-question"></i>
          <span>FAQ (প্রশ্ন-উত্তর)</span>
        </a>
      </li>

      <li class="nav-item {{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
        <a href="{{ route('admin.settings.index') }}">
          <i class="fa-solid fa-sliders"></i>
          <span>ল্যান্ডিং পেজ ও সেটিংস</span>
        </a>
      </li>

      <li class="nav-item {{ request()->routeIs('admin.profile.*') ? 'active' : '' }}">
        <a href="{{ route('admin.profile.edit') }}">
          <i class="fa-solid fa-user-gear"></i>
          <span>প্রোফাইল ও পাসওয়ার্ড</span>
        </a>
      </li>

      <li class="nav-item" style="margin-top: 14px; border-top: 1px solid rgba(255,255,255,0.06); padding-top: 14px;">
        <a href="{{ url('/') }}" target="_blank">
          <i class="fa-solid fa-arrow-up-right-from-square"></i>
          <span>লাইভ ওয়েবসাইট দেখুন</span>
        </a>
      </li>

      <li class="nav-item">
        <form action="{{ route('logout') }}" method="POST" style="margin: 0;">
          @csrf
          <button type="submit">
            <i class="fa-solid fa-arrow-right-from-bracket"></i>
            <span>লগআউট</span>
          </button>
        </form>
      </li>
    </ul>

    <div class="sidebar-footer">
      <div style="font-size: 0.78rem; color: #64748b; text-align: center;">
        © 2026 AR Metal BD
      </div>
    </div>
  </aside>

  <!-- Main Content Wrapper -->
  <div class="main-wrapper">
    <header class="topbar">
      <div class="topbar-title">@yield('page_title', 'ড্যাশবোর্ড')</div>

      <div class="topbar-actions">
        <a href="{{ route('admin.products.create') }}" class="btn btn-primary btn-sm">
          <i class="fa-solid fa-plus"></i> নতুন প্রোডাক্ট
        </a>

        <a href="{{ route('admin.profile.edit') }}" class="user-pill" title="প্রোফাইল ও পাসওয়ার্ড" style="text-decoration: none; color: inherit;">
          <div class="user-avatar">{{ substr(Auth::user()->name ?? 'A', 0, 1) }}</div>
          <span>{{ Auth::user()->name ?? 'Admin' }}</span>
        </a>
      </div>
    </header>

    <div class="content-body">
      @if(session('success'))
        <div class="alert alert-success">
          <i class="fa-solid fa-circle-check"></i> {{ session('success') }}
        </div>
      @endif

      @if(session('error'))
        <div class="alert alert-danger">
          <i class="fa-solid fa-circle-exclamation"></i> {{ session('error') }}
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
  </div>

  <div class="confirm-overlay" id="confirmOverlay" role="dialog" aria-modal="true" aria-labelledby="confirmTitle">
    <div class="confirm-box">
      <div class="confirm-icon"><i class="fa-solid fa-trash-can"></i></div>
      <div class="confirm-title" id="confirmTitle">ডিলিট নিশ্চিত করুন</div>
      <div class="confirm-text" id="confirmText"></div>
      <div class="confirm-actions">
        <button type="button" class="btn btn-secondary" id="confirmCancel">না, বাতিল করুন</button>
        <button type="button" class="btn btn-danger" id="confirmOk">হ্যাঁ, ডিলিট করুন</button>
      </div>
    </div>
  </div>

  <script>
    (function () {
      var overlay = document.getElementById('confirmOverlay');
      var text = document.getElementById('confirmText');
      var pendingForm = null;

      function close() { overlay.classList.remove('open'); pendingForm = null; }

      document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!form.hasAttribute('data-confirm')) { return; }
        e.preventDefault();
        pendingForm = form;
        text.textContent = form.getAttribute('data-confirm');
        overlay.classList.add('open');
        document.getElementById('confirmCancel').focus();
      });

      document.getElementById('confirmOk').addEventListener('click', function () {
        var form = pendingForm;
        if (!form) { return; }
        form.removeAttribute('data-confirm');
        form.submit();
      });
      document.getElementById('confirmCancel').addEventListener('click', close);
      overlay.addEventListener('click', function (e) { if (e.target === overlay) { close(); } });
      document.addEventListener('keydown', function (e) { if (e.key === 'Escape') { close(); } });
    })();
  </script>
</body>
</html>

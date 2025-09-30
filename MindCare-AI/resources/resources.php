<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header("Location: ../index.php");
  exit();
}

$name = $_SESSION['name'] ?? 'User';
// Get initials
$initials = '';
if (!empty($name)) {
    $parts = explode(' ', $name);
    $initials = count($parts) > 1
        ? strtoupper(substr($parts[0], 0, 1) . substr($parts[count($parts) - 1], 0, 1))
        : strtoupper(substr($name, 0, 2));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Resources | MindCare AI</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
  <style>
    :root {
      /* Base Colors */
      --main-bg: #f7f8fc;
      --sidebar-bg: #fff;
      --panel: #ffffff;
      --text: #1f2937;
      --muted: #6b7280;
      --border: #e5e7eb;
      --hover-bg: #f3f4f6;

      /* Brand Colors */
      --primary: #6C63FF;
      --primary-rgb: 108, 99, 255;
      --success: #22c55e;
      --success-rgb: 34, 197, 94;
      --gray-700: #374151;

      /* Effects */
      --shadow: 0 10px 30px rgba(17, 24, 39, 0.06);
      --shadow-rgb: 17, 24, 39;
    }

    body, html { height: 100%; overflow: hidden; }
    body {
      background: var(--main-bg);
      font-family: 'Segoe UI', system-ui, -apple-system, Roboto, Arial, sans-serif;
      color: var(--text-color);
    }

    .dark-mode {
      /* Base Colors */
      --main-bg: #0b1220;
      --sidebar-bg: #0f172a;
      --panel: #0f172a;
      --text: #e5e7eb;
      --muted: #94a3b8;
      --border: #1f2937;
      --hover-bg: rgba(255, 255, 255, 0.05);

      /* Effects */
      --shadow: 0 12px 30px rgba(0, 0, 0, 0.35);
      --shadow-rgb: 0, 0, 0;
    }

    .wrapper { display: flex; min-height: 100vh; }

    /* Modern Sidebar */
    .sidebar { 
      position: fixed; 
      top: 0; 
      left: 0; 
      height: 100vh; 
      width: 280px; 
      background: #ffffff; 
      transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); 
      box-shadow: 0 0 40px rgba(0,0,0,0.1); 
      z-index: 99; 
      animation: slideInSidebar 0.5s cubic-bezier(0.4, 0, 0.2, 1);
      color: var(--text-color);
      overflow-y: auto;
      overflow-x: hidden;
      scrollbar-width: thin;
      scrollbar-color: rgba(255,255,255,0.2) transparent;
    }

    .sidebar::-webkit-scrollbar {
      width: 5px;
    }

    .sidebar::-webkit-scrollbar-track {
      background: transparent;
    }

    .sidebar::-webkit-scrollbar-thumb {
      background-color: rgba(255,255,255,0.2);
      border-radius: 20px;
    }

    .sidebar.collapsed { 
      width: 80px;
    }

    .sidebar .logo-details { 
      height: 70px; 
      display: flex; 
      align-items: center; 
      padding: 0 20px; 
      background: var(--bg);
      border-bottom: 1px solid var(--border-color);
      margin-bottom: 10px;
      position: sticky;
      top: 0;
      z-index: 2;
    }

    .sidebar .logo_name { 
      font-size: 22px; 
      font-weight: 700; 
      margin-left: 15px;
      transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
      opacity: 1;
      color: var(--primary);
    }

    .sidebar .toggle-btn { 
      font-size: 24px; 
      background: none; 
      border: none; 
      color: var(--text-color); 
      cursor: pointer; 
      width: 40px;
      height: 40px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 10px;
      transition: all 0.3s ease;
    }

    .sidebar .toggle-btn:hover {
      background: var(--hover-bg);
      color: var(--primary);
    }

    .nav-links { 
      list-style: none; 
      margin: 15px 12px; 
      padding: 0;
      display: flex;
      flex-direction: column;
      gap: 5px;
    }

    .nav-links li { 
      opacity: 0; 
      animation: slideInNav 0.5s ease forwards; 
      animation-delay: calc(var(--i) * 0.1s);
      position: relative;
    }

    .nav-links li:nth-child(1) { --i: 1; }
    .nav-links li:nth-child(2) { --i: 2; }
    .nav-links li:nth-child(3) { --i: 3; }
    .nav-links li:nth-child(4) { --i: 4; }
    .nav-links li:nth-child(5) { --i: 5; }
    .nav-links li:nth-child(6) { --i: 6; }

    .nav-links li a { 
      display: flex; 
      align-items: center; 
      padding: 12px 15px; 
      color: var(--text-color); 
      text-decoration: none; 
      font-size: 15px;
      font-weight: 500;
      border-radius: 12px; 
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      position: relative;
      overflow: hidden;
    }

    .nav-links li a::before {
      content: '';
      position: absolute;
      left: 0;
      top: 0;
      width: 0;
      height: 100%;
      background: linear-gradient(90deg, var(--hover-bg), transparent);
      transition: width 0.3s ease;
    }

    .nav-links li a:hover::before {
      width: 100%;
    }

    .nav-links li a i { 
      font-size: 20px;
      min-width: 45px;
      height: 45px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 10px;
      margin-right: 10px;
      transition: all 0.3s ease;
      background: var(--hover-bg);
    }

    .nav-links li a:hover, 
    .nav-links li a.active { 
      color: var(--active-color);
      background: var(--hover-bg);
      transform: translateX(5px);
    }

    .nav-links li a:hover i, 
    .nav-links li a.active i {
      background: var(--primary);
      color: #fff;
    }

    .sidebar.collapsed .logo_name, 
    .sidebar.collapsed .sidebar-text { 
      opacity: 0;
      width: 0;
      display: none;
    }

    .sidebar.collapsed .nav-links li a {
      justify-content: center;
      padding: 12px;
    }

    .sidebar.collapsed .nav-links li a i {
      margin: 0;
    }

    /* Dark Mode Sidebar */
    body.dark-mode .sidebar {
      background: #1a1f2d;
      color: #fff;
    }
    
    body.dark-mode .sidebar .logo-details {
      background: rgba(255,255,255,0.03);
      backdrop-filter: blur(10px);
      border-bottom-color: rgba(255,255,255,0.1);
    }

    body.dark-mode .sidebar .logo_name {
      background: linear-gradient(45deg, #3b82f6, #60a5fa);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }

    body.dark-mode .sidebar .toggle-btn {
      color: #fff;
    }

    body.dark-mode .sidebar .toggle-btn:hover {
      background: rgba(255,255,255,0.1);
    }

    body.dark-mode .nav-links li a {
      color: rgba(255,255,255,0.7);
    }

    body.dark-mode .nav-links li a i {
      background: rgba(255,255,255,0.05);
    }

    body.dark-mode .nav-links li a:hover,
    body.dark-mode .nav-links li a.active {
      color: #fff;
      background: rgba(59,130,246,0.1);
    }

    body.dark-mode .nav-links li a:hover i,
    body.dark-mode .nav-links li a.active i {
      background: #3b82f6;
      color: #fff;
    }

    /* Mobile/off-canvas sidebar */
    .sidebar-backdrop { 
      position: fixed; 
      inset: 0; 
      background: rgba(0,0,0,0.25); 
      backdrop-filter: blur(2px); 
      z-index: 98; 
      display: none; 
      transition: opacity 0.3s ease; 
    }

    .sidebar-backdrop.show { 
      display: block; 
      opacity: 1; 
    }

    body.no-scroll { 
      overflow: hidden; 
    }

    @keyframes slideInSidebar {
      from { transform: translateX(-100%); }
      to { transform: translateX(0); }
    }

    @keyframes slideInNav {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }

    @media (max-width: 992px) {
    .sidebar {
        transform: translateX(-100%); 
        width: 260px; 
        left: 0; 
        top: 0; 
        height: 100vh;
        z-index: 99;
      }
      .sidebar.open { transform: translateX(0); }
      .sidebar.collapsed { width: 260px; }
      .main-content { 
        margin-left: 0 !important; 
        padding: 15px; 
      }
    }

    /* Main Content Shell */
    .main-content {
      margin-left: 280px;
      padding: 32px;
      flex-grow: 1;
      height: 100vh;
      transition: all 0.3s ease;
      overflow: hidden;
      display: flex;
      flex-direction: column;
      gap: 24px;
      background: var(--main-bg);
    }

    .sidebar.collapsed + .main-content {
      margin-left: 80px;
    }

    @media (max-width: 768px) {
      .main-content {
        padding: 20px;
      }
    }

    /* Modern Header */
    .header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 20px;
      padding: 24px;
      border-radius: 24px;
      background: var(--panel);
      border: 1px solid var(--border);
      margin-bottom: 20px;
      flex-wrap: wrap;
    }

    .header-title {
      display: flex;
      align-items: center;
      gap: 16px;
    }

    .header-icon {
      width: 48px;
      height: 48px;
      background: var(--primary);
      border-radius: 16px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 24px;
    }

    .header-content h5 {
      font-size: 20px;
      font-weight: 600;
      color: var(--text);
      margin: 0;
      margin-bottom: 4px;
    }

    .header-content .text-muted {
      font-size: 14px;
      color: var(--muted);
    }

    .header-controls {
      display: flex;
      align-items: center;
      gap: 15px;
      flex-wrap: wrap;
    }

    /* Modern Search */
    .search-wrap {
      position: relative;
      min-width: 300px;
    }

    .search-wrap .bi-search {
      position: absolute;
      left: 15px;
      top: 50%;
      transform: translateY(-50%);
      color: var(--muted);
      font-size: 16px;
    }

    .search-input {
      width: 100%;
      padding: 12px 15px 12px 45px;
      border-radius: 14px;
      border: 1px solid var(--border);
      background: var(--panel);
      color: var(--text);
      font-size: 14px;
      transition: all 0.3s ease;
    }

    .search-input:focus {
      border-color: var(--primary);
      box-shadow: 0 0 0 4px rgba(108,99,255,0.1);
    }

    .search-input::placeholder {
      color: var(--muted);
    }

    /* Modern Filter Pills */
    .filter-pills {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
    }

    .filter-pills .btn {
      padding: 10px 16px;
      border-radius: 12px;
      font-size: 14px;
      font-weight: 500;
      border: 1px solid var(--border);
      background: var(--panel);
      color: var(--text);
      transition: all 0.3s ease;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .filter-pills .btn i {
      font-size: 16px;
      color: var(--muted);
      transition: all 0.3s ease;
    }

    .filter-pills .btn:hover {
      background: var(--hover-bg);
      border-color: var(--border);
      transform: translateY(-1px);
    }

    .filter-pills .btn.active {
      background: var(--primary);
      color: white;
      border-color: var(--primary);
    }

    .filter-pills .btn.active i {
      color: white;
    }

    /* Dark Mode */
    body.dark-mode .header {
      background: var(--panel);
      border-color: rgba(255,255,255,0.1);
    }

    body.dark-mode .search-input {
      background: rgba(255,255,255,0.03);
      border-color: rgba(255,255,255,0.1);
    }

    body.dark-mode .search-input:focus {
      border-color: var(--primary);
      box-shadow: 0 0 0 4px rgba(108,99,255,0.1);
    }

    body.dark-mode .filter-pills .btn {
      background: rgba(255,255,255,0.03);
      border-color: rgba(255,255,255,0.1);
    }

    body.dark-mode .filter-pills .btn:hover {
      background: rgba(255,255,255,0.05);
    }

    /* Responsive */
    @media (max-width: 992px) {
      .header {
        padding: 20px;
      }

      .search-wrap {
        min-width: 250px;
      }
    }

    @media (max-width: 768px) {
      .header {
        flex-direction: column;
        align-items: stretch;
        gap: 15px;
      }

      .header-controls {
        flex-direction: column;
        align-items: stretch;
      }

      .search-wrap {
        min-width: 100%;
      }

      .filter-pills {
        justify-content: stretch;
      }

      .filter-pills .btn {
        flex: 1;
        justify-content: center;
      }
    }

    /* Modern Content Area */
    .content-area {
      flex: 1;
      background: var(--panel);
      border: 1px solid var(--border);
      border-radius: 24px;
      padding: 24px;
      display: flex;
      flex-direction: column;
      gap: 24px;
      overflow: hidden;
    }

    .lane-title {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 8px;
    }

    .lane-title span {
      font-size: 18px;
      font-weight: 600;
      color: var(--text);
    }

    .lane-title .small {
      font-size: 14px;
      color: var(--muted);
    }

    /* Horizontal Scrolling Lane */
    .lane {
      flex: 1;
      display: flex;
      gap: 20px;
      overflow-x: auto;
      overflow-y: hidden;
      padding: 4px 8px 20px;
      margin: 0 -8px;
      scroll-behavior: smooth;
      scroll-snap-type: x mandatory;
    }

    .lane::-webkit-scrollbar {
      height: 8px;
    }

    .lane::-webkit-scrollbar-track {
      background: var(--hover-bg);
      border-radius: 4px;
    }

    .lane::-webkit-scrollbar-thumb {
      background: var(--border);
      border-radius: 4px;
      transition: all 0.3s ease;
    }

    .lane::-webkit-scrollbar-thumb:hover {
      background: var(--muted);
    }

    /* Modern Resource Cards */
    .resource-card {
      min-width: 320px;
      max-width: 320px;
      flex: 0 0 auto;
      background: var(--panel);
      border: 1px solid var(--border);
      border-radius: 20px;
      overflow: hidden;
      display: flex;
      flex-direction: column;
      transition: all 0.3s ease;
      scroll-snap-align: start;
    }

    .resource-card:hover {
      transform: translateY(-2px);
      border-color: rgba(var(--primary-rgb), 0.3);
      box-shadow: 0 8px 24px rgba(var(--shadow-rgb), 0.1);
    }

    .resource-media {
      height: 140px;
      display: flex;
      align-items: center;
      justify-content: center;
      background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.1) 0%, rgba(var(--primary-rgb), 0.05) 100%);
      position: relative;
      overflow: hidden;
    }

    .resource-media::before {
      content: '';
      position: absolute;
      width: 200%;
      height: 200%;
      top: -50%;
      left: -50%;
      background: radial-gradient(circle at center, rgba(var(--primary-rgb), 0.1) 0%, transparent 70%);
      animation: rotate 10s linear infinite;
      pointer-events: none;
    }

    .resource-media i {
      font-size: 48px;
      color: var(--primary);
      position: relative;
      z-index: 1;
    }

    .resource-body {
      padding: 20px;
      display: flex;
      flex-direction: column;
      gap: 12px;
    }

    .resource-title {
      font-size: 18px;
      font-weight: 600;
      color: var(--text);
      margin: 0;
    }

    .resource-desc {
      font-size: 14px;
      color: var(--muted);
      line-height: 1.5;
      margin: 0;
    }

    .resource-tags {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
      margin-top: 4px;
    }

    .tag {
      font-size: 12px;
      font-weight: 500;
      padding: 6px 12px;
      border-radius: 10px;
      background: var(--hover-bg);
      color: var(--text);
      transition: all 0.3s ease;
    }

    .tag.primary {
      background: rgba(var(--primary-rgb), 0.1);
      color: var(--primary);
    }

    .tag.alt {
      background: rgba(var(--success-rgb), 0.1);
      color: var(--success);
    }

    .resource-actions {
      display: flex;
      gap: 8px;
      margin-top: 12px;
    }

    .resource-actions .btn {
      flex: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      padding: 12px;
      border-radius: 14px;
      font-size: 14px;
      font-weight: 500;
      transition: all 0.3s ease;
    }

    .resource-actions .btn i {
      font-size: 18px;
    }

    .resource-actions .btn:hover {
      transform: translateY(-1px);
    }

    .resource-actions .btn-primary:hover {
      box-shadow: 0 4px 12px rgba(var(--primary-rgb), 0.2);
    }

    .resource-actions .btn-success:hover {
      box-shadow: 0 4px 12px rgba(var(--success-rgb), 0.2);
    }

    .resource-actions .btn-secondary:hover {
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    /* Dark Mode Adjustments */
    body.dark-mode .resource-card {
      background: var(--panel);
      border-color: rgba(255, 255, 255, 0.1);
    }

    body.dark-mode .resource-card:hover {
      border-color: rgba(var(--primary-rgb), 0.3);
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
    }

    body.dark-mode .resource-media {
      background: linear-gradient(135deg, rgba(var(--primary-rgb), 0.15) 0%, rgba(var(--primary-rgb), 0.05) 100%);
    }

    body.dark-mode .tag {
      background: rgba(255, 255, 255, 0.05);
    }

    /* Responsive Adjustments */
    @media (max-width: 768px) {
      .content-area {
        padding: 16px;
      }

      .resource-card {
        min-width: 280px;
        max-width: 280px;
      }

      .resource-media {
        height: 120px;
      }

      .resource-body {
        padding: 16px;
      }
    }

    @keyframes rotate {
      from { transform: rotate(0deg); }
      to { transform: rotate(360deg); }
    }

    @media (max-width: 1200px) { .search-input { width: 240px; } }
    @media (max-width: 992px)  { .search-input { width: 200px; } .header { flex-wrap: wrap; } }
    @media (max-width: 768px)  { .search-input { width: 160px; } }
  </style>
</head>
<body>

<div class="wrapper">
  <!-- Modern Sidebar -->
  <div class="sidebar" id="sidebar">
    <div class="logo-details">
      <i class="bi bi-list toggle-btn" onclick="toggleSidebar()"></i>
      <span class="logo_name">MindCare</span>
    </div>
    <div class="initials" title="<?= htmlspecialchars($name) ?>"><?= $initials ?></div>
    <ul class="nav-links">
      <li><a href="../dashboard/user_dashboard.php"><i class="bi bi-house"></i> <span class="sidebar-text">Dashboard</span></a></li>
      <li><a href="../chatbot/index.php"><i class="bi bi-chat-dots"></i> <span class="sidebar-text">Chatbot</span></a></li>
      <li><a href="../journal/journal.php"><i class="bi bi-journal-text"></i> <span class="sidebar-text">Journal</span></a></li>
      <li><a href="resources.php" class="active"><i class="bi bi-lightbulb"></i> <span class="sidebar-text">Resources</span></a></li>
      <li><a href="../moodtracker/emotional_analysis.php"><i class="bi bi-activity"></i> <span class="sidebar-text">Emotional Analysis</span></a></li>
      <li><a href="../auth/logout.php" id="logoutLink"><i class="bi bi-box-arrow-right"></i> <span class="sidebar-text">Logout</span></a></li>
      <li><a href="#" onclick="toggleDarkMode()"><i class="bi bi-moon-stars"></i> <span class="sidebar-text">Dark Mode</span></a></li>
    </ul>
  </div>
  <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

  <style>
    /* User Initials */
    .sidebar .initials { 
      width: 45px; 
      height: 45px; 
      background: var(--primary);
      color: #fff; 
      border-radius: 12px; 
      display: flex; 
      align-items: center; 
      justify-content: center; 
      font-weight: 600; 
      font-size: 18px;
      margin: 20px auto; 
      transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }

    .sidebar .initials:hover { 
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(0,0,0,0.15);
    }

    .sidebar.collapsed .initials {
      width: 40px;
      height: 40px;
      font-size: 16px;
      border-radius: 10px;
    }

    body.dark-mode .sidebar .initials {
      background: var(--primary);
      box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    }
  </style>

  <!-- Main Content -->
  <div class="main-content">
    <!-- Modern Header -->
    <div class="header">
      <div class="header-title">
        <div class="header-icon">
          <i class="bi bi-book"></i>
        </div>
        <div class="header-content">
          <h5>Mental Health Resources</h5>
          <div class="text-muted">Curated articles, videos, and toolkits — tailored for your wellness journey</div>
        </div>
      </div>
      <div class="header-controls">
        <div class="filter-pills" role="group" aria-label="Filter">
          <button type="button" class="btn active" data-filter="all">
            <i class="bi bi-collection"></i>
            <span>All</span>
          </button>
          <button type="button" class="btn" data-filter="article">
            <i class="bi bi-journal-text"></i>
            <span>Articles</span>
          </button>
          <button type="button" class="btn" data-filter="video">
            <i class="bi bi-play-circle"></i>
            <span>Videos</span>
          </button>
          <button type="button" class="btn" data-filter="toolkit">
            <i class="bi bi-tools"></i>
            <span>Toolkits</span>
          </button>
          <button type="button" class="btn" data-filter="emergency">
            <i class="bi bi-shield-check"></i>
            <span>Emergency</span>
          </button>
        </div>
        <div class="search-wrap">
          <i class="bi bi-search"></i>
          <input id="searchInput" type="text" class="form-control search-input" placeholder="Search resources..." />
        </div>
      </div>
    </div>

    <!-- Content -->
    <div class="content-area">
      <div class="lane-title">
        <span>Explore</span>
        <span class="small">Scroll horizontally for more</span>
      </div>
      <div id="lane" class="lane">
        <!-- Article: Anxiety -->
        <div class="resource-card" data-type="article" data-title="Understanding Anxiety HelpGuide">
          <div class="resource-media">
            <i class="bi bi-journal-text"></i>
          </div>
          <div class="resource-body">
            <div class="resource-title">Understanding Anxiety</div>
            <div class="resource-desc">What anxiety is and practical ways to manage it.</div>
            <div class="resource-tags">
              <span class="tag">Article</span>
              <span class="tag alt">Coping</span>
            </div>
            <div class="resource-actions">
              <a href="https://www.helpguide.org/articles/anxiety/anxiety-disorders-and-anxiety-attacks.htm" target="_blank" class="btn btn-primary w-100"><i class="bi bi-box-arrow-up-right me-1"></i>Read</a>
            </div>
          </div>
        </div>

        <!-- Video: Mindfulness -->
        <div class="resource-card" data-type="video" data-title="Mindfulness Meditation 10-minute YouTube Calm">
          <div class="resource-media">
            <i class="bi bi-play-circle"></i>
          </div>
          <div class="resource-body">
            <div class="resource-title">Mindfulness Meditation</div>
            <div class="resource-desc">A 10-minute guided practice to calm your mind.</div>
            <div class="resource-tags">
              <span class="tag">Video</span>
              <span class="tag alt">Breathing</span>
            </div>
            <div class="resource-actions">
              <a href="https://www.youtube.com/watch?v=inpok4MKVLM" target="_blank" class="btn btn-success w-100"><i class="bi bi-play-fill me-1"></i>Watch</a>
            </div>
          </div>
        </div>

        <!-- Toolkit PDF -->
        <div class="resource-card" data-type="toolkit" data-title="Mental Health Toolkit PDF Download">
          <div class="resource-media">
            <i class="bi bi-folder2-open"></i>
          </div>
          <div class="resource-body">
            <div class="resource-title">Mental Health Toolkit</div>
            <div class="resource-desc">Download practical tips, exercises, and tools.</div>
            <div class="resource-tags">
              <span class="tag">Toolkit</span>
              <span class="tag alt">PDF</span>
            </div>
            <div class="resource-actions">
              <a href="../assets/resources/mental_health_toolkit.pdf" class="btn btn-secondary w-100" download><i class="bi bi-download me-1"></i>Download</a>
            </div>
          </div>
        </div>

        
        <!-- Additional curated placeholders (unique layout maintains single row) -->
        <div class="resource-card" data-type="article" data-title="Sleep Hygiene Better Rest Guide">
          <div class="resource-media">
            <i class="bi bi-moon-stars"></i>
          </div>
          <div class="resource-body">
            <div class="resource-title">Sleep Hygiene</div>
            <div class="resource-desc">Habits and routines to improve quality sleep.</div>
            <div class="resource-tags">
              <span class="tag">Article</span>
              <span class="tag alt">Routine</span>
            </div>
            <div class="resource-actions">
              <a href="https://www.sleepfoundation.org/sleep-hygiene" target="_blank" class="btn btn-primary w-100"><i class="bi bi-box-arrow-up-right me-1"></i>Read</a>
            </div>
          </div>
        </div>

        <div class="resource-card" data-type="video" data-title="Box Breathing Stress Relief">
          <div class="resource-media">
            <i class="bi bi-wind"></i>
          </div>
          <div class="resource-body">
            <div class="resource-title">Box Breathing</div>
            <div class="resource-desc">A simple technique for quick stress relief.</div>
            <div class="resource-tags">
              <span class="tag">Video</span>
              <span class="tag alt">Calm</span>
            </div>
            <div class="resource-actions">
              <a href="https://www.youtube.com/results?search_query=box+breathing+exercise" target="_blank" class="btn btn-success w-100"><i class="bi bi-play-fill me-1"></i>Watch</a>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>
</div>

<script>
function toggleSidebar() {
  const sb = document.getElementById('sidebar');
  const backdrop = document.getElementById('sidebarBackdrop');
  const isMobile = window.matchMedia('(max-width: 992px)').matches;
  if (isMobile) {
    const willOpen = !sb.classList.contains('open');
    sb.classList.toggle('open', willOpen);
    document.body.classList.toggle('no-scroll', willOpen);
    if (backdrop) backdrop.classList.toggle('show', willOpen);
  } else {
    sb.classList.toggle('collapsed');
  }
}

// Backdrop + resize
(function(){
  const backdrop = document.getElementById('sidebarBackdrop');
  if (backdrop) {
    backdrop.addEventListener('click', () => {
      const sb = document.getElementById('sidebar');
      sb.classList.remove('open');
      document.body.classList.remove('no-scroll');
      backdrop.classList.remove('show');
    });
  }
  window.addEventListener('resize', () => {
    const sb = document.getElementById('sidebar');
    const isMobile = window.matchMedia('(max-width: 992px)').matches;
    if (!isMobile) {
      sb.classList.remove('open');
      document.body.classList.remove('no-scroll');
      if (backdrop) backdrop.classList.remove('show');
    }
  });
})();

function toggleDarkMode() {
  document.body.classList.toggle('dark-mode');
  localStorage.setItem('darkMode', document.body.classList.contains('dark-mode'));
}

window.addEventListener('DOMContentLoaded', () => {
  if (localStorage.getItem('darkMode') === 'true') {
    document.body.classList.add('dark-mode');
  }
});

  // Filtering + search
  const filterButtons = document.querySelectorAll('.filter-pills .btn');
  const lane = document.getElementById('lane');
  const cards = Array.from(document.querySelectorAll('.resource-card'));
  const searchInput = document.getElementById('searchInput');

  function applyFilters() {
    const activeBtn = document.querySelector('.filter-pills .btn.active');
    const type = activeBtn ? activeBtn.getAttribute('data-filter') : 'all';
    const q = (searchInput.value || '').trim().toLowerCase();

    let visibleCount = 0;
    cards.forEach(card => {
      const t = card.getAttribute('data-type');
      const title = (card.getAttribute('data-title') || '').toLowerCase();
      const matchType = (type === 'all') || (t === type);
      const matchText = !q || title.includes(q);
      const show = matchType && matchText;
      card.style.display = show ? '' : 'none';
      if (show) visibleCount++;
    });

    // If nothing visible, show a simple empty state inline
    let empty = document.getElementById('emptyLane');
    if (!visibleCount) {
      if (!empty) {
        empty = document.createElement('div');
        empty.id = 'emptyLane';
        empty.className = 'd-flex align-items-center justify-content-center text-muted';
        empty.style.minWidth = '100%';
        empty.innerHTML = '<div class="text-center"><i class="bi bi-inbox fs-3"></i><div>No resources found</div></div>';
        lane.appendChild(empty);
      }
    } else if (empty) {
      empty.remove();
    }
  }

  filterButtons.forEach(btn => {
    btn.addEventListener('click', () => {
      filterButtons.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      applyFilters();
    });
  });

  searchInput.addEventListener('input', applyFilters);
  applyFilters();
</script>
<script src="/MindCare-AI/assets/js/floating_mood_widget.js?v=1" defer></script>
</body>
</html>
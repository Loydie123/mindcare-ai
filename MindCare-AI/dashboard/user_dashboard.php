<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$name = $_SESSION['name'] ?? 'User';
$email = $_SESSION['email'] ?? 'user@example.com';
$userId = (int)($_SESSION['user_id'] ?? 0);

// Get initials
$initials = '';
if (!empty($name)) {
    $parts = explode(' ', $name);
    $initials = count($parts) > 1
        ? strtoupper(substr($parts[0], 0, 1) . substr($parts[count($parts) - 1], 0, 1))
        : strtoupper(substr($name, 0, 2));
}

// Dynamic greeting based on time
date_default_timezone_set('America/Los_Angeles'); // Set to PST
$hour = (int)date('H');
$greeting = $hour < 12 ? 'Good Morning' : ($hour < 17 ? 'Good Afternoon' : 'Good Evening');

require_once '../config/dbcon.php';

// Fetch emergency numbers
$emergencyNumbers = $conn->query("SELECT * FROM emergency_numbers");

// Fetch facebook pages
$facebookPages = $conn->query("SELECT * FROM facebook_pages");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>User Dashboard | MindCare AI</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../assets/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <script src="../assets/js/bootstrap.bundle.min.js"></script>
<link rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">

<script defer
        src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script defer src="./assets/js/floating_mood_widget.js"></script>

  <style>
    /* Loader + bubbles */
    #loadingOverlay {
      position: fixed;
      top: 0; left: 0;
      width: 100%; height: 100%;
      display: flex;
      align-items: center;
      justify-content: center;
      flex-direction: column;
      background: rgba(0, 0, 0, 0.6);
      backdrop-filter: blur(8px);
      z-index: 2000;
      color: #fff;
      animation: fadeInOverlay 0.5s ease-in;
    }
    #loadingOverlay .circle img {
      width: 250px;
      height: auto;
      animation: pulse 2s infinite ease-in-out;
    }
    #loadingOverlay h1 { margin-top: 20px; font-size: 28px; font-weight: 700; }
    #loadingOverlay p { font-size: 16px; color: #ddd; margin-top: 8px; }
    .dots::after {
      content: "";
      animation: dots 1.5s steps(4, end) infinite;
    }
    @keyframes fadeInOverlay {
      from { opacity: 0; }
      to { opacity: 1; }
    }
    @keyframes pulse {
      0% { transform: scale(1); }
      50% { transform: scale(1.05); }
      100% { transform: scale(1); }
    }
    @keyframes dots {
      0% { content: ""; }
      25% { content: "."; }
      50% { content: ".."; }
      75% { content: "..."; }
      100% { content: ""; }
    }
    .bubble {
      position: absolute;
      bottom: -50px;
      width: 15px; height: 15px;
      background: rgba(0, 200, 255, 0.4);
      border-radius: 50%;
      animation: rise 6s infinite ease-in;
    }
    @keyframes rise {
      0% { transform: translateY(0) scale(1); opacity: 0.6; }
      100% { transform: translateY(-120vh) scale(0.3); opacity: 0; }
    }

    /* New Color Palette and Layout */
    :root {
      --primary: #007bff; /* A vibrant blue */
      --secondary: #6c757d; /* Gray for subtle elements */
      --accent: #17a2b8; /* A secondary blue */
      --hover-bg: #e2f1ff; /* Light blue hover effect */
      --active-color: #0056b3; /* Darker blue for active links */
      --text-color: #212529; /* Black for text */
      --card-text-color: #495057; /* Dark gray for card text */
      --bg: #f8f9fa; /* Light gray background */
      --card-bg: #ffffff; /* White card background */
      --border-color: #dee2e6; /* Light border color */
    }

    body { 
      margin: 0; 
      font-family: 'Segoe UI', sans-serif; 
      background: var(--bg); 
      color: var(--text-color); 
      display: flex; 
      transition: background 0.5s ease; 
      height: 100vh; 
      overflow: hidden; 
    }

    /* Modern Dashboard Banner */
    .dashboard-banner {
      background: linear-gradient(135deg, #fff 0%, #f8faff 100%);
      border-radius: 24px;
      padding: 40px 30px;
      margin: 20px 20px 30px;
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.03);
      animation: fadeInHero 0.8s cubic-bezier(0.4, 0, 0.2, 1);
      display: flex;
      align-items: center;
      justify-content: center;
      text-align: center;
      position: relative;
      overflow: hidden;
      border: 1px solid rgba(255, 255, 255, 0.7);
    }
    .dashboard-banner::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: linear-gradient(45deg, rgba(59,130,246,0.08) 0%, rgba(59,130,246,0) 100%);
      z-index: 1;
    }
    .dashboard-banner .banner-content {
      position: relative;
      z-index: 2;
      flex: 1;
      text-align: center;
      min-width: 200px;
    }
    .dashboard-banner h2 {
      font-size: 2.5em;
      font-weight: 800;
      background: linear-gradient(135deg, var(--primary) 0%, #60a5fa 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      margin-bottom: 15px;
      opacity: 0;
      animation: fadeInText 0.6s cubic-bezier(0.4, 0, 0.2, 1) forwards;
      animation-delay: 0.2s;
      letter-spacing: -0.02em;
    }
    .dashboard-banner p {
      font-size: 1.1em;
      color: var(--card-text-color);
      max-width: 600px;
      margin: 0 auto 15px;
      opacity: 0;
      animation: fadeInText 0.6s cubic-bezier(0.4, 0, 0.2, 1) forwards;
      animation-delay: 0.4s;
      line-height: 1.6;
    }
    .dashboard-banner::after {
      content: '';
      position: absolute;
      width: 600px;
      height: 600px;
      background: radial-gradient(circle, rgba(59,130,246,0.1) 0%, rgba(59,130,246,0) 70%);
      border-radius: 50%;
      top: -300px;
      right: -300px;
      z-index: 1;
    }
    
    @keyframes fadeInHero {
      from { opacity: 0; transform: translateY(-20px); }
      to { opacity: 1; transform: translateY(0); }
    }

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
    .nav-links li a:hover, .nav-links li a.active { 
      color: var(--active-color);
      background: var(--hover-bg);
      transform: translateX(5px);
    }
    .nav-links li a:hover i, .nav-links li a.active i {
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
    .sidebar.collapsed .initials {
      width: 40px;
      height: 40px;
      font-size: 16px;
      border-radius: 10px;
    }

    @keyframes slideInSidebar {
      from { transform: translateX(-100%); }
      to { transform: translateX(0); }
    }
    @keyframes slideInNav {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }

    /* Modern Main Content */
    .main { 
      margin-left: 280px; 
      padding: 0 10px; 
      flex: 1; 
      transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); 
      background: var(--bg); 
      animation: fadeInMain 0.7s cubic-bezier(0.4, 0, 0.2, 1); 
      height: 100vh; 
      overflow: hidden; 
      display: flex;
      flex-direction: column;
    }
    .sidebar.collapsed ~ .main { margin-left: 80px; }
    .dashboard-grid { 
      display: grid; 
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); 
      gap: 25px; 
      padding: 0 10px 20px; 
      flex: 1; 
      overflow-y: auto;
      align-items: start;
    }
    .card { 
      border-radius: 24px; 
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.03); 
      border: 1px solid rgba(255, 255, 255, 0.7);
      transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); 
      color: var(--card-text-color); 
      background: linear-gradient(135deg, #fff 0%, #f8faff 100%);
      overflow: hidden; 
      position: relative; 
      opacity: 0; 
      animation: cardFadeIn 0.5s cubic-bezier(0.4, 0, 0.2, 1) forwards; 
      animation-delay: calc(var(--i) * 0.1s); 
      height: 100%;
    }
    .card:nth-child(1) { --i: 1; }
    .card:nth-child(2) { --i: 2; }
    .card:nth-child(3) { --i: 3; }
    .card:nth-child(4) { --i: 4; }
    .card:nth-child(5) { --i: 5; }
    .card::before {
      content: '';
      position: absolute;
      inset: 0;
      background: linear-gradient(45deg, rgba(59,130,246,0.08) 0%, rgba(59,130,246,0) 100%);
      opacity: 0;
      transition: opacity 0.4s ease;
    }
    .card::after {
      content: '';
      position: absolute;
      width: 300px;
      height: 300px;
      background: radial-gradient(circle, rgba(59,130,246,0.1) 0%, rgba(59,130,246,0) 70%);
      border-radius: 50%;
      top: -150px;
      right: -150px;
      opacity: 0;
      transition: opacity 0.4s ease;
    }
    .card:hover { 
      transform: translateY(-5px); 
      box-shadow: 0 30px 60px rgba(0, 0, 0, 0.05); 
    }
    .card:hover::before,
    .card:hover::after { 
      opacity: 1; 
    }
    .card-body { 
      padding: 30px; 
      text-align: center; 
      position: relative;
      z-index: 1;
    }
    .card-title { 
      font-size: 1.4em; 
      font-weight: 700; 
      margin-bottom: 15px; 
      display: flex; 
      align-items: center; 
      gap: 12px; 
      color: var(--primary); 
      justify-content: center;
    }
    .card-title i {
      font-size: 1.4em;
      background: linear-gradient(135deg, var(--primary) 0%, #60a5fa 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }
    .card-text {
      font-size: 1em;
      margin-bottom: 25px;
      color: var(--card-text-color);
      opacity: 0;
      animation: fadeInText 0.6s ease forwards;
      animation-delay: calc(var(--i) * 0.2s);
      line-height: 1.6;
    }
    .card .btn {
      background: linear-gradient(135deg, var(--primary) 0%, #60a5fa 100%);
      color: white;
      padding: 12px 24px;
      border-radius: 14px;
      font-weight: 600;
      font-size: 0.95em;
      transition: all 0.3s ease;
      border: none;
      position: relative;
      overflow: hidden;
    }
    .card .btn::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, rgba(255,255,255,0) 0%, rgba(255,255,255,0.2) 50%, rgba(255,255,255,0) 100%);
      transform: translateX(-100%);
      transition: transform 0.6s ease;
    }
    .card .btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 20px rgba(59,130,246,0.3);
    }
    .card .btn:hover::before {
      transform: translateX(100%);
    }
    .btn {
      border: none;
      padding: 10px 20px;
      font-size: 0.95em;
      font-weight: 500;
      border-radius: 25px;
      transition: transform 0.2s ease, background-color 0.3s ease, box-shadow 0.3s ease;
      background: var(--primary);
      color: white;
      position: relative;
      overflow: hidden;
    }
    .btn::after {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
      transition: left 0.4s ease;
    }
    .btn:hover::after {
      left: 100%;
    }
    .btn:hover {
      transform: scale(1.05);
      background-color: var(--active-color);
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    }
    .mood-tracker-card { 
      background-color: #f1f1f1; 
      grid-column: span 2; 
    }
    .mood-tracker-card .card-header { 
      background: #fff; 
      padding: 10px 15px; 
      font-weight: 600; 
      border-bottom: 1px solid #ddd; 
      display: flex; 
      justify-content: space-between; 
      align-items: center; 
      gap: 8px; 
      flex-wrap: wrap; 
    }
    #moodContainer { 
      display: none; 
      flex-direction: column; 
      align-items: center; 
      padding: 15px; 
    }
    #cameraFeed { 
      max-width: 100%; 
      border-radius: 8px; 
      margin-bottom: 10px; 
      border: 2px solid #ddd; 
      transition: transform 0.3s ease; 
    }
    #cameraFeed:hover { transform: scale(1.02); }

    /* Mood meta */
    .mood-meta { 
      display: flex; 
      gap: 8px; 
      align-items: center; 
      flex-wrap: wrap; 
    }
    .badge-pill { 
      border-radius: 50px; 
      padding: 5px 10px; 
      font-size: 0.85rem; 
      transition: transform 0.2s ease; 
    }
    .badge-pill:hover { transform: scale(1.1); }

    /* Modern Emergency Button + Modal */
    .floating-emergency-btn { 
      position: fixed; 
      bottom: 30px; 
      right: 30px; 
      background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); 
      color: white; 
      border: none; 
      border-radius: 20px; 
      width: 60px; 
      height: 60px; 
      display: flex; 
      align-items: center; 
      justify-content: center; 
      font-size: 24px; 
      cursor: pointer; 
      box-shadow: 0 8px 30px rgba(220,38,38,0.3); 
      z-index: 1000; 
      transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
      overflow: hidden;
    }
    .floating-emergency-btn::before {
      content: '';
      position: absolute;
      inset: 0;
      background: linear-gradient(45deg, rgba(255,255,255,0.1), rgba(255,255,255,0));
      opacity: 0;
      transition: opacity 0.4s ease;
    }
    .floating-emergency-btn:hover { 
      transform: translateY(-5px); 
      box-shadow: 0 15px 40px rgba(220,38,38,0.4);
    }
    .floating-emergency-btn:hover::before {
      opacity: 1;
    }
    .floating-emergency-btn i {
      transition: transform 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .floating-emergency-btn:hover i {
      transform: scale(1.1);
    }
    .modal-overlay { 
      display: none; 
      position: fixed; 
      z-index: 999; 
      left: 0; 
      top: 0; 
      width: 100%; 
      height: 100%; 
      background-color: rgba(15,23,42,0.7); 
      backdrop-filter: blur(8px);
      animation: fadeInOverlay 0.3s cubic-bezier(0.4, 0, 0.2, 1); 
    }
    .modal-content { 
      background: linear-gradient(135deg, #fff 0%, #f8faff 100%);
      margin: 40px auto; 
      padding: 30px; 
      border-radius: 24px; 
      width: 90%; 
      max-width: 600px; 
      max-height: 85vh; 
      overflow-y: auto; 
      position: relative; 
      text-align: left; 
      animation: slideInModal 0.4s cubic-bezier(0.4, 0, 0.2, 1);
      border: 1px solid rgba(255,255,255,0.7);
      box-shadow: 0 20px 40px rgba(0,0,0,0.1);
    }
    .modal-content::-webkit-scrollbar {
      width: 8px;
    }
    .modal-content::-webkit-scrollbar-track {
      background: transparent;
    }
    .modal-content::-webkit-scrollbar-thumb {
      background: rgba(0,0,0,0.1);
      border-radius: 20px;
    }
    .modal-content h2 {
      font-size: 1.8em;
      font-weight: 800;
      color: #dc2626;
      margin-bottom: 25px;
      display: flex;
      align-items: center;
      gap: 12px;
    }
    .modal-content h4 {
      font-size: 1.2em;
      font-weight: 700;
      color: var(--text-color);
      margin: 20px 0 15px;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .close-btn { 
      position: absolute; 
      top: 20px; 
      right: 20px; 
      width: 36px;
      height: 36px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 20px; 
      color: var(--text-color);
      cursor: pointer; 
      transition: all 0.3s ease;
      border-radius: 12px;
      background: var(--hover-bg);
      border: none;
    }
    .close-btn:hover { 
      background: rgba(220,38,38,0.1);
      color: #dc2626;
      transform: rotate(90deg);
    }
    .info-list { 
      display: flex; 
      flex-direction: column; 
      gap: 15px; 
    }
    .info-item { 
      display: flex; 
      align-items: center; 
      gap: 15px; 
      padding: 15px; 
      border-radius: 16px;
      background: white;
      box-shadow: 0 4px 15px rgba(0,0,0,0.03);
      opacity: 0; 
      animation: fadeInText 0.5s cubic-bezier(0.4, 0, 0.2, 1) forwards; 
      animation-delay: calc(var(--i) * 0.1s);
      transition: all 0.3s ease;
      border: 1px solid rgba(0,0,0,0.05);
    }
    .info-item:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(0,0,0,0.05);
    }
    .info-item:nth-child(1) { --i: 1; }
    .info-item:nth-child(2) { --i: 2; }
    .info-item:nth-child(3) { --i: 3; }
    .info-item img { 
      width: 48px; 
      height: 48px; 
      object-fit: cover; 
      border-radius: 14px; 
      transition: transform 0.3s ease;
      box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    }
    .info-item img:hover { 
      transform: scale(1.1);
    }
    .info-item strong {
      font-size: 1.1em;
      color: var(--text-color);
      margin-bottom: 4px;
      display: block;
    }
    .info-item a {
      color: #3b82f6;
      font-weight: 500;
      transition: color 0.3s ease;
    }
    .info-item a:hover {
      color: #2563eb;
    }
    
    /* Dark mode modal styles */
    body.dark-mode .modal-content {
      background: linear-gradient(135deg, #1e293b 0%, #1a1f2d 100%);
      border-color: rgba(255,255,255,0.1);
    }
    body.dark-mode .modal-content h2 {
      color: #ef4444;
    }
    body.dark-mode .info-item {
      background: rgba(255,255,255,0.03);
      border-color: rgba(255,255,255,0.1);
    }
    body.dark-mode .info-item strong {
      color: #e2e8f0;
    }
    body.dark-mode .info-item a {
      color: #60a5fa;
    }
    body.dark-mode .info-item a:hover {
      color: #93c5fd;
    }
    body.dark-mode .close-btn {
      color: #e2e8f0;
      background: rgba(255,255,255,0.05);
    }
    body.dark-mode .close-btn:hover {
      background: rgba(239,68,68,0.2);
      color: #ef4444;
    }

    @keyframes fadeInMain {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }
    @keyframes cardFadeIn {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }
    @keyframes fadeInText {
      from { opacity: 0; }
      to { opacity: 1; }
    }
    @keyframes slideInModal {
      from { opacity: 0; transform: translateY(-50px); }
      to { opacity: 1; transform: translateY(0); }
    }

    /* Mobile/off-canvas sidebar and responsive tweaks */
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

    /* Dark Mode Styles */
    body.dark-mode {
      background: #0f172a;
      color: #e2e8f0;
    }

    body.dark-mode .dashboard-banner {
      background: linear-gradient(135deg, #1e293b 0%, #1a1f2d 100%);
      border-color: rgba(255, 255, 255, 0.1);
    }

    body.dark-mode .dashboard-banner h2 {
      background: linear-gradient(135deg, #60a5fa 0%, #93c5fd 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }

    body.dark-mode .dashboard-banner p {
      color: #94a3b8;
    }

    body.dark-mode .card {
      background: linear-gradient(135deg, #1e293b 0%, #1a1f2d 100%);
      border-color: rgba(255, 255, 255, 0.1);
    }

    body.dark-mode .card-title {
      color: #e2e8f0;
    }

    body.dark-mode .card-title i {
      background: linear-gradient(135deg, #60a5fa 0%, #93c5fd 100%);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }

    body.dark-mode .card-text {
      color: #94a3b8;
    }

    body.dark-mode .card::before {
      background: linear-gradient(45deg, rgba(96,165,250,0.1) 0%, rgba(96,165,250,0) 100%);
    }

    body.dark-mode .card::after {
      background: radial-gradient(circle, rgba(96,165,250,0.1) 0%, rgba(96,165,250,0) 70%);
    }

    body.dark-mode .card .btn {
      background: linear-gradient(135deg, #3b82f6 0%, #60a5fa 100%);
    }

    body.dark-mode .card .btn:hover {
      box-shadow: 0 10px 20px rgba(96,165,250,0.3);
    }

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

    body.dark-mode .card {
      background: #1e293b;
      border-color: #2d3748;
    }

    body.dark-mode .card-text {
      color: #cbd5e1;
    }

    body.dark-mode .dashboard-banner {
      background: #2d3748;
    }

    body.dark-mode .btn {
      background: #3b82f6;
    }

    body.dark-mode .btn:hover {
      background: #2563eb;
    }

    body.dark-mode .modal-content {
      background: #1e293b;
      color: #e2e8f0;
    }

    body.dark-mode .close-btn {
      color: #e2e8f0;
    }

    body.dark-mode .info-item {
      border-color: #2d3748;
    }

    a {
      text-decoration: none;
    }

    a:hover {
      text-decoration: none;
    }

    @media (max-width: 992px) {
      .dashboard-banner { 
        padding: 15px; 
        flex-direction: column; 
        align-items: center; 
        text-align: center; 
      }
      .dashboard-banner .banner-content { text-align: center; }
      .dashboard-banner img { display: none; }
      .dashboard-banner h2 { font-size: 1.5em; }
      .dashboard-banner p { font-size: 0.9em; max-width: 100%; }
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
      .main { 
        margin-left: 0; 
        padding: 15px; 
        height: 100vh; 
        display: flex; 
        flex-direction: column; 
      }
      .dashboard-grid { 
        grid-template-columns: 1fr; 
        gap: 12px; 
        padding: 10px; 
      }
      .mood-tracker-card { grid-column: auto; }
    }
    @media (max-width: 576px) {
      .dashboard-banner { padding: 10px; }
      .dashboard-banner img { display: none; }
      .dashboard-banner h2 { font-size: 1.3em; }
      .dashboard-banner p { font-size: 0.85em; }
      #loadingOverlay .circle img { width: clamp(140px, 50vw, 200px); }
      #loadingOverlay h1 { font-size: clamp(18px, 5vw, 24px); }
      #loadingOverlay p { font-size: clamp(12px, 3vw, 14px); }
      .floating-emergency-btn { 
        width: 50px; 
        height: 50px; 
        bottom: calc(12px + env(safe-area-inset-bottom)); 
        right: calc(12px + env(safe-area-inset-right)); 
        font-size: 22px; 
      }
      .dashboard-grid { padding: 8px; }
      .card-body { padding: 15px; }
      .card-title { font-size: 1.2em; }
      .btn { padding: 8px 16px; font-size: 0.9em; }
    }

  </style>
</head>
<body>

<div id="loadingOverlay">
  <div class="circle"><img src="../assets/img/brain2.png" alt="MindCare AI Logo"></div>
  <h1>MindCare-AI</h1>
  <p class="dots">Loading</p>
  <div class="bubble" style="left: 20%; animation-delay: 0s; animation-duration: 5s;"></div>
  <div class="bubble" style="left: 40%; animation-delay: 2s; animation-duration: 6s;"></div>
  <div class="bubble" style="left: 60%; animation-delay: 1s; animation-duration: 7s;"></div>
  <div class="bubble" style="left: 80%; animation-delay: 3s; animation-duration: 4s;"></div>
  <div class="bubble" style="left: 50%; animation-delay: 4s; animation-duration: 6s;"></div>
</div>

<div class="sidebar" id="sidebar">
  <div class="logo-details">
    <i class="bi bi-list toggle-btn" onclick="toggleSidebar()"></i>
    <span class="logo_name">MindCare</span>
  </div>
  <div class="initials" title="<?= htmlspecialchars($name) ?>"><?= $initials ?></div>
  <ul class="nav-links">
    <li><a href="#" class="active"><i class="bi bi-house"></i> <span class="sidebar-text">Dashboard</span></a></li>
    <li><a href="../chatbot/index.php"><i class="bi bi-chat-dots"></i> <span class="sidebar-text">Chatbot</span></a></li>
    <li><a href="../journal/journal.php"><i class="bi bi-journal-text"></i> <span class="sidebar-text">Journal</span></a></li>
    <li><a href="../resources/resources.php"><i class="bi bi-lightbulb"></i> <span class="sidebar-text">Resources</span></a></li>
    <li><a href="../moodtracker/emotional_analysis.php"><i class="bi bi-activity"></i> <span class="sidebar-text">Emotional Analysis</span></a></li>
    <li><a href="../auth/logout.php" id="logoutLink"><i class="bi bi-box-arrow-right"></i> <span class="sidebar-text">Logout</span></a></li>
    <li><a href="#" onclick="toggleDarkMode()"><i class="bi bi-moon-stars"></i> <span class="sidebar-text">Dark Mode</span></a></li>
  </ul>
</div>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

<div class="main">
  <div class="dashboard-banner">
    <div class="banner-content">
      <h2><?= $greeting ?>, <?= htmlspecialchars($name) ?>!</h2>
      <p>Take a moment to nurture your mind and find your inner peace.</p>
    </div>
  </div>

  <div class="dashboard-grid">
    <div class="card chat-card">
      <div class="card-body">
        <h5 class="card-title"><i class="bi bi-chat-dots"></i> Chat with Aura</h5>
        <p class="card-text">Your AI mental health assistant</p>
        <a href="../chatbot/index.php" class="btn">Start Chat</a>
      </div>
    </div>
    <div class="card journal-card">
      <div class="card-body">
        <h5 class="card-title"><i class="bi bi-journal-text"></i> Journal</h5>
        <p class="card-text">Write and reflect your thoughts</p>
        <a href="../journal/journal.php" class="btn">Add Entry</a>
      </div>
    </div>
    <div class="card resources-card">
      <div class="card-body">
        <h5 class="card-title"><i class="bi bi-lightbulb"></i> Resources</h5>
        <p class="card-text">Explore mental health guides</p>
        <a href="../resources/resources.php" class="btn">Get Started</a>
      </div>
    </div>
    <div class="card resources-card">
      <div class="card-body">
        <h5 class="card-title"><i class="fa-solid fa-brain"></i> Test Assessment</h5>
        <p class="card-text">Take a psychological test and view your results.</p>
        <a href="../assessment.php" class="btn">Take Assessment</a>
      </div>
    </div>
    <div class="card">
      <div class="card-body">
        <h5 class="card-title"><i class="bi bi-activity"></i> Emotional Analysis</h5>
        <p class="card-text">View your recent emotions and distribution from the mood tracker.</p>
        <a href="../moodtracker/emotional_analysis.php" class="btn">Open Analysis</a>
      </div>
    </div>
  </div>
</div>

<button class="floating-emergency-btn" id="emergencyBtn" title="Emergency Info">
  <i class="bi bi-telephone-fill"></i>
</button>

<div id="emergencyModal" class="modal-overlay">
  <div class="modal-content">
    <span class="close-btn">&times;</span>
    <h2>🚨 Emergency Information</h2>
    <h4>📞 Emergency Contacts</h4>
    <div class="info-list">
      <?php while($row = $emergencyNumbers->fetch_assoc()): ?>
        <div class="info-item">
          <img src="../<?= htmlspecialchars($row['logo']) ?>" alt="Logo">
          <div>
            <strong><?= htmlspecialchars($row['name']) ?></strong><br>
            <?= htmlspecialchars($row['contact_number']) ?>
          </div>
        </div>
      <?php endwhile; ?>
    </div>
    <hr>
    <h4>📘 MindCare Facebook Pages</h4>
    <div class="info-list">
      <?php while($page = $facebookPages->fetch_assoc()): ?>
        <div class="info-item">
          <img src="../<?= htmlspecialchars($page['logo']) ?>" alt="Logo">
          <div>
            <strong><?= htmlspecialchars($page['name']) ?></strong><br>
            <a href="<?= htmlspecialchars($page['page_link']) ?>" target="_blank">Visit Page</a>
          </div>
        </div>
      <?php endwhile; ?>
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

// Loader
window.addEventListener('load', () => {
  setTimeout(() => {
    document.getElementById('loadingOverlay').style.display = 'none';
  }, 1500);
});

// Emergency modal
document.getElementById('emergencyBtn').onclick = () => {
  document.getElementById('emergencyModal').style.display = 'block';
};
document.querySelector('.close-btn').onclick = () => {
  document.getElementById('emergencyModal').style.display = 'none';
};
window.onclick = (event) => {
  if (event.target == document.getElementById('emergencyModal')) {
    document.getElementById('emergencyModal').style.display = 'none';
  }
};

// Logout: stop camera from widget before logging out
document.getElementById('logoutLink').addEventListener('click', async (e) => {
  e.preventDefault();
  const href = e.currentTarget.getAttribute('href');
  if (window.stopCamera) await window.stopCamera();
  sessionStorage.removeItem('mc_session_start');
  window.location.href = href;
});

// ✅ Inject the floating widget (face-api.js version)
(function injectWidget(){
  if (!document.querySelector('script[src*="floating_mood_widget.js"]')) {
    const s = document.createElement('script');
    s.src = '../assets/js/floating_mood_widget.js?v=2'; // make sure this file exists
    s.defer = true;
    document.head.appendChild(s);
  }
})();
</script>


</body>
</html>
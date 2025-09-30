<?php
session_start();
require_once '../config/dbcon.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Ensure 'is_locked' column exists for privacy feature
$colRes = $conn->query("SHOW COLUMNS FROM journals LIKE 'is_locked'");
if ($colRes && $colRes->num_rows === 0) {
    $conn->query("ALTER TABLE journals ADD COLUMN is_locked TINYINT(1) NOT NULL DEFAULT 0 AFTER category");
}

// Add Entry
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['content']) && !isset($_POST['update_id']) && !isset($_POST['order'])) {
    $title = trim($_POST['title']) ?: 'Untitled';
    $content = $_POST['content'];
    $category = $_POST['category'];

    $orderRes = $conn->query("SELECT MAX(display_order) AS max_order FROM journals WHERE user_id = $user_id");
    $orderRow = $orderRes->fetch_assoc();
    $nextOrder = (int)($orderRow['max_order'] ?? 0) + 1;

    $query = $conn->prepare("INSERT INTO journals (user_id, title, content, category, display_order, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    $query->bind_param("isssi", $user_id, $title, $content, $category, $nextOrder);
    $query->execute();
}

// Update Entry
if (isset($_POST['update_id'])) {
    $update_id = (int)$_POST['update_id'];
    $update_title = $_POST['update_title'] ?: 'Untitled';
    $update_content = $_POST['update_content'] ?? '';
    $update_category = $_POST['update_category'] ?? 'General';

    $query = $conn->prepare("UPDATE journals SET title = ?, content = ?, category = ? WHERE id = ? AND user_id = ?");
    $query->bind_param("sssii", $update_title, $update_content, $update_category, $update_id, $user_id);
    $query->execute();
}

// Delete Entry
if (isset($_POST['delete_id'])) {
    $delete_id = (int)$_POST['delete_id'];
    $query = $conn->prepare("DELETE FROM journals WHERE id = ? AND user_id = ?");
    $query->bind_param("ii", $delete_id, $user_id);
    $query->execute();
}

// Lock/Unlock Entry
if (isset($_POST['lock_id'])) {
    $lock_id = (int)$_POST['lock_id'];
    $stmt = $conn->prepare("UPDATE journals SET is_locked = 1 WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $lock_id, $user_id);
    $stmt->execute();
}
if (isset($_POST['unlock_id'])) {
    $unlock_id = (int)$_POST['unlock_id'];
    $stmt = $conn->prepare("UPDATE journals SET is_locked = 0 WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $unlock_id, $user_id);
    $stmt->execute();
}

// Save Order
if (isset($_POST['order']) && is_array($_POST['order'])) {
    $order = $_POST['order'];
    foreach ($order as $position => $id) {
        $id = (int)$id;
        $pos = (int)$position;
        $stmt = $conn->prepare("UPDATE journals SET display_order = ? WHERE id = ? AND user_id = ?");
        $stmt->bind_param("iii", $pos, $id, $user_id);
        $stmt->execute();
    }
    exit();
}

// Fetch Entries
$entries = [];
$query = $conn->prepare("SELECT * FROM journals WHERE user_id = ? ORDER BY display_order ASC");
$query->bind_param("i", $user_id);
$query->execute();
$result = $query->get_result();
while ($row = $result->fetch_assoc()) {
    $entries[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Journal | MindCare AI</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <script src="https://cdn.tiny.cloud/1/qb4kfhyx5a4tdze96wb4yd31szbwlqga2e1e67agzi34svpo/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
  <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
  <style>
    :root {
      --bg: #f6f8fc;
      --panel: #ffffff;
      --muted: #6b7280;
      --text: #111827;
      --primary: #6C63FF;
      --success: #22c55e;
      --danger: #ef4444;
      --border: #e5e7eb;
      --shadow: 0 10px 30px rgba(17,24,39,0.06);
    }
    body.dark {
      --bg: #0b1220; --panel: #0f172a; --muted: #94a3b8; --text: #e5e7eb; --border: #1f2937; --shadow: 0 12px 30px rgba(0,0,0,0.35);
    }
    html, body { height: 100%; }
    body { 
      margin: 0; 
      background: var(--bg); 
      color: var(--text); 
      font-family: 'Segoe UI', system-ui, -apple-system, Roboto, Arial, sans-serif;
    }

    /* Shell */
    .app { 
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      gap: 20px;
      padding: 20px;
      background: var(--bg);
    }

    .topbar { background: var(--panel); border: 1px solid var(--border); border-radius: 14px; box-shadow: var(--shadow); padding: 12px 16px; display: flex; align-items: center; justify-content: space-between; }
    .brand { display: flex; align-items: center; gap: 10px; }
    .brand .dot { width: 10px; height: 10px; background: var(--primary); border-radius: 50%; box-shadow: 0 0 0 4px rgba(108,99,255,0.15); }
    .actions { display: flex; gap: 8px; }

    .controls { background: var(--panel); border: 1px solid var(--border); border-radius: 14px; box-shadow: var(--shadow); padding: 10px 12px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; }
    .chips { display: flex; gap: 8px; flex-wrap: wrap; }
    .chips .btn { border-radius: 999px; padding: 6px 12px; }
    .chips .btn.active { background: var(--primary); color: #fff; border-color: var(--primary); }
    .search-wrap { position: relative; }
    .search-wrap .bi-search { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: var(--muted); }
    .search-input { padding-left: 34px; border-radius: 12px; min-width: 260px; }

    .content { 
      display: grid; 
      grid-template-columns: 1fr 1.4fr; 
      gap: 20px;
      flex: 1;
    }

    .compose, .entries { 
      background: var(--panel); 
      border: 1px solid var(--border); 
      border-radius: 16px; 
      display: flex; 
      flex-direction: column;
      height: auto;
      min-height: 500px;
    }

    .compose-header, .entries-header { padding: 12px 14px; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; }
    .compose-body { padding: 12px; overflow: auto; }

    .entries-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 20px;
      border-bottom: 1px solid var(--border);
    }

    .entries-header .fw-semibold {
      font-size: 16px;
      color: var(--text);
    }

    .entries-header .hint { 
      color: var(--muted);
      font-size: 13px;
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .entries-header .hint i {
      font-size: 16px;
    }

    .entries-body { 
      padding: 20px; 
      overflow-y: auto;
    }

    .entries-grid { 
      display: grid; 
      grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); 
      gap: 20px;
    }

    .entry-card { 
      background: var(--panel);
      border: 1px solid var(--border); 
      border-radius: 16px; 
      padding: 20px; 
      display: flex; 
      flex-direction: column; 
      gap: 12px; 
      cursor: pointer; 
      transition: all 0.2s ease;
      position: relative;
    }

    .entry-card:hover { 
      transform: translateY(-2px);
      box-shadow: 0 4px 20px rgba(0,0,0,0.05);
    }

    .entry-card-header {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      gap: 10px;
    }

    .entry-title { 
      font-weight: 600; 
      font-size: 16px;
      line-height: 1.4;
      color: var(--text);
      margin: 0;
    }

    .entry-meta { 
      display: flex; 
      align-items: center; 
      justify-content: space-between;
      gap: 8px;
      color: var(--muted); 
      font-size: 13px;
    }

    .entry-meta .date {
      color: var(--muted);
    }

    .badge-cat { 
      background: var(--primary); 
      color: white;
      border-radius: 8px;
      padding: 6px 12px;
      font-size: 13px;
      font-weight: 500;
    }

    .entry-actions {
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .entry-actions i {
      color: var(--muted);
      font-size: 16px;
      padding: 6px;
      border-radius: 8px;
      transition: all 0.2s ease;
    }

    .entry-actions i:hover {
      background: var(--hover-bg);
      color: var(--primary);
    }

    .drag-handle { 
      cursor: move;
      color: var(--muted);
    }

    .entry-excerpt { 
      color: var(--muted);
      font-size: 14px;
      line-height: 1.5;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
      margin: 0;
    }

    /* Category Colors */
    .badge-cat[data-category="General"] { background: #3b82f6; }
    .badge-cat[data-category="Personal"] { background: #8b5cf6; }
    .badge-cat[data-category="Work"] { background: #10b981; }
    .badge-cat[data-category="Health"] { background: #ef4444; }
    .badge-cat[data-category="Ideas"] { background: #f59e0b; }

    /* Dark Mode */
    body.dark .entry-card {
      background: rgba(255,255,255,0.02);
    }

    body.dark .entry-card:hover {
      background: rgba(255,255,255,0.04);
      box-shadow: 0 4px 20px rgba(0,0,0,0.2);
    }

    @media (max-width: 768px) {
      .entries-grid {
        grid-template-columns: 1fr;
        gap: 15px;
      }
    }

    .btn-icon { border-radius: 10px; }
  </style>
</head>
<body>
<div class="app">
  <!-- Top Bar -->
  <div class="topbar">
    <div class="brand">
      <div class="brand-icon">
        <i class="bi bi-journal-text"></i>
      </div>
      <div>
        <h1 class="brand-title">Journal</h1>
        <p class="brand-subtitle">Capture thoughts and organize them by category</p>
      </div>
    </div>
    <div class="actions">
      <a href="../dashboard/user_dashboard.php" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left-short"></i>
        <span>Back</span>
      </a>
      <button id="darkModeToggle" class="btn btn-outline-dark">
        <i class="bi bi-moon-stars"></i>
      </button>
    </div>
  </div>

  <style>
    /* Header Styles */
    .topbar {
      background: var(--panel);
      border: 1px solid var(--border);
      border-radius: 16px;
      padding: 20px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 20px;
    }

    .brand {
      display: flex;
      align-items: center;
      gap: 15px;
    }

    .brand-icon {
      width: 45px;
      height: 45px;
      background: var(--primary);
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 22px;
    }

    .brand-title {
      font-size: 20px;
      font-weight: 600;
      color: var(--text);
      margin: 0;
      margin-bottom: 4px;
    }

    .brand-subtitle {
      color: var(--muted);
      font-size: 14px;
      margin: 0;
    }

    .actions {
      display: flex;
      gap: 10px;
    }

    .actions .btn {
      display: flex;
      align-items: center;
      gap: 6px;
      padding: 8px 16px;
      border-radius: 10px;
      font-size: 14px;
    }

    .actions .btn i {
      font-size: 18px;
    }

    @media (max-width: 992px) {
      .content {
        grid-template-columns: 1fr;
        gap: 20px;
      }

      .compose, .entries {
        min-height: 400px;
      }
    }

    @media (max-width: 576px) {
      .app {
        padding: 15px;
        gap: 15px;
      }

      .topbar {
        padding: 15px;
      }

      .brand-icon {
        width: 40px;
        height: 40px;
        font-size: 20px;
      }

      .brand-title {
        font-size: 18px;
      }

      .brand-subtitle {
        font-size: 13px;
      }

      .actions .btn span {
        display: none;
      }

      .actions .btn {
        padding: 8px;
      }

      .compose-header, 
      .entries-header {
        padding: 15px;
      }

      .compose-body,
      .entries-body {
        padding: 15px;
      }
    }
  </style>

  <!-- Controls -->
  <div class="controls">
    <div class="filter-section">
      <div class="chips" id="chips">
        <button type="button" class="filter-btn active" data-filter="all">
          <i class="bi bi-collection"></i>
          <span>All</span>
        </button>
        <button type="button" class="filter-btn" data-filter="General">
          <i class="bi bi-journal-text"></i>
          <span>General</span>
        </button>
        <button type="button" class="filter-btn" data-filter="Personal">
          <i class="bi bi-person"></i>
          <span>Personal</span>
        </button>
        <button type="button" class="filter-btn" data-filter="Work">
          <i class="bi bi-briefcase"></i>
          <span>Work</span>
        </button>
        <button type="button" class="filter-btn" data-filter="Health">
          <i class="bi bi-heart"></i>
          <span>Health</span>
        </button>
        <button type="button" class="filter-btn" data-filter="Ideas">
          <i class="bi bi-lightbulb"></i>
          <span>Ideas</span>
        </button>
      </div>
      <div class="search-wrap">
        <i class="bi bi-search"></i>
        <input type="text" id="searchInput" class="form-control" placeholder="Search entries...">
      </div>
    </div>
  </div>

  <style>
    /* Filter Controls */
    .controls {
      background: var(--panel);
      border: 1px solid var(--border);
      border-radius: 16px;
      padding: 20px;
      margin-bottom: 20px;
    }

    .filter-section {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 20px;
      flex-wrap: wrap;
    }

    .chips {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
    }

    .filter-btn {
      display: flex;
      align-items: center;
      gap: 8px;
      padding: 10px 16px;
      border-radius: 10px;
      border: 1px solid var(--border);
      background: var(--panel);
      color: var(--text);
      font-size: 14px;
      cursor: pointer;
    }

    .filter-btn i {
      font-size: 16px;
    }

    .filter-btn.active {
      background: var(--primary);
      color: white;
      border-color: var(--primary);
    }

    .search-wrap {
      position: relative;
      min-width: 300px;
    }

    .search-wrap i {
      position: absolute;
      left: 12px;
      top: 50%;
      transform: translateY(-50%);
      color: var(--muted);
      font-size: 16px;
    }

    .search-wrap .form-control {
      padding: 10px 15px 10px 38px;
      border-radius: 10px;
      border: 1px solid var(--border);
      background: var(--panel);
      color: var(--text);
      width: 100%;
    }

    .search-wrap .form-control::placeholder {
      color: var(--muted);
    }

    @media (max-width: 768px) {
      .filter-section {
        flex-direction: column;
        align-items: stretch;
        gap: 15px;
      }

      .search-wrap {
        min-width: 100%;
      }
    }

    @media (max-width: 576px) {
      .controls {
        padding: 15px;
      }

      .filter-btn {
        padding: 8px 12px;
        font-size: 13px;
      }

      .filter-btn i {
        font-size: 15px;
      }
    }
  </style>

  <!-- Content -->
  <div class="content">
    <!-- Compose Panel -->
    <div class="compose">
      <div class="compose-header">
        <div class="fw-semibold">New Entry</div>
        <span class="small text-muted">Auto-saves draft locally</span>
      </div>
      <div class="compose-body">
        <form method="POST" id="composeForm">
          <div class="compose-form-header">
            <input type="text" name="title" id="entryTitle" class="form-control" placeholder="Title of your entry">
            <div class="compose-actions">
              <select name="category" class="form-select">
                <option>General</option>
                <option>Personal</option>
                <option>Work</option>
                <option>Health</option>
                <option>Ideas</option>
              </select>
              <button type="submit" class="btn btn-primary">
                <i class="bi bi-save"></i>
                <span>Save Entry</span>
              </button>
            </div>
          </div>
          <div class="compose-editor">
            <textarea name="content" id="content"></textarea>
          </div>
        </form>

        <style>
          .compose-form-header {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-bottom: 15px;
          }

          .compose-actions {
            display: flex;
            align-items: center;
            gap: 10px;
          }

          .compose-actions .form-select {
            min-width: 140px;
            border-radius: 10px;
            border: 1px solid var(--border);
            padding: 10px 15px;
            background: var(--panel);
            color: var(--text);
          }

          .compose-actions .btn-primary {
            background: var(--primary);
            border: none;
            padding: 10px 20px;
            border-radius: 10px;
            color: white;
            display: flex;
            align-items: center;
            gap: 8px;
          }

          .compose-actions .btn-primary i {
            font-size: 18px;
          }

          #entryTitle {
            border-radius: 10px;
            border: 1px solid var(--border);
            padding: 12px 15px;
            font-size: 16px;
            background: var(--panel);
            color: var(--text);
          }

          #entryTitle::placeholder {
            color: var(--muted);
          }

          .compose-editor {
            flex: 1;
            min-height: 300px;
            display: flex;
            flex-direction: column;
          }

          @media (max-width: 576px) {
            .compose-actions {
              flex-direction: column;
              align-items: stretch;
            }

            .compose-actions .form-select,
            .compose-actions .btn-primary {
              width: 100%;
            }
          }
        </style>
      </div>
    </div>

    <!-- Entries Panel -->
    <div class="entries">
      <div class="entries-header">
        <div class="fw-semibold">Your Entries</div>
        <div class="hint small">Drag the handle to reorder</div>
      </div>
      <div class="entries-body">
        <div id="notesContainer" class="entries-grid">
          <?php foreach ($entries as $entry): ?>
            <?php 
              $excerpt = mb_strimwidth(strip_tags($entry['content']), 0, 120, '…');
              $titleLower = strtolower($entry['title']);
              $cat = $entry['category'];
              $dateStr = date('M j, Y g:i A', strtotime($entry['created_at']));
            ?>
            <div class="entry-card" data-id="<?= (int)$entry['id'] ?>" data-title="<?= htmlspecialchars($titleLower) ?>" data-category="<?= htmlspecialchars($cat) ?>" data-bs-toggle="modal" data-bs-target="#entryModal<?= (int)$entry['id'] ?>">
              <div class="entry-card-header">
                <span class="badge-cat" data-category="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></span>
                <div class="entry-actions">
                  <?php if (!empty($entry['is_locked'])): ?>
                    <i class="bi bi-lock-fill" title="Private"></i>
                  <?php endif; ?>
                  <i class="bi bi-grip-vertical drag-handle" title="Drag to reorder"></i>
                </div>
              </div>
              <h3 class="entry-title"><?= htmlspecialchars($entry['title']) ?></h3>
              <p class="entry-excerpt"><?= htmlspecialchars($excerpt) ?></p>
              <div class="entry-meta">
                <span class="date">
                  <i class="bi bi-calendar3"></i>
                  <?= $dateStr ?>
                </span>
              </div>
            </div>

            <!-- Edit Modal -->
            <div class="modal fade" id="entryModal<?= (int)$entry['id'] ?>" tabindex="-1">
              <div class="modal-dialog modal-lg">
                <form method="POST" class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title">Edit: <?= htmlspecialchars($entry['title']) ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                  </div>
                  <div class="modal-body">
                    <input type="hidden" name="update_id" value="<?= (int)$entry['id'] ?>">
                    <input type="text" name="update_title" class="form-control mb-2" value="<?= htmlspecialchars($entry['title']) ?>">
                    <select name="update_category" class="form-select mb-2">
                      <?php $categories = ['General', 'Personal', 'Work', 'Health', 'Ideas']; foreach ($categories as $catOpt): $sel = $catOpt === $cat ? 'selected' : ''; ?>
                        <option <?= $sel ?>><?= $catOpt ?></option>
                      <?php endforeach; ?>
                    </select>
                    <textarea class="update-editor" name="update_content" id="update_content_<?= (int)$entry['id'] ?>"><?= htmlspecialchars($entry['content']) ?></textarea>
                  </div>
                  <div class="modal-footer">
                    <button type="submit" class="btn btn-success"><i class="bi bi-save me-1"></i>Save</button>
                </form>
                <form method="POST" onsubmit="return confirm('Delete this entry?');">
                  <input type="hidden" name="delete_id" value="<?= (int)$entry['id'] ?>">
                  <button type="submit" class="btn btn-danger"><i class="bi bi-trash me-1"></i>Delete</button>
                </form>
                <form method="POST">
                  <?php if (empty($entry['is_locked'])): ?>
                    <input type="hidden" name="lock_id" value="<?= (int)$entry['id'] ?>">
                    <button type="submit" class="btn btn-warning"><i class="bi bi-lock-fill me-1"></i>Lock</button>
                  <?php else: ?>
                    <input type="hidden" name="unlock_id" value="<?= (int)$entry['id'] ?>">
                    <button type="submit" class="btn btn-outline-warning"><i class="bi bi-unlock me-1"></i>Unlock</button>
                  <?php endif; ?>
                </form>
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                  </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  // TinyMCE: compose
  tinymce.init({
    selector: '#content',
    menubar: false,
    height: 240,
    plugins: 'lists link emoticons',
    toolbar: 'bold italic underline | bullist numlist | link emoticons | undo redo',
    skin: document.body.classList.contains('dark') ? 'oxide-dark' : 'oxide',
    content_css: document.body.classList.contains('dark') ? 'dark' : 'default',
    setup: function (editor) {
      editor.on('Change', function () {
        localStorage.setItem('draftContent', editor.getContent());
      });
    }
  });

  // Init update editors lazily when modal opens
  document.addEventListener('shown.bs.modal', function (e) {
    const modal = e.target;
    const ta = modal.querySelector('textarea.update-editor');
    if (ta && !ta.dataset.editorInited) {
      tinymce.init({
        selector: `#${ta.id}`,
        menubar: false,
        height: 220,
        plugins: 'lists link emoticons',
        toolbar: 'bold italic underline | bullist numlist | link emoticons | undo redo',
        skin: document.body.classList.contains('dark') ? 'oxide-dark' : 'oxide',
        content_css: document.body.classList.contains('dark') ? 'dark' : 'default'
      });
      ta.dataset.editorInited = '1';
    }
  });

  document.addEventListener('DOMContentLoaded', () => {
    // Restore draft
    const savedTitle = localStorage.getItem('draftTitle');
    const savedContent = localStorage.getItem('draftContent');
    if (savedTitle) document.getElementById('entryTitle').value = savedTitle;
    if (savedContent) {
      const setDraft = () => tinymce.get('content')?.setContent(savedContent);
      if (tinymce.get('content')) setDraft(); else setTimeout(setDraft, 300);
    }
    document.getElementById('entryTitle').addEventListener('input', () => localStorage.setItem('draftTitle', document.getElementById('entryTitle').value));

    // Filter chips
    const chips = document.getElementById('chips');
    const notes = document.querySelectorAll('.entry-card');
    chips.addEventListener('click', (ev) => {
      const btn = ev.target.closest('button[data-filter]');
      if (!btn) return;
      chips.querySelectorAll('button').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      applyFilters();
    });

    // Search
    const searchInput = document.getElementById('searchInput');
    searchInput.addEventListener('input', applyFilters);

    function applyFilters() {
      const active = chips.querySelector('button.active')?.dataset.filter || 'all';
      const q = (searchInput.value || '').toLowerCase();
      document.querySelectorAll('.entry-card').forEach(card => {
        const cat = card.getAttribute('data-category');
        const title = (card.getAttribute('data-title') || '').toLowerCase();
        const matchCat = (active === 'all') || (cat === active);
        const matchText = !q || title.includes(q);
        card.style.display = (matchCat && matchText) ? '' : 'none';
      });
    }

    // Drag and drop reorder
    const container = document.getElementById('notesContainer');
    new Sortable(container, {
      animation: 150,
      handle: '.drag-handle',
      onEnd: function () {
        const ids = Array.from(container.children).filter(el => el.classList.contains('entry-card')).map(el => el.dataset.id);
        const params = new URLSearchParams();
        ids.forEach(id => params.append('order[]', id));
        fetch('', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: params.toString() });
      }
    });

    // Dark mode toggle
    const toggleBtn = document.getElementById('darkModeToggle');
    toggleBtn.addEventListener('click', () => {
      document.body.classList.toggle('dark');
      localStorage.setItem('journal_dark', document.body.classList.contains('dark'));
    });
    if (localStorage.getItem('journal_dark') === 'true') {
      document.body.classList.add('dark');
    }

    // Apply initial filters
    applyFilters();
  });
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
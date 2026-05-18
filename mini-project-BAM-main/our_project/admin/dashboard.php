<?php
session_start();

// ── Auth guard ───────────────────────────────────────────────────────────────
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// ── DB connection ────────────────────────────────────────────────────────────
require_once "../auth/config.php";

// ── Handle approve / reject POST ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['id'])) {
    $action = $_POST['action'];
    $id     = (int)$_POST['id'];

    if ($action === 'approve' && $id > 0) {
        $s = $connector->prepare("UPDATE users SET status = 'accepted' WHERE id = ? AND role = 'alumni'");
        $s->bind_param('i', $id); $s->execute(); $s->close();
    } elseif ($action === 'reject' && $id > 0) {
        $s = $connector->prepare("UPDATE users SET status = 'rejected' WHERE id = ? AND role = 'alumni'");
        $s->bind_param('i', $id); $s->execute(); $s->close();
    } elseif ($action === 'approve_story' && $id > 0) {
        $s = $connector->prepare("UPDATE success_stories SET status = 'approved' WHERE id = ?");
        $s->bind_param('i', $id); $s->execute(); $s->close();
    } elseif ($action === 'reject_story' && $id > 0) {
        $s = $connector->prepare("UPDATE stories SET status = 'rejected' WHERE id = ?");
        $s->bind_param('i', $id); $s->execute(); $s->close();
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// ── Stats (all direct PHP queries — no JSON API) ──────────────────────────────
$r = $connector->query("SELECT COUNT(*) FROM users WHERE role != 'admin'");
$total_users = (int)$r->fetch_row()[0];

$r = $connector->query("SELECT COUNT(*) FROM users WHERE role = 'alumni' AND status = 'pending'");
$alumni_pending = (int)$r->fetch_row()[0];

$r = $connector->query("SELECT COUNT(*) FROM success_stories WHERE status = 'pending'");
$stories_pending = (int)$r->fetch_row()[0];

$r = $connector->query("SELECT COUNT(*) FROM connection_requests WHERE status = 'accepted'");
$active_connections = (int)$r->fetch_row()[0];

$r = $connector->query("SELECT COUNT(*) FROM users WHERE role = 'alumni' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
$new_alumni_week = (int)$r->fetch_row()[0];

$r = $connector->query("SELECT COUNT(*) FROM success_stories WHERE DATE(created_at) = CURDATE()");
$stories_today = (int)$r->fetch_row()[0];

// ── Pending alumni (last 5) ───────────────────────────────────────────────────
$pending_alumni = [];
$res = $connector->query("
    SELECT u.id, u.first_name, u.last_name, u.email, u.profile_photo, u.created_at,
           ap.major, ap.graduation_year
    FROM users u
    LEFT JOIN alumni_profiles ap ON ap.user_id = u.id
    WHERE u.role = 'alumni' AND u.status = 'pending'
    ORDER BY u.created_at DESC LIMIT 5
");
while ($row = $res->fetch_assoc()) $pending_alumni[] = $row;

// ── Pending stories (last 5) ──────────────────────────────────────────────────
$pending_stories = [];
$res = $connector->query("
    SELECT s.id, s.title, s.created_at, u.first_name, u.last_name
    FROM success_stories s
    JOIN users u ON s.author_id = u.id
    WHERE s.status = 'pending'
    ORDER BY s.created_at DESC LIMIT 5
");
while ($row = $res->fetch_assoc()) $pending_stories[] = $row;

// ── Recent registrations (last 6) ────────────────────────────────────────────
$recent_users = [];
$res = $connector->query("
    SELECT id, first_name, last_name, email, role, status, created_at
    FROM users WHERE role != 'admin'
    ORDER BY created_at DESC LIMIT 6
");
while ($row = $res->fetch_assoc()) $recent_users[] = $row;

// ── Admin info ───────────────────────────────────────────────────────────────
$s = $connector->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
$s->bind_param('i', $_SESSION['user_id']);
$s->execute();
$s->bind_result($admin_first, $admin_last);
$s->fetch();
$s->close();
$admin_name   = htmlspecialchars(($admin_first ?? 'Admin') . ' ' . ($admin_last ?? ''));
$admin_letter = strtoupper(substr($admin_first ?? 'A', 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>UCA Connect – Admin Dashboard</title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
    * { font-family: 'DM Sans', sans-serif; }
    ::-webkit-scrollbar { width: 4px; height: 4px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 99px; }
    .nav-link {
        display: flex; align-items: center; gap: 10px;
        padding: 10px 14px; border-radius: 10px;
        font-size: 14px; color: #64748b;
        transition: all .15s ease; text-decoration: none;
    }
    .nav-link:hover { background: #eff6ff; color: #2563eb; }
    .nav-link.active { background: #eff6ff; color: #2563eb; font-weight: 600; }
    .stat-card { transition: transform .2s ease, box-shadow .2s ease; }
    .stat-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(37,99,235,.10); }
    @keyframes fadeUp {
        from { opacity: 0; transform: translateY(14px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    .fade-up { animation: fadeUp .35s ease both; }
    .badge { display:inline-block; font-size:11px; font-weight:600; padding:2px 8px; border-radius:99px; }
    .badge-pending  { background:#fef9c3; color:#854d0e; }
    .badge-approved { background:#dcfce7; color:#166534; }
    .badge-rejected { background:#fee2e2; color:#991b1b; }
    .badge-student  { background:#ede9fe; color:#5b21b6; }
    .badge-alumni   { background:#dbeafe; color:#1e40af; }
</style>
</head>
<body class="bg-[#f5f6fa]">

<div class="flex flex-col h-screen overflow-hidden">

  <!-- ── TOPBAR ──────────────────────────────────────────────────────────── -->
  <header class="h-14 bg-white border-b border-gray-200 flex items-center justify-between px-6 flex-shrink-0 z-10">
    <div class="flex items-center gap-2.5">
      <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center">
        <i data-lucide="zap" class="w-4 h-4 text-white fill-white"></i>
      </div>
      <span class="font-bold text-gray-900 text-[15px] tracking-tight">UCA Connect</span>
      <span class="ml-1 text-[11px] font-bold bg-blue-100 text-blue-600 px-2 py-0.5 rounded-full">Admin</span>
    </div>
    <div class="flex items-center gap-3">
      <span class="text-xs text-gray-400 hidden md:block"><?= date('l, F j, Y') ?></span>
      <div class="w-8 h-8 rounded-lg bg-blue-600 flex items-center justify-center text-white font-bold text-sm">
        <?= $admin_letter ?>
      </div>
      <span class="text-sm font-medium text-gray-700"><?= $admin_name ?></span>
      <a href="../login.php" class="p-1.5 rounded-lg hover:bg-gray-100 transition-colors" title="Logout">
        <i data-lucide="log-out" class="w-4 h-4 text-gray-400"></i>
      </a>
    </div>
  </header>

  <div class="flex flex-1 overflow-hidden">

    <!-- ── SIDEBAR ──────────────────────────────────────────────────────── -->
    <aside class="w-[210px] shrink-0 bg-white border-r border-gray-200 flex flex-col h-full">
      <nav class="flex-1 p-3 flex flex-col gap-0.5 overflow-y-auto pt-4">
        <a href="dashboard.php" class="nav-link active">
          <i data-lucide="layout-dashboard" class="w-4 h-4 shrink-0"></i> Dashboard
        </a>
        <a href="alumni-approvals.php" class="nav-link">
          <i data-lucide="user-check" class="w-4 h-4 shrink-0"></i>
          Alumni Approvals
          <?php if ($alumni_pending > 0): ?>
            <span class="ml-auto bg-blue-600 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full"><?= $alumni_pending ?></span>
          <?php endif; ?>
        </a>
        <a href="story-approvals.php" class="nav-link">
          <i data-lucide="book-open" class="w-4 h-4 shrink-0"></i>
          Story Approvals
          <?php if ($stories_pending > 0): ?>
            <span class="ml-auto bg-orange-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full"><?= $stories_pending ?></span>
          <?php endif; ?>
        </a>
        <a href="users.php" class="nav-link">
          <i data-lucide="users" class="w-4 h-4 shrink-0"></i> Manage Users
        </a>
      </nav>
      <div class="p-3 border-t border-gray-100">
        <a href="../login.php" class="nav-link" style="color:#ef4444">
          <i data-lucide="log-out" class="w-4 h-4 shrink-0"></i> Logout
        </a>
      </div>
    </aside>

    <!-- ── MAIN CONTENT ──────────────────────────────────────────────────── -->
    <main class="flex-1 overflow-y-auto">
      <div class="px-8 py-7 max-w-6xl mx-auto space-y-8">

        <!-- Page header -->
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Admin Dashboard</h1>
          <p class="text-sm text-gray-400 mt-1">Platform overview and pending actions.</p>
        </div>

        <!-- ── STATS ──────────────────────────────────────────────────── -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">

          <div class="stat-card fade-up bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
            <div class="flex items-center justify-between mb-3">
              <p class="text-sm text-gray-500 font-medium">Total Users</p>
              <div class="p-2 bg-blue-50 rounded-lg"><i data-lucide="users" class="w-4 h-4 text-blue-500"></i></div>
            </div>
            <p class="text-4xl font-bold text-gray-900"><?= $total_users ?></p>
            <p class="text-xs text-gray-400 mt-1">students &amp; alumni</p>
          </div>

          <div class="stat-card fade-up bg-white rounded-xl border border-gray-200 p-5 shadow-sm" style="animation-delay:.07s">
            <div class="flex items-center justify-between mb-3">
              <p class="text-sm text-gray-500 font-medium">Alumni Pending</p>
              <div class="p-2 bg-yellow-50 rounded-lg"><i data-lucide="user-plus" class="w-4 h-4 text-yellow-500"></i></div>
            </div>
            <p class="text-4xl font-bold text-gray-900"><?= $alumni_pending ?></p>
            <p class="text-xs text-gray-400 mt-1 mb-3"><?= $new_alumni_week ?> new this week</p>
            <a href="alumni-approvals.php" class="inline-flex items-center gap-1 text-xs font-semibold text-blue-600 hover:underline">
              Review <i data-lucide="arrow-right" class="w-3 h-3"></i>
            </a>
          </div>

          <div class="stat-card fade-up bg-white rounded-xl border border-gray-200 p-5 shadow-sm" style="animation-delay:.14s">
            <div class="flex items-center justify-between mb-3">
              <p class="text-sm text-gray-500 font-medium">Stories Pending</p>
              <div class="p-2 bg-orange-50 rounded-lg"><i data-lucide="book-open" class="w-4 h-4 text-orange-400"></i></div>
            </div>
            <p class="text-4xl font-bold text-gray-900"><?= $stories_pending ?></p>
            <p class="text-xs text-gray-400 mt-1 mb-3"><?= $stories_today ?> submitted today</p>
            <a href="story-approvals.php" class="inline-flex items-center gap-1 text-xs font-semibold text-blue-600 hover:underline">
              Review <i data-lucide="arrow-right" class="w-3 h-3"></i>
            </a>
          </div>

          <div class="stat-card fade-up bg-white rounded-xl border border-gray-200 p-5 shadow-sm" style="animation-delay:.21s">
            <div class="flex items-center justify-between mb-3">
              <p class="text-sm text-gray-500 font-medium">Active Connections</p>
              <div class="p-2 bg-emerald-50 rounded-lg"><i data-lucide="link" class="w-4 h-4 text-emerald-500"></i></div>
            </div>
            <p class="text-4xl font-bold text-gray-900"><?= $active_connections ?></p>
            <p class="text-xs text-gray-400 mt-1">accepted connections</p>
          </div>

        </div>

        <!-- ── PENDING TABLES ─────────────────────────────────────────── -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

          <!-- Pending Alumni -->
          <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
              <div>
                <h2 class="font-bold text-gray-800">Pending Alumni</h2>
                <p class="text-xs text-gray-400 mt-0.5">Awaiting approval</p>
              </div>
              <a href="alumni-approvals.php" class="text-xs font-semibold text-blue-600 hover:underline flex items-center gap-1">
                View all <i data-lucide="arrow-right" class="w-3 h-3"></i>
              </a>
            </div>

            <?php if (empty($pending_alumni)): ?>
              <div class="flex flex-col items-center justify-center py-12">
                <i data-lucide="check-circle" class="w-10 h-10 text-gray-200 mb-2"></i>
                <p class="text-sm text-gray-400">All caught up!</p>
              </div>
            <?php else: ?>
              <div class="divide-y divide-gray-100">
                <?php foreach ($pending_alumni as $a):
                  $name   = htmlspecialchars($a['first_name'] . ' ' . $a['last_name']);
                  $letter = strtoupper(substr($a['first_name'] ?? 'A', 0, 1));
                  $photo  = !empty($a['profile_photo']) ? '../' . ltrim($a['profile_photo'], './') : '';
                ?>
                <div class="flex items-center gap-3 px-6 py-3">
                  <div class="w-9 h-9 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold text-sm shrink-0 overflow-hidden">
                    <?php if ($photo): ?>
                      <img src="<?= htmlspecialchars($photo) ?>" class="w-full h-full object-cover"
                           onerror="this.parentElement.innerHTML='<?= addslashes($letter) ?>'">
                    <?php else: ?>
                      <?= $letter ?>
                    <?php endif; ?>
                  </div>
                  <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-gray-800 truncate"><?= $name ?></p>
                    <p class="text-xs text-gray-400 truncate">
                      <?= htmlspecialchars($a['major'] ?? 'N/A') ?> · <?= htmlspecialchars($a['graduation_year'] ?? 'N/A') ?>
                    </p>
                  </div>
                  <div class="flex gap-2 shrink-0">
                    <form method="POST">
                      <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                      <input type="hidden" name="action" value="reject">
                      <button type="submit" onclick="return confirm('Reject this alumni?')"
                        class="px-3 py-1 text-xs font-semibold border border-gray-200 rounded-lg text-gray-600 hover:bg-gray-100 transition-colors">
                        Reject
                      </button>
                    </form>
                    <form method="POST">
                      <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                      <input type="hidden" name="action" value="approve">
                      <button type="submit" onclick="return confirm('Approve this alumni?')"
                        class="px-3 py-1 text-xs font-semibold bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        Approve
                      </button>
                    </form>
                  </div>
                </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>

          <!-- Pending Stories -->
          <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
              <div>
                <h2 class="font-bold text-gray-800">Pending Stories</h2>
                <p class="text-xs text-gray-400 mt-0.5">Awaiting publication</p>
              </div>
              <a href="story-approvals.php" class="text-xs font-semibold text-blue-600 hover:underline flex items-center gap-1">
                View all <i data-lucide="arrow-right" class="w-3 h-3"></i>
              </a>
            </div>

            <?php if (empty($pending_stories)): ?>
              <div class="flex flex-col items-center justify-center py-12">
                <i data-lucide="check-circle" class="w-10 h-10 text-gray-200 mb-2"></i>
                <p class="text-sm text-gray-400">No new stories.</p>
              </div>
            <?php else: ?>
              <div class="divide-y divide-gray-100">
                <?php foreach ($pending_stories as $s):
                  $author = htmlspecialchars($s['first_name'] . ' ' . $s['last_name']);
                  $date   = date('M j, Y', strtotime($s['created_at']));
                ?>
                <div class="flex items-center gap-3 px-6 py-3">
                  <div class="p-2 bg-orange-50 rounded-lg shrink-0">
                    <i data-lucide="file-text" class="w-4 h-4 text-orange-400"></i>
                  </div>
                  <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-gray-800 truncate"><?= htmlspecialchars($s['title']) ?></p>
                    <p class="text-xs text-gray-400">by <?= $author ?> · <?= $date ?></p>
                  </div>
                  <div class="flex gap-2 shrink-0">
                    <form method="POST">
                      <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                      <input type="hidden" name="action" value="reject_story">
                      <button type="submit" onclick="return confirm('Reject this story?')"
                        class="px-3 py-1 text-xs font-semibold border border-gray-200 rounded-lg text-gray-600 hover:bg-gray-100 transition-colors">
                        Reject
                      </button>
                    </form>
                    <form method="POST">
                      <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                      <input type="hidden" name="action" value="approve_story">
                      <button type="submit" onclick="return confirm('Approve this story?')"
                        class="px-3 py-1 text-xs font-semibold bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        Approve
                      </button>
                    </form>
                  </div>
                </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>

        </div>

        <!-- ── RECENT REGISTRATIONS ───────────────────────────────────── -->
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
          <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <div>
              <h2 class="font-bold text-gray-800">Recent Registrations</h2>
              <p class="text-xs text-gray-400 mt-0.5">Latest users to join the platform</p>
            </div>
            <a href="users.php" class="text-xs font-semibold text-blue-600 hover:underline flex items-center gap-1">
              Manage all <i data-lucide="arrow-right" class="w-3 h-3"></i>
            </a>
          </div>
          <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
              <thead class="bg-gray-50 text-gray-400 text-[11px] uppercase tracking-wider">
                <tr>
                  <th class="px-6 py-3 font-semibold">Name</th>
                  <th class="px-6 py-3 font-semibold">Email</th>
                  <th class="px-6 py-3 font-semibold">Role</th>
                  <th class="px-6 py-3 font-semibold">Status</th>
                  <th class="px-6 py-3 font-semibold">Joined</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100 text-gray-600">
                <?php if (empty($recent_users)): ?>
                  <tr><td colspan="5" class="px-6 py-10 text-center text-gray-400">No users yet.</td></tr>
                <?php else: ?>
                  <?php foreach ($recent_users as $u):
                    $letter = strtoupper(substr($u['first_name'] ?? 'U', 0, 1));
                    $joined = date('M j, Y', strtotime($u['created_at']));
                    $status = $u['status'] ?? 'pending';
                    $statusClass = match($status) {
                      'approved' => 'badge-approved',
                      'rejected' => 'badge-rejected',
                      default    => 'badge-pending',
                    };
                    $roleClass = $u['role'] === 'alumni' ? 'badge-alumni' : 'badge-student';
                  ?>
                  <tr class="hover:bg-gray-50 transition-colors">
                    <td class="px-6 py-3">
                      <div class="flex items-center gap-2.5">
                        <div class="w-7 h-7 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-xs font-bold shrink-0">
                          <?= $letter ?>
                        </div>
                        <span class="font-medium text-gray-800">
                          <?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name']) ?>
                        </span>
                      </div>
                    </td>
                    <td class="px-6 py-3 text-gray-400 text-xs"><?= htmlspecialchars($u['email']) ?></td>
                    <td class="px-6 py-3"><span class="badge <?= $roleClass ?>"><?= ucfirst($u['role']) ?></span></td>
                    <td class="px-6 py-3"><span class="badge <?= $statusClass ?>"><?= ucfirst($status) ?></span></td>
                    <td class="px-6 py-3 text-gray-400 text-xs"><?= $joined ?></td>
                  </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

      </div>

      <!-- Footer -->
      <footer class="px-8 py-4 border-t border-gray-200 bg-white mt-8 flex items-center justify-between">
        <p class="text-xs text-gray-400">© <?= date('Y') ?> UCA Connect. All rights reserved.</p>
        <p class="text-xs text-gray-300">Admin Portal</p>
      </footer>
    </main>

  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    if (typeof lucide !== 'undefined') lucide.createIcons();
  });
</script>
</body>
</html>
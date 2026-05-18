<?php
session_start();

// Auth guard
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../auth/config.php';

// Handle approve / reject POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['id'])) {
    $action = $_POST['action'];
    $id     = (int)$_POST['id'];

    if ($id > 0) {
        if ($action === 'approve') {
            $stmt = $connector->prepare("UPDATE users SET status = 'accepted' WHERE id = ? AND role = 'alumni'");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();
        } elseif ($action === 'reject') {
            $stmt = $connector->prepare("UPDATE users SET status = 'rejected' WHERE id = ? AND role = 'alumni'");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();
        }
    }

    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Stats
$r = $connector->query("SELECT COUNT(*) FROM users WHERE role != 'admin'");
$total_users = $r ? (int)$r->fetch_row()[0] : 0;

$r = $connector->query("SELECT COUNT(*) FROM users WHERE role = 'alumni' AND status = 'pending'");
$alumni_pending = $r ? (int)$r->fetch_row()[0] : 0;

$r = $connector->query("SELECT COUNT(*) FROM users WHERE role = 'alumni' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
$new_alumni_week = $r ? (int)$r->fetch_row()[0] : 0;

$r = $connector->query("SELECT COUNT(*) FROM connection_requests WHERE status = 'accepted'");
$active_connections = $r ? (int)$r->fetch_row()[0] : 0;

// Pending alumni list
$pending_alumni = [];
$res = $connector->query(
    "SELECT u.id, u.first_name, u.last_name, u.email, u.profile_photo, u.created_at,\n           ap.major, ap.graduation_year, ap.bio\n    FROM users u\n    LEFT JOIN alumni_profiles ap ON ap.user_id = u.id\n    WHERE u.role = 'alumni' AND u.status = 'pending'\n    ORDER BY u.created_at DESC"
);
if ($res) {
    while ($row = $res->fetch_assoc()) $pending_alumni[] = $row;
}

// Admin info for header
$s = $connector->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
$s->bind_param('i', $_SESSION['user_id']);
$s->execute();
$s->bind_result($admin_first, $admin_last);
$s->fetch();
$s->close();
$admin_name = htmlspecialchars(trim(($admin_first ?? 'Admin') . ' ' . ($admin_last ?? '')));
$admin_letter = strtoupper(substr($admin_first ?? 'A', 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1.0" />
    <title>UCA Connect – Alumni Approvals</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'DM Sans', sans-serif; }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 99px; }
        .nav-link { display:flex; align-items:center; gap:10px; padding:10px 14px; border-radius:10px; font-size:14px; color:#64748b; text-decoration:none; }
        .nav-link:hover { background:#eff6ff; color:#2563eb; }
        .nav-link.active { background:#eff6ff; color:#2563eb; font-weight:600; }
        .badge { display:inline-block; font-size:11px; font-weight:600; padding:2px 8px; border-radius:99px; }
        .badge-pending  { background:#fef9c3; color:#854d0e; }
        .badge-alumni   { background:#dbeafe; color:#1e40af; }
    </style>
</head>
<body class="bg-[#f5f6fa]">

<div class="flex flex-col h-screen overflow-hidden">

  <!-- TOPBAR -->
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
      <div class="w-8 h-8 rounded-lg bg-blue-600 flex items-center justify-center text-white font-bold text-sm"><?= $admin_letter ?></div>
      <span class="text-sm font-medium text-gray-700"><?= $admin_name ?></span>
      <a href="../login.php" class="p-1.5 rounded-lg hover:bg-gray-100 transition-colors" title="Logout">
        <i data-lucide="log-out" class="w-4 h-4 text-gray-400"></i>
      </a>
    </div>
  </header>

  <div class="flex flex-1 overflow-hidden">

    <!-- SIDEBAR -->
    <aside class="w-[210px] shrink-0 bg-white border-r border-gray-200 flex flex-col h-full">
      <nav class="flex-1 p-3 flex flex-col gap-0.5 overflow-y-auto pt-4">
        <a href="dashboard.php" class="nav-link">
          <i data-lucide="layout-dashboard" class="w-4 h-4 shrink-0"></i> Dashboard
        </a>
        <a href="alumni-approvals.php" class="nav-link active">
          <i data-lucide="user-check" class="w-4 h-4 shrink-0"></i> Alumni Approvals
          <?php if ($alumni_pending > 0): ?>
            <span class="ml-auto bg-blue-600 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full"><?= $alumni_pending ?></span>
          <?php endif; ?>
        </a>
        <a href="story-approvals.php" class="nav-link">
          <i data-lucide="book-open" class="w-4 h-4 shrink-0"></i> Story Approvals
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

    <!-- MAIN -->
    <main class="flex-1 overflow-y-auto">
      <div class="px-8 py-7 max-w-6xl mx-auto space-y-8">

        <div>
          <h1 class="text-2xl font-bold text-gray-900">Alumni Approvals</h1>
          <p class="text-sm text-gray-400 mt-1">Review and manage new alumni registrations.</p>
        </div>

        <!-- Stats row -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
          <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
            <div class="flex items-center justify-between mb-3">
              <p class="text-sm text-gray-500 font-medium">Total Users</p>
              <div class="p-2 bg-blue-50 rounded-lg"><i data-lucide="users" class="w-4 h-4 text-blue-500"></i></div>
            </div>
            <p class="text-4xl font-bold text-gray-900"><?= $total_users ?></p>
            <p class="text-xs text-gray-400 mt-1">students &amp; alumni</p>
          </div>

          <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
            <div class="flex items-center justify-between mb-3">
              <p class="text-sm text-gray-500 font-medium">Alumni Pending</p>
              <div class="p-2 bg-yellow-50 rounded-lg"><i data-lucide="user-plus" class="w-4 h-4 text-yellow-500"></i></div>
            </div>
            <p class="text-4xl font-bold text-gray-900"><?= $alumni_pending ?></p>
            <p class="text-xs text-gray-400 mt-1"><?= $new_alumni_week ?> new this week</p>
          </div>

          <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
            <div class="flex items-center justify-between mb-3">
              <p class="text-sm text-gray-500 font-medium">Active Connections</p>
              <div class="p-2 bg-emerald-50 rounded-lg"><i data-lucide="link" class="w-4 h-4 text-emerald-500"></i></div>
            </div>
            <p class="text-4xl font-bold text-gray-900"><?= $active_connections ?></p>
            <p class="text-xs text-gray-400 mt-1">accepted connections</p>
          </div>

          <div class="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
            <div class="flex items-center justify-between mb-3">
              <p class="text-sm text-gray-500 font-medium">Quick Actions</p>
              <div class="p-2 bg-gray-50 rounded-lg"><i data-lucide="settings" class="w-4 h-4 text-gray-500"></i></div>
            </div>
            <p class="text-sm text-gray-600">Approve or reject alumni from the list below.</p>
          </div>
        </div>

        <!-- Search + grid -->
        <div class="max-w-3xl">
          <div class="relative mb-6">
            <i class="cursor-pointer absolute left-4 top-1/2 -translate-y-1/2 text-gray-400" data-lucide="search"></i>
            <input id="alumniSearch" type="text" placeholder="Search by name, email, or major..." class="w-full pl-12 pr-4 py-3 bg-white border border-gray-200 rounded-xl shadow-sm focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none">
          </div>
        </div>

        <div id="alumniGrid" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <?php if (empty($pending_alumni)): ?>
            <div class="col-span-full py-20 text-center bg-white rounded-3xl border-2 border-dashed border-gray-200">
              <i data-lucide="check-circle" class="w-12 h-12 text-gray-200 mb-4"></i>
              <p class="text-gray-400 font-medium">No pending alumni applications at the moment.</p>
            </div>
          <?php else: ?>
            <?php foreach ($pending_alumni as $a):
              $fullName = htmlspecialchars(($a['first_name'] ?? '') . ' ' . ($a['last_name'] ?? ''));
              $email    = htmlspecialchars($a['email'] ?? '');
              $major    = htmlspecialchars($a['major'] ?? 'Not specified');
              $gradYear = htmlspecialchars($a['graduation_year'] ?? 'N/A');
              $bio      = htmlspecialchars($a['bio'] ?? 'No bio provided.');
              $photo = !empty($a['profile_photo']) ? '../' . ltrim($a['profile_photo'], './') : 'https://ui-avatars.com/api/?name=' . urlencode($fullName) . '&background=random';
              $joined = date('M j, Y', strtotime($a['created_at'] ?? ''));
              $searchAttr = htmlspecialchars(strtolower($fullName . ' ' . $email . ' ' . $major));
            ?>
            <div class="alumni-card bg-white rounded-2xl border border-gray-200 p-6 shadow-sm hover:shadow-md transition-all" data-search="<?= $searchAttr ?>">
              <div class="flex items-start gap-5">
                <img src="<?= htmlspecialchars($photo) ?>" alt="<?= $fullName ?>" class="w-20 h-20 rounded-2xl object-cover border border-gray-100 shadow-sm">
                <div class="flex-1">
                  <div class="flex justify-between items-start">
                    <div>
                      <h3 class="text-xl font-bold text-gray-900"><?= $fullName ?></h3>
                      <p class="text-sm text-primary font-medium mb-3"><i class="fa-regular fa-envelope mr-1"></i> <?= $email ?></p>
                    </div>
                  </div>

                  <div class="grid grid-cols-1 gap-2 mb-5 text-sm text-gray-600">
                    <p><i class="fa-solid fa-graduation-cap w-6 text-gray-400"></i> <span class="font-bold">Major:</span> <?= $major ?></p>
                    <p><i class="fa-solid fa-calendar-check w-6 text-gray-400"></i> <span class="font-bold">Class of:</span> <?= $gradYear ?></p>
                    <div class="mt-2 p-3 bg-gray-50 rounded-lg border border-gray-100">
                      <span class="font-bold block text-xs uppercase text-gray-400 mb-1">Bio</span>
                      <p class="italic text-gray-700 leading-relaxed text-xs">"<?= $bio ?>"</p>
                    </div>
                  </div>

                  <div class="flex justify-end gap-3 pt-2 border-t border-gray-50">
                    <form method="POST" onsubmit="return confirm('Reject this alumni?');">
                      <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                      <input type="hidden" name="action" value="reject">
                      <button type="submit" class="px-6 py-2 text-xs font-semibold border border-gray-200 rounded-lg text-gray-600 hover:bg-gray-100 transition-all">Reject</button>
                    </form>
                    <form method="POST" onsubmit="return confirm('Approve this alumni?');">
                      <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                      <input type="hidden" name="action" value="approve">
                      <button type="submit" class="px-6 py-2 bg-blue-500 text-white rounded-xl text-sm font-bold hover:bg-blue-600 shadow-lg transition-all">Approve</button>
                    </form>
                  </div>
                </div>
              </div>
            </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>

      </div>

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

    const input = document.getElementById('alumniSearch');
    if (!input) return;

    input.addEventListener('input', function () {
      const q = input.value.trim().toLowerCase();
      document.querySelectorAll('.alumni-card').forEach(card => {
        const hay = card.getAttribute('data-search') || '';
        card.style.display = q === '' || hay.indexOf(q) !== -1 ? '' : 'none';
      });
    });
  });
</script>
</body>
</html>
<?php 
require_once "../includes/head.php"; 
require_once "../auth/config.php"; 
?>

<div class="flex">
    <?php
    require_once "../includes/sidebar.php";
    ?>
    <div class="w-full">
        <?php require_once "../includes/headers/header.php";?>

        <main class="p-8">
            <div class="max-w-6xl mx-auto">
                <div class="flex justify-between items-end mb-8">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-800">Alumni Approvals</h1>
                        <p class="text-gray-500 mt-1">Review and manage new alumni registrations.</p>
                    </div>
                </div>

                <div class="relative mb-10 max-w-lg">
                    <i class="cursor-pointer fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    <input type="text" id="alumniSearch" 
                        placeholder="Search by name, email, or major..." 
                        class="w-full pl-12 pr-4 py-3 bg-white border border-gray-200 rounded-xl shadow-sm focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all">
                </div>

                <div id="alumniGrid" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <?php
                    // We JOIN the two tables so we can get the name from 'users' 
                    // and the graduation details from 'alumni_profiles'
                    $query = "SELECT 
                                u.id, u.first_name, u.last_name, u.email, u.profile_photo, 
                                a.major, a.graduation_year, a.bio 
                            FROM users u 
                            JOIN alumni_profiles a ON u.id = a.user_id 
                            WHERE u.role='alumni' AND u.status='pending' 
                            ORDER BY u.created_at DESC";

                    $result = mysqli_query($connector, $query);

                    if (mysqli_num_rows($result) > 0):
                        while($row = mysqli_fetch_assoc($result)):
                            
                            // Now these variables will actually have data from the database
                            $fullName = htmlspecialchars($row['first_name'] . ' ' . $row['last_name']);
                            $email    = htmlspecialchars($row['email'] ?? 'No email');
                            $major    = htmlspecialchars($row['major'] ?? 'Not Specified');
                            $gradYear = htmlspecialchars($row['graduation_year'] ?? 'N/A');
                            $bio      = htmlspecialchars($row['bio'] ?? 'No bio provided.');
                            
                            // Handle Photo logic
                            $photo = !empty($row['profile_photo']) 
                                    ? $row['profile_photo'] 
                                    : "https://ui-avatars.com/api/?name=" . urlencode($fullName) . "&background=random";
                    ?>
                    <div class="alumni-card bg-white rounded-2xl border border-gray-200 p-6 shadow-sm hover:shadow-md transition-all duration-300">
                        <div class="flex items-start gap-5">
                            <img src="<?= $photo ?>" 
                                class="w-20 h-20 rounded-2xl object-cover border border-gray-100 shadow-sm" alt="Profile">
                            
                            <div class="flex-1">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h3 class="text-xl font-bold text-gray-900"><?= $fullName ?></h3>
                                        <p class="text-sm text-primary font-medium mb-3">
                                            <i class="fa-regular fa-envelope mr-1"></i> <?= $email ?>
                                        </p>
                                    </div>
                                </div>
                                
                                <div class="grid grid-cols-1 gap-2 mb-5 text-sm text-gray-600">
                                    <p><i class="fa-solid fa-graduation-cap w-6 text-gray-400"></i> 
                                    <span class="font-bold">Major:</span> <?= $major ?></p>
                                    <p><i class="fa-solid fa-calendar-check w-6 text-gray-400"></i> 
                                    <span class="font-bold">Class of:</span> <?= $gradYear ?></p>
                                    
                                    <div class="mt-2 p-3 bg-gray-50 rounded-lg border border-gray-100">
                                        <span class="font-bold block text-xs uppercase text-gray-400 mb-1">Bio</span>
                                        <p class="italic text-gray-700 leading-relaxed text-xs">
                                            "<?= $bio ?>"
                                        </p>
                                    </div>
                                </div>

                                <div class="flex justify-end gap-3 pt-2 border-t border-gray-50">
                                    <button onclick="handleAction(<?= $row['id'] ?>, 'reject')" 
                                        class="px-6 py-2 bg-red-50 text-red-600 rounded-xl text-sm font-bold hover:bg-red-500 hover:text-white transition-all">
                                        Reject
                                    </button>
                                    <button onclick="handleAction(<?= $row['id'] ?>, 'approve')" 
                                        class="px-6 py-2 bg-blue-500 text-white rounded-xl text-sm font-bold hover:bg-blue-600 shadow-lg transition-all">
                                        Approve
                                    </button>
                                    
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php 
                        endwhile; 
                    else:
                    ?>
                        <div class="col-span-full py-20 text-center bg-white rounded-3xl border-2 border-dashed border-gray-200">
                            <i class="fa-solid fa-circle-check text-5xl text-gray-200 mb-4"></i>
                            <p class="text-gray-400 font-medium">No pending alumni applications at the moment.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include "../includes/approve-script.php"; ?>
<?php
require_once "../includes/footer.php";
require_once "../includes/theme.php";
?>
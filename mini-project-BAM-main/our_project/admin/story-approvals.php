<?php
session_start();

// Auth guard
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
        header('Location: ../login.php');
        exit;
}

require_once __DIR__ . '/../auth/config.php';

// Handle POST approve / reject for stories
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['id'])) {
        $action = $_POST['action'];
        $id     = (int)$_POST['id'];

        if ($id > 0) {
                if ($action === 'approve') {
                        $stmt = $connector->prepare("UPDATE success_stories SET status = 'approved' WHERE id = ?");
                        $stmt->bind_param('i', $id);
                        $stmt->execute();
                        $stmt->close();
                } elseif ($action === 'reject') {
                        $stmt = $connector->prepare("UPDATE success_stories SET status = 'rejected' WHERE id = ?");
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

$r = $connector->query("SELECT COUNT(*) FROM success_stories WHERE status = 'pending'");
$stories_pending = $r ? (int)$r->fetch_row()[0] : 0;

$r = $connector->query("SELECT COUNT(*) FROM success_stories WHERE DATE(created_at) = CURDATE()");
$stories_today = $r ? (int)$r->fetch_row()[0] : 0;

$r = $connector->query("SELECT COUNT(*) FROM connection_requests WHERE status = 'accepted'");
$active_connections = $r ? (int)$r->fetch_row()[0] : 0;

// Pending stories
$pending_stories = [];
$res = $connector->query(
        "SELECT s.id, s.title, s.created_at, s.status, u.first_name, u.last_name\n     FROM success_stories s\n     JOIN users u ON s.author_id = u.id\n     WHERE s.status = 'pending'\n     ORDER BY s.created_at DESC"
);
if ($res) {
        while ($row = $res->fetch_assoc()) $pending_stories[] = $row;
}

// Admin info
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
        <title>UCA Connect – Story Approvals</title>
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
                <a href="alumni-approvals.php" class="nav-link">
                    <i data-lucide="user-check" class="w-4 h-4 shrink-0"></i> Alumni Approvals
                </a>
                <a href="story-approvals.php" class="nav-link active">
                    <i data-lucide="book-open" class="w-4 h-4 shrink-0"></i> Story Approvals
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

        <!-- MAIN -->
        <main class="flex-1 overflow-y-auto">
            <div class="px-8 py-7 max-w-6xl mx-auto space-y-8">

                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Story Approval Queue</h1>
                    <p class="text-sm text-gray-400 mt-1">Review and manage incoming success stories.</p>
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
                            <p class="text-sm text-gray-500 font-medium">Stories Pending</p>
                            <div class="p-2 bg-amber-50 rounded-lg"><i data-lucide="book-open" class="w-4 h-4 text-amber-400"></i></div>
                        </div>
                        <p class="text-4xl font-bold text-gray-900"><?= $stories_pending ?></p>
                        <p class="text-xs text-gray-400 mt-1"><?= $stories_today ?> submitted today</p>
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
                        <p class="text-sm text-gray-600">Approve or reject stories from the table below.</p>
                    </div>
                </div>

                <!-- Search + table -->
                <div class="flex items-center gap-4 mb-4">
                    <div class="relative flex-1 max-w-md">
                        <i class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400" data-lucide="search"></i>
                        <input id="storySearch" type="text" placeholder="Search stories by title or author..." class="w-full pl-12 pr-4 py-2 bg-white border border-gray-200 rounded-lg outline-none focus:border-primary transition-all">
                    </div>
                    <div>
                        <a href="story-approvals.php" class="text-xs font-semibold text-gray-600">Refresh</a>
                    </div>
                </div>

                <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                    <div class="p-4 border-b border-gray-100 bg-gray-50/50">
                        <h2 class="font-bold text-gray-700">Pending Stories (<?= count($pending_stories) ?>)</h2>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="text-xs uppercase text-gray-400 font-bold border-b border-gray-100">
                                    <th class="px-6 py-4">Title</th>
                                    <th class="px-6 py-4">Author</th>
                                    <th class="px-6 py-4">Date</th>
                                    <th class="px-6 py-4 text-center">Status</th>
                                    <th class="px-6 py-4 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50" id="storyTableBody">
                                <?php if (empty($pending_stories)): ?>
                                    <tr><td colspan="5" class="px-6 py-12 text-center text-gray-400 italic">No pending stories to review.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($pending_stories as $story):
                                        $title = htmlspecialchars($story['title']);
                                        $author = htmlspecialchars(trim(($story['first_name'] ?? '') . ' ' . ($story['last_name'] ?? '')));
                                        $date = date('M j, Y', strtotime($story['created_at'] ?? ''));
                                        $status = htmlspecialchars($story['status'] ?? 'pending');
                                        $search = htmlspecialchars(strtolower($title . ' ' . $author));
                                    ?>
                                    <tr class="story-row hover:bg-gray-50/50 transition-colors" data-search="<?= $search ?>">
                                        <td class="px-6 py-4 font-medium text-gray-800"><?= $title ?></td>
                                        <td class="px-6 py-4 text-gray-600"><?= $author ?></td>
                                        <td class="px-6 py-4 text-gray-500 text-sm"><?= $date ?></td>
                                        <td class="px-6 py-4 text-center">
                                            <span class="px-3 py-1 bg-amber-50 text-amber-600 rounded-full text-[10px] font-bold uppercase tracking-wider"><?= $status ?></span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex justify-end gap-2">
                                                <a href="story-review.php?id=<?= (int)$story['id'] ?>" class="p-2 text-gray-400 hover:text-primary transition-colors" title="View Story">
                                                    <i class="fa-solid fa-eye"></i>
                                                </a>
                                                <form method="POST" onsubmit="return confirm('Approve this story?');">
                                                    <input type="hidden" name="id" value="<?= (int)$story['id'] ?>">
                                                    <input type="hidden" name="action" value="approve">
                                                    <button type="submit" class="px-4 py-1.5 bg-blue-500 text-white text-xs font-bold rounded-lg hover:bg-blue-600 transition-all">Approve</button>
                                                </form>
                                                <form method="POST" onsubmit="return confirm('Reject this story?');">
                                                    <input type="hidden" name="id" value="<?= (int)$story['id'] ?>">
                                                    <input type="hidden" name="action" value="reject">
                                                    <button type="submit" class="px-4 py-1.5 bg-red-500 text-white text-xs font-bold rounded-lg hover:bg-red-600 transition-all">Reject</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
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

        const input = document.getElementById('storySearch');
        if (!input) return;
        input.addEventListener('input', function () {
            const q = input.value.trim().toLowerCase();
            document.querySelectorAll('.story-row').forEach(row => {
                const hay = row.getAttribute('data-search') || '';
                row.style.display = q === '' || hay.indexOf(q) !== -1 ? '' : 'none';
            });
        });
    });
</script>
</body>
</html>

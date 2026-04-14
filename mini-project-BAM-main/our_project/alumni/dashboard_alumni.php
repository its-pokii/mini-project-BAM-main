
<?php
include("../student/tools/userHeaderName.php"); 
?>

<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'alumni') {
    header("Location: ../login.php");
    exit;
}

require_once("../auth/config.php");

$user_id = $_SESSION['user_id'];

// Alumni info
$stmt = $connector->prepare("
    SELECT u.first_name, u.last_name, u.profile_photo, 
           ap.current_position, ap.current_company 
    FROM users u
    LEFT JOIN alumni_profiles ap ON u.id = ap.user_id 
    WHERE u.id = ?
");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->bind_result($first_name, $last_name, $profile_photo, $job_title, $company);
$stmt->fetch();
$stmt->close();

// Stats
$stmt2 = $connector->prepare("SELECT COUNT(*) FROM connection_requests WHERE alumni_id = ? AND status = 'pending'");
$stmt2->bind_param('i', $user_id); $stmt2->execute(); $stmt2->bind_result($pending_count); $stmt2->fetch(); $stmt2->close();

$stmt3 = $connector->prepare("SELECT COUNT(*) FROM connection_requests WHERE alumni_id = ? AND status = 'accepted'");
$stmt3->bind_param('i', $user_id); $stmt3->execute(); $stmt3->bind_result($accepted_count); $stmt3->fetch(); $stmt3->close();

$stmt4 = $connector->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0");
$stmt4->bind_param('i', $user_id); $stmt4->execute(); $stmt4->bind_result($unread_count); $stmt4->fetch(); $stmt4->close();

//$stmt5 = $connector->prepare("SELECT COALESCE(SUM(views), 0) FROM stories WHERE alumni_id = ?");
//$stmt5->bind_param('i', $user_id); $stmt5->execute(); $stmt5->bind_result($story_views); $stmt5->fetch(); $stmt5->close();

// Pending requests (last 3)
$pending_requests = [];
$stmt6 = $connector->prepare("
    SELECT u.first_name, u.last_name, sp.major, sp.current_year, c.id AS connection_id
    FROM connection_requests c 
    JOIN users u ON c.student_id = u.id
    LEFT JOIN student_profiles sp ON u.id = sp.user_id
    WHERE c.alumni_id = ? AND c.status = 'pending'
    ORDER BY c.created_at DESC LIMIT 3
");
$stmt6->bind_param('i', $user_id);
$stmt6->execute();
$res = $stmt6->get_result();
while ($row = $res->fetch_assoc()) $pending_requests[] = $row;
$stmt6->close();

// Formatting vars (used by sidebar + topbar)
$full_name     = htmlspecialchars(($first_name ?? 'Alumni') . ' ' . ($last_name ?? ''));
$first_only    = htmlspecialchars($first_name ?? 'Alumni');
$avatar_letter = strtoupper(substr($first_name ?? 'A', 0, 1));
$display_photo = $profile_photo ?? '';
$pending_count  = (int)($pending_count  ?? 0);
$accepted_count = (int)($accepted_count ?? 0);
$unread_count   = (int)($unread_count   ?? 0);
$story_views    = (int)($story_views    ?? 0);
?>
<?php require_once "../includes/head.php"; ?>
<!-- Lucide icons (loaded here so sidebar + topbar always have it) -->
<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<style>
    body { font-family: 'Inter', sans-serif; }
    ::-webkit-scrollbar { width: 4px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 99px; }
    .nav-link { transition: all .15s ease; }
    .nav-link:hover { background: #eff6ff; color: #2563eb; }
    .nav-link.active { background: #eff6ff; color: #2563eb; font-weight: 600; }
    .stat-card { transition: transform .2s ease, box-shadow .2s ease; }
    .stat-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(37,99,235,.10); }
    @keyframes fadeUp {
        from { opacity: 0; transform: translateY(12px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    .fade-up { animation: fadeUp .35s ease both; }
    .req-row:hover { background: #f8fafc; }
</style>

<div class="flex h-screen overflow-hidden bg-[#f5f6fa]">

    <!-- SIDEBAR -->
    <?php include 'sidebar_alumni.php'; ?>

    <!-- MAIN -->
    <div class="flex flex-col flex-1 min-w-0 overflow-hidden">

        <!-- TOPBAR -->
        <?php include '../student/tools/header.php'; ?>

        <!-- CONTENT -->
        <div class="flex-1 overflow-y-auto">
            <div class="px-8 py-7 max-w-6xl mx-auto space-y-8">

                <!-- Page Header -->
                <div class="flex items-start justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Welcome back, <span class="text-blue-600"><?= $first_only ?></span>!</h1>
                        <p class="text-sm text-gray-400 mt-1">Here's a snapshot of your UCA Connect activity.</p>
                    </div>
                    <p class="text-sm text-gray-400 hidden md:block pt-1"><?= date('l, F j, Y') ?></p>
                </div>

                <!-- Your Engagement at a Glance -->
                <div>
                    <h2 class="text-base font-semibold text-gray-700 mb-4">Your Engagement at a Glance</h2>
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">

                        <!-- Pending Requests -->
                        <div class="stat-card fade-up bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
                            <div class="flex items-center justify-between mb-3">
                                <p class="text-sm text-gray-500 font-medium">Connection Requests</p>
                                <i data-lucide="user-plus" class="w-4 h-4 text-blue-500"></i>
                            </div>
                            <p class="text-4xl font-bold text-gray-900"><?= $pending_count ?></p>
                            <p class="text-xs text-gray-400 mt-1 mb-4">pending from students</p>
                            <a href="requests.php" class="inline-flex items-center gap-1 text-xs font-semibold text-blue-600 hover:underline">
                                View Pending <i data-lucide="arrow-right" class="w-3 h-3"></i>
                            </a>
                        </div>

                        <!-- Accepted Connections -->
                        <div class="stat-card fade-up bg-white rounded-xl border border-gray-200 p-5 shadow-sm" style="animation-delay:.07s">
                            <div class="flex items-center justify-between mb-3">
                                <p class="text-sm text-gray-500 font-medium">Accepted Connections</p>
                                <i data-lucide="user-check" class="w-4 h-4 text-emerald-500"></i>
                            </div>
                            <p class="text-4xl font-bold text-gray-900"><?= $accepted_count ?></p>
                            <p class="text-xs text-gray-400 mt-1">current active connections</p>
                        </div>

                        <!-- Unread Messages -->
                        <div class="stat-card fade-up bg-white rounded-xl border border-gray-200 p-5 shadow-sm" style="animation-delay:.14s">
                            <div class="flex items-center justify-between mb-3">
                                <p class="text-sm text-gray-500 font-medium">Unread Messages</p>
                                <i data-lucide="message-square" class="w-4 h-4 text-orange-400"></i>
                            </div>
                            <p class="text-4xl font-bold text-gray-900"><?= $unread_count ?></p>
                            <p class="text-xs text-gray-400 mt-1 mb-4">from your network</p>
                            <a href="messages.php" class="inline-flex items-center gap-1 text-xs font-semibold text-blue-600 hover:underline">
                                Go to Inbox <i data-lucide="arrow-right" class="w-3 h-3"></i>
                            </a>
                        </div>

                        <!-- Story Views -->
                        <div class="stat-card fade-up bg-white rounded-xl border border-gray-200 p-5 shadow-sm" style="animation-delay:.21s">
                            <div class="flex items-center justify-between mb-3">
                                <p class="text-sm text-gray-500 font-medium">Story Views</p>
                                <i data-lucide="bar-chart-2" class="w-4 h-4 text-purple-500"></i>
                            </div>
                            <p class="text-4xl font-bold text-gray-900"><?= number_format($story_views) ?></p>
                            <p class="text-xs text-gray-400 mt-1 mb-4">on your shared stories</p>
                            <a href="stories.php" class="inline-flex items-center gap-1 text-xs font-semibold text-blue-600 hover:underline">
                                Manage Stories <i data-lucide="arrow-right" class="w-3 h-3"></i>
                            </a>
                        </div>

                    </div>
                </div>

                <!-- Pending Connection Requests -->
                <div>
                    <h2 class="text-base font-semibold text-gray-700 mb-4">Pending Connection Requests</h2>
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">

                        <?php if (empty($pending_requests)): ?>
                            <div class="flex flex-col items-center justify-center py-14">
                                <i data-lucide="inbox" class="w-10 h-10 text-gray-200 mb-2"></i>
                                <p class="text-sm text-gray-400">No pending requests right now</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($pending_requests as $i => $req): ?>
                            <div class="req-row flex items-center gap-4 px-6 py-4 border-b border-gray-100 last:border-0 transition-colors">
                                <!-- Avatar -->
                                <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold text-sm shrink-0">
                                    <?= strtoupper(substr($req['first_name'] ?? '', 0, 1)) ?>
                                </div>
                                <!-- Info -->
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-semibold text-gray-800">
                                        <?= htmlspecialchars($req['first_name'] . ' ' . $req['last_name']) ?>
                                    </p>
                                    <p class="text-xs text-gray-400">
                                        <?= htmlspecialchars($req['major'] ?? 'Student') ?>
                                        <?= !empty($req['current_year']) ? ', ' . htmlspecialchars($req['current_year']) : '' ?>
                                    </p>
                                    <?php if (!empty($req['message'])): ?>
                                    <p class="text-xs text-gray-500 mt-1 italic truncate">"<?= htmlspecialchars($req['message']) ?>"</p>
                                    <?php endif; ?>
                                </div>
                                <!-- Actions -->
                                <div class="flex gap-2 shrink-0">
                                    <form method="POST" action="connection-handler.php">
                                        <input type="hidden" name="connection_id" value="<?= (int)$req['connection_id'] ?>">
                                        <input type="hidden" name="action" value="decline">
                                        <button type="submit" class="px-4 py-1.5 text-xs font-semibold border border-gray-200 rounded-lg text-gray-600 hover:bg-gray-100 transition-colors">
                                            Decline
                                        </button>
                                    </form>
                                    <form method="POST" action="connection-handler.php">
                                        <input type="hidden" name="connection_id" value="<?= (int)$req['connection_id'] ?>">
                                        <input type="hidden" name="action" value="accept">
                                        <button type="submit" class="px-4 py-1.5 text-xs font-semibold bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                            Accept
                                        </button>
                                    </form>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <div class="px-6 py-3 bg-gray-50 border-t border-gray-100">
                                <a href="requests.php" class="text-xs font-semibold text-blue-600 hover:underline flex items-center gap-1">
                                    View all pending requests <i data-lucide="arrow-right" class="w-3 h-3"></i>
                                </a>
                            </div>
                        <?php endif; ?>

                    </div>
                </div>

                <!-- Quick Actions -->
                <div>
                    <h2 class="text-base font-semibold text-gray-700 mb-4">Quick Actions</h2>
                    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">

                        <a href="requests.php" class="stat-card bg-white rounded-xl border border-gray-200 p-5 shadow-sm flex flex-col gap-3 hover:border-blue-200 group">
                            <div class="w-10 h-10 bg-blue-50 rounded-xl flex items-center justify-center group-hover:bg-blue-600 transition-colors">
                                <i data-lucide="users" class="w-5 h-5 text-blue-600 group-hover:text-white transition-colors"></i>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-800">Manage Connections</p>
                                <p class="text-xs text-gray-400 mt-1">Review and organize your student and alumni connections.</p>
                            </div>
                            <span class="inline-flex items-center gap-1 text-xs font-semibold text-blue-600 mt-auto">
                                View All Connections <i data-lucide="arrow-right" class="w-3 h-3"></i>
                            </span>
                        </a>

                        <a href="stories.php" class="stat-card bg-white rounded-xl border border-gray-200 p-5 shadow-sm flex flex-col gap-3 hover:border-purple-200 group">
                            <div class="w-10 h-10 bg-purple-50 rounded-xl flex items-center justify-center group-hover:bg-purple-600 transition-colors">
                                <i data-lucide="book-open" class="w-5 h-5 text-purple-600 group-hover:text-white transition-colors"></i>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-800">Manage Stories</p>
                                <p class="text-xs text-gray-400 mt-1">Create, edit, or remove your success stories and insights.</p>
                            </div>
                            <span class="inline-flex items-center gap-1 text-xs font-semibold text-blue-600 mt-auto">
                                Go to Story Manager <i data-lucide="arrow-right" class="w-3 h-3"></i>
                            </span>
                        </a>

                        <a href="messages.php" class="stat-card bg-white rounded-xl border border-gray-200 p-5 shadow-sm flex flex-col gap-3 hover:border-orange-200 group">
                            <div class="w-10 h-10 bg-orange-50 rounded-xl flex items-center justify-center group-hover:bg-orange-500 transition-colors">
                                <i data-lucide="message-square" class="w-5 h-5 text-orange-500 group-hover:text-white transition-colors"></i>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-800">View Messages</p>
                                <p class="text-xs text-gray-400 mt-1">Read and respond to messages from your connections.</p>
                            </div>
                            <span class="inline-flex items-center gap-1 text-xs font-semibold text-blue-600 mt-auto">
                                Open Inbox <i data-lucide="arrow-right" class="w-3 h-3"></i>
                            </span>
                        </a>

                        <a href="profile.php" class="stat-card bg-white rounded-xl border border-gray-200 p-5 shadow-sm flex flex-col gap-3 hover:border-emerald-200 group">
                            <div class="w-10 h-10 bg-emerald-50 rounded-xl flex items-center justify-center group-hover:bg-emerald-600 transition-colors">
                                <i data-lucide="settings" class="w-5 h-5 text-emerald-600 group-hover:text-white transition-colors"></i>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-800">Update Profile</p>
                                <p class="text-xs text-gray-400 mt-1">Keep your professional details and availability current.</p>
                            </div>
                            <span class="inline-flex items-center gap-1 text-xs font-semibold text-blue-600 mt-auto">
                                Edit My Profile <i data-lucide="arrow-right" class="w-3 h-3"></i>
                            </span>
                        </a>

                    </div>
                </div>

            </div>

            <!-- FOOTER -->
            <footer class="mt-8 px-8 py-4 border-t border-gray-200 bg-white flex items-center justify-between">
                <p class="text-xs text-gray-400">© <?= date('Y') ?> UCA Connect. All rights reserved.</p>
                <p class="text-xs text-gray-300">Alumni Portal</p>
            </footer>

        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof lucide !== 'undefined') lucide.createIcons();
    });
</script>

<?php require_once "../includes/theme.php"; ?>
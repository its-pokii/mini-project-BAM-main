   <?php
include("../student/tools/userHeaderName.php"); 
?>

<?php
ini_set('display_errors', 0);
error_reporting(0);


if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'alumni') {
    header("Location: ../login.php");
    exit;
}

require_once("../auth/config.php");
$user_id = $_SESSION['user_id'];

// CSRF token generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Alumni info (for sidebar/topbar — same as dashboard)
$stmt = $connector->prepare("
    SELECT u.first_name, u.last_name, u.profile_photo, ap.current_position, ap.current_company 
    FROM users u LEFT JOIN alumni_profiles ap ON u.id = ap.user_id WHERE u.id = ?
");
$stmt->bind_param('i', $user_id); $stmt->execute();
$stmt->bind_result($first_name, $last_name, $profile_photo, $job_title, $company);
$stmt->fetch(); $stmt->close();

// Sidebar badge counts
$stmt2 = $connector->prepare("SELECT COUNT(*) FROM connection_requests WHERE alumni_id = ? AND status = 'pending'");
$stmt2->bind_param('i', $user_id); $stmt2->execute(); $stmt2->bind_result($pending_count); $stmt2->fetch(); $stmt2->close();

$stmt3 = $connector->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0");
$stmt3->bind_param('i', $user_id); $stmt3->execute(); $stmt3->bind_result($unread_count); $stmt3->fetch(); $stmt3->close();

// All pending requests
$incoming = [];
$stmt4 = $connector->prepare("
    SELECT u.first_name, u.last_name, u.profile_photo, sp.major, sp.current_year, sp.bio,
           c.id AS connection_id, c.student_id, c.created_at
    FROM connection_requests c
    JOIN users u ON c.student_id = u.id
    LEFT JOIN student_profiles sp ON u.id = sp.user_id
    WHERE c.alumni_id = ? AND c.status = 'pending'
");
$stmt4->bind_param('i', $user_id); $stmt4->execute();
$res = $stmt4->get_result();
while ($row = $res->fetch_assoc()) $incoming[] = $row;
$stmt4->close();

// Accepted connections
$accepted = [];
$stmt5 = $connector->prepare("
    SELECT u.first_name, u.last_name, u.profile_photo, ap2.current_position, ap2.current_company,
           c.id AS connection_id, c.student_id
    FROM connection_requests c
    JOIN users u ON c.student_id = u.id
    LEFT JOIN alumni_profiles ap2 ON u.id = ap2.user_id
    WHERE c.alumni_id = ? AND c.status = 'accepted'
");
$stmt5->bind_param('i', $user_id); $stmt5->execute();
$res = $stmt5->get_result();
while ($row = $res->fetch_assoc()) $accepted[] = $row;
$stmt5->close();

// Format vars (used by sidebar + topbar)
$full_name     = htmlspecialchars(($first_name ?? 'Alumni') . ' ' . ($last_name ?? ''));
$first_only    = htmlspecialchars($first_name ?? 'Alumni');
$avatar_letter = strtoupper(substr($first_name ?? 'A', 0, 1));
$display_photo = $profile_photo ?? '';
$pending_count = (int)($pending_count ?? 0);
$unread_count  = (int)($unread_count  ?? 0);

// Helper: build profile photo src
function photo_src($photo) {
    $p = trim($photo ?? '');
    if (empty($p)) return '';
    return '../' . htmlspecialchars(ltrim($p, './\\'));
}
?>
<?php require_once "../includes/head.php"; ?>
<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
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
    .req-row { transition: background .15s; }
    .req-row:hover { background: #f8fafc; }
    .conn-card { transition: transform .2s ease, box-shadow .2s ease; }
    .conn-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(37,99,235,.08); }
    @keyframes fadeUp {
        from { opacity:0; transform:translateY(10px); }
        to   { opacity:1; transform:translateY(0); }
    }
    .fade-up { animation: fadeUp .3s ease both; }
</style>

<!-- OUTER: full-height column — matches dashboard exactly -->
<div class="flex flex-col h-screen overflow-hidden bg-[#f5f6fa]">

    <!-- TOPBAR — full width across the top -->
    <?php include 'alumni_header.php'; ?>

    <!-- BELOW TOPBAR: sidebar + content side by side -->
    <div class="flex flex-1 overflow-hidden">

        <!-- SIDEBAR -->
        <?php include 'sidebar_alumni.php'; ?>

        <!-- MAIN CONTENT -->
        <div class="flex-1 overflow-y-auto">
            <div class="px-8 py-7 max-w-6xl mx-auto space-y-8">

                <!-- Header -->
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Requests & Connections</h1>
                    <p class="text-sm text-gray-400 mt-1">Review and manage student connection requests.</p>
                </div>

                <!-- INCOMING REQUESTS -->
                <div>
                    <h2 class="text-base font-semibold text-gray-700 mb-4">Incoming Connection Requests
                        <?php if ($pending_count > 0): ?>
                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-blue-100 text-blue-700"><?= $pending_count ?></span>
                        <?php endif; ?>
                    </h2>

                    <?php if (empty($incoming)): ?>
                    <div class="bg-white rounded-xl border border-gray-200 flex flex-col items-center justify-center py-14 shadow-sm">
                        <i data-lucide="inbox" class="w-10 h-10 text-gray-200 mb-3"></i>
                        <p class="text-sm text-gray-400 font-medium">No incoming requests right now</p>
                    </div>
                    <?php else: ?>
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                        <?php foreach ($incoming as $i => $req):
                            $photo    = photo_src($req['profile_photo']);
                            $initials = htmlspecialchars(strtoupper(substr($req['first_name'] ?? '', 0, 1)));
                        ?>
                        <div class="req-row flex items-start gap-4 px-6 py-5 border-b border-gray-100 last:border-0 fade-up" style="animation-delay:<?= $i * 0.06 ?>s">
                            <!-- Avatar -->
                            <div class="w-11 h-11 rounded-full bg-blue-100 overflow-hidden flex items-center justify-center text-blue-600 font-bold text-sm shrink-0"
                                 data-initials="<?= $initials ?>">
                                <?php if ($photo): ?>
                                    <img src="<?= $photo ?>" class="w-full h-full object-cover"
                                         onerror="this.parentElement.textContent=this.parentElement.dataset.initials">
                                <?php else: ?><?= $initials ?><?php endif; ?>
                            </div>
                            <!-- Info -->
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-gray-900">
                                    <?= htmlspecialchars($req['first_name'] . ' ' . $req['last_name']) ?>
                                </p>
                                <p class="text-xs text-gray-500 mt-0.5">
                                    <?= htmlspecialchars($req['major'] ?? 'Student') ?>
                                    <?= !empty($req['current_year']) ? ', Year ' . htmlspecialchars($req['current_year']) : '' ?>
                                </p>
                                <?php $msg = $req['bio'] ?? ''; if ($msg): ?>
                                <p class="text-xs text-gray-400 mt-1.5 line-clamp-2"><?= htmlspecialchars($msg) ?></p>
                                <?php endif; ?>
                            </div>
                            <!-- Actions -->
                            <div class="flex items-center gap-2 shrink-0 pt-0.5">
                                <form method="POST" action="connection-handler.php">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <input type="hidden" name="connection_id" value="<?= (int)$req['connection_id'] ?>">
                                    <input type="hidden" name="action" value="accept">
                                    <button type="submit" class="px-4 py-1.5 text-xs font-semibold bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                        Accept
                                    </button>
                                </form>
                                <form method="POST" action="connection-handler.php">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <input type="hidden" name="connection_id" value="<?= (int)$req['connection_id'] ?>">
                                    <input type="hidden" name="action" value="decline">
                                    <button type="submit" class="px-4 py-1.5 text-xs font-semibold border border-gray-200 rounded-lg text-gray-600 hover:bg-gray-100 transition-colors">
                                        Decline
                                    </button>
                                </form>
                                <a href="student_profile.php?id=<?= (int)$req['student_id'] ?>"
                                   class="px-4 py-1.5 text-xs font-semibold border border-gray-200 rounded-lg text-gray-600 hover:bg-gray-100 transition-colors flex items-center gap-1">
                                    <i data-lucide="user" class="w-3 h-3"></i> View Profile
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- ACCEPTED CONNECTIONS -->
                <div>
                    <h2 class="text-base font-semibold text-gray-700 mb-1">Accepted Connections</h2>
                    <p class="text-sm text-gray-400 mb-4">Your established network within UCA Connect.</p>

                    <?php if (empty($accepted)): ?>
                    <div class="bg-white rounded-xl border border-gray-200 flex flex-col items-center justify-center py-14 shadow-sm">
                        <i data-lucide="users" class="w-10 h-10 text-gray-200 mb-3"></i>
                        <p class="text-sm text-gray-400 font-medium">No accepted connections yet</p>
                    </div>
                    <?php else: ?>
                    <div class="grid grid-cols-2 lg:grid-cols-3 gap-4">
                        <?php foreach ($accepted as $i => $conn):
                            $photo    = photo_src($conn['profile_photo']);
                            $initials = htmlspecialchars(strtoupper(substr($conn['first_name'] ?? '', 0, 1)));
                            $name     = htmlspecialchars($conn['first_name'] . ' ' . $conn['last_name']);
                            $role     = htmlspecialchars(
                                ($conn['current_position'] ?? '') .
                                (!empty($conn['current_company']) ? ' at ' . $conn['current_company'] : '')
                            );
                        ?>
                        <div class="conn-card fade-up bg-white rounded-xl border border-gray-200 shadow-sm p-5 flex flex-col items-center text-center" style="animation-delay:<?= $i * 0.05 ?>s">
                            <div class="w-16 h-16 rounded-full bg-blue-100 overflow-hidden flex items-center justify-center text-blue-600 font-bold text-xl mb-3"
                                 data-initials="<?= $initials ?>">
                                <?php if ($photo): ?>
                                    <img src="<?= $photo ?>" class="w-full h-full object-cover"
                                         onerror="this.parentElement.textContent=this.parentElement.dataset.initials">
                                <?php else: ?><?= $initials ?><?php endif; ?>
                            </div>
                            <p class="text-sm font-semibold text-gray-900"><?= $name ?></p>
                            <?php if ($role): ?>
                            <p class="text-xs text-gray-400 mt-0.5 mb-4 line-clamp-2"><?= $role ?></p>
                            <?php else: ?>
                            <p class="text-xs text-gray-400 mt-0.5 mb-4">Student</p>
                            <?php endif; ?>
                            <div class="flex gap-2 w-full mt-auto">
                                <a href="messages.php?to=<?= (int)$conn['student_id'] ?>"
                                   class="flex-1 flex items-center justify-center gap-1.5 py-1.5 text-xs font-semibold border border-gray-200 rounded-lg text-gray-600 hover:bg-gray-50 transition-colors">
                                    <i data-lucide="message-square" class="w-3 h-3"></i> Message
                                </a>
                                <a href="student_profile.php?id=<?= (int)$conn['student_id'] ?>"
                                   class="flex-1 flex items-center justify-center gap-1.5 py-1.5 text-xs font-semibold border border-gray-200 rounded-lg text-gray-600 hover:bg-gray-50 transition-colors">
                                    <i data-lucide="user" class="w-3 h-3"></i> View Profile
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

            </div>

            <!-- Footer -->
            <footer class="mt-8 px-8 py-4 border-t border-gray-200 bg-white flex items-center justify-between">
                <p class="text-xs text-gray-400">© <?= date('Y') ?> UCA Connect. All rights reserved.</p>
                <p class="text-xs text-gray-300">Alumni Portal</p>
            </footer>
        </div><!-- closes main content -->

    </div><!-- closes sidebar + content row -->

</div><!-- closes outer column -->

<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof lucide !== 'undefined') lucide.createIcons();
    });
</script>
<?php require_once "../includes/theme.php"; ?>
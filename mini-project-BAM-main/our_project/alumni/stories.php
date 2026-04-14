<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'alumni') {
    header("Location: ../login.php");
    exit;
}

require_once("../auth/config.php");
$user_id = $_SESSION['user_id'];

// Alumni info (for sidebar/topbar)
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

// All stories for this alumni
$stories = [];
// $stmt4 = $connector->prepare("
//     SELECT id, title, content, status, views, created_at, updated_at
//     FROM stories
//     WHERE alumni_id = ?
//     ORDER BY created_at DESC
// ");
// $stmt4->bind_param('i', $user_id); $stmt4->execute();
// $res = $stmt4->get_result();
// while ($row = $res->fetch_assoc()) $stories[] = $row;
// $stmt4->close();

// Count by status
$counts = ['published' => 0, 'pending' => 0, 'draft' => 0, 'rejected' => 0];
foreach ($stories as $s) {
    $st = strtolower($s['status'] ?? 'draft');
    if (isset($counts[$st])) $counts[$st]++;
}

// Format vars for sidebar/topbar
$full_name     = htmlspecialchars(($first_name ?? 'Alumni') . ' ' . ($last_name ?? ''));
$first_only    = htmlspecialchars($first_name ?? 'Alumni');
$avatar_letter = strtoupper(substr($first_name ?? 'A', 0, 1));
$display_photo = $profile_photo ?? '';
$pending_count = (int)($pending_count ?? 0);
$unread_count  = (int)($unread_count  ?? 0);

// Status badge style helper
function status_badge($status) {
    return match(strtolower($status)) {
        'published' => 'bg-emerald-50 text-emerald-700 border border-emerald-200',
        'pending'   => 'bg-amber-50 text-amber-700 border border-amber-200',
        'draft'     => 'bg-gray-100 text-gray-600 border border-gray-200',
        'rejected'  => 'bg-red-50 text-red-600 border border-red-200',
        default     => 'bg-gray-100 text-gray-500 border border-gray-200',
    };
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
    .tab-btn { transition: all .15s; border-bottom: 2px solid transparent; }
    .tab-btn.active { border-bottom-color: #2563eb; color: #2563eb; font-weight: 600; }
    .story-row { transition: background .15s; }
    .story-row:hover { background: #f8fafc; }
    @keyframes fadeUp {
        from { opacity:0; transform:translateY(8px); }
        to   { opacity:1; transform:translateY(0); }
    }
    .fade-up { animation: fadeUp .3s ease both; }
</style>

<div class="flex h-screen overflow-hidden bg-[#f5f6fa]">
    <?php include 'sidebar_alumni.php'; ?>

    <div class="flex flex-col flex-1 min-w-0 overflow-hidden">
        <?php include 'Topbar_alumni.php'; ?>

        <div class="flex-1 overflow-y-auto">
            <div class="px-8 py-7 max-w-5xl mx-auto space-y-6">

                <!-- Header -->
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Stories Management</h1>
                        <p class="text-sm text-gray-400 mt-1">Manage your success stories, submit new ones, and track their approval status.</p>
                    </div>
                    <a href="submit-story.php"
                       class="shrink-0 inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700 transition-colors shadow-sm">
                        <i data-lucide="plus" class="w-4 h-4"></i> Submit New Story
                    </a>
                </div>

                <!-- Status Tabs -->
                <div class="flex items-center gap-1 border-b border-gray-200">
                    <button onclick="filterStories('all')" id="tab-all"
                            class="tab-btn active px-4 py-2.5 text-sm text-gray-600">
                        All <span class="ml-1 text-xs font-bold text-gray-400"><?= count($stories) ?></span>
                    </button>
                    <button onclick="filterStories('published')" id="tab-published"
                            class="tab-btn px-4 py-2.5 text-sm text-gray-600">
                        Published <span class="ml-1 text-xs font-bold text-emerald-600"><?= $counts['published'] ?></span>
                    </button>
                    <button onclick="filterStories('pending')" id="tab-pending"
                            class="tab-btn px-4 py-2.5 text-sm text-gray-600">
                        Pending Approval <span class="ml-1 text-xs font-bold text-amber-600"><?= $counts['pending'] ?></span>
                    </button>
                    <button onclick="filterStories('draft')" id="tab-draft"
                            class="tab-btn px-4 py-2.5 text-sm text-gray-600">
                        Drafts <span class="ml-1 text-xs font-bold text-gray-400"><?= $counts['draft'] ?></span>
                    </button>
                    <button onclick="filterStories('rejected')" id="tab-rejected"
                            class="tab-btn px-4 py-2.5 text-sm text-gray-600">
                        Rejected <span class="ml-1 text-xs font-bold text-red-500"><?= $counts['rejected'] ?></span>
                    </button>
                </div>

                <!-- Search + Table -->
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">

                    <!-- Search bar -->
                    <div class="px-5 py-3 border-b border-gray-100">
                        <div class="relative">
                            <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"></i>
                            <input type="text"
                                   id="story-search"
                                   placeholder="Search your stories..."
                                   oninput="searchStories(this.value)"
                                   class="w-full pl-9 pr-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-400 transition-all">
                        </div>
                    </div>

                    <?php if (empty($stories)): ?>
                    <div class="flex flex-col items-center justify-center py-16">
                        <i data-lucide="book-open" class="w-10 h-10 text-gray-200 mb-3"></i>
                        <p class="text-sm text-gray-400 font-medium">No stories yet</p>
                        <p class="text-xs text-gray-300 mt-1">Submit your first story to inspire students</p>
                    </div>
                    <?php else: ?>

                    <!-- Table header -->
                    <div class="grid grid-cols-[1fr_140px_130px_120px] gap-4 px-6 py-3 border-b border-gray-100 bg-gray-50">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Title</p>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Date</p>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</p>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide text-right">Actions</p>
                    </div>

                    <!-- Story rows -->
                    <div id="stories-list">
                        <?php foreach ($stories as $i => $story):
                            $excerpt = htmlspecialchars(substr(strip_tags($story['content'] ?? ''), 0, 120));
                            $status = strtolower($story['status'] ?? 'draft');
                            $badge   = status_badge($status);
                            $date    = date('Y-m-d', strtotime($story['created_at']));
                        ?>
                        <div class="story-row grid grid-cols-[1fr_140px_130px_120px] gap-4 items-start px-6 py-4 border-b border-gray-100 last:border-0 fade-up"
                             data-status="<?= $status ?>"
                             data-title="<?= strtolower(htmlspecialchars($story['title'] ?? '')) ?>"
                             style="animation-delay:<?= $i * 0.05 ?>s">

                            <!-- Title + excerpt -->
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-gray-900 leading-snug"><?= htmlspecialchars($story['title'] ?? 'Untitled') ?></p>
                                <?php if ($excerpt): ?>
                                <p class="text-xs text-gray-400 mt-1 line-clamp-2"><?= $excerpt ?>...</p>
                                <?php endif; ?>
                            </div>

                            <!-- Date -->
                            <p class="text-sm text-gray-500 pt-0.5"><?= $date ?></p>

                            <!-- Status badge -->
                            <div class="pt-0.5">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold <?= $badge ?>">
                                    <?= ucfirst($status) ?>
                                </span>
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center justify-end gap-1 pt-0.5">
                                <a href="edit-story.php?id=<?= (int)$story['id'] ?>"
                                   title="Edit"
                                   class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-blue-600 hover:bg-blue-50 transition-colors">
                                    <i data-lucide="pencil" class="w-4 h-4"></i>
                                </a>
                                <a href="view-story.php?id=<?= (int)$story['id'] ?>"
                                   title="View"
                                   class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-emerald-600 hover:bg-emerald-50 transition-colors">
                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                </a>
                                <button onclick="confirmDelete(<?= (int)$story['id'] ?>)"
                                        title="Delete"
                                        class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
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
        </div>
    </div>
</div>

<!-- Delete confirm modal -->
<div id="delete-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/30 backdrop-blur-sm">
    <div class="bg-white rounded-2xl shadow-xl p-6 w-full max-w-sm mx-4">
        <div class="w-12 h-12 bg-red-50 rounded-full flex items-center justify-center mx-auto mb-4">
            <i data-lucide="trash-2" class="w-5 h-5 text-red-500"></i>
        </div>
        <h3 class="text-base font-bold text-gray-900 text-center">Delete Story?</h3>
        <p class="text-sm text-gray-400 text-center mt-1 mb-5">This action cannot be undone.</p>
        <div class="flex gap-3">
            <button onclick="closeDelete()" class="flex-1 py-2 text-sm font-semibold border border-gray-200 rounded-lg text-gray-600 hover:bg-gray-50 transition-colors">
                Cancel
            </button>
            <form method="POST" action="story-handler.php" class="flex-1">
                <input type="hidden" name="story_id" id="delete-story-id">
                <input type="hidden" name="action" value="delete">
                <button type="submit" class="w-full py-2 text-sm font-semibold bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                    Delete
                </button>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof lucide !== 'undefined') lucide.createIcons();
    });

    let currentFilter = 'all';

    function filterStories(status) {
        currentFilter = status;
        // Update tab styles
        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
        document.getElementById('tab-' + status).classList.add('active');
        applyFilters();
    }

    function searchStories(query) {
        applyFilters(query.toLowerCase());
    }

    function applyFilters(query = '') {
        const q = query || document.getElementById('story-search').value.toLowerCase();
        document.querySelectorAll('#stories-list .story-row').forEach(row => {
            const matchStatus = currentFilter === 'all' || row.dataset.status === currentFilter;
            const matchQuery  = !q || row.dataset.title.includes(q);
            row.style.display = (matchStatus && matchQuery) ? '' : 'none';
        });
    }

    function confirmDelete(id) {
        document.getElementById('delete-story-id').value = id;
        document.getElementById('delete-modal').classList.remove('hidden');
    }

    function closeDelete() {
        document.getElementById('delete-modal').classList.add('hidden');
    }
</script>
<?php require_once "../includes/theme.php"; ?>
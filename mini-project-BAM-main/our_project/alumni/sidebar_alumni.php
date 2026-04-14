<?php
// Photo stored in DB as: "uploads/profiles/alumni_xxx.jpg"  (relative to project root our_project/)
// This file lives at:     our_project/alumni/sidebar_alumni.php
// So correct web path is: ../uploads/profiles/alumni_xxx.jpg
$_photo_raw = trim($profile_photo ?? $display_photo ?? '');
if (!empty($_photo_raw)) {
    // Strip any accidental leading slashes or ../
    $_photo_clean = ltrim($_photo_raw, './\\');
    $sidebar_photo_src = '../' . htmlspecialchars($_photo_clean);
} else {
    $sidebar_photo_src = '';
}
?>
<aside class="w-[220px] shrink-0 bg-white border-r border-gray-200 flex flex-col h-full">

    <!-- Logo -->
    <div class="h-16 flex items-center gap-2.5 px-5 border-b border-gray-100">
        <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center">
            <i data-lucide="zap" class="w-4 h-4 text-white fill-white"></i>
        </div>
        <span class="font-bold text-gray-900 text-[15px] tracking-tight">UCA Connect</span>
    </div>

    <!-- Nav -->
    <nav class="flex-1 p-3 flex flex-col gap-0.5 overflow-y-auto">
        <a href="dashboard_alumni.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'dashboard_alumni.php' ? 'active' : '' ?> flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-gray-600">
            <i data-lucide="layout-dashboard" class="w-4 h-4 shrink-0"></i> Dashboard
        </a>
        <a href="requests.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'requests.php' ? 'active' : '' ?> flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-gray-600">
            <i data-lucide="users" class="w-4 h-4 shrink-0"></i>
            Requests & Connections
            <?php if (!empty($pending_count) && $pending_count > 0): ?>
            <span class="ml-auto bg-blue-600 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full"><?= $pending_count ?></span>
            <?php endif; ?>
        </a>
        <a href="messages.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'messages.php' ? 'active' : '' ?> flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-gray-600">
            <i data-lucide="message-square" class="w-4 h-4 shrink-0"></i>
            Messages
            <?php if (!empty($unread_count) && $unread_count > 0): ?>
            <span class="ml-auto bg-red-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full"><?= $unread_count ?></span>
            <?php endif; ?>
        </a>
        <a href="stories.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'stories.php' ? 'active' : '' ?> flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-gray-600">
            <i data-lucide="book-open" class="w-4 h-4 shrink-0"></i> Stories Management
        </a>
    </nav>

    <!-- Bottom: Profile & Settings + User Card -->
    <div class="p-3 border-t border-gray-100">
        <a href="profile.php" class="nav-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-gray-600">
            <i data-lucide="settings" class="w-4 h-4 shrink-0"></i> Profile & Settings
        </a>

        <!-- User card -->
        <div class="mt-2 flex items-center gap-3 px-3 py-2">
            <div class="w-9 h-9 rounded-xl bg-blue-600 overflow-hidden flex items-center justify-center text-white font-bold text-sm shrink-0"
                 id="sidebar-avatar-wrap">
                <?php if (!empty($sidebar_photo_src)): ?>
                    <img src="<?= $sidebar_photo_src ?>"
                         alt="<?= htmlspecialchars($full_name ?? '') ?>"
                         class="w-full h-full object-cover"
                         onerror="this.parentElement.innerHTML='<?= addslashes($avatar_letter ?? 'A') ?>';">
                <?php else: ?>
                    <?= $avatar_letter ?? 'A' ?>
                <?php endif; ?>
            </div>
            <div class="min-w-0">
                <p class="text-xs font-semibold text-gray-800 truncate"><?= $full_name ?? 'Alumni' ?></p>
                <p class="text-[11px] text-gray-400 truncate"><?= htmlspecialchars($job_title ?? 'Alumni') ?></p>
            </div>
        </div>
    </div>

</aside>
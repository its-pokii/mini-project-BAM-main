<?php
// Photo stored in DB as: "uploads/photos/64_xxx.png" (relative to our_project/student/)
// This file lives at:     our_project/student/sidebar_student.php
// So correct web path is: uploads/photos/xxx.png (same folder level)
$_photo_raw = trim($user['profile_photo'] ?? '');
if (!empty($_photo_raw)) {
    $_photo_clean = ltrim($_photo_raw, './\\');
    $sidebar_photo_src = htmlspecialchars($_photo_clean);
} else {
    $sidebar_photo_src = '';
}

$_first   = $user['first_name'] ?? 'Student';
$_last    = $user['last_name']  ?? '';
$_full    = htmlspecialchars(trim($_first . ' ' . $_last));
$_letter  = strtoupper(substr($_first, 0, 1));
$_major   = htmlspecialchars($user['major'] ?? 'ENSA Safi');
$_current = basename($_SERVER['PHP_SELF']);
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
        <a href="01-dashboard.php" class="nav-link <?= $_current === '01-dashboard.php' ? 'active' : '' ?> flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-gray-600">
            <i data-lucide="layout-dashboard" class="w-4 h-4 shrink-0"></i> Dashboard
        </a>
        <a href="02-find-alumni.php" class="nav-link <?= $_current === '02-find-alumni.php' ? 'active' : '' ?> flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-gray-600">
            <i data-lucide="search" class="w-4 h-4 shrink-0"></i> Find Alumni
        </a>
        <a href="04-connections.php" class="nav-link <?= $_current === '04-connections.php' ? 'active' : '' ?> flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-gray-600">
            <i data-lucide="users" class="w-4 h-4 shrink-0"></i> Connections
        </a>
        <a href="05-messages.php" class="nav-link <?= $_current === '05-messages.php' ? 'active' : '' ?> flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-gray-600">
            <i data-lucide="message-square" class="w-4 h-4 shrink-0"></i>
            Messages
            <?php if (!empty($unread_count) && $unread_count > 0): ?>
            <span class="ml-auto bg-red-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full"><?= (int)$unread_count ?></span>
            <?php endif; ?>
        </a>
        <a href="06-stories.php" class="nav-link <?= $_current === '06-stories.php' ? 'active' : '' ?> flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-gray-600">
            <i data-lucide="book-open" class="w-4 h-4 shrink-0"></i> Stories
        </a>
    </nav>

    <!-- Bottom: Settings + User Card -->
    <div class="p-3 border-t border-gray-100">
        <a href="settings.php" class="nav-link <?= $_current === 'settings.php' ? 'active' : '' ?> flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-gray-600">
            <i data-lucide="settings" class="w-4 h-4 shrink-0"></i> Profile &amp; Settings
        </a>

        <!-- User card -->
        <div class="mt-2 flex items-center gap-3 px-3 py-2">
            <div class="w-9 h-9 rounded-xl bg-blue-600 overflow-hidden flex items-center justify-center text-white font-bold text-sm shrink-0">
                <?php if (!empty($sidebar_photo_src)): ?>
                    <img src="<?= $sidebar_photo_src ?>"
                         alt="<?= $_full ?>"
                         class="w-full h-full object-cover"
                         onerror="this.parentElement.innerHTML='<?= addslashes($_letter) ?>';">
                <?php else: ?>
                    <?= $_letter ?>
                <?php endif; ?>
            </div>
            <div class="min-w-0">
                <p class="text-xs font-semibold text-gray-800 truncate"><?= $_full ?></p>
                <p class="text-[11px] text-gray-400 truncate"><?= $_major ?></p>
            </div>
        </div>
    </div>

</aside>
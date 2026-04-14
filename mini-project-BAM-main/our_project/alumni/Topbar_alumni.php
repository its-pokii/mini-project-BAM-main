<?php
$_photo_raw = trim($display_photo ?? $profile_photo ?? '');
if (!empty($_photo_raw)) {
    $_photo_clean = ltrim($_photo_raw, './\\');
    $topbar_photo_src = '../' . htmlspecialchars($_photo_clean);
} else {
    $topbar_photo_src = '';
}
?>
<header class="h-16 bg-white border-b border-gray-200 flex items-center justify-end px-6 shrink-0">
    <div class="flex items-center gap-3">

        <!-- Bell -->
        <button class="relative w-9 h-9 flex items-center justify-center rounded-xl border border-gray-200 bg-white hover:bg-gray-50 transition-colors">
            <i data-lucide="bell" class="w-4 h-4 text-gray-500"></i>
            <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-red-500 rounded-full border-2 border-white"></span>
        </button>

        <!-- Avatar -->
        <a href="profile.php"
           class="w-9 h-9 rounded-xl bg-blue-600 overflow-hidden flex items-center justify-center text-white font-bold text-sm cursor-pointer hover:opacity-90 transition-opacity"
           id="topbar-avatar-wrap">
            <?php if (!empty($topbar_photo_src)): ?>
                <img src="<?= $topbar_photo_src ?>"
                     alt="Profile"
                     class="w-full h-full object-cover"
                     onerror="this.parentElement.innerHTML='<?= addslashes($avatar_letter ?? 'A') ?>';">
            <?php else: ?>
                <?= $avatar_letter ?? 'A' ?>
            <?php endif; ?>
        </a>

    </div>
</header>
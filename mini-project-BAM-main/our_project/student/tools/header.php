<header>
<div class="h-14 bg-white border-b border-gray-200 flex items-center justify-between px-6 flex-shrink-0">
    <div class="flex items-center gap-2 font-bold text-lg text-blue-600">
      UCA Connect
    </div>
    <div class="flex-1 max-w-sm mx-6 flex items-center bg-gray-50 border border-gray-200 rounded-lg px-3 py-1.5 gap-2">
      <!-- <span class="text-gray-400">🔍</span> -->
      <input class="border-none bg-transparent outline-none text-sm w-full" placeholder="Search..." />
    </div>
    <div class="flex items-center gap-4">
      <div class="flex items-center gap-2">
        <?php if (!empty($user['profile_photo'])): ?>
    <img 
        src="../<?= htmlspecialchars($user['profile_photo']) ?>"
        alt="Profile Photo"
        class="w-12 h-12 rounded-full object-cover"
    />
<?php else: ?>
    <!-- fallback — show initials if no photo -->
    <div class="w-12 h-12 rounded-full bg-blue-500 flex items-center justify-center text-white font-bold">
        <?= strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1)) ?>
    </div>
<?php endif; ?>
        <span class="text-sm font-medium text-gray-700">Hello, <span class="font-semibold text-blue-600"><?= htmlspecialchars($user['first_name'])?></span></span>
      </div>
    </div>
  </div>
  </header>
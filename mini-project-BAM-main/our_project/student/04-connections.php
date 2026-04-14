<?php
include("tools/userHeaderName.php"); 

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>UCA Connect – Connections</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="font-sans bg-gray-50 min-h-screen flex flex-col">

  <!-- TOP BAR -->
  <?php include("tools/header.php"); ?>

  <div class="flex flex-1 overflow-hidden">

    <!-- SIDEBAR -->
    <div class="w-56 border-r border-gray-200 flex flex-col justify-between py-6 bg-white flex-shrink-0">
      <nav class="flex flex-col">
        <a href="01-dashboard.php"   class="flex items-center gap-2.5 px-6 py-2.5 text-sm text-gray-900 border-r-4 border-transparent hover:bg-gray-100 no-underline transition-colors">Dashboard</a>
        <a href="02-find-alumni.php" class="flex items-center gap-2.5 px-6 py-2.5 text-sm text-gray-900 border-r-4 border-transparent hover:bg-gray-100 no-underline transition-colors">Find Alumni</a>
        <a href="04-connections.php" class="flex items-center gap-2.5 px-6 py-2.5 text-sm font-semibold text-blue-600 bg-blue-50 border-r-4 border-blue-600 no-underline">Connections</a>
        <a href="05-messages.php"    class="flex items-center gap-2.5 px-6 py-2.5 text-sm text-gray-900 border-r-4 border-transparent hover:bg-gray-100 no-underline transition-colors">Messages</a>
        <a href="06-stories.php"     class="flex items-center gap-2.5 px-6 py-2.5 text-sm text-gray-900 border-r-4 border-transparent hover:bg-gray-100 no-underline transition-colors">Stories</a>
      </nav>
      <a href="settings.php" class="flex items-center gap-2.5 px-6 py-2.5 text-sm text-gray-500 no-underline">Profile &amp; Settings</a>
    </div>

    <!-- MAIN -->
    <div class="flex-1 p-8 pb-16 overflow-y-auto">
      <h1 class="text-2xl font-bold text-gray-900 mb-6">Your Connections</h1>

      <!-- ACCEPTED -->
      <div class="mb-8">
        <h2 class="text-base font-semibold text-gray-900 mb-4">
          Accepted Connections
          <span id="acceptedCount" class="ml-2 bg-blue-600 text-white text-xs font-bold px-2 py-0.5 rounded-full">0</span>
        </h2>
        <div id="acceptedLoading" class="text-gray-400 text-sm mb-4">Loading...</div>
        <div id="acceptedGrid" class="grid grid-cols-3 gap-4"></div>
      </div>

      <!-- PENDING -->
      <div class="mb-8">
        <h2 class="text-base font-semibold text-gray-900 mb-4">
          Pending Requests (Sent)
          <span id="pendingCount" class="ml-2 bg-yellow-400 text-white text-xs font-bold px-2 py-0.5 rounded-full">0</span>
        </h2>
        <div id="pendingLoading" class="text-gray-400 text-sm mb-4">Loading...</div>
        <div id="pendingGrid" class="grid grid-cols-3 gap-4"></div>
      </div>
    </div>

  </div><!-- ← flex div closes HERE -->

  <!-- FOOTER -->
  <?php include("tools/footer.php"); ?>

<script>
const colors = [
  "#3B82F6","#10B981","#F59E0B","#EF4444",
  "#8B5CF6","#06B6D4","#EC4899","#F97316"
];

function initials(name) {
  if (!name) return '??';
  return name.split(' ').map(n => n[0]).join('').slice(0, 2).toUpperCase();
}

function avatarHTML(user, index) {
  return user.profile_photo
    ? `<img src="../uploads/${user.profile_photo}" 
            class="w-12 h-12 rounded-full object-cover flex-shrink-0" />`
    : `<div class="w-12 h-12 rounded-full flex items-center justify-center text-white font-bold text-base flex-shrink-0"
             style="background:${colors[index % colors.length]}">
         ${initials(user.first_name + ' ' + user.last_name)}
       </div>`;
}

// ─── Render Accepted ──────────────────────────────────────────
function renderAccepted(list) {
  document.getElementById('acceptedLoading').style.display = 'none';
  document.getElementById('acceptedCount').textContent     = list.length;
  const grid = document.getElementById('acceptedGrid');

  if (list.length === 0) {
    grid.innerHTML = '<p class="text-gray-400 text-sm">No accepted connections yet.</p>';
    return;
  }

  grid.innerHTML = list.map((c, index) => `
    <div class="bg-white border border-gray-200 rounded-xl p-5" id="accepted-${c.connection_id}">
      <div class="flex gap-3 items-center mb-3">
        ${avatarHTML(c, index)}
        <div>
          <div class="font-semibold text-sm text-gray-900">${c.first_name} ${c.last_name}</div>
          <div class="text-xs text-gray-500 mt-0.5">${c.current_position ?? 'Position not set'}</div>
          <div class="text-xs text-gray-400 mt-0.5">${c.current_company ?? ''}</div>
        </div>
      </div>
      <div class="flex gap-2">
        <a href="03-profile-view.php?id=${c.user_id}"
           class="flex-1 bg-white text-gray-900 border border-gray-200 rounded-lg py-2 text-xs text-center cursor-pointer hover:bg-gray-50 transition-colors no-underline">
          View Profile
        </a>
        <a href="05-messages.php?id=${c.user_id}"
           class="flex-1 bg-blue-600 hover:bg-blue-700 text-white border-none rounded-lg py-2 text-xs font-semibold text-center cursor-pointer transition-colors no-underline">
          Message
        </a>
      </div>
    </div>
  `).join('');
}

// ─── Render Pending ───────────────────────────────────────────
function renderPending(list) {
  document.getElementById('pendingLoading').style.display = 'none';
  document.getElementById('pendingCount').textContent     = list.length;
  const grid = document.getElementById('pendingGrid');

  if (list.length === 0) {
    grid.innerHTML = '<p class="text-gray-400 text-sm">No pending requests sent.</p>';
    return;
  }

  grid.innerHTML = list.map((c, index) => `
    <div class="bg-white border border-gray-200 rounded-xl p-5" id="pending-${c.connection_id}">
      <div class="flex gap-3 items-center mb-3">
        ${avatarHTML(c, index)}
        <div>
          <div class="font-semibold text-sm text-gray-900">${c.first_name} ${c.last_name}</div>
          <div class="text-xs text-gray-500 mt-0.5">${c.current_position ?? 'Position not set'}</div>
          <span class="inline-block mt-1 bg-yellow-100 text-yellow-600 text-xs font-semibold px-2 py-0.5 rounded">Pending</span>
        </div>
      </div>
      <div class="flex gap-2">
        <a href="03-profile-view.php?id=${c.user_id}"
           class="flex-1 bg-white text-gray-900 border border-gray-200 rounded-lg py-2 text-xs text-center cursor-pointer hover:bg-gray-50 transition-colors no-underline">
          View Profile
        </a>
        <button
          onclick="retractRequest(${c.connection_id})"
          class="flex-1 bg-red-600 hover:bg-red-700 text-white border-none rounded-lg py-2 text-xs font-semibold cursor-pointer transition-colors">
          Retract Request
        </button>
      </div>
    </div>
  `).join('');
}

// ─── Retract Request ──────────────────────────────────────────
function retractRequest(connectionId) {
  const card = document.getElementById(`pending-${connectionId}`);
  const btns = card.querySelectorAll('button');
  btns.forEach(b => b.disabled = true);

  fetch('tools/retractConnection.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ connection_id: connectionId })
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      card.innerHTML = `<div class="p-4 text-center text-gray-400 text-sm">✓ Request retracted</div>`;
      setTimeout(() => {
        card.remove();
        const count = document.getElementById('pendingCount');
        count.textContent = Math.max(0, parseInt(count.textContent) - 1);
      }, 1500);
    } else {
      alert(data.message);
      btns.forEach(b => b.disabled = false);
    }
  })
  .catch(err => {
    console.error('Error:', err);
    btns.forEach(b => b.disabled = false);
  });
}

// ─── Load on Page Start ───────────────────────────────────────
fetch('tools/getMyConnections.php')
  .then(res => res.json())
  .then(data => {
    if (!data.success) { console.error(data.message); return; }
    renderAccepted(data.accepted);
    renderPending(data.pending);
  })
  .catch(err => {
    console.error('Error:', err);
    document.getElementById('acceptedLoading').textContent = 'Failed to load.';
    document.getElementById('pendingLoading').textContent  = 'Failed to load.';
  });
</script>
</body>
</html>

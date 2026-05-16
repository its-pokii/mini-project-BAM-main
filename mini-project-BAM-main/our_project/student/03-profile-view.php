<?php
include("tools/userHeaderName.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit;
}

if (!isset($_GET['id'])) {
    header('Location: 02-find-alumni.php');
    exit;
}

$profile_id = (int)$_GET['id'];
$_SESSION['profile_id'] = $profile_id;

// FIX 1: Fetch alumni profile + redirect if not found
$stmt = mysqli_prepare($connector, "
    SELECT u.first_name, u.last_name, u.profile_photo,
           ap.*
    FROM users u
    LEFT JOIN alumni_profiles ap ON ap.user_id = u.id
    WHERE u.id = ?
");
mysqli_stmt_bind_param($stmt, "i", $profile_id);
mysqli_stmt_execute($stmt);
$result  = mysqli_stmt_get_result($stmt);
$profile = mysqli_fetch_assoc($result);

if (!$profile) {
    header('Location: 02-find-alumni.php');
    exit;
}

// Connection status
$connection_status = null;
if ($user_id) {
    $stmt2 = mysqli_prepare($connector,
        "SELECT status FROM connection_requests WHERE student_id = ? AND alumni_id = ?"
    );
    mysqli_stmt_bind_param($stmt2, "ii", $user_id, $profile_id);
    mysqli_stmt_execute($stmt2);
    $result2 = mysqli_stmt_get_result($stmt2);
    $conn    = mysqli_fetch_assoc($result2);
    $connection_status = $conn['status'] ?? null;
}

// FIX 3: Dynamic avatar
$initials  = strtoupper(substr($profile['first_name'] ?? 'A', 0, 1) . substr($profile['last_name'] ?? '', 0, 1));
$photo_raw = trim($profile['profile_photo'] ?? '');
$photo_src = !empty($photo_raw) ? htmlspecialchars('../' . ltrim($photo_raw, './\\')) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>UCA Connect – Profile</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="font-sans bg-gray-50 h-screen flex flex-col">

  <!-- TOP BAR -->
  <?php include("tools/header.php"); ?>

  <div class="flex flex-1 overflow-hidden">

    <!-- SIDEBAR (original, unchanged) -->
    <div class="w-56 border-r border-gray-200 flex flex-col justify-between py-6 bg-white flex-shrink-0">
      <nav class="flex flex-col">
        <a href="01-dashboard.php"   class="flex items-center gap-2.5 px-6 py-2.5 text-sm text-gray-900 border-r-4 border-transparent hover:bg-gray-100 no-underline transition-colors">Dashboard</a>
        <a href="02-find-alumni.php" class="flex items-center gap-2.5 px-6 py-2.5 text-sm font-semibold text-blue-600 bg-blue-50 border-r-4 border-blue-600 no-underline">Find Alumni</a>
        <a href="04-connections.php" class="flex items-center gap-2.5 px-6 py-2.5 text-sm text-gray-900 border-r-4 border-transparent hover:bg-gray-100 no-underline transition-colors">Connections</a>
        <a href="05-messages.php"    class="flex items-center gap-2.5 px-6 py-2.5 text-sm text-gray-900 border-r-4 border-transparent hover:bg-gray-100 no-underline transition-colors">Messages</a>
        <a href="06-stories.php"     class="flex items-center gap-2.5 px-6 py-2.5 text-sm text-gray-900 border-r-4 border-transparent hover:bg-gray-100 no-underline transition-colors">Stories</a>
      </nav>
      <a href="settings.php" class="flex items-center gap-2.5 px-6 py-2.5 text-sm text-gray-500 no-underline">Profile &amp; Settings</a>
    </div>

    <!-- MAIN -->
    <div class="flex-1 p-8 overflow-y-auto">

      <!-- HEADER CARD -->
      <div class="bg-white border border-gray-200 rounded-xl p-6 mb-4">
        <div class="flex items-start justify-between">
          <div class="flex gap-4">

            <!-- FIX 3: Real photo or dynamic initials -->
            <div class="w-[72px] h-[72px] rounded-full bg-blue-500 flex items-center justify-center text-white text-2xl font-bold flex-shrink-0 overflow-hidden">
              <?php if ($photo_src): ?>
                <img src="<?= $photo_src ?>" alt="<?= htmlspecialchars($initials) ?>"
                     class="w-full h-full object-cover"
                     onerror="this.parentElement.innerHTML='<?= addslashes($initials) ?>'">
              <?php else: ?>
                <?= $initials ?>
              <?php endif; ?>
            </div>

            <div>
              <div class="text-xl font-bold text-gray-900">
                <?= htmlspecialchars($profile['first_name'] . ' ' . $profile['last_name']) ?>
              </div>
              <div class="text-sm text-gray-500 mt-1">
                <?= htmlspecialchars($profile['current_position'] ?? 'Position not set') ?>
                <?php if (!empty($profile['current_company'])): ?>
                  · <?= htmlspecialchars($profile['current_company']) ?>
                <?php endif; ?>
              </div>
              <?php if (!empty($profile['location'])): ?>
              <div class="text-sm text-gray-500 mt-0.5">📍 <?= htmlspecialchars($profile['location']) ?></div>
              <?php endif; ?>
              <?php if (($profile['willing_to_mentor'] ?? '') === 'Yes'): ?>
                <span class="inline-block mt-1.5 bg-blue-100 text-blue-600 text-xs font-semibold px-2.5 py-0.5 rounded-full">Willing to Mentor</span>
              <?php endif; ?>
            </div>
          </div>

          <!-- Connect button -->
          <?php if ($connection_status === 'pending'): ?>
            <button id="connectBtn" disabled
              class="px-4 py-2 bg-gray-200 text-gray-500 rounded-lg text-sm font-semibold cursor-not-allowed">
              ✓ Connection Sent
            </button>
          <?php elseif ($connection_status === 'accepted'): ?>
            <button id="connectBtn" disabled
              class="px-4 py-2 bg-green-100 text-green-600 rounded-lg text-sm font-semibold cursor-not-allowed">
              ✓ Connected
            </button>
          <?php else: ?>
            <button id="connectBtn" onclick="sendConnection(<?= $profile_id ?>)"
              class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-semibold transition-colors">
              Connect
            </button>
          <?php endif; ?>
        </div>
      </div>

      <!-- ABOUT -->
      <div class="bg-white border border-gray-200 rounded-xl p-6 mb-4">
        <h3 class="text-base font-bold text-gray-900 mb-4">About</h3>
        <p class="text-sm text-gray-500 leading-relaxed">
          <?= nl2br(htmlspecialchars($profile['bio'] ?? 'No bio available.')) ?>
        </p>
      </div>

      <!-- FIX 4: Experience from DB (only shown if data exists) -->
      <?php if (!empty($profile['experience'])): ?>
      <div class="bg-white border border-gray-200 rounded-xl p-6 mb-4">
        <h3 class="text-base font-bold text-gray-900 mb-4">Professional Experience</h3>
        <p class="text-sm text-gray-500 leading-relaxed"><?= nl2br(htmlspecialchars($profile['experience'])) ?></p>
      </div>
      <?php endif; ?>

      <!-- FIX 4: Education from DB (only shown if data exists) -->
      <?php if (!empty($profile['education'])): ?>
      <div class="bg-white border border-gray-200 rounded-xl p-6 mb-4">
        <h3 class="text-base font-bold text-gray-900 mb-4">Education</h3>
        <p class="text-sm text-gray-500 leading-relaxed"><?= nl2br(htmlspecialchars($profile['education'])) ?></p>
      </div>
      <?php endif; ?>

      <!-- FIX 4: Skills from DB (only shown if data exists) -->
      <?php if (!empty($profile['skills'])): ?>
      <div class="bg-white border border-gray-200 rounded-xl p-6">
        <h3 class="text-base font-bold text-gray-900 mb-4">Skills &amp; Expertise</h3>
        <div class="flex flex-wrap gap-2">
          <?php foreach (array_filter(array_map('trim', explode(',', $profile['skills']))) as $skill): ?>
            <span class="bg-gray-100 border border-gray-200 rounded-full px-3 py-1 text-xs">
              🏷 <?= htmlspecialchars($skill) ?>
            </span>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

    </div>
  </div>

  <?php include("tools/footer.php"); ?>

<script>
function sendConnection(receiverId) {
  const btn = document.getElementById('connectBtn');
  btn.disabled    = true;
  btn.textContent = 'Sending...';

  fetch('tools/connectionRequest.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ receiver_id: receiverId })
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      btn.textContent = '✓ Connection Sent';
      btn.className   = 'px-4 py-2 bg-gray-200 text-gray-500 rounded-lg text-sm font-semibold cursor-not-allowed';
    } else {
      btn.textContent = 'Connect';
      btn.disabled    = false;
      btn.className   = 'px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-semibold transition-colors';
      alert(data.message);
    }
  })
  .catch(err => {
    console.error('Error:', err);
    btn.textContent = 'Connect';
    btn.disabled    = false;
  });
}
</script>
</body>
</html>
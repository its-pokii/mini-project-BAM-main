<?php
include("tools/userHeaderName.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit;
}
?>

<?php 
if (!isset($_GET['id'])) {
    header('Location: 02-find-alumni.php');
    exit;
}

$profile_id = (int)$_GET['id'];
$_SESSION['profile_id'] = $profile_id;
$stmt = mysqli_prepare($connector, "SELECT * FROM alumni_profiles INNER JOIN users ON alumni_profiles.user_id = users.id WHERE alumni_profiles.user_id = ?");
mysqli_stmt_bind_param($stmt, "i", $profile_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$profile = mysqli_fetch_assoc($result);
?>


<?php 
$connection_status = null;

if ($user_id) {
    $stmt = mysqli_prepare($connector,
        "SELECT status FROM connection_requests 
         WHERE student_id = ? AND alumni_id = ?"
    );
    mysqli_stmt_bind_param($stmt, "ii", $user_id, $profile_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $conn   = mysqli_fetch_assoc($result);
    $connection_status = $conn['status'] ?? null;
}
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
  <?php
  include("tools/header.php");
  ?>

  <div class="flex flex-1 overflow-hidden">

    <!-- SIDEBAR -->
    <div class="w-56 border-r border-gray-200 flex flex-col justify-between py-6 bg-white flex-shrink-0">
      <nav class="flex flex-col">
        <a href="01-dashboard.php"   class="flex items-center gap-2.5 px-6 py-2.5 text-sm text-gray-900 border-r-4 border-transparent hover:bg-gray-100 no-underline transition-colors"> Dashboard</a>
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
            <div class="w-18 h-18 rounded-full bg-blue-500 flex items-center justify-center text-white text-2xl font-bold flex-shrink-0" style="width:72px;height:72px">SC</div>
            <div>
              <div class="text-xl font-bold text-gray-900"><?php echo $profile['first_name'] . " " . $profile['last_name']; ?></div>
              <div class="text-sm text-gray-500 mt-1"><?php echo $profile['current_position'] ?? 'Position not set'; ?></div>
              <div class="text-sm text-gray-500 mt-0.5">📍 <?php echo $profile['location'] ?? 'Location not set'; ?></div>
               <?php if ($profile['willing_to_mentor'] == "Yes"): ?>
                <span class="inline-block mt-1.5 bg-blue-100 text-blue-600 text-xs font-semibold px-2.5 py-0.5 rounded-full">Willing to Mentor</span>
              <?php endif; ?>
            </div>
          </div>
          <?php if ($connection_status === 'pending'): ?>
    <!-- already sent -->
    <button id="connectBtn"
            class="px-4 py-2 bg-gray-200 text-gray-500 rounded-lg text-sm font-semibold cursor-not-allowed"
            disabled>
        ✓ Connection Sent
    </button>

<?php elseif ($connection_status === 'accepted'): ?>
    <!-- already connected -->
    <button id="connectBtn"
            class="px-4 py-2 bg-green-100 text-green-600 rounded-lg text-sm font-semibold cursor-not-allowed"
            disabled>
         Connected
    </button>

<?php else: ?>
    <!-- not connected yet -->
    <button id="connectBtn"
            onclick="sendConnection(<?= $profile_id ?>)"
            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-semibold transition-colors">
        Connect
    </button>
<?php endif; ?>
        </div>
        <div class="flex gap-2.5 mt-4">
          <button class="bg-gray-100 border-none rounded-lg px-3 py-2 cursor-pointer text-base hover:bg-gray-200 transition-colors" >📊</button>
          <button class="bg-gray-100 border-none rounded-lg px-3 py-2 cursor-pointer text-base hover:bg-gray-200 transition-colors">🔗</button>
        </div>
      </div>

      <!-- ABOUT -->
      <div class="bg-white border border-gray-200 rounded-xl p-6 mb-4">
        <h3 class="text-base font-bold text-gray-900 mb-4">About</h3>
        <p class="text-sm text-gray-500 leading-relaxed">
          <?php echo $profile['bio'] ?? 'No bio available.'; ?>
        </p>
      </div>

      <!-- EXPERIENCE -->
      <div class="bg-white border border-gray-200 rounded-xl p-6 mb-4">
        <h3 class="text-base font-bold text-gray-900 mb-4">Professional Experience</h3>

        <div class="flex gap-3 pb-4 mb-4 border-b border-gray-200">
          <span class="text-lg mt-0.5">🏢</span>
          <div>
            <div class="font-semibold text-sm text-gray-900">Senior Product Manager at InnovateX Solutions</div>
            <div class="text-xs text-gray-500 my-1">Jan 2021 – Present</div>
            <div class="text-sm text-gray-500 leading-relaxed">Led product development for AI-powered analytics platform, increasing user engagement by 25%. Managed a team of 5 product owners and collaborated closely with engineering and design.</div>
          </div>
        </div>

        <div class="flex gap-3 pb-4 mb-4 border-b border-gray-200">
          <span class="text-lg mt-0.5">🏢</span>
          <div>
            <div class="font-semibold text-sm text-gray-900">Product Manager at TechGrowth Inc.</div>
            <div class="text-xs text-gray-500 my-1">Jun 2018 – Dec 2020</div>
            <div class="text-sm text-gray-500 leading-relaxed">Launched new mobile application features, resulting in a 15% increase in subscription conversions. Conducted market research and competitive analysis to identify new product opportunities.</div>
          </div>
        </div>

        <div class="flex gap-3">
          <span class="text-lg mt-0.5">🏢</span>
          <div>
            <div class="font-semibold text-sm text-gray-900">Associate Product Manager at DigitalWave</div>
            <div class="text-xs text-gray-500 my-1">Aug 2016 – May 2018</div>
            <div class="text-sm text-gray-500 leading-relaxed">Supported the product lifecycle of several SaaS products, gathering requirements, and assisting with roadmap planning and execution.</div>
          </div>
        </div>
      </div>

      <!-- EDUCATION -->
      <div class="bg-white border border-gray-200 rounded-xl p-6 mb-4">
        <h3 class="text-base font-bold text-gray-900 mb-4">Education</h3>

        <div class="flex gap-3 mb-3">
          <span class="text-lg mt-0.5">🎓</span>
          <div>
            <div class="font-semibold text-sm text-gray-900">Master of Business Administration (MBA), University of California, Berkeley</div>
            <div class="text-xs text-gray-500 my-1">2020</div>
            <div class="text-sm text-gray-500">Focused on Technology Management and Entrepreneurship.</div>
          </div>
        </div>

        <div class="flex gap-3">
          <span class="text-lg mt-0.5">🎓</span>
          <div>
            <div class="font-semibold text-sm text-gray-900">B.S. in Computer Science, University College of Arts (UCA)</div>
            <div class="text-xs text-gray-500 my-1">2016</div>
            <div class="text-sm text-gray-500">Graduated with honors, specialized in Software Engineering.</div>
          </div>
        </div>
      </div>

      <!-- SKILLS -->
      <div class="bg-white border border-gray-200 rounded-xl p-6">
        <h3 class="text-base font-bold text-gray-900 mb-4">Skills &amp; Expertise</h3>
        <div class="flex flex-wrap gap-2">
          <span class="bg-gray-100 border border-gray-200 rounded-full px-3 py-1 text-xs">🏷 Product Management</span>
          <span class="bg-gray-100 border border-gray-200 rounded-full px-3 py-1 text-xs">🏷 AI/Machine Learning</span>
          <span class="bg-gray-100 border border-gray-200 rounded-full px-3 py-1 text-xs">🏷 SaaS</span>
          <span class="bg-gray-100 border border-gray-200 rounded-full px-3 py-1 text-xs">🏷 User Experience (UX)</span>
          <span class="bg-gray-100 border border-gray-200 rounded-full px-3 py-1 text-xs">🏷 Agile Methodologies</span>
          <span class="bg-gray-100 border border-gray-200 rounded-full px-3 py-1 text-xs">🏷 Market Research</span>
          <span class="bg-gray-100 border border-gray-200 rounded-full px-3 py-1 text-xs">🏷 Data Analytics</span>
          <span class="bg-gray-100 border border-gray-200 rounded-full px-3 py-1 text-xs">🏷 Strategy Development</span>
        </div>
      </div>

    </div>
  </div>

  <!-- FOOTER -->
  <?php 
  include("tools/footer.php");
  ?>

<script>
function sendConnection(receiverId) {
  const btn = document.getElementById('connectBtn');

  // disable button immediately so user can't click twice
  btn.disabled   = true;
  btn.textContent = 'Sending...';

   fetch('tools/connectionRequest.php',{
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ receiver_id: receiverId })
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      // ✅ change button to "Connection Sent"
      btn.textContent  = 'Connection Sent';
      btn.disabled     = true;
      btn.className    = 'px-4 py-2 bg-gray-200 text-gray-500 rounded-lg text-sm font-semibold cursor-not-allowed';
    } else {
      // ❌ something went wrong — reset button
      btn.textContent  = 'Connect';
      btn.disabled     = false;
      btn.className    = 'px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-semibold transition-colors';
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
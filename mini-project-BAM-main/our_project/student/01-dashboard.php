<?php
include("tools/userHeaderName.php"); 
?>

<?php 

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit;
}

$stmt1 = mysqli_prepare($connector, 'SELECT COUNT(*) AS total_rows FROM alumni_profiles ');
mysqli_execute($stmt1);
$result1 = mysqli_stmt_get_result($stmt1);
$count = mysqli_fetch_column($result1);

$stmt2 = mysqli_prepare($connector, 'SELECT COUNT(*) AS total_rows FROM connection_requests WHERE student_id = ? AND status = "accepted"');
mysqli_stmt_bind_param($stmt2, 'i', $_SESSION['user_id']);
mysqli_execute($stmt2);
$result2 = mysqli_stmt_get_result($stmt2);
$connections_count = mysqli_fetch_column($result2);

$stmt3 = mysqli_prepare($connector, 'SELECT COUNT(*) AS total_rows FROM messages WHERE receiver_id = ? AND is_read = 0');
mysqli_stmt_bind_param($stmt3, 'i', $_SESSION['user_id']);
mysqli_execute($stmt3);
$result3 = mysqli_stmt_get_result($stmt3);
$unread_messages_count = mysqli_fetch_column($result3);
$stmt4 = mysqli_prepare($connector, 'SELECT COUNT(*) AS total_rows FROM connection_requests WHERE student_id = ? AND status = "pending"');
mysqli_stmt_bind_param($stmt4, 'i', $_SESSION['user_id']);
mysqli_execute($stmt4);
$result4 = mysqli_stmt_get_result($stmt4);
$pending_requests_count = mysqli_fetch_column($result4);

$stmt5 = mysqli_prepare($connector, "
    SELECT ss.id, ss.title, ss.content, ss.cover_photo, ss.industry,
           u.first_name, u.last_name, u.profile_photo,
           ap.current_position, ap.current_company
    FROM success_stories ss
    JOIN users u ON ss.author_id = u.id
    LEFT JOIN alumni_profiles ap ON ss.author_id = ap.user_id
    WHERE ss.status = 'approved'
    ORDER BY ss.created_at DESC
    LIMIT 3
");
mysqli_execute($stmt5);
$featured_stories = mysqli_stmt_get_result($stmt5);

?>




<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>UCA Connect – Dashboard</title>
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
        <a href="01-dashboard.php" class="flex items-center gap-2.5 px-6 py-2.5 text-sm font-semibold text-blue-600 bg-blue-50 border-r-4 border-blue-600 no-underline">Dashboard</a>
        <a href="02-find-alumni.php" class="flex items-center gap-2.5 px-6 py-2.5 text-sm text-gray-900 border-r-4 border-transparent hover:bg-gray-100 no-underline transition-colors">Find Alumni</a>
        <a href="04-connections.php" class="flex items-center gap-2.5 px-6 py-2.5 text-sm text-gray-900 border-r-4 border-transparent hover:bg-gray-100 no-underline transition-colors">Connections</a>
        <a href="05-messages.php" class="flex items-center gap-2.5 px-6 py-2.5 text-sm text-gray-900 border-r-4 border-transparent hover:bg-gray-100 no-underline transition-colors">Messages</a>
        <a href="06-stories.php" class="flex items-center gap-2.5 px-6 py-2.5 text-sm text-gray-900 border-r-4 border-transparent hover:bg-gray-100 no-underline transition-colors">Stories</a>
      </nav>
      <a href="settings.php" class="flex items-center gap-2.5 px-6 py-2.5 text-sm text-gray-500 no-underline"> Profile &amp; Settings</a>
    </div>
    <!-- MAIN CONTENT -->
    <div class="flex-1 p-8 overflow-y-auto">
      <h1 class="text-2xl font-bold mb-6">Student Dashboard</h1>

      <!-- OVERVIEW -->
      <h2 class="text-base font-semibold mb-4">Overview</h2>
      <div class="grid grid-cols-4 gap-4 mb-8">
        <div class="bg-white border border-gray-200 rounded-xl p-5">
          <div class="flex justify-between items-center text-sm text-gray-500 mb-2"><span>Alumni Registered</span></div>
          <div class="text-3xl font-bold text-gray-900"><?php echo $count ?> </div>
        </div>
        <div class="bg-white border border-gray-200 rounded-xl p-5">
          <div class="flex justify-between items-center text-sm text-gray-500 mb-2"><span>Your Connections</span></div>
          <div class="text-3xl font-bold text-gray-900"><?php echo $connections_count ?></div>
        </div>
        <div class="bg-white border border-gray-200 rounded-xl p-5">
          <div class="flex justify-between items-center text-sm text-gray-500 mb-2"><span>Pending Requests</span></div>
          <div class="text-3xl font-bold text-gray-900"><?php echo $pending_requests_count ?></div>
        </div>
        <div class="bg-white border border-gray-200 rounded-xl p-5">
          <div class="flex justify-between items-center text-sm text-gray-500 mb-2"><span>New Messages</span></div>
          <div class="text-3xl font-bold text-gray-900"><?php echo $unread_messages_count ?></div>
        </div>
      </div>

      <!-- QUICK ACTIONS -->
      <h2 class="text-base font-semibold mb-4">Quick Actions</h2>
      <div class="flex gap-3 mb-8 flex-wrap">
        <a href="02-find-alumni.php" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm px-4 py-2.5 rounded-lg no-underline inline-flex items-center gap-1.5 transition-colors">Find Alumni</a>
        <a href="06-stories.php" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm px-4 py-2.5 rounded-lg no-underline inline-flex items-center gap-1.5 transition-colors">Browse Stories</a>
        <a href="05-messages.php" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm px-4 py-2.5 rounded-lg no-underline inline-flex items-center gap-1.5 transition-colors">Check Messages</a>
        <a href="04-connections.php" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm px-4 py-2.5 rounded-lg no-underline inline-flex items-center gap-1.5 transition-colors">Manage Connections</a>
      </div>

      <!-- FEATURED STORIES -->
      <!-- FEATURED STORIES -->
<h2 class="text-base font-semibold mb-4">Featured Stories</h2>
<?php
$stories_list = mysqli_fetch_all($featured_stories, MYSQLI_ASSOC);
if (empty($stories_list)):
?>
<div class="flex flex-col items-center justify-center py-12 text-center bg-white border border-gray-200 rounded-xl">
    <p class="text-3xl mb-3">📖</p>
    <p class="text-sm text-gray-400">No stories published yet</p>
</div>
<?php else: ?>
<div class="grid grid-cols-3 gap-5">
    <?php foreach ($stories_list as $s):
        $full_name = htmlspecialchars($s['first_name'] . ' ' . $s['last_name']);
        $excerpt   = htmlspecialchars(substr(strip_tags($s['content']), 0, 120)) . '...';
        $meta      = $s['current_position'] && $s['current_company']
                       ? htmlspecialchars($s['current_position'] . ' @ ' . $s['current_company'])
                       : $full_name;
    ?>
    <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
        <?php if ($s['cover_photo']): ?>
            <img src="../<?= htmlspecialchars($s['cover_photo']) ?>"
                 alt="<?= htmlspecialchars($s['title']) ?>"
                 class="w-full h-40 object-cover block" />
        <?php else: ?>
            <div class="w-full h-40 bg-blue-100 flex items-center justify-center text-blue-400 text-4xl font-bold">
                <?= strtoupper(substr($s['first_name'], 0, 1) . substr($s['last_name'], 0, 1)) ?>
            </div>
        <?php endif; ?>
        <div class="p-4">
            <h3 class="text-sm font-semibold mb-1.5 leading-snug"><?= htmlspecialchars($s['title']) ?></h3>
            <p class="text-xs text-gray-500 mb-1.5"><?= $meta ?></p>
            <p class="text-xs text-gray-500 leading-relaxed mb-3"><?= $excerpt ?></p>
            <a href="view-story.php?id=<?= (int)$s['id'] ?>"
               class="bg-white text-gray-900 border border-gray-200 rounded-lg px-3.5 py-1.5 text-xs cursor-pointer no-underline hover:bg-gray-50 transition-colors">
               View Story
            </a>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

      </div>
    </div>
  </div>

  <!-- FOOTER -->
  <?php 
  include("tools/footer.php");
  ?>

</body>
</html>

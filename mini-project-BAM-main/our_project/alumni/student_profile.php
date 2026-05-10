<?php
include("../student/tools/userHeaderName.php");

// if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'alumni') {
//     header("Location: ../login.php");
//     exit;
// }

// if (!isset($_GET['id'])) {
//     header('Location: 01-dashboard.php');
//     exit;
// }

$profile_id = (int)$_GET['id'];

// FETCH STUDENT PROFILE
$stmt = mysqli_prepare($connector,
    "SELECT users.first_name, users.last_name, users.profile_photo,
            student_profiles.*
     FROM student_profiles
     INNER JOIN users ON student_profiles.user_id = users.id
     WHERE user_id = ?"
);

mysqli_stmt_bind_param($stmt, "i", $profile_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$profile = mysqli_fetch_assoc($result);

if (!$profile) {
    echo "Profile not found";
    exit;
}

// SAFE OUTPUT FUNCTION
function e($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Profile</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-50 font-sans">

<?php include 'alumni_header.php'; ?>

<div class="flex">

<!-- SIDEBAR -->
<?php include("sidebar_alumni.php"); ?>

<!-- MAIN -->
<div class="flex-1 p-8">

<!-- HEADER -->
<div class="bg-white p-6 rounded-xl border mb-6 flex justify-between">

  <div class="flex gap-4">
    <?php if ($profile['profile_photo']): ?>
      <img src="../student/<?php echo e($profile['profile_photo']); ?>"
           class="w-20 h-20 rounded-full object-cover">
    <?php else: ?>
      <div class="w-20 h-20 bg-blue-500 text-white flex items-center justify-center rounded-full text-xl font-bold">
        <?php echo strtoupper(substr($profile['first_name'],0,1) . substr($profile['last_name'],0,1)); ?>
      </div>
    <?php endif; ?>

    <div>
      <h2 class="text-xl font-bold">
        <?php echo e($profile['first_name'] . " " . $profile['last_name']); ?>
      </h2>

      <p class="text-sm text-gray-500 mt-1">
        🎓 <?php echo e($profile['major'] ?? 'Major not set'); ?>
      </p>

      <p class="text-sm text-gray-500">
        📅 Year: <?php echo e($profile['year_of_study'] ?? 'Not set'); ?>
      </p>
    </div>
  </div>

</div>

<!-- ABOUT -->
<div class="bg-white p-6 rounded-xl border mb-6">
  <h3 class="font-bold mb-3">About</h3>
  <p class="text-sm text-gray-600">
    <?php echo e($profile['bio'] ?? 'No bio available'); ?>
  </p>
</div>

<!-- LINKS -->
<div class="bg-white p-6 rounded-xl border mb-6">
  <h3 class="font-bold mb-4">Links</h3>

  <div class="flex flex-col gap-3 text-sm">

    <?php if (!empty($profile['linkedin'])): ?>
      <a href="<?php echo e($profile['linkedin']); ?>" target="_blank"
         class="text-blue-600 hover:underline">🔗 LinkedIn</a>
    <?php endif; ?>

    <?php if (!empty($profile['github'])): ?>
      <a href="<?php echo e($profile['github']); ?>" target="_blank"
         class="text-blue-600 hover:underline">💻 GitHub</a>
    <?php endif; ?>

    <?php if (!empty($profile['other_url'])): ?>
      <a href="<?php echo e($profile['other_url']); ?>" target="_blank"
         class="text-blue-600 hover:underline">🌐 Portfolio / Website</a>
    <?php endif; ?>

    <?php if (
        empty($profile['linkedin']) &&
        empty($profile['github']) &&
        empty($profile['other_url'])
    ): ?>
      <p class="text-gray-400">No links provided.</p>
    <?php endif; ?>

  </div>
</div>

<!-- EXTRA SECTION (OPTIONAL FUTURE) -->
<div class="bg-white p-6 rounded-xl border">
  <h3 class="font-bold mb-3">Activity</h3>
  <p class="text-sm text-gray-400">No activity yet.</p>
</div>

</div>
</div>

<?php include("../student/tools/footer.php"); ?>

</body>
</html>
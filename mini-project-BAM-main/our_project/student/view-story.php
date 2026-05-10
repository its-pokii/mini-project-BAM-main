<?php
include("tools/userHeaderName.php");

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit;
}

require_once("../auth/config.php");

$story_id = (int)($_GET['id'] ?? 0);
if (!$story_id) {
    header("Location: 06-stories.php");
    exit;
}

$stmt = $connector->prepare("
    SELECT ss.id, ss.title, ss.content, ss.industry, ss.cover_photo, ss.created_at, ss.approved_at,
           u.first_name, u.last_name, u.profile_photo,
           ap.current_position, ap.current_company
    FROM success_stories ss
    JOIN users u ON ss.author_id = u.id
    LEFT JOIN alumni_profiles ap ON ss.author_id = ap.user_id
    WHERE ss.id = ? AND ss.status = 'approved'
");
$stmt->bind_param('i', $story_id);
$stmt->execute();
$res = $stmt->get_result();
$story = $res->fetch_assoc();
$stmt->close();

if (!$story) {
    header("Location: 06-stories.php");
    exit;
}

$full_name    = htmlspecialchars($story['first_name'] . ' ' . $story['last_name']);
$initials     = strtoupper(substr($story['first_name'], 0, 1) . substr($story['last_name'], 0, 1));
$position     = $story['current_position'] ? htmlspecialchars($story['current_position']) : '';
$company      = $story['current_company']  ? htmlspecialchars($story['current_company'])  : '';
$meta         = $position && $company ? "$position @ $company" : ($position ?: $company);
$date         = date('F j, Y', strtotime($story['created_at']));

function industry_color($industry) {
    return match($industry) {
        'Tech'       => '#3B82F6',
        'Finance'    => '#6366F1',
        'Healthcare' => '#EF4444',
        'Education'  => '#F59E0B',
        default      => '#10B981',
    };
}
$color = industry_color($story['industry'] ?? '');
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title><?= htmlspecialchars($story['title']) ?> – UCA Connect</title>
<script src="https://cdn.tailwindcss.com"></script>
<style>
  .story-content p  { margin-bottom: 1rem; line-height: 1.75; color: #374151; }
  .story-content h2 { font-size: 1.2rem; font-weight: 700; margin: 1.5rem 0 .5rem; color: #111827; }
  .story-content h3 { font-size: 1rem; font-weight: 600; margin: 1.25rem 0 .4rem; color: #1f2937; }
  .story-content ul { list-style: disc; padding-left: 1.5rem; margin-bottom: 1rem; color: #374151; }
  .story-content li { margin-bottom: .35rem; line-height: 1.7; }
</style>
</head>
<body class="font-sans bg-gray-50 h-screen flex flex-col">

  <?php include("tools/header.php"); ?>

  <div class="flex flex-1 overflow-hidden">

    <!-- SIDEBAR -->
    <div class="w-56 border-r border-gray-200 flex flex-col justify-between py-6 bg-white flex-shrink-0">
      <nav class="flex flex-col">
        <a href="01-dashboard.php"   class="flex items-center gap-2.5 px-6 py-2.5 text-sm text-gray-900 border-r-4 border-transparent hover:bg-gray-100 no-underline transition-colors">Dashboard</a>
        <a href="02-find-alumni.php" class="flex items-center gap-2.5 px-6 py-2.5 text-sm text-gray-900 border-r-4 border-transparent hover:bg-gray-100 no-underline transition-colors">Find Alumni</a>
        <a href="04-connections.php" class="flex items-center gap-2.5 px-6 py-2.5 text-sm text-gray-900 border-r-4 border-transparent hover:bg-gray-100 no-underline transition-colors">Connections</a>
        <a href="05-messages.php"    class="flex items-center gap-2.5 px-6 py-2.5 text-sm text-gray-900 border-r-4 border-transparent hover:bg-gray-100 no-underline transition-colors">Messages</a>
        <a href="06-stories.php"     class="flex items-center gap-2.5 px-6 py-2.5 text-sm font-semibold text-blue-600 bg-blue-50 border-r-4 border-blue-600 no-underline">Stories</a>
      </nav>
      <a href="settings.php" class="flex items-center gap-2.5 px-6 py-2.5 text-sm text-gray-500 no-underline">Profile &amp; Settings</a>
    </div>

    <!-- MAIN -->
    <div class="flex-1 overflow-y-auto">

      <!-- Cover -->
      <?php if ($story['cover_photo']): ?>
        <div class="w-full h-56 overflow-hidden">
          <img src="../../<?= htmlspecialchars($story['cover_photo'])  ?>"
               alt="Cover"
               class="w-full h-full object-cover" />
        </div>
      <?php else: ?>
        <div class="w-full h-56 flex items-center justify-center text-white text-6xl font-bold"
             style="background: <?= $color ?>">
          <?= $initials ?>
        </div>
      <?php endif; ?>

      <!-- Content -->
      <div class="max-w-3xl mx-auto px-6 py-8">

        <!-- Back -->
        <a href="06-stories.php"
           class="inline-flex items-center gap-1.5 text-sm text-gray-400 hover:text-gray-600 no-underline mb-6 transition-colors">
          ← Back to Stories
        </a>

        <!-- Industry tag -->
        <?php if ($story['industry']): ?>
        <span class="inline-block px-3 py-1 rounded-full text-xs font-semibold text-white mb-4"
              style="background: <?= $color ?>">
          <?= htmlspecialchars($story['industry']) ?>
        </span>
        <?php endif; ?>

        <!-- Title -->
        <h1 class="text-2xl font-bold text-gray-900 leading-snug mb-5">
          <?= htmlspecialchars($story['title']) ?>
        </h1>

        <!-- Author card -->
        <div class="flex items-center gap-3 mb-8 pb-6 border-b border-gray-100">
          <?php if ($story['profile_photo']): ?>
            <img src="<?= htmlspecialchars($story['profile_photo']) ?>"
                 class="w-11 h-11 rounded-full object-cover" />
          <?php else: ?>
            <div class="w-11 h-11 rounded-full flex items-center justify-center text-white font-bold text-sm flex-shrink-0"
                 style="background: <?= $color ?>">
              <?= $initials ?>
            </div>
          <?php endif; ?>
          <div>
            <p class="text-sm font-semibold text-gray-900"><?= $full_name ?></p>
            <?php if ($meta): ?>
            <p class="text-xs text-gray-400"><?= $meta ?></p>
            <?php endif; ?>
          </div>
          <span class="ml-auto text-xs text-gray-300"><?= $date ?></span>
        </div>

        <!-- Story body -->
        <div class="story-content text-sm">
          <?= nl2br(htmlspecialchars($story['content'])) ?>
        </div>

      </div>
    </div>
  </div>

  <?php include("tools/footer.php"); ?>

</body>
</html>
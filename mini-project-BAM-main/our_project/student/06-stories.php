<?php
include("tools/userHeaderName.php"); 

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: ../login.php");
    exit;
}

require_once("../auth/config.php");

// Fetch all approved stories with author info
$stories = [];
$stmt = $connector->prepare("
    SELECT ss.id, ss.title, ss.content, ss.industry, ss.cover_photo, ss.created_at,
           u.first_name, u.last_name, ap.current_position, ap.current_company
    FROM success_stories ss
    JOIN users u ON ss.author_id = u.id
    LEFT JOIN alumni_profiles ap ON ss.author_id = ap.user_id
    WHERE ss.status = 'approved'
    ORDER BY ss.created_at DESC
");
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) $stories[] = $row;
$stmt->close();

// Industry color map
function industry_color($industry) {
    return match($industry) {
        'Tech'        => '#3B82F6',
        'Finance'     => '#6366F1',
        'Healthcare'  => '#EF4444',
        'Education'   => '#F59E0B',
        default       => '#10B981',
    };
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>UCA Connect – Stories</title>
<script src="https://cdn.tailwindcss.com"></script>
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
    <div class="flex-1 p-8 overflow-y-auto">
      <h1 class="text-xl font-bold text-gray-900 mb-5">Alumni Success Stories</h1>

      <!-- SEARCH ROW -->
      <div class="flex gap-3 mb-6">
        <div class="flex-1 flex items-center bg-white border border-gray-200 rounded-lg px-3 py-2 gap-2">
          <span class="text-gray-400">🔍</span>
          <input
            id="storySearch"
            oninput="filterStories()"
            placeholder="Search stories by title, author, or keywords..."
            class="border-none outline-none text-sm w-full bg-transparent"
          />
        </div>
      </div>

      <!-- STORIES GRID -->
      <?php if (empty($stories)): ?>
      <div class="flex flex-col items-center justify-center py-24 text-center">
        <p class="text-4xl mb-4">📖</p>
        <p class="text-sm font-medium text-gray-500">No stories published yet</p>
        <p class="text-xs text-gray-400 mt-1">Check back soon for alumni success stories</p>
      </div>
      <?php else: ?>
      <div id="storiesGrid" class="grid grid-cols-4 gap-5">
        <?php foreach ($stories as $s):
            $full_name  = htmlspecialchars($s['first_name'] . ' ' . $s['last_name']);
            $initials   = strtoupper(substr($s['first_name'], 0, 1) . substr($s['last_name'], 0, 1));
            $color      = industry_color($s['industry'] ?? '');
            $excerpt    = htmlspecialchars(substr(strip_tags($s['content']), 0, 100)) . '...';
            $subtitle   = $s['current_position'] && $s['current_company']
                            ? htmlspecialchars($s['current_position'] . ' @ ' . $s['current_company'])
                            : htmlspecialchars($full_name);
        ?>
        <div class="story-card bg-white border border-gray-200 rounded-xl overflow-hidden flex flex-col"
             data-title="<?= strtolower(htmlspecialchars($s['title'])) ?>"
             data-author="<?= strtolower($full_name) ?>"
             data-desc="<?= strtolower($excerpt) ?>">

          <?php if ($s['cover_photo']): ?>
            <img src="../uploads/stories/<?= htmlspecialchars($s['cover_photo']) ?>"
                 alt="<?= htmlspecialchars($s['title']) ?>"
                 loading="lazy"
                 class="w-full h-36 object-cover block" />
          <?php else: ?>
            <div class="w-full h-36 flex items-center justify-center text-white text-3xl font-bold"
                 style="background: <?= $color ?>">
              <?= $initials ?>
            </div>
          <?php endif; ?>

          <div class="p-4 flex-1 flex flex-col">
            <div class="text-sm font-bold text-gray-900 mb-2 leading-snug">
              <?= htmlspecialchars($s['title']) ?>
            </div>
            <div class="text-xs text-gray-500 leading-relaxed flex-1 mb-3">
              <?= $excerpt ?>
            </div>
            <div class="flex items-center justify-between">
              <div class="flex items-center gap-1.5">
                <div class="w-6 h-6 rounded-full flex items-center justify-center text-white font-bold flex-shrink-0"
                     style="font-size:9px; background:<?= $color ?>">
                  <?= $initials ?>
                </div>
                <span class="text-xs text-gray-500"><?= $subtitle ?></span>
              </div>
              <a href="view-story.php?id=<?= (int)$s['id'] ?>"
                 class="bg-white text-gray-900 border border-gray-200 rounded-lg px-2.5 py-1 text-xs cursor-pointer hover:bg-gray-50 transition-colors no-underline">
                View Story
              </a>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Empty state when search returns nothing -->
      <div id="no-results" class="hidden flex-col items-center justify-center py-16 text-center">
        <p class="text-3xl mb-3">🔍</p>
        <p class="text-sm text-gray-400">No stories match your search</p>
      </div>
      <?php endif; ?>

    </div>
  </div>

  <?php include("tools/footer.php"); ?>

<script>
function filterStories() {
    const q = document.getElementById('storySearch').value.toLowerCase().trim();
    const cards = document.querySelectorAll('.story-card');
    let visible = 0;

    cards.forEach(card => {
        const match = !q
            || card.dataset.title.includes(q)
            || card.dataset.author.includes(q)
            || card.dataset.desc.includes(q);
        card.style.display = match ? '' : 'none';
        if (match) visible++;
    });

    const noResults = document.getElementById('no-results');
    if (noResults) noResults.classList.toggle('hidden', visible > 0);
}
</script>
</body>
</html>
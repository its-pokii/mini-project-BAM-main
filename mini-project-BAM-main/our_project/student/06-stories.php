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
<title>UCA Connect – Stories</title>
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
        <a href="01-dashboard.php"   class="flex items-center gap-2.5 px-6 py-2.5 text-sm text-gray-900 border-r-4 border-transparent hover:bg-gray-100 no-underline transition-colors">Dashboard</a>
        <a href="02-find-alumni.php" class="flex items-center gap-2.5 px-6 py-2.5 text-sm text-gray-900 border-r-4 border-transparent hover:bg-gray-100 no-underline transition-colors">Find Alumni</a>
        <a href="04-connections.php" class="flex items-center gap-2.5 px-6 py-2.5 text-sm text-gray-900 border-r-4 border-transparent hover:bg-gray-100 no-underline transition-colors">Connections</a>
        <a href="05-messages.php"    class="flex items-center gap-2.5 px-6 py-2.5 text-sm text-gray-900 border-r-4 border-transparent hover:bg-gray-100 no-underline transition-colors">Messages</a>
        <a href="06-stories.php"     class="flex items-center gap-2.5 px-6 py-2.5 text-sm font-semibold text-blue-600 bg-blue-50 border-r-4 border-blue-600 no-underline">Stories</a>
      </nav>
      <a href="settings.php" class="flex items-center gap-2.5 px-6 py-2.5 text-sm text-gray-500 no-underline">⚙️ Profile &amp; Settings</a>
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
        <button class="bg-white text-gray-900 border border-gray-200 rounded-lg px-4 py-2 text-sm cursor-pointer hover:bg-gray-50 transition-colors">
          🔽 Filter
        </button>
      </div>

      <!-- STORIES GRID -->
      <div id="storiesGrid" class="grid grid-cols-4 gap-5"></div>
    </div>
  </div>

  <!-- FOOTER -->
  <?php 
  include("tools/footer.php");
  ?>

<script>
const stories = [
  { title: "From UCA to Silicon Valley: My Journey as a Software Engineer",   author: "Ahmed El Kabbaj", desc: "Discover how a UCA alumnus leveraged his skills to land a dream job at a leading tech company in Silicon Valley.", img: "https://images.unsplash.com/photo-1486325212027-8081e485255e?w=600&q=80", color: "#3B82F6" },
  { title: "Building a Sustainable Future: My Startup Journey",                author: "Fatima Zahraoui",  desc: "Learn about the challenges and triumphs of a UCA graduate who founded a successful green technology startup.",  img: "https://images.unsplash.com/photo-1518531933037-91b2f5f229cc?w=600&q=80", color: "#10B981" },
  { title: "Impact in Public Service: A Career in Diplomacy",                  author: "Youssef Bensaid", desc: "A UCA International Relations alumna shares her experiences working in diplomacy, advocating for change on the world stage.", img: "https://images.unsplash.com/photo-1521791136064-7986c2920216?w=600&q=80", color: "#F59E0B" },
  { title: "Innovation in Healthcare: Developing New Medical Devices",         author: "Sara Kettani",    desc: "A UCA Biomedical Engineering graduate discusses her role in pioneering medical devices that save lives.", img: "https://images.unsplash.com/photo-1576091160399-112ba8d25d1f?w=600&q=80", color: "#EF4444" },
  { title: "Mastering the Markets: A Career in Investment Banking",            author: "Omar Benjelloun", desc: "Explore the high-stakes world of investment banking with a UCA Finance alumnus, who shares tips for breaking in.", img: "https://images.unsplash.com/photo-1642790551116-18e150f248e5?w=600&q=80", color: "#6366F1" },
  { title: "Crafting Experiences: My Path in Digital Product Design",          author: "Nadia Cherkaoui", desc: "Follow the creative journey of a UCA Design alumna who is now shaping user experiences for millions of people.", img: "https://images.unsplash.com/photo-1611532736597-de2d4265fba3?w=600&q=80", color: "#EC4899" },
];

function initials(name) { return name.split(' ').map(n => n[0]).join('').slice(0, 2); }

function renderStories(list) {
  document.getElementById('storiesGrid').innerHTML = list.map(s => `
    <div class="bg-white border border-gray-200 rounded-xl overflow-hidden flex flex-col">
      <img src="${s.img}" alt="${s.title}" loading="lazy" class="w-full h-36 object-cover block" />
      <div class="p-4 flex-1 flex flex-col">
        <div class="text-sm font-bold text-gray-900 mb-2 leading-snug">${s.title}</div>
        <div class="text-xs text-gray-500 leading-relaxed flex-1 mb-3">${s.desc}</div>
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-1.5">
            <div class="w-6 h-6 rounded-full flex items-center justify-center text-white font-bold flex-shrink-0"
                 style="font-size:9px; background:${s.color}">${initials(s.author)}</div>
            <span class="text-xs text-gray-500">${s.author}</span>
          </div>
          <button class="bg-white text-gray-900 border border-gray-200 rounded-lg px-2.5 py-1 text-xs cursor-pointer hover:bg-gray-50 transition-colors">
            View Story
          </button>
        </div>
      </div>
    </div>
  `).join('');
}

function filterStories() {
  const q = document.getElementById('storySearch').value.toLowerCase();
  const filtered = stories.filter(s =>
    s.title.toLowerCase().includes(q) ||
    s.author.toLowerCase().includes(q) ||
    s.desc.toLowerCase().includes(q)
  );
  renderStories(filtered);
}

renderStories(stories);
</script>
</body>
</html>
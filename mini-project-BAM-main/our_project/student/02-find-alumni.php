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
<title>UCA Connect – Find Alumni</title>
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
        <a href="02-find-alumni.php" class="flex items-center gap-2.5 px-6 py-2.5 text-sm font-semibold text-blue-600 bg-blue-50 border-r-4 border-blue-600 no-underline">Find Alumni</a>
        <a href="04-connections.php" class="flex items-center gap-2.5 px-6 py-2.5 text-sm text-gray-900 border-r-4 border-transparent hover:bg-gray-100 no-underline transition-colors">Connections</a>
        <a href="05-messages.php"    class="flex items-center gap-2.5 px-6 py-2.5 text-sm text-gray-900 border-r-4 border-transparent hover:bg-gray-100 no-underline transition-colors">Messages</a>
        <a href="06-stories.php"     class="flex items-center gap-2.5 px-6 py-2.5 text-sm text-gray-900 border-r-4 border-transparent hover:bg-gray-100 no-underline transition-colors">Stories</a>
      </nav>
      <a href="settings.php" class="flex items-center gap-2.5 px-6 py-2.5 text-sm text-gray-500 no-underline">⚙️ Profile &amp; Settings</a>
    </div>

    <!-- MAIN -->
    <div class="flex-1 p-8 overflow-y-auto">

      <!-- FILTER BOX -->
      <div class="bg-white border border-gray-200 rounded-xl p-5 mb-6">
        <div class="font-semibold mb-3">🔽 Filter Alumni</div>
        <input
          type="text"
          id="searchInput"
          oninput="filterAlumni()"
          placeholder="Search by name, company, major, or keywords..."
          class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none mb-3 focus:ring-2 focus:ring-blue-200"
        />
        <div class="flex gap-3 flex-wrap mb-3">
          <select class="border border-gray-200 rounded-lg px-3 py-1.5 text-sm text-gray-500 bg-white">
            <option>All Companies</option>
          </select>
          <select class="border border-gray-200 rounded-lg px-3 py-1.5 text-sm text-gray-500 bg-white">
            <option>All Majors</option>
          </select>
          <select class="border border-gray-200 rounded-lg px-3 py-1.5 text-sm text-gray-500 bg-white">
            <option>All Years</option>
          </select>
          <select class="border border-gray-200 rounded-lg px-3 py-1.5 text-sm text-gray-500 bg-white">
            <option>All Industries</option>
          </select>
        </div>
        <div class="flex items-center justify-between">
          <label class="flex items-center gap-2 text-sm cursor-pointer">
            <input type="checkbox" id="mentorFilter" onchange="filterAlumni()" class="accent-blue-600" />
            Willing to Mentor
          </label>
          <button onclick="clearFilters()" class="border border-gray-200 rounded-lg px-4 py-1.5 text-sm text-gray-500 bg-transparent cursor-pointer hover:bg-gray-50 transition-colors">
            Clear Filters
          </button>
        </div>
      </div>

      <!-- RESULTS -->
      <div id="resultsLabel" class="font-semibold mb-4">Alumni Results (10)</div>
      <div id="alumniGrid" class="grid grid-cols-3 gap-4"></div>

    </div>
  </div>

  <!-- FOOTER -->
  <?php 
  include("tools/footer.php");
  ?>

<script>
let alumni = [];

fetch('tools/alumniProfiles.php')
.then(res => res.json())
.then(data => {
  alumni = data;
  renderAlumni(alumni);
})
.catch(err => {
    console.error('Error:', err);
    document.getElementById('alumniGrid').innerHTML ='<p class="text-center text-red-500">Failed to load alumni.</p>';
  });


const colors = ["#3B82F6","#10B981","#F59E0B","#EF4444","#8B5CF6",
                 "#06B6D4","#EC4899","#F97316","#14B8A6","#6366F1"];


function initials(name) {
  return name.split(' ').map(n => n[0]).join('').slice(0, 2);
}

function renderAlumni(list) {
  document.getElementById('resultsLabel').textContent = `Alumni Results (${list.length})`;
  document.getElementById('alumniGrid').innerHTML = list.map((a, index) => `
    <div class="bg-white border border-gray-200 rounded-xl p-5 text-center">

      <!-- avatar: use photo if exists, otherwise colored initials -->
      ${a.profile_photo
        ? `<img src="../uploads/${a.profile_photo}" 
                class="w-14 h-14 rounded-full object-cover mx-auto mb-3" />`
        : `<div class="w-14 h-14 rounded-full flex items-center justify-center text-white font-bold text-xl mx-auto mb-3" 
                style="background:${colors[index % colors.length]}">
             ${initials(a.first_name + ' ' + a.last_name)}
           </div>`
      }

      <!-- name -->
      <div class="font-semibold text-sm">
        ${a.first_name} ${a.last_name}
      </div>

      <!-- job title -->
      <div class="text-xs text-gray-500 mt-1">
        ${a.current_position ?? 'Position not set'}
      </div>

      <!-- major + graduation year -->
      <div class="text-xs text-gray-500 mt-0.5">
        ${a.major ?? ''}, Class of ${a.graduation_year ?? ''}
      </div>

      <!-- mentor badge — MySQL stores 1 or 0, not true/false -->
      ${a.willing_to_mentor == "Yes"
        ? '<span class="inline-block mt-2 bg-blue-100 text-blue-600 text-xs font-semibold px-2 py-0.5 rounded-full">Willing to Mentor</span>' 
        : ''}

      <a href="03-profile-view.php?id=${a.id}" 
         class="block mt-3 w-full bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold py-2 rounded-lg no-underline transition-colors">
        View Profile
      </a>
    </div>
  `).join('');
}

function filterAlumni() {
  const q          = document.getElementById('searchInput').value.toLowerCase();
  const mentorOnly = document.getElementById('mentorFilter').checked;

  const filtered = alumni.filter(a => {
    const fullName = (a.first_name + ' ' + a.last_name).toLowerCase();
    const match    = 
      fullName.includes(q)                              ||
      (a.current_position ?? '').toLowerCase().includes(q) ||
      (a.major             ?? '').toLowerCase().includes(q);

    return match && (mentorOnly ? a.willing_to_mentor == 'Yes' : true);
  });

  renderAlumni(filtered);
}

function clearFilters() {
  document.getElementById('searchInput').value = '';
  document.getElementById('mentorFilter').checked = false;
  renderAlumni(alumni);
}

renderAlumni(alumni);
</script>
</body>
</html>
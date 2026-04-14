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
<title>UCA Connect – Messages</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="font-sans bg-gray-50 h-screen flex flex-col">

  <!-- TOP BAR -->
  <?php include("tools/header.php"); ?>

  <div class="flex flex-1 overflow-hidden">

    <!-- SIDEBAR -->
    <div class="w-56 border-r border-gray-200 flex flex-col justify-between py-6 bg-white flex-shrink-0">
      <nav class="flex flex-col">
        <a href="01-dashboard.php"   class="flex items-center gap-2.5 px-6 py-2.5 text-sm text-gray-900 border-r-4 border-transparent hover:bg-gray-100 no-underline transition-colors">Dashboard</a>
        <a href="02-find-alumni.php" class="flex items-center gap-2.5 px-6 py-2.5 text-sm text-gray-900 border-r-4 border-transparent hover:bg-gray-100 no-underline transition-colors">Find Alumni</a>
        <a href="04-connections.php" class="flex items-center gap-2.5 px-6 py-2.5 text-sm text-gray-900 border-r-4 border-transparent hover:bg-gray-100 no-underline transition-colors">Connections</a>
        <a href="05-messages.php"    class="flex items-center gap-2.5 px-6 py-2.5 text-sm font-semibold text-blue-600 bg-blue-50 border-r-4 border-blue-600 no-underline">Messages</a>
        <a href="06-stories.php"     class="flex items-center gap-2.5 px-6 py-2.5 text-sm text-gray-900 border-r-4 border-transparent hover:bg-gray-100 no-underline transition-colors">Stories</a>
      </nav>
      <a href="settings.php" class="flex items-center gap-2.5 px-6 py-2.5 text-sm text-gray-500 no-underline"> Profile &amp; Settings</a>
    </div>

    <!-- CONVERSATIONS LIST -->
    <div class="w-80 border-r border-gray-200 flex flex-col bg-white flex-shrink-0">
      <div class="px-4 pt-5 pb-3">
        <h2 class="text-lg font-bold text-gray-900 mb-3">Messages</h2>
        <div class="flex items-center bg-gray-50 border border-gray-200 rounded-lg px-3 py-1.5 gap-2 mb-3">
          <span class="text-gray-400">🔍</span>
          <input id="searchInput" class="border-none bg-transparent outline-none text-sm w-full" placeholder="Search conversations..." />
        </div>
        <div class="flex gap-2" id="tabsContainer">
          <button onclick="setTab(this)" class="bg-blue-600 text-white border border-blue-600 rounded-full px-2.5 py-1 text-xs cursor-pointer transition-colors">All</button>
          <button onclick="setTab(this)" class="bg-transparent text-gray-500 border border-gray-200 rounded-full px-2.5 py-1 text-xs cursor-pointer hover:bg-gray-50 transition-colors">Unread</button>
          <button onclick="setTab(this)" class="bg-transparent text-gray-500 border border-gray-200 rounded-full px-2.5 py-1 text-xs cursor-pointer hover:bg-gray-50 transition-colors">Connections</button>
        </div>
      </div>

      <!-- CONVO LIST — JS renders here -->
      <div class="flex-1 overflow-y-auto" id="convos">
        <p class="text-center text-gray-400 text-sm p-6">Loading...</p>
      </div>

      <!-- NEW MESSAGE BTN -->
      <div class="p-4 border-t border-gray-200">
        <button class="w-full bg-blue-600 hover:bg-blue-700 text-white border-none rounded-lg py-2.5 text-sm font-semibold cursor-pointer transition-colors">
          + Start New Message
        </button>
      </div>
    </div>

    <!-- CHAT PANEL -->
    <div class="flex-1 flex flex-col">

      <!-- CHAT HEADER — JS updates this -->
      <div class="px-5 py-3 bg-white border-b border-gray-200 flex items-center gap-3">
        <div id="chatAvatar" class="w-10 h-10 rounded-full bg-gray-300 flex items-center justify-center text-white font-bold text-sm flex-shrink-0"></div>
        <div>
          <div id="chatName" class="font-bold text-sm text-gray-900">Select a conversation</div>
          <div id="chatStatus" class="text-xs text-gray-400">—</div>
        </div>
        <div class="ml-auto flex gap-4 text-xl text-gray-400 cursor-pointer">⋮</div>
      </div>

      <!-- MESSAGES — JS renders here -->
      <div id="chatMessages" class="flex-1 overflow-y-auto p-5 flex flex-col gap-3 bg-gray-50">
        <p class="text-center text-gray-400 text-sm mt-10">Select a conversation to start chatting.</p>
      </div>

      <!-- INPUT -->
      <div class="bg-white border-t border-gray-200">
        <div class="flex items-center gap-3 px-4 py-3">
          <span class="text-xl cursor-pointer">📎</span>
          <input
            id="msgInput"
            placeholder="Type your message..."
            onkeydown="if(event.key==='Enter') sendMsg()"
            class="flex-1 border border-gray-200 rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-blue-200"
          />
          <button onclick="sendMsg()" class="bg-transparent border-none text-2xl cursor-pointer text-blue-600">➤</button>
        </div>
        <div class="text-center text-xs text-gray-500 py-1.5 border-t border-gray-200">
          Your personal messages are secured
        </div>
      </div>

    </div>
  </div><!-- closes flex div -->

<script>
const colors = [
  "#3B82F6","#10B981","#F59E0B","#EF4444",
  "#8B5CF6","#06B6D4","#EC4899","#F97316"
];

const MY_ID = <?= $_SESSION['user_id'] ?>;

let convos        = [];
let selectedConvo = null;
let refreshTimer  = null;

// ─── SECURITY: Escape HTML (FIX XSS) ──────────────────────────
function escapeHTML(str) {
  return str.replace(/[&<>"']/g, match => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#39;'
  })[match]);
}

// ─── HELPERS ─────────────────────────────────────────────────
function initials(name) {
  if (!name) return '??';
  return name.split(' ').map(n => n[0]).join('').slice(0, 2).toUpperCase();
}

function formatTime(datetime) {
  if (!datetime) return '';
  const date = new Date(datetime);
  return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

function getColor(index) {
  return colors[index % colors.length];
}

// ─── LOAD CONVERSATIONS ──────────────────────────────────────
function loadConversations() {
  fetch('tools/getConversations.php')
    .then(res => res.json())
    .then(data => {
      if (!data.success) return;

      convos = data.data;
      renderConvos();

      // KEEP selection if possible
      if (convos.length > 0 && !selectedConvo) {
        selectConvo(convos[0].user_id, 0);
      }
    })
    .catch(err => console.error('Error loading convos:', err));
}

// ─── RENDER CONVERSATIONS ────────────────────────────────────
function renderConvos() {
  const el = document.getElementById('convos');

  if (convos.length === 0) {
    el.innerHTML = '<p class="text-center text-gray-400 text-sm p-6">No conversations yet.</p>';
    return;
  }

  el.innerHTML = convos.map((c, i) => `
    <div data-id="${c.user_id}"
         class="convo flex gap-3 px-4 py-3 border-b border-gray-200 cursor-pointer
                ${c.user_id == selectedConvo ? 'bg-blue-50' : 'hover:bg-gray-50'}">

      ${c.profile_photo
        ? `<img src="../uploads/${escapeHTML(c.profile_photo)}" class="w-10 h-10 rounded-full object-cover"/>`
        : `<div class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold text-sm"
                style="background:${getColor(i)}">
             ${initials(c.first_name + ' ' + c.last_name)}
           </div>`
      }

      <div class="flex-1 min-w-0">
        <div class="flex justify-between">
          <span class="font-semibold text-sm">${escapeHTML(c.first_name)} ${escapeHTML(c.last_name)}</span>
          <span class="text-xs text-gray-400">${formatTime(c.last_time)}</span>
        </div>

        <div class="flex justify-between mt-1">
          <span class="text-xs text-gray-500 truncate max-w-[160px]">
            ${c.sender_id == MY_ID ? 'You: ' : ''}
            ${escapeHTML(c.last_message || '')}
          </span>

          ${c.unread_count > 0
            ? `<span class="bg-blue-600 text-white rounded-full text-xs px-1.5">${c.unread_count}</span>`
            : ''}
        </div>
      </div>
    </div>
  `).join('');

  // ADD EVENT LISTENERS (NO INLINE JS)
  document.querySelectorAll('.convo').forEach((el, index) => {
    el.addEventListener('click', () => {
      const id = el.getAttribute('data-id');
      selectConvo(id, index);
    });
  });
}

// ─── SELECT CONVERSATION ─────────────────────────────────────
function selectConvo(userId, colorIndex) {
  selectedConvo = userId;

  const c = convos.find(c => c.user_id == userId);
  if (!c) return;

  const avatar = document.getElementById('chatAvatar');

  if (c.profile_photo) {
    avatar.innerHTML = `<img src="../uploads/${escapeHTML(c.profile_photo)}" class="w-10 h-10 rounded-full object-cover"/>`;
    avatar.style.background = 'transparent';
  } else {
    avatar.textContent = initials(c.first_name + ' ' + c.last_name);
    avatar.style.background = getColor(colorIndex);
  }

  document.getElementById('chatName').textContent = c.first_name + ' ' + c.last_name;
  document.getElementById('chatStatus').textContent = 'Online';
  document.getElementById('chatStatus').className = 'text-xs text-green-500';

  renderConvos();
  loadMessages(userId);

  if (refreshTimer) clearInterval(refreshTimer);

  // BETTER POLLING (8s instead of 3s)
  refreshTimer = setInterval(() => loadMessages(userId), 8000);
}

// ─── LOAD MESSAGES ───────────────────────────────────────────
function loadMessages(userId) {
  fetch(`tools/getMessages.php?user_id=${userId}`)
    .then(res => res.json())
    .then(data => {
      if (!data.success) return;
      renderMessages(data.data);
    })
    .catch(err => console.error('Error loading messages:', err));
}

// ─── RENDER MESSAGES ─────────────────────────────────────────
function renderMessages(messages) {
  const el = document.getElementById('chatMessages');

  if (messages.length === 0) {
    el.innerHTML = '<p class="text-center text-gray-400 mt-10">No messages yet 👋</p>';
    return;
  }

  const isNearBottom = el.scrollTop + el.clientHeight >= el.scrollHeight - 100;

  el.innerHTML = messages.map(m => `
    <div class="max-w-[60%] ${m.sender_id == MY_ID ? 'self-end' : 'self-start'}">

      <div class="px-3.5 py-2.5 rounded-xl text-sm
        ${m.sender_id == MY_ID
          ? 'bg-blue-600 text-white'
          : 'bg-white border'}">

        ${escapeHTML(m.message)}
      </div>

      <div class="text-xs text-gray-400 mt-1 ${m.sender_id == MY_ID ? 'text-right' : 'text-left'}">
        ${formatTime(m.created_at)}
      </div>
    </div>
  `).join('');

  if (isNearBottom) {
    el.scrollTop = el.scrollHeight;
  }
}

// ─── SEND MESSAGE ────────────────────────────────────────────
function sendMsg() {
  const input = document.getElementById('msgInput');
  const text  = input.value.trim();

  if (!text || !selectedConvo) return;

  input.value = '';

  fetch('tools/sendMessage.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      receiver_id: selectedConvo,
      message: text
    })
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      loadMessages(selectedConvo);
      loadConversations();
    }
  })
  .catch(err => console.error('Error sending:', err));

  input.focus(); // UX FIX
}

// ─── SEARCH ─────────────────────────────────────────────────
document.getElementById('searchInput').addEventListener('input', function () {
  const q = this.value.toLowerCase();

  document.querySelectorAll('#convos .convo').forEach(el => {
    const name = el.querySelector('.font-semibold')?.textContent.toLowerCase() ?? '';
    el.style.display = name.includes(q) ? '' : 'none';
  });
});

// ─── INIT ────────────────────────────────────────────────────
document.getElementById('msgInput').addEventListener('keydown', e => {
  if (e.key === 'Enter') sendMsg();
});

loadConversations();
</script>
</body>
  </html>
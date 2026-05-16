   <?php
include("../student/tools/userHeaderName.php"); 
?>

<?php
require_once('../auth/config.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'alumni') {
    header("Location: ../login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];

// Alumni info (for sidebar/topbar — same as dashboard)
$stmt = $connector->prepare("
    SELECT u.first_name, u.last_name, u.profile_photo, ap.current_position, ap.current_company
    FROM users u LEFT JOIN alumni_profiles ap ON u.id = ap.user_id WHERE u.id = ?
");
$stmt->bind_param('i', $user_id); $stmt->execute();
$stmt->bind_result($first_name, $last_name, $profile_photo, $job_title, $company);
$stmt->fetch(); $stmt->close();

// Sidebar badge counts
$stmt2 = $connector->prepare("SELECT COUNT(*) FROM connection_requests WHERE alumni_id = ? AND status = 'pending'");
$stmt2->bind_param('i', $user_id); $stmt2->execute(); $stmt2->bind_result($pending_count); $stmt2->fetch(); $stmt2->close();

$stmt3 = $connector->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0");
$stmt3->bind_param('i', $user_id); $stmt3->execute(); $stmt3->bind_result($unread_count); $stmt3->fetch(); $stmt3->close();

// Format vars (used by sidebar + topbar)
$full_name     = htmlspecialchars(($first_name ?? 'Alumni') . ' ' . ($last_name ?? ''));
$first_only    = htmlspecialchars($first_name ?? 'Alumni');
$avatar_letter = strtoupper(substr($first_name ?? 'A', 0, 1));
$display_photo = $profile_photo ?? '';
$pending_count = (int)($pending_count ?? 0);
$unread_count  = (int)($unread_count  ?? 0);
?>
<?php require_once "../includes/head.php"; ?>
<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<style>
    body { font-family: 'Inter', sans-serif; }
    ::-webkit-scrollbar { width: 4px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 99px; }
    .nav-link { transition: all .15s ease; }
    .nav-link:hover { background: #eff6ff; color: #2563eb; }
    .nav-link.active { background: #eff6ff; color: #2563eb; font-weight: 600; }
    @keyframes fadeUp {
        from { opacity: 0; transform: translateY(12px); }
        to   { opacity: 1; transform: translateY(0); }
    }
    .fade-up { animation: fadeUp .35s ease both; }
</style>

<!-- OUTER: full-height column — matches dashboard exactly -->
<div class="flex flex-col h-screen overflow-hidden bg-[#f5f6fa]">

    <!-- TOPBAR — full width across the top -->
    <?php include 'alumni_header.php'; ?>

    <!-- BELOW TOPBAR: sidebar + content side by side -->
    <div class="flex flex-1 overflow-hidden">

        <!-- SIDEBAR -->
        <?php include 'sidebar_alumni.php'; ?>

        <!-- MAIN CONTENT — messages need an inner flex row for the two panels -->
        <div class="flex flex-1 overflow-hidden">

            <!-- CONVERSATIONS LIST -->
            <div class="w-80 border-r border-gray-200 flex flex-col bg-white flex-shrink-0">
                <div class="px-4 pt-5 pb-3">
                    <h2 class="text-lg font-bold text-gray-900 mb-3">Messages</h2>
                    <div class="flex items-center bg-gray-50 border border-gray-200 rounded-lg px-3 py-1.5 gap-2 mb-3">
                        <i data-lucide="search" class="w-4 h-4 text-gray-400 flex-shrink-0"></i>
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

            </div><!-- closes chat panel -->

        </div><!-- closes inner flex row (convo list + chat) -->

    </div><!-- closes sidebar + content row -->

</div><!-- closes outer column -->

<script>
const colors = [
    "#3B82F6","#10B981","#F59E0B","#EF4444",
    "#8B5CF6","#06B6D4","#EC4899","#F97316"
];

const MY_ID = <?= (int)$_SESSION['user_id'] ?>;

let convos        = [];
let selectedConvo = null;
let refreshTimer  = null;

function initials(name) {
    if (!name) return '??';
    return name.split(' ').map(n => n[0]).join('').slice(0, 2).toUpperCase();
}

function formatTime(datetime) {
    if (!datetime) return '';
    return new Date(datetime).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

function getColor(index) {
    return colors[index % colors.length];
}

function photoImg(photo, size = '10') {
    return `<img src="../student/${photo}" class="w-${size} h-${size} rounded-full object-cover flex-shrink-0"/>`;
}

// ─── Load Conversations ───────────────────────────────────────
function loadConversations() {
    fetch('../student/tools/getConversations.php')
        .then(res => res.json())
        .then(data => {
            if (!data.success) return;
            convos = data.data;
            renderConvos();
            if (convos.length > 0 && selectedConvo === null) {
                selectConvo(convos[0].user_id, 0);
            }
        })
        .catch(err => console.error('Error loading convos:', err));
}

// ─── Render Conversations List ────────────────────────────────
function renderConvos() {
    const el = document.getElementById('convos');
    if (convos.length === 0) {
        el.innerHTML = '<p class="text-center text-gray-400 text-sm p-6">No conversations yet.</p>';
        return;
    }
    el.innerHTML = convos.map((c, i) => `
        <div onclick="selectConvo(${c.user_id}, ${i})"
             class="flex gap-3 px-4 py-3 border-b border-gray-200 cursor-pointer transition-colors
                    ${c.user_id == selectedConvo ? 'bg-blue-50' : 'hover:bg-gray-50'}">
            ${c.profile_photo
                ? photoImg(c.profile_photo)
                : `<div class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold text-sm flex-shrink-0"
                        style="background:${getColor(i)}">
                     ${initials(c.first_name + ' ' + c.last_name)}
                   </div>`
            }
            <div class="flex-1 min-w-0">
                <div class="flex justify-between items-center">
                    <span class="font-semibold text-sm text-gray-900">${c.first_name} ${c.last_name}</span>
                    <span class="text-xs text-gray-400">${formatTime(c.last_time)}</span>
                </div>
                <div class="flex justify-between items-center mt-0.5">
                    <span class="text-xs text-gray-500 overflow-hidden text-ellipsis whitespace-nowrap max-w-[160px]">
                        ${c.sender_id == MY_ID ? 'You: ' : ''}${c.last_message}
                    </span>
                    ${c.unread_count > 0
                        ? `<span class="bg-blue-600 text-white rounded-full text-xs px-1.5 py-0.5 font-bold leading-none">${c.unread_count}</span>`
                        : ''}
                </div>
            </div>
        </div>
    `).join('');
}

// ─── Select Conversation ──────────────────────────────────────
function selectConvo(userId, colorIndex) {
    selectedConvo = userId;
    const c = convos.find(c => c.user_id == userId);
    if (!c) return;

    const avatar = document.getElementById('chatAvatar');
    if (c.profile_photo) {
        avatar.innerHTML        = photoImg(c.profile_photo);
        avatar.style.background = 'transparent';
    } else {
        avatar.textContent      = initials(c.first_name + ' ' + c.last_name);
        avatar.style.background = getColor(colorIndex);
    }

    document.getElementById('chatName').textContent   = c.first_name + ' ' + c.last_name;
    document.getElementById('chatStatus').textContent = 'Active';
    document.getElementById('chatStatus').className   = 'text-xs text-green-500';

    renderConvos();
    loadMessages(userId);

    if (refreshTimer) clearInterval(refreshTimer);
    refreshTimer = setInterval(() => loadMessages(userId), 3000);
}

// ─── Load Messages ────────────────────────────────────────────
function loadMessages(userId) {
    fetch(`../student/tools/getMessages.php?user_id=${userId}`)
        .then(res => res.json())
        .then(data => {
            if (!data.success) return;
            renderMessages(data.data);
        })
        .catch(err => console.error('Error loading messages:', err));
}

// ─── Render Messages ──────────────────────────────────────────
function renderMessages(messages) {
    const el = document.getElementById('chatMessages');
    if (messages.length === 0) {
        el.innerHTML = '<p class="text-center text-gray-400 text-sm mt-10">No messages yet. Say hello! 👋</p>';
        return;
    }
    const isAtBottom = el.scrollHeight - el.clientHeight <= el.scrollTop + 50;
    el.innerHTML = messages.map(m => `
        <div class="max-w-[60%] ${m.sender_id == MY_ID ? 'self-end' : 'self-start'}">
            <div class="px-3.5 py-2.5 rounded-xl text-sm leading-relaxed
                ${m.sender_id == MY_ID
                    ? 'bg-blue-600 text-white'
                    : 'bg-white text-gray-900 border border-gray-200'}">
                ${m.message}
            </div>
            <div class="text-xs text-gray-400 mt-1 ${m.sender_id == MY_ID ? 'text-right' : 'text-left'}">
                ${formatTime(m.created_at)}
            </div>
        </div>
    `).join('');
    if (isAtBottom) el.scrollTop = el.scrollHeight;
}

// ─── Send Message ─────────────────────────────────────────────
function sendMsg() {
    const input = document.getElementById('msgInput');
    const text  = input.value.trim();
    if (!text || !selectedConvo) return;
    input.value = '';
    fetch('../student/tools/sendMessage.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ receiver_id: selectedConvo, message: text })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            loadMessages(selectedConvo);
            loadConversations();
        }
    })
    .catch(err => console.error('Error sending:', err));
}

// ─── Tab Filter ───────────────────────────────────────────────
function setTab(btn) {
    document.querySelectorAll('#tabsContainer button').forEach(t => {
        t.className = 'bg-transparent text-gray-500 border border-gray-200 rounded-full px-2.5 py-1 text-xs cursor-pointer hover:bg-gray-50 transition-colors';
    });
    btn.className = 'bg-blue-600 text-white border border-blue-600 rounded-full px-2.5 py-1 text-xs cursor-pointer transition-colors';
}

// ─── Search ───────────────────────────────────────────────────
document.getElementById('searchInput').addEventListener('input', function () {
    const q = this.value.toLowerCase();
    document.querySelectorAll('#convos > div').forEach(el => {
        const name = el.querySelector('.font-semibold')?.textContent.toLowerCase() ?? '';
        el.style.display = name.includes(q) ? '' : 'none';
    });
});

// ─── Init ─────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
    if (typeof lucide !== 'undefined') lucide.createIcons();
});
loadConversations();
</script>
<?php require_once "../includes/theme.php"; ?>
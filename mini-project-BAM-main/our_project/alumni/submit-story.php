<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
include("../student/tools/userHeaderName.php"); 

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'alumni') {
    header("Location: ../login.php");
    exit;
}

require_once("../auth/config.php");
$user_id = $_SESSION['user_id'];

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}




// Alumni info for sidebar/topbar
$stmt = $connector->prepare("
    SELECT u.first_name, u.last_name, u.profile_photo, ap.current_position, ap.current_company
    FROM users u LEFT JOIN alumni_profiles ap ON u.id = ap.user_id WHERE u.id = ?
");
$stmt->bind_param('i', $user_id); $stmt->execute();
$stmt->bind_result($first_name, $last_name, $profile_photo, $job_title, $company);
$stmt->fetch(); $stmt->close();

$stmt2 = $connector->prepare("SELECT COUNT(*) FROM connection_requests WHERE alumni_id = ? AND status = 'pending'");
$stmt2->bind_param('i', $user_id); $stmt2->execute(); $stmt2->bind_result($pending_count); $stmt2->fetch(); $stmt2->close();

$stmt3 = $connector->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0");
$stmt3->bind_param('i', $user_id); $stmt3->execute(); $stmt3->bind_result($unread_count); $stmt3->fetch(); $stmt3->close();

$full_name     = htmlspecialchars(($first_name ?? 'Alumni') . ' ' . ($last_name ?? ''));
$first_only    = htmlspecialchars($first_name ?? 'Alumni');
$avatar_letter = strtoupper(substr($first_name ?? 'A', 0, 1));
$display_photo = $profile_photo ?? '';
$pending_count = (int)($pending_count ?? 0);
$unread_count  = (int)($unread_count  ?? 0);

$success = '';
$error   = '';

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check
    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = 'Invalid request. Please try again.';
    } else {
        $title    = trim($_POST['title']    ?? '');
        $content  = trim($_POST['content']  ?? '');
        $industry = trim($_POST['industry'] ?? '');

        $allowed_industries = ['Tech', 'Finance', 'Healthcare', 'Education', 'Other'];

        if (empty($title)) {
            $error = 'Please enter a title.';
        } elseif (strlen($title) > 200) {
            $error = 'Title must be under 200 characters.';
        } elseif (empty($content)) {
            $error = 'Please enter your story content.';
        } elseif (!in_array($industry, $allowed_industries)) {
            $error = 'Please select a valid industry.';
        } else {
            // Handle cover photo upload
            $cover_photo = null;
            if (!empty($_FILES['cover_photo']['name'])) {
                $file     = $_FILES['cover_photo'];
                $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $allowed  = ['jpg', 'jpeg', 'png', 'webp'];

                if (!in_array($ext, $allowed)) {
                    $error = 'Cover photo must be JPG, PNG, or WEBP.';
                } elseif ($file['size'] > 3 * 1024 * 1024) {
                    $error = 'Cover photo must be under 3MB.';
                } else {
                    $upload_dir = '../uploads/stories/';
                    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
                    $filename    = uniqid('story_', true) . '.' . $ext;
                    $destination = $upload_dir . $filename;
                    if (move_uploaded_file($file['tmp_name'], $destination)) {
                        $cover_photo = 'uploads/stories/' . $filename;
                    } else {
                        $error = 'Failed to upload cover photo. Please try again.';
                    }
                }
            }

            if (empty($error)) {
                $stmt = $connector->prepare("
                    INSERT INTO success_stories (author_id, title, content, industry, cover_photo, status, created_at)
                    VALUES (?, ?, ?, ?, ?, 'pending', NOW())
                ");
                $stmt->bind_param('issss', $user_id, $title, $content, $industry, $cover_photo);
                if ($stmt->execute()) {
                    // Regenerate CSRF token after successful submission
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                    $success = 'Your story has been submitted and is pending review. We\'ll notify you once it\'s approved.';
                } else {
                    $error = 'Something went wrong. Please try again.';
                }
                $stmt->close();
            }
        }
    }
}
?>
<?php require_once "../includes/head.php"; ?>
<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<style>
    body { font-family: 'Inter', sans-serif; }
    ::-webkit-scrollbar { width: 4px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 99px; }
    textarea { resize: vertical; min-height: 220px; }
    #preview-wrap img { max-height: 200px; object-fit: cover; }
</style>

<div class="flex flex-col h-screen overflow-hidden bg-[#f5f6fa]">
    <?php include 'alumni_header.php'; ?>

    <div class="flex flex-1 overflow-hidden">
        <?php include 'sidebar_alumni.php'; ?>

        <div class="flex-1 overflow-y-auto">
            <div class="px-8 py-7 max-w-3xl mx-auto space-y-6">

                <!-- Header -->
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Submit Your Story</h1>
                    <p class="text-sm text-gray-400 mt-1">Share your journey to inspire current students. Stories are reviewed before publishing.</p>
                </div>

                <!-- Success -->
                <?php if ($success): ?>
                <div class="flex items-start gap-3 bg-green-50 border border-green-200 text-green-800 rounded-xl px-5 py-4 text-sm">
                    <i data-lucide="check-circle" class="w-5 h-5 text-green-500 shrink-0 mt-0.5"></i>
                    <p><?= htmlspecialchars($success) ?></p>
                </div>
                <?php endif; ?>

                <!-- Error -->
                <?php if ($error): ?>
                <div class="flex items-start gap-3 bg-red-50 border border-red-200 text-red-800 rounded-xl px-5 py-4 text-sm">
                    <i data-lucide="alert-circle" class="w-5 h-5 text-red-500 shrink-0 mt-0.5"></i>
                    <p><?= htmlspecialchars($error) ?></p>
                </div>
                <?php endif; ?>

                <!-- Form -->
                <form method="POST" enctype="multipart/form-data" class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 space-y-5">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                    <!-- Title -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Title <span class="text-red-400">*</span></label>
                        <input type="text" name="title" maxlength="200" required
                               value="<?= htmlspecialchars($_POST['title'] ?? '') ?>"
                               placeholder="e.g. How I landed my first role at a startup"
                               class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                        <p class="text-xs text-gray-400 mt-1">Max 200 characters</p>
                    </div>

                    <!-- Industry -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Industry <span class="text-red-400">*</span></label>
                        <select name="industry" required
                                class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition bg-white">
                            <option value="" disabled <?= empty($_POST['industry']) ? 'selected' : '' ?>>Select an industry</option>
                            <?php foreach (['Tech', 'Finance', 'Healthcare', 'Education', 'Other'] as $ind): ?>
                            <option value="<?= $ind ?>" <?= (($_POST['industry'] ?? '') === $ind) ? 'selected' : '' ?>><?= $ind ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Content -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Your Story <span class="text-red-400">*</span></label>
                        <textarea name="content" required
                                  placeholder="Write about your career journey, challenges you faced, lessons learned, and advice for students..."
                                  class="w-full px-4 py-2.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"><?= htmlspecialchars($_POST['content'] ?? '') ?></textarea>
                    </div>

                    <!-- Cover Photo -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1.5">Cover Photo <span class="text-gray-400 font-normal">(optional)</span></label>
                        <div class="border-2 border-dashed border-gray-200 rounded-lg p-5 text-center hover:border-blue-300 transition cursor-pointer"
                             onclick="document.getElementById('cover_photo').click()">
                            <div id="preview-wrap" class="hidden mb-3 rounded-lg overflow-hidden">
                                <img id="preview-img" src="" alt="Preview" class="w-full rounded-lg">
                            </div>
                            <i data-lucide="image" class="w-8 h-8 text-gray-300 mx-auto mb-2" id="upload-icon"></i>
                            <p class="text-sm text-gray-400" id="upload-label">Click to upload a cover photo</p>
                            <p class="text-xs text-gray-300 mt-1">JPG, PNG, WEBP — max 3MB</p>
                        </div>
                        <input type="file" name="cover_photo" id="cover_photo" accept=".jpg,.jpeg,.png,.webp" class="hidden">
                    </div>

                    <!-- Submit -->
                    <div class="flex items-center justify-between pt-2">
                        <p class="text-xs text-gray-400">
                            <i data-lucide="clock" class="w-3.5 h-3.5 inline-block mr-1 align-middle"></i>
                            Stories are reviewed by admins before going live.
                        </p>
                        <button type="submit"
                                class="px-6 py-2.5 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700 active:scale-95 transition-all flex items-center gap-2">
                            <i data-lucide="send" class="w-4 h-4"></i>
                            Submit Story
                        </button>
                    </div>
                </form>

            </div>

            <footer class="mt-8 px-8 py-4 border-t border-gray-200 bg-white flex items-center justify-between">
                <p class="text-xs text-gray-400">© <?= date('Y') ?> UCA Connect. All rights reserved.</p>
                <p class="text-xs text-gray-300">Alumni Portal</p>
            </footer>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof lucide !== 'undefined') lucide.createIcons();

    const input   = document.getElementById('cover_photo');
    const preview = document.getElementById('preview-img');
    const wrap    = document.getElementById('preview-wrap');
    const icon    = document.getElementById('upload-icon');
    const label   = document.getElementById('upload-label');

    input.addEventListener('change', function () {
        const file = this.files[0];
        if (!file) return;

        if (file.size > 3 * 1024 * 1024) {
            alert('File is too large. Maximum size is 3MB.');
            this.value = '';
            return;
        }

        const reader = new FileReader();
        reader.onload = function (e) {
            preview.src = e.target.result;
            wrap.classList.remove('hidden');
            icon.classList.add('hidden');
            label.textContent = file.name;
        };
        reader.readAsDataURL(file);
    });
});
</script>
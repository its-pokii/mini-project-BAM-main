<?php
session_start();
include("../auth/config.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$success = "";
$error   = "";

function fetchUser($connector, $user_id) {
    $stmt = $connector->prepare("
        SELECT u.first_name, u.last_name, u.email, u.profile_photo,
               ap.bio, ap.major, ap.graduation_year,
               ap.current_company, ap.current_position, ap.industry,
               ap.willing_to_mentor,
               ap.linkedin_url, ap.github_url, ap.other_url
        FROM users u
        LEFT JOIN alumni_profiles ap ON ap.user_id = u.id
        WHERE u.id = ?
    ");
    $stmt->execute([$user_id]);
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $user;
}

$user = fetchUser($connector, $user_id);

if (!$user) {
    die("User not found.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['save_profile'])) {
        $bio               = trim($_POST['bio']               ?? '');
        $major             = trim($_POST['major']             ?? '');
        $graduation_year   = trim($_POST['graduation_year']   ?? '');
        $current_company   = trim($_POST['current_company']   ?? '');
        $current_position  = trim($_POST['current_position']  ?? '');
        $industry          = trim($_POST['industry']          ?? '');
        $linkedin_url      = trim($_POST['linkedin_url']      ?? '');
        $github_url        = trim($_POST['github_url']        ?? '');
        $other_url         = trim($_POST['other_url']         ?? '');
        $willing_to_mentor = isset($_POST['willing_to_mentor']) && $_POST['willing_to_mentor'] === '1' ? 'Yes' : 'No';

        // Handle photo upload
        $photo_path = $user['profile_photo'];
        if (!empty($_FILES['profile_photo']['name'])) {
            $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $mime    = mime_content_type($_FILES['profile_photo']['tmp_name']);
            if (in_array($mime, $allowed)) {
                $ext       = pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION);
                $uploadDir = '../uploads/profiles/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $filename = 'uploads/profiles/' . $user_id . '_' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], '../' . $filename)) {
                    $photo_path = $filename;
                    $s = $connector->prepare("UPDATE users SET profile_photo = ? WHERE id = ?");
                    $s->execute([$photo_path, $user_id]);
                    $s->close();
                }
            } else {
                $error = "Invalid image format.";
            }
        }

        // Update alumni_profiles
        $s = $connector->prepare("
            UPDATE alumni_profiles
            SET bio = ?, major = ?, graduation_year = ?,
                current_company = ?, current_position = ?, industry = ?,
                willing_to_mentor = ?,
                linkedin_url = ?, github_url = ?, other_url = ?
            WHERE user_id = ?
        ");
        $s->execute([
            $bio, $major, $graduation_year,
            $current_company, $current_position, $industry,
            $willing_to_mentor,
            $linkedin_url, $github_url, $other_url,
            $user_id
        ]);
        $s->close();

        if (!$error) $success = "profile_saved";
        $user = fetchUser($connector, $user_id);
    }

    if (isset($_POST['update_password'])) {
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password']     ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        $row = $connector->prepare("SELECT password FROM users WHERE id = ?");
        $row->execute([$user_id]);
        $data = $row->get_result()->fetch_assoc();
        $row->close();
        $hash = $data['password'] ?? '';

        if (!password_verify($current, $hash)) {
            $error = "current_password_wrong";
        } elseif (strlen($new) < 8) {
            $error = "password_too_short";
        } elseif ($new !== $confirm) {
            $error = "password_mismatch";
        } else {
            $s = $connector->prepare("UPDATE users SET password = ? WHERE id = ?");
            $s->execute([password_hash($new, PASSWORD_DEFAULT), $user_id]);
            $s->close();
            $success = "password_updated";
        }
    }

    if (isset($_POST['save_prefs'])) {
        $email_notif = isset($_POST['email_notif']) ? 1 : 0;
        $push_notif  = isset($_POST['push_notif'])  ? 1 : 0;

        $s = $connector->prepare("UPDATE users SET email_notif = ?, push_notif = ? WHERE id = ?");
        $s->execute([$email_notif, $push_notif, $user_id]);
        $s->close();
        $success = "prefs_saved";
    }

    if (isset($_POST['delete_account'])) {
        $s = $connector->prepare("DELETE FROM alumni_profiles WHERE user_id = ?");
        $s->execute([$user_id]);
        $s->close();
        $s = $connector->prepare("DELETE FROM users WHERE id = ?");
        $s->execute([$user_id]);
        $s->close();
        session_destroy();
        header("Location: ../index.php");
        exit;
    }
}

$full_name         = htmlspecialchars($user['first_name'] . ' ' . $user['last_name']);
$photo_src         = !empty($user['profile_photo']) ? '../' . htmlspecialchars($user['profile_photo']) : 'https://i.pravatar.cc/64?img=47';
$willing_to_mentor = ($user['willing_to_mentor'] ?? 'No') === 'Yes' ? 1 : 0;
$email_notif       = $user['email_notif'] ?? 1;
$push_notif        = $user['push_notif']  ?? 0;

$majors     = ['GIIA', 'GIND', 'GTR', 'GMSI', 'GATE', 'GPMA'];
$industries = ['Tech', 'Finance', 'Healthcare', 'Education', 'Other'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>UCA Connect – Profile & Settings</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="font-sans bg-gray-100 h-screen flex flex-col">

  <?php include("alumni_header.php"); ?>

  <?php if ($success || $error): ?>
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      <?php if ($success === 'profile_saved'): ?>
        showToast('Profile saved!');
      <?php elseif ($success === 'password_updated'): ?>
        showToast('Password updated!');
      <?php elseif ($success === 'prefs_saved'): ?>
        showToast('Preferences saved!');
      <?php elseif ($error === 'current_password_wrong'): ?>
        showToast('Current password is incorrect.', true);
      <?php elseif ($error === 'password_mismatch'): ?>
        showToast('New passwords do not match.', true);
      <?php elseif ($error === 'password_too_short'): ?>
        showToast('Password must be at least 8 characters.', true);
      <?php elseif ($error): ?>
        showToast('<?= addslashes($error) ?>', true);
      <?php endif; ?>
    });
  </script>
  <?php endif; ?>

  <div class="flex flex-1 overflow-hidden">

    <?php include 'sidebar_alumni.php'; ?>

    <div class="flex-1 p-8 overflow-y-auto">
      <h1 class="text-xl font-bold text-gray-900 mb-6">Profile &amp; Account Settings</h1>

      <!-- PROFILE INFORMATION -->
      <section class="bg-white border border-gray-200 rounded-xl p-6 mb-6">
        <h2 class="text-base font-bold text-gray-900 mb-5">Profile Information</h2>
        <form method="POST" enctype="multipart/form-data">

          <!-- Avatar -->
          <div class="flex items-center gap-4 mb-6">
            <div class="w-16 h-16 rounded-full bg-gray-300 overflow-hidden flex-shrink-0">
              <img id="photoPreview" src="../our_project/<?= $photo_src ?>" alt="<?= $full_name ?>" class="w-full h-full object-cover" />
            </div>
            <div>
              <div class="font-semibold text-sm text-gray-900 mb-1"><?= $full_name ?></div>
              <label class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold px-3 py-1.5 rounded-lg cursor-pointer transition-colors">
                Change Photo
                <input type="file" name="profile_photo" accept="image/*" class="hidden" onchange="previewPhoto(this)" />
              </label>
            </div>
          </div>

          <!-- Bio -->
          <div class="mb-4">
            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Bio</label>
            <textarea name="bio" rows="4"
              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm text-gray-700 outline-none resize-none focus:ring-2 focus:ring-blue-200 leading-relaxed"
            ><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
          </div>

          <!-- Major + Graduation Year -->
          <div class="grid grid-cols-2 gap-4 mb-4">
            <div>
              <label class="block text-xs font-semibold text-gray-700 mb-1.5">Major</label>
              <select name="major" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm text-gray-700 outline-none focus:ring-2 focus:ring-blue-200 bg-white">
                <?php foreach ($majors as $m): ?>
                  <option value="<?= $m ?>" <?= ($user['major'] ?? '') === $m ? 'selected' : '' ?>><?= $m ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-700 mb-1.5">Graduation Year</label>
              <input type="number" name="graduation_year" min="1990" max="2099"
                     value="<?= htmlspecialchars($user['graduation_year'] ?? '') ?>"
                     class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm text-gray-700 outline-none focus:ring-2 focus:ring-blue-200" />
            </div>
          </div>

          <!-- Current Position + Company -->
          <div class="grid grid-cols-2 gap-4 mb-4">
            <div>
              <label class="block text-xs font-semibold text-gray-700 mb-1.5">Current Position</label>
              <input type="text" name="current_position"
                     value="<?= htmlspecialchars($user['current_position'] ?? '') ?>"
                     placeholder="e.g. Software Engineer"
                     class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm text-gray-700 outline-none focus:ring-2 focus:ring-blue-200" />
            </div>
            <div>
              <label class="block text-xs font-semibold text-gray-700 mb-1.5">Current Company</label>
              <input type="text" name="current_company"
                     value="<?= htmlspecialchars($user['current_company'] ?? '') ?>"
                     placeholder="e.g. Google"
                     class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm text-gray-700 outline-none focus:ring-2 focus:ring-blue-200" />
            </div>
          </div>

          <!-- Industry -->
          <div class="mb-5">
            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Industry</label>
            <select name="industry" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm text-gray-700 outline-none focus:ring-2 focus:ring-blue-200 bg-white">
              <?php foreach ($industries as $ind): ?>
                <option value="<?= $ind ?>" <?= ($user['industry'] ?? '') === $ind ? 'selected' : '' ?>><?= $ind ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Links -->
          <div class="mb-5">
            <label class="block text-xs font-semibold text-gray-700 mb-3">Links</label>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-xs text-gray-500 mb-1">LinkedIn Profile</label>
                <div class="flex items-center border border-gray-200 rounded-lg px-3 py-2 gap-2 focus-within:ring-2 focus-within:ring-blue-200">
                  <span class="text-gray-400 text-sm">🔗</span>
                  <input type="url" name="linkedin_url" value="<?= htmlspecialchars($user['linkedin_url'] ?? '') ?>"
                    class="flex-1 border-none outline-none text-sm text-gray-700 bg-transparent" />
                </div>
              </div>
              <div>
                <label class="block text-xs text-gray-500 mb-1">GitHub</label>
                <div class="flex items-center border border-gray-200 rounded-lg px-3 py-2 gap-2 focus-within:ring-2 focus-within:ring-blue-200">
                  <span class="text-gray-400 text-sm">💻</span>
                  <input type="url" name="github_url" value="<?= htmlspecialchars($user['github_url'] ?? '') ?>"
                    class="flex-1 border-none outline-none text-sm text-gray-700 bg-transparent" />
                </div>
              </div>
              <div class="col-span-2">
                <label class="block text-xs text-gray-500 mb-1">Portfolio / Other URL</label>
                <div class="flex items-center border border-gray-200 rounded-lg px-3 py-2 gap-2 focus-within:ring-2 focus-within:ring-blue-200">
                  <span class="text-gray-400 text-sm">🌐</span>
                  <input type="url" name="other_url" value="<?= htmlspecialchars($user['other_url'] ?? '') ?>"
                    class="flex-1 border-none outline-none text-sm text-gray-700 bg-transparent" />
                </div>
              </div>
            </div>
          </div>

          <!-- Willing to Mentor -->
          <div class="flex items-center justify-between py-4 border-t border-gray-100 mb-5">
            <div>
              <div class="text-sm font-semibold text-gray-900">Willing to Mentor</div>
              <div class="text-xs text-gray-500 mt-0.5">Let students know you're open to mentorship requests.</div>
            </div>
            <button type="button" id="mentorToggle" onclick="toggleSwitch('mentorToggle', 'mentorHidden')"
              class="relative w-11 h-6 rounded-full transition-colors duration-200 <?= $willing_to_mentor ? 'bg-blue-600' : 'bg-gray-300' ?> focus:outline-none">
              <span class="absolute top-0.5 <?= $willing_to_mentor ? 'left-5' : 'left-0.5' ?> w-5 h-5 bg-white rounded-full shadow transition-all duration-200"></span>
            </button>
            <input type="hidden" name="willing_to_mentor" id="mentorHidden" value="<?= $willing_to_mentor ? '1' : '0' ?>" />
          </div>

          <div class="flex justify-end">
            <button type="submit" name="save_profile"
              class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-5 py-2 rounded-lg border-none cursor-pointer transition-colors">
              Save Profile
            </button>
          </div>
        </form>
      </section>

      <!-- CHANGE PASSWORD -->
      <section class="bg-white border border-gray-200 rounded-xl p-6 mb-6">
        <h2 class="text-base font-bold text-gray-900 mb-5">Change Password</h2>
        <form method="POST">
          <div class="mb-3">
            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Current Password</label>
            <input type="password" name="current_password" placeholder="Current Password"
              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm text-gray-700 outline-none focus:ring-2 focus:ring-blue-200" />
          </div>
          <div class="mb-3">
            <label class="block text-xs font-semibold text-gray-700 mb-1.5">New Password</label>
            <input type="password" name="new_password" placeholder="New Password (min. 8 characters)"
              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm text-gray-700 outline-none focus:ring-2 focus:ring-blue-200" />
          </div>
          <div class="mb-5">
            <label class="block text-xs font-semibold text-gray-700 mb-1.5">Confirm New Password</label>
            <input type="password" name="confirm_password" placeholder="Confirm New Password"
              class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm text-gray-700 outline-none focus:ring-2 focus:ring-blue-200" />
          </div>
          <div class="flex justify-end">
            <button type="submit" name="update_password"
              class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-5 py-2 rounded-lg border-none cursor-pointer transition-colors">
              Update Password
            </button>
          </div>
        </form>
      </section>

    

 <!-- DELETE ACCOUNT -->
      <section class="bg-white border border-gray-200 rounded-xl p-6">
        <h2 class="text-base font-bold text-gray-900 mb-4">Delete Account</h2>
        <div class="bg-red-50 border border-red-200 rounded-lg px-4 py-3 mb-4">
          <p class="text-sm text-red-700">Permanently delete your UCA Connect account and all associated data. This action cannot be undone.</p>
        </div>
        <button onclick="confirmDelete()"
          class="bg-red-600 hover:bg-red-700 text-white text-sm font-semibold px-5 py-2 rounded-lg border-none cursor-pointer transition-colors">
          Delete Account
        </button>
      </section>

      <!-- LOG OUT -->
      <section class="bg-white border border-gray-200 rounded-xl p-6 mt-6">
        <h2 class="text-base font-bold text-gray-900 mb-4">Session</h2>
        <p class="text-sm text-gray-500 mb-4">Sign out of your UCA Connect account on this device.</p>
        <a href="../login.php"
           class="inline-block bg-gray-100 hover:bg-gray-200 text-gray-800 text-sm font-semibold px-5 py-2 rounded-lg transition-colors no-underline">
          Log Out
        </a>
      </section>

                </div><!-- closes page container -->

        </div><!-- closes main content -->

    </div><!-- closes sidebar/content row -->

</div><!-- closes outer wrapper -->

  <!-- TOAST -->
  <div id="toast" ...>...</div>

  <!-- DELETE CONFIRM MODAL — this stays completely separate at the bottom -->
  <div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-xl p-6 w-full max-w-sm shadow-2xl">
      <h3 class="text-base font-bold text-gray-900 mb-2">Delete Account</h3>
      <p class="text-sm text-gray-500 mb-5">Are you sure? ...</p>
      <div class="flex gap-3">
        <button onclick="closeModal()">Cancel</button>
        <form method="POST" class="flex-1">
          <button type="submit" name="delete_account">Yes, Delete</button>
        </form>
      </div>
    </div>
  </div>
  

<script>
  const toggleStates = {
    emailToggle:  <?= $email_notif       ? 'true' : 'false' ?>,
    pushToggle:   <?= $push_notif        ? 'true' : 'false' ?>,
    mentorToggle: <?= $willing_to_mentor ? 'true' : 'false' ?>
  };

  function toggleSwitch(id, hiddenId) {
    toggleStates[id] = !toggleStates[id];
    const btn    = document.getElementById(id);
    const thumb  = btn.querySelector('span');
    const hidden = document.getElementById(hiddenId);
    if (toggleStates[id]) {
      btn.classList.replace('bg-gray-300', 'bg-blue-600');
      thumb.classList.replace('left-0.5', 'left-5');
      hidden.value = '1';
    } else {
      btn.classList.replace('bg-blue-600', 'bg-gray-300');
      thumb.classList.replace('left-5', 'left-0.5');
      hidden.value = '0';
    }
  }

  function showToast(msg, isError = false) {
    const t = document.getElementById('toast');
    t.textContent = (isError ? '❌ ' : '✅ ') + msg;
    t.classList.toggle('bg-red-700', isError);
    t.classList.toggle('bg-gray-900', !isError);
    t.classList.remove('opacity-0');
    t.classList.add('opacity-100');
    setTimeout(() => { t.classList.remove('opacity-100'); t.classList.add('opacity-0'); }, 3000);
  }

  function previewPhoto(input) {
    if (input.files && input.files[0]) {
      const reader = new FileReader();
      reader.onload = e => document.getElementById('photoPreview').src = e.target.result;
      reader.readAsDataURL(input.files[0]);
    }
  }

  function confirmDelete() { document.getElementById('deleteModal').classList.remove('hidden'); }
  function closeModal()     { document.getElementById('deleteModal').classList.add('hidden'); }
  document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
  });
</script>
</body>
</html>
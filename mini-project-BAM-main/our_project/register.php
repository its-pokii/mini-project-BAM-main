<?php
session_start();
if (isset($_SESSION['errors'])) {
    echo '<div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-4">';
    foreach ($_SESSION['errors'] as $error) {
        echo '<p class="text-center">' . htmlspecialchars($error) . '</p>';
    }
    echo '</div>';
    unset($_SESSION['errors']);
}

if (isset($_SESSION['success'])) {
    echo '<div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded mb-4">';
    echo '<p class="text-center">' . htmlspecialchars($_SESSION['success']) . '</p>';
    echo '</div>';
    unset($_SESSION['success']);
}

$old = $_SESSION['user_input'] ?? [];
unset($_SESSION['user_input']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - UCA Connect</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="box-border m-0 p-0 font-['Roboto'] bg-[#f8fafc] text-[#1a202c] min-h-screen flex flex-col">

    <nav class="bg-white sticky top-0 z-50 border border-white border-b border-[#e2e8f0] px-6 py-3">
        <div class="mx-7 flex justify-between items-center">
            <div class="text-[#228cef] font-bold text-xl flex items-center gap-2">
                <i class="fa-solid fa-bolt-lightning"></i>
                <span><a class="cursor-pointer" href="index.php">UCA Connect</a></span>
            </div>
            <div class="flex gap-[1em]">
                <a href="register.php" class="no-underline bg-[#228cef] text-white border border-[#edf8fb] px-8 py-2.5 rounded-lg font-semibold hover:bg-blue-400 inline-block">Register</a>
                <a href="login.php" class="no-underline bg-white text-black border border-[#edf8fb] px-8 py-2.5 rounded-lg font-semibold hover:bg-gray-200 inline-block">Login</a>
            </div>
        </div>
    </nav>

    <main class="flex-grow flex justify-center px-4 py-12" x-data="{ role: 'student' , currentYear: ''}">
        <div class="bg-white border border-[#e2e8f0] rounded-lg w-full max-w-[650px] p-10 shadow-sm">

            <div class="text-center mb-10">
                <h1 class="text-2xl font-bold mb-2">Create Your UCA Connect Account</h1>
                <p class="text-[#718096] text-sm">Join the UCA community by registering as a student or an alumnus.</p>
                <div class="inline-flex bg-[#f1f5f9] p-1 rounded-lg mt-6">
                    <button 
                        @click="role = 'student'" 
                        :class="role === 'student' ? 'bg-white text-[#1a202c] shadow-sm' : 'bg-transparent text-[#718096]'"
                        class="px-8 py-2 rounded-md text-sm font-semibold cursor-pointer border-none transition-all">
                        Student
                    </button>
                    <button 
                        @click="role = 'alumni'" 
                        :class="role === 'alumni' ? 'bg-white text-[#1a202c] shadow-sm' : 'bg-transparent text-[#718096]'"
                        class="px-8 py-2 rounded-md text-sm font-semibold cursor-pointer border-none transition-all">
                        Alumni
                    </button>
                </div>
            </div>

            <form action="auth/register-handler.php" enctype="multipart/form-data" method="post">
                <!-- Hidden input to track role -->
                <input type="hidden" name="role" :value="role">

                <!-- Common Fields -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <div>
                        <label class="block text-sm font-semibold mb-1.5 text-[#4a5568]">First Name</label>
                        <input value="<?php echo htmlspecialchars($old['first_name'] ?? ''); ?>" name="first_name" type="text" placeholder="John" class="w-full px-4 py-2.5 bg-[#f8fafc] border border-[#cbd5e0] rounded-md text-sm outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold mb-1.5 text-[#4a5568]">Last Name</label>
                        <input value="<?php echo htmlspecialchars($old['last_name'] ?? ''); ?>" name="last_name" type="text" placeholder="Doe" class="w-full px-4 py-2.5 bg-[#f8fafc] border border-[#cbd5e0] rounded-md text-sm outline-none">
                    </div>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-semibold mb-1.5 text-[#4a5568]" x-text="role === 'student' ? 'UCA Email' : 'Email Address'"></label>
                    <div class="relative">
                        <i class="fa-regular fa-envelope absolute left-3.5 top-1/2 -translate-y-1/2 text-[#a0aec0]"></i>
                        <input <?php echo htmlspecialchars($old['email'] ?? ''); ?> name="email" type="email" :placeholder="role === 'student' ? 'john.doe@uca.ac.ma' : 'john.doe@gmail.com'" class="w-full pl-[44px] pr-4 py-2.5 bg-[#f8fafc] border border-[#cbd5e0] rounded-md text-sm outline-none">
                    </div>
                    <small class="text-[11px] text-[#718096] mt-1.5 block">
                        <i x-text="role === 'student' ? 'Must be a valid UCA email address ending with @uca.ac.ma.' : 'Must be a valid email address.'"></i>
                    </small>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-semibold mb-1.5 text-[#4a5568]">Password</label>
                    <div class="relative">
                        <i class="fa-solid fa-lock absolute left-3.5 top-1/2 -translate-y-1/2 text-[#a0aec0]"></i>
                        <input name="password" type="password" placeholder="********" class="w-full pl-[44px] pr-4 py-2.5 bg-[#f8fafc] border border-[#cbd5e0] rounded-md text-sm outline-none">
                    </div>
                    <small class="text-[11px] text-[#718096] mt-1.5 block"><i>Must be at least 8 characters long.</i></small>
                </div>
        
                <div class="mb-6">
                    <label class="block text-sm font-semibold mb-1.5 text-[#4a5568]">Confirm Password</label>
                    <div class="relative">
                        <i class="fa-solid fa-lock absolute left-3.5 top-1/2 -translate-y-1/2 text-[#a0aec0]"></i>
                        <input name="confirm_password" type="password" placeholder="********" class="w-full pl-[44px] pr-4 py-2.5 bg-[#f8fafc] border border-[#cbd5e0] rounded-md text-sm outline-none">
                    </div>
                </div>

                <!-- STUDENT ONLY FIELDS -->
                <div x-show="role === 'student'">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                        <div>
                            <label class="block text-sm font-semibold mb-1.5 text-[#4a5568]">Student ID</label>
                            <div class="relative">
                                <i class="fa-regular fa-user absolute left-3.5 top-1/2 -translate-y-1/2 text-[#a0aec0]"></i>
                                <input value="<?php echo htmlspecialchars($old['student_id'] ?? ''); ?>" name="student_id" type="text" placeholder="S12345678" class="w-full pl-[44px] pr-4 py-2.5 bg-[#f8fafc] border border-[#cbd5e0] rounded-md text-sm outline-none">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold mb-1.5 text-[#4a5568]">Current Year</label>
                            <select x-model="currentYear" name="current_year" class="w-full px-4 py-2.5 bg-[#f8fafc] border border-[#cbd5e0] rounded-md text-sm outline-none">
                                <option selected disabled>Select current year</option>
                                <option>CP1</option>
                                <option>CP2</option>
                                <option>CI1</option>
                                <option>CI2</option>
                                <option>CI3</option>
                            </select>
                        </div>
                        <div x-show="role === 'student' && ['CI1', 'CI2', 'CI3'].includes(currentYear)" x-cloak>
                            <label class="block text-sm font-semibold mb-1.5 text-[#4a5568]">Major</label>
                            <select name="major" class="w-full px-4 py-2.5 bg-[#f8fafc] border border-[#cbd5e0] rounded-md text-sm outline-none">
                                <option selected disabled>Select major</option>
                                <option>GIIA</option>
                                <option>GIND</option>
                                <option>GPMA</option>
                                <option>GMSI</option>
                                <option>GATE</option>
                                <option>GTR</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- ALUMNI ONLY FIELDS -->
                <div x-show="role === 'alumni'" x-cloak>
                    <div class="mb-6">
                        <label class="block text-sm font-semibold mb-1.5 text-[#4a5568]">Industry</label>
                        <select name="industry" class="w-full px-4 py-2.5 bg-[#f8fafc] border border-[#cbd5e0] rounded-md text-sm outline-none">
                            <option selected disabled>Select Industry</option>
                            <option>Tech</option>
                            <option>Healthcare</option>
                            <option>Finance</option>
                            <option>Education</option>
                            <option>Engineering</option>
                            <option>Marketing</option>
                            <option>Law</option>
                            <option>Other</option>
                        </select>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-semibold mb-1.5 text-[#4a5568]">Current Company</label>
                        <div class="relative">
                            <i class="fa-solid fa-building absolute left-3.5 top-1/2 -translate-y-1/2 text-[#a0aec0]"></i>
                            <input value="<?php echo htmlspecialchars($old['company'] ?? ''); ?>" name="company" type="text" placeholder="Capgemini" class="w-full pl-[44px] pr-4 py-2.5 bg-[#f8fafc] border border-[#cbd5e0] rounded-md text-sm outline-none">
                        </div>
                        <small class="text-[11px] text-[#718096] mt-1.5 block"><i>Preferable to help filtration.</i></small>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-semibold mb-1.5 text-[#4a5568]">Current Job Position</label>
                        <div class="relative">
                            <i class="fa-solid fa-briefcase absolute left-3.5 top-1/2 -translate-y-1/2 text-[#a0aec0]"></i>
                            <input value="<?php echo htmlspecialchars($old['job'] ?? ''); ?>" name="job" type="text" placeholder="Developer" class="w-full pl-[44px] pr-4 py-2.5 bg-[#f8fafc] border border-[#cbd5e0] rounded-md text-sm outline-none">
                        </div>
                        <small class="text-[11px] text-[#718096] mt-1.5 block"><i>Preferable to help filtration.</i></small>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-semibold mb-1.5 text-[#4a5568]">Willing to mentor other students?</label>
                        <select name="willing_to_mentor" class="w-full px-4 py-2.5 bg-[#f8fafc] border border-[#cbd5e0] rounded-md text-sm outline-none">
                            <option selected disabled>Yes / No</option>
                            <option>Yes</option>
                            <option>No</option>
                        </select>
                        <small class="text-[11px] text-[#718096] mt-1.5 block"><i>Preferable to help other passionate students.</i></small>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-semibold mb-1.5 text-[#4a5568]">Major of Graduation</label>
                        <select name="major" class="w-full px-4 py-2.5 bg-[#f8fafc] border border-[#cbd5e0] rounded-md text-sm outline-none">
                            <option selected disabled>Select major</option>
                            <option>GIIA</option>
                            <option>GIND</option>
                            <option>GPMA</option>
                            <option>GMSI</option>
                            <option>GATE</option>
                            <option>GTR</option>
                        </select>
                    </div>

                    <div class="mb-6">
                        <label class="block text-sm font-semibold mb-1.5 text-[#4a5568]">Year of Graduation</label>
                        <div class="relative">
                            <i class="fa-solid fa-graduation-cap absolute left-3.5 top-1/2 -translate-y-1/2 text-[#a0aec0]"></i>
                            <input value="<?php echo htmlspecialchars(string: $old['grad_year'] ?? ''); ?>" name="grad_year" type="number" placeholder="2025" class="w-full pl-[44px] pr-4 py-2.5 bg-[#f8fafc] border border-[#cbd5e0] rounded-md text-sm outline-none">
                        </div>
                        <small class="text-[11px] text-[#718096] mt-1.5 block"><i>Preferable to help filtration.</i></small>
                    </div>
                </div>

                <!-- COMMON FIELDS CONTINUED -->
                <div class="mb-6">
                    <label class="block text-sm font-semibold mb-1.5 text-[#4a5568]">About Me (Bio)</label>
                    <textarea name="about_me" maxlength="500" placeholder="Tell us a little about yourself..." class="w-full px-4 py-2.5 bg-[#f8fafc] border border-[#cbd5e0] rounded-md text-sm outline-none resize-none h-[100px]"><?php echo htmlspecialchars($old['about_me'] ?? ''); ?></textarea>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-semibold mb-1.5 text-[#4a5568]">Profile Photo</label>
                    <div class="flex flex-col items-center gap-2.5 mb-5">
                        <input name="profile_photo" type="file" id="imageInput" accept="image/*" class="hidden">
                        <div class="w-[100px] h-[100px] rounded-full overflow-hidden border-2 border-[#228cef] cursor-pointer relative group"
                            onclick="document.getElementById('imageInput').click();">
                            <img id="preview" src="https://www.shareicon.net/data/128x128/2016/07/26/802043_man_512x512.png" alt="Preview" class="w-full h-full object-cover opacity-50">
                            <div class="absolute bottom-0 w-full bg-black/50 text-white text-xs py-1 text-center opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                                <i class="fa-solid fa-camera"></i>
                            </div>
                        </div>
                        <small class="text-[11px] text-[#718096]">Click the circle to upload a photo (Max 2MB).</small>
                    </div>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-semibold mb-1.5 text-[#4a5568]">LinkedIn Profile URL</label>
                    <div class="relative">
                        <i class="fa-brands fa-linkedin absolute left-3.5 top-1/2 -translate-y-1/2 text-[#a0aec0]"></i>
                        <input value="<?php echo htmlspecialchars($old['linkedin_url'] ?? ''); ?>" name="linkedin_url" type="url" placeholder="https://linkedin.com/in/johndoe" class="w-full pl-[44px] pr-4 py-2.5 bg-[#f8fafc] border border-[#cbd5e0] rounded-md text-sm outline-none">
                    </div>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-semibold mb-1.5 text-[#4a5568]">GitHub Profile URL</label>
                    <div class="relative">
                        <i class="fa-brands fa-github absolute left-3.5 top-1/2 -translate-y-1/2 text-[#a0aec0]"></i>
                        <input value="<?php echo htmlspecialchars($old['github_url'] ?? ''); ?>" name="github_url" type="url" placeholder="https://github.com/user123" class="w-full pl-[44px] pr-4 py-2.5 bg-[#f8fafc] border border-[#cbd5e0] rounded-md text-sm outline-none">
                    </div>
                </div>

                <div class="mb-6">
                    <label class="block text-sm font-semibold mb-1.5 text-[#4a5568]">Other URLs</label>
                    <div class="relative">
                        <i class="fa-brands absolute left-3.5 top-1/2 -translate-y-1/2 text-[#a0aec0]"></i>
                        <input value="<?php echo htmlspecialchars($old['other_url'] ?? ''); ?>" name="other_url" type="url" :placeholder="role === 'student' ? 'https://myportfolio.org' : 'https://myprofile.com'" class="w-full pl-[44px] pr-4 py-2.5 bg-[#f8fafc] border border-[#cbd5e0] rounded-md text-sm outline-none">
                    </div>
                </div>

                <button type="submit" class="w-full bg-[#228cef] hover:bg-blue-400 text-white border-none py-3.5 rounded-md font-bold cursor-pointer mt-2">
                    <span x-text="role === 'student' ? 'Register as Student' : 'Register as Alumni'"></span>
                </button>

                <p class="text-center mt-6 text-sm text-[#4a5568]">
                    Already have an account? <a href="login.php" class="text-[#228cef] hover:text-blue-300 no-underline font-semibold">Login</a>
                </p>
            </form>
        </div>
    </main>

    <?php
        include("includes/copyright.php");
    ?>

    <style>
        [x-cloak] { display: none !important; }
    </style>

    <script>
        const imageInput = document.getElementById('imageInput');
        const preview = document.getElementById('preview');

        imageInput.onchange = evt => {
            const [file] = imageInput.files;
            if (file) {
                preview.src = URL.createObjectURL(file);
            }
        }
    </script>

</body>
</html>
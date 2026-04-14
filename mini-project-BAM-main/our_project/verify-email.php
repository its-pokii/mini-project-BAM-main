<?php 
include("C:/xampp/htdocs/alumni-project/our_project/includes/headers/free-header.php");
include("C:/xampp/htdocs/alumni-project/our_project/includes/script-var.php");
session_start();

// Get email & link from session
$email = $_SESSION['email_to_verify'] ?? 'your email';
$verification_link = $_SESSION['verification_link'] ?? '#';
$token = $_SESSION['token'];
?>
<!-- <link rel="stylesheet" href="../tailwind/src/output.css"> -->
<body class="bg-[#f8fafc] box-border p-0 m-0 font-['Roboto']">
    <div class="flex items-center justify-center h-[80vh]">
        <div class="mt-[50px] px-[1em] py-[3em]  bg-white md:px-[4em] md:py-[5em] rounded-3xl shadow-lg">
            <div class="flex justify-center mb-8">
                <div class=mt-[-30px]><img class="w-16" src="uploads/assets/yes.png"></div>
            </div>
            
            <div class="text-center">
                <h1 class="text-2xl font-bold mb-2">Verify Your Academique Email Adress</h1>
                <p class="text-gray-600 mb-4">
                    You have entered <span class="underline font-bold text-gray-800"><?= htmlspecialchars($email) ?></span>.
                </p>
                <p class="text-gray-600 mb-6">
                    Please click the button below to verify your account.
                </p>

                <!-- Verify Button -->
                <a href="<?php $verification_link . "?token=" . $token ?>" class="inline-block bg-primary  hover:bg-blue-400 text-white px-6 py-3 rounded-lg transition">
                    Verify Now
                </a>

                <p class="text-gray-500 mt-6 text-sm">
                    Or copy and paste this link into your browser:
                </p>
                <p class="cursor-pointer hover:text-blue-400 underline text-blue-600 break-all text-sm">
                    <?= htmlspecialchars($verification_link) ?>
                </p>
            </div>
        </div>
    </div>
</body>
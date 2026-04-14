<?php
session_start();
if (isset($_SESSION['reset_password_errors'])) {
    echo '<div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-4">';
    foreach ($_SESSION['reset_password_errors'] as $reset_password_errors) {
        echo '<p class="text-center">' . htmlspecialchars($reset_password_errors) . '</p>';
    }
    echo '</div>';
    unset($_SESSION['reset_password_errors']);
}
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Login - UCA Connect</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Bitcount+Prop+Single:wght@100..900&family=Caudex:ital,wght@0,400;0,700;1,400;1,700&family=Cedarville+Cursive&family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Jacquard+24&family=Open+Sans:ital,wght@0,300..800;1,300..800&family=Pixelify+Sans:wght@400..700&family=Roboto:ital,wght@0,100..900;1,100..900&family=Science+Gothic:wght@100..900&family=Sixtyfour&family=Workbench&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    </head>
    <body class="font-['Roboto'] box-border m-0 p-0 font-[Inter,sans-serif] bg-[#f8fafc] text-[#1a202c] min-h-screen flex flex-col">

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

        <main class="grow flex justify-center items-center p-5">
            <div class="bg-white border border-[#e2e8f0] rounded-xl w-full max-w-[400px] p-10 shadow-md">
                <h2 class="text-center text-2xl font-bold mb-8 text-[#1a202c]">Reset your password</h2>

                <form action="auth/reset-password-handler.php" method="post">
                    <div class="mb-6">
                        <label class="block text-sm font-semibold mb-1.5 text-[#4a5568]"> New Password</label>
                        <div class="relative">
                            <i class="fa-solid fa-lock absolute left-3.5 top-1/2 -translate-y-1/2 text-[#a0aec0]"></i>
                            <input name="new_password" type="password" placeholder="********" class="w-full pl-[44px] pr-4 py-2.5 bg-[#f8fafc] border border-[#cbd5e0] rounded-md text-sm outline-none">
                        </div>
                        <small class="text-[11px] text-[#718096] mt-1.5 block"><i>Must be at least 8 characters long.</i></small>
                    </div>
            
                    <div class="mb-6">
                        <label class="block text-sm font-semibold mb-1.5 text-[#4a5568]">Confirm Password</label>
                        <div class="relative">
                            <i class="fa-solid fa-lock absolute left-3.5 top-1/2 -translate-y-1/2 text-[#a0aec0]"></i>
                            <input name="confirm_new_password" type="password" placeholder="********" class="w-full pl-[44px] pr-4 py-2.5 bg-[#f8fafc] border border-[#cbd5e0] rounded-md text-sm outline-none">
                        </div>
                    </div>

                    <button name="save" type="submit"
                        class="w-full bg-[#228cef] hover:bg-blue-300 text-white border-none py-3 rounded-lg font-bold text-base cursor-pointer transition">
                        Save
                    </button>

                    <p class="text-center mt-6 text-sm text-[#718096]">
                        Don't have an account? <a href="register.php" class="text-[#228cef] hover:text-blue-300 no-underline font-semibold">Register</a>
                    </p>
                </form>
            </div>
        </main>

        <footer class="py-6 text-center text-xs text-[#718096] border-t border-[#e2e8f0] bg-white">
            <p>&copy; 2026 UCA Connect. All rights reserved.</p>
        </footer>

    </body>
</html>
<?php 
include("includes/headers/free-header.php");
include("includes/script-var.php")
?>

    <body class="bg-[#f8fafc] box-border p-0 m-0 font-['Roboto']">
        <main>
            <div class="min-h-[500px] px-4 py-12 bg-light-bg flex justify-center items-center">
                <div class="connect-container w-[800px] text-center flex flex-col justify-center items-center">
                    <div class="welcome-text">
                        <h1 class="text-primary text-[50px] mx-auto mb-[5px]">Connect with UCA Alumni</h1>
                    </div>
                    <div class="desc-txt">
                        <p class="text-muted">Forge meaningful connections, discover inspiring sucess stories, and contribute to a vibrant university community
                        </p>
                    </div>
                    <div class="flex gap-[1em]">
                        <a href="login.php" class="no-underline bg-primary text-white border border-[#edf8fb] px-8 py-2.5 rounded-lg font-semibold hover:bg-blue-400 inline-block">Login</a>
                        <a href="register.php" class="no-underline bg-white text-black border border-[#edf8fb] px-8 py-2.5 rounded-lg font-semibold hover:bg-gray-200 inline-block">Register</a>
                    </div>
                </div>
            </div>
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
                <h2 class="text-3xl font-bold text-center mb-12">How It Works</h2>
                
                <div class="grid md:grid-cols-3 gap-8">
                    <div class="text-center">
                        <div class="bg-blue-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i data-lucide="users" class="w-8 h-8 text-blue-600"></i>
                        </div>
                        <h3 class="text-xl font-semibold mb-2">Find Alumni</h3>
                        <p class="text-gray-600">
                            Search and connect with alumni from your field of study working at top companies worldwide
                        </p>
                    </div>

                    <div class="text-center">
                        <div class="bg-blue-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i data-lucide="message-circle" class="w-8 h-8 text-blue-600"></i>
                        </div>
                        <h3 class="text-xl font-semibold mb-2">Get Mentorship</h3>
                        <p class="text-gray-600">
                            Message alumni directly for career advice, interview tips, and industry insights
                        </p>
                    </div>

                    <div class="text-center">
                        <div class="bg-blue-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i data-lucide="book-open" class="w-8 h-8 text-blue-600"></i>
                        </div>
                        <h3 class="text-xl font-semibold mb-2">Read Success Stories</h3>
                        <p class="text-gray-600">
                            Get inspired by career journeys and learn from the experiences of successful alumni
                        </p>
                    </div>
                </div>
            </div>

            <div class="gateway bg-light-bg h-[400px] flex justify-center items-center border-b border-[rgb(210,211,211)]">
                <div class="w-[800px] text-center flex flex-col justify-center items-center">
                    <div class="gateway-txt text-[22px]">
                        <h2 class="text-primary">Your Gateway to a Thriving UCA Network</h2>
                    </div>
                    <div class="text-muted">
                        <p>
                            UCA Connect bridges the gap between past and present, offering students unparalleled access to
                            mentorship and career insights from successful alumni. Alumni can give back by mentoring the next generation, 
                            sharing their professional journeys, and rediscovering old connections.
                        </p>
                        <p>
                            Whether you're looking for guidance, aiming to share your expertise, or simply want to stay in touch with UCA community, 
                            our platform provides the tools and connections you need to succeed.
                        </p>
                    </div>
                </div>
            </div>

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 text-center">
                <h2 class="text-3xl font-bold mb-4">Ready to Get Started?</h2>
                <p class="text-gray-600 mb-8">Join the UCA Alumni Network today</p>
                <a href="register.php" class="no-underline bg-[#228cef] text-white border border-[#edf8fb] px-8 py-3 rounded-lg font-semibold hover:bg-blue-400 inline-block">
                    Create Your Account
                </a>
            </div>
        </main>
        <?php 
        include("includes/footer.php");
        ?>
    </body>

    <script>
        lucide.createIcons();
    </script>
</html>
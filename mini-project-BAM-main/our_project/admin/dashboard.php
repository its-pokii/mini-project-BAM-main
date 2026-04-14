<?php 
require_once "../includes/head.php";
require_once "api/get-stats.php";
?>

<div class="flex">
    <?php
    require_once "../includes/sidebar.php";
    ?>
    <div class="w-full">
        <?php require_once "../includes/headers/header.php";?>
        <div class="m-12">
            <div>
                <h2 class="text-3xl font-bold">Admin Dashboard</h2>
            </div>
            <div class="mt-7">
                <div>
                    <h3 class="text-2xl font-medium text-gray-800">Overview Statistics</h3>
                </div>
                <div class="text-gray-400 flex justify-center gap-4 p-4">
                    <div class="border border-gray-300 w-1/4 h-[200px] bg-white p-4 rounded shadow">
                        <div class="text-gray-400 flex justify-between items-center">
                            <h2 class="text-gray-700 text-xl font-semibold">Total Users</h2>
                            <i class="text-xl fa-solid fa-users"></i>
                        </div>
                        <h2 class="text-gray-900 text-5xl mt-5 mb-3" id="total-users"></h2>
                        <p>+ or - ....% from last day</p>
                    </div>
                    <div class="border border-gray-300 w-1/4 h-[200px] bg-white p-4 rounded shadow">
                        <div class="text-gray-500 flex justify-between items-center">
                            <h2 class="text-gray-600 text-xl font-semibold">Alumni Pending Approval</h2>
                            <i class="text-xl text-gray-400 fa-solid fa-clipboard-check"></i>
                        </div>
                        <h2 class="text-gray-900 text-5xl mt-5 mb-3" id="alumni-pending"></h2>
                        <p>.... requests this week</p>
                    </div>
                    <div class="border border-gray-300 w-1/4 h-[200px] bg-white p-4 rounded shadow">
                        <div class="text-gray-500 flex justify-between items-center">
                            <h2 class="text-gray-600 text-xl font-semibold">Stories Pending Approval</h2>
                            <i class="text-xl text-gray-400 fa-solid fa-heart-circle-check"></i>
                        </div>
                        <h2 class="text-gray-900 text-5xl mt-5 mb-3" id="stories-pending"></h2>
                        <p>... submitted this week</p>
                    </div>
                    <div class="border border-gray-300 w-1/4 h-[200px] bg-white p-4 rounded shadow">
                        <div class="text-gray-500 flex justify-between items-center">
                            <h2 class="text-gray-600 text-xl font-semibold">Active Users</h2>
                            <i class="text-xl text-gray-400 fa-solid fa-user-check"></i>
                        </div>
                        <h2 class="text-gray-900 text-5xl mt-5 mb-3" id="active-users"></h2>
                        <p>.... this quarter</p>
                    </div>
                </div>
            </div>
            <div></div>
        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', async function() {
        let response = await fetch("api/get-stats.php");
        let data = await response.json();

        let totalUsers = document.getElementById('total-users');
        let alumniPending = document.getElementById('alumni-pending');
        let storiesPending = document.getElementById('stories-pending');
        let activeUsers = document.getElementById('active-users');

        totalUsers.innerHTML = data.total_users;
        alumniPending.innerHTML = data.alumni_pending;
        storiesPending.innerHTML = data.stories_pending;
        activeUsers.innerHTML = data.active_users;
    })
</script>
<?php
require_once "../includes/footer.php";
require_once "../includes/theme.php";
?>
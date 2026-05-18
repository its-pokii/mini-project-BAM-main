<?php 
require_once "../includes/head.php";
require_once "api/get-chart-data.php"; // Pull in the logic
require_once "../auth/config.php"; 
require_once "../includes/theme.php";
?>

<div class="flex">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <?php
    require_once "../includes/sidebar.php";
    ?>
    <div class="w-full">
        <?php require_once "../includes/headers/header.php";?>

        <main class="p-8">
            <div class="max-w-7xl mx-auto">
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-800">Analytics Dashboard</h1>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                    <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm h-80">
                        <canvas id="userChart"></canvas>
                    </div>
                    <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm h-80">
                        <canvas id="trendChart"></canvas>
                    </div>
                </div>

                <?php include "../includes/stats-table.php"; ?>
            </div>
        </main>
    </div>
</div>

<?php 
include "../includes/stats-script.php"; // Pull in the JavaScript
require_once "../includes/footer.php"; 
?>
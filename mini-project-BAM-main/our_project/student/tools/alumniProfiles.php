<?php 
require_once('../../auth/config.php');

$stmt = mysqli_prepare($connector, 'SELECT * FROM Alumni_profiles INNER JOIN users ON users.id = alumni_profiles.user_id');
mysqli_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$alumni =[];
while($row = mysqli_fetch_assoc($result)){
    $alumni[]= $row;
    }
header('Content-Type: application/json');
echo json_encode($alumni);
?>

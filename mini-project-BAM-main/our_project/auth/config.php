<?php
try {
    $connector = new mysqli("localhost",
                        "root",
                        "",
                        "alumni_network");
    
    // echo "<p style='color: green;'>Connected!</p>";
}
catch (mysqli_sql_exception) {
    echo "<p style='color: red;'>Couldn't connect!</p>";
}
?>
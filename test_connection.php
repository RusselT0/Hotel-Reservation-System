
<?php

require_once 'database.php';

echo "Database connection successful!";

echo "<p>Connected to: " .
    htmlspecialchars($dbname, ENT_QUOTES, 'UTF-8') .
    "</p>";
?>
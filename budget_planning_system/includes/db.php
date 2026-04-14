<?php
// includes/db.php  –  Database connection (PDO)
define('DB_HOST', 'localhost');
define('DB_NAME', 'budget_planning_system');
define('DB_USER', 'root');
define('DB_PASS', '');          // Change to your MySQL password if needed

try {
    $pdo = new PDO(
        "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4",
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
         PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
         PDO::ATTR_EMULATE_PREPARES   => false]
    );
} catch (PDOException $e) {
    die('<div style="font-family:monospace;padding:20px;background:#fee;border:1px solid red;margin:20px">
         <b>Database Connection Failed:</b><br>'.$e->getMessage().'<br><br>
         Make sure XAMPP MySQL is running and you have imported <code>db/budget_system.sql</code>
         </div>');
}

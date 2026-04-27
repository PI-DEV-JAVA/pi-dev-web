<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=pidev', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo->exec("UPDATE offers SET recruiter_id = NULL WHERE recruiter_id NOT IN (SELECT id FROM users) AND recruiter_id IS NOT NULL");
    $pdo->exec("UPDATE formation SET recruiter_id = NULL WHERE recruiter_id NOT IN (SELECT id FROM users) AND recruiter_id IS NOT NULL");
    $pdo->exec("UPDATE project SET project_manager_id = NULL WHERE project_manager_id NOT IN (SELECT id FROM users) AND project_manager_id IS NOT NULL");

    echo "Orphans sanitized!\n";
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}

<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=skilllink_services', 'root', '');
    $stmt = $pdo->query("SELECT email, password FROM users WHERE user_type='super_admin' OR user_type='admin'");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (PDOException $e) {
    echo $e->getMessage();
}
?>

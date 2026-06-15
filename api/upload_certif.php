<?php
session_start();
header('Content-Type: application/json');

$host = 'mysql-24db47b8-ndaoba26-ba1b.g.aivencloud.com'; 
$port = '12042';
$db   = 'defaultdb'; 
$user = 'avnadmin'; 
$pass = 'AVNS_b3qBqbHeP_yrkm3vkRD';

try {
    $options = [
        PDO::MYSQL_ATTR_SSL_CA => true,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ];
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db;charset=utf8", $user, $pass, $options);
    
    // Script de création automatique de la table
    $sql = "CREATE TABLE IF NOT EXISTS certifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        document_path VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );";
    $pdo->exec($sql);
    echo json_encode(['status' => 'success', 'message' => 'Table creee ou deja existante !']);
} catch (\PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Erreur : ' . $e->getMessage()]);
}

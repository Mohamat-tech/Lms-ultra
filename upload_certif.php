<?php
session_start();
header('Content-Type: application/json');

// Configuration de la connexion à la base de données Aiven
$host = 'mysql-24db47b8-ndaoba26-ba1b.g.aivencloud.com'; 
$port = '12042';
$db   = 'defaultdb'; 
$user = 'avnadmin'; 
$pass = 'AVNS_b3qBqbHeP_yrkm3vkRD';

try {
    // Aiven impose une connexion sécurisée SSL, on l'active ici
    $options = [
        PDO::MYSQL_ATTR_SSL_CA => true,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ];
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db;charset=utf8", $user, $pass, $options);
} catch (\PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Erreur de connexion : ' . $e->getMessage()]);
    exit;
}

// Traitement du téléversement du fichier (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['certification'])) {
    $userId = $_SESSION['user_id'] ?? 1; // ID temporaire pour le test
    
    $targetDir = "uploads/certs/";
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    
    $fileName = time() . '_' . basename($_FILES["certification"]["name"]);
    $targetFilePath = $targetDir . $fileName;
    $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);
    
    $allowTypes = array('pdf', 'png', 'jpg', 'jpeg');
    if (in_array(strtolower($fileType), $allowTypes)) {
        if (move_uploaded_file($_FILES["certification"]["tmp_name"], $targetFilePath)) {
            // Insertion des données dans la table certifications
            $stmt = $pdo->prepare("INSERT INTO certifications (user_id, document_path) VALUES (?, ?)");
            $stmt->execute([$userId, $targetFilePath]);
            
            echo json_encode(['status' => 'success', 'message' => 'Certification soumise avec succès. Analyse en cours.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Échec du téléversement du fichier.']);
        }
    } else {
        echo json_encode(

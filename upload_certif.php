<?php
session_start();
header('Content-Type: application/json');

$host = 'localhost'; $db = 'lms_platform'; $user = 'root'; $pass = '';
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
} catch (\PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Erreur de connexion']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['certification'])) {
    $userId = $_SESSION['user_id'] ?? 1; // ID de session mocké pour l'exemple
    
    $targetDir = "uploads/certs/";
    if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);
    
    $fileName = time() . '_' . basename($_FILES["certification"]["name"]);
    $targetFilePath = $targetDir . $fileName;
    $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);
    
    $allowTypes = array('pdf', 'png', 'jpg', 'jpeg');
    if (in_array(strtolower($fileType), $allowTypes)) {
        if (move_uploaded_file($_FILES["certification"]["tmp_name"], $targetFilePath)) {
            // Insertion en base
            $stmt = $pdo->prepare("INSERT INTO certifications (user_id, document_path) VALUES (?, ?)");
            $stmt->execute([$userId, $targetFilePath]);
            
            echo json_encode(['status' => 'success', 'message' => 'Certification soumise avec succès. Analyse en cours.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Échec du téléversement.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Seuls les formats PDF, JPG, JPEG & PNG sont autorisés.']);
    }
    exit;
}

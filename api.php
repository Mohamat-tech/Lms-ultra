<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Autorise le frontend Vercel

// Connexion MySQL
 $host = 'localhost';
 $db   = 'lms_database';
 $user = 'root';
 $pass = '';
 $charset = 'utf8mb4';

 $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
 $pdo = new PDO($dsn, $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);

 $action = $_GET['action'] ?? '';

switch($action) {
    case 'get_courses':
        $stmt = $pdo->query("SELECT * FROM courses");
        echo json_encode($stmt->fetchAll());
        break;
    
    case 'save_progress':
        // Enregistre la progression de l'étudiant
        $username = $_POST['username'];
        $course_id = $_POST['course_id'];
        $score = $_POST['score'];
        
        $stmt = $pdo->prepare("INSERT INTO progress (username, course_id, score) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE score = ?");
        $stmt->execute([$username, $course_id, $score, $score]);
        
        // Vérifie si le module est validé pour certificat
        if($score >= 50) {
            echo json_encode(['status' => 'success', 'message' => 'Module validé, certificat disponible!']);
        } else {
            echo json_encode(['status' => 'success', 'message' => 'Score enregistré.']);
        }
        break;
}
?>

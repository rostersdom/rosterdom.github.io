<?php
session_start();
require_once 'config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['photos' => []]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT 
            p.*, 
            AVG(r.rating) as average_rating,
            COUNT(r.id) as vote_count
        FROM photos p
        LEFT JOIN ratings r ON p.id = r.photo_id
        WHERE p.user_id = ?
        GROUP BY p.id
        ORDER BY p.upload_date DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $photos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Обрабатываем данные
    foreach ($photos as &$photo) {
        $photo['average_rating'] = $photo['average_rating'] ? floatval($photo['average_rating']) : null;
        $photo['vote_count'] = intval($photo['vote_count']);
    }
    
    echo json_encode(['photos' => $photos]);
} catch (PDOException $e) {
    echo json_encode(['photos' => [], 'error' => $e->getMessage()]);
}
?>
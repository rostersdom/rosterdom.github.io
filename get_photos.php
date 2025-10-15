<?php
session_start();
require_once 'config/database.php';

header('Content-Type: application/json');

if (isset($_GET['count'])) {
    // Возвращаем количество неоцененных фото
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['count' => 0]);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM photos 
            WHERE id NOT IN (
                SELECT photo_id FROM ratings WHERE user_id = ?
            ) AND user_id != ?
        ");
        $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode(['count' => intval($result['count'])]);
    } catch (PDOException $e) {
        echo json_encode(['count' => 0, 'error' => $e->getMessage()]);
    }
    exit;
}

// Возвращаем следующее фото для оценки
if (isset($_SESSION['user_id'])) {
    try {
        $stmt = $pdo->prepare("
            SELECT p.*, u.name as user_name 
            FROM photos p 
            LEFT JOIN users u ON p.user_id = u.id 
            WHERE p.id NOT IN (
                SELECT photo_id FROM ratings WHERE user_id = ?
            ) AND p.user_id != ?
            ORDER BY p.upload_date DESC
            LIMIT 1
        ");
        $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
        $photo = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode(['photo' => $photo]);
    } catch (PDOException $e) {
        echo json_encode(['photo' => null, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['photo' => null]);
}
?>
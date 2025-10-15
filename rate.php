<?php
session_start();
require_once 'config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Необходима авторизация']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $photo_id = $_POST['photo_id'] ?? null;
    $rating = $_POST['rating'] ?? null;
    
    if (!$photo_id || !$rating) {
        echo json_encode(['success' => false, 'message' => 'Неверные данные']);
        exit;
    }
    
    try {
        // Проверяем, не оценивал ли пользователь уже это фото
        $stmt = $pdo->prepare("SELECT id FROM ratings WHERE user_id = ? AND photo_id = ?");
        $stmt->execute([$_SESSION['user_id'], $photo_id]);
        
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Вы уже оценили это фото']);
            exit;
        }
        
        // Добавляем оценку
        $stmt = $pdo->prepare("INSERT INTO ratings (user_id, photo_id, rating) VALUES (?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $photo_id, $rating]);
        
        echo json_encode(['success' => true]);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Ошибка базы данных: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Неверный метод запроса']);
}
?>
<?php
session_start();
require_once 'config/database.php';

header('Content-Type: application/json');

// Проверяем права администратора
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Необходима авторизация']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT is_admin FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if (!$user || !$user['is_admin']) {
        echo json_encode(['success' => false, 'message' => 'Доступ запрещен']);
        exit;
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Ошибка проверки прав']);
    exit;
}

try {
    // Получаем все фото с информацией о пользователях
    $stmt = $pdo->prepare("
        SELECT 
            p.*,
            u.name as user_name,
            u.is_admin as user_is_admin,
            AVG(r.rating) as average_rating,
            COUNT(r.id) as vote_count
        FROM photos p
        LEFT JOIN users u ON p.user_id = u.id
        LEFT JOIN ratings r ON p.id = r.photo_id
        GROUP BY p.id
        ORDER BY p.upload_date DESC
    ");
    
    $stmt->execute();
    $photos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Обрабатываем данные
    foreach ($photos as &$photo) {
        $photo['average_rating'] = $photo['average_rating'] ? floatval($photo['average_rating']) : null;
        $photo['vote_count'] = intval($photo['vote_count']);
        $photo['user_is_admin'] = (bool)$photo['user_is_admin'];
    }
    
    echo json_encode(['success' => true, 'photos' => $photos]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
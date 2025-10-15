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
    // Получаем статистику
    $stats = [];
    
    // Всего фото
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM photos");
    $stats['total_photos'] = $stmt->fetch()['count'];
    
    // Всего пользователей
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $stats['total_users'] = $stmt->fetch()['count'];
    
    // Всего оценок
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM ratings");
    $stats['total_ratings'] = $stmt->fetch()['count'];
    
    // Администраторов
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE is_admin = TRUE");
    $stats['total_admins'] = $stmt->fetch()['count'];
    
    echo json_encode(['success' => true, 'stats' => $stats]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
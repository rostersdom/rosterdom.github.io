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
    // Получаем всех пользователей
    $stmt = $pdo->query("
        SELECT 
            u.*,
            COUNT(p.id) as photos_count
        FROM users u
        LEFT JOIN photos p ON u.id = p.user_id
        GROUP BY u.id
        ORDER BY u.created_at DESC
    ");
    
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Обрабатываем данные
    foreach ($users as &$user) {
        $user['photos_count'] = intval($user['photos_count']);
        $user['is_admin'] = (bool)$user['is_admin'];
        
        // Убедимся, что поле role существует
        if (!isset($user['role'])) {
            $user['role'] = 'user';
        }
    }
    
    echo json_encode(['success' => true, 'users' => $users]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
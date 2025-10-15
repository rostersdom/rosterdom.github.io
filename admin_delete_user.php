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

// Проверяем данные
if (!isset($_POST['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Не указан ID пользователя']);
    exit;
}

$user_id = intval($_POST['user_id']);

// Не позволяем удалить самого себя
if ($user_id == $_SESSION['user_id']) {
    echo json_encode(['success' => false, 'message' => 'Нельзя удалить самого себя']);
    exit;
}

try {
    // Начинаем транзакцию
    $pdo->beginTransaction();
    
    // Проверяем, не является ли пользователь администратором
    $stmt = $pdo->prepare("SELECT is_admin FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $target_user = $stmt->fetch();
    
    if ($target_user && $target_user['is_admin']) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Нельзя удалить администратора']);
        exit;
    }
    
    // Удаляем пользователя (каскадное удаление сработает благодаря FOREIGN KEY)
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    
    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Пользователь удален']);
    
} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
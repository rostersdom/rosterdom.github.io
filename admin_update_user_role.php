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
if (!isset($_POST['user_id']) || !isset($_POST['role'])) {
    echo json_encode(['success' => false, 'message' => 'Недостаточно данных']);
    exit;
}

$user_id = intval($_POST['user_id']);
$role = $_POST['role'];

// Проверяем валидность роли
$allowed_roles = ['user', 'vip', 'moderator', 'admin'];
if (!in_array($role, $allowed_roles)) {
    echo json_encode(['success' => false, 'message' => 'Недопустимая роль']);
    exit;
}

try {
    // Не позволяем снять права администратора с самого себя
    if ($user_id == $_SESSION['user_id'] && $role != 'admin') {
        echo json_encode(['success' => false, 'message' => 'Нельзя снять права администратора с самого себя']);
        exit;
    }
    
    // Обновляем роль пользователя
    $stmt = $pdo->prepare("UPDATE users SET role = ?, is_admin = ? WHERE id = ?");
    $is_admin = ($role == 'admin') ? 1 : 0;
    $stmt->execute([$role, $is_admin, $user_id]);
    
    echo json_encode(['success' => true, 'message' => 'Роль пользователя обновлена']);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
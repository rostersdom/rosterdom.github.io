<?php
session_start();
require_once 'config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Необходима авторизация']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Неверный метод запроса']);
    exit;
}

if (!isset($_POST['status'])) {
    echo json_encode(['success' => false, 'message' => 'Статус не указан']);
    exit;
}

$STATUS = trim($_POST['status']);

// Ограничиваем длину статуса
if (strlen($STATUS) > 255) {
    $STATUS = substr($STATUS, 0, 255);
}

// Если статус пустой, устанавливаем значение по умолчанию
if (empty($STATUS)) {
    $STATUS = 'Новый пользователь';
}

try {
    // Проверяем есть ли поле STATUS в таблице
    $check_column = $pdo->query("SHOW COLUMNS FROM users LIKE 'STATUS'");
    $STATUS_column_exists = $check_column->rowCount() > 0;
    
    if (!$STATUS_column_exists) {
        // Создаем поле если его нет
        $pdo->exec("ALTER TABLE users ADD COLUMN STATUS VARCHAR(255) DEFAULT 'Новый пользователь'");
    }
    
    // Обновляем STATUS
    $stmt = $pdo->prepare("UPDATE users SET STATUS = ? WHERE id = ?");
    $result = $stmt->execute([$STATUS, $_SESSION['user_id']]);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Статус обновлен']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Не удалось обновить статус']);
    }
    
} catch (PDOException $e) {
    error_log("Update user STATUS error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Ошибка базы данных']);
}
?>
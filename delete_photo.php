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

if (!isset($_POST['photo_id'])) {
    echo json_encode(['success' => false, 'message' => 'Не указан ID фото']);
    exit;
}

$photo_id = intval($_POST['photo_id']);

try {
    // Получаем информацию о фото и пользователе
    $stmt = $pdo->prepare("
        SELECT p.*, u.id as user_id, u.role, u.is_admin 
        FROM photos p 
        JOIN users u ON p.user_id = u.id 
        WHERE p.id = ?
    ");
    $stmt->execute([$photo_id]);
    $photo = $stmt->fetch();
    
    if (!$photo) {
        echo json_encode(['success' => false, 'message' => 'Фото не найдено']);
        exit;
    }
    
    // Проверяем права: владелец, VIP, модератор или администратор
    $can_delete = false;
    
    if ($_SESSION['user_id'] == $photo['user_id']) {
        // Владелец фото
        $can_delete = true;
    } else {
        // Проверяем роль текущего пользователя
        $stmt = $pdo->prepare("SELECT role, is_admin FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $current_user = $stmt->fetch();
        
        if ($current_user) {
            if ($current_user['is_admin'] || $current_user['role'] == 'moderator') {
                // Администратор или модератор может удалять любые фото
                $can_delete = true;
            } elseif ($current_user['role'] == 'vip' && $_SESSION['user_id'] == $photo['user_id']) {
                // VIP может удалять только свои фото
                $can_delete = true;
            }
        }
    }
    
    if (!$can_delete) {
        echo json_encode(['success' => false, 'message' => 'Недостаточно прав для удаления этого фото']);
        exit;
    }
    
    // Удаляем оценки
    $stmt = $pdo->prepare("DELETE FROM ratings WHERE photo_id = ?");
    $stmt->execute([$photo_id]);
    
    // Удаляем запись о фото
    $stmt = $pdo->prepare("DELETE FROM photos WHERE id = ?");
    $stmt->execute([$photo_id]);
    
    // Удаляем файл
    $file_path = 'uploads/' . $photo['filename'];
    if (file_exists($file_path)) {
        unlink($file_path);
    }
    
    echo json_encode(['success' => true, 'message' => 'Фото успешно удалено']);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
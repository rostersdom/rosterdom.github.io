<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['success' => false, 'message' => 'Не авторизован']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    echo json_encode(['success' => false, 'message' => 'Метод не разрешен']);
    exit;
}

if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Ошибка загрузки файла: ' . $_FILES['avatar']['error']]);
    exit;
}

// Проверяем тип файла
$allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$file_type = $_FILES['avatar']['type'];
if (!in_array($file_type, $allowed_types)) {
    echo json_encode(['success' => false, 'message' => 'Разрешены только JPEG, PNG, GIF и WebP']);
    exit;
}

// Проверяем размер файла (максимум 5MB)
if ($_FILES['avatar']['size'] > 5 * 1024 * 1024) {
    echo json_encode(['success' => false, 'message' => 'Размер файла не должен превышать 5MB']);
    exit;
}

// Создаем папку для аватарок если ее нет
$avatar_dir = 'avatars';
if (!is_dir($avatar_dir)) {
    if (!mkdir($avatar_dir, 0755, true)) {
        echo json_encode(['success' => false, 'message' => 'Не удалось создать папку для аватарок']);
        exit;
    }
}

// Проверяем права на запись в папку
if (!is_writable($avatar_dir)) {
    echo json_encode(['success' => false, 'message' => 'Нет прав на запись в папку аватарок']);
    exit;
}

try {
    $user_id = $_SESSION['user_id'];
    
    // Генерируем уникальное имя файла
    $file_extension = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
    $filename = 'avatar_' . $user_id . '_' . time() . '.' . $file_extension;
    $filepath = $avatar_dir . '/' . $filename;

    // Удаляем старую аватарку если она есть
    $stmt = $pdo->prepare("SELECT avatar FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $old_avatar = $stmt->fetchColumn();
    
    if ($old_avatar && file_exists($avatar_dir . '/' . $old_avatar)) {
        unlink($avatar_dir . '/' . $old_avatar);
    }

    // Перемещаем файл
    if (move_uploaded_file($_FILES['avatar']['tmp_name'], $filepath)) {
        // Обновляем запись в базе данных
        $stmt = $pdo->prepare("UPDATE users SET avatar = ? WHERE id = ?");
        $stmt->execute([$filename, $user_id]);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Аватарка обновлена',
            'avatar_url' => $avatar_dir . '/' . $filename
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Ошибка сохранения файла']);
    }
} catch (PDOException $e) {
    // Удаляем загруженный файл в случае ошибки
    if (isset($filepath) && file_exists($filepath)) {
        unlink($filepath);
    }
    echo json_encode(['success' => false, 'message' => 'Ошибка базы данных: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Ошибка: ' . $e->getMessage()]);
}
?>
<?php
session_start();
require_once 'config/database.php';

// Включаем логирование
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

// Логируем запрос
file_put_contents('update_title_log.txt', date('Y-m-d H:i:s') . " - Update title request\n", FILE_APPEND);

if (!isset($_SESSION['user_id'])) {
    file_put_contents('update_title_log.txt', "User not authorized\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => 'Необходима авторизация']);
    exit;
}

file_put_contents('update_title_log.txt', "User ID: " . $_SESSION['user_id'] . "\n", FILE_APPEND);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    file_put_contents('update_title_log.txt', "Wrong method: " . $_SERVER['REQUEST_METHOD'] . "\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => 'Неверный метод запроса']);
    exit;
}

if (!isset($_POST['photo_id']) || !isset($_POST['title'])) {
    file_put_contents('update_title_log.txt', "Missing data: photo_id=" . ($_POST['photo_id'] ?? 'not set') . ", title=" . ($_POST['title'] ?? 'not set') . "\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => 'Недостаточно данных']);
    exit;
}

$photo_id = intval($_POST['photo_id']);
$title = trim($_POST['title']);

file_put_contents('update_title_log.txt', "Photo ID: " . $photo_id . ", Title: " . $title . "\n", FILE_APPEND);

// Ограничиваем длину названия
if (strlen($title) > 255) {
    $title = substr($title, 0, 255);
}

// Если название пустое, устанавливаем значение по умолчанию
if (empty($title)) {
    $title = 'Без названия';
}

try {
    // Сначала проверяем есть ли поле title в таблице
    $check_column = $pdo->query("SHOW COLUMNS FROM photos LIKE 'title'");
    $title_column_exists = $check_column->rowCount() > 0;
    
    file_put_contents('update_title_log.txt', "Title column exists: " . ($title_column_exists ? 'YES' : 'NO') . "\n", FILE_APPEND);
    
    if (!$title_column_exists) {
        // Если поля нет, создаем его
        file_put_contents('update_title_log.txt', "Creating title column...\n", FILE_APPEND);
        $pdo->exec("ALTER TABLE photos ADD COLUMN title VARCHAR(255) DEFAULT 'Без названия'");
        file_put_contents('update_title_log.txt', "Title column created\n", FILE_APPEND);
    }
    
    // Проверяем, принадлежит ли фото пользователю
    $check_stmt = $pdo->prepare("SELECT user_id FROM photos WHERE id = ?");
    $check_stmt->execute([$photo_id]);
    $photo = $check_stmt->fetch();
    
    file_put_contents('update_title_log.txt', "Photo found: " . ($photo ? 'YES' : 'NO') . "\n", FILE_APPEND);
    
    if (!$photo) {
        file_put_contents('update_title_log.txt', "Photo not found\n", FILE_APPEND);
        echo json_encode(['success' => false, 'message' => 'Фото не найдено']);
        exit;
    }
    
    file_put_contents('update_title_log.txt', "Photo user ID: " . $photo['user_id'] . ", Current user ID: " . $_SESSION['user_id'] . "\n", FILE_APPEND);
    
    if ($photo['user_id'] != $_SESSION['user_id']) {
        file_put_contents('update_title_log.txt', "User doesn't own this photo\n", FILE_APPEND);
        echo json_encode(['success' => false, 'message' => 'Нет прав для редактирования этого фото']);
        exit;
    }
    
    // Обновляем название
    file_put_contents('update_title_log.txt', "Updating title...\n", FILE_APPEND);
    $update_stmt = $pdo->prepare("UPDATE photos SET title = ? WHERE id = ?");
    $result = $update_stmt->execute([$title, $photo_id]);
    
    file_put_contents('update_title_log.txt', "Update result: " . ($result ? 'SUCCESS' : 'FAILED') . "\n", FILE_APPEND);
    file_put_contents('update_title_log.txt', "Rows affected: " . $update_stmt->rowCount() . "\n", FILE_APPEND);
    
    if ($result && $update_stmt->rowCount() > 0) {
        file_put_contents('update_title_log.txt', "Title updated successfully\n", FILE_APPEND);
        echo json_encode(['success' => true, 'message' => 'Название обновлено']);
    } else {
        file_put_contents('update_title_log.txt', "No rows affected\n", FILE_APPEND);
        echo json_encode(['success' => false, 'message' => 'Не удалось обновить название']);
    }
    
} catch (PDOException $e) {
    $error_msg = "Update photo title error: " . $e->getMessage();
    file_put_contents('update_title_log.txt', $error_msg . "\n", FILE_APPEND);
    error_log($error_msg);
    echo json_encode(['success' => false, 'message' => 'Ошибка базы данных: ' . $e->getMessage()]);
}

file_put_contents('update_title_log.txt', "Request completed\n\n", FILE_APPEND);
?>
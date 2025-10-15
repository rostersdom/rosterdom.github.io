<?php
session_start();
require_once 'config/database.php';

// Если нет сессии, создаем тестового пользователя
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 999;
    $_SESSION['user_name'] = 'Test User';
}

echo "<h2>Тестовая загрузка</h2>";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['test_photo'])) {
    $upload_dir = 'uploads';
    
    // Создаем папку если нет
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $file = $_FILES['test_photo'];
    
    echo "Информация о файле:<br>";
    echo "Имя: " . $file['name'] . "<br>";
    echo "Размер: " . $file['size'] . "<br>";
    echo "Тип: " . $file['type'] . "<br>";
    echo "Временный файл: " . $file['tmp_name'] . "<br>";
    echo "Ошибка: " . $file['error'] . "<br>";
    
    // Проверяем ошибки
    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo "✗ Ошибка загрузки: " . $file['error'] . "<br>";
        exit;
    }
    
    // Проверяем тип файла
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $file_type = mime_content_type($file['tmp_name']);
    
    if (!in_array($file_type, $allowed_types)) {
        echo "✗ Неподдерживаемый тип файла: " . $file_type . "<br>";
        exit;
    }
    
    // Генерируем имя файла
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'test_' . time() . '.' . $extension;
    $upload_path = $upload_dir . '/' . $filename;
    
    echo "Путь для сохранения: " . $upload_path . "<br>";
    
    // Пытаемся переместить файл
    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        echo "✓ Файл перемещен успешно<br>";
        
        // Пытаемся сохранить в БД
        try {
            $stmt = $pdo->prepare("INSERT INTO photos (user_id, filename, original_name) VALUES (?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $filename, $file['name']]);
            echo "✓ Файл сохранен в базу данных<br>";
            echo "✓ ID новой записи: " . $pdo->lastInsertId() . "<br>";
        } catch (PDOException $e) {
            echo "✗ Ошибка базы данных: " . $e->getMessage() . "<br>";
        }
        
    } else {
        echo "✗ Ошибка перемещения файла<br>";
        echo "✓ Права на запись в папку: " . (is_writable($upload_dir) ? 'Да' : 'Нет') . "<br>";
    }
    
    echo "<br><a href='debug_upload.php'>Вернуться к диагностике</a>";
} else {
    echo "Файл не получен";
}
?>
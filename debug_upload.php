<?php
session_start();
require_once 'config/database.php';

// Автоматически создаем таблицы если их нет
createTablesIfNotExist($pdo);

echo "<h2>Диагностика загрузки фотографий</h2>";

// Проверка сессии
echo "<h3>1. Проверка сессии:</h3>";
if (isset($_SESSION['user_id'])) {
    echo "✓ Пользователь авторизован: ID=" . $_SESSION['user_id'] . "<br>";
} else {
    echo "✗ Пользователь не авторизован<br>";
    // Создаем тестовую сессию для отладки
    $_SESSION['user_id'] = 999;
    $_SESSION['user_name'] = 'Test User';
    echo "✓ Создана тестовая сессия<br>";
}
// ... остальной код без изменений
?>
<?php
session_start();
require_once 'config/database.php';

echo "<h2>Диагностика загрузки фотографий</h2>";

// Проверка сессии
echo "<h3>1. Проверка сессии:</h3>";
if (isset($_SESSION['user_id'])) {
    echo "✓ Пользователь авторизован: ID=" . $_SESSION['user_id'] . "<br>";
} else {
    echo "✗ Пользователь не авторизован<br>";
}

// Проверка папки uploads
echo "<h3>2. Проверка папки uploads:</h3>";
$upload_dir = 'uploads';
if (file_exists($upload_dir)) {
    echo "✓ Папка существует<br>";
    echo "✓ Права на чтение: " . (is_readable($upload_dir) ? 'Да' : 'Нет') . "<br>";
    echo "✓ Права на запись: " . (is_writable($upload_dir) ? 'Да' : 'Нет') . "<br>";
    echo "✓ Права: " . substr(sprintf('%o', fileperms($upload_dir)), -4) . "<br>";
} else {
    echo "✗ Папка не существует. Пытаемся создать...<br>";
    if (mkdir($upload_dir, 0755, true)) {
        echo "✓ Папка создана<br>";
    } else {
        echo "✗ Не удалось создать папку<br>";
    }
}

// Проверка базы данных
echo "<h3>3. Проверка базы данных:</h3>";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM photos");
    $result = $stmt->fetch();
    echo "✓ База данных подключена. Фото в базе: " . $result['count'] . "<br>";
} catch (PDOException $e) {
    echo "✗ Ошибка базы данных: " . $e->getMessage() . "<br>";
}

// Проверка таблицы photos
echo "<h3>4. Проверка таблицы photos:</h3>";
try {
    $stmt = $pdo->query("DESCRIBE photos");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "✓ Таблица photos существует. Колонки: " . implode(', ', $columns) . "<br>";
} catch (PDOException $e) {
    echo "✗ Ошибка таблицы photos: " . $e->getMessage() . "<br>";
}

// Простая форма для тестирования загрузки
echo "<h3>5. Тестовая форма загрузки:</h3>";
?>
<form action="test_upload.php" method="post" enctype="multipart/form-data">
    <input type="file" name="test_photo" accept="image/*" required>
    <button type="submit">Тестовая загрузка</button>
</form>
<?php

// Показываем последние загруженные фото
echo "<h3>6. Последние загруженные фото:</h3>";
try {
    $stmt = $pdo->query("SELECT * FROM photos ORDER BY upload_date DESC LIMIT 5");
    $photos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($photos) {
        foreach ($photos as $photo) {
            echo "Фото: " . $photo['filename'] . " | Пользователь: " . $photo['user_id'] . " | Дата: " . $photo['upload_date'] . "<br>";
            echo "<img src='uploads/" . $photo['filename'] . "' style='max-width: 200px; margin: 5px;'><br>";
        }
    } else {
        echo "Нет загруженных фото<br>";
    }
} catch (PDOException $e) {
    echo "Ошибка: " . $e->getMessage() . "<br>";
}
?>
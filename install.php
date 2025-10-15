<?php
require_once 'config/database.php';

echo "<h1>Установка Roster App</h1>";

// Проверяем существование базы данных
try {
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "<h3>Существующие таблицы:</h3>";
    if ($tables) {
        echo "<ul>";
        foreach ($tables as $table) {
            echo "<li>$table</li>";
        }
        echo "</ul>";
    } else {
        echo "Таблиц нет<br>";
    }
} catch (PDOException $e) {
    echo "Ошибка: " . $e->getMessage() . "<br>";
}

// Создаем таблицы
$queries = [
    "users" => "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        login VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB",
    
    "photos" => "CREATE TABLE IF NOT EXISTS photos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        filename VARCHAR(255) NOT NULL,
        original_name VARCHAR(255) NOT NULL,
        upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB",
    
    "ratings" => "CREATE TABLE IF NOT EXISTS ratings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        photo_id INT,
        rating INT NOT NULL CHECK (rating >= 1 AND rating <= 10),
        rated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (photo_id) REFERENCES photos(id) ON DELETE CASCADE,
        UNIQUE KEY unique_rating (user_id, photo_id)
    ) ENGINE=InnoDB"
];

echo "<h3>Создание таблиц:</h3>";
foreach ($queries as $table => $sql) {
    echo "Создание таблицы <strong>$table</strong>: ";
    try {
        $pdo->exec($sql);
        echo "✓ Успешно<br>";
    } catch (PDOException $e) {
        echo "✗ Ошибка: " . $e->getMessage() . "<br>";
    }
}

// Создаем тестового пользователя
echo "<h3>Создание тестового пользователя:</h3>";
try {
    $stmt = $pdo->prepare("INSERT IGNORE INTO users (id, name, login, password) VALUES (999, 'Test User', 'test', ?)");
    $hashed_password = password_hash('test123', PASSWORD_DEFAULT);
    $stmt->execute([$hashed_password]);
    echo "✓ Тестовый пользователь создан (логин: test, пароль: test123)<br>";
} catch (PDOException $e) {
    echo "✗ Ошибка пользователя: " . $e->getMessage() . "<br>";
}

// Проверяем создание папки uploads
echo "<h3>Проверка папки uploads:</h3>";
$upload_dir = 'uploads';
if (!file_exists($upload_dir)) {
    if (mkdir($upload_dir, 0755, true)) {
        echo "✓ Папка uploads создана<br>";
    } else {
        echo "✗ Не удалось создать папку uploads<br>";
    }
} else {
    echo "✓ Папка uploads существует<br>";
    echo "✓ Права на запись: " . (is_writable($upload_dir) ? 'Да' : 'Нет') . "<br>";
}

echo "<h3 style='color: green;'>Установка завершена!</h3>";
echo "<p><a href='index.php'>Перейти к приложению</a></p>";
echo "<p><a href='debug_upload.php'>Провести диагностику</a></p>";
?>
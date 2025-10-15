<?php
require_once 'config/database.php';

echo "<h2>Создание таблиц в базе данных</h2>";

$queries = [
    "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        login VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )" => "Таблица users",
    
    "CREATE TABLE IF NOT EXISTS photos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        filename VARCHAR(255) NOT NULL,
        original_name VARCHAR(255) NOT NULL,
        upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )" => "Таблица photos",
    
    "CREATE TABLE IF NOT EXISTS ratings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        photo_id INT,
        rating INT NOT NULL CHECK (rating >= 1 AND rating <= 10),
        rated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (photo_id) REFERENCES photos(id) ON DELETE CASCADE,
        UNIQUE KEY unique_rating (user_id, photo_id)
    )" => "Таблица ratings"
];

foreach ($queries as $sql => $description) {
    echo "<p><strong>$description:</strong> ";
    try {
        $pdo->exec($sql);
        echo "✓ Успешно создана</p>";
    } catch (PDOException $e) {
        echo "✗ Ошибка: " . $e->getMessage() . "</p>";
    }
}

echo "<h3>Проверка таблиц:</h3>";
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
echo "Существующие таблицы: " . implode(', ', $tables);

echo "<h3>Добавление тестовых данных:</h3>";

// Добавляем тестового пользователя если нет
try {
    $stmt = $pdo->prepare("INSERT IGNORE INTO users (id, name, login, password) VALUES (999, 'Test User', 'test', 'test')");
    $stmt->execute();
    echo "✓ Тестовый пользователь добавлен<br>";
} catch (PDOException $e) {
    echo "✗ Ошибка пользователя: " . $e->getMessage() . "<br>";
}

echo "<br><a href='debug_upload.php'>Вернуться к диагностике</a>";
?>
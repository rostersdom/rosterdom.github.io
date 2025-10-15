<?php
require_once 'config/database.php';

echo "<h2>Проверка и обновление базы данных для профилей</h2>";

try {
    // Проверяем наличие поля created_at
    $check_created = $pdo->query("SHOW COLUMNS FROM users LIKE 'created_at'");
    $created_exists = $check_created->rowCount() > 0;
    
    if (!$created_exists) {
        $pdo->exec("ALTER TABLE users ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
        echo "✓ Поле created_at добавлено в таблицу users<br>";
    } else {
        echo "✓ Поле created_at уже существует<br>";
    }
    
    // Проверяем наличие поля STATUS
    $check_status = $pdo->query("SHOW COLUMNS FROM users LIKE 'STATUS'");
    $status_exists = $check_status->rowCount() > 0;
    
    if (!$status_exists) {
        $pdo->exec("ALTER TABLE users ADD COLUMN STATUS VARCHAR(255) DEFAULT 'Новый пользователь'");
        echo "✓ Поле STATUS добавлено в таблицу users<br>";
    } else {
        echo "✓ Поле STATUS уже существует<br>";
    }
    
    echo "<h3 style='color: green;'>База данных готова для работы с профилями!</h3>";
    echo "<p><a href='index.php'>Вернуться к приложению</a></p>";
    
} catch (PDOException $e) {
    echo "✗ Ошибка: " . $e->getMessage() . "<br>";
}
?>
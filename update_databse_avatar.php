<?php
require_once 'config/database.php';

echo "<h2>Добавление поля avatar в базу данных</h2>";

try {
    // Добавляем поле avatar если его нет
    $pdo->exec("ALTER TABLE users ADD COLUMN avatar VARCHAR(255) DEFAULT NULL");
    echo "✓ Поле avatar добавлено в таблицу users<br>";
    
    echo "<h3 style='color: green;'>База данных успешно обновлена!</h3>";
    echo "<p><a href='index.php'>Вернуться к приложению</a></p>";
    
} catch (PDOException $e) {
    echo "✗ Ошибка: " . $e->getMessage() . "<br>";
}
?>
<?php
require_once 'config/database.php';

echo "<h2>Обновление базы данных</h2>";

try {
    // Добавляем поле is_admin если его нет
    $pdo->exec("ALTER TABLE users ADD COLUMN is_admin BOOLEAN DEFAULT FALSE");
    echo "✓ Поле is_admin добавлено в таблицу users<br>";
    
    // Сделаем тестового пользователя админом
    $stmt = $pdo->prepare("UPDATE users SET is_admin = TRUE WHERE id = 999");
    $stmt->execute();
    echo "✓ Пользователь с ID 999 назначен администратором<br>";
    
    echo "<h3 style='color: green;'>База данных успешно обновлена!</h3>";
    echo "<p><a href='index.php'>Вернуться к приложению</a></p>";
    
} catch (PDOException $e) {
    echo "✗ Ошибка: " . $e->getMessage() . "<br>";
}
?>
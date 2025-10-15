<?php
session_start();
require_once 'config/database.php';

echo "<h2>Диагностика топа фотографий</h2>";

// Проверяем данные в базе
echo "<h3>1. Проверка данных в базе:</h3>";

// Фото
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM photos");
    $result = $stmt->fetch();
    echo "Всего фото: " . $result['count'] . "<br>";
} catch (PDOException $e) {
    echo "Ошибка: " . $e->getMessage() . "<br>";
}

// Рейтинги
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM ratings");
    $result = $stmt->fetch();
    echo "Всего оценок: " . $result['count'] . "<br>";
} catch (PDOException $e) {
    echo "Ошибка: " . $e->getMessage() . "<br>";
}

// Топ фото
echo "<h3>2. Топ фото (SQL запрос):</h3>";
try {
    $stmt = $pdo->query("
        SELECT 
            p.*, 
            u.name as user_name,
            AVG(r.rating) as average_rating,
            COUNT(r.id) as vote_count
        FROM photos p
        LEFT JOIN users u ON p.user_id = u.id
        LEFT JOIN ratings r ON p.id = r.photo_id
        GROUP BY p.id
        HAVING COUNT(r.id) > 0
        ORDER BY average_rating DESC, vote_count DESC
        LIMIT 10
    ");
    
    $photos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($photos) {
        foreach ($photos as $photo) {
            echo "Фото: " . $photo['filename'] . " | Рейтинг: " . ($photo['average_rating'] ? round($photo['average_rating'], 1) : 'нет') . " | Голосов: " . $photo['vote_count'] . " | Пользователь: " . $photo['user_name'] . "<br>";
            echo "<img src='uploads/" . $photo['filename'] . "' style='max-width: 200px; margin: 5px;'><br>";
        }
    } else {
        echo "Нет фото с оценками<br>";
    }
} catch (PDOException $e) {
    echo "Ошибка: " . $e->getMessage() . "<br>";
}

echo "<h3>3. Тест API get_top_photos.php:</h3>";
$test_url = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . "/get_top_photos.php";
echo "<a href='$test_url' target='_blank'>Открыть get_top_photos.php</a>";

echo "<br><br><a href='index.php'>Вернуться к приложению</a>";
?>
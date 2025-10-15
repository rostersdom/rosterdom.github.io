<?php
session_start();
require_once 'config/database.php';

header('Content-Type: application/json');

try {
    // Получаем топ фото с информацией о ролях авторов
    $sql = "SELECT p.*, 
                   u.name as user_name, 
                   u.avatar as user_avatar,
                   u.role as user_role,
                   u.is_vip as user_is_vip,
                   u.is_moderator as user_is_moderator,
                   u.is_admin as user_is_admin,
                   COALESCE(AVG(r.rating), 0) as average_rating,
                   COUNT(r.id) as vote_count
            FROM photos p
            LEFT JOIN users u ON p.user_id = u.id
            LEFT JOIN ratings r ON p.id = r.photo_id
            GROUP BY p.id, u.id
            HAVING COUNT(r.id) > 0
            ORDER BY average_rating DESC, vote_count DESC
            LIMIT 50";
    
    $stmt = $pdo->query($sql);
    $photos = $stmt->fetchAll();
    
    $result = [];
    foreach ($photos as $photo) {
        // Определяем роль автора для отображения
        $author_role = 'user';
        if ($photo['user_is_admin']) {
            $author_role = 'admin';
        } elseif ($photo['user_is_moderator']) {
            $author_role = 'moderator';
        } elseif ($photo['user_is_vip']) {
            $author_role = 'vip';
        }
        
        $result[] = [
            'id' => $photo['id'],
            'filename' => $photo['filename'],
            'title' => $photo['title'],
            'user_name' => $photo['user_name'],
            'user_avatar' => $photo['user_avatar'],
            'user_role' => $author_role,
            'average_rating' => round(floatval($photo['average_rating']), 1),
            'vote_count' => intval($photo['vote_count']),
            'upload_date' => $photo['upload_date']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'photos' => $result
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Ошибка загрузки топа фото: ' . $e->getMessage()
    ]);
}
?>
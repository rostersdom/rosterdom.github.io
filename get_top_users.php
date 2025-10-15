<?php
session_start();
require_once 'config/database.php';

header('Content-Type: application/json');

try {
    // Получаем топ пользователей с информацией о ролях
    $sql = "SELECT u.id, u.name, u.avatar, u.role, u.is_vip, u.is_moderator, u.is_admin,
                   COALESCE(AVG(r.rating), 0) as avg_rating,
                   COUNT(DISTINCT p.id) as photos_count
            FROM users u 
            LEFT JOIN photos p ON u.id = p.user_id 
            LEFT JOIN ratings r ON p.id = r.photo_id 
            GROUP BY u.id, u.name, u.avatar, u.role, u.is_vip, u.is_moderator, u.is_admin
            HAVING COUNT(p.id) > 0
            ORDER BY avg_rating DESC, photos_count DESC 
            LIMIT 50";
    
    $stmt = $pdo->query($sql);
    $users = $stmt->fetchAll();
    
    $result = [];
    foreach ($users as $user) {
        // Определяем роль пользователя для отображения
        $display_role = 'user';
        if ($user['is_admin']) {
            $display_role = 'admin';
        } elseif ($user['is_moderator']) {
            $display_role = 'moderator';
        } elseif ($user['is_vip']) {
            $display_role = 'vip';
        }
        
        $result[] = [
            'id' => $user['id'],
            'name' => $user['name'],
            'avatar' => $user['avatar'],
            'role' => $display_role,
            'avg_rating' => round(floatval($user['avg_rating']), 1),
            'photos_count' => intval($user['photos_count']),
            'initial' => strtoupper(mb_substr($user['name'], 0, 1, 'UTF-8'))
        ];
    }
    
    echo json_encode([
        'success' => true,
        'users' => $result
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Ошибка загрузки топа пользователей: ' . $e->getMessage()
    ]);
}
?>
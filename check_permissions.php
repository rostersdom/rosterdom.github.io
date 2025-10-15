<?php
function requireModeratorAccess() {
    global $pdo;
    
    if (!isset($_SESSION['user_id'])) {
        http_response_code(403);
        die('Доступ запрещен');
    }
    
    $permissions = getUserPermissions($pdo, $_SESSION['user_id']);
    if (!$permissions['can_moderate']) {
        http_response_code(403);
        die('Недостаточно прав');
    }
}

function requireVIPAccess() {
    global $pdo;
    
    if (!isset($_SESSION['user_id'])) {
        http_response_code(403);
        die('Доступ запрещен');
    }
    
    $permissions = getUserPermissions($pdo, $_SESSION['user_id']);
    if (!$permissions['is_vip']) {
        http_response_code(403);
        die('Требуется VIP статус');
    }
}
?>
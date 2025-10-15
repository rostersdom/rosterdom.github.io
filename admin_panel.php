<?php
session_start();
require_once 'config/database.php';

// Проверяем права администратора
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT is_admin FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if (!$user || !$user['is_admin']) {
        header('Location: index.php');
        exit;
    }
} catch (PDOException $e) {
    die('Ошибка проверки прав: ' . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель администратора - Roster</title>
    <style>
        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .admin-header {
            background: linear-gradient(135deg, #ff4b7d 0%, #6a5af9 100%);
            color: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 30px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #ff4b7d;
            margin-bottom: 10px;
        }
        
        .photo-list {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .photo-item {
            display: flex;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #eee;
            gap: 15px;
        }
        
        .photo-item:last-child {
            border-bottom: none;
        }
        
        .photo-thumb {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
        }
        
        .photo-info {
            flex: 1;
        }
        
        .delete-btn {
            background: #f44336;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .delete-btn:hover {
            background: #d32f2f;
        }
        
        .user-badge {
            background: #2196F3;
            color: white;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
        }
        
        .admin-badge {
            background: #ff4b7d;
            color: white;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1>👑 Панель администратора</h1>
            <p>Управление фотографиями и пользователями</p>
            <a href="index.php" style="color: white; text-decoration: underline;">← Вернуться к приложению</a>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number" id="total-photos">0</div>
                <div>Всего фото</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="total-users">0</div>
                <div>Всего пользователей</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="total-ratings">0</div>
                <div>Всего оценок</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" id="total-admins">0</div>
                <div>Администраторов</div>
            </div>
        </div>
        
        <div class="photo-list">
            <h2>Управление фотографиями</h2>
            <div id="photos-container">
                <!-- Список фото будет здесь -->
            </div>
        </div>
    </div>

    <script>
        // Загрузка статистики
        async function loadStats() {
            try {
                const response = await fetch('admin_stats.php');
                const data = await response.json();
                
                if (data.success) {
                    document.getElementById('total-photos').textContent = data.stats.total_photos;
                    document.getElementById('total-users').textContent = data.stats.total_users;
                    document.getElementById('total-ratings').textContent = data.stats.total_ratings;
                    document.getElementById('total-admins').textContent = data.stats.total_admins;
                }
            } catch (error) {
                console.error('Ошибка загрузки статистики:', error);
            }
        }
        
        // Загрузка списка фото
        async function loadPhotos() {
            try {
                const response = await fetch('admin_get_photos.php');
                const data = await response.json();
                
                const container = document.getElementById('photos-container');
                
                if (data.success && data.photos.length > 0) {
                    container.innerHTML = data.photos.map(photo => `
                        <div class="photo-item" data-photo-id="${photo.id}">
                            <img src="uploads/${photo.filename}" alt="Фото" class="photo-thumb" 
                                 onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZGRkIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtZmFtaWx5PSJBcmlhbCIgZm9udC1zaXplPSIxMiIgZmlsbD0iIzk5OSIgdGV4dC1hbmNob3I9Im1pZGRsZSIgZHk9Ii4zZW0iPk5ldCDRgdC10LrRg9C90LQ8L3RleHQ+PC9zdmc+'">
                            <div class="photo-info">
                                <div><strong>ID:</strong> ${photo.id}</div>
                                <div><strong>Пользователь:</strong> 
                                    <span class="${photo.user_is_admin ? 'admin-badge' : 'user-badge'}">
                                        ${photo.user_name} ${photo.user_is_admin ? '👑' : ''}
                                    </span>
                                </div>
                                <div><strong>Загружено:</strong> ${new Date(photo.upload_date).toLocaleString()}</div>
                                <div><strong>Рейтинг:</strong> ${photo.average_rating ? photo.average_rating.toFixed(1) : 'Нет'} (${photo.vote_count} голосов)</div>
                            </div>
                            <button class="delete-btn" onclick="deletePhoto(${photo.id}, this)">
                                🗑️ Удалить
                            </button>
                        </div>
                    `).join('');
                } else {
                    container.innerHTML = '<p style="text-align: center; padding: 40px; color: #777;">Нет загруженных фото</p>';
                }
            } catch (error) {
                console.error('Ошибка загрузки фото:', error);
                document.getElementById('photos-container').innerHTML = '<p style="text-align: center; color: red;">Ошибка загрузки</p>';
            }
        }
        
        // Удаление фото
        async function deletePhoto(photoId, button) {
            if (!confirm('Вы уверены, что хотите удалить это фото?')) {
                return;
            }
            
            button.disabled = true;
            button.textContent = 'Удаление...';
            
            try {
                const response = await fetch('delete_photo.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `photo_id=${photoId}`
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Удаляем элемент из списка
                    const photoItem = button.closest('.photo-item');
                    photoItem.style.opacity = '0.5';
                    setTimeout(() => photoItem.remove(), 500);
                    
                    // Обновляем статистику
                    loadStats();
                    
                    alert('Фото успешно удалено');
                } else {
                    alert('Ошибка: ' + data.message);
                    button.disabled = false;
                    button.textContent = '🗑️ Удалить';
                }
            } catch (error) {
                console.error('Ошибка удаления:', error);
                alert('Произошла ошибка при удалении');
                button.disabled = false;
                button.textContent = '🗑️ Удалить';
            }
        }
        
        // Инициализация
        loadStats();
        loadPhotos();
        
        // Обновляем каждые 30 секунд
        setInterval(loadStats, 30000);
    </script>
    <div class="photo-list" style="margin-top: 30px;">
    <h2>Управление пользователями</h2>
    <div id="users-container">
        <!-- Список пользователей будет здесь -->
    </div>
</div>

<script>
// Загрузка списка пользователей
async function loadUsers() {
    try {
        const response = await fetch('admin_get_users.php');
        const data = await response.json();
        
        const container = document.getElementById('users-container');
        
        if (data.success && data.users.length > 0) {
            container.innerHTML = data.users.map(user => `
                <div class="photo-item">
                    <div style="width: 50px; height: 50px; border-radius: 50%; background: #667eea; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">
                        ${user.name.charAt(0).toUpperCase()}
                    </div>
                    <div class="photo-info">
                        <div><strong>ID:</strong> ${user.id}</div>
                        <div><strong>Имя:</strong> ${user.name}</div>
                        <div><strong>Логин:</strong> ${user.login}</div>
                        <div><strong>Зарегистрирован:</strong> ${new Date(user.created_at).toLocaleDateString()}</div>
                        <div><strong>Статус:</strong> 
                            <span class="user-role-badge role-${user.role}">${getRoleName(user.role)}</span>
                            ${user.is_admin ? ' <span class="admin-badge">Админ</span>' : ''}
                        </div>
                    </div>
                    <div>
                        <select class="role-select" data-user-id="${user.id}" style="padding: 8px; border-radius: 5px; border: 1px solid #ddd; margin-right: 10px;">
                            <option value="user" ${user.role === 'user' ? 'selected' : ''}>Обычный</option>
                            <option value="vip" ${user.role === 'vip' ? 'selected' : ''}>VIP</option>
                            <option value="moderator" ${user.role === 'moderator' ? 'selected' : ''}>Модератор</option>
                            ${user.is_admin ? '<option value="admin" selected>Админ</option>' : ''}
                        </select>
                        <button class="delete-btn" onclick="deleteUser(${user.id}, this)" ${user.is_admin ? 'disabled style="opacity: 0.5;"' : ''}>
                            🗑️ Удалить
                        </button>
                    </div>
                </div>
            `).join('');
            
            // Добавляем обработчики изменения ролей
            document.querySelectorAll('.role-select').forEach(select => {
                select.addEventListener('change', function() {
                    updateUserRole(this.getAttribute('data-user-id'), this.value);
                });
            });
        } else {
            container.innerHTML = '<p style="text-align: center; padding: 40px; color: #777;">Нет пользователей</p>';
        }
    } catch (error) {
        console.error('Ошибка загрузки пользователей:', error);
        document.getElementById('users-container').innerHTML = '<p style="text-align: center; color: red;">Ошибка загрузки</p>';
    }
}

function getRoleName(role) {
    const roles = {
        'user': 'Обычный',
        'vip': 'VIP',
        'moderator': 'Модератор',
        'admin': 'Администратор'
    };
    return roles[role] || role;
}

// Обновление роли пользователя
async function updateUserRole(userId, newRole) {
    try {
        const response = await fetch('admin_update_user_role.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `user_id=${userId}&role=${newRole}`
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert('Роль пользователя успешно обновлена');
            loadUsers(); // Перезагружаем список
        } else {
            alert('Ошибка: ' + data.message);
            loadUsers(); // Перезагружаем чтобы откатить изменения
        }
    } catch (error) {
        console.error('Ошибка обновления роли:', error);
        alert('Произошла ошибка при обновлении роли');
        loadUsers();
    }
}

// Удаление пользователя
async function deleteUser(userId, button) {
    if (!confirm('Вы уверены, что хотите удалить этого пользователя? Все его фото также будут удалены.')) {
        return;
    }
    
    button.disabled = true;
    button.textContent = 'Удаление...';
    
    try {
        const response = await fetch('admin_delete_user.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `user_id=${userId}`
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Удаляем элемент из списка
            const userItem = button.closest('.photo-item');
            userItem.style.opacity = '0.5';
            setTimeout(() => userItem.remove(), 500);
            
            // Обновляем статистику
            loadStats();
            
            alert('Пользователь успешно удален');
        } else {
            alert('Ошибка: ' + data.message);
            button.disabled = false;
            button.textContent = '🗑️ Удалить';
        }
    } catch (error) {
        console.error('Ошибка удаления:', error);
        alert('Произошла ошибка при удалении');
        button.disabled = false;
        button.textContent = '🗑️ Удалить';
    }
}

// Инициализация
loadUsers();
</script>

<style>
.user-role-badge {
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 0.8rem;
    color: white;
}

.role-user { background: #2196F3; }
.role-vip { background: #FF9800; }
.role-moderator { background: #9C27B0; }
.role-admin { background: #f44336; }
</style>
</body>
</html>
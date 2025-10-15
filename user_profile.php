<?php
session_start();
require_once 'config/database.php';

// Заголовки против кэширования
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die('Неверный ID пользователя');
}

$user_id = intval($_GET['id']);

// Получаем данные пользователя
try {
    $stmt = $pdo->prepare("
        SELECT u.*, 
               COUNT(DISTINCT p.id) as photos_count,
               COALESCE(AVG(r.rating), 0) as avg_rating,
               COUNT(r.id) as ratings_received
        FROM users u 
        LEFT JOIN photos p ON u.id = p.user_id 
        LEFT JOIN ratings r ON p.id = r.photo_id 
        WHERE u.id = ?
        GROUP BY u.id
    ");
    
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        die('Пользователь не найден');
    }
    
    // Убедимся, что поле STATUS существует в массиве $user
    if (!isset($user['STATUS'])) {
        $user['STATUS'] = 'Новый пользователь';
    }
    
} catch (PDOException $e) {
    die('Ошибка базы данных: ' . $e->getMessage());
}

// Получаем права текущего пользователя
$current_user_permissions = ['can_moderate' => false, 'can_delete' => false, 'is_vip' => false];
if (isset($_SESSION['user_id'])) {
    $current_user_permissions = getUserPermissions($pdo, $_SESSION['user_id']);
}

$can_edit_profile = false;
if (isset($_SESSION['user_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT role, is_admin FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $current_user = $stmt->fetch();
        
        if ($current_user) {
            // Модераторы и администраторы могут редактировать чужие профили
            if ($current_user['is_admin'] || $current_user['role'] == 'moderator') {
                $can_edit_profile = true;
            }
            // Владелец профиля может редактировать свой профиль
            elseif ($_SESSION['user_id'] == $user_id) {
                $can_edit_profile = true;
            }
        }
    } catch (PDOException $e) {
        // В случае ошибки просто не показываем возможности редактирования
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Профиль <?= htmlspecialchars($user['name']) ?> - Roster</title>
    <link rel="stylesheet" href="style.css?v=1.5">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="container">
        <header>
            <div class="logo">
                <a href="index.php" style="display: flex; align-items: center; gap: 10px; text-decoration: none; color: inherit;">
                    <i class="fas fa-heart"></i>
                    <span>Roster</span>
                </a>
            </div>
            <div class="user-header-main">
                <button onclick="history.back()" class="btn btn-outline">
                    <i class="fas fa-arrow-left"></i> Назад
                </button>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="profile.php" class="btn btn-outline">Мой профиль</a>
                <?php endif; ?>
            </div>
        </header>

        <div class="profile-section">
            <div class="profile-header">
                <div class="profile-avatar-container">
                    <?php if ($user['avatar']): ?>
                    <img src="avatars/<?= htmlspecialchars($user['avatar']) ?>?t=<?= time() ?>" alt="Аватар" class="profile-avatar-img">
                    <?php else: ?>
                    <div class="profile-avatar">
                        <?= strtoupper(substr($user['name'], 0, 1)) ?>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="profile-info">
                    <h2><?= htmlspecialchars($user['name']) ?></h2>
                    <p>Зарегистрирован: <?= date('d.m.Y', strtotime($user['created_at'])) ?></p>
                    
                    <!-- Блок статуса -->
                    <?php if ($can_edit_profile): ?>
                    <div class="user-status">
                        <div class="status-editable" id="status-display">
                            <span class="status-text"><?= htmlspecialchars($user['STATUS']) ?></span>
                            <button class="status-edit-btn" id="edit-status-btn">
                                <i class="fas fa-edit"></i> Изменить
                            </button>
                        </div>
                        
                        <form class="status-edit-form" id="status-edit-form">
                            <input type="text" class="status-edit-input" id="status-input" 
                                   value="<?= htmlspecialchars($user['STATUS']) ?>" 
                                   placeholder="Введите ваш статус" maxlength="255">
                            <div class="status-edit-actions">
                                <button type="submit" class="save-status-btn">Сохранить</button>
                                <button type="button" class="cancel-status-btn">Отмена</button>
                            </div>
                        </form>
                    </div>
                    <?php else: ?>
                    <div class="user-status">
                        <div class="status-display">
                            <span class="status-text"><?= htmlspecialchars($user['STATUS']) ?></span>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Бейджи ролей -->
                    <div style="margin-top: 10px;">
                        <?php if ($user['is_admin']): ?>
                        <div class="admin-badge-profile">
                            <i class="fas fa-crown"></i> Администратор
                        </div>
                        <?php elseif ($user['role'] == 'moderator'): ?>
                        <div class="user-role-badge role-moderator">
                            <i class="fas fa-shield-alt"></i> Модератор
                        </div>
                        <?php elseif ($user['role'] == 'vip'): ?>
                        <div class="user-role-badge role-vip">
                            <i class="fas fa-crown"></i> VIP
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="profile-stats">
                <div class="profile-stat">
                    <div class="stat-value"><?= number_format($user['avg_rating'], 1) ?></div>
                    <div class="stat-label">Средний рейтинг</div>
                </div>
                <div class="profile-stat">
                    <div class="stat-value"><?= $user['ratings_received'] ?></div>
                    <div class="stat-label">Оценок получено</div>
                </div>
                <div class="profile-stat">
                    <div class="stat-value"><?= $user['photos_count'] ?></div>
                    <div class="stat-label">Фотографий</div>
                </div>
            </div>

            <!-- Панель модератора -->
            <?php if ($current_user_permissions['can_moderate']): ?>
            <div style="margin-top: 20px; padding: 15px; background: #fff3cd; border-radius: 8px; border: 1px solid #ffeaa7;">
                <h4 style="margin-bottom: 10px; color: #856404;">⚡ Панель модератора</h4>
                <button class="btn btn-danger" onclick="deleteAllUserPhotos(<?= $user_id ?>)">
                    <i class="fas fa-trash"></i> Удалить все фото пользователя
                </button>
            </div>
            <?php endif; ?>
        </div>

        <!-- Секция с фотографиями пользователя -->
        <section class="top-photos-section">
            <h2 class="section-title">Фотографии пользователя</h2>
            <div class="top-photos-container" id="user-photos-container">
                <!-- Фотографии будут загружены через JavaScript -->
            </div>
        </section>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Загрузка фотографий пользователя
        function loadUserPhotos() {
            const userId = <?= $user_id ?>;
            
            fetch('get_user_public_photos.php?id=' + userId)
                .then(response => response.json())
                .then(data => {
                    const container = document.getElementById('user-photos-container');
                    if (!container) return;
                    
                    container.innerHTML = '';
                    
                    if (data.photos && data.photos.length > 0) {
                        data.photos.forEach(photo => {
                            const photoCard = document.createElement('div');
                            photoCard.className = 'top-photo-card';
                            photoCard.innerHTML = `
                                <img src="uploads/${photo.filename}" alt="Фото" class="top-photo-img">
                                <div class="top-photo-rating">
                                    <i class="fas fa-star"></i>
                                    <span>${photo.average_rating ? photo.average_rating.toFixed(1) : 'Нет'}</span>
                                </div>
                                <div class="top-photo-votes">
                                    <i class="fas fa-users"></i>
                                    <span>${photo.vote_count || 0}</span>
                                </div>
                                <div class="photo-card-content">
                                    <div class="photo-card-title">${photo.title || 'Без названия'}</div>
                                    <div style="font-size: 10px; color: #888; margin-top: 8px;">
                                        Загружено: ${new Date(photo.upload_date).toLocaleDateString()}
                                    </div>
                                </div>
                            `;
                            container.appendChild(photoCard);
                        });
                    } else {
                        container.innerHTML = '<div class="empty-state"><i class="fas fa-camera"></i><p>Пользователь еще не загрузил фотографии</p></div>';
                    }
                })
                .catch(error => {
                    console.error('Ошибка загрузки фотографий:', error);
                    const container = document.getElementById('user-photos-container');
                    if (container) {
                        container.innerHTML = '<div class="empty-state"><i class="fas fa-exclamation-triangle"></i><p>Ошибка загрузки фотографий</p></div>';
                    }
                });
        }

        // Функция для удаления всех фото пользователя (для модераторов)
        async function deleteAllUserPhotos(userId) {
            if (!confirm('Вы уверены, что хотите удалить ВСЕ фотографии этого пользователя? Это действие нельзя отменить.')) {
                return;
            }
            
            try {
                const response = await fetch('moderator_delete_user_photos.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `user_id=${userId}`
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert('Все фотографии пользователя удалены');
                    location.reload();
                } else {
                    alert('Ошибка: ' + data.message);
                }
            } catch (error) {
                console.error('Ошибка удаления:', error);
                alert('Произошла ошибка при удалении');
            }
        }

        // Функционал редактирования статуса
        const statusDisplay = document.getElementById('status-display');
        const statusEditForm = document.getElementById('status-edit-form');
        const editStatusBtn = document.getElementById('edit-status-btn');
        const statusInput = document.getElementById('status-input');
        const cancelStatusBtn = document.querySelector('.cancel-status-btn');

        if (editStatusBtn && statusDisplay && statusEditForm) {
            editStatusBtn.addEventListener('click', function() {
                statusDisplay.style.display = 'none';
                statusEditForm.style.display = 'block';
            });

            cancelStatusBtn.addEventListener('click', function() {
                statusEditForm.style.display = 'none';
                statusDisplay.style.display = 'flex';
            });

            statusEditForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const newStatus = statusInput.value.trim();
                if (!newStatus) return;

                try {
                    const response = await fetch('update_user_status.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `status=${encodeURIComponent(newStatus)}&user_id=<?= $user_id ?>`
                    });

                    const data = await response.json();
                    
                    if (data.success) {
                        document.querySelector('.status-text').textContent = newStatus;
                        statusEditForm.style.display = 'none';
                        statusDisplay.style.display = 'flex';
                        alert('Статус обновлен');
                    } else {
                        alert('Ошибка: ' + data.message);
                    }
                } catch (error) {
                    console.error('Ошибка обновления статуса:', error);
                    alert('Произошла ошибка при обновлении статуса');
                }
            });
        }

        loadUserPhotos();
    });
    </script>
</body>
</html>
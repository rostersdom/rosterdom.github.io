<?php
session_start();
require_once 'config/database.php';

// Заголовки против кэширования
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}
// В начале файла, после получения данных пользователя, добавьте:
try {
    // Сначала проверяем есть ли поле STATUS в таблице
    $check_column = $pdo->query("SHOW COLUMNS FROM users LIKE 'STATUS'");
    $STATUS_column_exists = $check_column->rowCount() > 0;
    
    // Проверяем есть ли поле avatar в таблице
    $check_avatar = $pdo->query("SHOW COLUMNS FROM users LIKE 'avatar'");
    $avatar_column_exists = $check_avatar->rowCount() > 0;
    
    // Проверяем есть ли поля is_vip и is_moderator
    $check_vip = $pdo->query("SHOW COLUMNS FROM users LIKE 'is_vip'");
    $vip_column_exists = $check_vip->rowCount() > 0;
    
    $check_moderator = $pdo->query("SHOW COLUMNS FROM users LIKE 'is_moderator'");
    $moderator_column_exists = $check_moderator->rowCount() > 0;
    
    // Если поля avatar нет, создаем его
    if (!$avatar_column_exists) {
        $pdo->exec("ALTER TABLE users ADD COLUMN avatar VARCHAR(255) DEFAULT NULL");
    }
    
    // Если поля is_vip и is_moderator нет, создаем их
    if (!$vip_column_exists) {
        $pdo->exec("ALTER TABLE users ADD COLUMN is_vip BOOLEAN DEFAULT FALSE");
    }
    
    if (!$moderator_column_exists) {
        $pdo->exec("ALTER TABLE users ADD COLUMN is_moderator BOOLEAN DEFAULT FALSE");
    }
    
    // Формируем запрос с новыми полями
    $stmt = $pdo->prepare("
        SELECT u.*, 
               COUNT(p.id) as photos_count,
               COALESCE(AVG(r.rating), 0) as avg_rating,
               COUNT(r.id) as ratings_received
        FROM users u 
        LEFT JOIN photos p ON u.id = p.user_id 
        LEFT JOIN ratings r ON p.id = r.photo_id 
        WHERE u.id = ?
        GROUP BY u.id
    ");
    
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if (!$user) {
        die('Пользователь не найден');
    }
    
    // Убедимся, что поле STATUS существует в массиве $user
    if (!isset($user['STATUS'])) {
        $user['STATUS'] = 'Новый пользователь';
    }
    
    // Убедимся, что поля is_vip и is_moderator существуют
    if (!isset($user['is_vip'])) {
        $user['is_vip'] = false;
    }
    
    if (!isset($user['is_moderator'])) {
        $user['is_moderator'] = false;
    }
    
} catch (PDOException $e) {
    die('Ошибка базы данных: ' . $e->getMessage());
}

// Получаем данные пользователя
try {
    // Сначала проверяем есть ли поле STATUS в таблице
    $check_column = $pdo->query("SHOW COLUMNS FROM users LIKE 'STATUS'");
    $STATUS_column_exists = $check_column->rowCount() > 0;
    
    // Проверяем есть ли поле avatar в таблице
    $check_avatar = $pdo->query("SHOW COLUMNS FROM users LIKE 'avatar'");
    $avatar_column_exists = $check_avatar->rowCount() > 0;
    
    // Если поля avatar нет, создаем его
    if (!$avatar_column_exists) {
        $pdo->exec("ALTER TABLE users ADD COLUMN avatar VARCHAR(255) DEFAULT NULL");
    }
    
    // Формируем запрос
    $stmt = $pdo->prepare("
        SELECT u.*, 
               COUNT(p.id) as photos_count,
               COALESCE(AVG(r.rating), 0) as avg_rating,
               COUNT(r.id) as ratings_received
        FROM users u 
        LEFT JOIN photos p ON u.id = p.user_id 
        LEFT JOIN ratings r ON p.id = r.photo_id 
        WHERE u.id = ?
        GROUP BY u.id
    ");
    
    $stmt->execute([$_SESSION['user_id']]);
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
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Профиль - Roster</title>
    <link rel="stylesheet" href="style.css?v=<?= time() ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Подключаем библиотеку для обрезки -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.css">
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
    <div class="user-info-compact">
        <?php if ($user['avatar']): ?>
        <img src="avatars/<?= htmlspecialchars($user['avatar']) ?>?t=<?= time() ?>" alt="Аватар" class="user-avatar">
        <?php else: ?>
        <div class="user-avatar"><?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?></div>
        <?php endif; ?>
        <a href="profile.php" class="user-name-link"><?= htmlspecialchars($_SESSION['user_name']) ?></a>
        
        <!-- Бейджи привилегий в хедере -->
        <?php 
        $is_vip = isset($user['is_vip']) ? $user['is_vip'] : false;
        $is_moderator = isset($user['is_moderator']) ? $user['is_moderator'] : false;
        
        if ($user['is_admin']): ?>
            <a href="admin_panel.php" class="admin-badge">👑 Админ</a>
        <?php elseif ($is_moderator): ?>
            <span class="moderator-badge">⚡ Модератор</span>
        <?php elseif ($is_vip): ?>
            <span class="vip-badge">⭐ VIP</span>
        <?php endif; ?>
    </div>
    <a href="index.php" class="btn btn-outline">Главная</a>
    <a href="logout.php" class="btn btn-outline">Выйти</a>
</div>
        </header>

        <div class="profile-section">
            <div class="profile-header">
    <div class="profile-avatar-container">
        <?php if ($user['avatar']): ?>
        <img src="avatars/<?= htmlspecialchars($user['avatar']) ?>?t=<?= time() ?>" alt="Аватар" class="profile-avatar-img" id="profile-avatar-img">
        <?php else: ?>
        <div class="profile-avatar" id="profile-avatar-text">
            <?= strtoupper(substr($user['name'], 0, 1)) ?>
        </div>
        <?php endif; ?>
        <div class="avatar-actions">
            <button class="btn btn-outline btn-small" id="change-avatar-btn">
                <i class="fas fa-camera"></i> Сменить аватар
            </button>
            <?php if ($user['avatar']): ?>
            <button class="btn btn-outline btn-small btn-danger" id="remove-avatar-btn">
                <i class="fas fa-trash"></i> Удалить
            </button>
            <?php endif; ?>
        </div>
        <input type="file" id="avatar-input" accept="image/*" style="display: none;">
    </div>
    <div class="profile-info">
        <h2><?= htmlspecialchars($user['name']) ?></h2>
        <p>Зарегистрирован: <?= date('d.m.Y', strtotime($user['created_at'])) ?></p>
        
        <!-- Блок статуса -->
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
        
        <!-- Бейдж роли -->
        <div class="user-role-display">
            <?php 
            // Проверяем наличие полей is_vip и is_moderator
            $is_vip = isset($user['is_vip']) ? $user['is_vip'] : false;
            $is_moderator = isset($user['is_moderator']) ? $user['is_moderator'] : false;
            
            if ($user['is_admin']): ?>
                <span class="user-role-badge role-admin">👑 Администратор</span>
            <?php elseif ($is_moderator): ?>
                <span class="user-role-badge role-moderator">⚡ Модератор</span>
            <?php elseif ($is_vip): ?>
                <span class="user-role-badge role-vip">⭐ VIP Пользователь</span>
            <?php else: ?>
                <span class="user-role-badge role-user">👤 Пользователь</span>
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
            </div>
        </div>

        <!-- Секция с фотографиями пользователя -->
        <section class="top-photos-section">
            <h2 class="section-title">Мои фотографии</h2>
            <div class="top-photos-container" id="user-photos-container">
                <!-- Фотографии будут загружены через JavaScript -->
            </div>
        </section>
    </div>

    <!-- Модальное окно для обрезки аватарки -->
    <div class="modal-overlay" id="crop-modal">
        <div class="modal">
            <button class="modal-close" id="crop-close">&times;</button>
            <h2 class="modal-title">Обрезка аватарки</h2>
            <div class="crop-container">
                <img id="crop-image" src="" alt="Обрезка">
            </div>
            <div class="crop-actions">
                <button class="btn btn-outline" id="cancel-crop">Отмена</button>
                <button class="btn btn-primary" id="save-crop">Сохранить аватарку</button>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Элементы для аватарки
        const changeAvatarBtn = document.getElementById('change-avatar-btn');
        const removeAvatarBtn = document.getElementById('remove-avatar-btn');
        const avatarInput = document.getElementById('avatar-input');
        const profileAvatarImg = document.getElementById('profile-avatar-img');
        const profileAvatarText = document.getElementById('profile-avatar-text');
        const cropModal = document.getElementById('crop-modal');
        const cropImage = document.getElementById('crop-image');
        const cancelCrop = document.getElementById('cancel-crop');
        const saveCrop = document.getElementById('save-crop');
        const cropClose = document.getElementById('crop-close');

        // Элементы для статуса
        const statusDisplay = document.getElementById('status-display');
        const statusEditForm = document.getElementById('status-edit-form');
        const statusInput = document.getElementById('status-input');
        const editStatusBtn = document.getElementById('edit-status-btn');
        const cancelStatusBtn = statusEditForm ? statusEditForm.querySelector('.cancel-status-btn') : null;

        let cropper;

        // Обработчик смены аватарки
        if (changeAvatarBtn) {
            changeAvatarBtn.addEventListener('click', function() {
                avatarInput.click();
            });
        }

        // Обработчик выбора файла
        if (avatarInput) {
            avatarInput.addEventListener('change', function(e) {
                if (this.files && this.files[0]) {
                    const file = this.files[0];
                    
                    // Проверяем тип файла
                    if (!file.type.match('image.*')) {
                        showNotification('Пожалуйста, выберите изображение', 'error');
                        return;
                    }
                    
                    // Проверяем размер файла (максимум 5MB)
                    if (file.size > 5 * 1024 * 1024) {
                        showNotification('Размер файла не должен превышать 5MB', 'error');
                        return;
                    }
                    
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        // Показываем модальное окно для обрезки
                        cropImage.src = e.target.result;
                        cropModal.classList.add('active');
                        
                        // Инициализируем обрезку
                        setTimeout(() => {
                            if (cropper) {
                                cropper.destroy();
                            }
                            cropper = new Cropper(cropImage, {
                                aspectRatio: 1,
                                viewMode: 1,
                                autoCropArea: 0.8,
                                responsive: true,
                                restore: false,
                                guides: true,
                                center: true,
                                highlight: false,
                                cropBoxMovable: true,
                                cropBoxResizable: true,
                                toggleDragModeOnDblclick: false,
                            });
                        }, 100);
                    };
                    
                    reader.readAsDataURL(file);
                }
            });
        }

        // Обработчик удаления аватарки
        if (removeAvatarBtn) {
            removeAvatarBtn.addEventListener('click', function() {
                if (!confirm('Вы уверены, что хотите удалить аватарку?')) {
                    return;
                }
                
                removeAvatar();
            });
        }

        // Отмена обрезки
        if (cancelCrop) {
            cancelCrop.addEventListener('click', function() {
                cropModal.classList.remove('active');
                if (cropper) {
                    cropper.destroy();
                    cropper = null;
                }
            });
        }

        if (cropClose) {
            cropClose.addEventListener('click', function() {
                cropModal.classList.remove('active');
                if (cropper) {
                    cropper.destroy();
                    cropper = null;
                }
            });
        }

        // Сохранение обрезанной аватарки
       // Сохранение обрезанной аватарки
if (saveCrop) {
    saveCrop.addEventListener('click', function() {
        if (!cropper) {
            showNotification('Ошибка обрезки', 'error');
            return;
        }
        
        const saveBtn = this;
        const originalText = saveBtn.textContent;
        
        // Показываем индикатор загрузки
        saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Сохранение...';
        saveBtn.disabled = true;
        
        try {
            // Получаем обрезанное изображение
            const canvas = cropper.getCroppedCanvas({
                width: 200,
                height: 200,
                imageSmoothingEnabled: true,
                imageSmoothingQuality: 'high'
            });
            
            canvas.toBlob(function(blob) {
                const formData = new FormData();
                formData.append('avatar', blob, 'avatar.jpg');
                
                fetch('update_avatar.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Response data:', data);
                    if (data.success) {
                        // Обновляем аватарку на странице
                        updateAvatarDisplay(data.avatar_url);
                        cropModal.classList.remove('active');
                        if (cropper) {
                            cropper.destroy();
                            cropper = null;
                        }
                        showNotification('Аватарка успешно обновлена', 'success');
                    } else {
                        showNotification('Ошибка: ' + (data.message || 'Неизвестная ошибка'), 'error');
                    }
                })
                .catch(error => {
                    console.error('Ошибка загрузки:', error);
                    showNotification('Произошла ошибка при загрузке: ' + error.message, 'error');
                })
                .finally(() => {
                    saveBtn.textContent = originalText;
                    saveBtn.disabled = false;
                });
            }, 'image/jpeg', 0.9);
        } catch (error) {
            console.error('Ошибка обрезки:', error);
            showNotification('Ошибка при обрезке изображения', 'error');
            saveBtn.textContent = originalText;
            saveBtn.disabled = false;
        }
    });
}

        // Функция обновления отображения аватарки
       function updateAvatarDisplay(avatarUrl) {
    const timestamp = '?t=' + new Date().getTime();
    const fullAvatarUrl = avatarUrl + timestamp;
    
    // Обновляем в профиле
    if (profileAvatarImg) {
        profileAvatarImg.src = fullAvatarUrl;
    } else if (profileAvatarText) {
        profileAvatarText.style.display = 'none';
        const img = document.createElement('img');
        img.src = fullAvatarUrl;
        img.alt = 'Аватар';
        img.className = 'profile-avatar-img';
        img.id = 'profile-avatar-img';
        profileAvatarText.parentNode.appendChild(img);
    }
    
    // Обновляем в хедере профиля
    updateHeaderAvatar(fullAvatarUrl);
    
    // Отправляем событие для обновления в index.php
    broadcastAvatarUpdate(avatarUrl);
    
    // Показываем кнопку удаления если ее нет
    if (!removeAvatarBtn) {
        const removeBtn = document.createElement('button');
        removeBtn.className = 'btn btn-outline btn-small btn-danger';
        removeBtn.id = 'remove-avatar-btn';
        removeBtn.innerHTML = '<i class="fas fa-trash"></i> Удалить';
        removeBtn.addEventListener('click', function() {
            if (!confirm('Вы уверены, что хотите удалить аватарку?')) {
                return;
            }
            removeAvatar();
        });
        document.querySelector('.avatar-actions').appendChild(removeBtn);
    }
}

// Функция удаления аватарки
function removeAvatar() {
    fetch('remove_avatar.php', {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Восстанавливаем текстовый аватар в профиле
            if (profileAvatarImg) {
                profileAvatarImg.remove();
            }
            if (profileAvatarText) {
                profileAvatarText.style.display = 'flex';
            }
            
            // Обновляем в хедере профиля
            removeHeaderAvatar();
            
            // Отправляем событие для удаления в index.php
            broadcastAvatarRemoval();
            
            // Убираем кнопку удаления
            const removeBtn = document.getElementById('remove-avatar-btn');
            if (removeBtn) {
                removeBtn.remove();
            }
            
            showNotification('Аватарка удалена', 'success');
        } else {
            showNotification('Ошибка: ' + data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Ошибка удаления:', error);
        showNotification('Произошла ошибка при удалении', 'error');
    });
}

// Функция для обновления аватарки в хедере профиля
function updateHeaderAvatar(avatarUrl) {
    const headerAvatar = document.querySelector('.user-info-compact .user-avatar');
    if (headerAvatar) {
        if (headerAvatar.tagName === 'DIV') {
            headerAvatar.style.display = 'none';
            const img = document.createElement('img');
            img.src = avatarUrl;
            img.alt = 'Аватар';
            img.className = 'user-avatar';
            headerAvatar.parentNode.insertBefore(img, headerAvatar);
        } else if (headerAvatar.tagName === 'IMG') {
            headerAvatar.src = avatarUrl;
        }
    }
}

// Функция для удаления аватарки из хедера профиля
function removeHeaderAvatar() {
    const headerAvatar = document.querySelector('.user-info-compact .user-avatar');
    if (headerAvatar && headerAvatar.tagName === 'IMG') {
        headerAvatar.remove();
        const userInfoCompact = document.querySelector('.user-info-compact');
        const userNameLink = document.querySelector('.user-name-link');
        const userName = userNameLink ? userNameLink.textContent.trim() : 'U';
        
        const div = document.createElement('div');
        div.className = 'user-avatar';
        div.textContent = userName.charAt(0).toUpperCase();
        userInfoCompact.insertBefore(div, userNameLink);
    }
}

// Функции для межвкладковой коммуникации
function broadcastAvatarUpdate(avatarUrl) {
    localStorage.setItem('avatarUpdated', JSON.stringify({ 
        avatarUrl: avatarUrl,
        timestamp: new Date().getTime()
    }));
    setTimeout(() => localStorage.removeItem('avatarUpdated'), 100);
}

// Функция для отправки события об удалении аватарки
function broadcastAvatarRemoval() {
    localStorage.setItem('avatarRemoved', JSON.stringify({
        timestamp: new Date().getTime()
    }));
    setTimeout(() => localStorage.removeItem('avatarRemoved'), 100);
}

// Слушаем события от других вкладок
window.addEventListener('storage', function(e) {
    if (e.key === 'avatarUpdated') {
        const data = JSON.parse(e.newValue);
        if (data.avatarUrl) {
            updateAvatarDisplay(data.avatarUrl);
        }
    }
    
    if (e.key === 'avatarRemoved') {
        removeAvatar();
    }
});

        // === Код для редактирования статуса ===
        if (editStatusBtn && statusDisplay && statusEditForm && cancelStatusBtn) {
            // Показать форму редактирования статуса
            editStatusBtn.addEventListener('click', function() {
                statusDisplay.style.display = 'none';
                statusEditForm.style.display = 'block';
                statusInput.focus();
                statusInput.select();
            });

            // Скрыть форму редактирования статуса
            cancelStatusBtn.addEventListener('click', function() {
                statusDisplay.style.display = 'flex';
                statusEditForm.style.display = 'none';
                // Восстанавливаем исходное значение
                statusInput.value = statusDisplay.querySelector('.status-text').textContent;
            });

            // Сохранение статуса
            statusEditForm.addEventListener('submit', function(e) {
                e.preventDefault();
                saveUserStatus();
            });

            // Сохранение при нажатии Enter и отмена при Escape
            statusInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    saveUserStatus();
                } else if (e.key === 'Escape') {
                    cancelStatusBtn.click();
                }
            });
        }

        async function saveUserStatus() {
            const newStatus = statusInput.value.trim();
            const saveBtn = statusEditForm.querySelector('.save-status-btn');
            const originalText = saveBtn.textContent;

            // Валидация
            if (newStatus.length > 255) {
                showNotification('Статус слишком длинный (максимум 255 символов)', 'error');
                return;
            }

            // Показываем индикатор загрузки
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Сохранение...';
            saveBtn.disabled = true;

            try {
                const response = await fetch('update_user_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `status=${encodeURIComponent(newStatus)}`
                });

                const data = await response.json();

                if (data.success) {
                    // Обновляем отображаемый статус
                    const statusText = statusDisplay.querySelector('.status-text');
                    statusText.textContent = newStatus || 'Новый пользователь';
                    
                    // Скрываем форму
                    statusDisplay.style.display = 'flex';
                    statusEditForm.style.display = 'none';
                    
                    showNotification('Статус успешно обновлен', 'success');
                } else {
                    showNotification('Ошибка: ' + data.message, 'error');
                }
            } catch (error) {
                console.error('Ошибка обновления статуса:', error);
                showNotification('Произошла ошибка при обновлении статуса', 'error');
            } finally {
                // Восстанавливаем кнопку
                saveBtn.textContent = originalText;
                saveBtn.disabled = false;
            }
        }

        // Функция показа уведомлений
        function showNotification(message, type = 'info') {
            // Удаляем существующие уведомления
            const existingNotifications = document.querySelectorAll('.custom-notification');
            existingNotifications.forEach(notification => notification.remove());

            const notification = document.createElement('div');
            notification.className = `custom-notification ${type}`;
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: ${type === 'success' ? '#4CAF50' : type === 'error' ? '#f44336' : '#2196F3'};
                color: white;
                padding: 15px 20px;
                border-radius: 5px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                z-index: 10000;
                max-width: 300px;
                animation: slideIn 0.3s ease-out;
            `;
            
            notification.innerHTML = `
                <div style="display: flex; align-items: center; gap: 10px;">
                    <i class="fas ${type === 'success' ? 'fa-check' : type === 'error' ? 'fa-exclamation-triangle' : 'fa-info'}"></i>
                    <span>${message}</span>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.style.animation = 'slideOut 0.3s ease-in';
                    setTimeout(() => notification.remove(), 300);
                }
            }, 5000);
        }

        // Добавляем стили для анимации
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            @keyframes slideOut {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
        `;
        document.head.appendChild(style);

        // Загрузка фотографий пользователя
        function loadUserPhotos() {
            fetch('get_user_photos.php')
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
                        container.innerHTML = '<div class="empty-state"><i class="fas fa-camera"></i><p>Вы еще не загрузили фотографии</p></div>';
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

        loadUserPhotos();
    });
    </script>
</body>
</html>
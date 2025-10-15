<?php
session_start();
require_once 'config/database.php';

// Проверка прав администратора и получение данных пользователя
$is_admin = false;
$user_avatar = null;
$user_name = '';

if (isset($_SESSION['user_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT name, avatar, is_admin FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        if ($user) {
            $is_admin = $user['is_admin'];
            $user_avatar = $user['avatar'];
            $user_name = $user['name'];
        }
    } catch (PDOException $e) {
        error_log("User data fetch error: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Roster - Оценивай фотографии</title>
    <link rel="stylesheet" href="style.css?v=<?= time() ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="container">
        <header>
            <div class="logo">
                <i class="fas fa-heart"></i>
                <span>Roster</span>
            </div>
<?php if (isset($_SESSION['user_id'])): ?>
<div class="user-header-main">
    <div class="user-info-compact">
        <?php if ($user_avatar): ?>
        <img src="avatars/<?= htmlspecialchars($user_avatar) ?>?t=<?= time() ?>" alt="Аватар" class="user-avatar">
        <?php else: ?>
        <div class="user-avatar"><?= strtoupper(mb_substr($user_name, 0, 1, 'UTF-8')) ?></div>
        <?php endif; ?>
        <div class="user-name-and-badges">
            <a href="profile.php" class="user-name-link">
                <?= htmlspecialchars($user_name) ?>
            </a>
            
            <!-- Бейджи привилегий -->
            <?php 
            // Получаем данные о роли пользователя
            $user_role = 'user';
            $is_vip = false;
            $is_moderator = false;
            
            try {
                $stmt = $pdo->prepare("SELECT role, is_vip, is_moderator FROM users WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $user_data = $stmt->fetch();
                if ($user_data) {
                    $user_role = $user_data['role'];
                    $is_vip = $user_data['is_vip'];
                    $is_moderator = $user_data['is_moderator'];
                }
            } catch (PDOException $e) {
                error_log("User role fetch error: " . $e->getMessage());
            }
            
            // Отображаем бейджи в зависимости от роли и привилегий
            if ($is_admin): ?>
                <a href="admin_panel.php" class="admin-badge">👑 Админ</a>
            <?php elseif ($is_moderator): ?>
                <span class="moderator-badge">⚡ Модератор</span>
            <?php elseif ($is_vip): ?>
                <span class="vip-badge">⭐ VIP</span>
            <?php endif; ?>
        </div>
    </div>
    <a href="logout.php" class="btn btn-outline logout-btn">Выйти</a>
</div>
<?php endif; ?>       
</header>

        <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-error" style="background: #ffebee; color: #c62828; padding: 15px; border-radius: 5px; margin: 20px 0; text-align: center;">
            <?php 
            $errors = [
                'invalid_credentials' => 'Неверный логин или пароль',
                'missing_fields' => 'Заполните все поля',
                'empty_fields' => 'Все поля обязательны для заполнения'
            ];
            echo $errors[$_GET['error']] ?? 'Произошла ошибка';
            ?>
        </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['user_id'])): ?>
        <div class="tabs">
            <button class="tab active" data-tab="rating">Оценивать фото</button>
            <button class="tab" data-tab="top">Топ</button>
            <button class="tab" data-tab="upload">Мои фото</button>
        </div>
        
        <!-- Секция оценки фотографий -->
        <section id="rating-section" class="section active-section">
            <h2 class="section-title">Оцените фотографию</h2>
            
            <div class="progress-info">
                Осталось фотографий: <span id="photos-left">0</span>
            </div>
            
            <div class="current-photo-container">
                <div id="photo-container">
                    <!-- Фото будет загружено через JavaScript -->
                </div>
            </div>
            
            <div class="rating-controls" id="rating-controls" style="display: none;">
                <div class="rating-slider-container">
                    <input type="range" min="1" max="10" value="5" class="rating-slider" id="rating-slider">
                    <div class="rating-labels">
                        <span>1</span>
                        <span>10</span>
                    </div>
                </div>
                <div class="rating-value-display" id="rating-value">5</div>
                <button class="submit-rating" id="submit-rating">Оценить</button>
            </div>
            
            <div class="progress-info" id="no-photos-message">
                Нет фотографий для оценки. Загрузите свои фотографии или дождитесь, пока другие пользователи загрузят свои.
            </div>
        </section>
        
        <!-- Секция топа -->
        <section id="top-section" class="section hidden">
            <h2 class="section-title">Топ</h2>
            
            <!-- Вкладки для топа -->
            <div class="tabs" style="margin-bottom: 20px;">
                <button class="tab active" data-subtab="photos">Топ фото</button>
                <button class="tab" data-subtab="users">Топ пользователей</button>
            </div>
            
            <!-- Контейнер для топа фото -->
            <div id="top-photos-container" class="top-subsection active-subsection">
                <div class="top-photos-container" id="photos-container">
                    <!-- Топ фото будут загружаться через JavaScript -->
                </div>
                
                <div class="empty-state" id="empty-top-photos">
                    <i class="fas fa-trophy"></i>
                    <p>Пока нет оцененных фотографий</p>
                    <p>Начните оценивать фотографии, чтобы увидеть здесь топ</p>
                </div>
            </div>
            
            <!-- Контейнер для топа пользователей -->
            <div id="top-users-container" class="top-subsection hidden">
                <div class="top-users-container" id="users-container">
                    <!-- Топ пользователи будут загружаться через JavaScript -->
                </div>
                
                <div class="empty-state" id="empty-top-users">
                    <i class="fas fa-users"></i>
                    <p>Пока нет пользователей с оценками</p>
                    <p>Начните оценивать фотографии, чтобы увидеть здесь топ пользователей</p>
                </div>
            </div>
        </section>
        
        <!-- Секция загрузки фотографий -->
        <section id="upload-section" class="section hidden">
            <h2 class="section-title">Загрузите свои фотографии</h2>
            
            <form id="upload-form" enctype="multipart/form-data">
                <div class="upload-area" id="upload-area">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <p>Перетащите сюда свои фотографии или</p>
                    <label for="file-input" class="upload-btn">Выберите файлы</label>
                    <input type="file" id="file-input" name="photos[]" accept="image/*" multiple>
                    <div id="file-list" style="margin-top: 15px;"></div>
                </div>
                
                <div class="form-group" style="max-width: 500px; margin: 20px auto;">
                    <label for="photo-title">Название фото (будет отображаться в карточке)</label>
                    <input type="text" id="photo-title" name="photo_title" class="form-control" 
                           placeholder="Введите название для фото" maxlength="50">
                    <small style="color: #666; font-size: 0.8rem;">Максимум 50 символов</small>
                </div>
                
                <p style="text-align: center; margin-bottom: 20px;">Рекомендуем загружать качественные фотографии. Максимальный размер: 5MB</p>
                <button type="submit" class="submit-rating" style="display: block; margin: 0 auto;">Загрузить выбранные фото</button>
            </form>
            
            <div id="upload-status" style="text-align: center; margin: 20px 0;"></div>
            
            <h3 style="margin: 30px 0 15px 0;">Ваши загруженные фото</h3>
            <div id="user-photos" class="top-photos-container">
                <!-- Фото пользователя будут загружаться здесь -->
            </div>
        </section>
        
        <?php else: ?>
        <div class="rating-section" style="text-align: center; padding: 60px;">
            <h2>Добро пожаловать в Roster!</h2>
            <p style="margin: 20px 0; font-size: 1.2rem;">Оценивайте фотографии других пользователей и загружайте свои</p>
            <div style="display: flex; gap: 15px; justify-content: center; margin-top: 30px;">
                <button class="btn btn-primary" id="welcome-register-btn">Зарегистрироваться</button>
                <button class="btn btn-outline" id="welcome-login-btn">Войти</button>
            </div>
        </div>
        <?php endif; ?>
        
        <footer>
            <p>© 2025 Roster - Все права защищены.</p>
            <p>Политика конфиденциальности | Условия использования</p>
        </footer>
    </div>

    <!-- Модальное окно деталей фотографии -->
    <div class="details-modal" id="details-modal">
        <div class="details-modal-content">
            <button class="close-details-modal" id="close-details-modal">&times;</button>
            <h2 class="details-modal-title"></h2>
            
            <div class="enlarged-photo-container">
                <img src="" alt="Увеличенное фото" class="enlarged-photo" id="enlarged-photo">
            </div>
            
            <div class="author-info">
                <div class="author-avatar" id="modal-author-avatar">А</div>
                <div class="author-details">
                    <h3 id="modal-author-name">Пользователь бывший</h3>
                    <p id="modal-upload-date">Загружено: 15 мая 2023</p>
                </div>
            </div>
            <div class="photo-details">
                <h4>Характеристики</h4>
                <div class="characteristics">
                    <div class="characteristic">
                        <div class="characteristic-value" id="modal-rating">57</div>
                        <div class="characteristic-label">Рейтинг</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Модальное окно регистрации -->
    <div class="modal-overlay" id="register-modal">
        <div class="modal">
            <button class="modal-close" id="register-close">&times;</button>
            <h2 class="modal-title">Регистрация</h2>
            <form id="register-form" action="register.php" method="post">
                <div class="form-group">
                    <label for="reg-name">Имя</label>
                    <input type="text" id="reg-name" name="name" class="form-control" placeholder="Ваше имя" required>
                </div>
                <div class="form-group">
                    <label for="reg-login">Логин</label>
                    <input type="text" id="reg-login" name="login" class="form-control" placeholder="Придумайте логин" required minlength="3">
                </div>
                <div class="form-group">
                    <label for="reg-password">Пароль</label>
                    <input type="password" id="reg-password" name="password" class="form-control" placeholder="Придумайте пароль" required minlength="6">
                </div>
                <div class="form-group">
                    <label for="reg-confirm">Подтверждение пароля</label>
                    <input type="password" id="reg-confirm" name="confirm_password" class="form-control" placeholder="Повторите пароль" required>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-outline" id="switch-to-login">Уже есть аккаунт?</button>
                    <button type="submit" class="btn btn-primary">Зарегистрироваться</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Модальное окно входа -->
    <div class="modal-overlay" id="login-modal">
        <div class="modal">
            <button class="modal-close" id="login-close">&times;</button>
            <h2 class="modal-title">Вход</h2>
            <form id="login-form" action="login.php" method="post">
                <div class="form-group">
                    <label for="login-login">Логин</label>
                    <input type="text" id="login-login" name="login" class="form-control" placeholder="Ваш логин" required>
                </div>
                <div class="form-group">
                    <label for="login-password">Пароль</label>
                    <input type="password" id="login-password" name="password" class="form-control" placeholder="Ваш пароль" required>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-outline" id="switch-to-register">Создать аккаунт</button>
                    <button type="submit" class="btn btn-primary">Войти</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const fileInput = document.getElementById('file-input');
        const uploadArea = document.getElementById('upload-area');
        const tabs = document.querySelectorAll('.tab[data-tab]');
        const sections = document.querySelectorAll('.section');
        const loginBtn = document.getElementById('login-btn');
        const registerBtn = document.getElementById('register-btn');
        const welcomeLoginBtn = document.getElementById('welcome-login-btn');
        const welcomeRegisterBtn = document.getElementById('welcome-register-btn');
        const ratingSlider = document.getElementById('rating-slider');
        const ratingValue = document.getElementById('rating-value');
        const submitRating = document.getElementById('submit-rating');
        const photosLeftElement = document.getElementById('photos-left');
        const noPhotosMessage = document.getElementById('no-photos-message');
        const ratingControls = document.getElementById('rating-controls');
        const photoContainer = document.getElementById('photo-container');
        
        // Элементы модальных окон
        const registerModal = document.getElementById('register-modal');
        const loginModal = document.getElementById('login-modal');
        const registerClose = document.getElementById('register-close');
        const loginClose = document.getElementById('login-close');
        const switchToLogin = document.getElementById('switch-to-login');
        const switchToRegister = document.getElementById('switch-to-register');
        
        // Элементы для деталей фотографии
        const detailsModal = document.getElementById('details-modal');
        const closeDetailsModal = document.getElementById('close-details-modal');
        const enlargedPhoto = document.getElementById('enlarged-photo');
        const modalAuthorAvatar = document.getElementById('modal-author-avatar');
        const modalAuthorName = document.getElementById('modal-author-name');
        const modalUploadDate = document.getElementById('modal-upload-date');
        const modalRating = document.getElementById('modal-rating');

        let topInitialized = false;

        // Функция для получения цвета по значению
        function getColorForValue(value) {
            const colors = {
                1: '#f44336', // Красный
                2: '#f44336',
                3: '#ff9800', // Оранжевый
                4: '#ff9800',
                5: '#ffeb3b', // Желтый
                6: '#ffeb3b',
                7: '#8bc34a', // Светло-зеленый
                8: '#8bc34a',
                9: '#4caf50', // Зеленый
                10: '#4caf50'
            };
            return colors[value] || '#ffeb3b';
        }

        // Блок для слайдера
        if (ratingSlider) {
            function updateSliderProgress() {
                const value = ratingSlider.value;
                const min = ratingSlider.min ? parseInt(ratingSlider.min) : 1;
                const max = ratingSlider.max ? parseInt(ratingSlider.max) : 10;
                const percentage = ((value - min) / (max - min)) * 100;
                
                const progressColor = getColorForValue(parseInt(value));
                
                ratingSlider.style.setProperty('--progress', percentage + '%');
                ratingSlider.style.setProperty('--progress-color', progressColor);
                ratingValue.textContent = value;
            }
            
            function initSlider() {
                ratingSlider.value = 5;
                updateSliderProgress();
            }
            
            ratingSlider.addEventListener('input', updateSliderProgress);
            ratingSlider.addEventListener('change', updateSliderProgress);
            
            initSlider();
            
            if (submitRating) {
                submitRating.addEventListener('click', function() {
                    setTimeout(initSlider, 100);
                });
            }
        }

        // Переключение между основными вкладками
        if (tabs.length > 0) {
            tabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    const tabId = this.getAttribute('data-tab');
                    
                    // Обновляем активную вкладку
                    tabs.forEach(t => t.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Показываем соответствующую секцию
                    sections.forEach(section => {
                        section.classList.remove('active-section');
                        section.classList.add('hidden');
                    });
                    
                    const activeSection = document.getElementById(`${tabId}-section`);
                    if (activeSection) {
                        activeSection.classList.remove('hidden');
                        activeSection.classList.add('active-section');
                        
                        // Если перешли на вкладку топа, инициализируем его вкладки
                        if (tabId === 'top') {
                            setTimeout(() => {
                                initTopTabs();
                            }, 100);
                        }
                        
                        // Если перешли на вкладку загрузки, загружаем фото пользователя
                        if (tabId === 'upload') {
                            loadUserPhotos();
                        }
                    }
                });
            });
        }
        
        // Загрузка фото для оценки
        function loadCurrentPhoto() {
            fetch('get_photos.php')
                .then(response => response.json())
                .then(data => {
                    if (data.photo) {
                        photoContainer.innerHTML = `
                            <img src="uploads/${data.photo.filename}" alt="Текущее фото" class="current-photo" data-photo-id="${data.photo.id}">
                            <div class="photo-author-info">
                                📸 Загружено: ${data.photo.user_name}
                            </div>
                        `;
                        ratingControls.style.display = 'flex';
                        noPhotosMessage.style.display = 'none';
                        
                        if (ratingSlider) {
                            setTimeout(() => {
                                ratingSlider.value = 5;
                                const progressColor = getColorForValue(5);
                                ratingSlider.style.setProperty('--progress', '50%');
                                ratingSlider.style.setProperty('--progress-color', progressColor);
                                ratingValue.textContent = '5';
                            }, 100);
                        }
                    } else {
                        photoContainer.innerHTML = '';
                        ratingControls.style.display = 'none';
                        noPhotosMessage.style.display = 'block';
                    }
                    updatePhotosCount();
                })
                .catch(error => {
                    console.error('Error loading photo:', error);
                    photoContainer.innerHTML = '';
                    ratingControls.style.display = 'none';
                    noPhotosMessage.style.display = 'block';
                });
        }

        // Обновление счетчика фото
        function updatePhotosCount() {
            fetch('get_photos.php?count=true')
                .then(response => response.json())
                .then(data => {
                    photosLeftElement.textContent = data.count;
                })
                .catch(error => {
                    console.error('Error updating photos count:', error);
                    photosLeftElement.textContent = '0';
                });
        }
        
        // Оценка фото
        if (submitRating) {
            submitRating.addEventListener('click', function() {
                const rating = parseInt(ratingSlider.value);
                const currentPhoto = document.querySelector('.current-photo');
                
                if (!currentPhoto) {
                    alert('Нет фото для оценки');
                    return;
                }
                
                const photoId = currentPhoto.getAttribute('data-photo-id');
                
                fetch('rate.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `photo_id=${photoId}&rating=${rating}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        loadCurrentPhoto();
                    } else {
                        alert('Ошибка: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Произошла ошибка при оценке фото');
                });
            });
        }
        
        // Загрузка топа фото
      function loadTopPhotos() {
    console.log('Loading top photos...');
    fetch('get_top_photos.php')
        .then(response => {
            console.log('Photos response status:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('Top photos data:', data);
            const container = document.getElementById('photos-container');
            if (!container) {
                console.error('Photos container not found');
                return;
            }
            
            container.innerHTML = '';
            
            if (!data.photos || data.photos.length === 0) {
                document.getElementById('empty-top-photos').style.display = 'block';
                return;
            }
            
            document.getElementById('empty-top-photos').style.display = 'none';
            
            data.photos.forEach(photo => {
                const photoCard = document.createElement('div');
                photoCard.className = 'top-photo-card';
                photoCard.setAttribute('data-photo-id', photo.id);
                
                const title = photo.title || `Фото ${photo.id}`;
                const userName = photo.user_name || 'Неизвестный автор';
                const userInitial = userName.charAt(0).toUpperCase();
                const rating = photo.average_rating ? photo.average_rating.toFixed(1) : '0.0';
                const votes = photo.vote_count || 0;
                
                photoCard.innerHTML = `
    <img src="uploads/${photo.filename}" alt="Топ фото" class="top-photo-img">
    <div class="top-photo-rating">
        <i class="fas fa-star"></i>
        <span>${rating}</span>
    </div>
    <div class="top-photo-votes">
        <i class="fas fa-users"></i>
        <span>${votes}</span>
    </div>
    <div class="photo-card-content">
        <div class="photo-card-title">${title}</div>
        <div class="photo-card-user">
            <div class="user-avatar-small">${userInitial}</div>
            <div class="user-name">
                ${userName}
                ${photo.user_role === 'admin' ? '<span class="user-role-badge role-admin">👑 Админ</span>' : ''}
                ${photo.user_role === 'moderator' ? '<span class="user-role-badge role-moderator">⚡ Модератор</span>' : ''}
                ${photo.user_role === 'vip' ? '<span class="user-role-badge role-vip">⭐ VIP</span>' : ''}
            </div>
        </div>
        <div class="photo-card-stats">
            <div class="stat-small">
                <div class="stat-value-small">${rating}</div>
                <div class="stat-label-small">Оценка</div>
            </div>
            <div class="stat-small">
                <div class="stat-value-small">${votes}</div>
                <div class="stat-label-small">Голосов</div>
            </div>
        </div>
    </div>
`;
                
                photoCard.addEventListener('click', function() {
                    openPhotoDetails(photo, {
                        title,
                        userName,
                        userInitial,
                        rating,
                        votes
                    });
                });
                
                container.appendChild(photoCard);
            });
        })
        .catch(error => {
            console.error('Error loading top photos:', error);
            document.getElementById('empty-top-photos').style.display = 'block';
        });
}

        // Загрузка топа пользователей
        function loadTopUsers() {
            console.log('Loading top users...');
            
            fetch('get_top_users.php')
                .then(response => {
                    console.log('Users response status:', response.status);
                    return response.json();
                })
                .then(data => {
                    console.log('Top users data:', data);
                    const container = document.getElementById('users-container');
                    if (!container) {
                        console.error('Users container not found');
                        return;
                    }
                    
                    container.innerHTML = '';
                    
                    if (!data.success || !data.users || data.users.length === 0) {
                        document.getElementById('empty-top-users').style.display = 'block';
                        return;
                    }
                    
                    document.getElementById('empty-top-users').style.display = 'none';
                    
                    data.users.forEach((user, index) => {
                        const userCard = document.createElement('div');
                        userCard.className = 'top-user-card';
                        userCard.setAttribute('data-user-id', user.id);
                        
                        let avatarHTML = '';
                        const timestamp = '?t=' + new Date().getTime();
                        
                        if (user.avatar) {
                            avatarHTML = `<img src="avatars/${user.avatar}${timestamp}" alt="${user.name}" class="user-avatar-medium">`;
                        } else {
                            avatarHTML = `<div class="user-avatar-medium">${user.initial}</div>`;
                        }
                        
userCard.innerHTML = `
    <div class="user-rank">${index + 1}</div>
    ${avatarHTML}
    <div class="user-info">
        <div class="user-name">
            ${user.name}
            ${user.role === 'admin' ? '<span class="user-role-badge role-admin">👑 Админ</span>' : ''}
            ${user.role === 'moderator' ? '<span class="user-role-badge role-moderator">⚡ Модератор</span>' : ''}
            ${user.role === 'vip' ? '<span class="user-role-badge role-vip">⭐ VIP</span>' : ''}
        </div>
        <div class="user-stats">
            <div class="user-stat">
                <i class="fas fa-star"></i>
                <span>${user.avg_rating || '0.0'}</span>
            </div>
            <div class="user-stat">
                <i class="fas fa-camera"></i>
                <span>${user.photos_count || 0}</span>
            </div>
        </div>
    </div>
`;
                        
                        userCard.addEventListener('click', function(e) {
                            if (e.target.tagName === 'A' || e.target.closest('a')) {
                                return;
                            }
                            const userId = this.getAttribute('data-user-id');
                            window.location.href = `user_profile.php?id=${userId}`;
                        });
                        
                        container.appendChild(userCard);
                    });
                })
                .catch(error => {
                    console.error('Error loading top users:', error);
                    document.getElementById('empty-top-users').style.display = 'block';
                });
        }

        // Инициализация вкладок топа
        function initTopTabs() {
            console.log('Initializing top tabs...');
            const topTabs = document.querySelectorAll('#top-section [data-subtab]');
            const topSubsections = document.querySelectorAll('.top-subsection');
            
            console.log('Found top tabs:', topTabs.length);
            console.log('Found top subsections:', topSubsections.length);
            
            if (topTabs.length === 0) {
                console.error('No top tabs found');
                return;
            }
            
            // Удаляем старые обработчики и добавляем новые
            topTabs.forEach(tab => {
                const newTab = tab.cloneNode(true);
                tab.parentNode.replaceChild(newTab, tab);
            });
            
            // Получаем обновленные элементы
            const updatedTopTabs = document.querySelectorAll('#top-section [data-subtab]');
            
            updatedTopTabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    const subtabId = this.getAttribute('data-subtab');
                    console.log('Top tab clicked:', subtabId);
                    
                    // Обновляем активную вкладку
                    updatedTopTabs.forEach(t => t.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Показываем соответствующую секцию
                    topSubsections.forEach(section => {
                        section.classList.remove('active-subsection');
                        section.classList.add('hidden');
                    });
                    
                    const activeSection = document.getElementById(`top-${subtabId}-container`);
                    if (activeSection) {
                        activeSection.classList.remove('hidden');
                        activeSection.classList.add('active-subsection');
                        
                        // Загружаем данные
                        if (subtabId === 'photos') {
                            console.log('Loading top photos...');
                            loadTopPhotos();
                        } else if (subtabId === 'users') {
                            console.log('Loading top users...');
                            loadTopUsers();
                        }
                    }
                });
            });
            
            // Активируем первую вкладку
            const activeTab = document.querySelector('#top-section [data-subtab].active');
            if (activeTab) {
                console.log('Activating tab:', activeTab.getAttribute('data-subtab'));
                activeTab.click();
            } else if (updatedTopTabs[0]) {
                console.log('Activating first tab:', updatedTopTabs[0].getAttribute('data-subtab'));
                updatedTopTabs[0].click();
            }
            
            topInitialized = true;
        }

        // Функция открытия деталей фотографии
        function openPhotoDetails(photo, details) {
            enlargedPhoto.src = `uploads/${photo.filename}`;
            modalAuthorAvatar.textContent = details.userInitial;
            modalAuthorName.textContent = `${details.userName}`;
            modalUploadDate.textContent = `Загружено: ${new Date().toLocaleDateString()}`;
            modalRating.textContent = details.rating;
            
            detailsModal.style.display = 'flex';
        }
        
        // Обработчики для модального окна деталей
        closeDetailsModal.addEventListener('click', function() {
            detailsModal.style.display = 'none';
        });
        
        window.addEventListener('click', function(e) {
            if (e.target === detailsModal) {
                detailsModal.style.display = 'none';
            }
        });
        
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                detailsModal.style.display = 'none';
            }
        });
        
        // Загрузка фото пользователя
        function loadUserPhotos() {
            fetch('get_user_photos.php')
                .then(response => response.json())
                .then(data => {
                    const container = document.getElementById('user-photos');
                    container.innerHTML = '';
                    
                    if (data.photos && data.photos.length > 0) {
                        data.photos.forEach(photo => {
                            const photoCard = document.createElement('div');
                            photoCard.className = 'top-photo-card';
                            photoCard.setAttribute('data-photo-id', photo.id);
                            
                            photoCard.innerHTML = `
                                <img src="uploads/${photo.filename}" alt="Ваше фото" class="top-photo-img">
                                <div class="top-photo-rating">
                                    <i class="fas fa-star"></i>
                                    <span>${photo.average_rating ? photo.average_rating.toFixed(1) : 'Нет'}</span>
                                </div>
                                <div class="top-photo-votes">
                                    <i class="fas fa-users"></i>
                                    <span>${photo.vote_count || 0}</span>
                                </div>
                                
                                <div class="photo-card-content">
                                    <div class="photo-title-editable" data-photo-id="${photo.id}">
                                        <strong>${photo.title || 'Без названия'}</strong>
                                        <button class="edit-title-btn" title="Редактировать название">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </div>
                                    
                                    <form class="title-edit-form" data-photo-id="${photo.id}">
                                        <input type="text" class="title-edit-input" value="${photo.title || ''}" 
                                               placeholder="Введите название фото" maxlength="50">
                                        <div class="title-edit-actions">
                                            <button type="submit" class="save-title-btn">Сохранить</button>
                                            <button type="button" class="cancel-title-btn">Отмена</button>
                                        </div>
                                    </form>
                                    
                                    <div class="photo-card-user">
                                        <div class="user-avatar-small">${photo.user_initial || 'U'}</div>
                                        <div class="user-name">${photo.user_name || 'Вы'}</div>
                                    </div>
                                    <div class="photo-card-stats">
                                        <div class="stat-small">
                                            <div class="stat-value-small">${photo.average_rating ? photo.average_rating.toFixed(1) : '0.0'}</div>
                                            <div class="stat-label-small">Оценка</div>
                                        </div>
                                        <div class="stat-small">
                                            <div class="stat-value-small">${photo.vote_count || 0}</div>
                                            <div class="stat-label-small">Голосов</div>
                                        </div>
                                    </div>
                                    <div style="font-size: 10px; color: #888; margin-top: 8px;">
                                        Загружено: ${new Date(photo.upload_date).toLocaleDateString()}
                                    </div>
                                </div>
                                
                                <button class="delete-photo-btn" data-photo-id="${photo.id}">
                                    <i class="fas fa-times"></i>
                                </button>
                            `;
                            
                            container.appendChild(photoCard);
                        });
                        
                        addEditTitleHandlers();
                        addDeleteHandlers();
                    } else {
                        container.innerHTML = '<div class="empty-state"><i class="fas fa-camera"></i><p>Вы еще не загрузили фотографии</p></div>';
                    }
                });
        }

        // Функция для добавления обработчиков редактирования названий
        function addEditTitleHandlers() {
            document.querySelectorAll('.edit-title-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const photoId = this.closest('.photo-title-editable').getAttribute('data-photo-id');
                    showEditTitleForm(photoId);
                });
            });
            
            document.querySelectorAll('.title-edit-form').forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const photoId = this.getAttribute('data-photo-id');
                    const newTitle = this.querySelector('.title-edit-input').value.trim();
                    savePhotoTitle(photoId, newTitle);
                });
            });
            
            document.querySelectorAll('.cancel-title-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const photoId = this.closest('.title-edit-form').getAttribute('data-photo-id');
                    hideEditTitleForm(photoId);
                });
            });
        }

        function showEditTitleForm(photoId) {
            const titleElement = document.querySelector(`.photo-title-editable[data-photo-id="${photoId}"]`);
            const formElement = document.querySelector(`.title-edit-form[data-photo-id="${photoId}"]`);
            
            if (titleElement && formElement) {
                titleElement.style.display = 'none';
                formElement.style.display = 'block';
                
                const input = formElement.querySelector('.title-edit-input');
                input.focus();
                input.select();
            }
        }

        function hideEditTitleForm(photoId) {
            const titleElement = document.querySelector(`.photo-title-editable[data-photo-id="${photoId}"]`);
            const formElement = document.querySelector(`.title-edit-form[data-photo-id="${photoId}"]`);
            
            if (titleElement && formElement) {
                titleElement.style.display = 'block';
                formElement.style.display = 'none';
            }
        }

        function savePhotoTitle(photoId, newTitle) {
            if (!newTitle) {
                newTitle = 'Без названия';
            }
            
            const saveBtn = document.querySelector(`.title-edit-form[data-photo-id="${photoId}"] .save-title-btn`);
            const originalText = saveBtn.textContent;
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            saveBtn.disabled = true;
            
            fetch('update_photo_title.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `photo_id=${photoId}&title=${encodeURIComponent(newTitle)}`
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
                    const titleElement = document.querySelector(`.photo-title-editable[data-photo-id="${photoId}"] strong`);
                    if (titleElement) {
                        titleElement.textContent = newTitle;
                    }
                    
                    hideEditTitleForm(photoId);
                    showNotification('Название фото обновлено', 'success');
                } else {
                    showNotification('Ошибка: ' + (data.message || 'Неизвестная ошибка'), 'error');
                }
            })
            .catch(error => {
                console.error('Ошибка обновления названия:', error);
                showNotification('Произошла ошибка при обновлении: ' + error.message, 'error');
            })
            .finally(() => {
                saveBtn.textContent = 'Сохранить';
                saveBtn.disabled = false;
            });
        }

        function addDeleteHandlers() {
            document.querySelectorAll('.delete-photo-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const photoId = this.getAttribute('data-photo-id');
                    deletePhoto(photoId, this);
                });
            });
        }

        function deletePhoto(photoId, button) {
            if (!confirm('Вы уверены, что хотите удалить это фото? Это действие нельзя отменить.')) {
                return;
            }
            
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            button.disabled = true;
            
            fetch('delete_photo.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `photo_id=${photoId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const photoCard = button.closest('.top-photo-card');
                    photoCard.style.opacity = '0.5';
                    
                    setTimeout(() => {
                        photoCard.remove();
                        
                        showNotification('Фото успешно удалено', 'success');
                        
                        const container = document.getElementById('user-photos');
                        if (container.children.length === 0) {
                            container.innerHTML = '<div class="empty-state"><i class="fas fa-camera"></i><p>Вы еще не загрузили фотографии</p></div>';
                        }
                        
                        updatePhotosCount();
                        
                    }, 500);
                    
                } else {
                    showNotification('Ошибка: ' + data.message, 'error');
                    button.innerHTML = '<i class="fas fa-times"></i>';
                    button.disabled = false;
                }
            })
            .catch(error => {
                console.error('Ошибка удаления:', error);
                showNotification('Произошла ошибка при удалении', 'error');
                button.innerHTML = '<i class="fas fa-times"></i>';
                button.disabled = false;
            });
        }

        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.innerHTML = `
                <div style="position: fixed; top: 20px; right: 20px; background: ${type === 'success' ? '#4CAF50' : type === 'error' ? '#f44336' : '#2196F3'}; color: white; padding: 15px 20px; border-radius: 5px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); z-index: 10000; max-width: 300px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <i class="fas ${type === 'success' ? 'fa-check' : type === 'error' ? 'fa-exclamation-triangle' : 'fa-info'}"></i>
                        <span>${message}</span>
                    </div>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.remove();
            }, 5000);
        }
        
        // Обработка загрузки файлов
        const uploadForm = document.getElementById('upload-form');
        if (uploadForm) {
            fileInput.addEventListener('change', function() {
                const fileList = document.getElementById('file-list');
                fileList.innerHTML = '';
                
                if (this.files.length > 0) {
                    fileList.innerHTML = '<p>Выбрано файлов: ' + this.files.length + '</p>';
                    
                    if (this.files.length > 1) {
                        fileList.innerHTML += '<p style="color: #ff9800; margin-top: 10px;">⚠️ Для нескольких фото будет использовано одно название</p>';
                    }
                }
            });
            
            uploadArea.addEventListener('dragover', function(e) {
                e.preventDefault();
                uploadArea.classList.add('active');
            });
            
            uploadArea.addEventListener('dragleave', function() {
                uploadArea.classList.remove('active');
            });
            
            uploadArea.addEventListener('drop', function(e) {
                e.preventDefault();
                uploadArea.classList.remove('active');
                const files = e.dataTransfer.files;
                fileInput.files = files;
                
                const fileList = document.getElementById('file-list');
                fileList.innerHTML = '<p>Выбрано файлов: ' + files.length + '</p>';
                
                if (files.length > 1) {
                    fileList.innerHTML += '<p style="color: #ff9800; margin-top: 10px;">⚠️ Для нескольких фото будет использовано одно название</p>';
                }
            });
            
            uploadForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                const statusDiv = document.getElementById('upload-status');
                const files = fileInput.files;
                const photoTitle = document.getElementById('photo-title').value;
                
                if (files.length === 0) {
                    statusDiv.innerHTML = '<p style="color: red;">Выберите файлы для загрузки</p>';
                    return;
                }
                
                if (photoTitle.trim()) {
                    formData.append('photo_title', photoTitle.trim());
                } else {
                    formData.append('photo_title', 'Без названия');
                }
                
                statusDiv.innerHTML = '<p>Загрузка ' + files.length + ' файлов...</p>';
                
                fetch('upload.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        statusDiv.innerHTML = '<p style="color: green;">✓ Успешно загружено ' + data.uploaded_count + ' файлов</p>';
                        uploadForm.reset();
                        document.getElementById('file-list').innerHTML = '';
                        document.getElementById('photo-title').value = '';
                        loadUserPhotos();
                        loadCurrentPhoto();
                    } else {
                        let errorMsg = 'Ошибка загрузки';
                        if (data.errors && data.errors.length > 0) {
                            errorMsg += ': ' + data.errors.join(', ');
                        }
                        statusDiv.innerHTML = '<p style="color: red;">✗ ' + errorMsg + '</p>';
                    }
                })
                .catch(error => {
                    console.error('Ошибка:', error);
                    statusDiv.innerHTML = '<p style="color: red;">✗ Произошла ошибка при загрузке</p>';
                });
            });
        }
        
        // Инициализация
        if (document.getElementById('rating-section')) {
            loadCurrentPhoto();
        }
        
        // Модальные окна
        if (registerBtn) registerBtn.addEventListener('click', () => registerModal.classList.add('active'));
        if (welcomeRegisterBtn) welcomeRegisterBtn.addEventListener('click', () => registerModal.classList.add('active'));
        if (loginBtn) loginBtn.addEventListener('click', () => loginModal.classList.add('active'));
        if (welcomeLoginBtn) welcomeLoginBtn.addEventListener('click', () => loginModal.classList.add('active'));
        
        registerClose.addEventListener('click', () => registerModal.classList.remove('active'));
        loginClose.addEventListener('click', () => loginModal.classList.remove('active'));
        
        switchToLogin.addEventListener('click', () => {
            registerModal.classList.remove('active');
            loginModal.classList.add('active');
        });
        
        switchToRegister.addEventListener('click', () => {
            loginModal.classList.remove('active');
            registerModal.classList.add('active');
        });
        
        document.addEventListener('click', function(e) {
            if (e.target === registerModal) {
                registerModal.classList.remove('active');
            }
            if (e.target === loginModal) {
                loginModal.classList.remove('active');
            }
        });
        
        console.log('DOM fully loaded and parsed');
    });
    </script>
</body>
</html>
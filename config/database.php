<?php
$host = 'localhost';
$dbname = 'roster_app';
$username = 'root';
$password = '';

try {
    // Сначала подключаемся без выбора базы данных
    $pdo_temp = new PDO("mysql:host=$host", $username, $password);
    $pdo_temp->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Создаем базу данных если она не существует
    $pdo_temp->exec("CREATE DATABASE IF NOT EXISTS $dbname CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    
    // Теперь подключаемся к конкретной базе данных
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Устанавливаем кодировку
    $pdo->exec("SET NAMES 'utf8mb4'");
    $pdo->exec("SET CHARACTER SET utf8mb4");
    
} catch (PDOException $e) {
    die("Ошибка подключения к базе данных: " . $e->getMessage());
}

// Функция для создания таблиц если их нет
function createTablesIfNotExist($pdo) {
    $queries = [
        "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            login VARCHAR(50) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            avatar VARCHAR(255) DEFAULT NULL,
            STATUS VARCHAR(255) DEFAULT 'Новый пользователь',
            role ENUM('user', 'vip', 'moderator', 'admin') DEFAULT 'user',
            is_admin BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB",
        
        "CREATE TABLE IF NOT EXISTS photos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            filename VARCHAR(255) NOT NULL,
            original_name VARCHAR(255) NOT NULL,
            title VARCHAR(255) DEFAULT 'Без названия',
            upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB",
        
        "CREATE TABLE IF NOT EXISTS ratings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            photo_id INT,
            rating INT NOT NULL CHECK (rating >= 1 AND rating <= 10),
            rated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (photo_id) REFERENCES photos(id) ON DELETE CASCADE,
            UNIQUE KEY unique_rating (user_id, photo_id)
        ) ENGINE=InnoDB"
    ];
    
    foreach ($queries as $sql) {
        try {
            $pdo->exec($sql);
        } catch (PDOException $e) {
            error_log("Table creation error: " . $e->getMessage());
        }
    }
    
    // Добавляем недостающие столбцы если их нет
    $alter_queries = [
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS avatar VARCHAR(255) DEFAULT NULL",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS STATUS VARCHAR(255) DEFAULT 'Новый пользователь'",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS role ENUM('user', 'vip', 'moderator', 'admin') DEFAULT 'user'",
        "ALTER TABLE photos ADD COLUMN IF NOT EXISTS title VARCHAR(255) DEFAULT 'Без названия'"
    ];
    
    foreach ($alter_queries as $sql) {
        try {
            $pdo->exec($sql);
        } catch (PDOException $e) {
            // Игнорируем ошибки если столбцы уже существуют
            if (strpos($e->getMessage(), 'Duplicate column name') === false) {
                error_log("Column addition error: " . $e->getMessage());
            }
        }
    }
    
    // Создаем папки если их нет
    if (!file_exists('uploads')) {
        mkdir('uploads', 0755, true);
    }
    if (!file_exists('avatars')) {
        mkdir('avatars', 0755, true);
    }
}

// Создаем таблицы при подключении
createTablesIfNotExist($pdo);

// Создаем первого администратора если его нет
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE is_admin = TRUE");
    $admin_count = $stmt->fetch()['count'];
    
    if ($admin_count == 0) {
        $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (name, login, password, is_admin, role) VALUES (?, ?, ?, TRUE, 'admin')");
        $stmt->execute(['Администратор', 'admin', $admin_password]);
        
        error_log("Создан администратор: логин - admin, пароль - admin123");
    }
} catch (PDOException $e) {
    error_log("Admin creation error: " . $e->getMessage());
}
function getUserPermissions($pdo, $user_id) {
    try {
        $stmt = $pdo->prepare("SELECT role, is_admin FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return ['can_moderate' => false, 'can_delete' => false, 'is_vip' => false];
        }
        
        return [
            'can_moderate' => $user['is_admin'] || $user['role'] == 'moderator',
            'can_delete' => $user['is_admin'] || $user['role'] == 'moderator',
            'is_vip' => $user['role'] == 'vip' || $user['is_admin'],
            'is_admin' => (bool)$user['is_admin'],
            'role' => $user['role']
        ];
    } catch (PDOException $e) {
        return ['can_moderate' => false, 'can_delete' => false, 'is_vip' => false];
    }
}
?>
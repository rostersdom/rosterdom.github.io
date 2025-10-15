<?php
session_start();
require_once 'config/database.php';

// Включаем вывод ошибок для отладки
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

// Логируем запрос
file_put_contents('upload_log.txt', date('Y-m-d H:i:s') . " - Upload request started\n", FILE_APPEND);

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    file_put_contents('upload_log.txt', "User not authorized\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => 'Необходима авторизация']);
    exit;
}

file_put_contents('upload_log.txt', "User ID: " . $_SESSION['user_id'] . "\n", FILE_APPEND);

// Проверка метода запроса
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    file_put_contents('upload_log.txt', "Wrong method: " . $_SERVER['REQUEST_METHOD'] . "\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => 'Неверный метод запроса']);
    exit;
}

// Проверка загруженных файлов
if (!isset($_FILES['photos']) || empty($_FILES['photos']['name'][0])) {
    file_put_contents('upload_log.txt', "No files in \$_FILES or first file is empty\n", FILE_APPEND);
    echo json_encode(['success' => false, 'message' => 'Файлы не получены или файлы пустые']);
    exit;
}

file_put_contents('upload_log.txt', "Files count: " . count($_FILES['photos']['name']) . "\n", FILE_APPEND);

// Получаем название фото из формы
$photo_title = 'Без названия'; // Значение по умолчанию
if (!empty($_POST['photo_title'])) {
    $photo_title = trim($_POST['photo_title']);
    // Ограничиваем длину названия
    if (strlen($photo_title) > 50) {
        $photo_title = substr($photo_title, 0, 50);
    }
    file_put_contents('upload_log.txt', "Photo title: " . $photo_title . "\n", FILE_APPEND);
} else {
    file_put_contents('upload_log.txt', "No photo title provided, using default\n", FILE_APPEND);
}

// Создаем папку uploads если не существует
$upload_dir = 'uploads';
if (!file_exists($upload_dir)) {
    if (!mkdir($upload_dir, 0755, true)) {
        file_put_contents('upload_log.txt', "Failed to create upload directory\n", FILE_APPEND);
        echo json_encode(['success' => false, 'message' => 'Не удалось создать папку для загрузки']);
        exit;
    }
}

// Проверяем права доступа
if (!is_writable($upload_dir)) {
    // Пытаемся исправить права
    chmod($upload_dir, 0755);
    if (!is_writable($upload_dir)) {
        file_put_contents('upload_log.txt', "Upload directory not writable\n", FILE_APPEND);
        echo json_encode(['success' => false, 'message' => 'Папка uploads недоступна для записи']);
        exit;
    }
}

$uploaded_files = [];
$errors = [];

// Обрабатываем каждый файл
foreach ($_FILES['photos']['name'] as $key => $name) {
    // Пропускаем пустые файлы
    if (empty($name)) {
        file_put_contents('upload_log.txt', "Skipping empty file at index: " . $key . "\n", FILE_APPEND);
        continue;
    }
    
    file_put_contents('upload_log.txt', "Processing file: " . $name . " (index: " . $key . ")\n", FILE_APPEND);
    
    $tmp_name = $_FILES['photos']['tmp_name'][$key];
    $error = $_FILES['photos']['error'][$key];
    $size = $_FILES['photos']['size'][$key];
    
    // Проверяем, был ли файл действительно загружен
    if (!is_uploaded_file($tmp_name)) {
        $error_msg = "Файл не был загружен через HTTP POST: {$name}";
        $errors[] = $error_msg;
        file_put_contents('upload_log.txt', $error_msg . "\n", FILE_APPEND);
        continue;
    }
    
    // Проверяем ошибки загрузки
    if ($error !== UPLOAD_ERR_OK) {
        $error_messages = [
            UPLOAD_ERR_INI_SIZE => 'Размер файла превышает разрешенный директивой upload_max_filesize',
            UPLOAD_ERR_FORM_SIZE => 'Размер файла превышает разрешенный значением MAX_FILE_SIZE в форме',
            UPLOAD_ERR_PARTIAL => 'Файл был загружен только частично',
            UPLOAD_ERR_NO_FILE => 'Файл не был загружен',
            UPLOAD_ERR_NO_TMP_DIR => 'Отсутствует временная папка',
            UPLOAD_ERR_CANT_WRITE => 'Не удалось записать файл на диск',
            UPLOAD_ERR_EXTENSION => 'PHP расширение остановило загрузку файла'
        ];
        
        $error_msg = "Ошибка загрузки файла {$name}: " . ($error_messages[$error] ?? "Неизвестная ошибка (код {$error})");
        $errors[] = $error_msg;
        file_put_contents('upload_log.txt', $error_msg . "\n", FILE_APPEND);
        continue;
    }
    
    // Проверяем размер файла (5MB)
    $max_file_size = 5 * 1024 * 1024; // 5MB в байтах
    if ($size > $max_file_size) {
        $error_msg = "Файл слишком большой: {$name} (" . round($size / 1024 / 1024, 2) . " MB)";
        $errors[] = $error_msg;
        file_put_contents('upload_log.txt', $error_msg . "\n", FILE_APPEND);
        continue;
    }
    
    // Проверяем тип файла
    $file_info = finfo_open(FILEINFO_MIME_TYPE);
    $file_type = finfo_file($file_info, $tmp_name);
    finfo_close($file_info);
    
    $allowed_types = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png', 
        'image/gif' => 'gif',
        'image/webp' => 'webp',
        'image/svg+xml' => 'svg'
    ];
    
    if (!array_key_exists($file_type, $allowed_types)) {
        $error_msg = "Недопустимый тип файла: {$name} ({$file_type})";
        $errors[] = $error_msg;
        file_put_contents('upload_log.txt', $error_msg . "\n", FILE_APPEND);
        continue;
    }
    
    // Получаем расширение из MIME типа для безопасности
    $extension = $allowed_types[$file_type];
    
    // Генерируем уникальное имя файла
    $filename = uniqid() . '_' . $_SESSION['user_id'] . '.' . $extension;
    $upload_path = $upload_dir . '/' . $filename;
    
    file_put_contents('upload_log.txt', "Moving file to: " . $upload_path . "\n", FILE_APPEND);
    
    // Перемещаем файл
    if (move_uploaded_file($tmp_name, $upload_path)) {
        file_put_contents('upload_log.txt', "File moved successfully\n", FILE_APPEND);
        
        // Устанавливаем правильные права для файла
        chmod($upload_path, 0644);
        
        // Сохраняем в базу данных
        try {
            // Проверяем структуру таблицы photos
            $stmt_check = $pdo->prepare("SHOW COLUMNS FROM photos");
            $stmt_check->execute();
            $columns = $stmt_check->fetchAll(PDO::FETCH_COLUMN);
            
            file_put_contents('upload_log.txt', "Available columns in photos table: " . implode(', ', $columns) . "\n", FILE_APPEND);
            
            if (in_array('title', $columns)) {
                // Если столбец title существует
                $stmt = $pdo->prepare("INSERT INTO photos (user_id, filename, original_name, title, upload_date) VALUES (?, ?, ?, ?, NOW())");
                $stmt->execute([$_SESSION['user_id'], $filename, $name, $photo_title]);
                file_put_contents('upload_log.txt', "File saved to database with title: " . $photo_title . "\n", FILE_APPEND);
            } else {
                // Если столбец title не существует, сохраняем без него
                $stmt = $pdo->prepare("INSERT INTO photos (user_id, filename, original_name, upload_date) VALUES (?, ?, ?, NOW())");
                $stmt->execute([$_SESSION['user_id'], $filename, $name]);
                file_put_contents('upload_log.txt', "File saved to database without title (column doesn't exist)\n", FILE_APPEND);
            }
            
            $uploaded_files[] = [
                'filename' => $filename,
                'original_name' => $name,
                'title' => $photo_title,
                'size' => $size
            ];
            
        } catch (PDOException $e) {
            $error_msg = "Ошибка базы данных для файла {$name}: " . $e->getMessage();
            $errors[] = $error_msg;
            file_put_contents('upload_log.txt', $error_msg . "\n", FILE_APPEND);
            file_put_contents('upload_log.txt', "Error details: " . $e->getTraceAsString() . "\n", FILE_APPEND);
            
            // Удаляем файл если не удалось сохранить в БД
            if (file_exists($upload_path)) {
                unlink($upload_path);
                file_put_contents('upload_log.txt', "Deleted file due to database error: " . $upload_path . "\n", FILE_APPEND);
            }
        }
    } else {
        $error_msg = "Ошибка перемещения файла: {$name}. Проверьте права доступа к папке uploads.";
        $errors[] = $error_msg;
        file_put_contents('upload_log.txt', $error_msg . "\n", FILE_APPEND);
        
        // Дополнительная информация об ошибке
        $upload_errors = [
            UPLOAD_ERR_OK => 'Нет ошибок',
            UPLOAD_ERR_INI_SIZE => 'Размер файла превышает upload_max_filesize',
            UPLOAD_ERR_FORM_SIZE => 'Размер файла превышает MAX_FILE_SIZE',
            UPLOAD_ERR_PARTIAL => 'Файл загружен частично',
            UPLOAD_ERR_NO_FILE => 'Файл не был загружен',
            UPLOAD_ERR_NO_TMP_DIR => 'Отсутствует временная папка',
            UPLOAD_ERR_CANT_WRITE => 'Не удалось записать файл на диск',
            UPLOAD_ERR_EXTENSION => 'Расширение PHP остановило загрузку'
        ];
        
        file_put_contents('upload_log.txt', "Upload error code: " . $error . " - " . ($upload_errors[$error] ?? 'Неизвестная ошибка') . "\n", FILE_APPEND);
        file_put_contents('upload_log.txt', "Temporary file exists: " . (file_exists($tmp_name) ? 'yes' : 'no') . "\n", FILE_APPEND);
        file_put_contents('upload_log.txt', "Upload directory writable: " . (is_writable($upload_dir) ? 'yes' : 'no') . "\n", FILE_APPEND);
    }
}

// Формируем ответ
file_put_contents('upload_log.txt', "Upload completed. Success: " . count($uploaded_files) . ", Errors: " . count($errors) . "\n\n", FILE_APPEND);

if (count($uploaded_files) > 0) {
    $response = [
        'success' => true,
        'message' => 'Успешно загружено файлов: ' . count($uploaded_files),
        'uploaded_count' => count($uploaded_files),
        'uploaded_files' => $uploaded_files,
        'photo_title' => $photo_title
    ];
    
    if (count($errors) > 0) {
        $response['warnings'] = $errors;
        $response['message'] .= ' (с предупреждениями)';
    }
} else {
    $response = [
        'success' => false,
        'message' => 'Не удалось загрузить ни одного файла',
        'errors' => $errors
    ];
    
    // Если есть конкретные ошибки, добавляем их в сообщение
    if (count($errors) > 0) {
        $response['message'] .= ': ' . implode(', ', array_slice($errors, 0, 3));
        if (count($errors) > 3) {
            $response['message'] .= '...';
        }
    }
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>
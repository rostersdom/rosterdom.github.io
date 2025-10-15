<?php
session_start();
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $login = $_POST['login'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Валидация
    if ($password !== $confirm_password) {
        header('Location: index.php?error=password_mismatch');
        exit;
    }
    
    // Проверка существующего пользователя
    $stmt = $pdo->prepare("SELECT id FROM users WHERE login = ?");
    $stmt->execute([$login]);
    
    if ($stmt->fetch()) {
        header('Location: index.php?error=user_exists');
        exit;
    }
    
    // Создание пользователя
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (name, login, password) VALUES (?, ?, ?)");
    $stmt->execute([$name, $login, $hashed_password]);
    
    $_SESSION['user_id'] = $pdo->lastInsertId();
    $_SESSION['user_name'] = $name;
    
    header('Location: index.php');
    exit;
}
?>
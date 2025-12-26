<?php
session_start();

// Настройки подключения к БД
define('DB_HOST', 'localhost');
define('DB_USER', 'Erofeeva');
define('DB_PASS', '12345f');
define('DB_NAME', 'exam');

// Подключение к БД
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Ошибка подключения: " . $e->getMessage());
}

// Проверка авторизации
function checkAuth() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit();
    }
}

// Получение информации о текущем пользователе
function getCurrentUser($pdo) {
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Редирект по ролям
function redirectByRole($role) {
    switch ($role) {
        case 'student':
            header('Location: student/index.php');
            break;
        case 'teacher':
            header('Location: teacher/index.php');
            break;
        case 'admin':
            header('Location: admin/index.php');
            break;
    }
    exit();
}
?>
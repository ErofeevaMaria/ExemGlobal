<?php
require_once 'config.php';

// Если пользователь уже авторизован, перенаправляем его
if (isset($_SESSION['user_id'])) {
    $user = getCurrentUser($pdo);
    if ($user) {
        redirectByRole($user['role']);
    }
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = 'student'; // По умолчанию регистрируем как студента
    
    // Валидация
    if (empty($full_name) || empty($email) || empty($password)) {
        $error = 'Пожалуйста, заполните все обязательные поля';
    } elseif ($password !== $confirm_password) {
        $error = 'Пароли не совпадают';
    } elseif (strlen($password) < 6) {
        $error = 'Пароль должен содержать минимум 6 символов';
    } else {
        // Проверяем, нет ли уже пользователя с таким email
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        
        if ($stmt->fetch()) {
            $error = 'Пользователь с таким email уже существует';
        } else {
            // Хешируем пароль
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Создаем пользователя
            $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, ?)");
            
            try {
                $stmt->execute([$full_name, $email, $hashed_password, $role]);
                $success = 'Регистрация успешна! Теперь вы можете войти.';
                
                // Очищаем форму
                $_POST = [];
            } catch (PDOException $e) {
                $error = 'Ошибка при регистрации: ' . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-header">
            <h1>Регистрация</h1>
            <p>Создание нового аккаунта</p>
        </div>
        
        <div class="auth-body">
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="full_name">ФИО:</label>
                    <input type="text" id="full_name" name="full_name" class="form-control" required 
                           value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" class="form-control" required 
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">Пароль:</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Подтвердите пароль:</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                </div>
                
                <button type="submit" class="btn" style="width: 100%;">Зарегистрироваться</button>
            </form>
            
            <div class="auth-links">
                <p>Уже есть аккаунт? <a href="login.php">Войдите</a></p>
                <p><a href="index.php">Вернуться на главную</a></p>
            </div>
        </div>
    </div>
</body>
</html>
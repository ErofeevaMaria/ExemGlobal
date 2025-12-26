<?php
require_once 'config.php';
if (isset($_SESSION['user_id'])) {
    $user = getCurrentUser($pdo);
    if ($user) {
        redirectByRole($user['role']);
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Пожалуйста, заполните все поля';
    } else {
        // Ищем пользователя
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            // Авторизация успешна
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            
            // Редирект в зависимости от роли
            redirectByRole($user['role']);
        } else {
            $error = 'Неверный email или пароль';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход в систему</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-header">
            <h1>Вход в систему</h1>
            <p>Университетская система управления</p>
        </div>
        
        <div class="auth-body">
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" class="form-control" required 
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">Пароль:</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>
                
                <button type="submit" class="btn" style="width: 100%;">Войти</button>
            </form>
            
            <div class="auth-links">
                <p>Нет аккаунта? <a href="register.php">Зарегистрируйтесь</a></p>
                <p><a href="index.php">Вернуться на главную</a></p>
            </div>
        </div>
    </div>
</body>
</html>
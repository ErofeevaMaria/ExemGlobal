<?php
require_once 'config.php';

// Если пользователь уже авторизован, перенаправляем его
if (isset($_SESSION['user_id'])) {
    $user = getCurrentUser($pdo);
    if ($user) {
        redirectByRole($user['role']);
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Университетская система</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <div class="container">
            <div class="header-content">
                <a href="index.php" class="logo">Университетская система</a>
                <nav class="user-menu">
                    <a href="login.php" class="btn">Войти</a>
                    <a href="register.php" class="btn btn-secondary">Регистрация</a>
                </nav>
            </div>
        </div>
    </header>

    <main>
        <div class="container">
            <div class="row">
                <div class="card">
                    <h2>Для студентов</h2>
                    <ul style="padding-left: 20px; margin-top: 15px;">
                        <li>Просмотр расписания занятий</li>
                        <li>Доступ к оценкам</li>
                        <li>Общение с преподавателями</li>
                        <li>Учебные материалы</li>
                    </ul>
                </div>
                
                <div class="card">
                    <h2>Для преподавателей</h2>
                    <ul style="padding-left: 20px; margin-top: 15px;">
                        <li>Ведение расписания</li>
                        <li>Выставление оценок</li>
                        <li>Общение со студентами</li>
                        <li>Учебные планы</li>
                    </ul>
                </div>
                
                <div class="card">
                    <h2>Для администраторов</h2>
                    <ul style="padding-left: 20px; margin-top: 15px;">
                        <li>Управление пользователями</li>
                        <li>Редактирование расписания</li>
                        <li>Управление группами</li>
                        <li>Статистика и отчеты</li>
                    </ul>
                </div>
            </div>
            
            <div class="card">
                <h2>О системе</h2>
                <p>Университетская система управления учебным процессом. Простой и удобный интерфейс для студентов, преподавателей и администраторов вуза.</p>
                
                <div style="margin-top: 20px;">
                    <a href="login.php" class="btn" style="margin-right: 10px;">Войти в систему</a>
                    <a href="register.php" class="btn btn-secondary">Зарегистрироваться</a>
                </div>
            </div>
        </div>
    </main>

    <footer style="background: #2e7d32; color: white; padding: 20px 0; text-align: center; margin-top: 50px;">
        <div class="container">
            <p>Университетская система &copy; 2025</p>
            <p style="margin-top: 10px; font-size: 0.9em; opacity: 0.8;">Тестовый прототип для экзамена</p>
        </div>
    </footer>
</body>
</html>
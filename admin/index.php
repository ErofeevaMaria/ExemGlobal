<?php
require_once '../config.php';
checkAuth();

$user = getCurrentUser($pdo);
if ($user['role'] != 'admin') {
    header('Location: ../index.php');
    exit();
}
// Статистика для админа
$stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
$total_users = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

$stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'student'");
$total_students = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

$stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'teacher'");
$total_teachers = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

$stmt = $pdo->query("SELECT COUNT(*) as count FROM lessons");
$total_lessons = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель администратора</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <header>
        <div class="container">
            <div class="header-content">
                <a href="index.php" class="logo">Администратор</a>
                <div class="user-menu">
                    <div class="user-info">
                        <span><?php echo htmlspecialchars($user['full_name']); ?></span>
                    </div>
                    <a href="../logout.php" class="btn btn-secondary">Выйти</a>
                </div>
            </div>
        </div>
    </header>

    <main>
        <div class="container">
            <nav class="nav-tabs">
                <a href="index.php" class="nav-tab active">Главная</a>
                <a href="users.php" class="nav-tab">Пользователи</a>
                <a href="schedule.php" class="nav-tab">Расписание</a>
                <a href="groups.php" class="nav-tab">Группы</a>
                <a href="subjects.php" class="nav-tab">Предметы</a>
           <a href="backup.php" class="nav-tab">Бэкап системы</a> 
            </nav>

            <div class="row">
                <div class="card">
                    <h2>Статистика системы</h2>
                    <div style="font-size: 1.2em; padding: 20px 0;">
                        <p>Всего пользователей: <strong><?php echo $total_users; ?></strong></p>
                        <p>Студентов: <strong><?php echo $total_students; ?></strong></p>
                        <p>Преподавателей: <strong><?php echo $total_teachers; ?></strong></p>
                        <p>Занятий: <strong><?php echo $total_lessons; ?></strong></p>
                    </div>
                </div>

                <div class="card">
                    <h2>Быстрые действия</h2>
                    <div style="display: flex; flex-direction: column; gap: 10px; margin-top: 20px;">
                        <a href="users.php?action=add" class="btn">Добавить пользователя</a>
                        <a href="schedule.php?action=add" class="btn">Добавить занятие</a>
                        <a href="groups.php" class="btn">Управление группами</a>
                        <a href="subjects.php" class="btn">Управление предметами</a>
                    <a href="backup.php" class="btn" style="background-color: #ff9800;">Создать бэкап</a> 
                    </div>
                </div>
            </div>

            <div class="card">
                <h2>Последние действия</h2>
                <p>Здесь может отображаться лог последних действий в системе...</p>
            </div>
        </div>
    </main>

    <footer style="background: #2e7d32; color: white; padding: 20px 0; text-align: center; margin-top: 50px;">
        <div class="container">
            <p>Университетская система &copy; 2025</p>
        </div>
    </footer>
</body>
</html>
<?php
require_once '../config.php';
checkAuth();

$user = getCurrentUser($pdo);
if ($user['role'] != 'teacher') {
    header('Location: ../index.php');
    exit();
}

// Получаем расписание преподавателя
$stmt = $pdo->prepare("
    SELECT l.*, s.name as subject_name, g.name as group_name
    FROM lessons l
    JOIN subjects s ON l.subject_id = s.id
    JOIN groups g ON l.group_id = g.id
    WHERE l.teacher_id = ?
    ORDER BY l.date_time DESC
");
$stmt->execute([$user['id']]);
$lessons = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Расписание преподавателя</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <header>
        <div class="container">
            <div class="header-content">
                <a href="index.php" class="logo">Преподаватель</a>
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
                <a href="index.php" class="nav-tab">Главная</a>
                <a href="schedule.php" class="nav-tab active">Расписание</a>
                <a href="grades.php" class="nav-tab">Оценки</a>
                <a href="messages.php" class="nav-tab">Сообщения</a>
            </nav>

            <div class="card">
                <h2>Расписание занятий</h2>
                <?php if (count($lessons) > 0): ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Дата и время</th>
                                <th>Предмет</th>
                                <th>Группа</th>
                                <th>Тема</th>
                                <th>Аудитория</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($lessons as $lesson): ?>
                                <tr>
                                    <td><?php echo date('d.m.Y H:i', strtotime($lesson['date_time'])); ?></td>
                                    <td><?php echo htmlspecialchars($lesson['subject_name']); ?></td>
                                    <td><?php echo htmlspecialchars($lesson['group_name']); ?></td>
                                    <td><?php echo htmlspecialchars($lesson['topic']); ?></td>
                                    <td><?php echo htmlspecialchars($lesson['classroom']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>Нет занятий в расписании</p>
                <?php endif; ?>
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
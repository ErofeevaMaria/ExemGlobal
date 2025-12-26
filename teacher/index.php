<?php
require_once '../config.php';
checkAuth();

$user = getCurrentUser($pdo);
if ($user['role'] != 'teacher') {
    header('Location: ../index.php');
    exit();
}

// количество студентов у преподавателя
$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT st.user_id) as student_count
    FROM lessons l
    JOIN students st ON l.group_id = st.group_id
    WHERE l.teacher_id = ?
");
$stmt->execute([$user['id']]);
$student_count = $stmt->fetch(PDO::FETCH_ASSOC)['student_count'];

// ближайшие занятия
$stmt = $pdo->prepare("
    SELECT l.*, s.name as subject_name, g.name as group_name
    FROM lessons l
    JOIN subjects s ON l.subject_id = s.id
    JOIN groups g ON l.group_id = g.id
    WHERE l.teacher_id = ? AND l.date_time > NOW()
    ORDER BY l.date_time ASC
    LIMIT 5
");
$stmt->execute([$user['id']]);
$lessons = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Личный кабинет преподавателя</title>
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
                <a href="index.php" class="nav-tab active">Главная</a>
                <a href="schedule.php" class="nav-tab">Расписание</a>
                <a href="grades.php" class="nav-tab">Оценки</a>
                <a href="messages.php" class="nav-tab">Сообщения</a>
            </nav>

            <div class="row">
                <div class="card">
                    <h2>Статистика</h2>
                    <div style="font-size: 1.2em; padding: 20px 0;">
                        <p>Количество студентов: <strong><?php echo $student_count; ?></strong></p>
                    </div>
                </div>

                <div class="card">
                    <h2>Ближайшие занятия</h2>
                    <?php if (count($lessons) > 0): ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Дата и время</th>
                                    <th>Предмет</th>
                                    <th>Группа</th>
                                    <th>Тема</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($lessons as $lesson): ?>
                                    <tr>
                                        <td><?php echo date('d.m.Y H:i', strtotime($lesson['date_time'])); ?></td>
                                        <td><?php echo htmlspecialchars($lesson['subject_name']); ?></td>
                                        <td><?php echo htmlspecialchars($lesson['group_name']); ?></td>
                                        <td><?php echo htmlspecialchars($lesson['topic']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>Нет предстоящих занятий</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card">
                <h2>Быстрые действия</h2>
                <div style="display: flex; gap: 15px; margin-top: 20px;">
                    <a href="schedule.php" class="btn">Мое расписание</a>
                    <a href="grades.php" class="btn">Выставить оценку</a>
                    <a href="messages.php" class="btn">Сообщения</a>
                </div>
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
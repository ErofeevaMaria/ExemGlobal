<?php
require_once '../config.php';
checkAuth();

$user = getCurrentUser($pdo);
if ($user['role'] != 'student') {
    header('Location: ../index.php');
    exit();
}

// Получаем группу студента
$stmt = $pdo->prepare("SELECT group_id FROM students WHERE user_id = ?");
$stmt->execute([$user['id']]);
$student_group = $stmt->fetch(PDO::FETCH_ASSOC);

// Получаем расписание для группы студента
if ($student_group) {
    $stmt = $pdo->prepare("
        SELECT l.*, s.name as subject_name, u.full_name as teacher_name
        FROM lessons l
        JOIN subjects s ON l.subject_id = s.id
        JOIN users u ON l.teacher_id = u.id
        WHERE l.group_id = ?
        ORDER BY l.date_time DESC
    ");
    $stmt->execute([$student_group['group_id']]);
    $lessons = $stmt->fetchAll();
} else {
    $lessons = [];
}

// Получаем расписание на текущую неделю
$current_week_start = date('Y-m-d', strtotime('monday this week'));
$current_week_end = date('Y-m-d', strtotime('sunday this week'));

if ($student_group) {
    $stmt = $pdo->prepare("
        SELECT l.*, s.name as subject_name, u.full_name as teacher_name,
               DAYNAME(l.date_time) as day_name
        FROM lessons l
        JOIN subjects s ON l.subject_id = s.id
        JOIN users u ON l.teacher_id = u.id
        WHERE l.group_id = ? 
        AND DATE(l.date_time) BETWEEN ? AND ?
        ORDER BY l.date_time ASC
    ");
    $stmt->execute([$student_group['group_id'], $current_week_start, $current_week_end]);
    $week_lessons = $stmt->fetchAll();
} else {
    $week_lessons = [];
}

// Группируем занятия по дням недели
$lessons_by_day = [];
foreach ($week_lessons as $lesson) {
    $day = date('d.m.Y', strtotime($lesson['date_time']));
    if (!isset($lessons_by_day[$day])) {
        $lessons_by_day[$day] = [];
    }
    $lessons_by_day[$day][] = $lesson;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Расписание студента</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .day-schedule {
            margin-bottom: 30px;
        }
        .day-header {
            background-color: #e8f5e9;
            padding: 10px 15px;
            border-radius: 6px;
            margin-bottom: 10px;
            font-weight: bold;
            color: #2e7d32;
        }
        .lesson-item {
            background: white;
            border-left: 4px solid #4caf50;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 0 6px 6px 0;
        }
        .lesson-time {
            font-weight: bold;
            color: #2e7d32;
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <div class="header-content">
                <a href="index.php" class="logo">Студент</a>
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
                <h2>Расписание на текущую неделю</h2>
                <?php if (count($lessons_by_day) > 0): ?>
                    <?php foreach ($lessons_by_day as $day => $day_lessons): ?>
                        <div class="day-schedule">
                            <div class="day-header">
                                <?php echo date('l', strtotime($day)); ?>, <?php echo $day; ?>
                            </div>
                            <?php foreach ($day_lessons as $lesson): ?>
                                <div class="lesson-item">
                                    <div class="lesson-time">
                                        <?php echo date('H:i', strtotime($lesson['date_time'])); ?>
                                    </div>
                                    <div><strong><?php echo htmlspecialchars($lesson['subject_name']); ?></strong></div>
                                    <div>Преподаватель: <?php echo htmlspecialchars($lesson['teacher_name']); ?></div>
                                    <div>Тема: <?php echo htmlspecialchars($lesson['topic']); ?></div>
                                    <div>Аудитория: <?php echo htmlspecialchars($lesson['classroom']); ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>На этой неделе занятий нет</p>
                <?php endif; ?>
            </div>

            <div class="card">
                <h2>Все занятия</h2>
                <?php if (count($lessons) > 0): ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Дата и время</th>
                                <th>Предмет</th>
                                <th>Преподаватель</th>
                                <th>Тема</th>
                                <th>Аудитория</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($lessons as $lesson): ?>
                                <tr>
                                    <td><?php echo date('d.m.Y H:i', strtotime($lesson['date_time'])); ?></td>
                                    <td><?php echo htmlspecialchars($lesson['subject_name']); ?></td>
                                    <td><?php echo htmlspecialchars($lesson['teacher_name']); ?></td>
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
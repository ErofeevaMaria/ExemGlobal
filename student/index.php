<?php
require_once '../config.php';
checkAuth();

$user = getCurrentUser($pdo);
if ($user['role'] != 'student') {
    header('Location: ../index.php');
    exit();
}

// группа студента
$stmt = $pdo->prepare("
    SELECT g.name as group_name 
    FROM students s
    JOIN groups g ON s.group_id = g.id
    WHERE s.user_id = ?
");
$stmt->execute([$user['id']]);
$group = $stmt->fetch(PDO::FETCH_ASSOC);

//средний балл студента
$stmt = $pdo->prepare("
    SELECT AVG(CAST(grade AS DECIMAL(10,2))) as avg_grade 
    FROM grades 
    WHERE student_id = ? AND grade REGEXP '^[0-9]+$'
");
$stmt->execute([$user['id']]);
$avg_grade = $stmt->fetch(PDO::FETCH_ASSOC)['avg_grade'];

//количество оценок
$stmt = $pdo->prepare("SELECT COUNT(*) as grade_count FROM grades WHERE student_id = ?");
$stmt->execute([$user['id']]);
$grade_count = $stmt->fetch(PDO::FETCH_ASSOC)['grade_count'];

// Получаем ближайшие занятия
$stmt = $pdo->prepare("
    SELECT l.*, s.name as subject_name, u.full_name as teacher_name
    FROM lessons l
    JOIN subjects s ON l.subject_id = s.id
    JOIN users u ON l.teacher_id = u.id
    WHERE l.group_id = (SELECT group_id FROM students WHERE user_id = ?)
    AND l.date_time > NOW()
    ORDER BY l.date_time ASC
    LIMIT 5
");
$stmt->execute([$user['id']]);
$upcoming_lessons = $stmt->fetchAll();

// Получаем последние оценки
$stmt = $pdo->prepare("
    SELECT g.*, s.name as subject_name, u.full_name as teacher_name, l.date_time
    FROM grades g
    JOIN lessons l ON g.lesson_id = l.id
    JOIN subjects s ON l.subject_id = s.id
    JOIN users u ON l.teacher_id = u.id
    WHERE g.student_id = ?
    ORDER BY g.created_at DESC
    LIMIT 5
");
$stmt->execute([$user['id']]);
$recent_grades = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Личный кабинет студента</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <header>
        <div class="container">
            <div class="header-content">
                <a href="index.php" class="logo">Студент</a>
                <div class="user-menu">
                    <div class="user-info">
                        <span><?php echo htmlspecialchars($user['full_name']); ?></span>
                        <span class="status status-success"><?php echo htmlspecialchars($group['group_name'] ?? 'Без группы'); ?></span>
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
                    <h2>Основная информация</h2>
                    <div style="font-size: 1.2em; padding: 20px 0;">
                        <p><strong>ФИО:</strong> <?php echo htmlspecialchars($user['full_name']); ?></p>
                        <p><strong>Группа:</strong> <?php echo htmlspecialchars($group['group_name'] ?? 'Не указана'); ?></p>
                        <p><strong>Средний балл:</strong> <?php echo $avg_grade ? number_format($avg_grade, 2) : 'Нет оценок'; ?></p>
                        <p><strong>Всего оценок:</strong> <?php echo $grade_count; ?></p>
                    </div>
                </div>

                <div class="card">
                    <h2>Ближайшие занятия</h2>
                    <?php if (count($upcoming_lessons) > 0): ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Дата и время</th>
                                    <th>Предмет</th>
                                    <th>Преподаватель</th>
                                    <th>Тема</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($upcoming_lessons as $lesson): ?>
                                    <tr>
                                        <td><?php echo date('d.m.Y H:i', strtotime($lesson['date_time'])); ?></td>
                                        <td><?php echo htmlspecialchars($lesson['subject_name']); ?></td>
                                        <td><?php echo htmlspecialchars($lesson['teacher_name']); ?></td>
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
                <h2>Последние оценки</h2>
                <?php if (count($recent_grades) > 0): ?>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Дата</th>
                                <th>Предмет</th>
                                <th>Оценка</th>
                                <th>Преподаватель</th>
                                <th>Комментарий</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_grades as $grade): ?>
                                <tr>
                                    <td><?php echo date('d.m.Y', strtotime($grade['created_at'])); ?></td>
                                    <td><?php echo htmlspecialchars($grade['subject_name']); ?></td>
                                    <td>
                                        <span class="status <?php echo ($grade['grade'] == '5' || $grade['grade'] == 'зачет') ? 'status-success' : 'status-warning'; ?>">
                                            <?php echo htmlspecialchars($grade['grade']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($grade['teacher_name']); ?></td>
                                    <td><?php echo htmlspecialchars($grade['comment']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>Нет оценок</p>
                <?php endif; ?>
                <a href="grades.php" class="btn">Все оценки</a>
            </div>

            <div class="card">
                <h2>Быстрые действия</h2>
                <div style="display: flex; gap: 15px; margin-top: 20px;">
                    <a href="schedule.php" class="btn">Посмотреть расписание</a>
                    <a href="messages.php" class="btn">Написать преподавателю</a>
                    <a href="grades.php" class="btn">Мои оценки</a>
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
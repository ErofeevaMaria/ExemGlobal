<?php
require_once '../config.php';
checkAuth();

$user = getCurrentUser($pdo);
if ($user['role'] != 'student') {
    header('Location: ../index.php');
    exit();
}

// Получаем все оценки студента
$stmt = $pdo->prepare("
    SELECT g.*, s.name as subject_name, u.full_name as teacher_name, 
           l.date_time, l.topic
    FROM grades g
    JOIN lessons l ON g.lesson_id = l.id
    JOIN subjects s ON l.subject_id = s.id
    JOIN users u ON l.teacher_id = u.id
    WHERE g.student_id = ?
    ORDER BY g.created_at DESC
");
$stmt->execute([$user['id']]);
$grades = $stmt->fetchAll();

// Статистика по оценкам
$grade_stats = [];
foreach ($grades as $grade) {
    $subject = $grade['subject_name'];
    if (!isset($grade_stats[$subject])) {
        $grade_stats[$subject] = [
            'grades' => [],
            'count' => 0,
            'avg' => 0
        ];
    }
    
    // Преобразуем оценку в числовое значение если возможно
    $grade_value = $grade['grade'];
    if (is_numeric($grade_value)) {
        $grade_stats[$subject]['grades'][] = (float)$grade_value;
    } else {
        // Для нечисловых оценок (зачет/незачет) используем 5/2
        $grade_stats[$subject]['grades'][] = ($grade_value == 'зачет') ? 5 : 2;
    }
}

// Вычисляем средние для каждого предмета
foreach ($grade_stats as $subject => &$stats) {
    $stats['count'] = count($stats['grades']);
    $stats['avg'] = $stats['count'] > 0 ? array_sum($stats['grades']) / $stats['count'] : 0;
}

// Общая статистика
$total_grades = count($grades);
$numeric_grades = array_filter($grades, function($g) {
    return is_numeric($g['grade']);
});
$average_grade = $total_grades > 0 ? 
    array_sum(array_column($numeric_grades, 'grade')) / count($numeric_grades) : 0;
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Оценки студента</title>
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
                <a href="schedule.php" class="nav-tab">Расписание</a>
                <a href="grades.php" class="nav-tab active">Оценки</a>
                <a href="messages.php" class="nav-tab">Сообщения</a>
            </nav>

            <div class="row">
                <div class="card">
                    <h2>Статистика оценок</h2>
                    <div style="font-size: 1.2em; padding: 20px 0;">
                        <p><strong>Всего оценок:</strong> <?php echo $total_grades; ?></p>
                        <p><strong>Средний балл:</strong> <?php echo $average_grade ? number_format($average_grade, 2) : 'Нет оценок'; ?></p>
                        
                        <?php if (count($grade_stats) > 0): ?>
                            <h3 style="margin-top: 20px;">По предметам:</h3>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Предмет</th>
                                        <th>Кол-во оценок</th>
                                        <th>Средний балл</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($grade_stats as $subject => $stats): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($subject); ?></td>
                                            <td><?php echo $stats['count']; ?></td>
                                            <td><?php echo number_format($stats['avg'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card">
                    <h2>Все оценки</h2>
                    <?php if (count($grades) > 0): ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Дата занятия</th>
                                    <th>Предмет</th>
                                    <th>Оценка</th>
                                    <th>Преподаватель</th>
                                    <th>Тема занятия</th>
                                    <th>Комментарий</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($grades as $grade): ?>
                                    <tr>
                                        <td><?php echo date('d.m.Y', strtotime($grade['date_time'])); ?></td>
                                        <td><?php echo htmlspecialchars($grade['subject_name']); ?></td>
                                        <td>
                                            <span class="status <?php 
                                                echo ($grade['grade'] == '5' || $grade['grade'] == 'зачет') ? 'status-success' : 
                                                     ($grade['grade'] == '4' ? 'status-warning' : 'status-error');
                                            ?>">
                                                <?php echo htmlspecialchars($grade['grade']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($grade['teacher_name']); ?></td>
                                        <td><?php echo htmlspecialchars($grade['topic']); ?></td>
                                        <td><?php echo htmlspecialchars($grade['comment']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>У вас пока нет оценок</p>
                    <?php endif; ?>
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
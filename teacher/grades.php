<?php
require_once '../config.php';
checkAuth();

$user = getCurrentUser($pdo);
if ($user['role'] != 'teacher') {
    header('Location: ../index.php');
    exit();
}

// Получаем список занятий преподавателя для выпадающего списка
$stmt = $pdo->prepare("
    SELECT l.id, l.date_time, s.name as subject_name, g.name as group_name
    FROM lessons l
    JOIN subjects s ON l.subject_id = s.id
    JOIN groups g ON l.group_id = g.id
    WHERE l.teacher_id = ?
    ORDER BY l.date_time DESC
");
$stmt->execute([$user['id']]);
$lessons = $stmt->fetchAll();

// Получаем список студентов для выбранного занятия
$students = [];
if (isset($_GET['lesson_id'])) {
    $lesson_id = $_GET['lesson_id'];
    
    $stmt = $pdo->prepare("
        SELECT u.id, u.full_name
        FROM students st
        JOIN users u ON st.user_id = u.id
        JOIN lessons l ON st.group_id = l.group_id
        WHERE l.id = ?
        ORDER BY u.full_name
    ");
    $stmt->execute([$lesson_id]);
    $students = $stmt->fetchAll();
}

// Обработка выставления оценки
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_grade'])) {
    $student_id = $_POST['student_id'];
    $lesson_id = $_POST['lesson_id'];
    $grade = $_POST['grade'];
    $comment = $_POST['comment'] ?? '';
    
    // Проверяем, не выставлена ли уже оценка
    $stmt = $pdo->prepare("SELECT id FROM grades WHERE student_id = ? AND lesson_id = ?");
    $stmt->execute([$student_id, $lesson_id]);
    
    if ($stmt->fetch()) {
        $error = "Оценка уже выставлена этому студенту за это занятие";
    } else {
        // Добавляем оценку
        $stmt = $pdo->prepare("INSERT INTO grades (student_id, lesson_id, grade, comment) VALUES (?, ?, ?, ?)");
        $stmt->execute([$student_id, $lesson_id, $grade, $comment]);
        $success = "Оценка успешно выставлена";
    }
}

// Получаем последние выставленные оценки
$stmt = $pdo->prepare("
    SELECT g.*, u.full_name as student_name, l.date_time, s.name as subject_name
    FROM grades g
    JOIN users u ON g.student_id = u.id
    JOIN lessons l ON g.lesson_id = l.id
    JOIN subjects s ON l.subject_id = s.id
    WHERE l.teacher_id = ?
    ORDER BY g.created_at DESC
    LIMIT 10
");
$stmt->execute([$user['id']]);
$recent_grades = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Выставление оценок</title>
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
                <a href="schedule.php" class="nav-tab">Расписание</a>
                <a href="grades.php" class="nav-tab active">Оценки</a>
                <a href="messages.php" class="nav-tab">Сообщения</a>
            </nav>

            <div class="row">
                <div class="card">
                    <h2>Выставить оценку</h2>
                    
                    <?php if (isset($error)): ?>
                        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    
                    <?php if (isset($success)): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                    <?php endif; ?>
                    
                    <form method="GET" action="" style="margin-bottom: 20px;">
                        <div class="form-group">
                            <label for="lesson_id">Выберите занятие:</label>
                            <select id="lesson_id" name="lesson_id" class="form-control" onchange="this.form.submit()">
                                <option value="">-- Выберите занятие --</option>
                                <?php foreach ($lessons as $lesson): ?>
                                    <option value="<?php echo $lesson['id']; ?>" 
                                        <?php echo (isset($_GET['lesson_id']) && $_GET['lesson_id'] == $lesson['id']) ? 'selected' : ''; ?>>
                                        <?php echo date('d.m.Y', strtotime($lesson['date_time'])) . ' - ' . 
                                               htmlspecialchars($lesson['subject_name']) . ' (' . 
                                               htmlspecialchars($lesson['group_name']) . ')'; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </form>
                    
                    <?php if (isset($_GET['lesson_id']) && count($students) > 0): ?>
                        <form method="POST" action="">
                            <input type="hidden" name="lesson_id" value="<?php echo $_GET['lesson_id']; ?>">
                            
                            <div class="form-group">
                                <label for="student_id">Студент:</label>
                                <select id="student_id" name="student_id" class="form-control" required>
                                    <option value="">-- Выберите студента --</option>
                                    <?php foreach ($students as $student): ?>
                                        <option value="<?php echo $student['id']; ?>">
                                            <?php echo htmlspecialchars($student['full_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="grade">Оценка:</label>
                                <input type="text" id="grade" name="grade" class="form-control" required 
                                       placeholder="5, 4, 3, 2, зачет, незачет">
                            </div>
                            
                            <div class="form-group">
                                <label for="comment">Комментарий (необязательно):</label>
                                <textarea id="comment" name="comment" class="form-control" rows="3"></textarea>
                            </div>
                            
                            <button type="submit" name="add_grade" class="btn">Выставить оценку</button>
                        </form>
                    <?php elseif (isset($_GET['lesson_id'])): ?>
                        <p>Для выбранного занятия нет студентов в группе.</p>
                    <?php endif; ?>
                </div>

                <div class="card">
                    <h2>Последние выставленные оценки</h2>
                    <?php if (count($recent_grades) > 0): ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Дата</th>
                                    <th>Студент</th>
                                    <th>Предмет</th>
                                    <th>Оценка</th>
                                    <th>Комментарий</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_grades as $grade): ?>
                                    <tr>
                                        <td><?php echo date('d.m.Y', strtotime($grade['created_at'])); ?></td>
                                        <td><?php echo htmlspecialchars($grade['student_name']); ?></td>
                                        <td><?php echo htmlspecialchars($grade['subject_name']); ?></td>
                                        <td><?php echo htmlspecialchars($grade['grade']); ?></td>
                                        <td><?php echo htmlspecialchars($grade['comment']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>Еще нет выставленных оценок</p>
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
<?php
require_once '../config.php';
checkAuth();

$user = getCurrentUser($pdo);
if ($user['role'] != 'admin') {
    header('Location: ../index.php');
    exit();
}

// Получаем все занятия
$stmt = $pdo->query("
    SELECT l.*, s.name as subject_name, g.name as group_name, u.full_name as teacher_name
    FROM lessons l
    JOIN subjects s ON l.subject_id = s.id
    JOIN groups g ON l.group_id = g.id
    JOIN users u ON l.teacher_id = u.id
    ORDER BY l.date_time DESC
");
$lessons = $stmt->fetchAll();

// Получаем данные для форм
$stmt = $pdo->query("SELECT id, name FROM subjects ORDER BY name");
$subjects = $stmt->fetchAll();

$stmt = $pdo->query("SELECT id, name FROM groups ORDER BY name");
$groups = $stmt->fetchAll();

$stmt = $pdo->query("SELECT id, full_name FROM users WHERE role = 'teacher' ORDER BY full_name");
$teachers = $stmt->fetchAll();

// Обработка удаления занятия
if (isset($_GET['delete'])) {
    $lesson_id = $_GET['delete'];
    
    $stmt = $pdo->prepare("DELETE FROM lessons WHERE id = ?");
    $stmt->execute([$lesson_id]);
    
    header('Location: schedule.php?success=Занятие удалено');
    exit();
}

// Обработка добавления/редактирования занятия
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'] ?? 0;
    $subject_id = $_POST['subject_id'];
    $group_id = $_POST['group_id'];
    $teacher_id = $_POST['teacher_id'];
    $date_time = $_POST['date_time'];
    $topic = $_POST['topic'] ?? '';
    $classroom = $_POST['classroom'] ?? '';
    
    if ($id > 0) {
        // Редактирование
        $stmt = $pdo->prepare("
            UPDATE lessons 
            SET subject_id = ?, group_id = ?, teacher_id = ?, date_time = ?, topic = ?, classroom = ?
            WHERE id = ?
        ");
        $stmt->execute([$subject_id, $group_id, $teacher_id, $date_time, $topic, $classroom, $id]);
        $success = "Занятие обновлено";
    } else {
        // Добавление
        $stmt = $pdo->prepare("
            INSERT INTO lessons (subject_id, group_id, teacher_id, date_time, topic, classroom)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$subject_id, $group_id, $teacher_id, $date_time, $topic, $classroom]);
        $success = "Занятие добавлено";
    }
    
    header('Location: schedule.php?success=' . urlencode($success));
    exit();
}

// Если редактируем занятие
$edit_lesson = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM lessons WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_lesson = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление расписанием</title>
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
                <a href="index.php" class="nav-tab">Главная</a>
                <a href="users.php" class="nav-tab">Пользователи</a>
                <a href="schedule.php" class="nav-tab active">Расписание</a>
                <a href="groups.php" class="nav-tab">Группы</a>
                <a href="subjects.php" class="nav-tab">Предметы</a>
            </nav>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
            <?php endif; ?>

            <div class="row">
                <div class="card">
                    <h2><?php echo $edit_lesson ? 'Редактирование' : 'Добавление'; ?> занятия</h2>
                    <form method="POST" action="">
                        <input type="hidden" name="id" value="<?php echo $edit_lesson['id'] ?? 0; ?>">
                        
                        <div class="form-group">
                            <label for="subject_id">Предмет:</label>
                            <select id="subject_id" name="subject_id" class="form-control" required>
                                <option value="">-- Выберите предмет --</option>
                                <?php foreach ($subjects as $subject): ?>
                                    <option value="<?php echo $subject['id']; ?>" 
                                        <?php echo ($edit_lesson['subject_id'] ?? '') == $subject['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($subject['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="group_id">Группа:</label>
                            <select id="group_id" name="group_id" class="form-control" required>
                                <option value="">-- Выберите группу --</option>
                                <?php foreach ($groups as $group): ?>
                                    <option value="<?php echo $group['id']; ?>" 
                                        <?php echo ($edit_lesson['group_id'] ?? '') == $group['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($group['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="teacher_id">Преподаватель:</label>
                            <select id="teacher_id" name="teacher_id" class="form-control" required>
                                <option value="">-- Выберите преподавателя --</option>
                                <?php foreach ($teachers as $teacher): ?>
                                    <option value="<?php echo $teacher['id']; ?>" 
                                        <?php echo ($edit_lesson['teacher_id'] ?? '') == $teacher['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($teacher['full_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="date_time">Дата и время:</label>
                            <input type="datetime-local" id="date_time" name="date_time" class="form-control" required 
                                   value="<?php echo isset($edit_lesson['date_time']) ? date('Y-m-d\TH:i', strtotime($edit_lesson['date_time'])) : ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="topic">Тема (необязательно):</label>
                            <input type="text" id="topic" name="topic" class="form-control" 
                                   value="<?php echo htmlspecialchars($edit_lesson['topic'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="classroom">Аудитория (необязательно):</label>
                            <input type="text" id="classroom" name="classroom" class="form-control" 
                                   value="<?php echo htmlspecialchars($edit_lesson['classroom'] ?? ''); ?>">
                        </div>
                        
                        <button type="submit" class="btn"><?php echo $edit_lesson ? 'Обновить' : 'Добавить'; ?></button>
                        <?php if ($edit_lesson): ?>
                            <a href="schedule.php" class="btn btn-secondary">Отмена</a>
                        <?php endif; ?>
                    </form>
                </div>

                <div class="card">
                    <h2>Все занятия</h2>
                    <?php if (count($lessons) > 0): ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Дата и время</th>
                                    <th>Предмет</th>
                                    <th>Группа</th>
                                    <th>Преподаватель</th>
                                    <th>Тема</th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($lessons as $lesson): ?>
                                    <tr>
                                        <td><?php echo date('d.m.Y H:i', strtotime($lesson['date_time'])); ?></td>
                                        <td><?php echo htmlspecialchars($lesson['subject_name']); ?></td>
                                        <td><?php echo htmlspecialchars($lesson['group_name']); ?></td>
                                        <td><?php echo htmlspecialchars($lesson['teacher_name']); ?></td>
                                        <td><?php echo htmlspecialchars($lesson['topic']); ?></td>
                                        <td>
                                            <a href="schedule.php?edit=<?php echo $lesson['id']; ?>" class="btn btn-secondary" style="padding: 5px 10px; font-size: 14px;">Изменить</a>
                                            <a href="schedule.php?delete=<?php echo $lesson['id']; ?>" class="btn btn-danger" style="padding: 5px 10px; font-size: 14px;" 
                                               onclick="return confirm('Удалить занятие?')">Удалить</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>Нет занятий</p>
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
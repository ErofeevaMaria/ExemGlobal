<?php
require_once '../config.php';
checkAuth();

$user = getCurrentUser($pdo);
if ($user['role'] != 'admin') {
    header('Location: ../index.php');
    exit();
}

// Получаем все предметы
$stmt = $pdo->query("
    SELECT s.*, COUNT(l.id) as lesson_count 
    FROM subjects s 
    LEFT JOIN lessons l ON s.id = l.subject_id 
    GROUP BY s.id 
    ORDER BY s.name
");
$subjects = $stmt->fetchAll();

// Обработка удаления предмета
if (isset($_GET['delete'])) {
    $subject_id = $_GET['delete'];
    
    // Проверяем, есть ли занятия по этому предмету
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM lessons WHERE subject_id = ?");
    $stmt->execute([$subject_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] > 0) {
        $error = "Нельзя удалить предмет, по которому есть занятия";
    } else {
        $stmt = $pdo->prepare("DELETE FROM subjects WHERE id = ?");
        $stmt->execute([$subject_id]);
        $success = "Предмет удален";
    }
}

// Обработка добавления/редактирования предмета
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'] ?? 0;
    $name = trim($_POST['name']);
    
    if (empty($name)) {
        $error = "Название предмета не может быть пустым";
    } else {
        if ($id > 0) {
            // Редактирование
            $stmt = $pdo->prepare("UPDATE subjects SET name = ? WHERE id = ?");
            $stmt->execute([$name, $id]);
            $success = "Предмет обновлен";
        } else {
            // Добавление
            $stmt = $pdo->prepare("INSERT INTO subjects (name) VALUES (?)");
            $stmt->execute([$name]);
            $success = "Предмет добавлен";
        }
        
        header('Location: subjects.php?success=' . urlencode($success));
        exit();
    }
}

// Если редактируем предмет
$edit_subject = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM subjects WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_subject = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление предметами</title>
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
                <a href="schedule.php" class="nav-tab">Расписание</a>
                <a href="groups.php" class="nav-tab">Группы</a>
                <a href="subjects.php" class="nav-tab active">Предметы</a>
            </nav>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="row">
                <div class="card">
                    <h2><?php echo $edit_subject ? 'Редактирование' : 'Добавление'; ?> предмета</h2>
                    <form method="POST" action="">
                        <input type="hidden" name="id" value="<?php echo $edit_subject['id'] ?? 0; ?>">
                        
                        <div class="form-group">
                            <label for="name">Название предмета:</label>
                            <input type="text" id="name" name="name" class="form-control" required 
                                   value="<?php echo htmlspecialchars($edit_subject['name'] ?? ''); ?>">
                        </div>
                        
                        <button type="submit" class="btn"><?php echo $edit_subject ? 'Обновить' : 'Добавить'; ?></button>
                        <?php if ($edit_subject): ?>
                            <a href="subjects.php" class="btn btn-secondary">Отмена</a>
                        <?php endif; ?>
                    </form>
                </div>

                <div class="card">
                    <h2>Список предметов</h2>
                    <?php if (count($subjects) > 0): ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Название</th>
                                    <th>Количество занятий</th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($subjects as $subject): ?>
                                    <tr>
                                        <td><?php echo $subject['id']; ?></td>
                                        <td><?php echo htmlspecialchars($subject['name']); ?></td>
                                        <td><?php echo $subject['lesson_count']; ?></td>
                                        <td>
                                            <a href="subjects.php?edit=<?php echo $subject['id']; ?>" class="btn btn-secondary" style="padding: 5px 10px; font-size: 14px;">Изменить</a>
                                            <a href="subjects.php?delete=<?php echo $subject['id']; ?>" class="btn btn-danger" style="padding: 5px 10px; font-size: 14px;" 
                                               onclick="return confirm('Удалить предмет?')">Удалить</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>Нет предметов</p>
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
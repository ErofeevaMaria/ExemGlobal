<?php
require_once '../config.php';
checkAuth();

$user = getCurrentUser($pdo);
if ($user['role'] != 'admin') {
    header('Location: ../index.php');
    exit();
}

// Получаем все группы
$stmt = $pdo->query("SELECT g.*, COUNT(st.user_id) as student_count FROM groups g LEFT JOIN students st ON g.id = st.group_id GROUP BY g.id ORDER BY g.name");
$groups = $stmt->fetchAll();

// Обработка удаления группы
if (isset($_GET['delete'])) {
    $group_id = $_GET['delete'];
    
    // Проверяем, есть ли студенты в группе
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM students WHERE group_id = ?");
    $stmt->execute([$group_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] > 0) {
        $error = "Нельзя удалить группу, в которой есть студенты";
    } else {
        $stmt = $pdo->prepare("DELETE FROM groups WHERE id = ?");
        $stmt->execute([$group_id]);
        $success = "Группа удалена";
    }
}

// Обработка добавления/редактирования группы
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'] ?? 0;
    $name = trim($_POST['name']);
    
    if ($id > 0) {
        // Редактирование
        $stmt = $pdo->prepare("UPDATE groups SET name = ? WHERE id = ?");
        $stmt->execute([$name, $id]);
        $success = "Группа обновлена";
    } else {
        // Добавление
        $stmt = $pdo->prepare("INSERT INTO groups (name) VALUES (?)");
        $stmt->execute([$name]);
        $success = "Группа добавлена";
    }
    
    header('Location: groups.php?success=' . urlencode($success));
    exit();
}

// Если редактируем группу
$edit_group = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM groups WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_group = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление группами</title>
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
                <a href="groups.php" class="nav-tab active">Группы</a>
                <a href="subjects.php" class="nav-tab">Предметы</a>
            </nav>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="row">
                <div class="card">
                    <h2><?php echo $edit_group ? 'Редактирование' : 'Добавление'; ?> группы</h2>
                    <form method="POST" action="">
                        <input type="hidden" name="id" value="<?php echo $edit_group['id'] ?? 0; ?>">
                        
                        <div class="form-group">
                            <label for="name">Название группы:</label>
                            <input type="text" id="name" name="name" class="form-control" required 
                                   value="<?php echo htmlspecialchars($edit_group['name'] ?? ''); ?>">
                        </div>
                        
                        <button type="submit" class="btn"><?php echo $edit_group ? 'Обновить' : 'Добавить'; ?></button>
                        <?php if ($edit_group): ?>
                            <a href="groups.php" class="btn btn-secondary">Отмена</a>
                        <?php endif; ?>
                    </form>
                </div>

                <div class="card">
                    <h2>Список групп</h2>
                    <?php if (count($groups) > 0): ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Название</th>
                                    <th>Количество студентов</th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($groups as $group): ?>
                                    <tr>
                                        <td><?php echo $group['id']; ?></td>
                                        <td><?php echo htmlspecialchars($group['name']); ?></td>
                                        <td><?php echo $group['student_count']; ?></td>
                                        <td>
                                            <a href="groups.php?edit=<?php echo $group['id']; ?>" class="btn btn-secondary" style="padding: 5px 10px; font-size: 14px;">Изменить</a>
                                            <a href="groups.php?delete=<?php echo $group['id']; ?>" class="btn btn-danger" style="padding: 5px 10px; font-size: 14px;" 
                                               onclick="return confirm('Удалить группу?')">Удалить</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>Нет групп</p>
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
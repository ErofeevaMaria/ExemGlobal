<?php
require_once '../config.php';
checkAuth();

$user = getCurrentUser($pdo);
if ($user['role'] != 'admin') {
    header('Location: ../index.php');
    exit();
}

// Получаем всех пользователей
$stmt = $pdo->query("
    SELECT u.*, g.name as group_name 
    FROM users u
    LEFT JOIN students st ON u.id = st.user_id
    LEFT JOIN groups g ON st.group_id = g.id
    ORDER BY u.created_at DESC
");
$users = $stmt->fetchAll();

// Обработка удаления пользователя
if (isset($_GET['delete'])) {
    $user_id = $_GET['delete'];
    
    if ($user_id != $user['id']) { // Нельзя удалить себя
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        
        header('Location: users.php?success=Пользователь удален');
        exit();
    } else {
        $error = "Нельзя удалить себя";
    }
}

// Обработка добавления/редактирования пользователя
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'] ?? 0;
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $group_id = $_POST['group_id'] ?? null;
    $password = $_POST['password'] ?? '';
    
    if ($id > 0) {
        // Редактирование
        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, role = ?, password = ? WHERE id = ?");
            $stmt->execute([$full_name, $email, $role, $hashed_password, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, role = ? WHERE id = ?");
            $stmt->execute([$full_name, $email, $role, $id]);
        }
        
        // Обновляем группу для студента
        if ($role == 'student') {
            $stmt = $pdo->prepare("DELETE FROM students WHERE user_id = ?");
            $stmt->execute([$id]);
            
            if ($group_id) {
                $stmt = $pdo->prepare("INSERT INTO students (user_id, group_id) VALUES (?, ?)");
                $stmt->execute([$id, $group_id]);
            }
        }
        
        $success = "Пользователь обновлен";
    } else {
        // Добавление
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, ?)");
        $stmt->execute([$full_name, $email, $hashed_password, $role]);
        $new_user_id = $pdo->lastInsertId();
        
        // Добавляем группу для студента
        if ($role == 'student' && $group_id) {
            $stmt = $pdo->prepare("INSERT INTO students (user_id, group_id) VALUES (?, ?)");
            $stmt->execute([$new_user_id, $group_id]);
        }
        
        $success = "Пользователь добавлен";
    }
    
    header('Location: users.php?success=' . urlencode($success));
    exit();
}

// Получаем группы для выпадающего списка
$stmt = $pdo->query("SELECT id, name FROM groups ORDER BY name");
$groups = $stmt->fetchAll();

// Если редактируем пользователя
$edit_user = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT u.*, st.group_id FROM users u LEFT JOIN students st ON u.id = st.user_id WHERE u.id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_user = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление пользователями</title>
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
                <a href="users.php" class="nav-tab active">Пользователи</a>
                <a href="schedule.php" class="nav-tab">Расписание</a>
                <a href="groups.php" class="nav-tab">Группы</a>
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
                    <h2><?php echo $edit_user ? 'Редактирование' : 'Добавление'; ?> пользователя</h2>
                    <form method="POST" action="">
                        <input type="hidden" name="id" value="<?php echo $edit_user['id'] ?? 0; ?>">
                        
                        <div class="form-group">
                            <label for="full_name">ФИО:</label>
                            <input type="text" id="full_name" name="full_name" class="form-control" required 
                                   value="<?php echo htmlspecialchars($edit_user['full_name'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email:</label>
                            <input type="email" id="email" name="email" class="form-control" required 
                                   value="<?php echo htmlspecialchars($edit_user['email'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="password">Пароль (оставьте пустым, чтобы не менять):</label>
                            <input type="password" id="password" name="password" class="form-control" 
                                   <?php echo !$edit_user ? 'required' : ''; ?>>
                        </div>
                        
                        <div class="form-group">
                            <label for="role">Роль:</label>
                            <select id="role" name="role" class="form-control" required>
                                <option value="student" <?php echo ($edit_user['role'] ?? '') == 'student' ? 'selected' : ''; ?>>Студент</option>
                                <option value="teacher" <?php echo ($edit_user['role'] ?? '') == 'teacher' ? 'selected' : ''; ?>>Преподаватель</option>
                                <option value="admin" <?php echo ($edit_user['role'] ?? '') == 'admin' ? 'selected' : ''; ?>>Администратор</option>
                            </select>
                        </div>
                        
                        <div class="form-group" id="group-field" style="display: none;">
                            <label for="group_id">Группа (для студентов):</label>
                            <select id="group_id" name="group_id" class="form-control">
                                <option value="">-- Выберите группу --</option>
                                <?php foreach ($groups as $group): ?>
                                    <option value="<?php echo $group['id']; ?>" 
                                        <?php echo ($edit_user['group_id'] ?? '') == $group['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($group['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn"><?php echo $edit_user ? 'Обновить' : 'Добавить'; ?></button>
                        <?php if ($edit_user): ?>
                            <a href="users.php" class="btn btn-secondary">Отмена</a>
                        <?php endif; ?>
                    </form>
                    
                    <script>
                        // Показываем поле группы только для студентов
                        document.getElementById('role').addEventListener('change', function() {
                            var groupField = document.getElementById('group-field');
                            groupField.style.display = this.value === 'student' ? 'block' : 'none';
                        });
                        
                        // Инициализация при загрузке
                        document.addEventListener('DOMContentLoaded', function() {
                            var roleSelect = document.getElementById('role');
                            var groupField = document.getElementById('group-field');
                            groupField.style.display = roleSelect.value === 'student' ? 'block' : 'none';
                        });
                    </script>
                </div>

                <div class="card">
                    <h2>Список пользователей</h2>
                    <?php if (count($users) > 0): ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>ФИО</th>
                                    <th>Email</th>
                                    <th>Роль</th>
                                    <th>Группа</th>
                                    <th>Дата регистрации</th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $u): ?>
                                    <tr>
                                        <td><?php echo $u['id']; ?></td>
                                        <td><?php echo htmlspecialchars($u['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($u['email']); ?></td>
                                        <td>
                                            <span class="status <?php echo $u['role'] == 'admin' ? 'status-warning' : 'status-success'; ?>">
                                                <?php echo $u['role']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($u['group_name'] ?? '-'); ?></td>
                                        <td><?php echo date('d.m.Y', strtotime($u['created_at'])); ?></td>
                                        <td>
                                            <a href="users.php?edit=<?php echo $u['id']; ?>" class="btn btn-secondary" style="padding: 5px 10px; font-size: 14px;">Изменить</a>
                                            <?php if ($u['id'] != $user['id']): ?>
                                                <a href="users.php?delete=<?php echo $u['id']; ?>" class="btn btn-danger" style="padding: 5px 10px; font-size: 14px;" 
                                                   onclick="return confirm('Удалить пользователя?')">Удалить</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>Нет пользователей</p>
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
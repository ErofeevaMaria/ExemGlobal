<?php
require_once '../config.php';
checkAuth();

$user = getCurrentUser($pdo);
if ($user['role'] != 'teacher') {
    header('Location: ../index.php');
    exit();
}

// Получаем сообщения для преподавателя
$stmt = $pdo->prepare("
    SELECT m.*, u_sender.full_name as sender_name, u_receiver.full_name as receiver_name
    FROM messages m
    JOIN users u_sender ON m.sender_id = u_sender.id
    JOIN users u_receiver ON m.receiver_id = u_receiver.id
    WHERE m.receiver_id = ? OR m.sender_id = ?
    ORDER BY m.created_at DESC
");
$stmt->execute([$user['id'], $user['id']]);
$messages = $stmt->fetchAll();

// Получаем список студентов для отправки сообщения
$stmt = $pdo->prepare("
    SELECT DISTINCT u.id, u.full_name
    FROM lessons l
    JOIN students st ON l.group_id = st.group_id
    JOIN users u ON st.user_id = u.id
    WHERE l.teacher_id = ?
    ORDER BY u.full_name
");
$stmt->execute([$user['id']]);
$students = $stmt->fetchAll();

// Отправка нового сообщения
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_message'])) {
    $receiver_id = $_POST['receiver_id'];
    $subject = $_POST['subject'];
    $message = $_POST['message'];
    
    $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, subject, message) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user['id'], $receiver_id, $subject, $message]);
    
    header('Location: messages.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Сообщения преподавателя</title>
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
                <a href="grades.php" class="nav-tab">Оценки</a>
                <a href="messages.php" class="nav-tab active">Сообщения</a>
            </nav>

            <div class="row">
                <div class="card">
                    <h2>Отправить сообщение студенту</h2>
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="receiver_id">Студент:</label>
                            <select id="receiver_id" name="receiver_id" class="form-control" required>
                                <option value="">Выберите студента</option>
                                <?php foreach ($students as $student): ?>
                                    <option value="<?php echo $student['id']; ?>">
                                        <?php echo htmlspecialchars($student['full_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="subject">Тема:</label>
                            <input type="text" id="subject" name="subject" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="message">Сообщение:</label>
                            <textarea id="message" name="message" class="form-control" rows="4" required></textarea>
                        </div>
                        
                        <button type="submit" name="send_message" class="btn">Отправить</button>
                    </form>
                </div>

                <div class="card">
                    <h2>Входящие и исходящие сообщения</h2>
                    <?php if (count($messages) > 0): ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>От кого</th>
                                    <th>Кому</th>
                                    <th>Тема</th>
                                    <th>Дата</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($messages as $message): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($message['sender_name']); ?></td>
                                        <td><?php echo htmlspecialchars($message['receiver_name']); ?></td>
                                        <td><?php echo htmlspecialchars($message['subject']); ?></td>
                                        <td><?php echo date('d.m.Y H:i', strtotime($message['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>Нет сообщений</p>
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
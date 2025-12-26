<?php
require_once '../config.php';
checkAuth();

$user = getCurrentUser($pdo);
if ($user['role'] != 'student') {
    header('Location: ../index.php');
    exit();
}

// Получаем сообщения студента
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

// Получаем преподавателей для отправки сообщения
$stmt = $pdo->prepare("
    SELECT DISTINCT u.id, u.full_name
    FROM lessons l
    JOIN users u ON l.teacher_id = u.id
    WHERE l.group_id = (SELECT group_id FROM students WHERE user_id = ?)
    ORDER BY u.full_name
");
$stmt->execute([$user['id']]);
$teachers = $stmt->fetchAll();

// Получаем количество непрочитанных сообщений
$unread_count = 0;
foreach ($messages as $message) {
    if ($message['receiver_id'] == $user['id'] && !$message['is_read']) {
        $unread_count++;
    }
}

// Отправка нового сообщения
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_message'])) {
    $receiver_id = $_POST['receiver_id'];
    $subject = $_POST['subject'];
    $message_text = $_POST['message'];
    
    $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, subject, message) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user['id'], $receiver_id, $subject, $message_text]);
    
    header('Location: messages.php');
    exit();
}

// Помечаем сообщение как прочитанное
if (isset($_GET['read'])) {
    $message_id = $_GET['read'];
    
    $stmt = $pdo->prepare("UPDATE messages SET is_read = TRUE WHERE id = ? AND receiver_id = ?");
    $stmt->execute([$message_id, $user['id']]);
    
    header('Location: messages.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Сообщения студента</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .message-unread {
            background-color: #f0f9ff;
            border-left: 4px solid #2196f3;
        }
        .message-read {
            background-color: #f9f9f9;
            border-left: 4px solid #ccc;
        }
        .message-item {
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 0 6px 6px 0;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .message-item:hover {
            background-color: #f5f5f5;
        }
        .message-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .message-sender {
            font-weight: bold;
        }
        .message-date {
            color: #666;
            font-size: 0.9em;
        }
        .message-subject {
            font-weight: bold;
            margin-bottom: 5px;
            color: #333;
        }
        .message-preview {
            color: #666;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }
        .unread-badge {
            background-color: #f44336;
            color: white;
            border-radius: 50%;
            padding: 2px 8px;
            font-size: 0.8em;
            margin-left: 10px;
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
                        <?php if ($unread_count > 0): ?>
                            <span class="unread-badge"><?php echo $unread_count; ?> нов.</span>
                        <?php endif; ?>
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
                    <h2>Новое сообщение</h2>
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="receiver_id">Преподаватель:</label>
                            <select id="receiver_id" name="receiver_id" class="form-control" required>
                                <option value="">Выберите преподавателя</option>
                                <?php foreach ($teachers as $teacher): ?>
                                    <option value="<?php echo $teacher['id']; ?>">
                                        <?php echo htmlspecialchars($teacher['full_name']); ?>
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
                        <div>
                            <?php foreach ($messages as $message): ?>
                                <a href="message_detail.php?id=<?php echo $message['id']; ?>" style="text-decoration: none; color: inherit;">
                                    <div class="message-item <?php echo ($message['receiver_id'] == $user['id'] && !$message['is_read']) ? 'message-unread' : 'message-read'; ?>">
                                        <div class="message-header">
                                            <div class="message-sender">
                                                <?php if ($message['sender_id'] == $user['id']): ?>
                                                    <span style="color: #2e7d32;">→ <?php echo htmlspecialchars($message['receiver_name']); ?></span>
                                                <?php else: ?>
                                                    <span style="color: #1976d2;">← <?php echo htmlspecialchars($message['sender_name']); ?></span>
                                                <?php endif; ?>
                                                <?php if ($message['receiver_id'] == $user['id'] && !$message['is_read']): ?>
                                                    <span style="color: #f44336; font-size: 0.9em;"> (Новое)</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="message-date">
                                                <?php echo date('d.m.Y H:i', strtotime($message['created_at'])); ?>
                                            </div>
                                        </div>
                                        <div class="message-subject">
                                            <?php echo htmlspecialchars($message['subject']); ?>
                                        </div>
                                        <div class="message-preview">
                                            <?php echo htmlspecialchars(substr($message['message'], 0, 100)); ?>
                                            <?php if (strlen($message['message']) > 100): ?>...<?php endif; ?>
                                        </div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
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
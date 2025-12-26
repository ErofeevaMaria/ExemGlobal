<?php
require_once '../config.php';
checkAuth();

$user = getCurrentUser($pdo);
if ($user['role'] != 'student') {
    header('Location: ../index.php');
    exit();
}

if (!isset($_GET['id'])) {
    header('Location: messages.php');
    exit();
}

$message_id = $_GET['id'];

// Получаем детали сообщения
$stmt = $pdo->prepare("
    SELECT m.*, u_sender.full_name as sender_name, u_sender.email as sender_email,
           u_receiver.full_name as receiver_name, u_receiver.email as receiver_email
    FROM messages m
    JOIN users u_sender ON m.sender_id = u_sender.id
    JOIN users u_receiver ON m.receiver_id = u_receiver.id
    WHERE m.id = ? AND (m.receiver_id = ? OR m.sender_id = ?)
");
$stmt->execute([$message_id, $user['id'], $user['id']]);
$message = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$message) {
    header('Location: messages.php');
    exit();
}

// Помечаем как прочитанное, если это входящее сообщение
if ($message['receiver_id'] == $user['id'] && !$message['is_read']) {
    $stmt = $pdo->prepare("UPDATE messages SET is_read = TRUE WHERE id = ?");
    $stmt->execute([$message_id]);
}

// Обработка ответа на сообщение
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reply'])) {
    $subject = "Re: " . $message['subject'];
    $reply_text = $_POST['reply_text'];
    $receiver_id = $message['sender_id'] == $user['id'] ? $message['receiver_id'] : $message['sender_id'];
    
    $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, subject, message) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user['id'], $receiver_id, $subject, $reply_text]);
    
    header('Location: messages.php?success=Ответ отправлен');
    exit();
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Сообщение</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .message-detail {
            background: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .message-meta {
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .message-meta-row {
            display: flex;
            margin-bottom: 5px;
        }
        .message-meta-label {
            font-weight: bold;
            min-width: 120px;
            color: #2e7d32;
        }
        .message-body {
            line-height: 1.6;
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 6px;
            white-space: pre-wrap;
        }
        .reply-form {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
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
                <a href="schedule.php" class="nav-tab">Расписание</a>
                <a href="grades.php" class="nav-tab">Оценки</a>
                <a href="messages.php" class="nav-tab active">Сообщения</a>
            </nav>

            <div class="card">
                <h2>Просмотр сообщения</h2>
                
                <div class="message-detail">
                    <div class="message-meta">
                        <div class="message-meta-row">
                            <div class="message-meta-label">От:</div>
                            <div><?php echo htmlspecialchars($message['sender_name']); ?> (<?php echo htmlspecialchars($message['sender_email']); ?>)</div>
                        </div>
                        <div class="message-meta-row">
                            <div class="message-meta-label">Кому:</div>
                            <div><?php echo htmlspecialchars($message['receiver_name']); ?> (<?php echo htmlspecialchars($message['receiver_email']); ?>)</div>
                        </div>
                        <div class="message-meta-row">
                            <div class="message-meta-label">Тема:</div>
                            <div><strong><?php echo htmlspecialchars($message['subject']); ?></strong></div>
                        </div>
                        <div class="message-meta-row">
                            <div class="message-meta-label">Дата:</div>
                            <div><?php echo date('d.m.Y H:i', strtotime($message['created_at'])); ?></div>
                        </div>
                    </div>
                    
                    <div class="message-body">
                        <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                    </div>
                    
                    <?php if ($message['sender_id'] != $user['id']): ?>
                        <div class="reply-form">
                            <h3>Ответить</h3>
                            <form method="POST" action="">
                                <div class="form-group">
                                    <label for="reply_text">Текст ответа:</label>
                                    <textarea id="reply_text" name="reply_text" class="form-control" rows="4" required></textarea>
                                </div>
                                <button type="submit" name="reply" class="btn">Отправить ответ</button>
                                <a href="messages.php" class="btn btn-secondary">Вернуться к списку</a>
                            </form>
                        </div>
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
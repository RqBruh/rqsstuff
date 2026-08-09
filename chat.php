<?php
session_start();
require 'db.php';

// Если имя пользователя не задано — просим задать
if (!isset($_SESSION['username'])) {
    if (isset($_POST['set_username'])) {
        $username = trim($_POST['username']);
        if ($username) {
            $_SESSION['username'] = $username;
        }
    }
    if (!isset($_SESSION['username'])) {
        echo <<<HTML
<form method="post">
    <input type="text" name="username" placeholder="Ваше имя" required>
    <button type="submit" name="set_username">Войти в чат</button>
</form>
HTML;
        exit;
    }
}

$username = $_SESSION['username'];

// Отправка сообщения
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $message = trim($_POST['message']);
    if ($message) {
        $stmt = $pdo->prepare('INSERT INTO chat_messages (username, message) VALUES (?, ?)');
        $stmt->execute([$username, $message]);
    }
    exit; // Не возвращаем HTML после POST
}

// Загрузка сообщений
$limit = 20;
$stmt = $pdo->query('SELECT * FROM chat_messages ORDER BY created_at DESC LIMIT ?');
$stmt->execute([$limit]);
$messages = $stmt->fetchAll();
$messages = array_reverse($messages); // Чтобы старые сверху
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Чат</title>
    <style>
        #chat-box { border: 1px solid #ccc; height: 300px; overflow-y: auto; padding: 10px; margin-bottom: 10px; }
        .message { margin: 5px 0; }
        .username { font-weight: bold; color: #333; }
    </style>
</head>
<body>
    <h2>Чат</h2>
    <div id="chat-box">
        <?php foreach ($messages as $msg): ?>
            <div class="message">
                <span class="username"><?= htmlspecialchars($msg['username']) ?>:</span>
                <?= nl2br(htmlspecialchars($msg['message'])) ?>
            </div>
        <?php endforeach; ?>
    </div>

    <form id="chat-form">
        <input type="text" id="message" placeholder="Сообщение..." autocomplete="off" required style="width: 70%;">
        <button type="submit">Отправить</button>
    </form>

    <script>
        const chatBox = document.getElementById('chat-box');
        const form = document.getElementById('chat-form');
        const input = document.getElementById('message');

        function loadMessages() {
            fetch('chat.php')
                .then(r => r.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newBox = doc.querySelector('#chat-box');
                    if (newBox) {
                        chatBox.innerHTML = newBox.innerHTML;
                        chatBox.scrollTop = chatBox.scrollHeight;
                    }
                });
        }

        setInterval(loadMessages, 3000); // Обновляем каждые 3 секунды

        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const message = input.value.trim();
            if (!message) return;

            fetch('chat.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'message=' + encodeURIComponent(message)
            }).then(() => {
                input.value = '';
                loadMessages();
            });
        });
    </script>
</body>
</html>

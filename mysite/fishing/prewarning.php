<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Генерируем уникальный ID для каждого визита
function generate_visit_id() {
    return uniqid('visit_', true);
}

// Логируем факт показа антивирусного предупреждения
function log_av_warning($visit_id) {
    $session_id = session_id();
    $pdo = new PDO("pgsql:host=localhost;dbname=phishing_training", "trainer", "secure_password");
    $stmt = $pdo->prepare("INSERT INTO av_warning_stats (session_id, visit_id, warning_shown) VALUES (?, ?, TRUE)");
    $stmt->execute([$session_id, $visit_id]);
}

// Если у нас нет visit_id в сессии для этого предупреждения, генерируем и логируем
if (empty($_SESSION['av_warning_visit_id'])) {
    $visit_id = generate_visit_id();
    $_SESSION['av_warning_visit_id'] = $visit_id;
    log_av_warning($visit_id);
} else {
    $visit_id = $_SESSION['av_warning_visit_id'];
}

// Обработка выбора пользователя
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action == 'proceed') {
        $session_id = session_id();
        $pdo = new PDO("pgsql:host=localhost;dbname=phishing_training", "trainer", "secure_password");
        $stmt = $pdo->prepare("UPDATE av_warning_stats SET user_ignored_warning = TRUE WHERE visit_id = ?");
        $stmt->execute([$visit_id]);
        
        // Перенаправляем на index.php, передавая visit_id через сессию
        header("Location: index.php");
        exit;
    } elseif ($action == 'leave') {
        $session_id = session_id();
        $pdo = new PDO("pgsql:host=localhost;dbname=phishing_training", "trainer", "secure_password");
        $stmt = $pdo->prepare("UPDATE av_warning_stats SET user_left = TRUE WHERE visit_id = ?");
        $stmt->execute([$visit_id]);
        header("Location: https://www.google.com");
        exit;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Обнаружена угроза безопасности</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f0f0f0; text-align: center; }
        .warning-box { 
            width: 500px; margin: 100px auto; padding: 30px; 
            background: white; border: 2px solid #ff4444; border-radius: 10px;
            box-shadow: 0 0 20px rgba(255, 68, 68, 0.3);
        }
        .warning-header { color: #ff4444; font-size: 24px; margin-bottom: 20px; }
        .warning-icon { font-size: 48px; margin-bottom: 20px; }
        .button { 
            padding: 10px 20px; margin: 10px; border: none; border-radius: 5px; 
            cursor: pointer; font-size: 16px;
        }
        .proceed-btn { background: #ff4444; color: white; }
        .leave-btn { background: #4CAF50; color: white; }
    </style>
</head>
<body>
    <div class="warning-box">
        <div class="warning-icon">⚠</div>
        <div class="warning-header">Обнаружена фишинговая угроза</div>
        <p>Система безопасности обнаружила, что данный сайт может пытаться получить ваши личные данные.</p>
        <p><strong>URL:</strong> <?php echo htmlspecialchars("http://".$_SERVER['HTTP_HOST']); ?></p>
        <br>
        <p>Рекомендуется немедленно покинуть страницу.</p>
        
        <form method="POST">
            <button type="submit" name="action" value="leave" class="button leave-btn">Покинуть страницу (Рекомендуется)</button>
            <button type="submit" name="action" value="proceed" class="button proceed-btn">Все равно продолжить (Опасно)</button>
        </form>
    </div>
</body>
</html>

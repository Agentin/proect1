<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Подключение к БД
$pdo = new PDO("pgsql:host=localhost;dbname=phishing_training_2", "trainer", "secure_password");

// Новая статистика с правильной логикой
$stats = $pdo->query("
    SELECT 
        COUNT(*) as total_visits,
        COUNT(CASE WHEN was_submitted THEN 1 END) as submitted_forms,
        COUNT(CASE WHEN was_submitted AND is_legitimate THEN 1 END) as legitimate_credentials_used,
        COUNT(CASE WHEN was_submitted AND NOT is_legitimate THEN 1 END) as fake_credentials_used,
        COUNT(CASE WHEN was_submitted THEN 1 END) as phishing_attempts,
        COUNT(CASE WHEN NOT was_submitted THEN 1 END) as successful_avoidance
    FROM simulation_results
")->fetch(PDO::FETCH_ASSOC);

// Добавляем статистику из антивирусных предупреждений
$av_stats = $pdo->query("
    SELECT 
        COUNT(*) as total_warnings,
        COUNT(CASE WHEN user_left THEN 1 END) as left_successfully,
        COUNT(CASE WHEN user_ignored_warning THEN 1 END) as ignored_warning
    FROM av_warning_stats
")->fetch(PDO::FETCH_ASSOC);

// Объединяем статистику успешных избеганий
$total_successful_avoidance = $stats['successful_avoidance'] + $av_stats['left_successfully'];

// Рассчитываем проценты
$phishing_percent = $stats['total_visits'] > 0 ? round(($stats['phishing_attempts'] / $stats['total_visits']) * 100, 1) : 0;
$success_percent = $stats['total_visits'] > 0 ? round(($total_successful_avoidance / $stats['total_visits']) * 100, 1) : 0;
$legitimate_percent = $stats['submitted_forms'] > 0 ? round(($stats['legitimate_credentials_used'] / $stats['submitted_forms']) * 100, 1) : 0;
$fake_percent = $stats['submitted_forms'] > 0 ? round(($stats['fake_credentials_used'] / $stats['submitted_forms']) * 100, 1) : 0;

// Получаем данные о текущем пользователе
$visit_id = $_GET['visit_id'] ?? '';
$user_data = null;

if ($visit_id) {
    $user_stmt = $pdo->prepare("SELECT * FROM simulation_results WHERE visit_id = ?");
    $user_stmt->execute([$visit_id]);
    $user_data = $user_stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Результаты обучения</title>
    <style>
        /* Сохраняем ВЕСЬ ваш оригинальный CSS без изменений */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        
        .content {
            padding: 40px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
        }
        
        .lesson-section, .stats-section {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 15px;
            border-left: 5px solid #007cba;
        }
        
        .lesson-section {
            border-left-color: #ff6b6b;
        }
        
        .stats-section {
            border-left-color: #00b894;
        }
        
        h2 {
            color: #2d3436;
            margin-bottom: 20px;
            font-size: 1.8em;
        }
        
        h3 {
            color: #636e72;
            margin: 20px 0 10px 0;
        }
        
        h4 {
            color: #2d3436;
            margin: 15px 0 10px 0;
        }
        
        .warning-points, .correct-actions {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin: 15px 0;
            border-left: 4px solid #fd79a8;
        }
        
        .correct-actions {
            border-left-color: #00b894;
        }
        
        ul {
            list-style: none;
            padding-left: 0;
        }
        
        li {
            padding: 8px 0;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
        }
        
        li:before {
            content: "⚠️";
            margin-right: 10px;
            font-size: 1.2em;
        }
        
        .correct-actions li:before {
            content: "✅";
        }
        
        .stat-cards {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-top: 20px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .stat-number {
            font-size: 2em;
            font-weight: bold;
            color: #007cba;
            display: block;
        }
        
        .stat-label {
            color: #636e72;
            font-size: 0.9em;
        }
        
        .progress-bar {
            background: #e0e0e0;
            border-radius: 10px;
            height: 20px;
            margin: 10px 0;
            overflow: hidden;
        }
        
        .progress {
            height: 100%;
            background: linear-gradient(90deg, #00b894, #55efc4);
            transition: width 0.3s ease;
        }
        
        .user-result {
            background: #ffeaa7;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            text-align: center;
            font-size: 1.2em;
            font-weight: bold;
        }
        
        .success {
            background: #55efc4;
            color: #00b894;
        }
        
        .danger {
            background: #ff7675;
            color: #d63031;
        }
        
        .visual-comparison {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-top: 20px;
        }
        
        .comparison-card {
            text-align: center;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .comparison-header {
            padding: 15px;
            color: white;
            font-weight: bold;
        }
        
        .real-site .comparison-header {
            background: #00b894;
        }
        
        .phishing-site .comparison-header {
            background: #ff7675;
        }
        
        .comparison-content {
            padding: 20px;
            background: white;
        }
        
        .comparison-image {
            background: #f1f2f6;
            height: 120px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 5px;
            margin-bottom: 15px;
            font-size: 3em;
        }
        
        .real-site .comparison-image {
            color: #00b894;
        }
        
        .phishing-site .comparison-image {
            color: #ff7675;
        }
        
        .comparison-features {
            list-style: none;
            text-align: left;
            font-size: 0.9em;
        }
        
        .comparison-features li {
            padding: 5px 0;
            border-bottom: none;
            display: flex;
            align-items: center;
        }
        
        .comparison-features li:before {
            margin-right: 8px;
        }
        
        .real-site .comparison-features li:before {
            content: "✅";
        }
        
        .phishing-site .comparison-features li:before {
            content: "❌";
        }
        
        .tips-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-top: 15px;
        }
        
        .tip-item {
            display: flex;
            align-items: center;
            background: white;
            padding: 15px;
            border-radius: 8px;
        }
        
        .tip-icon {
            font-size: 2em;
            margin-right: 15px;
        }
        
        @media (max-width: 768px) {
            .content {
                grid-template-columns: 1fr;
            }
            
            .stat-cards {
                grid-template-columns: 1fr;
            }
            
            .visual-comparison {
                grid-template-columns: 1fr;
            }
            
            .tips-grid {
                grid-template-columns: 1fr;
            }
            
            .header h1 {
                font-size: 2em;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Результаты обучения кибербезопасности</h1>
            <p>Анализ ваших действий и статистика тренировки</p>
        </div>
        
        <div class="content">
            <div class="lesson-section">
                <h2>Обучающий момент</h2>
                
                <?php if ($user_data): ?>
                    <div class="user-result <?php echo $user_data['is_legitimate'] ? 'danger' : 'danger'; ?>">
                        <?php if ($user_data['is_legitimate']): ?>
                            ❌ Опасно! Вы ввели реальные данные в фишинговой форме
                        <?php else: ?>
                            ❌ Внимание! Вы ввели данные в фишинговой форме
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <div class="warning-points">
                    <h3>Признаки фишинговой атаки:</h3>
                    <ul>
                        <li>Подозрительный URL-адрес (несоответствие официальному домену)</li>
                        <li>Отсутствие SSL-сертификата (замка в адресной строке)</li>
                        <li>Ошибки в тексте и дизайне страницы</li>
                        <li>Требование срочных действий</li>
                        <li>Запрос личных данных без явной необходимости</li>
                    </ul>
                </div>
                
                <div class="correct-actions">
                    <h3>Правильные действия:</h3>
                    <ul>
                        <li>Всегда проверяйте URL перед вводом данных</li>
                        <li>Используйте двухфакторную аутентификацию</li>
                        <li>Не переходите по подозрительным ссылкам в письмах</li>
                        <li>Обращайте внимание на орфографические ошибки</li>
                        <li>При сомнениях - свяжитесь с организацией напрямую</li>
                    </ul>
                </div>
                
                <h3>Визуальные примеры:</h3>
                <p style="margin-top: 10px; color: #636e72;">
                    Сравнение настоящего и фишингового сайта:
                </p>
                
                <div class="visual-comparison">
                    <div class="comparison-card real-site">
                        <div class="comparison-header">
                            ✅ Настоящий сайт
                        </div>
                        <div class="comparison-content">
                            <div class="comparison-image">
                                🔒
                            </div>
                            <p style="font-weight: bold; color: #00b894; margin-bottom: 10px;">https://ok.ru</p>
                            <ul class="comparison-features">
                                <li>Защищенное соединение (HTTPS)</li>
                                <li>Официальный домен</li>
                                <li>Надежный сертификат</li>
                                <li>Правильная орфография</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="comparison-card phishing-site">
                        <div class="comparison-header">
                            ❌ Фишинговый сайт
                        </div>
                        <div class="comparison-content">
                            <div class="comparison-image">
                                ⚠️
                            </div>
                            <p style="font-weight: bold; color: #ff7675; margin-bottom: 10px;">http://myok.ru</p>
                            <ul class="comparison-features">
                                <li>Незащищенное соединение (HTTP)</li>
                                <li>Подозрительный домен</li>
                                <li>Отсутствует сертификат</li>
                                <li>Ошибки в оформлении</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <div style="background: #ffeaa7; padding: 20px; border-radius: 10px; margin-top: 20px;">
                    <h4 style="color: #e17055; margin-bottom: 15px;">На что обращать внимание:</h4>
                    <div class="tips-grid">
                        <div class="tip-item">
                            <span class="tip-icon">🔐</span>
                            <div>
                                <strong>Адресная строка</strong><br>
                                <small>Всегда проверяйте наличие HTTPS и замка</small>
                            </div>
                        </div>
                        <div class="tip-item">
                            <span class="tip-icon">🌐</span>
                            <div>
                                <strong>Доменное имя</strong><br>
                                <small>Официальные сайты имеют четкие домены</small>
                            </div>
                        </div>
                        <div class="tip-item">
                            <span class="tip-icon">✍️</span>
                            <div>
                                <strong>Оформление</strong><br>
                                <small>Ошибки в тексте - красный флаг</small>
                            </div>
                        </div>
                        <div class="tip-item">
                            <span class="tip-icon">🚨</span>
                            <div>
                                <strong>Предупреждения</strong><br>
                                <small>Браузер предупредит о опасных сайтах</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="stats-section">
                <h2>Статистика тренировки</h2>
                <p>Общая эффективность обучения всех участников:</p>
                
                <div class="stat-cards">
                    <div class="stat-card">
                        <span class="stat-number"><?php echo $stats['total_visits'] ?? 0; ?></span>
                        <span class="stat-label">Всего посещений</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-number" style="color: #dc3545;"><?php echo $stats['phishing_attempts'] ?? 0; ?></span>
                        <span class="stat-label">Фишинговых попыток</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-number" style="color: #28a745;"><?php echo $total_successful_avoidance ?? 0; ?></span>
                        <span class="stat-label">Успешных избеганий</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-number" style="color: #ffc107;"><?php echo $stats['legitimate_credentials_used'] ?? 0; ?></span>
                        <span class="stat-label">Разглашено реальных данных</span>
                    </div>
                </div>
                
                <h3 style="margin-top: 30px;"> Соотношение попыток и избеганий:</h3>
                
                <div style="margin: 15px 0;">
                    <strong>Фишинговые попытки: <?php echo $phishing_percent; ?>%</strong>
                    <div class="progress-bar">
                        <div class="progress" style="width: <?php echo $phishing_percent; ?>%; background: linear-gradient(90deg, #ff7675, #fd79a8);"></div>
                    </div>
                </div>
                
                <div style="margin: 15px 0;">
                    <strong>Успешные избегания: <?php echo $success_percent; ?>%</strong>
                    <div class="progress-bar">
                        <div class="progress" style="width: <?php echo $success_percent; ?>%"></div>
                    </div>
                </div>

                <div style="margin: 15px 0;">
                    <strong>Использование реальных данных: <?php echo $legitimate_percent; ?>% от попыток</strong>
                    <div class="progress-bar">
                        <div class="progress" style="width: <?php echo $legitimate_percent; ?>%; background: linear-gradient(90deg, #dc3545, #fd79a8);"></div>
                    </div>
                </div>
                
                <div style="background: #dfe6e9; padding: 20px; border-radius: 10px; margin-top: 20px;">
                    <h3>Рекомендации для улучшения:</h3>
                    <p style="margin-top: 10px;">
                        <?php
                        if ($stats['total_visits'] == 0) {
                            echo "Пока нет данных для анализа. Пройдите тренировки, чтобы увидеть статистику!";
                        } elseif ($success_percent >= 80) {
                            echo "Отличные результаты! Команда демонстрирует высокий уровень киберграмотности.";
                        } elseif ($success_percent >= 60) {
                            echo "Хорошие показатели! Рекомендуется регулярное повторение тренировок.";
                        } elseif ($success_percent >= 40) {
                            echo "Есть над чем работать! Рекомендуем обратить внимание на обучающие материалы.";
                        } else {
                            echo "Требуется серьезная работа! Необходимо провести дополнительные занятия по кибербезопасности.";
                        }
                        ?>
                    </p>
                </div>

                <!-- Статистика антивирусных предупреждений -->
                <div style="background: #e3f2fd; padding: 15px; border-radius: 10px; margin-top: 20px;">
                    <h4 style="color: #1976d2; margin-bottom: 10px;">Статистика антивирусных предупреждений:</h4>
                    <p style="margin: 5px 0; font-size: 0.9em;">
                        <strong>Показано предупреждений:</strong> <?php echo $av_stats['total_warnings'] ?? 0; ?>
                    </p>
                    <p style="margin: 5px 0; font-size: 0.9em;">
                        <strong>Ушли с страницы:</strong> <span style="color: #28a745;"><?php echo $av_stats['left_successfully'] ?? 0; ?></span>
                    </p>
                    <p style="margin: 5px 0; font-size: 0.9em;">
                        <strong>Проигнорировали предупреждение:</strong> <span style="color: #dc3545;"><?php echo $av_stats['ignored_warning'] ?? 0; ?></span>
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

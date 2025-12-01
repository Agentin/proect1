<?php
// Подключение к базам данных обоих тренажеров
try {
    // Первая база данных
    $pdo1 = new PDO("pgsql:host=localhost;dbname=phishing_training", "trainer", "secure_password");
    $pdo1->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Вторая база данных  
    $pdo2 = new PDO("pgsql:host=localhost;dbname=phishing_training_2", "trainer", "secure_password");
    $pdo2->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Получаем обновленную статистику с первого сайта
    $stats1 = $pdo1->query("
        SELECT 
            COUNT(*) as total_visits,
            COUNT(CASE WHEN was_submitted THEN 1 END) as phishing_attempts,
            COUNT(CASE WHEN NOT was_submitted THEN 1 END) as successful_avoidance,
            COUNT(CASE WHEN was_submitted AND is_legitimate THEN 1 END) as legitimate_credentials_used
        FROM simulation_results
    ")->fetch(PDO::FETCH_ASSOC);
    
    // Статистика антивируса для первого сайта
    $av_stats1 = $pdo1->query("
        SELECT COUNT(*) as total_warnings,
               COUNT(CASE WHEN user_left THEN 1 END) as left_successfully
        FROM av_warning_stats
    ")->fetch(PDO::FETCH_ASSOC);
    
    // Получаем обновленную статистику со второго сайта
    $stats2 = $pdo2->query("
        SELECT 
            COUNT(*) as total_visits,
            COUNT(CASE WHEN was_submitted THEN 1 END) as phishing_attempts,
            COUNT(CASE WHEN NOT was_submitted THEN 1 END) as successful_avoidance,
            COUNT(CASE WHEN was_submitted AND is_legitimate THEN 1 END) as legitimate_credentials_used
        FROM simulation_results
    ")->fetch(PDO::FETCH_ASSOC);
    
    // Статистика антивируса для второго сайта
    $av_stats2 = $pdo2->query("
        SELECT COUNT(*) as total_warnings,
               COUNT(CASE WHEN user_left THEN 1 END) as left_successfully
        FROM av_warning_stats
    ")->fetch(PDO::FETCH_ASSOC);
    
    // Общая статистика с правильной логикой
    $total_visits = ($stats1['total_visits'] ?? 0) + ($stats2['total_visits'] ?? 0);
    $total_phishing_attempts = ($stats1['phishing_attempts'] ?? 0) + ($stats2['phishing_attempts'] ?? 0);
    $total_successful_avoidance = ($stats1['successful_avoidance'] ?? 0) + ($stats2['successful_avoidance'] ?? 0) + 
                                 ($av_stats1['left_successfully'] ?? 0) + ($av_stats2['left_successfully'] ?? 0);
    $total_legitimate_used = ($stats1['legitimate_credentials_used'] ?? 0) + ($stats2['legitimate_credentials_used'] ?? 0);
    
    // Проценты
    $success_rate = $total_visits > 0 ? round(($total_successful_avoidance / $total_visits) * 100, 1) : 0;
    $phishing_rate = $total_visits > 0 ? round(($total_phishing_attempts / $total_visits) * 100, 1) : 0;
    
} catch (PDOException $e) {
    // Если есть ошибки подключения, используем нулевые значения
    $stats1 = ['total_visits' => 0, 'phishing_attempts' => 0, 'successful_avoidance' => 0, 'legitimate_credentials_used' => 0];
    $stats2 = ['total_visits' => 0, 'phishing_attempts' => 0, 'successful_avoidance' => 0, 'legitimate_credentials_used' => 0];
    $av_stats1 = ['left_successfully' => 0];
    $av_stats2 = ['left_successfully' => 0];
    $total_visits = $total_phishing_attempts = $total_successful_avoidance = $total_legitimate_used = 0;
    $success_rate = $phishing_rate = 0;
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Специальные предложения</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
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
        
        .header p {
            font-size: 1.2em;
            opacity: 0.9;
        }
        
        .banners-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            padding: 40px;
        }
        
        .banner {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            border-radius: 15px;
            padding: 30px;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            position: relative;
            overflow: hidden;
        }
        
        .banner:before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, transparent 0%, rgba(0,0,0,0.1) 100%);
        }
        
        .banner:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.3);
        }
        
        .banner-1 {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        
        .banner-2 {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
        }
        
        .banner-content {
            position: relative;
            z-index: 2;
        }
        
        .banner h2 {
            font-size: 1.8em;
            margin-bottom: 15px;
        }
        
        .banner p {
            font-size: 1.1em;
            margin-bottom: 20px;
            opacity: 0.9;
        }
        
        .banner-features {
            list-style: none;
            margin-bottom: 20px;
        }
        
        .banner-features li {
            padding: 5px 0;
            display: flex;
            align-items: center;
        }
        
        .banner-features li:before {
            content: "✓";
            margin-right: 10px;
            font-weight: bold;
        }
        
        .cta-button {
            display: inline-block;
            background: rgba(255,255,255,0.2);
            padding: 12px 30px;
            border-radius: 25px;
            text-decoration: none;
            color: white;
            font-weight: bold;
            border: 2px solid rgba(255,255,255,0.3);
            transition: all 0.3s ease;
        }
        
        .cta-button:hover {
            background: rgba(255,255,255,0.3);
            transform: scale(1.05);
        }

        /* Стили для кнопок статистики */
        .stats-buttons {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 25px;
            flex-wrap: wrap;
        }
        
        .stats-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 12px 24px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        
        .stats-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.3);
            background: linear-gradient(135deg, #764ba2, #667eea);
        }
        
        .stats-section {
            background: #f8f9fa;
            padding: 40px;
            text-align: center;
            border-top: 1px solid #eee;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-top: 20px;
        }
        
        .stat-item {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .stat-number {
            font-size: 2em;
            font-weight: bold;
            color: #667eea;
            display: block;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.9em;
        }
        
        .footer {
            background: #2d3436;
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .disclaimer {
            background: #ffeaa7;
            color: #e17055;
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
            text-align: center;
            font-size: 0.9em;
        }

        /* Цвета для различных типов статистики */
        .stat-success {
            color: #28a745 !important;
        }
        
        .stat-danger {
            color: #dc3545 !important;
        }
        
        .stat-warning {
            color: #ffc107 !important;
        }
        
        @media (max-width: 768px) {
            .banners-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: 1fr 1fr;
            }
            
            .header h1 {
                font-size: 2em;
            }
            
            .stats-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .stats-button {
                width: 100%;
                max-width: 250px;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Специальные предложения</h1>
            <p>Ограниченные акции для наших пользователей</p>
        </div>
        
        <div class="disclaimer">
            <strong>⚠️ Внимание:</strong> Это учебный проект по кибербезопасности. Все ссылки ведут на тренировочные сайты.
        </div>
        
        <!-- РЕКЛАМНЫЕ БАННЕРЫ -->
        <div class="banners-grid">
            <a href="http://myvk.ru" class="banner banner-1" target="_blank">
                <div class="banner-content">
                    <h2>Горящее предложение от ВКонтакте</h2>
                    <p>Получите эксклюзивный доступ к новым функциям социальной сети</p>
                    <ul class="banner-features">
                        <li>Увеличьте лимиты для сообществ</li>
                        <li>Расширенные настройки приватности</li>
                        <li>Премиум-стикеры в подарок</li>
                        <li>Приоритетная техническая поддержка</li>
                    </ul>
                    <div class="cta-button">Узнать подробнее →</div>
                </div>
            </a>
            
            <a href="http://myok.ru" class="banner banner-2" target="_blank">
                <div class="banner-content">
                    <h2>Новые возможности в однокласниках</h2>
                    <p>Обновите ваш аккаунт и получите доступ к бета-функциям</p>
                    <ul class="banner-features">
                        <li>Расширенная аналитика профиля</li>
                        <li>Кастомизация интерфейса</li>
                        <li>Ранний доступ к новым функциям</li>
                        <li>Эксклюзивные значки в профиле</li>
                    </ul>
                    <div class="cta-button">Получить доступ →</div>
                </div>
            </a>
        </div>
        
        <!-- СТАТИСТИКА -->
        <div class="stats-section">
            <h2 style="color: #2d3436; margin-bottom: 20px;"> Реальная статистика тренировок</h2>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
                <div style="background: white; padding: 20px; border-radius: 10px; text-align: center; border-left: 4px solid #4facfe;">
                    <h3 style="color: #4facfe; margin-bottom: 15px;"> Тренажер 1</h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                        <div>
                            <div style="font-size: 1.5em; font-weight: bold; color: #4facfe;"><?php echo $stats1['total_visits'] ?? 0; ?></div>
                            <div style="font-size: 0.8em; color: #666;">Посещений</div>
                        </div>
                        <div>
                            <div style="font-size: 1.5em; font-weight: bold; color: #28a745;"><?php echo ($stats1['successful_avoidance'] + ($av_stats1['left_successfully'] ?? 0)); ?></div>
                            <div style="font-size: 0.8em; color: #666;">Успешных</div>
                        </div>
                    </div>
                </div>
                
                <div style="background: white; padding: 20px; border-radius: 10px; text-align: center; border-left: 4px solid #43e97b;">
                    <h3 style="color: #43e97b; margin-bottom: 15px;"> Тренажер 2</h3>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                        <div>
                            <div style="font-size: 1.5em; font-weight: bold; color: #43e97b;"><?php echo $stats2['total_visits'] ?? 0; ?></div>
                            <div style="font-size: 0.8em; color: #666;">Посещений</div>
                        </div>
                        <div>
                            <div style="font-size: 1.5em; font-weight: bold; color: #28a745;"><?php echo ($stats2['successful_avoidance'] + ($av_stats2['left_successfully'] ?? 0)); ?></div>
                            <div style="font-size: 0.8em; color: #666;">Успешных</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <h3 style="color: #2d3436; margin-bottom: 15px;">Общая статистика по всем тренажерам</h3>
            <div class="stats-grid">
                <div class="stat-item">
                    <span class="stat-number"><?php echo $total_visits; ?></span>
                    <span class="stat-label">Всего посещений</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number stat-danger"><?php echo $total_phishing_attempts; ?></span>
                    <span class="stat-label">Фишинговых попыток</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number stat-success"><?php echo $total_successful_avoidance; ?></span>
                    <span class="stat-label">Успешных избеганий</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number stat-warning"><?php echo $total_legitimate_used; ?></span>
                    <span class="stat-label">Разглашено реальных данных</span>
                </div>
            </div>
            
            <!-- Прогресс-бар общей эффективности -->
            <div style="background: white; padding: 20px; border-radius: 10px; margin-top: 20px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                    <span style="font-weight: bold;">Общая эффективность обучения:</span>
                    <span style="font-weight: bold; color: #28a745;"><?php echo $success_rate; ?>%</span>
                </div>
                <div style="background: #e0e0e0; height: 20px; border-radius: 10px; overflow: hidden;">
                    <div style="height: 100%; background: linear-gradient(90deg, #28a745, #55efc4); width: <?php echo $success_rate; ?>%; transition: width 0.5s ease;"></div>
                </div>
                <div style="display: flex; justify-content: space-between; margin-top: 5px; font-size: 0.8em; color: #666;">
                    <span>Низкая</span>
                    <span>Высокая</span>
                </div>
            </div>
            
            <!-- Рекомендации на основе статистики -->
            <div style="background: #ffeaa7; padding: 20px; border-radius: 10px; margin-top: 20px;">
                <h4 style="color: #e17055; margin-bottom: 10px;">Рекомендации на основе статистики:</h4>
                <p style="margin: 0; color: #e17055;">
                    <?php
                    if ($total_visits == 0) {
                        echo "Пока нет данных для анализа. Пройдите тренировки, чтобы увидеть статистику!";
                    } elseif ($success_rate >= 80) {
                        echo "Отличные результаты! Команда демонстрирует высокий уровень киберграмотности.";
                    } elseif ($success_rate >= 60) {
                        echo "Хорошие показатели! Рекомендуется регулярное повторение тренировок.";
                    } elseif ($success_rate >= 40) {
                        echo "Есть над чем работать! Рекомендуем обратить внимание на обучающие материалы.";
                    } else {
                        echo "Требуется серьезная работа! Необходимо провести дополнительные занятия по кибербезопасности.";
                    }
                    ?>
                </p>
            </div>

            <!-- Кнопки для перехода к полной статистике -->
            <div class="stats-buttons">
                <a href="http://myvk.ru/stats.php" class="stats-button">
                    Полная статистика тренажера 1
                </a>
                <a href="http://myok.ru/stats.php" class="stats-button">
                    Полная статистика тренажера 2  
                </a>
            </div>
        </div>
        
        <div class="footer">
            <p>© 2024 Учебный проект по кибербезопасности. Все права защищены.</p>
            <p style="margin-top: 10px; opacity: 0.7; font-size: 0.9em;">
                Данный сайт создан в образовательных целях для тренировки навыков распознавания фишинговых атак.
                <br>Статистика обновляется в реальном времени на основе данных с тренировочных сайтов.
            </p>
        </div>
    </div>
</body>
</html>

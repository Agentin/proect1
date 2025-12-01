<?php
$pdo = new PDO("pgsql:host=localhost;dbname=phishing_training", "trainer", "secure_password");

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

// Детальные данные
$details = $pdo->query("
    SELECT timestamp, submitted_data, is_legitimate, user_ip 
    FROM simulation_results 
    WHERE was_submitted = TRUE 
    ORDER BY timestamp DESC
")->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Статистика обучения</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .stats { background: #f5f5f5; padding: 20px; border-radius: 10px; margin-bottom: 20px; }
        .stats-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); 
            gap: 15px; 
            margin-top: 15px;
        }
        .stat-card {
            background: white;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .stat-number { 
            font-size: 24px; 
            font-weight: bold; 
            display: block; 
        }
        .success { color: #28a745; }
        .danger { color: #dc3545; }
        .warning { color: #ffc107; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background-color: #f8f9fa; }
    </style>
</head>
<body>
    <h1>Статистика фишингового обучения</h1>
    
    <div class="stats">
        <h2>Общая статистика:</h2>
        <div class="stats-grid">
            <div class="stat-card">
                <span class="stat-number"><?= $stats['total_visits'] ?></span>
                <span class="stat-label">Всего посещений</span>
            </div>
            <div class="stat-card">
                <span class="stat-number danger"><?= $stats['phishing_attempts'] ?></span>
                <span class="stat-label">Фишинговых попыток</span>
            </div>
            <div class="stat-card">
                <span class="stat-number success"><?= $total_successful_avoidance ?></span>
                <span class="stat-label">Успешных избеганий</span>
            </div>
            <div class="stat-card">
                <span class="stat-number warning"><?= $stats['legitimate_credentials_used'] ?></span>
                <span class="stat-label">Разглашено реальных данных</span>
            </div>
        </div>
        
        <div style="margin-top: 20px;">
            <h3>Статистика антивирусных предупреждений:</h3>
            <p>Показано предупреждений: <?= $av_stats['total_warnings'] ?? 0 ?></p>
            <p>Ушли с страницы: <span class="success"><?= $av_stats['left_successfully'] ?? 0 ?></span></p>
            <p>Проигнорировали предупреждение: <span class="danger"><?= $av_stats['ignored_warning'] ?? 0 ?></span></p>
        </div>
        
        <p><strong>Процент попавшихся на фишинг:</strong> 
            <?= $stats['total_visits'] > 0 ? round(($stats['phishing_attempts'] / $stats['total_visits']) * 100, 2) : 0 ?>%
        </p>
    </div>
        
    <h2>Детальные данные фишинговых попыток:</h2>
    <table border="1">
        <tr>
            <th>Время</th>
            <th>Данные</th>
            <th>Тип данных</th>
            <th>IP</th>
        </tr>
        <?php foreach ($details as $row): ?>
        <tr>
            <td><?= $row['timestamp'] ?></td>
            <td><?= htmlspecialchars($row['submitted_data']) ?></td>
            <td>
                <?php if ($row['is_legitimate']): ?>
                    <span style="color: #dc3545;">⚠️ Реальные данные</span>
                <?php else: ?>
                    <span style="color: #ffc107;">🤔 Фейковые данные</span>
                <?php endif; ?>
            </td>
            <td><?= $row['user_ip'] ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>

<?php
session_start();

// Проверка прав доступа (только для преподавателей)
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'teacher') {
    header('Location: ../login.php');
    exit;
}

require_once '../classes/SecurityLogger.php';
require_once '../classes/RateLimit.php';
require_once '../classes/EnvLoader.php';
require_once '../includes/translations.php';

// Получение ID текущего преподавателя
$teacher_id = $_SESSION['user_id'];

// Получение статистики безопасности для данного преподавателя и его студентов
$timeframe = $_GET['timeframe'] ?? '24';
$stats = SecurityLogger::getTeacherSecurityStats($teacher_id, intval($timeframe));
$rateLimitStats = RateLimit::getStats(); // Общая статистика rate limiting
$recentLogs = SecurityLogger::getTeacherSecurityLogs($teacher_id, 50);

// Переменные для фильтрации
$filter = $_GET['filter'] ?? 'all';

// Настройки для header.php
$page_title = "Панель безопасности";
$page_icon = "fas fa-shield-alt";
require_once 'header.php';
?>

<style>
    /* Дополнительные стили для security-dashboard */
    .page-header {
        margin-bottom: 2rem;
    }
    
    .page-header h1 {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 0.5rem;
    }
    
    .page-header p {
        color: #666;
        font-size: 0.9rem;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .stat-card {
        background: white;
        border-radius: 10px;
        padding: 1.5rem;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        border-left: 4px solid;
        position: relative;
        overflow: hidden;
    }

    .stat-card.info { border-left-color: #3498db; }
    .stat-card.warning { border-left-color: #f39c12; }
    .stat-card.danger { border-left-color: #e74c3c; }
    .stat-card.success { border-left-color: #27ae60; }

    .stat-card .number {
        font-size: 2.5rem;
        font-weight: bold;
        margin-bottom: 0.5rem;
    }

    .stat-card .label {
        color: #7f8c8d;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .filters {
        background: white;
        border-radius: 10px;
        padding: 1.5rem;
        margin-bottom: 2rem;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }

    .filters h3 {
        margin-bottom: 1rem;
        color: #2c3e50;
    }

    .filter-group {
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
        align-items: center;
    }

    .filter-group select,
    .filter-group button {
        padding: 0.5rem 1rem;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 0.9rem;
    }

    .filter-group button {
        background: #667eea;
        color: white;
        cursor: pointer;
        border: none;
    }

    .filter-group button:hover {
        background: #5a67d8;
    }

    .logs-section {
        background: white;
        border-radius: 10px;
        padding: 1.5rem;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }

    .logs-section h3 {
        margin-bottom: 1rem;
        color: #2c3e50;
    }

    .log-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 1rem;
    }

    .log-table th,
    .log-table td {
        padding: 0.75rem;
        text-align: left;
        border-bottom: 1px solid #eee;
    }

    .log-table th {
        background: #f8f9fa;
        font-weight: 600;
        color: #2c3e50;
    }

    .log-table tr:hover {
        background: #f8f9fa;
    }

    .level-badge {
        padding: 0.25rem 0.5rem;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
    }

    .level-badge.info { background: #d1ecf1; color: #0c5460; }
    .level-badge.warning { background: #fff3cd; color: #856404; }
    .level-badge.error { background: #f8d7da; color: #721c24; }

    .chart-container {
        height: 300px;
        margin: 1rem 0;
    }

    @media (max-width: 768px) {
        .stats-grid {
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        }
        
        .filter-group {
            flex-direction: column;
            align-items: stretch;
        }
        
        .log-table {
            font-size: 0.8rem;
        }
        
        .log-table th,
        .log-table td {
            padding: 0.5rem;
        }
    }
</style>
<div class="container">
<div class="page-header">
    <h1><i class="<?php echo $page_icon; ?>"></i> <?php echo $page_title; ?></h1>
    <p>📊 Отображаются события безопасности для вашего аккаунта и ваших студентов</p>
</div>

<!-- Статистика -->
<div class="stats-grid">
    <div class="stat-card info">
        <div class="number"><?php echo $stats['total_events']; ?></div>
        <div class="label">Всего событий (<?php echo $timeframe; ?>ч)</div>
    </div>
    
    <div class="stat-card <?php echo $stats['failed_logins'] > 10 ? 'danger' : 'warning'; ?>">
        <div class="number"><?php echo $stats['failed_logins']; ?></div>
        <div class="label">Неудачные входы</div>
    </div>
    
    <div class="stat-card success">
        <div class="number"><?php echo $stats['successful_logins']; ?></div>
        <div class="label">Успешные входы</div>
    </div>
    
    <div class="stat-card <?php echo $stats['csrf_attempts'] > 0 ? 'danger' : 'info'; ?>">
        <div class="number"><?php echo $stats['csrf_attempts']; ?></div>
        <div class="label">CSRF атаки</div>
    </div>
    
    <div class="stat-card <?php echo $stats['rate_limit_exceeded'] > 5 ? 'warning' : 'info'; ?>">
        <div class="number"><?php echo $stats['rate_limit_exceeded']; ?></div>
        <div class="label">Превышения лимитов</div>
    </div>
    
    <div class="stat-card <?php echo $stats['suspicious_activities'] > 0 ? 'danger' : 'info'; ?>">
        <div class="number"><?php echo $stats['suspicious_activities']; ?></div>
        <div class="label">Подозрительные действия</div>
    </div>
</div>

<!-- Фильтры -->
<div class="filters">
    <h3>🔍 Фильтры</h3>
    <form method="GET" class="filter-group">
        <select name="timeframe">
            <option value="1" <?php echo $timeframe == '1' ? 'selected' : ''; ?>>Последний час</option>
            <option value="6" <?php echo $timeframe == '6' ? 'selected' : ''; ?>>Последние 6 часов</option>
            <option value="24" <?php echo $timeframe == '24' ? 'selected' : ''; ?>>Последние 24 часа</option>
            <option value="168" <?php echo $timeframe == '168' ? 'selected' : ''; ?>>Последняя неделя</option>
        </select>
        
        <select name="filter">
            <option value="all" <?php echo $filter == 'all' ? 'selected' : ''; ?>>Все события</option>
            <option value="login" <?php echo $filter == 'login' ? 'selected' : ''; ?>>Входы в систему</option>
            <option value="security" <?php echo $filter == 'security' ? 'selected' : ''; ?>>События безопасности</option>
            <option value="errors" <?php echo $filter == 'errors' ? 'selected' : ''; ?>>Ошибки</option>
        </select>
        
        <button type="submit">Применить</button>
    </form>
</div>

<!-- Журнал событий -->
<div class="logs-section">
    <h3>📋 Журнал событий безопасности</h3>
    
    <?php if (empty($recentLogs)): ?>
        <p style="text-align: center; color: #666; padding: 2rem;">
            Событий безопасности не найдено
        </p>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <table class="log-table">
                <thead>
                    <tr>
                        <th>Время</th>
                        <th>Уровень</th>
                        <th>Событие</th>
                        <th>IP адрес</th>
                        <th>Детали</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentLogs as $log): 
                        // Фильтрация событий
                        if ($filter != 'all') {
                            $skip = true;
                            switch ($filter) {
                                case 'login':
                                    if (strpos($log['event'], 'LOGIN') !== false) $skip = false;
                                    break;
                                case 'security':
                                    if (in_array($log['event'], ['CSRF_ATTACK', 'XSS_ATTEMPT', 'SQL_INJECTION_ATTEMPT', 'SUSPICIOUS_ACTIVITY'])) $skip = false;
                                    break;
                                case 'errors':
                                    if ($log['level'] == 'ERROR' || $log['level'] == 'WARNING') $skip = false;
                                    break;
                            }
                            if ($skip) continue;
                        }
                    ?>
                        <tr>
                            <td><?php echo date('d.m.Y H:i:s', strtotime($log['timestamp'])); ?></td>
                            <td>
                                <span class="level-badge <?php echo strtolower($log['level']); ?>">
                                    <?php echo $log['level']; ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($log['event']); ?></td>
                            <td><?php echo htmlspecialchars($log['ip']); ?></td>
                            <td>
                                <?php 
                                if (!empty($log['data'])) {
                                    $details = [];
                                    if (isset($log['data']['username'])) $details[] = 'User: ' . htmlspecialchars($log['data']['username']);
                                    if (isset($log['data']['reason'])) $details[] = 'Reason: ' . htmlspecialchars($log['data']['reason']);
                                    if (isset($log['data']['form'])) $details[] = 'Form: ' . htmlspecialchars($log['data']['form']);
                                    echo implode(', ', $details);
                                }
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
</div>
<script>
    // Автообновление страницы каждые 30 секунд
    setTimeout(function() {
        location.reload();
    }, 30000);
</script>

<?php
// Проверяем, есть ли footer.php
if (file_exists('footer.php')) {
    require_once 'footer.php';
}
?>

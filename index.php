<?php
session_start();

// Проверяем, авторизован ли пользователь
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    // Редиректим в зависимости от роли
    if ($_SESSION['role'] === 'teacher') {
        header('Location: teacher/dashboard.php');
        exit();
    } elseif ($_SESSION['role'] === 'student') {
        header('Location: student/dashboard.php');
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="kk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📚 QuizCard - Карточкалармен тілдерді үйрену жүйесі</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            overflow-x: hidden;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        /* Language Switcher */
        .language-switcher {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 25px;
            padding: 5px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            backdrop-filter: blur(10px);
        }
        
        .language-switcher button {
            background: none;
            border: none;
            padding: 8px 15px;
            border-radius: 20px;
            cursor: pointer;
            font-size: 0.9em;
            font-weight: 600;
            transition: all 0.3s ease;
            color: #667eea;
        }
        
        .language-switcher button.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .language-switcher button:hover:not(.active) {
            background: rgba(102, 126, 234, 0.1);
        }
        
        /* Content sections for different languages */
        .lang-content {
            display: none;
        }
        
        .lang-content.active {
            display: block !important;
        }
        
        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 100px 0;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .hero::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: repeating-linear-gradient(
                45deg,
                rgba(255,255,255,0.1) 0px,
                rgba(255,255,255,0.1) 1px,
                transparent 1px,
                transparent 20px
            );
            animation: move 20s linear infinite;
        }
        
        @keyframes move {
            0% { transform: translate(-50%, -50%) rotate(0deg); }
            100% { transform: translate(-50%, -50%) rotate(360deg); }
        }
        
        .hero-content {
            position: relative;
            z-index: 2;
        }
        
        .hero h1 {
            font-size: 3.5em;
            margin-bottom: 20px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
            animation: fadeInUp 1s ease-out;
        }
        
        .hero .subtitle {
            font-size: 1.4em;
            margin-bottom: 30px;
            opacity: 0.9;
            animation: fadeInUp 1s ease-out 0.3s both;
        }
        
        .hero .description {
            font-size: 1.1em;
            margin-bottom: 40px;
            opacity: 0.8;
            animation: fadeInUp 1s ease-out 0.6s both;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .cta-button {
            display: inline-block;
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 18px 40px;
            text-decoration: none;
            border-radius: 50px;
            font-size: 1.2em;
            font-weight: 600;
            transition: all 0.3s ease;
            border: 2px solid rgba(255,255,255,0.3);
            animation: fadeInUp 1s ease-out 0.9s both;
        }
        
        .cta-button:hover {
            background: white;
            color: #667eea;
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
        }
        
        /* Features Section */
        .features {
            padding: 80px 0;
            background: #f8f9fa;
        }
        
        .section-title {
            text-align: center;
            font-size: 2.5em;
            margin-bottom: 60px;
            color: #333;
            position: relative;
        }
        
        .section-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 2px;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 40px;
            margin-top: 40px;
        }
        
        .feature-card {
            background: white;
            padding: 40px 30px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }
        
        .feature-card .icon {
            font-size: 3.5em;
            margin-bottom: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .feature-card h3 {
            font-size: 1.5em;
            margin-bottom: 15px;
            color: #333;
        }
        
        .feature-card p {
            color: #666;
            line-height: 1.6;
        }
        
        /* Capabilities Section */
        .capabilities {
            padding: 80px 0;
            background: white;
        }
        
        .capabilities-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 40px;
            margin-top: 40px;
        }
        
        .capability-item {
            padding: 30px;
            border-left: 4px solid #667eea;
            background: #f8f9fa;
            border-radius: 0 10px 10px 0;
        }
        
        .capability-item h4 {
            color: #667eea;
            margin-bottom: 15px;
            font-size: 1.2em;
        }
        
        .capability-item ul {
            list-style: none;
            padding: 0;
        }
        
        .capability-item li {
            margin-bottom: 10px;
            padding-left: 25px;
            position: relative;
            color: #666;
        }
        
        .capability-item li::before {
            content: '✓';
            position: absolute;
            left: 0;
            color: #667eea;
            font-weight: bold;
        }
        
        /* Statistics Section */
        .stats {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 80px 0;
            text-align: center;
        }
        
        .stats .section-title {
            color: white;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 40px;
            margin-top: 40px;
        }
        
        .stat-item {
            padding: 20px;
        }
        
        .stat-number {
            font-size: 3em;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .stat-label {
            font-size: 1.1em;
            opacity: 0.9;
        }
        
        /* CTA Section */
        .cta-section {
            background: #f8f9fa;
            padding: 80px 0;
            text-align: center;
        }
        
        .cta-section h2 {
            font-size: 2.5em;
            margin-bottom: 20px;
            color: #333;
        }
        
        .cta-section p {
            font-size: 1.2em;
            color: #666;
            margin-bottom: 40px;
        }
        
        .cta-buttons {
            display: flex;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 18px 40px;
            text-decoration: none;
            border-radius: 50px;
            font-size: 1.2em;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.3);
        }
        
        .btn-secondary {
            background: white;
            color: #667eea;
            padding: 18px 40px;
            text-decoration: none;
            border-radius: 50px;
            font-size: 1.2em;
            font-weight: 600;
            transition: all 0.3s ease;
            border: 2px solid #667eea;
        }
        
        .btn-secondary:hover {
            background: #667eea;
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
        }
        
        /* Footer */
        footer {
            background: #333;
            color: white;
            text-align: center;
            padding: 40px 0;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2.5em;
            }
            
            .hero .subtitle {
                font-size: 1.2em;
            }
            
            .features-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .section-title {
                font-size: 2em;
            }
            
            .cta-buttons {
                flex-direction: column;
                align-items: center;
            }
        }
    </style>
    <script>
        // Language switching functionality
        let currentLang = 'kk'; // Default to Kazakh
        
        function switchLanguage(lang) {
            currentLang = lang;
            
            // Save selected language to localStorage
            localStorage.setItem('selectedLanguage', lang);
            
            // Hide all language content
            document.querySelectorAll('.lang-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // Show selected language content
            const selectedContent = document.querySelector(`[data-lang="${lang}"]`);
            if (selectedContent) {
                selectedContent.classList.add('active');
            }
            
            // Update active button
            document.querySelectorAll('.language-switcher button').forEach(btn => {
                btn.classList.remove('active');
            });
            const selectedBtn = document.querySelector(`[data-lang-btn="${lang}"]`);
            if (selectedBtn) {
                selectedBtn.classList.add('active');
            }
            
            // Update document language
            document.documentElement.lang = lang;
            
            // Update page title
            const titles = {
                'kk': '📚 QuizCard - Карточкалармен тілдерді үйрену жүйесі',
                'ru': '📚 QuizCard - Система изучения языков с карточками',
                'en': '📚 QuizCard - Language Learning System with Cards'
            };
            document.title = titles[lang];
        }
        
        // Get saved language from localStorage or default to Kazakh
        function getSavedLanguage() {
            const savedLang = localStorage.getItem('selectedLanguage');
            // Check if saved language is valid
            if (savedLang && ['kk', 'ru', 'en'].includes(savedLang)) {
                return savedLang;
            }
            return 'kk'; // Default to Kazakh
        }
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded, initializing language switcher...');
            
            // Get saved language or default
            const savedLanguage = getSavedLanguage();
            currentLang = savedLanguage;
            
            // Switch to saved language
            switchLanguage(savedLanguage);
        });
    </script>
</head>
<body>

    <!-- Language Switcher -->
    <div class="language-switcher">
        <button data-lang-btn="kk" onclick="switchLanguage('kk')" class="active">🇰🇿 ҚАЗ</button>
        <button data-lang-btn="ru" onclick="switchLanguage('ru')">🇷🇺 РУС</button>
        <button data-lang-btn="en" onclick="switchLanguage('en')">🇬🇧 ENG</button>
    </div>

    <!-- KAZAKH CONTENT -->
    <div class="lang-content active" data-lang="kk">
        <!-- Hero Section -->
        <section class="hero">
            <div class="hero-content">
                <div class="container">
                    <h1>📚 QuizCard</h1>
                    <p class="subtitle">Карточкалармен тілдерді үйрену жүйесі</p>
                    <p class="description">Мұғалімдер мен оқушыларға арналған шетел тілдерін үйрену және интервалды қайталау жүйесі бар веб-қосымша</p>
                    <a href="login.php" class="cta-button">Үйренуді бастау</a>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section class="features">
            <div class="container">
                <h2 class="section-title">✨ Негізгі мүмкіндіктер</h2>
                <div class="features-grid">
                    <div class="feature-card">
                        <div class="icon">👨‍🏫</div>
                        <h3>Мұғалімдерге арналған</h3>
                        <p>Оқу процесіне толық бақылау: сөз жиынтықтарын жасау, оқушыларды басқару, прогресті қадағалау және сөздерді жаппай импорттау</p>
                    </div>
                    <div class="feature-card">
                        <div class="icon">🎓</div>
                        <h3>Оқушыларға арналған</h3>
                        <p>Интервалды қайталау жүйесімен тиімді үйрену, прогресті қадағалау және жаңа сөздердің күндік лимиттері</p>
                    </div>
                    <div class="feature-card">
                        <div class="icon">🆓</div>
                        <h3>Толықтай тегін</h3>
                        <p>Жасырын төлемдер, жазылымдар немесе шектеулер жоқ. Барлық мүмкіндіктер барлық пайдаланушылар үшін тегін қолжетімді</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Capabilities Section -->
        <section class="capabilities">
            <div class="container">
                <h2 class="section-title">🚀 Жүйе мүмкіндіктері</h2>
                <div class="capabilities-grid">
                    <div class="capability-item">
                        <h4>👥 Мұғалімдерге арналған</h4>
                        <ul>
                            <li>Оқушыларды басқару</li>
                            <li>Оқушы деректерін өңдеу</li>
                            <li>Сөздермен жиынтықтар жасау</li>
                            <li>Оқушыларға жиынтықтарды тағайындау</li>
                            <li>Оқу статистикасын көру</li>
                            <li>Сөздерге суреттер қосу</li>
                            <li>Excel және CSV файлдарынан жаппай импорт</li>
                        </ul>
                    </div>
                    <div class="capability-item">
                        <h4>📚 Оқушыларға арналған</h4>
                        <ul>
                            <li>Тағайындалған жиынтықтарды үйрену</li>
                            <li>Интервалды қайталау жүйесі</li>
                            <li>Прогресті қадағалау</li>
                            <li>Жаңа сөздердің күндік лимиттері</li>
                            <li>Интерактивті карточкалар</li>
                            <li>Үйрену статистикасы</li>
                            <li>Сөздікті қарау</li>
                        </ul>
                    </div>
                    <div class="capability-item">
                        <h4>🔧 Техникалық мүмкіндіктер</h4>
                        <ul>
                            <li>CSV және Excel импорты</li>
                            <li>Суреттерді қолдау</li>
                            <li>Сөз мәртебелерінің жүйесі</li>
                            <li>Күндік лимиттер</li>
                            <li>Интервалды қайталаулар</li>
                            <li>Статистика және есептер</li>
                            <li>Адаптивті дизайн</li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <!-- Statistics Section -->
        <section class="stats">
            <div class="container">
                <h2 class="section-title">📊 Интервалды қайталау жүйесі</h2>
                <p style="font-size: 1.2em; margin-bottom: 40px; opacity: 0.9;">
                    QuizCard сөздерді максималды тиімді есте сақтау үшін ғылыми негізделген интервалды қайталау әдісін пайдаланады
                </p>
                
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-number">🆕</div>
                        <div class="stat-label">Жаңа сөздер</div>
                        <p style="font-size: 0.9em; margin-top: 10px; opacity: 0.8;">Алғаш рет үйренетін сөздер</p>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">📖</div>
                        <div class="stat-label">Үйренуде</div>
                        <p style="font-size: 0.9em; margin-top: 10px; opacity: 0.8;">Есте сақтау процесіндегі сөздер</p>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">✅</div>
                        <div class="stat-label">Үйренген</div>
                        <p style="font-size: 0.9em; margin-top: 10px; opacity: 0.8;">Жақсы білетін сөздер</p>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">🔄</div>
                        <div class="stat-label">Қайталаулар</div>
                        <p style="font-size: 0.9em; margin-top: 10px; opacity: 0.8;">Үйренген сөздерді тұрақты қайталау</p>
                    </div>
                </div>
                
                <div style="margin-top: 60px; text-align: left; margin-left: auto; margin-right: auto;">
                    <h3 style="text-align: center; margin-bottom: 30px; font-size: 1.5em;">🧠 Интервалды қайталау қалай жұмыс істейді?</h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 30px; margin-top: 30px;">
                        <div style="background: rgba(255,255,255,0.1); padding: 20px; border-radius: 10px;">
                            <h4 style="margin-bottom: 15px; color: #fff;">📈 Ғылыми көзқарас</h4>
                            <p style="opacity: 0.9; font-size: 0.95em; line-height: 1.6;">
                                Жүйе Эббингауз ұмыту қисығына негізделген. Сөздер миы оларды ұмытуға дайын болған сәттерде қайталанады, бұл ұзақ мерзімді жадты нығайтады.
                            </p>
                        </div>
                        <div style="background: rgba(255,255,255,0.1); padding: 20px; border-radius: 10px;">
                            <h4 style="margin-bottom: 15px; color: #fff;">⏰ Оңтайлы интервалдар</h4>
                            <p style="opacity: 0.9; font-size: 0.95em; line-height: 1.6;">
                                Қайталаулар арасындағы интервалдар автоматты түрде ұлғаяды: 1 күн → 3 күн → 1 апта → 2 апта → 1 ай және т.б.
                            </p>
                        </div>
                        <div style="background: rgba(255,255,255,0.1); padding: 20px; border-radius: 10px;">
                            <h4 style="margin-bottom: 15px; color: #fff;">🎯 Жеке көзқарас</h4>
                            <p style="opacity: 0.9; font-size: 0.95em; line-height: 1.6;">
                                Жүйе әр оқушыға бейімделеді: қиын сөздер жиі қайталанады, ал жеңіл сөздер сирек. Бұл уақытты үнемдейді және тиімділікті арттырады.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="cta-section">
            <div class="container">
                <h2>🎉 Бастауға дайынсыз ба?</h2>
                <p>Тілдерді тиімді үйрену үшін QuizCard пайдаланатын мұғалімдер мен оқушыларға қосылыңыз</p>
                <div class="cta-buttons">
                    <a href="login.php" class="btn-primary">Жүйеге кіру</a>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer>
            <div class="container">
                <p>&copy; 2025 QuizCard. Карточкалармен тілдерді үйрену жүйесі. Мұғалімдер мен оқушыларға арналған.</p>
                <p>Толықтай тегін және ашық көзді</p>
            </div>
        </footer>
    </div>

    <!-- RUSSIAN CONTENT -->
    <div class="lang-content" data-lang="ru">
        <!-- Hero Section -->
        <section class="hero">
            <div class="hero-content">
                <div class="container">
                    <h1>📚 QuizCard</h1>
                    <p class="subtitle">Система изучения языков с карточками</p>
                    <p class="description">Веб-приложение для преподавателей и учеников с карточками для изучения иностранных слов и системой интервальных повторений</p>
                    <a href="login.php" class="cta-button">Начать изучение</a>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section class="features">
            <div class="container">
                <h2 class="section-title">✨ Основные возможности</h2>
                <div class="features-grid">
                    <div class="feature-card">
                        <div class="icon">👨‍🏫</div>
                        <h3>Для преподавателей</h3>
                        <p>Полный контроль над учебным процессом: создание колод, управление учениками, отслеживание прогресса и массовый импорт слов</p>
                    </div>
                    <div class="feature-card">
                        <div class="icon">🎓</div>
                        <h3>Для учеников</h3>
                        <p>Эффективное изучение с системой интервальных повторений, отслеживание прогресса и дневные лимиты новых слов</p>
                    </div>
                    <div class="feature-card">
                        <div class="icon">🆓</div>
                        <h3>Полностью бесплатно</h3>
                        <p>Никаких скрытых платежей, подписок или ограничений. Все возможности доступны бесплатно для всех пользователей</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Capabilities Section -->
        <section class="capabilities">
            <div class="container">
                <h2 class="section-title">🚀 Возможности системы</h2>
                <div class="capabilities-grid">
                    <div class="capability-item">
                        <h4>👥 Для преподавателей</h4>
                        <ul>
                            <li>Управление учениками</li>
                            <li>Редактирование данных учеников</li>
                            <li>Создание колод со словами</li>
                            <li>Назначение колод ученикам</li>
                            <li>Просмотр статистики обучения</li>
                            <li>Добавление изображений к словам</li>
                            <li>Массовый импорт из Excel и CSV</li>
                        </ul>
                    </div>
                    <div class="capability-item">
                        <h4>📚 Для учеников</h4>
                        <ul>
                            <li>Изучение назначенных колод</li>
                            <li>Система интервальных повторений</li>
                            <li>Отслеживание прогресса</li>
                            <li>Дневные лимиты новых слов</li>
                            <li>Интерактивные карточки</li>
                            <li>Статистика изучения</li>
                            <li>Просмотр словаря</li>
                        </ul>
                    </div>
                    <div class="capability-item">
                        <h4>🔧 Технические возможности</h4>
                        <ul>
                            <li>Импорт из CSV и Excel</li>
                            <li>Поддержка изображений</li>
                            <li>Система статусов слов</li>
                            <li>Дневные лимиты</li>
                            <li>Интервальные повторения</li>
                            <li>Статистика и отчеты</li>
                            <li>Адаптивный дизайн</li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <!-- Statistics Section -->
        <section class="stats">
            <div class="container">
                <h2 class="section-title">📊 Система интервальных повторений</h2>
                <p style="font-size: 1.2em; margin-bottom: 40px; opacity: 0.9;">
                    QuizCard использует научно обоснованную методику интервальных повторений для максимально эффективного запоминания слов
                </p>
                
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-number">🆕</div>
                        <div class="stat-label">Новые слова</div>
                        <p style="font-size: 0.9em; margin-top: 10px; opacity: 0.8;">Слова, которые вы изучаете впервые</p>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">📖</div>
                        <div class="stat-label">Изучаются</div>
                        <p style="font-size: 0.9em; margin-top: 10px; opacity: 0.8;">Слова в процессе запоминания</p>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">✅</div>
                        <div class="stat-label">Изучены</div>
                        <p style="font-size: 0.9em; margin-top: 10px; opacity: 0.8;">Слова, которые вы хорошо знаете</p>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">🔄</div>
                        <div class="stat-label">Повторения</div>
                        <p style="font-size: 0.9em; margin-top: 10px; opacity: 0.8;">Регулярные повторения изученных слов</p>
                    </div>
                </div>
                
                <div style="margin-top: 60px; text-align: left; margin-left: auto; margin-right: auto;">
                    <h3 style="text-align: center; margin-bottom: 30px; font-size: 1.5em;">🧠 Как работает интервальное повторение?</h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 30px; margin-top: 30px;">
                        <div style="background: rgba(255,255,255,0.1); padding: 20px; border-radius: 10px;">
                            <h4 style="margin-bottom: 15px; color: #fff;">📈 Научный подход</h4>
                            <p style="opacity: 0.9; font-size: 0.95em; line-height: 1.6;">
                                Система основана на кривой забывания Эббингауза. Слова повторяются в моменты, когда мозг готов их забыть, что укрепляет долговременную память.
                            </p>
                        </div>
                        <div style="background: rgba(255,255,255,0.1); padding: 20px; border-radius: 10px;">
                            <h4 style="margin-bottom: 15px; color: #fff;">⏰ Оптимальные интервалы</h4>
                            <p style="opacity: 0.9; font-size: 0.95em; line-height: 1.6;">
                                Интервалы между повторениями автоматически увеличиваются: 1 день → 3 дня → 1 неделя → 2 недели → 1 месяц и так далее.
                            </p>
                        </div>
                        <div style="background: rgba(255,255,255,0.1); padding: 20px; border-radius: 10px;">
                            <h4 style="margin-bottom: 15px; color: #fff;">🎯 Персональный подход</h4>
                            <p style="opacity: 0.9; font-size: 0.95em; line-height: 1.6;">
                                Система адаптируется под каждого ученика: сложные слова повторяются чаще, а легкие - реже. Это экономит время и повышает эффективность.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="cta-section">
            <div class="container">
                <h2>🎉 Готовы начать?</h2>
                <p>Присоединяйтесь к преподавателям и ученикам, которые уже используют QuizCard для эффективного изучения языков</p>
                <div class="cta-buttons">
                    <a href="login.php" class="btn-primary">Войти в систему</a>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer>
            <div class="container">
                <p>&copy; 2025 QuizCard. Система изучения языков с карточками. Создано для преподавателей и учеников.</p>
                <p>Полностью бесплатно и с открытым исходным кодом</p>
            </div>
        </footer>
    </div>

    <!-- ENGLISH CONTENT -->
    <div class="lang-content" data-lang="en">
        <!-- Hero Section -->
        <section class="hero">
            <div class="hero-content">
                <div class="container">
                    <h1>📚 QuizCard</h1>
                    <p class="subtitle">Language Learning System with Cards</p>
                    <p class="description">Web application for teachers and students with flashcards for learning foreign words and spaced repetition system</p>
                    <a href="login.php" class="cta-button">Start Learning</a>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section class="features">
            <div class="container">
                <h2 class="section-title">✨ Main Features</h2>
                <div class="features-grid">
                    <div class="feature-card">
                        <div class="icon">👨‍🏫</div>
                        <h3>For Teachers</h3>
                        <p>Complete control over the learning process: creating decks, managing students, tracking progress and mass importing words</p>
                    </div>
                    <div class="feature-card">
                        <div class="icon">🎓</div>
                        <h3>For Students</h3>
                        <p>Effective learning with spaced repetition system, progress tracking and daily limits for new words</p>
                    </div>
                    <div class="feature-card">
                        <div class="icon">🆓</div>
                        <h3>Completely Free</h3>
                        <p>No hidden fees, subscriptions or restrictions. All features are available free for all users</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Capabilities Section -->
        <section class="capabilities">
            <div class="container">
                <h2 class="section-title">🚀 System Capabilities</h2>
                <div class="capabilities-grid">
                    <div class="capability-item">
                        <h4>👥 For Teachers</h4>
                        <ul>
                            <li>Student management</li>
                            <li>Edit student data</li>
                            <li>Create word decks</li>
                            <li>Assign decks to students</li>
                            <li>View learning statistics</li>
                            <li>Add images to words</li>
                            <li>Mass import from Excel and CSV</li>
                        </ul>
                    </div>
                    <div class="capability-item">
                        <h4>📚 For Students</h4>
                        <ul>
                            <li>Study assigned decks</li>
                            <li>Spaced repetition system</li>
                            <li>Progress tracking</li>
                            <li>Daily limits for new words</li>
                            <li>Interactive flashcards</li>
                            <li>Learning statistics</li>
                            <li>Dictionary view</li>
                        </ul>
                    </div>
                    <div class="capability-item">
                        <h4>🔧 Technical Features</h4>
                        <ul>
                            <li>Import from CSV and Excel</li>
                            <li>Image support</li>
                            <li>Word status system</li>
                            <li>Daily limits</li>
                            <li>Spaced repetitions</li>
                            <li>Statistics and reports</li>
                            <li>Responsive design</li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <!-- Statistics Section -->
        <section class="stats">
            <div class="container">
                <h2 class="section-title">📊 Spaced Repetition System</h2>
                <p style="font-size: 1.2em; margin-bottom: 40px; opacity: 0.9;">
                    QuizCard uses scientifically proven spaced repetition methodology for maximum effective word memorization
                </p>
                
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-number">🆕</div>
                        <div class="stat-label">New Words</div>
                        <p style="font-size: 0.9em; margin-top: 10px; opacity: 0.8;">Words you are learning for the first time</p>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">📖</div>
                        <div class="stat-label">Learning</div>
                        <p style="font-size: 0.9em; margin-top: 10px; opacity: 0.8;">Words in the memorization process</p>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">✅</div>
                        <div class="stat-label">Learned</div>
                        <p style="font-size: 0.9em; margin-top: 10px; opacity: 0.8;">Words you know well</p>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">🔄</div>
                        <div class="stat-label">Reviews</div>
                        <p style="font-size: 0.9em; margin-top: 10px; opacity: 0.8;">Regular repetitions of learned words</p>
                    </div>
                </div>
                
                <div style="margin-top: 60px; text-align: left; margin-left: auto; margin-right: auto;">
                    <h3 style="text-align: center; margin-bottom: 30px; font-size: 1.5em;">🧠 How does spaced repetition work?</h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 30px; margin-top: 30px;">
                        <div style="background: rgba(255,255,255,0.1); padding: 20px; border-radius: 10px;">
                            <h4 style="margin-bottom: 15px; color: #fff;">📈 Scientific Approach</h4>
                            <p style="opacity: 0.9; font-size: 0.95em; line-height: 1.6;">
                                The system is based on Ebbinghaus's forgetting curve. Words are repeated at moments when the brain is ready to forget them, strengthening long-term memory.
                            </p>
                        </div>
                        <div style="background: rgba(255,255,255,0.1); padding: 20px; border-radius: 10px;">
                            <h4 style="margin-bottom: 15px; color: #fff;">⏰ Optimal Intervals</h4>
                            <p style="opacity: 0.9; font-size: 0.95em; line-height: 1.6;">
                                Intervals between repetitions automatically increase: 1 day → 3 days → 1 week → 2 weeks → 1 month and so on.
                            </p>
                        </div>
                        <div style="background: rgba(255,255,255,0.1); padding: 20px; border-radius: 10px;">
                            <h4 style="margin-bottom: 15px; color: #fff;">🎯 Personal Approach</h4>
                            <p style="opacity: 0.9; font-size: 0.95em; line-height: 1.6;">
                                The system adapts to each student: difficult words are repeated more often, while easy ones less. This saves time and increases efficiency.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="cta-section">
            <div class="container">
                <h2>🎉 Ready to Start?</h2>
                <p>Join teachers and students who are already using QuizCard for effective language learning</p>
                <div class="cta-buttons">
                    <a href="login.php" class="btn-primary">Sign In</a>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer>
            <div class="container">
                <p>&copy; 2025 QuizCard. Language Learning System with Cards. Created for teachers and students.</p>
                <p>Completely free and open source</p>
            </div>
        </footer>
    </div>

</body>
</html>

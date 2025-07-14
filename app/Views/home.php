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
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 40px;
            margin-top: 60px;
        }
        
        .feature-card {
            background: white;
            padding: 40px 30px;
            border-radius: 20px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 50px rgba(0,0,0,0.15);
        }
        
        .feature-card .icon {
            font-size: 3em;
            margin-bottom: 20px;
        }
        
        .feature-card h3 {
            color: #333;
            margin-bottom: 15px;
            font-size: 1.4em;
        }
        
        .feature-card p {
            color: #666;
            line-height: 1.8;
        }
        
        /* Capabilities Section */
        .capabilities {
            padding: 80px 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .capabilities-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 40px;
            margin-top: 60px;
        }
        
        .capability-item {
            background: rgba(255,255,255,0.1);
            padding: 30px;
            border-radius: 15px;
            backdrop-filter: blur(10px);
        }
        
        .capability-item h4 {
            font-size: 1.3em;
            margin-bottom: 20px;
            color: #fff;
        }
        
        .capability-item ul {
            list-style: none;
        }
        
        .capability-item li {
            padding: 8px 0;
            padding-left: 25px;
            position: relative;
            opacity: 0.9;
        }
        
        .capability-item li::before {
            content: '✓';
            position: absolute;
            left: 0;
            color: #4ade80;
            font-weight: bold;
        }
        
        /* Statistics Section */
        .stats {
            padding: 80px 0;
            background: #1a1a2e;
            color: white;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 40px;
            margin-top: 40px;
        }
        
        .stat-item {
            text-align: center;
            padding: 30px 20px;
            background: rgba(255,255,255,0.05);
            border-radius: 15px;
            transition: transform 0.3s ease;
        }
        
        .stat-item:hover {
            transform: translateY(-5px);
        }
        
        .stat-number {
            font-size: 3em;
            font-weight: bold;
            margin-bottom: 10px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .stat-label {
            font-size: 1.2em;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        /* CTA Section */
        .cta-section {
            padding: 80px 0;
            background: linear-gradient(135deg, #4ade80 0%, #22c55e 100%);
            color: white;
            text-align: center;
        }
        
        .cta-section h2 {
            font-size: 2.5em;
            margin-bottom: 20px;
        }
        
        .cta-section p {
            font-size: 1.2em;
            margin-bottom: 40px;
            opacity: 0.9;
        }
        
        .cta-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn-primary {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 18px 40px;
            text-decoration: none;
            border-radius: 50px;
            font-size: 1.1em;
            font-weight: 600;
            transition: all 0.3s ease;
            border: 2px solid rgba(255,255,255,0.3);
        }
        
        .btn-primary:hover {
            background: white;
            color: #22c55e;
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
        }
        
        /* Footer */
        footer {
            background: #1a1a2e;
            color: white;
            padding: 40px 0;
            text-align: center;
        }
        
        footer p {
            margin-bottom: 10px;
            opacity: 0.8;
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2.5em;
            }
            
            .hero .subtitle {
                font-size: 1.2em;
            }
            
            .section-title {
                font-size: 2em;
            }
            
            .features-grid,
            .capabilities-grid {
                grid-template-columns: 1fr;
            }
            
            .language-switcher {
                top: 10px;
                right: 10px;
            }
        }
    </style>
</head>
<body>
    <!-- Language Switcher -->
    <div class="language-switcher">
        <button onclick="switchLanguage('kk')" class="active" id="lang-kk">ҚАЗ</button>
        <button onclick="switchLanguage('ru')" id="lang-ru">РУС</button>
        <button onclick="switchLanguage('en')" id="lang-en">ENG</button>
    </div>

    <!-- KAZAKH CONTENT -->
    <div class="lang-content active" data-lang="kk">
        <!-- Hero Section -->
        <section class="hero">
            <div class="hero-content">
                <div class="container">
                    <h1>📚 QuizCard</h1>
                    <p class="subtitle">Карточкалармен тілдерді үйрену жүйесі</p>
                    <p class="description">Мұғалімдер мен оқушыларға арналған шет тілінің сөздерін үйренуге және интервалдық қайталауға арналған веб-қолданба</p>
                    <a href="/login" class="cta-button">Үйренуді бастау</a>
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
                        <h3>Мұғалімдерге</h3>
                        <p>Оқу процесін толық басқару: колодалар құру, оқушыларды басқару, прогрессті қадағалау және сөздерді жаппай импорттау</p>
                    </div>
                    <div class="feature-card">
                        <div class="icon">🎓</div>
                        <h3>Оқушыларға</h3>
                        <p>Интервалдық қайталау жүйесімен тиімді оқу, прогрессті бақылау және жаңа сөздерге күнделікті шектеулер</p>
                    </div>
                    <div class="feature-card">
                        <div class="icon">🆓</div>
                        <h3>Толығымен тегін</h3>
                        <p>Жасырын төлемдер, жазылымдар немесе шектеулер жоқ. Барлық мүмкіндіктер барлық пайдаланушыларға тегін қол жетімді</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Capabilities Section -->
        <section class="capabilities">
            <div class="container">
                <h2 class="section-title">🚀 Жүйенің мүмкіндіктері</h2>
                <div class="capabilities-grid">
                    <div class="capability-item">
                        <h4>👥 Мұғалімдерге</h4>
                        <ul>
                            <li>Оқушыларды басқару</li>
                            <li>Оқушы деректерін өңдеу</li>
                            <li>Сөз колодаларын құру</li>
                            <li>Оқушыларға колодалар тағайындау</li>
                            <li>Оқу статистикасын көру</li>
                            <li>Сөздерге суреттер қосу</li>
                            <li>Excel мен CSV-дан жаппай импорт</li>
                        </ul>
                    </div>
                    <div class="capability-item">
                        <h4>📚 Оқушыларға</h4>
                        <ul>
                            <li>Тағайындалған колодаларды оқу</li>
                            <li>Интервалдық қайталау жүйесі</li>
                            <li>Прогрессті бақылау</li>
                            <li>Жаңа сөздерге күнделікті шектеулер</li>
                            <li>Интерактивті карточкалар</li>
                            <li>Оқу статистикасы</li>
                            <li>Сөздік көрініс</li>
                        </ul>
                    </div>
                    <div class="capability-item">
                        <h4>🔧 Техникалық мүмкіндіктер</h4>
                        <ul>
                            <li>CSV мен Excel-дан импорт</li>
                            <li>Суреттерді қолдау</li>
                            <li>Сөз статусы жүйесі</li>
                            <li>Күнделікті шектеулер</li>
                            <li>Интервалдық қайталаулар</li>
                            <li>Статистика мен есептер</li>
                            <li>Адаптивті дизайн</li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <!-- Statistics Section -->
        <section class="stats">
            <div class="container">
                <h2 class="section-title">📊 Интервалдық қайталау жүйесі</h2>
                <p style="font-size: 1.2em; margin-bottom: 40px; opacity: 0.9;">
                    QuizCard ғылыми дәлелденген интервалдық қайталау әдістемесін пайдаланады, бұл сөздерді максималды тиімді есте сақтауға мүмкіндік береді
                </p>
                
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-number">🆕</div>
                        <div class="stat-label">Жаңа сөздер</div>
                        <p style="font-size: 0.9em; margin-top: 10px; opacity: 0.8;">Алғаш рет үйреніп жатқан сөздер</p>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">📖</div>
                        <div class="stat-label">Үйрену</div>
                        <p style="font-size: 0.9em; margin-top: 10px; opacity: 0.8;">Есте сақтау процесіндегі сөздер</p>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">✅</div>
                        <div class="stat-label">Үйренілген</div>
                        <p style="font-size: 0.9em; margin-top: 10px; opacity: 0.8;">Жақсы білетін сөздер</p>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">🔄</div>
                        <div class="stat-label">Қайталаулар</div>
                        <p style="font-size: 0.9em; margin-top: 10px; opacity: 0.8;">Үйренілген сөздерді тұрақты қайталау</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="cta-section">
            <div class="container">
                <h2>🎉 Бастауға дайынсыз ба?</h2>
                <p>Тілдерді тиімді үйрену үшін QuizCard-ты пайдаланып жатқан мұғалімдер мен оқушыларға қосылыңыз</p>
                <div class="cta-buttons">
                    <a href="/login" class="btn-primary">Жүйеге кіру</a>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer>
            <div class="container">
                <p>&copy; 2025 QuizCard. Карточкалармен тілдерді үйрену жүйесі. Мұғалімдер мен оқушыларға арналып жасалған.</p>
                <p>Толығымен тегін және ашық бастапқы кодпен</p>
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
                    <a href="/login" class="cta-button">Начать изучение</a>
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
                        <p>Полный контроль учебного процесса: создание колод, управление учениками, отслеживание прогресса и массовый импорт слов</p>
                    </div>
                    <div class="feature-card">
                        <div class="icon">🎓</div>
                        <h3>Для учеников</h3>
                        <p>Эффективное обучение с системой интервальных повторений, отслеживание прогресса и ежедневные лимиты на новые слова</p>
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
                            <li>Создание колод слов</li>
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
                            <li>Ежедневные лимиты на новые слова</li>
                            <li>Интерактивные карточки</li>
                            <li>Статистика обучения</li>
                            <li>Словарный просмотр</li>
                        </ul>
                    </div>
                    <div class="capability-item">
                        <h4>🔧 Технические возможности</h4>
                        <ul>
                            <li>Импорт из CSV и Excel</li>
                            <li>Поддержка изображений</li>
                            <li>Система статусов слов</li>
                            <li>Ежедневные лимиты</li>
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
                    QuizCard использует научно доказанную методику интервальных повторений для максимально эффективного запоминания слов
                </p>
                
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-number">🆕</div>
                        <div class="stat-label">Новые слова</div>
                        <p style="font-size: 0.9em; margin-top: 10px; opacity: 0.8;">Слова, которые изучаете впервые</p>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">📖</div>
                        <div class="stat-label">Изучение</div>
                        <p style="font-size: 0.9em; margin-top: 10px; opacity: 0.8;">Слова в процессе запоминания</p>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">✅</div>
                        <div class="stat-label">Изучено</div>
                        <p style="font-size: 0.9em; margin-top: 10px; opacity: 0.8;">Слова, которые хорошо знаете</p>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number">🔄</div>
                        <div class="stat-label">Повторения</div>
                        <p style="font-size: 0.9em; margin-top: 10px; opacity: 0.8;">Регулярные повторения изученных слов</p>
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
                    <a href="/login" class="btn-primary">Войти в систему</a>
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
                    <a href="/login" class="cta-button">Start Learning</a>
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
                    <a href="/login" class="btn-primary">Sign In</a>
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

    <script>
        // Language switcher functionality
        function switchLanguage(lang) {
            // Hide all language content
            document.querySelectorAll('.lang-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // Show selected language content
            document.querySelector(`[data-lang="${lang}"]`).classList.add('active');
            
            // Update language switcher buttons
            document.querySelectorAll('.language-switcher button').forEach(btn => {
                btn.classList.remove('active');
            });
            document.getElementById(`lang-${lang}`).classList.add('active');
            
            // Save language preference
            localStorage.setItem('language', lang);
        }
        
        // Load saved language on page load
        document.addEventListener('DOMContentLoaded', function() {
            const savedLang = localStorage.getItem('language') || 'kk';
            switchLanguage(savedLang);
        });
    </script>
</body>
</html>

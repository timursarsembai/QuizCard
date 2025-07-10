<?php
// Переключатель языков для страниц преподавателя
// Этот файл должен быть включен после подключения translations.php
?>

<style>
    .language-switcher {
        display: flex;
        gap: 0.5rem;
        margin-bottom: 2rem;
        justify-content: center;
    }

    .language-switcher button {
        padding: 0.5rem 1rem;
        border: 2px solid #667eea;
        background: white;
        color: #667eea;
        border-radius: 25px;
        cursor: pointer;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .language-switcher button.active {
        background: #667eea;
        color: white;
    }
    
    .language-switcher button:hover:not(.active) {
        background: rgba(102, 126, 234, 0.1);
    }
</style>

<div class="language-switcher">
    <button onclick="switchLanguage('kk')" data-lang="kk">🇰🇿 ҚАЗ</button>
    <button onclick="switchLanguage('ru')" data-lang="ru">🇷🇺 РУС</button>
    <button onclick="switchLanguage('en')" data-lang="en">🇬🇧 ENG</button>
</div>

<script>
    const translations = <?php echo json_encode($translations); ?>;
    let currentLang = '<?php echo getCurrentLanguage(); ?>'; // Получаем язык из PHP сессии

    function getSavedLanguage() {
        return localStorage.getItem('selectedLanguage');
    }

    function saveLanguage(lang) {
        localStorage.setItem('selectedLanguage', lang);
    }

    function syncLanguageWithServer(lang) {
        return fetch('../includes/set_language.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ language: lang })
        })
        .then(response => response.json())
        .catch(error => {
            console.error('Error syncing language with server:', error);
            return { success: false };
        });
    }

    function switchLanguage(lang) {
        if (lang === currentLang) return;
        
        currentLang = lang;
        
        // Сохраняем в localStorage
        saveLanguage(lang);
        
        // Синхронизируем с сервером
        syncLanguageWithServer(lang).then(result => {
            if (result.success) {
                translatePage();
                updateLanguageSwitcher();
            } else {
                console.error('Failed to sync language with server');
                // Откатываем изменения при ошибке
                currentLang = '<?php echo getCurrentLanguage(); ?>';
                updateLanguageSwitcher();
            }
        });
    }

    function translatePage() {
        document.querySelectorAll('[data-translate-key]').forEach(element => {
            const key = element.getAttribute('data-translate-key');
            if (translations[currentLang] && translations[currentLang][key]) {
                // Для заголовка страницы
                if (key === 'dashboard_title' || key === 'decks_title' || key === 'tests_title' || key === 'students_title' || key === 'account_title' || key === 'deck_students_title') {
                    const headerTitle = document.querySelector('.logo h1');
                    if(headerTitle) {
                        const iconElement = headerTitle.querySelector('i');
                        const iconClass = iconElement ? iconElement.className : '';
                        headerTitle.innerHTML = (iconClass ? `<i class="${iconClass}"></i> ` : '') + '👥 ' + translations[currentLang][key];
                    }
                    document.title = translations[currentLang][key];
                } else if (element.hasAttribute('placeholder')) {
                    // Для placeholder атрибутов
                    element.setAttribute('placeholder', translations[currentLang][key]);
                } else if (element.hasAttribute('data-confirm-key')) {
                    // Для confirm диалога
                    const confirmKey = element.getAttribute('data-confirm-key');
                    if (translations[currentLang] && translations[currentLang][confirmKey]) {
                        const currentOnclick = element.getAttribute('onclick');
                        const newOnclick = currentOnclick.replace(/confirm\('[^']*'\)/, `confirm('${translations[currentLang][confirmKey]}')`);
                        element.setAttribute('onclick', newOnclick);
                    }
                    // Сохраняем иконку для кнопки удаления
                    if (element.innerHTML.includes('🗑️')) {
                        element.innerHTML = '🗑️';
                    } else {
                        // Для обычных кнопок с текстом
                        element.innerHTML = translations[currentLang][key];
                    }
                    element.setAttribute('title', translations[currentLang][key]);
                } else if (element.tagName === 'A' && element.innerHTML.includes('✏️')) {
                    // Для кнопок действий с иконками
                    element.innerHTML = '✏️';
                    element.setAttribute('title', translations[currentLang][key]);
                } else if (element.tagName === 'A' && element.innerHTML.includes('📤')) {
                    element.innerHTML = '📤';
                    element.setAttribute('title', translations[currentLang][key]);
                } else if (element.tagName === 'A' && element.innerHTML.includes('👥')) {
                    element.innerHTML = '👥';
                    element.setAttribute('title', translations[currentLang][key]);
                } else if (element.tagName === 'A' && element.innerHTML.includes('🗑️')) {
                    element.innerHTML = '🗑️';
                    element.setAttribute('title', translations[currentLang][key]);
                } else if (element.tagName === 'OPTION' && element.hasAttribute('data-words-count')) {
                    // Для option элементов с количеством слов
                    const wordsCount = element.getAttribute('data-words-count');
                    const deckName = element.textContent.split('(')[0].trim();
                    const wordsText = translations[currentLang]['words_plural'] || 'слов';
                    element.textContent = `${deckName} (${wordsCount} ${wordsText})`;
                } else {
                    // Для остальных элементов
                    element.innerHTML = translations[currentLang][key];
                }
            }
        });
        
        // Дополнительно переводим span элементы внутри option
        document.querySelectorAll('option span[data-translate-key]').forEach(element => {
            const key = element.getAttribute('data-translate-key');
            if (translations[currentLang] && translations[currentLang][key]) {
                element.innerHTML = translations[currentLang][key];
            }
        });
        
        // Переводим навигацию
        translateNavigation();
    }
    
    function translateNavigation() {
        document.querySelectorAll('.nav-links a[data-translate-key]').forEach(element => {
            const key = element.getAttribute('data-translate-key');
            if (translations[currentLang] && translations[currentLang][key]) {
                element.innerHTML = translations[currentLang][key];
            }
        });
    }
    
    function updateLanguageSwitcher() {
        document.querySelectorAll('.language-switcher button').forEach(btn => {
            if (btn.getAttribute('data-lang') === currentLang) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        });
    }

    // Инициализация при загрузке страницы
    document.addEventListener('DOMContentLoaded', function() {
        // Синхронизируем localStorage с сессией сервера при загрузке
        const savedLang = getSavedLanguage();
        const serverLang = '<?php echo getCurrentLanguage(); ?>';
        
        // Если в localStorage есть язык, отличный от серверного, синхронизируем
        if (savedLang && savedLang !== serverLang && ['kk', 'ru', 'en'].includes(savedLang)) {
            syncLanguageWithServer(savedLang).then(result => {
                if (result.success) {
                    currentLang = savedLang;
                    // Перезагружаем страницу для применения нового языка
                    window.location.reload();
                } else {
                    // Если синхронизация не удалась, используем серверный язык
                    saveLanguage(serverLang);
                    currentLang = serverLang;
                    updateLanguageSwitcher();
                    translatePage();
                }
            });
        } else {
            // Сохраняем серверный язык в localStorage, если его там нет
            if (!savedLang || savedLang !== serverLang) {
                saveLanguage(serverLang);
            }
            updateLanguageSwitcher();
            translatePage();
        }
    });
</script>

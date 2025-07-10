<?php
// Переключатель языков для страниц ученика
// Этот файл должен быть включен после подключения translations.php
?>

<style>
    .language-switcher {
        display: flex;
        gap: 0.5rem;
        align-items: center;
    }

    .language-switcher button {
        padding: 0.4rem 0.8rem;
        border: 2px solid rgba(255,255,255,0.3);
        background: rgba(255,255,255,0.2);
        color: white;
        border-radius: 20px;
        cursor: pointer;
        font-weight: 500;
        font-size: 0.85rem;
        transition: all 0.3s ease;
    }

    .language-switcher button.active {
        background: rgba(255,255,255,0.3);
        border-color: rgba(255,255,255,0.5);
        color: white;
    }
    
    .language-switcher button:hover:not(.active) {
        background: rgba(255,255,255,0.25);
        border-color: rgba(255,255,255,0.4);
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
                // Обновляем кнопки переключателя
                updateLanguageButtons();
                
                // Обновляем переводы на странице
                updateTranslations();
                
                // Устанавливаем язык HTML элемента
                document.documentElement.lang = lang;
            } else {
                console.error('Failed to sync language with server');
                // Откатываем изменения при ошибке
                currentLang = '<?php echo getCurrentLanguage(); ?>';
                updateLanguageButtons();
            }
        });
    }

    function updateLanguageButtons() {
        document.querySelectorAll('.language-switcher button').forEach(btn => {
            if (btn.dataset.lang === currentLang) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        });
    }

    function updateTranslations() {
        // Обновляем все элементы с data-translate-key
        document.querySelectorAll('[data-translate-key]').forEach(element => {
            const key = element.getAttribute('data-translate-key');
            const langTranslations = translations[currentLang] || translations['ru'];
            
            if (langTranslations && langTranslations[key]) {
                if (element.tagName.toLowerCase() === 'input' || element.tagName.toLowerCase() === 'textarea') {
                    if (element.hasAttribute('placeholder')) {
                        element.placeholder = langTranslations[key];
                    } else {
                        element.value = langTranslations[key];
                    }
                } else {
                    element.textContent = langTranslations[key];
                }
            }
        });

        // Обновляем title страницы
        const langTranslations = translations[currentLang] || translations['ru'];
        if (langTranslations && langTranslations['student_dashboard_title']) {
            document.title = langTranslations['student_dashboard_title'];
        }
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
                    updateLanguageButtons();
                }
            });
        } else {
            // Сохраняем серверный язык в localStorage, если его там нет
            if (!savedLang || savedLang !== serverLang) {
                saveLanguage(serverLang);
            }
            updateLanguageButtons();
        }
    });
</script>

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
    const translations = <?php echo json_encode($translations ?? []); ?>;
    let currentLang = '<?php echo $_SESSION['language'] ?? 'ru'; ?>';

    function getSavedLanguage() {
        return localStorage.getItem('selectedLanguage');
    }

    function saveLanguage(lang) {
        localStorage.setItem('selectedLanguage', lang);
    }

    function syncLanguageWithServer(lang) {
        return fetch('/set-language', {
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
                currentLang = '<?php echo $_SESSION['language'] ?? 'ru'; ?>';
                updateLanguageSwitcher();
            }
        });
    }

    function translatePage() {
        document.querySelectorAll('[data-translate-key]').forEach(element => {
            const key = element.getAttribute('data-translate-key');
            if (translations[currentLang] && translations[currentLang][key]) {
                if (element.hasAttribute('placeholder')) {
                    element.setAttribute('placeholder', translations[currentLang][key]);
                } else {
                    element.innerHTML = translations[currentLang][key];
                }
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
        const savedLang = getSavedLanguage();
        const serverLang = '<?php echo $_SESSION['language'] ?? 'ru'; ?>';
        
        if (savedLang && savedLang !== serverLang && ['kk', 'ru', 'en'].includes(savedLang)) {
            syncLanguageWithServer(savedLang).then(result => {
                if (result.success) {
                    currentLang = savedLang;
                    window.location.reload();
                } else {
                    saveLanguage(serverLang);
                    currentLang = serverLang;
                    updateLanguageSwitcher();
                    translatePage();
                }
            });
        } else {
            if (!savedLang || savedLang !== serverLang) {
                saveLanguage(serverLang);
            }
            updateLanguageSwitcher();
            translatePage();
        }
    });
</script>

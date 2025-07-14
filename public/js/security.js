/**
 * Система безопасности для AJAX запросов
 * Автоматически добавляет CSRF токены ко всем формам и AJAX запросам
 */

class SecurityManager {
    constructor() {
        this.csrfToken = null;
        this.init();
    }

    /**
     * Инициализация системы безопасности
     */
    init() {
        this.loadCSRFToken();
        this.setupFormProtection();
        this.setupAjaxProtection();
        this.setupValidation();
    }

    /**
     * Загрузка CSRF токена из мета-тега
     */
    loadCSRFToken() {
        const metaTag = document.querySelector('meta[name="csrf-token"]');
        if (metaTag) {
            this.csrfToken = metaTag.getAttribute('content');
        }
    }

    /**
     * Получение текущего CSRF токена
     */
    getCSRFToken() {
        return this.csrfToken;
    }

    /**
     * Обновление CSRF токена
     */
    async refreshCSRFToken() {
        try {
            const response = await fetch('api/csrf-token.php');
            const data = await response.json();
            
            if (data.token) {
                this.csrfToken = data.token;
                
                // Обновляем мета-тег
                const metaTag = document.querySelector('meta[name="csrf-token"]');
                if (metaTag) {
                    metaTag.setAttribute('content', this.csrfToken);
                }
                
                // Обновляем все скрытые поля в формах
                this.updateFormTokens();
                
                return true;
            }
        } catch (error) {
            console.error('Ошибка обновления CSRF токена:', error);
            return false;
        }
    }

    /**
     * Настройка защиты форм
     */
    setupFormProtection() {
        // Добавляем CSRF токены ко всем формам без них
        document.querySelectorAll('form').forEach(form => {
            this.protectForm(form);
        });

        // Следим за динамически добавляемыми формами
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                mutation.addedNodes.forEach((node) => {
                    if (node.nodeType === 1) { // Element node
                        if (node.tagName === 'FORM') {
                            this.protectForm(node);
                        } else {
                            node.querySelectorAll('form').forEach(form => {
                                this.protectForm(form);
                            });
                        }
                    }
                });
            });
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }

    /**
     * Защита отдельной формы
     */
    protectForm(form) {
        // Пропускаем GET формы
        if (form.method.toLowerCase() === 'get') {
            return;
        }

        // Проверяем, есть ли уже CSRF поле
        const existingToken = form.querySelector('input[name="csrf_token"]');
        if (existingToken) {
            // Обновляем токен если он пустой
            if (!existingToken.value && this.csrfToken) {
                existingToken.value = this.csrfToken;
            }
            return;
        }

        // Добавляем CSRF поле
        if (this.csrfToken) {
            const tokenInput = document.createElement('input');
            tokenInput.type = 'hidden';
            tokenInput.name = 'csrf_token';
            tokenInput.value = this.csrfToken;
            form.appendChild(tokenInput);
        }
    }

    /**
     * Обновление токенов во всех формах
     */
    updateFormTokens() {
        document.querySelectorAll('input[name="csrf_token"]').forEach(input => {
            if (this.csrfToken) {
                input.value = this.csrfToken;
            }
        });
    }

    /**
     * Настройка защиты AJAX запросов
     */
    setupAjaxProtection() {
        const self = this;

        // Перехватываем XMLHttpRequest
        const originalXHROpen = XMLHttpRequest.prototype.open;
        const originalXHRSend = XMLHttpRequest.prototype.send;

        XMLHttpRequest.prototype.open = function(method, url, async, user, password) {
            this._method = method;
            this._url = url;
            return originalXHROpen.apply(this, arguments);
        };

        XMLHttpRequest.prototype.send = function(data) {
            if (this._method && this._method.toUpperCase() === 'POST') {
                // Добавляем CSRF заголовок
                if (self.csrfToken) {
                    this.setRequestHeader('X-CSRF-Token', self.csrfToken);
                }

                // Добавляем CSRF в FormData
                if (data instanceof FormData) {
                    if (!data.has('csrf_token') && self.csrfToken) {
                        data.append('csrf_token', self.csrfToken);
                    }
                }
            }

            return originalXHRSend.apply(this, arguments);
        };

        // Перехватываем fetch API
        const originalFetch = window.fetch;
        window.fetch = function(url, options = {}) {
            const method = options.method || 'GET';
            
            if (method.toUpperCase() === 'POST') {
                // Добавляем заголовки
                options.headers = options.headers || {};
                if (self.csrfToken && !options.headers['X-CSRF-Token']) {
                    options.headers['X-CSRF-Token'] = self.csrfToken;
                }

                // Добавляем в body если это FormData
                if (options.body instanceof FormData) {
                    if (!options.body.has('csrf_token') && self.csrfToken) {
                        options.body.append('csrf_token', self.csrfToken);
                    }
                }
            }

            return originalFetch.apply(this, arguments);
        };
    }

    /**
     * Настройка клиентской валидации
     */
    setupValidation() {
        // Валидация email
        document.querySelectorAll('input[type="email"]').forEach(input => {
            input.addEventListener('blur', this.validateEmail);
        });

        // Валидация паролей
        document.querySelectorAll('input[type="password"]').forEach(input => {
            input.addEventListener('blur', this.validatePassword);
        });

        // Валидация имен пользователей
        document.querySelectorAll('input[name*="username"]').forEach(input => {
            input.addEventListener('blur', this.validateUsername);
        });

        // Подтверждение пароля
        const passwordConfirm = document.querySelector('input[name*="confirm"]');
        const password = document.querySelector('input[name*="password"]:not([name*="confirm"])');
        
        if (passwordConfirm && password) {
            passwordConfirm.addEventListener('blur', () => {
                this.validatePasswordMatch(password, passwordConfirm);
            });
        }
    }

    /**
     * Валидация email
     */
    validateEmail(event) {
        const input = event.target;
        const value = input.value.trim();
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

        if (value && !emailRegex.test(value)) {
            SecurityManager.showFieldError(input, 'Некорректный формат email');
            return false;
        } else {
            SecurityManager.clearFieldError(input);
            return true;
        }
    }

    /**
     * Валидация пароля
     */
    validatePassword(event) {
        const input = event.target;
        const value = input.value;
        const errors = [];

        if (value.length < 6) {
            errors.push('Минимум 6 символов');
        }

        if (!/[a-zA-Z]/.test(value)) {
            errors.push('Должна быть хотя бы одна буква');
        }

        if (!/[0-9]/.test(value)) {
            errors.push('Должна быть хотя бы одна цифра');
        }

        if (errors.length > 0) {
            SecurityManager.showFieldError(input, errors.join(', '));
            return false;
        } else {
            SecurityManager.clearFieldError(input);
            return true;
        }
    }

    /**
     * Валидация имени пользователя
     */
    validateUsername(event) {
        const input = event.target;
        const value = input.value.trim();
        const usernameRegex = /^[a-zA-Z0-9_-]+$/;

        if (value && value.length < 3) {
            SecurityManager.showFieldError(input, 'Минимум 3 символа');
            return false;
        }

        if (value && !usernameRegex.test(value)) {
            SecurityManager.showFieldError(input, 'Только буквы, цифры, дефис и подчеркивание');
            return false;
        } else {
            SecurityManager.clearFieldError(input);
            return true;
        }
    }

    /**
     * Валидация совпадения паролей
     */
    validatePasswordMatch(passwordInput, confirmInput) {
        if (passwordInput.value !== confirmInput.value) {
            SecurityManager.showFieldError(confirmInput, 'Пароли не совпадают');
            return false;
        } else {
            SecurityManager.clearFieldError(confirmInput);
            return true;
        }
    }

    /**
     * Показать ошибку поля
     */
    static showFieldError(input, message) {
        SecurityManager.clearFieldError(input);
        
        input.classList.add('error');
        
        const errorDiv = document.createElement('div');
        errorDiv.className = 'field-error';
        errorDiv.textContent = message;
        
        input.parentNode.appendChild(errorDiv);
    }

    /**
     * Очистить ошибку поля
     */
    static clearFieldError(input) {
        input.classList.remove('error');
        
        const existingError = input.parentNode.querySelector('.field-error');
        if (existingError) {
            existingError.remove();
        }
    }

    /**
     * Защита от XSS при выводе пользовательских данных
     */
    static escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Безопасная установка innerHTML
     */
    static safeSetHTML(element, html) {
        // Простая очистка от опасных тегов
        const cleanHTML = html.replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '')
                             .replace(/<iframe\b[^<]*(?:(?!<\/iframe>)<[^<]*)*<\/iframe>/gi, '')
                             .replace(/javascript:/gi, '')
                             .replace(/on\w+\s*=/gi, '');
        
        element.innerHTML = cleanHTML;
    }

    /**
     * Логирование подозрительных действий
     */
    static logSuspiciousActivity(type, details) {
        try {
            fetch('api/log-security.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    type: type,
                    details: details,
                    timestamp: new Date().toISOString(),
                    url: window.location.href
                })
            });
        } catch (error) {
            console.error('Ошибка логирования:', error);
        }
    }
}

// Инициализация при загрузке страницы
document.addEventListener('DOMContentLoaded', function() {
    window.securityManager = new SecurityManager();
    
    // Добавляем CSS для ошибок валидации
    const style = document.createElement('style');
    style.textContent = `
        .error {
            border-color: #e74c3c !important;
            box-shadow: 0 0 5px rgba(231, 76, 60, 0.3) !important;
        }
        
        .field-error {
            color: #e74c3c;
            font-size: 0.8em;
            margin-top: 5px;
            display: block;
        }
        
        .security-warning {
            background-color: #ffe6e6;
            border: 1px solid #ffcccc;
            color: #cc0000;
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
            font-size: 0.9em;
        }
    `;
    document.head.appendChild(style);
});

// Обновление CSRF токена каждые 30 минут
setInterval(() => {
    if (window.securityManager) {
        window.securityManager.refreshCSRFToken();
    }
}, 30 * 60 * 1000);

// Предупреждение при попытке уйти со страницы с несохраненными данными
let isFormSubmitting = false;
let formSubmitHandled = false;

// Отслеживаем отправку форм
document.addEventListener('submit', function(e) {
    const form = e.target;
    
    // Для форм входа - не показываем предупреждение
    if (form.querySelector('input[name="username"]') && form.querySelector('input[name="password"]')) {
        isFormSubmitting = true;
        formSubmitHandled = true;
        
        // Снимаем обработчик beforeunload для форм входа
        window.removeEventListener('beforeunload', beforeUnloadHandler);
        return;
    }
    
    // Для других форм - разрешаем отправку
    isFormSubmitting = true;
});

function beforeUnloadHandler(e) {
    // Не показываем предупреждение если форма отправляется
    if (isFormSubmitting || formSubmitHandled) {
        return;
    }
    
    // Проверяем только формы с классом 'needs-save-warning' или длинные формы
    const complexForms = document.querySelectorAll('form.needs-save-warning, form textarea, form input[type="file"]');
    let hasUnsavedChanges = false;
    
    complexForms.forEach(form => {
        const inputs = form.querySelectorAll('input:not([type="hidden"]):not([type="submit"]), textarea, select');
        inputs.forEach(input => {
            if (input.defaultValue !== input.value && input.value.trim() !== '') {
                hasUnsavedChanges = true;
            }
        });
    });
    
    // Показываем предупреждение только для сложных форм с данными
    if (hasUnsavedChanges) {
        e.preventDefault();
        e.returnValue = 'У вас есть несохраненные изменения. Вы действительно хотите покинуть страницу?';
        return e.returnValue;
    }
}

// Устанавливаем обработчик только для страниц с длинными формами
if (document.querySelector('form textarea, form input[type="file"], form.needs-save-warning')) {
    window.addEventListener('beforeunload', beforeUnloadHandler);
}

/**
 * Аудиоплеер для QuizCard
 * Обеспечивает воспроизведение аудиофайлов для словарных слов
 */

class QuizCardAudioPlayer {
    constructor() {
        this.currentAudio = null;
        this.isPlaying = false;
        this.volume = 0.8;
        this.init();
    }

    /**
     * Инициализация аудиоплеера
     */
    init() {
        // Устанавливаем обработчики для всех аудио кнопок на странице
        this.attachAudioButtonListeners();
        
        // Создаем глобальный аудио элемент
        this.setupGlobalAudio();
        
        // Обработчики клавиатуры
        this.setupKeyboardShortcuts();
    }

    /**
     * Настройка глобального аудио элемента
     */
    setupGlobalAudio() {
        if (!this.currentAudio) {
            this.currentAudio = new Audio();
            this.currentAudio.volume = this.volume;
            
            // Обработчики событий аудио
            this.currentAudio.addEventListener('ended', () => {
                this.onAudioEnded();
            });
            
            this.currentAudio.addEventListener('error', (e) => {
                this.onAudioError(e);
            });
            
            this.currentAudio.addEventListener('loadstart', () => {
                this.onAudioLoadStart();
            });
            
            this.currentAudio.addEventListener('canplay', () => {
                this.onAudioCanPlay();
            });
        }
    }

    /**
     * Присоединение обработчиков к аудио кнопкам
     */
    attachAudioButtonListeners() {
        // Обработчики для кнопок воспроизведения
        document.addEventListener('click', (e) => {
            if (e.target.matches('.audio-play-btn, .audio-play-btn *')) {
                e.preventDefault();
                e.stopPropagation(); // Предотвращаем всплытие события
                const button = e.target.closest('.audio-play-btn');
                if (button) {
                    const audioPath = button.dataset.audioPath;
                    const wordId = button.dataset.wordId;
                    this.playAudio(audioPath, button, wordId);
                }
            }
        });

        // Обработчики для кнопок остановки
        document.addEventListener('click', (e) => {
            if (e.target.matches('.audio-stop-btn, .audio-stop-btn *')) {
                e.preventDefault();
                e.stopPropagation(); // Предотвращаем всплытие события
                this.stopAudio();
            }
        });
    }

    /**
     * Воспроизведение аудиофайла
     * @param {string} audioPath Путь к аудиофайлу
     * @param {Element} button Кнопка воспроизведения
     * @param {string} wordId ID слова (опционально)
     */
    playAudio(audioPath, button, wordId = null) {
        if (!audioPath) {
            console.warn('Аудиофайл не найден');
            return;
        }

        // Останавливаем текущее воспроизведение
        this.stopAudio();

        try {
            // Устанавливаем новый источник
            this.currentAudio.src = audioPath;
            this.currentAudio.currentTime = 0;

            // Обновляем UI
            this.setPlayingState(button, true);

            // Запускаем воспроизведение
            const playPromise = this.currentAudio.play();
            
            if (playPromise !== undefined) {
                playPromise.then(() => {
                    this.isPlaying = true;
                    this.onPlayStart(button, wordId);
                }).catch((error) => {
                    console.error('Ошибка воспроизведения:', error);
                    this.onPlayError(button, error);
                });
            }

        } catch (error) {
            console.error('Ошибка при настройке аудио:', error);
            this.onPlayError(button, error);
        }
    }

    /**
     * Остановка воспроизведения
     */
    stopAudio() {
        if (this.currentAudio && this.isPlaying) {
            this.currentAudio.pause();
            this.currentAudio.currentTime = 0;
            this.isPlaying = false;
            
            // Сбрасываем состояние всех кнопок
            this.resetAllPlayButtons();
        }
    }

    /**
     * Переключение воспроизведения/паузы
     * @param {string} audioPath Путь к аудиофайлу
     * @param {Element} button Кнопка
     * @param {string} wordId ID слова
     */
    toggleAudio(audioPath, button, wordId = null) {
        if (this.isPlaying && this.currentAudio.src.includes(audioPath)) {
            this.stopAudio();
        } else {
            this.playAudio(audioPath, button, wordId);
        }
    }

    /**
     * Установка громкости
     * @param {number} volume Громкость (0-1)
     */
    setVolume(volume) {
        this.volume = Math.max(0, Math.min(1, volume));
        if (this.currentAudio) {
            this.currentAudio.volume = this.volume;
        }
    }

    /**
     * Получение текущей громкости
     * @returns {number} Текущая громкость
     */
    getVolume() {
        return this.volume;
    }

    /**
     * Обработка начала воспроизведения
     * @param {Element} button Кнопка воспроизведения
     * @param {string} wordId ID слова
     */
    onPlayStart(button, wordId) {
        // Можно добавить аналитику или другую логику
        console.log('Воспроизведение началось:', wordId);
    }

    /**
     * Обработка ошибки воспроизведения
     * @param {Element} button Кнопка воспроизведения
     * @param {Error} error Ошибка
     */
    onPlayError(button, error) {
        this.setPlayingState(button, false);
        this.showErrorMessage('Ошибка воспроизведения аудио');
        console.error('Ошибка аудио:', error);
    }

    /**
     * Обработка завершения воспроизведения
     */
    onAudioEnded() {
        this.isPlaying = false;
        this.resetAllPlayButtons();
    }

    /**
     * Обработка ошибки аудио
     * @param {Event} event Событие ошибки
     */
    onAudioError(event) {
        this.isPlaying = false;
        this.resetAllPlayButtons();
        this.showErrorMessage('Не удалось загрузить аудиофайл');
    }

    /**
     * Обработка начала загрузки аудио
     */
    onAudioLoadStart() {
        // Показываем индикатор загрузки если нужно
    }

    /**
     * Обработка готовности аудио к воспроизведению
     */
    onAudioCanPlay() {
        // Скрываем индикатор загрузки если нужно
    }

    /**
     * Установка состояния кнопки воспроизведения
     * @param {Element} button Кнопка
     * @param {boolean} isPlaying Состояние воспроизведения
     */
    setPlayingState(button, isPlaying) {
        if (!button) return;

        const icon = button.querySelector('i, .icon');
        const text = button.querySelector('.btn-text');

        if (isPlaying) {
            button.classList.add('playing');
            if (icon) {
                icon.className = 'fas fa-pause';
            }
            if (text) {
                text.textContent = 'Пауза';
            }
            button.title = 'Остановить воспроизведение';
        } else {
            button.classList.remove('playing');
            if (icon) {
                icon.className = 'fas fa-play';
            }
            if (text) {
                text.textContent = 'Воспроизвести';
            }
            button.title = 'Воспроизвести аудио';
        }
    }

    /**
     * Сброс состояния всех кнопок воспроизведения
     */
    resetAllPlayButtons() {
        const buttons = document.querySelectorAll('.audio-play-btn');
        buttons.forEach(button => {
            this.setPlayingState(button, false);
        });
    }

    /**
     * Показ сообщения об ошибке
     * @param {string} message Сообщение
     */
    showErrorMessage(message) {
        // Простое уведомление, можно заменить на более красивое
        if (typeof window.showNotification === 'function') {
            window.showNotification(message, 'error');
        } else {
            console.warn(message);
            // Можно показать toast или другое уведомление
        }
    }

    /**
     * Настройка горячих клавиш
     */
    setupKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            // Проверяем, что не находимся в поле ввода
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') {
                return;
            }

            switch (e.key) {
                case ' ': // Пробел - пауза/воспроизведение
                    e.preventDefault();
                    if (this.isPlaying) {
                        this.stopAudio();
                    } else {
                        // Находим первую доступную кнопку аудио на странице
                        const firstAudioBtn = document.querySelector('.audio-play-btn[data-audio-path]');
                        if (firstAudioBtn && firstAudioBtn.dataset.audioPath) {
                            this.playAudio(firstAudioBtn.dataset.audioPath, firstAudioBtn, firstAudioBtn.dataset.wordId);
                        }
                    }
                    break;
                
                case 'Escape': // Escape - остановка
                    this.stopAudio();
                    break;
            }
        });
    }

    /**
     * Проверка поддержки аудио в браузере
     * @param {string} mimeType MIME тип аудио
     * @returns {boolean} Поддерживается ли формат
     */
    isAudioSupported(mimeType) {
        const audio = new Audio();
        return audio.canPlayType(mimeType) !== '';
    }

    /**
     * Получение поддерживаемых форматов
     * @returns {Object} Объект с поддерживаемыми форматами
     */
    getSupportedFormats() {
        const audio = new Audio();
        return {
            mp3: audio.canPlayType('audio/mpeg') !== '',
            wav: audio.canPlayType('audio/wav') !== '',
            ogg: audio.canPlayType('audio/ogg') !== ''
        };
    }

    /**
     * Очистка ресурсов
     */
    destroy() {
        this.stopAudio();
        if (this.currentAudio) {
            this.currentAudio.src = '';
            this.currentAudio = null;
        }
        this.isPlaying = false;
    }
}

// Инициализация аудиоплеера при загрузке страницы
let quizCardAudioPlayer;

document.addEventListener('DOMContentLoaded', function() {
    quizCardAudioPlayer = new QuizCardAudioPlayer();
    
    // Экспортируем в глобальную область видимости для использования в других скриптах
    window.quizCardAudioPlayer = quizCardAudioPlayer;
});

// Дополнительные утилиты для работы с аудио

/**
 * Создание кнопки воспроизведения аудио
 * @param {string} audioPath Путь к аудиофайлу
 * @param {string} wordId ID слова
 * @param {string} buttonClass Дополнительные CSS классы
 * @returns {string} HTML кнопки
 */
function createAudioButton(audioPath, wordId = '', buttonClass = '') {
    if (!audioPath) {
        return '';
    }

    return `
        <button type="button" 
                class="audio-play-btn ${buttonClass}" 
                data-audio-path="${audioPath}" 
                data-word-id="${wordId}"
                title="Воспроизвести аудио">
            <i class="fas fa-play"></i>
            <span class="btn-text d-none d-sm-inline">Воспроизвести</span>
        </button>
    `;
}

/**
 * Создание компактной кнопки воспроизведения аудио
 * @param {string} audioPath Путь к аудиофайлу
 * @param {string} wordId ID слова
 * @returns {string} HTML компактной кнопки
 */
function createCompactAudioButton(audioPath, wordId = '') {
    if (!audioPath) {
        return '';
    }

    return `
        <button type="button" 
                class="audio-play-btn btn btn-sm btn-outline-primary" 
                data-audio-path="${audioPath}" 
                data-word-id="${wordId}"
                title="Воспроизвести аудио">
            <i class="fas fa-volume-up"></i>
        </button>
    `;
}

/**
 * Проверка наличия аудиофайла
 * @param {string} audioPath Путь к аудиофайлу
 * @returns {boolean} Есть ли аудиофайл
 */
function hasAudio(audioPath) {
    return audioPath && audioPath.trim() !== '';
}

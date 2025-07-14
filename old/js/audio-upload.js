/**
 * JavaScript для загрузки аудиофайлов в QuizCard
 * Обеспечивает валидацию, предпросмотр и загрузку аудиофайлов
 */

class AudioUploader {
    constructor(options = {}) {
        this.maxSize = options.maxSize || 3 * 1024 * 1024; // 3MB
        this.maxDuration = options.maxDuration || 30; // 30 секунд
        this.allowedTypes = options.allowedTypes || ['audio/mpeg', 'audio/wav', 'audio/ogg', 'audio/mp3'];
        this.allowedExtensions = options.allowedExtensions || ['mp3', 'wav', 'ogg'];
        
        this.currentFile = null;
        this.previewAudio = null;
        
        this.init();
    }

    /**
     * Инициализация загрузчика аудио
     */
    init() {
        this.setupFileInputListeners();
        this.setupDragAndDrop();
        this.setupPreviewControls();
    }

    /**
     * Настройка обработчиков для input[type="file"]
     */
    setupFileInputListeners() {
        document.addEventListener('change', (e) => {
            if (e.target.type === 'file' && e.target.accept && e.target.accept.includes('audio/')) {
                this.handleFileSelect(e.target.files, e.target);
            }
        });
    }

    /**
     * Настройка drag & drop для аудиофайлов
     */
    setupDragAndDrop() {
        // Находим все зоны для drag & drop аудио
        const dropZones = document.querySelectorAll('.audio-drop-zone, .audio-upload-area');
        
        dropZones.forEach(zone => {
            zone.addEventListener('dragover', (e) => {
                e.preventDefault();
                zone.classList.add('drag-over');
            });

            zone.addEventListener('dragleave', (e) => {
                e.preventDefault();
                zone.classList.remove('drag-over');
            });

            zone.addEventListener('drop', (e) => {
                e.preventDefault();
                zone.classList.remove('drag-over');
                
                const files = e.dataTransfer.files;
                const fileInput = zone.querySelector('input[type="file"]');
                
                if (files.length > 0 && fileInput) {
                    // Присваиваем файлы к input
                    fileInput.files = files;
                    this.handleFileSelect(files, fileInput);
                }
            });
        });
    }

    /**
     * Настройка элементов управления предпросмотром
     */
    setupPreviewControls() {
        document.addEventListener('click', (e) => {
            if (e.target.matches('.audio-preview-play, .audio-preview-play *')) {
                e.preventDefault();
                const button = e.target.closest('.audio-preview-play');
                this.togglePreviewPlayback(button);
            }

            if (e.target.matches('.audio-remove-btn, .audio-remove-btn *')) {
                e.preventDefault();
                const button = e.target.closest('.audio-remove-btn');
                this.removeAudioFile(button);
            }
        });
    }

    /**
     * Обработка выбора файлов
     * @param {FileList} files Выбранные файлы
     * @param {HTMLInputElement} input Input элемент
     */
    handleFileSelect(files, input) {
        if (files.length === 0) {
            this.clearPreview(input);
            return;
        }

        const file = files[0];
        const validationResult = this.validateAudioFile(file);

        if (!validationResult.valid) {
            this.showValidationErrors(validationResult.errors, input);
            this.clearFileInput(input);
            return;
        }

        this.currentFile = file;
        this.showAudioPreview(file, input);
    }

    /**
     * Валидация аудиофайла
     * @param {File} file Файл для валидации
     * @returns {Object} Результат валидации
     */
    validateAudioFile(file) {
        const result = {
            valid: true,
            errors: []
        };

        // Проверка размера файла
        if (file.size > this.maxSize) {
            result.valid = false;
            result.errors.push(`Размер файла слишком большой. Максимум: ${this.formatFileSize(this.maxSize)}, текущий: ${this.formatFileSize(file.size)}`);
        }

        // Проверка типа файла
        if (!this.allowedTypes.includes(file.type)) {
            result.valid = false;
            result.errors.push(`Недопустимый тип файла. Разрешены: ${this.allowedExtensions.join(', ').toUpperCase()}`);
        }

        // Проверка расширения файла
        const extension = this.getFileExtension(file.name);
        if (!this.allowedExtensions.includes(extension)) {
            result.valid = false;
            result.errors.push(`Недопустимое расширение файла. Разрешены: ${this.allowedExtensions.join(', ').toUpperCase()}`);
        }

        return result;
    }

    /**
     * Показ предпросмотра аудиофайла
     * @param {File} file Аудиофайл
     * @param {HTMLInputElement} input Input элемент
     */
    showAudioPreview(file, input) {
        const previewContainer = this.findPreviewContainer(input);
        if (!previewContainer) {
            console.warn('Контейнер предпросмотра не найден');
            return;
        }

        // Создаем URL для предпросмотра
        const audioUrl = URL.createObjectURL(file);
        
        // Создаем элемент аудио для предпросмотра
        if (this.previewAudio) {
            this.previewAudio.pause();
            URL.revokeObjectURL(this.previewAudio.src);
        }
        
        this.previewAudio = new Audio(audioUrl);
        
        // Обработчики событий аудио
        this.previewAudio.addEventListener('loadedmetadata', () => {
            this.validateAudioDuration(this.previewAudio.duration, input);
        });

        this.previewAudio.addEventListener('ended', () => {
            this.updatePreviewButton(previewContainer, false);
        });

        // Создаем HTML предпросмотра
        const previewHtml = this.createPreviewHtml(file, audioUrl);
        previewContainer.innerHTML = previewHtml;
        previewContainer.style.display = 'block';
    }

    /**
     * Создание HTML предпросмотра
     * @param {File} file Аудиофайл
     * @param {string} audioUrl URL аудиофайла
     * @returns {string} HTML предпросмотра
     */
    createPreviewHtml(file, audioUrl) {
        return `
            <div class="audio-preview-item">
                <div class="audio-preview-info">
                    <div class="audio-preview-name">
                        <i class="fas fa-music"></i>
                        <span class="file-name">${this.escapeHtml(file.name)}</span>
                    </div>
                    <div class="audio-preview-details">
                        <span class="file-size">${this.formatFileSize(file.size)}</span>
                        <span class="file-type">${this.getFileExtension(file.name).toUpperCase()}</span>
                    </div>
                </div>
                <div class="audio-preview-controls">
                    <button type="button" class="btn btn-sm btn-outline-primary audio-preview-play" data-audio-url="${audioUrl}">
                        <i class="fas fa-play"></i>
                        <span class="btn-text">Прослушать</span>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger audio-remove-btn">
                        <i class="fas fa-times"></i>
                        <span class="btn-text d-none d-sm-inline">Удалить</span>
                    </button>
                </div>
            </div>
        `;
    }

    /**
     * Переключение воспроизведения предпросмотра
     * @param {HTMLElement} button Кнопка воспроизведения
     */
    togglePreviewPlayback(button) {
        if (!this.previewAudio) return;

        if (this.previewAudio.paused) {
            this.previewAudio.play();
            this.updatePreviewButton(button.closest('.audio-preview-item'), true);
        } else {
            this.previewAudio.pause();
            this.updatePreviewButton(button.closest('.audio-preview-item'), false);
        }
    }

    /**
     * Обновление кнопки предпросмотра
     * @param {HTMLElement} container Контейнер предпросмотра
     * @param {boolean} isPlaying Статус воспроизведения
     */
    updatePreviewButton(container, isPlaying) {
        const button = container.querySelector('.audio-preview-play');
        const icon = button.querySelector('i');
        const text = button.querySelector('.btn-text');

        if (isPlaying) {
            icon.className = 'fas fa-pause';
            text.textContent = 'Пауза';
            button.classList.add('playing');
        } else {
            icon.className = 'fas fa-play';
            text.textContent = 'Прослушать';
            button.classList.remove('playing');
        }
    }

    /**
     * Удаление аудиофайла
     * @param {HTMLElement} button Кнопка удаления
     */
    removeAudioFile(button) {
        const container = button.closest('.audio-preview-item');
        const previewContainer = container.closest('.audio-preview-container');
        const fileInput = this.findRelatedFileInput(previewContainer);

        // Останавливаем воспроизведение
        if (this.previewAudio) {
            this.previewAudio.pause();
            URL.revokeObjectURL(this.previewAudio.src);
            this.previewAudio = null;
        }

        // Очищаем input
        if (fileInput) {
            this.clearFileInput(fileInput);
        }

        // Скрываем предпросмотр
        this.clearPreview(fileInput);
        
        this.currentFile = null;
    }

    /**
     * Валидация длительности аудио
     * @param {number} duration Длительность в секундах
     * @param {HTMLInputElement} input Input элемент
     */
    validateAudioDuration(duration, input) {
        if (duration > this.maxDuration) {
            const errors = [`Длительность аудио слишком большая. Максимум: ${this.maxDuration} сек., текущая: ${Math.round(duration)} сек.`];
            this.showValidationErrors(errors, input);
            this.removeAudioFile(input.closest('.audio-upload-container').querySelector('.audio-remove-btn'));
        }
    }

    /**
     * Показ ошибок валидации
     * @param {Array} errors Массив ошибок
     * @param {HTMLInputElement} input Input элемент
     */
    showValidationErrors(errors, input) {
        const errorContainer = this.findErrorContainer(input);
        
        if (errorContainer) {
            const errorHtml = errors.map(error => `<div class="text-danger small">${this.escapeHtml(error)}</div>`).join('');
            errorContainer.innerHTML = errorHtml;
            errorContainer.style.display = 'block';
        } else {
            // Fallback: показываем алерт
            alert('Ошибки валидации:\n' + errors.join('\n'));
        }
    }

    /**
     * Очистка предпросмотра
     * @param {HTMLInputElement} input Input элемент
     */
    clearPreview(input) {
        const previewContainer = this.findPreviewContainer(input);
        if (previewContainer) {
            previewContainer.innerHTML = '';
            previewContainer.style.display = 'none';
        }

        const errorContainer = this.findErrorContainer(input);
        if (errorContainer) {
            errorContainer.innerHTML = '';
            errorContainer.style.display = 'none';
        }
    }

    /**
     * Очистка file input
     * @param {HTMLInputElement} input Input элемент
     */
    clearFileInput(input) {
        if (input) {
            input.value = '';
        }
    }

    /**
     * Поиск контейнера предпросмотра
     * @param {HTMLInputElement} input Input элемент
     * @returns {HTMLElement|null} Контейнер предпросмотра
     */
    findPreviewContainer(input) {
        const container = input.closest('.audio-upload-container, .form-group, .mb-3');
        return container ? container.querySelector('.audio-preview-container') : null;
    }

    /**
     * Поиск контейнера ошибок
     * @param {HTMLInputElement} input Input элемент
     * @returns {HTMLElement|null} Контейнер ошибок
     */
    findErrorContainer(input) {
        const container = input.closest('.audio-upload-container, .form-group, .mb-3');
        return container ? container.querySelector('.audio-error-container') : null;
    }

    /**
     * Поиск связанного file input
     * @param {HTMLElement} previewContainer Контейнер предпросмотра
     * @returns {HTMLInputElement|null} File input
     */
    findRelatedFileInput(previewContainer) {
        const container = previewContainer.closest('.audio-upload-container, .form-group, .mb-3');
        return container ? container.querySelector('input[type="file"]') : null;
    }

    /**
     * Получение расширения файла
     * @param {string} filename Имя файла
     * @returns {string} Расширение файла
     */
    getFileExtension(filename) {
        return filename.split('.').pop().toLowerCase();
    }

    /**
     * Форматирование размера файла
     * @param {number} bytes Размер в байтах
     * @returns {string} Отформатированный размер
     */
    formatFileSize(bytes) {
        if (bytes >= 1024 * 1024) {
            return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
        } else if (bytes >= 1024) {
            return (bytes / 1024).toFixed(2) + ' KB';
        } else {
            return bytes + ' B';
        }
    }

    /**
     * Экранирование HTML
     * @param {string} text Текст для экранирования
     * @returns {string} Экранированный текст
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Очистка ресурсов
     */
    destroy() {
        if (this.previewAudio) {
            this.previewAudio.pause();
            URL.revokeObjectURL(this.previewAudio.src);
            this.previewAudio = null;
        }
        this.currentFile = null;
    }
}

// Инициализация загрузчика аудио при загрузке страницы
let audioUploader;

document.addEventListener('DOMContentLoaded', function() {
    audioUploader = new AudioUploader();
    
    // Экспортируем в глобальную область видимости
    window.audioUploader = audioUploader;
});

// Утилиты для создания HTML элементов загрузки аудио

/**
 * Создание HTML для загрузки аудиофайла
 * @param {string} inputName Имя input поля
 * @param {string} currentAudio Текущий аудиофайл (опционально)
 * @returns {string} HTML для загрузки аудио
 */
function createAudioUploadHtml(inputName, currentAudio = '') {
    const hasCurrentAudio = currentAudio && currentAudio.trim() !== '';
    
    return `
        <div class="audio-upload-container">
            <div class="audio-drop-zone">
                <input type="file" 
                       name="${inputName}" 
                       id="${inputName}" 
                       accept="audio/mp3,audio/wav,audio/ogg,audio/mpeg" 
                       class="form-control">
                <div class="drop-zone-text">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <p>Перетащите аудиофайл сюда или нажмите для выбора</p>
                    <small class="text-muted">MP3, WAV, OGG • Максимум 3MB • До 30 секунд</small>
                </div>
            </div>
            
            <div class="audio-preview-container" style="display: none;"></div>
            <div class="audio-error-container" style="display: none;"></div>
            
            ${hasCurrentAudio ? `
                <div class="current-audio-info">
                    <small class="text-muted">Текущий аудиофайл:</small>
                    <div class="current-audio-item">
                        ${createCompactAudioButton(currentAudio, '')}
                        <span class="current-audio-name">${currentAudio.split('/').pop()}</span>
                    </div>
                </div>
            ` : ''}
        </div>
    `;
}

/**
 * Создание простого input для аудио
 * @param {string} inputName Имя input поля
 * @param {string} label Подпись поля
 * @returns {string} HTML простого input
 */
function createSimpleAudioInput(inputName, label = 'Аудиофайл') {
    return `
        <div class="mb-3 audio-upload-container">
            <label for="${inputName}" class="form-label">${label}</label>
            <input type="file" 
                   name="${inputName}" 
                   id="${inputName}" 
                   accept="audio/mp3,audio/wav,audio/ogg,audio/mpeg" 
                   class="form-control">
            <div class="form-text">MP3, WAV, OGG • Максимум 3MB • До 30 секунд</div>
            <div class="audio-preview-container" style="display: none;"></div>
            <div class="audio-error-container" style="display: none;"></div>
        </div>
    `;
}

<?php
/*
 * Инструкция по добавлению переключателя языков в новые страницы
 * 
 * 1. Добавьте после всех require_once в начале файла:
 * require_once '../includes/translations.php';
 * 
 * 2. В HTML разметке добавьте атрибуты data-translate-key для элементов, которые нужно переводить:
 * <h2 data-translate-key="page_title">Заголовок страницы</h2>
 * <p data-translate-key="description">Описание</p>
 * <button data-translate-key="button_text">Кнопка</button>
 * 
 * 3. Для placeholder добавьте атрибут:
 * <input type="text" data-translate-key="placeholder_key" placeholder="Текст">
 * 
 * 4. Для confirm диалогов добавьте data-confirm-key:
 * <a onclick="return confirm('Вы уверены?')" data-confirm-key="confirm_key">Удалить</a>
 * 
 * 5. В контейнер с основным содержимым добавьте:
 * <div class="container">
 *     <?php include 'language_switcher.php'; ?>
 *     <!-- Остальное содержимое -->
 * </div>
 * 
 * 6. Добавьте переводы в файл /includes/translations.php в соответствующие языковые секции:
 * 'page_title' => 'Заголовок страницы',
 * 'description' => 'Описание',
 * 'button_text' => 'Кнопка',
 * 'placeholder_key' => 'Текст placeholder',
 * 'confirm_key' => 'Подтверждение',
 * 
 * 7. Для заголовков страниц (tests_title, students_title, account_title) переводы уже добавлены
 */

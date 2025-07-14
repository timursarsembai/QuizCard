<?php

namespace App\Controllers;

class TeacherLanguageSwitcherController 
{
    public function render() 
    {
        // Загружаем view для переключателя языков
        include __DIR__ . '/../Views/teacher/language_switcher.php';
    }
}

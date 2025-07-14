<?php

namespace App\Controllers;

class StudentLanguageSwitcherController 
{
    public function render() 
    {
        // Загружаем view для переключателя языков студента
        include __DIR__ . '/../Views/student/language_switcher.php';
    }
}

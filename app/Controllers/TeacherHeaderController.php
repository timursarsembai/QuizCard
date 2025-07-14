<?php

namespace App\Controllers;

class TeacherHeaderController 
{
    public function render() 
    {
        // Загружаем view для header преподавателя
        include __DIR__ . '/../Views/teacher/header.php';
    }
}

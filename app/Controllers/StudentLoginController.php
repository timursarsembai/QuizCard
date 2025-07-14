<?php

namespace App\Controllers;

class StudentLoginController 
{
    public function login() 
    {
        // Перенаправляем на основной контроллер логина
        header("Location: /login");
        exit();
    }
}

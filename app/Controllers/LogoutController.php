<?php

namespace App\Controllers;

class LogoutController 
{
    private $db;
    
    public function __construct($db) 
    {
        $this->db = $db;
    }
    
    public function index() 
    {
        session_start();
        session_destroy();
        header("Location: /");
        exit();
    }
}

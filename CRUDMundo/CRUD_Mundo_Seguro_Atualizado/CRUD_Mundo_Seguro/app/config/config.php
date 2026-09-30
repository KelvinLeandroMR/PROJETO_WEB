<?php

declare(strict_types=1);

const APP_NAME = 'CRUD Mundo - Segurança de Dados';
const APP_BASE_URL = '';
const MAX_LOGIN_ATTEMPTS = 3;
const LOCKOUT_MINUTES = 15;

// Ajuste para o seu MySQL/XAMPP/WAMP.
const DB_HOST = '127.0.0.1';
const DB_PORT = '3306';
const DB_NAME = 'bd_mundo';
const DB_USER = 'root';
const DB_PASS = '';

// Segurança de sessão.
ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    ini_set('session.cookie_secure', '1');
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

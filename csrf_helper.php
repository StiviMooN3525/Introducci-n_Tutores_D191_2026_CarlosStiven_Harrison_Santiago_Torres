<?php
/**
 * Helper para protección CSRF
 */

/**
 * Genera token CSRF y lo guarda en sesión
 */
function generate_csrf_token() {
    if (!isset($_SESSION)) {
        session_start();
    }
    
    $token = bin2hex(random_bytes(32));
    $_SESSION['csrf_token'] = $token;
    $_SESSION['csrf_token_time'] = time();
    
    return $token;
}

/**
 * Obtiene el token CSRF actual
 */
function get_csrf_token() {
    if (!isset($_SESSION)) {
        session_start();
    }
    
    return $_SESSION['csrf_token'] ?? generate_csrf_token();
}

/**
 * Verifica si el token CSRF es válido
 */
function verify_csrf_token($token, $max_age = 3600) {
    if (!isset($_SESSION)) {
        session_start();
    }
    
    if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time'])) {
        return false;
    }
    
    // Verificar que el token coincida
    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        return false;
    }
    
    // Verificar que no haya expirado (1 hora por defecto)
    if (time() - $_SESSION['csrf_token_time'] > $max_age) {
        unset($_SESSION['csrf_token']);
        unset($_SESSION['csrf_token_time']);
        return false;
    }
    
    return true;
}

/**
 * Genera campo HTML oculto para CSRF
 */
function csrf_field() {
    $token = get_csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}
?>

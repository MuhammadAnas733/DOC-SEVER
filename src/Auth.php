<?php
// src/Auth.php

namespace Hospital;

require_once __DIR__ . '/Config.php';

use Exception;

class Auth {
    public static function login($username, $password) {
        $username = trim($username);
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            if (session_status() === PHP_SESSION_NONE) {
                session_save_path(__DIR__ . '/../sessions');
                session_start();
            }
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            return true;
        }
        return false;
    }

    public static function logout() {
        if (session_status() === PHP_SESSION_NONE) {
            session_save_path(__DIR__ . '/../sessions');
            session_start();
        }
        session_destroy();
    }

    public static function check() {
        if (session_status() === PHP_SESSION_NONE) {
            session_save_path(__DIR__ . '/../sessions');
            session_start();
        }
        return isset($_SESSION['user_id']);
    }

    public static function isAdmin() {
        return self::check() && ($_SESSION['role'] ?? '') === 'admin';
    }

    public static function requireLogin() {
        if (!self::check()) {
            header("Location: login.php");
            exit;
        }
    }

    public static function requireAdmin() {
        self::requireLogin();
        if (!self::isAdmin()) {
            http_response_code(403);
            die("Access Denied: Admin privileges required.");
        }
    }
}

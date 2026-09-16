<?php

declare(strict_types=1);

namespace Safe360\Controllers;

use Safe360\Core\Auth;
use Safe360\Core\Csrf;
use Safe360\Core\Request;
use Safe360\Core\Response;

final class AuthController
{
    public function login(): void
    {
        Csrf::requireValid();
        $email = trim((string) Request::input('email', ''));
        $password = (string) Request::input('password', '');

        if ($email === '' || $password === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['_flash_error'] = 'Enter a valid email address and password.';
            Response::redirect('/login');
        }

        if (!Auth::attempt($email, $password)) {
            usleep(random_int(80000, 160000));
            $_SESSION['_flash_error'] = 'The email or password is incorrect, or the account is inactive.';
            Response::redirect('/login');
        }

        $user = Auth::user();
        Response::redirect($user && $user['role'] === 'admin' ? '/admin' : '/dashboard');
    }

    public function logout(): void
    {
        Auth::requireUser();
        Csrf::requireValid();
        Auth::logout();
        Response::redirect('/login');
    }
}


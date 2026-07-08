<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;

final class AuthController extends Controller
{
    public function showLogin(Request $request): void
    {
        if (Auth::check()) {
            $this->redirect('/');
        }

        $this->render('auth/login', ['title' => 'Giriş']);
    }

    public function login(Request $request): void
    {
        $this->verifyCsrf($request);

        $email = (string) $request->input('email', '');
        $password = (string) $request->input('password', '');

        if (Auth::attempt($email, $password)) {
            $this->redirect('/');
        }

        $this->flash('error', 'Email və ya parol yanlışdır.');
        $this->redirect('/login');
    }

    public function showRegister(Request $request): void
    {
        if (Auth::check()) {
            $this->redirect('/');
        }

        $this->render('auth/register', ['title' => 'Qeydiyyat']);
    }

    public function register(Request $request): void
    {
        $this->verifyCsrf($request);

        $name = trim((string) $request->input('name', ''));
        $email = trim((string) $request->input('email', ''));
        $password = (string) $request->input('password', '');

        if ($name === '' || $email === '' || strlen($password) < 6) {
            $this->flash('error', 'Bütün sahələri düzgün doldurun (parol min. 6 simvol).');
            $this->redirect('/register');
        }

        $exists = $this->db->fetch('SELECT id FROM users WHERE email = :email', ['email' => $email]);
        if ($exists !== null) {
            $this->flash('error', 'Bu email artıq qeydiyyatdan keçib.');
            $this->redirect('/register');
        }

        $this->db->insert('users', [
            'name' => $name,
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'status' => 'active',
            'is_super' => 0,
        ]);

        Auth::attempt($email, $password);
        $this->redirect('/');
    }

    public function logout(Request $request): void
    {
        $this->verifyCsrf($request);
        Auth::logout();
        $this->redirect('/login');
    }
}

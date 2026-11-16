<?php

namespace App\Controllers;

use App\Models\User;
use Core\Auth;
use Core\Controller;
use Core\Request;
use Core\Response;
use Core\Session;

class AuthController extends Controller
{
    public function showLogin(Request $request): Response
    {
        return $this->view('auth/login', ['title' => 'Log in']);
    }

    public function login(Request $request): Response
    {
        $this->validate($request, ['email' => 'required|email', 'password' => 'required']);

        if (!Auth::attempt(strtolower($request->input('email')), (string) $request->input('password'))) {
            Session::flash('errors', ['email' => 'These credentials do not match our records.']);
            Session::flash('old', ['email' => $request->input('email')]);
            return $this->redirect('/login');
        }

        $this->flash('success', 'Welcome back, ' . Auth::user()['username'] . '!');
        $intended = Session::get('intended', '/');
        Session::forget('intended');
        return $this->redirect($intended);
    }

    public function showRegister(Request $request): Response
    {
        return $this->view('auth/register', ['title' => 'Create an account']);
    }

    public function register(Request $request): Response
    {
        $data = $this->validate($request, [
            'username' => 'required|alpha_dash|min:3|max:30|unique:users,username',
            'email'    => 'required|email|max:191|unique:users,email',
            'password' => 'required|min:8|confirmed',
        ]);

        $id = User::register($data);
        Auth::login(User::find($id));

        $this->flash('success', 'Account created! You can now buy and sell on LootVault.');
        return $this->redirect('/');
    }

    public function logout(Request $request): Response
    {
        Auth::logout();
        $this->flash('info', 'You have been logged out.');
        return $this->redirect('/');
    }
}

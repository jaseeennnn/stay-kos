<?php

namespace App\Controllers;

use CodeIgniter\HTTP\RedirectResponse;
use App\Models\UserModel;

class Auth extends BaseController
{
    protected UserModel $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    // Helper untuk redirect berdasarkan role
    private function redirectByRole(): RedirectResponse
    {
        return session()->get('role') === 'admin'
            ? redirect()->to('/admin/dashboard')
            : redirect()->to('/user/dashboard');
    }

    // View Register
    public function register()
    {
        return view('auth/register');
    }

    // Process Register
    public function registerProcess()
    {
        $rules = [
            'username'         => 'required|min_length[3]|is_unique[users.username]',
            'email'            => 'required|valid_email|is_unique[users.email]',
            'password'         => 'required|min_length[6]',
            'confirm_password' => 'required|matches[password]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $this->userModel->insert([
            'username' => $this->request->getPost('username'),
            'email'    => $this->request->getPost('email'),
            'password' => $this->request->getPost('password'),
            'role'     => 'user',
        ]);

        return redirect()->to('/login')->with('success', 'Registrasi berhasil. Silakan login.');
    }

    // Login View
    public function login()
    {
        if (session()->get('isLoggedIn')) {
            return $this->redirectByRole();
        }

        return view('auth/login');
    }

    // Proses Login
    public function loginProcess()
    {
        $rules = [
            'username' => 'required',
            'password' => 'required',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $username = $this->request->getPost('username');
        $password = $this->request->getPost('password');
        $user     = $this->userModel->findByUsername($username);

        if (! $user || ! password_verify($password, $user['password'])) {
            return redirect()->back()->withInput()->with('error', 'Username atau password salah.');
        }

        session()->set([
            'isLoggedIn' => true,
            'userId'     => $user['id'],
            'username'   => $user['username'],
            'role'       => $user['role'],
        ]);

        return $this->redirectByRole();
    }

    // Proses Logout
    public function logout()
    {
        session()->destroy();
        return redirect()->to('/login');
    }
}
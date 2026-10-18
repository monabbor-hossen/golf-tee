<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Repositories\SettingRepository;
use App\Services\AuthService;

class AuthController
{
    private AuthService $authService;
    private SettingRepository $settingRepo;

    public function __construct(?AuthService $authService = null, ?SettingRepository $settingRepo = null)
    {
        $this->authService = $authService ?? new AuthService();
        $this->settingRepo = $settingRepo ?? new SettingRepository();
    }

    public function login(Request $request): void
    {
        if (Session::isAuthenticated()) {
            Response::redirect('index.php');
        }

        $error = '';
        if ($request->getString('timeout') === '1') {
            $error = 'Your session expired due to inactivity. Please sign in again.';
        }

        if ($request->isPost()) {
            // Validate CSRF token
            Session::validateRequestCsrf($request);

            $email = $request->getString('email');
            $pass  = $request->getString('password');

            $result = $this->authService->attempt($email, $pass);
            if (!empty($result['success'])) {
                Response::redirect('index.php');
            } else {
                $error = $result['error'] ?? 'Invalid email or password.';
            }
        }

        $courseName = $this->settingRepo->get('course_name', 'Pine Valley Golf Club');

        View::render('login', [
            'error'      => $error,
            'courseName' => $courseName,
        ], false);
    }

    public function logout(): never
    {
        $this->authService->logout();
        Response::redirect('login.php');
    }
}

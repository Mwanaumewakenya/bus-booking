<?php
/**
 * Authentication Controller
 * 
 * Handles user authentication (login/logout)
 */

class AuthController extends BaseController 
{
    private $userModel;

    public function __construct() 
    {
        parent::__construct();
        $this->userModel = new User();
    }

    /**
     * Show login form
     */
    public function showLogin(): void 
    {
        // Redirect if already authenticated
        if ($this->isAuthenticated()) {
            $this->redirect(url('dashboard'));
            return;
        }

        $this->view('auth/login', [
            'page_title' => 'Admin Login'
        ]);
    }

    /**
     * Process login
     */
    public function login(): void 
    {
        if (!$this->validateCsrf()) {
            $this->setError('Invalid request. Please try again.');
            $this->back();
            return;
        }

        $username = trim($this->request->input('username'));
        $password = $this->request->input('password');

        // Validate inputs
        $errors = $this->validate([
            'username' => 'required|min:3',
            'password' => 'required|min:6'
        ]);

        if (!empty($errors)) {
            $_SESSION['login_errors'] = $errors;
            $this->back();
            return;
        }

        // Attempt authentication
        $user = $this->userModel->authenticate($username, $password);

        if (!$user) {
            $this->setError('Invalid username or password');
            $this->logActivity('Failed login attempt', "Username: $username");
            $this->back();
            return;
        }

        // Set session data
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_data'] = $user;
        
        // Regenerate session ID for security
        session_regenerate_id(true);

        $this->logActivity('Successful login', "User: {$user['name']}");
        $this->setSuccess("Welcome back, {$user['name']}!");

        // Redirect to intended URL or dashboard
        $intendedUrl = $_SESSION['intended_url'] ?? url('dashboard');
        unset($_SESSION['intended_url']);
        $this->redirect($intendedUrl);
    }

    /**
     * Logout user
     */
    public function logout(): void 
    {
        $user = $this->getUser();
        
        if ($user) {
            $this->logActivity('User logout', "User: {$user['name']}");
        }

        // Clear session data
        session_unset();
        session_destroy();

        // Start new session
        session_start();
        session_regenerate_id(true);

        $this->setSuccess('You have been logged out successfully');
        $this->redirect(url());
    }

    /**
     * Check authentication status (AJAX)
     */
    public function checkAuth(): void 
    {
        $this->json([
            'authenticated' => $this->isAuthenticated(),
            'user' => $this->getUser()
        ]);
    }
}
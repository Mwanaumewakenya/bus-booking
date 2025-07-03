<?php
/**
 * Base Controller Class
 * 
 * Provides common functionality for all controllers
 */

abstract class BaseController 
{
    protected $request;
    protected $response;

    public function __construct() 
    {
        $this->request = new Request();
        $this->response = new Response();
    }

    /**
     * Render a view with data
     */
    protected function view(string $view, array $data = []): void 
    {
        $this->response->view($view, $data);
    }

    /**
     * Return JSON response
     */
    protected function json(array $data, int $status = 200): void 
    {
        $this->response->json($data, $status);
    }

    /**
     * Redirect to URL
     */
    protected function redirect(string $url): void 
    {
        $this->response->redirect($url);
    }

    /**
     * Redirect back
     */
    protected function back(): void 
    {
        $this->response->back();
    }

    /**
     * Check if user is authenticated
     */
    protected function isAuthenticated(): bool 
    {
        return isset($_SESSION['user_id']);
    }

    /**
     * Get authenticated user ID
     */
    protected function getUserId(): ?int 
    {
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * Get authenticated user data
     */
    protected function getUser(): ?array 
    {
        if (!$this->isAuthenticated()) {
            return null;
        }

        if (!isset($_SESSION['user_data'])) {
            $userModel = new User();
            $_SESSION['user_data'] = $userModel->find($this->getUserId());
        }

        return $_SESSION['user_data'];
    }

    /**
     * Check if user is admin
     */
    protected function isAdmin(): bool 
    {
        $user = $this->getUser();
        return $user && $user['user_type'] == User::TYPE_ADMIN;
    }

    /**
     * Require authentication
     */
    protected function requireAuth(): void 
    {
        if (!$this->isAuthenticated()) {
            $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'];
            $this->redirect(url('admin'));
            exit;
        }
    }

    /**
     * Require admin access
     */
    protected function requireAdmin(): void 
    {
        $this->requireAuth();
        
        if (!$this->isAdmin()) {
            $this->redirect(url('admin'));
            exit;
        }
    }

    /**
     * Validate CSRF token
     */
    protected function validateCsrf(): bool 
    {
        return validate_csrf();
    }

    /**
     * Set flash message
     */
    protected function setFlash(string $type, string $message): void 
    {
        $_SESSION['flash'] = [
            'type' => $type,
            'message' => $message
        ];
    }

    /**
     * Set success flash message
     */
    protected function setSuccess(string $message): void 
    {
        $this->setFlash('success', $message);
    }

    /**
     * Set error flash message
     */
    protected function setError(string $message): void 
    {
        $this->setFlash('danger', $message);
    }

    /**
     * Set warning flash message
     */
    protected function setWarning(string $message): void 
    {
        $this->setFlash('warning', $message);
    }

    /**
     * Set info flash message
     */
    protected function setInfo(string $message): void 
    {
        $this->setFlash('info', $message);
    }

    /**
     * Get flash message and clear it
     */
    protected function getFlash(): ?array 
    {
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        return $flash;
    }

    /**
     * Validate input data
     */
    protected function validate(array $rules, array $data = null): array 
    {
        $data = $data ?? $this->request->all();
        $errors = [];

        foreach ($rules as $field => $ruleSet) {
            $fieldRules = explode('|', $ruleSet);
            $value = $data[$field] ?? null;

            foreach ($fieldRules as $rule) {
                $result = $this->validateField($field, $value, $rule, $data);
                if ($result !== true) {
                    $errors[$field] = $result;
                    break; // Stop at first error for this field
                }
            }
        }

        return $errors;
    }

    /**
     * Validate individual field
     */
    private function validateField(string $field, $value, string $rule, array $data): mixed 
    {
        if (strpos($rule, ':') !== false) {
            [$ruleName, $parameter] = explode(':', $rule, 2);
        } else {
            $ruleName = $rule;
            $parameter = null;
        }

        switch ($ruleName) {
            case 'required':
                return empty($value) ? "$field is required" : true;
                
            case 'min':
                return strlen($value) < $parameter ? "$field must be at least $parameter characters" : true;
                
            case 'max':
                return strlen($value) > $parameter ? "$field must not exceed $parameter characters" : true;
                
            case 'email':
                return !filter_var($value, FILTER_VALIDATE_EMAIL) ? "$field must be a valid email" : true;
                
            case 'numeric':
                return !is_numeric($value) ? "$field must be a number" : true;
                
            case 'integer':
                return !filter_var($value, FILTER_VALIDATE_INT) ? "$field must be an integer" : true;
                
            case 'confirmed':
                $confirmField = $parameter ?? $field . '_confirmation';
                return $value !== ($data[$confirmField] ?? null) ? "$field confirmation does not match" : true;
                
            default:
                return true;
        }
    }

    /**
     * Log activity
     */
    protected function logActivity(string $action, string $details = ''): void 
    {
        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'user_id' => $this->getUserId(),
            'action' => $action,
            'details' => $details,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ];

        error_log("Activity: " . json_encode($logData));
    }
}
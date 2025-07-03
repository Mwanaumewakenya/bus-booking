<?php
/**
 * Authentication Middleware
 * 
 * Ensures user is authenticated before accessing protected routes
 */

class AuthMiddleware 
{
    public function handle(Request $request, Response $response): bool 
    {
        if (!isset($_SESSION['user_id'])) {
            // Store intended URL for redirect after login
            $_SESSION['intended_url'] = $request->path();
            
            if ($request->isMethod('POST')) {
                $response->json(['error' => 'Authentication required'], 401);
            } else {
                $response->redirect(url('admin'));
            }
            
            return false;
        }
        
        return true;
    }
}

/**
 * Admin Middleware
 * 
 * Ensures user has admin privileges
 */
class AdminMiddleware 
{
    public function handle(Request $request, Response $response): bool 
    {
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['intended_url'] = $request->path();
            $response->redirect(url('admin'));
            return false;
        }
        
        $user = $_SESSION['user_data'] ?? null;
        if (!$user || $user['user_type'] != User::TYPE_ADMIN) {
            if ($request->isMethod('POST')) {
                $response->json(['error' => 'Admin access required'], 403);
            } else {
                $response->redirect(url('admin'));
            }
            return false;
        }
        
        return true;
    }
}

/**
 * CSRF Middleware
 * 
 * Validates CSRF tokens on POST requests
 */
class CsrfMiddleware 
{
    public function handle(Request $request, Response $response): bool 
    {
        if ($request->isMethod('POST')) {
            $token = $request->input('csrf_token');
            
            if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
                if ($request->isMethod('POST')) {
                    $response->json(['error' => 'Invalid CSRF token'], 419);
                } else {
                    $response->redirect($request->path());
                }
                return false;
            }
        }
        
        return true;
    }
}
<?php
/**
 * User Model
 * 
 * Handles user-related database operations and authentication
 */

class User extends BaseModel 
{
    protected $table = 'users';
    protected $fillable = [
        'name', 'username', 'password', 'user_type', 'status'
    ];
    protected $hidden = ['password'];

    const TYPE_ADMIN = 1;
    const TYPE_FACULTY = 2;
    const TYPE_STUDENT = 3;

    const STATUS_INACTIVE = 0;
    const STATUS_ACTIVE = 1;

    /**
     * Authenticate user credentials
     */
    public function authenticate(string $username, string $password): ?array 
    {
        $user = $this->first(['username' => $username, 'status' => self::STATUS_ACTIVE]);
        
        if ($user && password_verify($password, $user['password'])) {
            // Remove password from returned data
            unset($user['password']);
            return $user;
        }
        
        return null;
    }

    /**
     * Create a new user with hashed password
     */
    public function createUser(array $data): int 
    {
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT, [
                'cost' => config('security.password_bcrypt_rounds', 12)
            ]);
        }
        
        return $this->create($data);
    }

    /**
     * Update user password
     */
    public function updatePassword(int $userId, string $newPassword): bool 
    {
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT, [
            'cost' => config('security.password_bcrypt_rounds', 12)
        ]);
        
        return $this->update($userId, ['password' => $hashedPassword]);
    }

    /**
     * Check if username exists
     */
    public function usernameExists(string $username, int $excludeId = null): bool 
    {
        $conditions = ['username' => $username];
        
        if ($excludeId) {
            $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE username = :username AND id != :exclude_id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':username', $username);
            $stmt->bindValue(':exclude_id', $excludeId, PDO::PARAM_INT);
            $stmt->execute();
            
            $result = $stmt->fetch();
            return (int) $result['count'] > 0;
        }
        
        return $this->exists($conditions);
    }

    /**
     * Get active users
     */
    public function getActiveUsers(): array 
    {
        return $this->where(['status' => self::STATUS_ACTIVE]);
    }

    /**
     * Get admin users
     */
    public function getAdminUsers(): array 
    {
        return $this->where([
            'user_type' => self::TYPE_ADMIN,
            'status' => self::STATUS_ACTIVE
        ]);
    }

    /**
     * Get user type label
     */
    public static function getUserTypeLabel(int $type): string 
    {
        switch ($type) {
            case self::TYPE_ADMIN:
                return 'Administrator';
            case self::TYPE_FACULTY:
                return 'Faculty';
            case self::TYPE_STUDENT:
                return 'Student';
            default:
                return 'Unknown';
        }
    }

    /**
     * Get status label
     */
    public static function getStatusLabel(int $status): string 
    {
        return $status === self::STATUS_ACTIVE ? 'Active' : 'Inactive';
    }

    /**
     * Validate user data
     */
    public function validateUserData(array $data, int $userId = null): array 
    {
        $errors = [];
        
        // Validate name
        if (empty($data['name'])) {
            $errors['name'] = 'Name is required';
        } elseif (strlen($data['name']) < 2) {
            $errors['name'] = 'Name must be at least 2 characters';
        }
        
        // Validate username
        if (empty($data['username'])) {
            $errors['username'] = 'Username is required';
        } elseif (strlen($data['username']) < 3) {
            $errors['username'] = 'Username must be at least 3 characters';
        } elseif ($this->usernameExists($data['username'], $userId)) {
            $errors['username'] = 'Username already exists';
        }
        
        // Validate password (only for new users or when password is provided)
        if ($userId === null || !empty($data['password'])) {
            if (empty($data['password'])) {
                $errors['password'] = 'Password is required';
            } elseif (strlen($data['password']) < 6) {
                $errors['password'] = 'Password must be at least 6 characters';
            }
        }
        
        // Validate user type
        if (!isset($data['user_type']) || !in_array($data['user_type'], [self::TYPE_ADMIN, self::TYPE_FACULTY, self::TYPE_STUDENT])) {
            $errors['user_type'] = 'Invalid user type';
        }
        
        return $errors;
    }
}
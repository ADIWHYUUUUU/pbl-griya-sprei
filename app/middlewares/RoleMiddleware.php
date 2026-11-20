class RoleMiddleware
{
    public static function handle(string $role)
    {
        if (($_SESSION['role'] ?? '') !== $role) {
            http_response_code(403);
            exit('Akses ditolak');
        }
    }
}
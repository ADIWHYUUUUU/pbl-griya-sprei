class AuthMiddleware
{
    public static function handle()
    {
        if (empty($_SESSION['id_user'])) {
            header('Location: /login');
            exit;
        }
    }
}
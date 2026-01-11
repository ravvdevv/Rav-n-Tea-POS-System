<?php
session_start();
require_once 'config/database.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            
            // Redirect based on role
            $redirect = match($user['role']) {
                'admin' => 'admin/',
                'cashier' => 'cashier/',
                'barista' => 'barista/',
                default => 'index.php'
            };
            
            header("Location: $redirect");
            exit();
        } else {
            $error = 'Invalid username or password';
        }
    }
}
?>
<!DOCTYPE html>
<html data-theme="cupcake">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Rav n tea</title>
    <!-- Tailwind CSS with DaisyUI -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@4.7.2/dist/full.min.css" rel="stylesheet" type="text/css" />
    <!-- Lucide Icons -->
    <script type="module">
        import { createIcons, User, LockKeyhole, LogIn } from 'https://cdn.jsdelivr.net/npm/lucide@0.562.0/+esm'
        
        // Initialize icons
        createIcons({
            icons: {
                User,
                LockKeyhole,
                LogIn
            }
        });
    </script>
    <script>
        tailwind.config = {
            daisyui: {
                themes: ["light", "dark", "cupcake"],
            },
        }
    </script>
</head>
<body class="min-h-screen bg-base-200 flex items-center justify-center p-4">
    <div class="card w-full max-w-md bg-base-100 shadow-xl">
        <div class="card-body">
            <!-- Logo with background -->
            <div class="flex justify-center mb-6">
                <div class="p-3 bg-base-200/50 rounded-xl shadow-inner">
                    <img src="logo.png" alt="Rav'n'Tea Logo" class="h-20 w-auto rounded-lg">
                </div>
            </div>
            <h1 class="text-2xl font-bold text-center mb-2">Rav'n'Tea POS</h1>
                
            <?php if ($error): ?>
                <div class="alert alert-error mb-4">
                    <i data-lucide="alert-circle" class="w-4 h-4"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php">
                <div class="form-control mb-4">
                    <label class="label" for="username">
                        <span class="label-text flex items-center gap-2">
                            <i data-lucide="user" class="w-4 h-4"></i>
                            Username
                        </span>
                    </label>
                    <div class="relative">
                        <input 
                            type="text" 
                            id="username" 
                            name="username" 
                            class="input input-bordered w-full pl-10" 
                            placeholder="Enter your username"
                            required
                            value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                        >
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                            <i data-lucide="user" class="w-5 h-5"></i>
                        </span>
                    </div>
                </div>

                <div class="form-control mb-6">
                    <label class="label" for="password">
                        <span class="label-text flex items-center gap-2">
                            <i data-lucide="lock-keyhole" class="w-4 h-4"></i>
                            Password
                        </span>
                    </label>
                    <div class="relative">
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            class="input input-bordered w-full pl-10" 
                            placeholder="Enter your password"
                            required
                        >
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                            <i data-lucide="lock-keyhole" class="w-5 h-5"></i>
                        </span>
                    </div>
                </div>

                <div class="form-control mt-6">
                    <button type="submit" class="btn btn-primary w-full gap-2">
                        <i data-lucide="log-in" class="w-5 h-5"></i>
                        Login
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
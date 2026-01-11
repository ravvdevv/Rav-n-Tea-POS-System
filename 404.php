<?php
// Set the HTTP response code to 404
http_response_code(404);
$pageTitle = 'Page Not Found';
?>
<!DOCTYPE html>
<html data-theme="cupcake" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - Rav'n'Tea POS</title>
    <!-- Tailwind CSS with DaisyUI -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/daisyui@3.9.4/dist/full.css" rel="stylesheet">
    <!-- Lucide Icons -->
    <script type="module" src="https://cdn.jsdelivr.net/npm/lucide@0.562.0/+esm"></script>
    <style>
        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f3f4f6;
            font-family: system-ui, -apple-system, sans-serif;
            padding: 1rem;
        }
        .error-card {
            background: white;
            border-radius: 1rem;
            padding: 2rem;
            text-align: center;
            max-width: 400px;
            width: 100%;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
    <div class="error-card">
        <!-- Logo & Error -->
        <div class="mb-6 relative">
            <div class="relative z-10">
                <img src="../logo.png" alt="Rav'n'Tea Logo" class="h-16 mx-auto mb-4">
                <h1 class="text-6xl font-bold text-error/20 mb-2">404</h1>
                <h2 class="text-2xl font-semibold text-error flex items-center justify-center gap-2">
                    <i data-lucide="search-x" class="w-6 h-6"></i>
                    Page Not Found
                </h2>
            </div>
            <!-- Floating icons -->
            <i data-lucide="coffee" class="absolute -top-2 -left-2 w-8 h-8 text-amber-600/30"></i>
            <i data-lucide="mug" class="absolute -bottom-2 -right-2 w-8 h-8 text-amber-600/30"></i>
        </div>
        
        <!-- Message -->
        <div class="bg-amber-50 border-l-4 border-amber-400 p-4 mb-6 rounded-r">
            <div class="flex items-center">
                <i data-lucide="alert-circle" class="w-5 h-5 text-amber-600 mr-2"></i>
                <p class="text-amber-700">
                    The page you're looking for doesn't exist or has been moved.
                </p>
            </div>
        </div>
        
        <!-- Actions -->
        <div class="flex flex-col space-y-3">
            <a href="/" class="btn btn-primary w-full group">
                <i data-lucide="home" class="w-5 h-5 mr-2 transition-transform group-hover:scale-110"></i>
                Go to Homepage
            </a>
            <button onclick="history.back()" class="btn btn-ghost w-full group">
                <i data-lucide="arrow-left" class="w-5 h-5 mr-2 transition-transform group-hover:-translate-x-1"></i>
                Go Back
            </button>
        </div>
        
        <!-- Footer -->
        <div class="mt-8 text-sm text-gray-500 flex items-center justify-center gap-2">
            <i data-lucide="copyright" class="w-4 h-4"></i>
            <?php echo date('Y'); ?> Rav'n'Tea POS
        </div>
    </div>

    <script>
        // Initialize Lucide icons
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    </script>
</body>
</html>
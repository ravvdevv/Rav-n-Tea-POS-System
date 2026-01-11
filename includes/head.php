<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Rav'n'Tea POS System</title>
<meta name="description" content="Rav'n'Tea POS System">

<!-- Favicon -->
<style>
    /* Rounded favicon */
    link[rel="icon"],
    link[rel="apple-touch-icon"],
    link[rel="shortcut icon"] {
        border-radius: 0.5rem;
    }
</style>
<link rel="icon" type="image/png" href="../logo.png">
<link rel="apple-touch-icon" href="../logo.png">
<link rel="shortcut icon" href="../logo.png" type="image/x-icon">

<!-- DaisyUI + Tailwind CSS -->
<link href="https://cdn.jsdelivr.net/npm/daisyui@3.9.4/dist/full.min.css" rel="stylesheet" type="text/css" />
<script src="https://cdn.tailwindcss.com"></script>

<!-- Lucide Icons -->
<script src="https://unpkg.com/lucide@latest"></script>

<!-- Custom Styles -->
<style>
    [data-theme='light'] {
        --color-primary: 56, 189, 248;  /* sky-400 */
        --color-secondary: 99, 102, 241; /* indigo-500 */
        --color-accent: 236, 72, 153;   /* pink-500 */
        --color-neutral: 100, 116, 139; /* slate-500 */
        --color-base-100: 255, 255, 255; /* white */
        --color-base-200: 241, 245, 249; /* slate-100 */
        --color-base-300: 226, 232, 240; /* slate-200 */
        --color-base-content: 15, 23, 42; /* slate-900 */
    }

    [data-theme='cupcake'] {
        --color-primary: 147, 197, 253;  /* blue-300 */
        --color-secondary: 165, 180, 252; /* indigo-300 */
        --color-accent: 249, 168, 212;   /* pink-200 */
        --color-neutral: 100, 116, 139; /* slate-500 */
        --color-base-100: 248, 250, 252; /* slate-50 */
        --color-base-200: 226, 232, 240; /* slate-200 */
        --color-base-300: 203, 213, 225; /* slate-300 */
        --color-base-content: 15, 23, 42; /* slate-900 */
    }

    /* Smooth transitions for theme changes */
    html {
        transition: background-color 0.3s ease, color 0.3s ease;
    }

    /* Custom scrollbar */
    ::-webkit-scrollbar {
        width: 8px;
        height: 8px;
    }

    ::-webkit-scrollbar-track {
        background: rgba(0, 0, 0, 0.05);
        border-radius: 4px;
    }

    ::-webkit-scrollbar-thumb {
        background: rgba(0, 0, 0, 0.2);
        border-radius: 4px;
    }

    ::-webkit-scrollbar-thumb:hover {
        background: rgba(0, 0, 0, 0.3);
    }

    /* Animation for page transitions */
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .animate-fade-in {
        animation: fadeIn 0.3s ease-out forwards;
    }

    /* Custom classes */
    .min-h-screen {
        min-height: 100vh;
    }

    .min-h-full {
        min-height: 100%;
    }
</style>

<!-- Page-specific styles -->
<?php // if (function_exists('page_styles')) { page_styles(); } ?>
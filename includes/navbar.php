<header class="bg-base-100/80 backdrop-blur-sm border-b border-base-300 sticky top-0 z-40">
    <div class="navbar px-4">
        <!-- Logo -->
        <div class="flex items-center">
            <a href="../index.php" class="flex items-center space-x-2">
                <img src="../logo.png" alt="Rav'n'Tea Logo" class="h-8 w-auto rounded-lg shadow-sm">
            </a>
        </div>
        <!-- Mobile menu button -->
        <button id="sidebar-toggle" class="btn btn-ghost btn-square md:hidden">
            <i data-lucide="menu" class="w-5 h-5"></i>
        </button>
        
        <!-- Breadcrumb -->
        <div class="text-sm breadcrumbs px-4 hidden md:block">
            <ul>
                <li><a href="../index.php" class="text-base-content/70 hover:text-primary">Home</a></li>
                <li class="text-base-content/50">
                    <?php 
                    $currentPage = basename($_SERVER['PHP_SELF'], '.php');
                    echo ucfirst($currentPage === 'index' ? 'Dashboard' : $currentPage);
                    ?>
                </li>
            </ul>
        </div>
        
        <!-- Right side navigation -->
        <div class="flex-1 justify-end">
            <div class="flex items-center space-x-2">
                <!-- Theme Toggle -->
                <button id="theme-toggle" class="btn btn-ghost btn-square hidden md:inline-flex">
                    <i data-lucide="moon" class="w-5 h-5"></i>
                </button>
                
                <!-- User Menu -->
                <div class="dropdown dropdown-end">
                    <button class="btn btn-ghost">
                        <i data-lucide="user" class="w-5 h-5"></i>
                    </button>
                    <ul tabindex="0" class="menu menu-sm dropdown-content mt-3 z-[1] p-2 shadow bg-base-100 rounded-box w-40 border border-base-300">
                        <li>
                            <a href="../logout.php" class="text-error hover:bg-error/10 flex items-center">
                                <i data-lucide="log-out" class="w-4 h-4 mr-2"></i>
                                Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</header>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle sidebar on mobile
    const sidebarToggle = document.getElementById('sidebar-toggle');
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            if (sidebar && overlay) {
                sidebar.classList.toggle('-translate-x-full');
                overlay.classList.toggle('hidden');
                document.body.classList.toggle('overflow-hidden');
            }
        });
    }
    
    // Close sidebar when clicking on a link (for mobile)
    document.querySelectorAll('#sidebar a').forEach(link => {
        link.addEventListener('click', () => {
            if (window.innerWidth < 768) {
                const sidebar = document.getElementById('sidebar');
                const overlay = document.getElementById('sidebar-overlay');
                if (sidebar && overlay) {
                    sidebar.classList.add('-translate-x-full');
                    overlay.classList.add('hidden');
                    document.body.classList.remove('overflow-hidden');
                }
            }
        });
    });
    
    // Handle window resize
    function handleResize() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebar-overlay');
        if (window.innerWidth >= 768 && sidebar && overlay) {
            sidebar.classList.remove('-translate-x-full');
            overlay.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }
    }
    
    window.addEventListener('resize', handleResize);
});
</script>

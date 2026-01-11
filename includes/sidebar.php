<!-- Sidebar Toggle Button (Mobile) -->
<button id="sidebar-toggle" class="fixed bottom-4 right-4 z-50 p-3 rounded-full bg-primary text-white shadow-lg md:hidden">
    <i data-lucide="menu"></i>
</button>

<!-- Sidebar Overlay -->
<div id="sidebar-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden"></div>

<!-- Sidebar -->
<aside id="sidebar" class="fixed md:static left-0 top-0 h-full w-64 bg-base-200/95 backdrop-blur-sm border-r border-base-300 transform -translate-x-full md:translate-x-0 transition-all duration-300 ease-in-out z-50 md:z-0 overflow-y-auto">
    <!-- Branding -->
    <div class="p-4 border-b border-base-300">
        <div class="flex items-center space-x-3">
            <i data-lucide="coffee" class="w-6 h-6 text-primary"></i>
            <span class="text-xl font-bold bg-gradient-to-r from-primary to-secondary bg-clip-text text-transparent">Cafe</span>
        </div>
    </div>
    
    <!-- Navigation -->
    <div class="p-4 space-y-1">
        <p class="text-xs uppercase font-semibold text-base-content/60 px-2 mb-2">Main Menu</p>
        
        <a href="dashboard.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg transition-colors duration-200 <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'bg-primary/10 text-primary' : 'hover:bg-base-300/50'; ?>">
            <div class="p-1.5 rounded-lg bg-primary/10 text-primary">
                <i data-lucide="layout-dashboard" class="w-5 h-5"></i>
            </div>
            <span class="font-medium">Dashboard</span>
        </a>
        
        <a href="products.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg transition-colors duration-200 <?php echo basename($_SERVER['PHP_SELF']) == 'products.php' ? 'bg-primary/10 text-primary' : 'hover:bg-base-300/50'; ?>">
            <div class="p-1.5 rounded-lg bg-amber-100 text-amber-600">
                <i data-lucide="coffee" class="w-5 h-5"></i>
            </div>
            <span class="font-medium">Products</span>
        </a>
        
        <a href="inventory.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg transition-colors duration-200 <?php echo basename($_SERVER['PHP_SELF']) == 'inventory.php' ? 'bg-primary/10 text-primary' : 'hover:bg-base-300/50'; ?>">
            <div class="p-1.5 rounded-lg bg-emerald-100 text-emerald-600">
                <i data-lucide="package" class="w-5 h-5"></i>
            </div>
            <span class="font-medium">Inventory</span>
        </a>
        
        <a href="orders.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg transition-colors duration-200 <?php echo basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'bg-primary/10 text-primary' : 'hover:bg-base-300/50'; ?>">
            <div class="p-1.5 rounded-lg bg-amber-100 text-amber-600">
                <i data-lucide="shopping-cart" class="w-5 h-5"></i>
            </div>
            <span class="font-medium">Orders</span>
        </a>
        
        <a href="users.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg transition-colors duration-200 <?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'bg-primary/10 text-primary' : 'hover:bg-base-300/50'; ?>">
            <div class="p-1.5 rounded-lg bg-indigo-100 text-indigo-600">
                <i data-lucide="users" class="w-5 h-5"></i>
            </div>
            <span class="font-medium">Users</span>
        </a>
    </div>
    
    <div class="p-4 border-t border-base-300 mt-auto">
        <p class="text-xs uppercase font-semibold text-base-content/60 px-2 mb-2">Preferences</p>
        
        <button id="theme-toggle" class="w-full flex items-center space-x-3 px-3 py-2.5 rounded-lg transition-colors duration-200 hover:bg-base-300/50 text-left">
            <div class="p-1.5 rounded-lg bg-base-300">
                <i data-lucide="moon" class="w-5 h-5"></i>
            </div>
            <span class="font-medium">Toggle Theme</span>
        </button>
        
        <a href="../logout.php" class="flex items-center space-x-3 px-3 py-2.5 rounded-lg transition-colors duration-200 hover:bg-error/10 text-error">
            <div class="p-1.5 rounded-lg bg-error/10">
                <i data-lucide="log-out" class="w-5 h-5"></i>
            </div>
            <span class="font-medium">Logout</span>
        </a>
    </div>
</aside>

<!-- Mobile menu button -->
<button class="md:hidden fixed bottom-4 right-4 btn btn-circle btn-primary shadow-lg z-50" id="mobile-menu-button">
    <i data-lucide="menu"></i>
</button>

<!-- Mobile sidebar (hidden by default) -->
<div class="md:hidden fixed inset-0 bg-black bg-opacity-50 z-40 hidden" id="mobile-menu-overlay">
    <div class="absolute right-0 top-0 bottom-0 w-64 bg-base-200 shadow-2xl transform transition-transform duration-300 ease-in-out" id="mobile-sidebar">
        <div class="p-4">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold">Menu</h3>
                <button id="close-mobile-menu" class="btn btn-sm btn-circle">
                    <i data-lucide="x"></i>
                </button>
            </div>
            <div class="flex items-center space-x-3 p-2 mb-4">
                <div class="avatar">
                    <div class="w-10 rounded-full bg-primary text-primary-content flex items-center justify-center">
                        <span class="text-xl font-bold"><?php echo strtoupper(substr($username, 0, 1)); ?></span>
                    </div>
                </div>
                <div>
                    <p class="font-semibold"><?php echo htmlspecialchars($username); ?></p>
                    <p class="text-sm opacity-70"><?php echo ucfirst($userRole); ?></p>
                </div>
            </div>
            <ul class="menu p-0">
                <li><a href="dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">Dashboard</a></li>
                <li><a href="products.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : ''; ?>">Products</a></li>
                <li><a href="inventory.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'inventory.php' ? 'active' : ''; ?>">Inventory</a></li>
                <li><a href="users.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : ''; ?>">Users</a></li>
                <li class="divider my-0"></li>
                <li><a href="#" id="mobile-theme-toggle">Toggle Theme</a></li>
                <li><a href="../logout.php">Logout</a></li>
            </ul>
        </div>
    </div>
</div>

<script>
// Sidebar toggle functionality
const sidebar = document.getElementById('sidebar');
const sidebarToggle = document.getElementById('sidebar-toggle');
const closeSidebar = document.getElementById('close-sidebar');
const sidebarOverlay = document.getElementById('sidebar-overlay');

function openSidebar() {
    sidebar.classList.remove('-translate-x-full');
    sidebarOverlay.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
}

function closeSidebarFunc() {
    sidebar.classList.add('-translate-x-full');
    sidebarOverlay.classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
}

// Toggle sidebar
if (sidebarToggle) {
    sidebarToggle.addEventListener('click', openSidebar);
}

// Close sidebar button
if (closeSidebar) {
    closeSidebar.addEventListener('click', closeSidebarFunc);
}

// Close sidebar when clicking outside
if (sidebarOverlay) {
    sidebarOverlay.addEventListener('click', closeSidebarFunc);
}

// Close sidebar when clicking on a link (for mobile)
document.querySelectorAll('#sidebar a').forEach(link => {
    link.addEventListener('click', () => {
        if (window.innerWidth < 768) { // Only for mobile
            closeSidebarFunc();
        }
    });
});

// Handle window resize
function handleResize() {
    if (window.innerWidth >= 768) {
        sidebar.classList.remove('-translate-x-full');
        sidebarOverlay.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    } else if (!sidebar.classList.contains('-translate-x-full')) {
        closeSidebarFunc();
    }
}

// Add event listener for window resize
window.addEventListener('resize', handleResize);

// Mobile menu toggle
const mobileMenuButton = document.getElementById('mobile-menu-button');
const mobileMenuOverlay = document.getElementById('mobile-menu-overlay');
const mobileSidebar = document.getElementById('mobile-sidebar');
const closeMobileMenu = document.getElementById('close-mobile-menu');

function openMobileMenu() {
    mobileMenuOverlay.classList.remove('hidden');
    setTimeout(() => {
        mobileSidebar.classList.remove('translate-x-full');
    }, 10);
    document.body.style.overflow = 'hidden';
}

function closeMobileMenuFunc() {
    mobileSidebar.classList.add('translate-x-full');
    setTimeout(() => {
        mobileMenuOverlay.classList.add('hidden');
    }, 300);
    document.body.style.overflow = '';
}

mobileMenuButton.addEventListener('click', openMobileMenu);
closeMobileMenu.addEventListener('click', closeMobileMenuFunc);

// Close menu when clicking outside
mobileMenuOverlay.addEventListener('click', (e) => {
    if (e.target === mobileMenuOverlay) {
        closeMobileMenuFunc();
    }
});

// Theme toggle functionality
const themeToggle = document.getElementById('theme-toggle') || document.getElementById('mobile-theme-toggle');
const mobileThemeToggle = document.getElementById('mobile-theme-toggle');

function toggleTheme() {
    const html = document.documentElement;
    const currentTheme = html.getAttribute('data-theme');
    const newTheme = currentTheme === 'light' ? 'cupcake' : 'light';
    
    html.setAttribute('data-theme', newTheme);
    localStorage.setItem('theme', newTheme);
    
    // Update icons
    const moonIcons = document.querySelectorAll('[data-lucide="moon"]');
    const sunIcons = document.querySelectorAll('[data-lucide="sun"]');
    
    if (newTheme === 'light') {
        moonIcons.forEach(icon => {
            icon.setAttribute('data-lucide', 'sun');
            lucide.createIcons();
        });
    } else {
        sunIcons.forEach(icon => {
            icon.setAttribute('data-lucide', 'moon');
            lucide.createIcons();
        });
    }
}

// Initialize theme from localStorage
function initTheme() {
    const savedTheme = localStorage.getItem('theme') || 'cupcake';
    document.documentElement.setAttribute('data-theme', savedTheme);
    
    // Update icons based on theme
    const moonIcons = document.querySelectorAll('[data-lucide="moon"]');
    const sunIcons = document.querySelectorAll('[data-lucide="sun"]');
    
    if (savedTheme === 'light') {
        moonIcons.forEach(icon => {
            icon.setAttribute('data-lucide', 'sun');
        });
    }
    
    // Reinitialize lucide icons
    lucide.createIcons();
}

// Add event listeners
if (themeToggle) themeToggle.addEventListener('click', (e) => {
    e.preventDefault();
    toggleTheme();
});

if (mobileThemeToggle) {
    mobileThemeToggle.addEventListener('click', (e) => {
        e.preventDefault();
        toggleTheme();
        closeMobileMenuFunc();
    });
}

// Initialize theme when page loads
document.addEventListener('DOMContentLoaded', initTheme);
</script>

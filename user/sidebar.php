<div class="sidebar">
    <div class="logo-section">
        <div class="brand-logo-container">
            <img src="logo.png" alt="CarbonWise Logo">
        </div>
        <span class="logo-text">CARBONWISE</span>
    </div>
    <div class="menu-items">
        <?php 
            // Automatically detects the current page filename to apply the active class
            $currentPage = basename($_SERVER['PHP_SELF']); 
        ?>
        <a href="dashboard.php" class="menu-item <?= $currentPage == 'dashboard.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-border-all"></i> Dashboard
        </a>
        <a href="activity_input.php" class="menu-item <?= $currentPage == 'activity_input.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-pen-to-square"></i> Activity Input
        </a>
        <a href="reports.php" class="menu-item <?= $currentPage == 'reports.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-chart-simple"></i> Reports
        </a>
        <a href="mitigation_strategies.php" class="menu-item <?= $currentPage == 'mitigation_strategies.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-lightbulb"></i> Mitigation Strategies
        </a>
        <a href="profile.php" class="menu-item <?= $currentPage == 'profile.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-circle-user"></i> View Profile
        </a>
        <a href="settings.php" class="menu-item <?= $currentPage == 'settings.php' ? 'active' : '' ?>">
            <i class="fa-solid fa-gear"></i> Settings
        </a>
        
        <div class="sidebar-footer">
            <div class="sidebar-divider"></div>
            <button class="theme-toggle-item" id="themeToggle" title="Toggle Light/Dark Mode">
                <i class="fa-solid fa-moon" id="themeIcon"></i> <span id="themeText">Dark Mode</span>
            </button>
            <a href="logout.php" class="menu-item">
                <i class="fa-solid fa-right-from-bracket"></i> Log Out
            </a>
        </div>
    </div>
</div>

<script>
    // This script runs on EVERY page that includes this sidebar
    const themeToggleBtn = document.getElementById('themeToggle');
    const themeIcon = document.getElementById('themeIcon');
    const themeText = document.getElementById('themeText');
    
    // Read preference globally from localStorage
    const currentTheme = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', currentTheme);
    updateToggleElement(currentTheme);

    themeToggleBtn.addEventListener('click', () => {
        let theme = document.documentElement.getAttribute('data-theme');
        let newTheme = theme === 'dark' ? 'light' : 'dark';
        
        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        updateToggleElement(newTheme);
    });

    function updateToggleElement(theme) {
        if (theme === 'dark') {
            themeIcon.className = 'fa-solid fa-sun';
            themeText.textContent = 'Light Mode';
        } else {
            themeIcon.className = 'fa-solid fa-moon';
            themeText.textContent = 'Dark Mode';
        }
    }
</script>
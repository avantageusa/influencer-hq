<?php
/**
 * Template Name: Influencer HQ Figma
 * Description: A custom template for displaying the influencer HQ.
 * This template is used to render the influencer HQ content and layout.
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package Avantage_Baccarat
 */
get_header();
?>

    <main id="primary" class="site-main">
        
        <!-- Fixed Left Sidebar Menu -->
        <div class="hq-sidebar-tabs">
            <ul class="nav nav-pills flex-column">
                <li class="nav-item">
                    <a class="nav-link" href="#">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="#">Challenges</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">Genius Network</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">Followers</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">Rankings</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">Profile & Settings</a>
                </li>
            </ul>
        </div>
        
        <section class="hero-section">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-12">
                        
                        <!-- Top Search Bar -->
                        <div class="top-search-bar">
                            <span class="search-icon">🔍</span>
                            <input type="text" placeholder="Search">
                        </div>
                        
                        <!-- Page Header -->
                        <header class="page-header">
                            <div class="header-row">
                                <button class="hamburger-menu-btn">
                                    <span></span>
                                    <span></span>
                                    <span></span>
                                </button>
                                <div class="hq-logo">
                                    <img src="<?php echo get_template_directory_uri(); ?>/images/logo-hq.png" alt="influencerHQ" class="logo-img">
                                </div>
                                <div class="header-right">
                                    <div class="language-selector">
                                        EN <span class="dropdown-arrow">▼</span>
                                    </div>
                                    <div class="profile-pic">
                                        <img src="https://i.pravatar.cc/300" alt="Profile">
                                    </div>
                                </div>
                            </div>
                        </header>

                        <!-- Main Content -->
                        <div class="challenges-content">
                            <h1 class="page-title">Challenges</h1>

                            <!-- Tab Navigation -->
                            <div class="tab-nav-container">
                                <div class="tab-nav">
                                    <button class="tab-btn">Today</button>
                                    <button class="tab-btn">Week</button>
                                    <button class="tab-btn active">Month</button>
                                    <button class="tab-btn">Year</button>
                                    <button class="tab-btn">Total</button>
                                </div>
                            </div>
                            
                            <!-- Search Challenges -->
                            <div class="search-box-challenges">
                                <span class="search-icon">🔍</span>
                                <input type="text" placeholder="Search Challenges">
                            </div>

                            <!-- Challenges Joined Today -->
                            <section class="challenge-section mb-4">
                                <div class="section-header">
                                    <h2 class="section-title">CHALLENGES JOINED TODAY</h2>
                                    <a href="#" class="view-all">View All →</a>
                                </div>
                                <div class="challenge-card">
                                                    <div class="user-item">
                                                        <div class="user-avatar">
                                                            <img src="https://via.placeholder.com/40" alt="Tad">
                                                        </div>
                                                        <span class="user-name">Tad</span>
                                                    </div>
                                                    <div class="user-item">
                                                        <div class="user-avatar">
                                                            <img src="https://via.placeholder.com/40" alt="Fang Fang">
                                                        </div>
                                                        <span class="user-name">Fang Fang</span>
                                                    </div>
                                                    <div class="user-item">
                                                        <div class="user-avatar">
                                                            <img src="https://via.placeholder.com/40" alt="Nicholas">
                                                        </div>
                                                        <span class="user-name">Nicholas</span>
                                                        <button class="btn-accept">Accept Invitation</button>
                                                    </div>
                                                </div>
                            </section>

                            <!-- Private Challenges -->
                            <section class="challenge-section mb-4">
                                <h2 class="section-title">PRIVATE CHALLENGES</h2>
                                <div class="challenge-card">
                                                    <button class="btn-challenge">
                                                        Challenge My Followers
                                                        <span class="arrow">→</span>
                                                    </button>
                                                    <button class="btn-challenge">
                                                        Challenge Another Influencer
                                                        <span class="arrow">→</span>
                                                    </button>
                                                </div>
                            </section>

                            <!-- World Weekend Challenge -->
                            <section class="challenge-section mb-4">
                                <h2 class="section-title">JOIN THE WORLD WEEKEND CHALLENGE</h2>
                                <div class="challenge-card">
                                                    <div class="status-badge">
                                                        Status: <span class="status-opted">OPTED IN</span>
                                                    </div>
                                                    <div class="challenge-time">
                                                        <span class="time-text">Today - 2:15 PM</span>
                                                        <button class="btn-join">Join Challenge</button>
                                                    </div>
                                                    <div class="challenge-time">
                                                        <span class="time-text">Tomorrow - 6:15 PM</span>
                                                        <button class="btn-join">Join Challenge</button>
                                                    </div>
                                                    <button class="btn-view-upcoming">
                                                        View Upcoming Challenges
                                                        <span class="arrow">→</span>
                                                    </button>
                                                </div>
                            </section>
                        </div>
                        
                    </div>
                </div>
            </div>
        </section>
    </main><!-- #main -->
<style>
    /* Modern CSS Variables */
    :root {
        --accent-red: #d9222a;
        --accent-yellow: #d9222a;
        --accent-gold: #d9222a;
        --light-gray: #e0e0e0;
        --dark-bg: #0a0a0a;
        --card-bg: rgba(26, 26, 26, 0.95);
        --border-color: rgba(217, 34, 42, 0.2);
    }

    /* Page Layout */
    body {
        background: linear-gradient(135deg, var(--dark-bg) 0%, #1a1a1a 100%);
        min-height: 100vh;
    }
    
    .page {
        margin: 0;
        min-height: 100vh;
    }

    /* Hero Section */
    .hero-section {
        background: var(--dark-bg);
        min-height: 100vh;
        padding: 2rem 0;
    }

    .hero-content {
        padding: 1rem 0;
    }
    
    /* Logo Styling */
    .logo-hq {
        height: auto;
    }
    
    /* Modern Text Gradient */
    .text-gradient {
        background: linear-gradient(135deg, var(--accent-gold) 0%, var(--accent-yellow) 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    /* Typography */
    body, h1, h2, h3, h4, h5, h6, p, label, input, select, textarea {
        font-family: 'Source Sans Pro', -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif !important;
    }
    
    h1, h2, h3 {
        color: #fff;
    }
    
    p, label {
        color: var(--light-gray);
    }
    
    /* Color Utilities */
    .text-yellow {
        color: var(--accent-yellow) !important;
    }
    
    .text-light-gray {
        color: var(--light-gray) !important;
    }
    
    /* Form Elements */
    .form-select {
        --bs-form-select-bg-img: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23fff' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e");
    }
    
    .form-control, .form-select {
        background: var(--card-bg);
        border: 2px solid var(--accent-gold);
        color: #fff;
        border-radius: 10px;
        transition: all 0.3s ease;
    }
    
    .form-control:focus, .form-select:focus {
        background: var(--card-bg);
        border-color: var(--accent-yellow);
        color: #fff;
        box-shadow: 0 0 0 0.25rem rgba(255, 149, 0, 0.25);
    }
    
    .form-control::placeholder,
    .form-select::placeholder,
    textarea::placeholder {
        color: #fff !important;
        opacity: 0.7;
    }
    /* Content Cards */
    .challenge-builder {
        background: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: 15px;
        padding: 2rem;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
        transition: all 0.3s ease;
    }
    
    .challenge-builder:hover {
        border-color: rgba(255, 149, 0, 0.4);
        box-shadow: 0 15px 50px rgba(255, 149, 0, 0.2);
    }
    /* Mobile Responsive */
    @media (max-width: 767px) {
        .page {
            min-height: 100vh;
            overflow: auto;
        }

        .hero-content {
            padding: 0.5rem 0;
        }
        
        .hero-section {
            overflow: auto;
            padding: 1rem 0;
        }
        
        h1 {
            font-size: 2rem;
        }
        
        .challenge-builder {
            padding: 1.5rem;
        }
    }
    
    /* Buttons */
    .btn-warning {
        background: linear-gradient(135deg, var(--accent-gold) 0%, var(--accent-yellow) 100%);
        border: none;
        color: #000;
        font-weight: 600;
        padding: 0.75rem 2rem;
        border-radius: 50px;
        transition: all 0.3s ease;
        box-shadow: 0 5px 20px rgba(255, 193, 7, 0.4);
    }
    
    .btn-warning:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 30px rgba(255, 193, 7, 0.6);
        background: linear-gradient(135deg, var(--accent-yellow) 0%, var(--accent-gold) 100%);
    }
    
    .btn-outline-warning {
        border: 2px solid var(--accent-gold);
        color: var(--accent-gold);
        background: transparent;
        border-radius: 50px;
        padding: 0.5rem 1.5rem;
        transition: all 0.3s ease;
    }
    
    .btn-outline-warning:hover {
        background: var(--accent-gold);
        color: #000;
        transform: translateY(-2px);
    }

    /* Alert Styling */
    .alert-warning {
        background: rgba(255, 149, 0, 0.15);
        border: 1px solid rgba(255, 149, 0, 0.5);
        color: var(--light-gray);
        border-radius: 10px;
        padding: 1rem 1.25rem;
    }
    
    /* Section Dividers */
    .section-divider {
        border-color: rgba(255, 149, 0, 0.3);
        margin: 2rem 0;
    }
    
    /* Callout Boxes */
    .callout-box {
        background: var(--card-bg);
        border-left: 4px solid var(--accent-yellow);
        border-radius: 10px;
        padding: 1.25rem;
        margin: 1.5rem 0;
    }
    
    .pro-tip {
        background: var(--card-bg);
        border: 1px solid var(--border-color);
        border-radius: 10px;
        padding: 1.25rem;
        margin: 1.5rem 0;
        font-style: italic;
    }
    
    /* Fixed Left Sidebar Tabs */
    .hq-sidebar-tabs {
        position: fixed;
        left: 0;
        top: 80px;
        width: 220px;
        height: calc(100vh - 80px);
        background: var(--card-bg);
        border-right: 2px solid var(--border-color);
        padding: 2rem 0;
        z-index: 100;
        overflow-y: auto;
    }
    
    .hq-sidebar-tabs .nav-pills {
        padding: 0 1rem;
    }
    
    .hq-sidebar-tabs .nav-item {
        margin-bottom: 0.5rem;
    }
    
    .hq-sidebar-tabs .nav-link {
        color: var(--light-gray);
        font-size: 1rem;
        font-weight: 600;
        padding: 12px 16px;
        border: none;
        background: transparent;
        transition: all 0.3s ease;
        border-radius: 8px;
        text-align: left;
        width: 100%;
        border-left: 3px solid transparent;
    }
    
    .hq-sidebar-tabs .nav-link:hover {
        color: #fff;
        background: rgba(217, 34, 42, 0.15);
        border-left-color: rgba(217, 34, 42, 0.5);
    }
    
    .hq-sidebar-tabs .nav-link.active {
        color: var(--accent-yellow);
        background: rgba(217, 34, 42, 0.2);
        border-left-color: var(--accent-yellow);
        font-weight: 700;
    }
    
    /* Adjust main content for sidebar */
    .hero-section {
        margin-left: 220px;
    }
    
    /* Tab content styling */
    .tab-content {
        padding-top: 0;
    }
    
    /* Mobile responsive */
    @media (max-width: 991px) {
        .hamburger-menu-btn {
            display: flex;
        }
        
        .hq-sidebar-tabs {
            transform: translateX(-100%);
            transition: transform 0.3s ease;
            z-index: 150;
        }
        
        .hq-sidebar-tabs.show {
            transform: translateX(0);
        }
        
        .hero-section {
            margin-left: 0;
        }
    }

    /* Page Header */
    .page-header {
        padding: 15px 0;
    }
    
    /* Top Search Bar */
    .top-search-bar {
        background: #fff;
        border-radius: 25px;
        padding: 10px 20px;
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 20px;
    }
    
    .top-search-bar input {
        background: none;
        border: none;
        color: #000;
        font-size: 16px;
        outline: none;
        width: 100%;
    }
    
    .top-search-bar input::placeholder {
        color: #666;
    }
    
    .top-search-bar .search-icon {
        font-size: 18px;
        color: #000;
    }
    
    /* Header Row */
    .header-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
    }
    
    .hamburger-menu-btn {
        background: none;
        border: none;
        cursor: pointer;
        padding: 5px;
        display: flex;
        flex-direction: column;
        gap: 5px;
    }
    
    .hamburger-menu-btn span {
        width: 28px;
        height: 3px;
        background: #fff;
        display: block;
        border-radius: 2px;
        transition: all 0.3s ease;
    }
    
    .hamburger-menu-btn.active span:nth-child(1) {
        transform: translateY(8px) rotate(45deg);
    }
    
    .hamburger-menu-btn.active span:nth-child(2) {
        opacity: 0;
    }
    
    .hamburger-menu-btn.active span:nth-child(3) {
        transform: translateY(-8px) rotate(-45deg);
    }
    
    .header-right {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .hq-logo {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .logo-img {
        height: 40px;
        width: auto;
    }

    .language-selector {
        color: #fff;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
    }

    .dropdown-arrow {
        font-size: 12px;
    }

    .profile-pic {
        width: 45px;
        height: 45px;
        border-radius: 50%;
        overflow: hidden;
        border: 2px solid var(--accent-red);
    }

    .profile-pic img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    /* Main Content */
    .challenges-content {
        padding: 0;
    }

    .page-title {
        color: #ff0000;
        font-size: 28px;
        font-weight: 700;
        margin: 0 0 20px 0;
    }

    /* Tab Navigation */
    .tab-nav-container {
        background: #1a1a1a;
        border-radius: 30px;
        padding: 8px 15px;
        margin-bottom: 20px;
        display: inline-flex;
    }
    
    .tab-nav {
        display: flex;
        gap: 25px;
    }

    .tab-btn {
        background: none;
        border: none;
        color: #888;
        font-size: 16px;
        font-weight: 500;
        cursor: pointer;
        padding: 8px 0;
        transition: color 0.3s;
        position: relative;
    }

    .tab-btn.active {
        color: #ff0000;
        font-weight: 600;
    }

    .tab-btn:hover {
        color: #fff;
    }
    
    /* Search Challenges */
    .search-box-challenges {
        background: #1a1a1a;
        border-radius: 12px;
        padding: 12px 20px;
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 30px;
    }
    
    .search-box-challenges input {
        background: none;
        border: none;
        color: #666;
        font-size: 16px;
        outline: none;
        width: 100%;
    }
    
    .search-box-challenges input::placeholder {
        color: #666;
    }
    
    .search-box-challenges .search-icon {
        font-size: 18px;
        color: #666;
    }

    /* Sections */
    .challenge-section {
        margin-bottom: 30px;
    }

    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
    }

    .section-title {
        color: #ff0000;
        font-size: 13px;
        font-weight: 700;
        letter-spacing: 1px;
        margin: 0;
    }

    .view-all {
        color: #888;
        font-size: 14px;
        text-decoration: none;
        transition: color 0.3s;
    }

    .view-all:hover {
        color: #fff;
    }

    /* Challenge Card */
    .challenge-card {
        background: #1a1a1a;
        border-radius: 12px;
        padding: 20px;
        border: 1px solid #2a2a2a;
    }

    /* User Item */
    .user-item {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 15px 0;
        border-bottom: 1px solid #2a2a2a;
    }

    .user-item:last-child {
        border-bottom: none;
    }

    .user-avatar {
        width: 45px;
        height: 45px;
        border-radius: 50%;
        overflow: hidden;
    }

    .user-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .user-name {
        color: #fff;
        font-size: 16px;
        flex: 1;
    }

    .btn-accept {
        background: none;
        border: 1px solid #00ff00;
        color: #00ff00;
        padding: 8px 20px;
        border-radius: 6px;
        font-size: 14px;
        cursor: pointer;
        transition: all 0.3s;
    }

    .btn-accept:hover {
        background: #00ff00;
        color: #000;
    }

    /* Challenge Buttons */
    .btn-challenge {
        width: 100%;
        background: none;
        border: 1px solid #ff0000;
        color: #fff;
        padding: 18px;
        border-radius: 8px;
        font-size: 16px;
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 12px;
        transition: all 0.3s;
    }

    .btn-challenge:last-of-type {
        margin-bottom: 0;
    }

    .btn-challenge:hover {
        background: rgba(255, 0, 0, 0.1);
        border-color: #ff3333;
    }

    .arrow {
        color: #ff0000;
        font-size: 18px;
    }

    /* Status Badge */
    .status-badge {
        color: #888;
        font-size: 14px;
        margin-bottom: 20px;
    }

    .status-opted {
        color: #00ff00;
        font-weight: 700;
    }

    /* Challenge Time */
    .challenge-time {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
    }

    .time-text {
        color: #888;
        font-size: 16px;
    }

    .btn-join {
        background: none;
        border: 1px solid #ff0000;
        color: #fff;
        padding: 10px 24px;
        border-radius: 6px;
        font-size: 14px;
        cursor: pointer;
        transition: all 0.3s;
    }

    .btn-join:hover {
        background: #ff0000;
    }

    .btn-view-upcoming {
        width: 100%;
        background: none;
        border: 1px solid #ff0000;
        color: #fff;
        padding: 18px;
        border-radius: 8px;
        font-size: 16px;
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 20px;
        transition: all 0.3s;
    }

    .btn-view-upcoming:hover {
        background: rgba(255, 0, 0, 0.1);
        border-color: #ff3333;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .page-title {
            font-size: 24px;
        }

        .search-box-top {
            min-width: auto;
            width: 100%;
        }

        .tab-nav {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .challenge-card {
            padding: 15px;
        }
    }

</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Mobile menu toggle
    const menuToggle = document.querySelector('.hamburger-menu-btn');
    const sidebar = document.querySelector('.hq-sidebar-tabs');
    const navLinks = document.querySelectorAll('.hq-sidebar-tabs .nav-link');
    
    if (menuToggle && sidebar) {
        menuToggle.addEventListener('click', function() {
            this.classList.toggle('active');
            sidebar.classList.toggle('show');
        });
        
        // Close menu when clicking a nav link on mobile
        navLinks.forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 991) {
                    menuToggle.classList.remove('active');
                    sidebar.classList.remove('show');
                }
            });
        });
        
        // Close menu when clicking outside on mobile
        document.addEventListener('click', function(event) {
            if (window.innerWidth <= 991) {
                if (!sidebar.contains(event.target) && !menuToggle.contains(event.target)) {
                    menuToggle.classList.remove('active');
                    sidebar.classList.remove('show');
                }
            }
        });
    }
});
</script>

<?php
get_footer();

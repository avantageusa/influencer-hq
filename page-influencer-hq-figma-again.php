<?php
/**
 * Template Name: Influencer HQ Figma again
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
        
        <!-- Background Image -->
        <div class="page-background" style="background-image: url('<?php echo get_template_directory_uri(); ?>/images/bgnd-phone-portal.jpg');"></div>
        
        <div class="mobile-container">
            
            <!-- Top Search Bar -->
            <div class="top-search-wrapper">
                <div class="search-pill">
                    <span class="search-icon">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    </span>
                    <input type="text" placeholder="">
                </div>
            </div>

            <!-- Header: Menu, Logo, Globe -->
            <header class="mobile-header">
                <button class="hamburger-btn">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>

                <!-- Menu Overlay -->
                <div class="menu-overlay" id="menuOverlay"></div>

                <!-- Mobile Menu Modal -->
                <div class="mobile-menu-modal" id="mobileMenuModal">
                    <ul class="mobile-menu-list">
                        <li><a href="#"><span class="menu-icon">🏠</span> Dashboard</a></li>
                        <li><a href="#"><span class="menu-icon">📊</span> Performance</a></li>
                        <li><a href="#"><span class="menu-icon">🏆</span> Leaderboards</a></li>
                        <li><a href="#"><span class="menu-icon">🚩</span> Challenges</a></li>
                        <li><a href="#"><span class="menu-icon">👥</span> Referrals</a></li>
                        <li><a href="#"><span class="menu-icon">👁️</span> Live Show</a></li>
                        <li><a href="#"><span class="menu-icon">📹</span> Streaming</a></li>
                        <li><a href="#"><span class="menu-icon">🎧</span> Support Desk</a></li>
                        <li><a href="#"><span class="menu-icon">⚙️</span> Settings</a></li>
                        <li><a href="#"><span class="menu-icon">🚪</span> Logout</a></li>
                    </ul>
                </div>
                <div class="logo-container">
                    <img src="<?php echo get_template_directory_uri(); ?>/images/logo-hq.png" alt="influencerHQ" class="logo-img">
                </div>
                <div class="globe-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#E6CFA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>
                </div>
            </header>

            <!-- Profile Section -->
            <div class="profile-section">
                <div class="profile-left">
                    <div class="profile-avatar">
                        <img src="https://i.pravatar.cc/150?img=5" alt="AliceGames">
                    </div>
                    <div class="profile-info">
                        <h2 class="profile-name">AliceGames</h2>
                        <p class="profile-handle">@alicegames</p>
                    </div>
                </div>
                <div class="profile-right">
                    <div class="profile-badge">QUALIFIED</div>
                </div>
            </div>

            <!-- Qualification Status Card -->
            <div class="status-card">
                <div class="status-bar"></div>
                <div class="status-content">
                    <h3 class="card-label">Qualification Status</h3>
                    <h2 class="status-value">QUALIFIED</h2>
                    <p class="status-msg">Congratulations!</p>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons">
                <button class="gold-btn">Start a Challenge</button>
                <button class="gold-btn">Invite Followers</button>
                <button class="gold-btn">View Network</button>
            </div>

            <!-- Activity Snapshot -->
            <div class="info-card">
                <h3 class="card-title">Your Activity Snapshot</h3>
                <ul class="stats-list">
                    <li>
                        <span class="stat-label">• Followers</span>
                        <span class="stat-value-group">
                            <span class="stat-num green">+243</span>
                            <span class="stat-sub">past 7 days</span>
                        </span>
                    </li>
                    <li>
                        <span class="stat-label">• Network Growth</span>
                        <span class="stat-value-group">
                            <span class="stat-num green">+79</span>
                            <span class="stat-sub">Level 1-3 referrals</span>
                        </span>
                    </li>
                    <li>
                        <span class="stat-label">• Active Challenges</span>
                        <span class="stat-value-group">
                            <span class="stat-num green">+2</span>
                            <span class="stat-sub">ongoing</span>
                        </span>
                    </li>
                    <li>
                        <span class="stat-label">• Current Rank</span>
                        <span class="stat-value-group">
                            <span class="stat-num green">#27</span>
                            <span class="stat-sub">out of 427</span>
                        </span>
                    </li>
                </ul>
            </div>

            <!-- Challenge Ending -->
            <div class="info-card challenge-ending">
                <div class="challenge-header">
                    <h3 class="card-title">Challenge Ending In</h3>
                </div>
                <div class="challenge-body">
                    <div class="timer-red">2d 14h 23m</div>
                    <a href="#" class="view-link">View Challenge ></a>
                </div>
                <div class="challenge-rank">Your Rank: #27</div>
            </div>

            <footer class="simple-footer">
                <a href="#">Terms</a> | <a href="#">Privacy</a>
                <div class="home-indicator"></div>
            </footer>
        </div>
    </main><!-- #main -->
<style>
    :root {
        --gold: #E6CFA0;
        --gold-dark: #C4A46D;
        --green-light: #98D8A1;
        --green-bright: #66D986;
        --red: #FF5555;
        --dark-bg: #0a0a0a;
        --card-bg: linear-gradient(90deg, rgb(0 0 0) 0%, rgb(0 0 0 / 0%) 50%, rgba(0, 0, 0, 0.5) 100%);
        --border-gold: #E6CFA0;
    }

    body {
        margin: 0;
        padding: 0;
        font-family: 'Source Sans Pro', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        background-color: var(--dark-bg);
        color: #fff;
    }

    .site-main {
        position: relative;
        min-height: 100vh;
        overflow-x: hidden;
    }

    /* Background */
    .page-background {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
        z-index: -1;
    }
    
    /* Overlay to darken background if needed */
    .page-background::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.4);
    }

    .mobile-container {
        width: 100%;
        margin: 0 auto;
        padding: 20px;
        position: relative;
        z-index: 1;
    }

    @media (min-width: 1024px) {
        .mobile-container {
            max-width: 960px;
        }
    }

    /* Top Search */
    .top-search-wrapper {
        margin-bottom: 20px;
    }

    .search-pill {
        background: #fff;
        border-radius: 30px;
        padding: 8px 15px;
        display: flex;
        align-items: center;
        box-shadow: 0 2px 10px rgba(0,0,0,0.2);
    }

    .search-icon {
        color: #000;
        margin-right: 10px;
        display: flex;
        align-items: center;
    }

    .search-pill input {
        border: none;
        background: transparent;
        width: 100%;
        outline: none;
        font-size: 16px;
        color: #000;
    }

    /* Header */
    .mobile-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
    }

    .hamburger-btn {
        background: none;
        border: none;
        display: flex;
        flex-direction: column;
        gap: 5px;
        cursor: pointer;
        padding: 0;
    }

    .hamburger-btn span {
        display: block;
        width: 24px;
        height: 2px;
        background-color: #fff;
        border-radius: 2px;
    }

    .logo-container {
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .logo-img {
        height: 40px;
        width: auto;
    }

    .logo-text {
        font-size: 24px;
        font-weight: 700;
        color: #fff;
        letter-spacing: -0.5px;
    }

    .logo-hq {
        color: #fff;
    }

    .logo-waves {
        display: flex;
        align-items: center;
        margin-left: 2px;
    }
    
    .logo-waves svg {
        stroke: #d9222a;
    }

    /* Profile Section */
    .profile-section {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
    }

    .profile-left {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .profile-avatar {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        overflow: hidden;
        border: 2px solid #fff;
    }

    .profile-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .profile-name {
        margin: 0;
        font-size: 18px;
        font-weight: 700;
        color: #fff;
    }

    .profile-handle {
        margin: 0;
        font-size: 14px;
        color: #aaa;
    }

    .profile-badge {
        border: 1px solid var(--green-light);
        color: var(--green-light);
        padding: 5px 15px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: 1px;
        background: rgba(152, 216, 161, 0.1);
    }

    /* Status Card */
    .status-card {
        background: var(--card-bg);
        border: 1px solid var(--border-gold);
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 25px;
        display: flex;
        position: relative;
        overflow: hidden;
    }

    .status-bar {
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 15px;
        background-color: var(--green-light);
    }

    .status-content {
        margin-left: 25px; /* Space for the bar */
    }

    .card-label {
        color: var(--gold);
        font-size: 14px;
        font-weight: 600;
        margin: 0 0 5px 0;
    }

    .status-value {
        color: var(--green-light);
        font-size: 32px;
        font-weight: 700;
        margin: 0 0 15px 0;
        line-height: 1.2;
    }

    .status-msg {
        color: #ccc;
        font-size: 14px;
        margin: 0;
    }

    /* Action Buttons */
    .action-buttons {
        display: flex;
        flex-direction: column;
        gap: 15px;
        margin-bottom: 30px;
    }

    .gold-btn {
        background: var(--card-bg);
        border: 1px solid var(--border-gold);
        color: var(--gold);
        padding: 15px;
        border-radius: 8px;
        font-size: 20px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.3s ease;
        text-align: center;
    }

    .gold-btn:hover {
        background: rgba(230, 207, 160, 0.1);
        transform: translateY(-2px);
    }

    /* Info Cards */
    .info-card {
        background: var(--card-bg);
        border: 1px solid var(--border-gold);
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
    }

    .card-title {
        color: #fff;
        font-size: 16px;
        font-weight: 400;
        margin: 0 0 15px 0;
    }

    .stats-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .stats-list li {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 0;
        border-bottom: 1px solid rgba(255,255,255,0.1);
    }

    .stats-list li:last-child {
        border-bottom: none;
    }

    .stat-label {
        color: var(--gold);
        font-size: 14px;
    }

    .stat-value-group {
        text-align: right;
    }

    .stat-num {
        font-weight: 700;
        font-size: 16px;
    }

    .stat-num.green {
        color: var(--green-bright);
    }

    .stat-sub {
        color: #aaa;
        font-size: 12px;
        margin-left: 5px;
    }

    /* Challenge Ending */
    .challenge-ending .card-title {
        margin-bottom: 10px;
    }

    .challenge-body {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
    }

    .timer-red {
        color: var(--red);
        font-size: 24px;
        font-weight: 700;
    }

    .view-link {
        color: var(--gold);
        text-decoration: none;
        font-size: 14px;
    }

    .challenge-rank {
        color: #fff;
        font-size: 14px;
    }

    /* Footer */
    .simple-footer {
        text-align: center;
        padding: 20px 0;
        color: #888;
        font-size: 12px;
    }

    .simple-footer a {
        color: #888;
        text-decoration: none;
    }

    .home-indicator {
        width: 140px;
        height: 5px;
        background-color: var(--gold);
        border-radius: 10px;
        margin: 15px auto 0;
    }

    /* Mobile Menu Modal Styling */
    .menu-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.7);
        z-index: 999;
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.3s ease, visibility 0.3s;
        backdrop-filter: blur(2px);
    }

    .menu-overlay.active {
        opacity: 1;
        visibility: visible;
    }

    .mobile-menu-modal {
        opacity: 0;
        visibility: hidden;
        transform: translateY(-10px);
        transition: opacity 0.2s ease, transform 0.2s ease, visibility 0.2s;
        position: absolute;
        top: 60px;
        left: 20px;
        z-index: 1000;
        width: 250px;
        background: linear-gradient(to bottom, #1a1a1a, #050505);
        border: 1px solid #E6CFA0;
        border-radius: 10px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.8);
        padding: 10px 0;
    }

    .mobile-menu-modal.active {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }

    .mobile-menu-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .mobile-menu-list li {
        border-bottom: 1px solid rgba(230, 207, 160, 0.2);
    }

    .mobile-menu-list li:last-child {
        border-bottom: none;
    }

    .mobile-menu-list a {
        display: flex;
        align-items: center;
        padding: 12px 20px;
        color: #E6CFA0;
        text-decoration: none;
        font-size: 16px;
        font-weight: 700;
        transition: all 0.2s;
        text-shadow: 0 1px 2px rgba(0,0,0,0.5);
    }

    .mobile-menu-list a:hover {
        background: rgba(230, 207, 160, 0.1);
        color: #fff;
    }

    .menu-icon {
        margin-right: 12px;
        font-size: 18px;
        /* Attempt to colorize emojis to gold */
        filter: grayscale(100%) sepia(100%) hue-rotate(5deg) saturate(400%) brightness(0.9);
    }
    footer {display: none;}

</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Mobile menu toggle
    const menuBtn = document.querySelector('.hamburger-btn');
    const mobileMenu = document.querySelector('#mobileMenuModal');
    const menuOverlay = document.querySelector('#menuOverlay');
    
    function closeMenu() {
        if (menuBtn) menuBtn.classList.remove('active');
        if (mobileMenu) mobileMenu.classList.remove('active');
        if (menuOverlay) menuOverlay.classList.remove('active');
    }

    function toggleMenu() {
        if (menuBtn) menuBtn.classList.toggle('active');
        if (mobileMenu) mobileMenu.classList.toggle('active');
        if (menuOverlay) menuOverlay.classList.toggle('active');
    }
    
    if (menuBtn && mobileMenu && menuOverlay) {
        menuBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            toggleMenu();
        });

        menuOverlay.addEventListener('click', function() {
            closeMenu();
        });
        
        // Close menu when clicking outside (fallback)
        document.addEventListener('click', function(event) {
            if (!mobileMenu.contains(event.target) && !menuBtn.contains(event.target)) {
                closeMenu();
            }
        });
    }
});
</script>

<?php
get_footer();

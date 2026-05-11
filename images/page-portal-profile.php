<?php
/**
 * Template Name: Portal Profile
 * Description: A custom template for displaying user profile and statistics.
 *
 * @package Avantage_Baccarat
 */
get_header();

// Load styles before content to prevent FOUC
get_template_part( 'template-parts/portal-styles' );
?>

    <main id="primary" class="site-main">
        
        <?php get_template_part( 'template-parts/portal-header' ); ?>
        
        <div class="container py-2" style="max-width: 1024px; padding-left: 20px; padding-right: 20px;">

            <!-- Profile Content -->
            <div class="profile-page-content">
                
                <!-- Profile Header -->
                <div class="profile-header">
                    <div class="profile-icon">👤</div>
                    <h1 class="profile-title">PROFILE</h1>
                </div>

                <!-- Profile User Info -->
                <div class="profile-user-info">
                    <div class="user-avatar" onclick="openProfileModal()">
                        <img src="<?php echo get_template_directory_uri(); ?>/images/avatar-placeholder.png" alt="AliceChen" id="profileAvatar">
                    </div>
                    <div class="user-details">
                        <h2 class="user-name">AliceChen</h2>
                        <p class="user-location">EN 🇹🇭</p>
                    </div>
                </div>

                <!-- Celebrity League Section -->
                <div class="league-section">
                    <div class="league-header" onclick="toggleSection('celebrityLeague')">
                        <h3>CELEBRITY BNX OVERALL LEAGUE</h3>
                        <span class="toggle-icon">▶</span>
                    </div>
                    <div class="league-content" id="celebrityLeague">
                        <div class="league-tabs">
                            <button class="league-tab active">Team Score</button>
                            <button class="league-tab">Individual Score</button>
                            <button class="league-tab">Weekly Score</button>
                        </div>
                        <table class="league-table">
                            <thead>
                                <tr>
                                    <th>Team Name</th>
                                    <th>Team Leader</th>
                                    <th>Score</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Team Alpha</td>
                                    <td>Leader 1</td>
                                    <td>15,420</td>
                                </tr>
                                <tr>
                                    <td>Team Beta</td>
                                    <td>Leader 2</td>
                                    <td>14,850</td>
                                </tr>
                                <tr>
                                    <td>Team Gamma</td>
                                    <td>Leader 3</td>
                                    <td>13,990</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- International League Section -->
                <div class="league-section">
                    <div class="league-header" onclick="toggleSection('internationalLeague')">
                        <h3>INTERNATIONAL LEAGUE</h3>
                        <span class="toggle-icon">▼</span>
                    </div>
                    <div class="league-content" id="internationalLeague" style="display: block;">
                        <table class="league-table international-table">
                            <thead>
                                <tr>
                                    <th>Score / Week #1</th>
                                    <th>Score / Week #2</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <div class="match-list">
                                            <div class="match-item">2 Match / W: null</div>
                                            <div class="match-item">Celeb / In Round 1:</div>
                                            <div class="match-item">Round 1 Score: 0</div>
                                            <div class="match-item">Celeb / In Round 2:</div>
                                            <div class="match-item">Round 2 Score: 0</div>
                                            <div class="match-item">Celeb / In Round 3:</div>
                                            <div class="match-item">Round 3 Score: 0</div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="match-list">
                                            <div class="match-item">12 Match / W: null</div>
                                            <div class="match-item">Celeb / In Round 1:</div>
                                            <div class="match-item">Round 1 Score: 0</div>
                                            <div class="match-item">Celeb / In Round 2:</div>
                                            <div class="match-item">Round 2 Score: 0</div>
                                            <div class="match-item">Celeb / In Round 3:</div>
                                            <div class="match-item">Round 3 Score: 0</div>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Team Leader Ranking & Awards Section -->
                <div class="league-section">
                    <div class="league-header" onclick="toggleSection('teamLeaderRanking')">
                        <h3>TEAM LEADER RANKING & AWARDS</h3>
                        <span class="toggle-icon">▶</span>
                    </div>
                    <div class="league-content" id="teamLeaderRanking">
                        <div class="ranking-tabs">
                            <button class="ranking-tab">Featured</button>
                            <button class="ranking-tab">1-on-1 week</button>
                            <button class="ranking-tab">3 person</button>
                            <button class="ranking-tab">All Matches</button>
                            <button class="ranking-tab">All</button>
                        </div>
                        <div class="team-stats">
                            <p>Playing Record: <strong>High Roller</strong></p>
                            <p>Rating: <strong>~420</strong></p>
                            <p>Ranking History: <strong>Feb-08 - Bng-V 21</strong></p>
                            <p>Playing Method: <strong>~440</strong></p>
                        </div>
                    </div>
                </div>

                <!-- Ranking Section -->
                <div class="ranking-section">
                    <h3>RANKING</h3>
                    <div class="ranking-tabs-main">
                        <button class="ranking-tab-main active">Overall</button>
                        <button class="ranking-tab-main">6 months</button>
                        <button class="ranking-tab-main">1 Year</button>
                        <button class="ranking-tab-main">All</button>
                    </div>
                </div>

                <!-- Points Section -->
                <div class="points-section">
                    <h3>POINTS</h3>
                    <table class="points-table">
                        <thead>
                            <tr>
                                <th>GAME</th>
                                <th colspan="2">ROUND/MATCH BREAKDOWN</th>
                                <th>SCORE</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>1-Round-A</td>
                                <td colspan="2">-</td>
                                <td>-1,245</td>
                            </tr>
                            <tr>
                                <td>1-Round-B</td>
                                <td colspan="2">-</td>
                                <td>+2,100</td>
                            </tr>
                            <tr>
                                <td>3-person-A</td>
                                <td colspan="2">-</td>
                                <td>-1,980</td>
                            </tr>
                            <tr class="total-row">
                                <td colspan="3">TOTAL POINTS</td>
                                <td>+3,215</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Medals Section -->
                <div class="medals-section">
                    <h3>MEDALS</h3>
                    <table class="medals-table">
                        <thead>
                            <tr>
                                <th>GAME</th>
                                <th>TROPHY/MEDAL/CERTIFICATE 1</th>
                                <th>SCORE</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>1-Round-A</td>
                                <td>-</td>
                                <td>255</td>
                            </tr>
                            <tr>
                                <td>1-Round-B</td>
                                <td>-</td>
                                <td>0.5</td>
                            </tr>
                            <tr>
                                <td>3-person-A</td>
                                <td>-</td>
                                <td>3.25</td>
                            </tr>
                            <tr>
                                <td>All</td>
                                <td>-</td>
                                <td>250</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Rules at a Glance Section -->
                <div class="league-section">
                    <div class="league-header" onclick="toggleSection('rulesGlance')">
                        <h3>RULES AT A GLANCE</h3>
                        <span class="toggle-icon">▼</span>
                    </div>
                    <div class="league-content" id="rulesGlance" style="display: block;">
                        <div class="rules-content">
                            <p><strong>Title Verification:</strong> Same (3)</p>
                            <p><strong>Prerequisites:</strong> League Team's Fll ip prize.</p>
                            <p><strong>Objective:</strong> Simple Match All Of the Rising Shoes.</p>
                        </div>
                    </div>
                </div>

                <!-- Statistics Section -->
                <div class="statistics-section">
                    <h3>STATISTICS</h3>
                    <div class="stats-grid">
                        <div class="stat-item">
                            <div class="stat-circle">RANK #1</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-circle">RANK #2</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-circle">RANK #3</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-circle">RANK #4</div>
                        </div>
                    </div>
                    <div class="stats-details">
                        <div class="stat-detail-item">
                            <p>3 Matches Won</p>
                            <p>1 From South CACFIN</p>
                        </div>
                        <div class="stat-detail-item">
                            <p>1 Win, Matches</p>
                            <p>1 All Matches</p>
                            <p>1 From South CACFIN</p>
                        </div>
                        <div class="stat-detail-item">
                            <p>1 Matches Won</p>
                            <p>2 Win - From All UP</p>
                            <p>1 From UP Of 4 DW</p>
                        </div>
                    </div>
                </div>

            </div>
            
        </div>
        
        <!-- Profile Modal -->
        <div id="profileModal" class="profile-modal" onclick="closeProfileModal(event)">
            <div class="modal-content" onclick="event.stopPropagation()">
                <div class="modal-header">
                    <button class="modal-close" onclick="closeProfileModal(event)">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="modal-avatar">
                        <img src="<?php echo get_template_directory_uri(); ?>/images/avatar-placeholder.jpg" alt="Profile">
                    </div>
                    <h3 class="modal-username">AliceChen</h3>
                    <button class="modal-change-btn">Change Photo</button>
                </div>
            </div>
        </div>
        
        <!-- Fixed Footer Links -->
        <?php get_template_part( 'template-parts/portal-footer' ); ?>
    </main><!-- #main -->

<style>
    /* Profile Header */
    .profile-header {
        text-align: center;
        margin-bottom: 30px;
        padding: 20px 0;
    }

    .profile-icon {
        font-size: 60px;
        margin-bottom: 15px;
    }

    .profile-title {
        color: #fff;
        font-size: 2rem;
        font-weight: 300;
        letter-spacing: 0.15em;
        margin: 0;
    }

    /* Profile User Info */
    .profile-user-info {
        background: #000;
        border: 2px solid rgba(255, 255, 255, 0.3);
        border-radius: 12px;
        padding: 25px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 20px;
    }

    .user-avatar {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        overflow: hidden;
        border: 2px solid #fff;
        cursor: pointer;
        flex-shrink: 0;
    }

    .user-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .user-details {
        flex: 1;
    }

    .user-name {
        color: #fff;
        font-size: 1.5rem;
        font-weight: 600;
        margin: 0 0 5px 0;
    }

    .user-location {
        color: rgba(255, 255, 255, 0.7);
        font-size: 1rem;
        margin: 0;
    }

    /* League Sections */
    .league-section {
        background: #000;
        border: 2px solid rgba(255, 255, 255, 0.3);
        border-radius: 12px;
        margin-bottom: 20px;
        overflow: hidden;
    }

    .league-header {
        background: rgba(255, 255, 255, 0.05);
        padding: 15px 20px;
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .league-header h3 {
        color: #fff;
        font-size: 1rem;
        font-weight: 600;
        margin: 0;
        letter-spacing: 0.05em;
    }

    .toggle-icon {
        color: #fff;
        font-size: 0.8rem;
        transition: transform 0.3s;
    }

    .league-content {
        padding: 20px;
        display: none;
    }

    .league-content[style*="display: block"] {
        display: block;
    }

    /* League Tabs */
    .league-tabs {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }

    .league-tab {
        background: rgba(255, 255, 255, 0.1);
        color: #fff;
        border: 1px solid rgba(255, 255, 255, 0.3);
        border-radius: 6px;
        padding: 8px 16px;
        font-size: 0.9rem;
        cursor: pointer;
        transition: all 0.3s;
    }

    .league-tab.active,
    .league-tab:hover {
        background: rgba(255, 255, 255, 0.2);
        border-color: #fff;
    }

    /* League Tables */
    .league-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 15px;
    }

    .league-table thead th {
        color: rgba(255, 255, 255, 0.7);
        font-size: 0.85rem;
        font-weight: 600;
        text-align: left;
        padding: 10px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .league-table tbody td {
        color: #fff;
        font-size: 0.9rem;
        padding: 12px 10px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .league-table tbody tr:last-child td {
        border-bottom: none;
    }

    /* International Table */
    .international-table td {
        vertical-align: top;
    }

    .match-list {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }

    .match-item {
        font-size: 0.85rem;
        color: rgba(255, 255, 255, 0.9);
        line-height: 1.4;
    }

    /* Ranking Tabs */
    .ranking-tabs,
    .ranking-tabs-main {
        display: flex;
        gap: 10px;
        margin: 15px 0;
        flex-wrap: wrap;
    }

    .ranking-tab,
    .ranking-tab-main {
        background: transparent;
        color: rgba(255, 255, 255, 0.7);
        border: 1px solid rgba(255, 255, 255, 0.3);
        border-radius: 4px;
        padding: 6px 14px;
        font-size: 0.85rem;
        cursor: pointer;
        transition: all 0.3s;
    }

    .ranking-tab.active,
    .ranking-tab-main.active,
    .ranking-tab:hover,
    .ranking-tab-main:hover {
        background: rgba(255, 255, 255, 0.15);
        color: #fff;
        border-color: #fff;
    }

    /* Team Stats */
    .team-stats {
        margin-top: 15px;
    }

    .team-stats p {
        color: rgba(255, 255, 255, 0.9);
        font-size: 0.9rem;
        margin: 8px 0;
        line-height: 1.6;
    }

    .team-stats strong {
        color: #fff;
        font-weight: 600;
    }

    /* Ranking Section */
    .ranking-section {
        background: #000;
        border: 2px solid rgba(255, 255, 255, 0.3);
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 20px;
    }

    .ranking-section h3 {
        color: #fff;
        font-size: 1rem;
        font-weight: 600;
        margin: 0 0 15px 0;
        letter-spacing: 0.05em;
    }

    /* Points Section */
    .points-section {
        background: #000;
        border: 2px solid rgba(255, 255, 255, 0.3);
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 20px;
    }

    .points-section h3 {
        color: #fff;
        font-size: 1rem;
        font-weight: 600;
        margin: 0 0 15px 0;
        letter-spacing: 0.05em;
    }

    .points-table {
        width: 100%;
        border-collapse: collapse;
    }

    .points-table thead th {
        color: rgba(255, 255, 255, 0.7);
        font-size: 0.75rem;
        font-weight: 600;
        text-align: left;
        padding: 10px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .points-table tbody td {
        color: #fff;
        font-size: 0.9rem;
        padding: 12px 10px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .points-table .total-row {
        background: rgba(255, 255, 255, 0.05);
        font-weight: 600;
    }

    .points-table .total-row td {
        border-bottom: none;
        padding: 15px 10px;
    }

    /* Medals Section */
    .medals-section {
        background: #000;
        border: 2px solid rgba(255, 255, 255, 0.3);
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 20px;
    }

    .medals-section h3 {
        color: #fff;
        font-size: 1rem;
        font-weight: 600;
        margin: 0 0 15px 0;
        letter-spacing: 0.05em;
    }

    .medals-table {
        width: 100%;
        border-collapse: collapse;
    }

    .medals-table thead th {
        color: rgba(255, 255, 255, 0.7);
        font-size: 0.75rem;
        font-weight: 600;
        text-align: left;
        padding: 10px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    .medals-table tbody td {
        color: #fff;
        font-size: 0.9rem;
        padding: 12px 10px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .medals-table tbody tr:last-child td {
        border-bottom: none;
    }

    /* Rules Content */
    .rules-content {
        padding: 10px 0;
    }

    .rules-content p {
        color: rgba(255, 255, 255, 0.9);
        font-size: 0.9rem;
        margin: 10px 0;
        line-height: 1.6;
    }

    .rules-content strong {
        color: #fff;
        font-weight: 600;
    }

    /* Statistics Section */
    .statistics-section {
        background: #000;
        border: 2px solid rgba(255, 255, 255, 0.3);
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 20px;
    }

    .statistics-section h3 {
        color: #fff;
        font-size: 1rem;
        font-weight: 600;
        margin: 0 0 20px 0;
        letter-spacing: 0.05em;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 15px;
        margin-bottom: 20px;
    }

    .stat-circle {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: #fff;
        color: #000;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
        font-weight: 600;
        text-align: center;
        margin: 0 auto;
        line-height: 1.2;
    }

    .stats-details {
        display: flex;
        flex-direction: column;
        gap: 15px;
        margin-top: 20px;
    }

    .stat-detail-item {
        padding: 10px 0;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
    }

    .stat-detail-item p {
        color: rgba(255, 255, 255, 0.9);
        font-size: 0.85rem;
        margin: 5px 0;
        line-height: 1.5;
    }

    /* Profile Modal */
    .profile-modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.8);
        z-index: 10000;
        justify-content: center;
        align-items: center;
        padding: 20px;
    }

    .profile-modal.active {
        display: flex;
    }

    .modal-content {
        background: #000;
        border: 2px solid rgba(255, 255, 255, 0.3);
        border-radius: 12px;
        padding: 30px;
        max-width: 400px;
        width: 100%;
        position: relative;
    }

    .modal-header {
        display: flex;
        justify-content: flex-end;
        margin-bottom: 20px;
    }

    .modal-close {
        background: transparent;
        border: none;
        color: #fff;
        font-size: 2rem;
        cursor: pointer;
        padding: 0;
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        line-height: 1;
    }

    .modal-body {
        text-align: center;
    }

    .modal-avatar {
        width: 150px;
        height: 150px;
        border-radius: 50%;
        overflow: hidden;
        border: 3px solid #fff;
        margin: 0 auto 20px;
    }

    .modal-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .modal-username {
        color: #fff;
        font-size: 1.5rem;
        font-weight: 600;
        margin: 0 0 20px 0;
    }

    .modal-change-btn {
        background: rgba(255, 255, 255, 0.1);
        color: #fff;
        border: 1px solid rgba(255, 255, 255, 0.3);
        border-radius: 6px;
        padding: 10px 30px;
        font-size: 1rem;
        cursor: pointer;
        transition: all 0.3s;
    }

    .modal-change-btn:hover {
        background: rgba(255, 255, 255, 0.2);
        border-color: #fff;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }

        .league-tabs,
        .ranking-tabs,
        .ranking-tabs-main {
            gap: 8px;
        }

        .league-tab,
        .ranking-tab,
        .ranking-tab-main {
            font-size: 0.8rem;
            padding: 6px 12px;
        }
    }

    @media (max-width: 480px) {
        .profile-user-info {
            flex-direction: column;
            text-align: center;
        }

        .stats-grid {
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .stat-circle {
            width: 60px;
            height: 60px;
            font-size: 0.7rem;
        }
    }
</style>

<script>
    function toggleSection(sectionId) {
        const section = document.getElementById(sectionId);
        const header = section.previousElementSibling;
        const icon = header.querySelector('.toggle-icon');
        
        if (section.style.display === 'block') {
            section.style.display = 'none';
            icon.textContent = '▶';
        } else {
            section.style.display = 'block';
            icon.textContent = '▼';
        }
    }

    function openProfileModal() {
        const modal = document.getElementById('profileModal');
        modal.classList.add('active');
    }

    function closeProfileModal(event) {
        event.stopPropagation();
        const modal = document.getElementById('profileModal');
        modal.classList.remove('active');
    }

    // Tab functionality for league tabs
    document.addEventListener('DOMContentLoaded', function() {
        const leagueTabs = document.querySelectorAll('.league-tab');
        leagueTabs.forEach(tab => {
            tab.addEventListener('click', function() {
                // Remove active class from siblings
                this.parentElement.querySelectorAll('.league-tab').forEach(t => t.classList.remove('active'));
                // Add active class to clicked tab
                this.classList.add('active');
            });
        });

        const rankingTabs = document.querySelectorAll('.ranking-tab-main');
        rankingTabs.forEach(tab => {
            tab.addEventListener('click', function() {
                // Remove active class from siblings
                this.parentElement.querySelectorAll('.ranking-tab-main').forEach(t => t.classList.remove('active'));
                // Add active class to clicked tab
                this.classList.add('active');
            });
        });
    });
</script>

<?php
get_template_part( 'template-parts/portal-scripts' );
get_footer();

<?php
/**
 * Template Name: Influencer success Desk
 * Description: A custom template for displaying the influencer success desk.
 * This template is used to render the influencer success desk content and layout.
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package Avantage_Baccarat
 */

// Handle Form Submission
$success_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_help_request'])) {
    $user_id = get_current_user_id();
    if ($user_id) {
        $help_topics = isset($_POST['help_topics']) ? array_map('sanitize_text_field', $_POST['help_topics']) : [];
        $connect_preference = isset($_POST['connect_preference']) ? sanitize_text_field($_POST['connect_preference']) : '';
        $appointment_date = isset($_POST['appointment_date']) ? sanitize_text_field($_POST['appointment_date']) : '';
        $appointment_time = isset($_POST['appointment_time']) ? sanitize_text_field($_POST['appointment_time']) : '';
        $time_zone = isset($_POST['time_zone']) ? sanitize_text_field($_POST['time_zone']) : '';

        $data = [
            'help_topics' => $help_topics,
            'connect_preference' => $connect_preference,
            'appointment_date' => $appointment_date,
            'appointment_time' => $appointment_time,
            'time_zone' => $time_zone,
            'submitted_at' => current_time('mysql')
        ];

        update_user_meta($user_id, 'influencer_success_desk_request', $data);
        $success_message = 'Your request has been submitted successfully.';
    }
}

// Retrieve saved data
$saved_data = [];
$user_id = get_current_user_id();
if ($user_id) {
    $saved_data = get_user_meta($user_id, 'influencer_success_desk_request', true);
}

// Default values
$saved_help_topics = isset($saved_data['help_topics']) ? $saved_data['help_topics'] : [];
$saved_connect_preference = isset($saved_data['connect_preference']) ? $saved_data['connect_preference'] : '';
$saved_appointment_date = isset($saved_data['appointment_date']) ? $saved_data['appointment_date'] : '';
$saved_appointment_time = isset($saved_data['appointment_time']) ? $saved_data['appointment_time'] : '';
$saved_time_zone = isset($saved_data['time_zone']) ? $saved_data['time_zone'] : '';

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
                            <h1 class="page-title">Influencer Success Desk</h1>
                            
                            <?php if ($success_message) : ?>
                                <div class="alert alert-success" style="color: #00ff00; background: rgba(0, 255, 0, 0.1); padding: 15px; border-radius: 10px; margin-bottom: 20px; border: 1px solid #00ff00;">
                                    <?php echo esc_html($success_message); ?>
                                </div>
                            <?php endif; ?>

                            <div class="challenge-card" style="margin-bottom: 30px;">
                                <p style="font-size: 18px; color: #fff; margin-bottom: 10px;">We believe you deserve the level of support you want — when you want it.</p>
                                <p style="color: var(--light-gray);">Tell us how you’d like help, and we’ll take it from there. You can change this anytime.</p>
                            </div>

                            <form method="post" action="" id="help-request-form">
                                
                                <!-- Section 1: Help Topics -->
                                <div class="challenge-card" style="margin-bottom: 20px;">
                                    <h2 class="section-title" style="font-size: 18px; margin-bottom: 20px;">What would you like help with?</h2>
                                    <p style="font-size: 14px; color: #888; margin-bottom: 15px;">(Select all that apply)</p>
                                    
                                    <div class="form-group">
                                        <?php 
                                        $topics = [
                                            'How Challenges work (Private, Peer, World)',
                                            'How to qualify for Live Appearances',
                                            'Equity awards and the long-term ownership model',
                                            'Celebrity Follower Leagues',
                                            'International League participation',
                                            'Learning to stream (or choosing not to)',
                                            'Technical or setup questions',
                                            'Other'
                                        ];
                                        foreach ($topics as $topic) : 
                                            $is_checked = in_array($topic, $saved_help_topics) ? 'checked' : '';
                                            ?>
                                            <div class="custom-control custom-checkbox" style="margin-bottom: 10px;">
                                                <label class="checkbox-container">
                                                    <input type="checkbox" name="help_topics[]" value="<?php echo esc_attr($topic); ?>" <?php echo $is_checked; ?>>
                                                    <span class="checkmark"></span>
                                                    <span class="label-text"><?php echo esc_html($topic); ?></span>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <!-- Section 2: Connection Preference -->
                                <div class="challenge-card" style="margin-bottom: 20px;">
                                    <h2 class="section-title" style="font-size: 18px; margin-bottom: 20px;">How would you like to connect for this request?</h2>
                                    
                                    <div class="form-group">
                                        <label class="radio-container" style="margin-bottom: 10px; display: block;">
                                            <input type="radio" name="connect_preference" value="You call us" id="pref-you-call" <?php checked($saved_connect_preference, 'You call us'); ?>>
                                            <span class="radio-checkmark"></span>
                                            <span class="label-text">You call us</span>
                                        </label>
                                        <label class="radio-container" style="margin-bottom: 10px; display: block;">
                                            <input type="radio" name="connect_preference" value="We call you" id="pref-we-call" <?php checked($saved_connect_preference, 'We call you'); ?>>
                                            <span class="radio-checkmark"></span>
                                            <span class="label-text">We call you</span>
                                        </label>
                                    </div>
                                </div>

                                <!-- Section 3: Appointment Details (Conditional) -->
                                <div class="challenge-card" id="appointment-details" style="margin-bottom: 20px; display: none;">
                                    <h2 class="section-title" style="font-size: 18px; margin-bottom: 10px;">Appointment details</h2>
                                    <p style="font-size: 14px; color: #888; margin-bottom: 20px;">(Shown only when an appointment or callback is selected)</p>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label" style="display: block; margin-bottom: 8px; color: #fff;">Preferred date</label>
                                            <input type="date" name="appointment_date" class="form-control" style="width: 100%;" value="<?php echo esc_attr($saved_appointment_date); ?>" onclick="this.showPicker()">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label" style="display: block; margin-bottom: 8px; color: #fff;">Preferred time</label>
                                            <input type="time" name="appointment_time" class="form-control" style="width: 100%;" value="<?php echo esc_attr($saved_appointment_time); ?>" onclick="this.showPicker()">
                                        </div>
                                        <div class="col-12 mb-3">
                                            <label class="form-label" style="display: block; margin-bottom: 8px; color: #fff;">Time zone</label>
                                            <select name="time_zone" class="form-select" style="width: 100%;">
                                                <option value="">Select Time Zone</option>
                                                <?php
                                                $time_zones = [
                                                    'UTC-12:00' => 'International Date Line West',
                                                    'UTC-11:00' => 'Midway Island, Samoa',
                                                    'UTC-10:00' => 'Hawaii',
                                                    'UTC-09:00' => 'Alaska',
                                                    'UTC-08:00' => 'Pacific Time (US & Canada)',
                                                    'UTC-07:00' => 'Mountain Time (US & Canada)',
                                                    'UTC-06:00' => 'Central Time (US & Canada), Mexico City',
                                                    'UTC-05:00' => 'Eastern Time (US & Canada), Bogota, Lima',
                                                    'UTC-04:00' => 'Atlantic Time (Canada), Caracas, La Paz',
                                                    'UTC-03:30' => 'Newfoundland',
                                                    'UTC-03:00' => 'Brazil, Buenos Aires, Georgetown',
                                                    'UTC-02:00' => 'Mid-Atlantic',
                                                    'UTC-01:00' => 'Azores, Cape Verde Islands',
                                                    'UTC+00:00' => 'Western Europe Time, London, Lisbon, Casablanca',
                                                    'UTC+01:00' => 'Brussels, Copenhagen, Madrid, Paris',
                                                    'UTC+02:00' => 'Kaliningrad, South Africa',
                                                    'UTC+03:00' => 'Baghdad, Riyadh, Moscow, St. Petersburg',
                                                    'UTC+03:30' => 'Tehran',
                                                    'UTC+04:00' => 'Abu Dhabi, Muscat, Baku, Tbilisi',
                                                    'UTC+04:30' => 'Kabul',
                                                    'UTC+05:00' => 'Ekaterinburg, Islamabad, Karachi, Tashkent',
                                                    'UTC+05:30' => 'Bombay, Calcutta, Madras, New Delhi',
                                                    'UTC+05:45' => 'Kathmandu, Pokhara',
                                                    'UTC+06:00' => 'Almaty, Dhaka, Colombo',
                                                    'UTC+06:30' => 'Yangon, Mandalay',
                                                    'UTC+07:00' => 'Bangkok, Hanoi, Jakarta',
                                                    'UTC+08:00' => 'Beijing, Perth, Singapore, Hong Kong',
                                                    'UTC+08:45' => 'Eucla',
                                                    'UTC+09:00' => 'Tokyo, Seoul, Osaka, Sapporo, Yakutsk',
                                                    'UTC+09:30' => 'Adelaide, Darwin',
                                                    'UTC+10:00' => 'Eastern Australia, Guam, Vladivostok',
                                                    'UTC+10:30' => 'Lord Howe Island',
                                                    'UTC+11:00' => 'Magadan, Solomon Islands, New Caledonia',
                                                    'UTC+11:30' => 'Norfolk Island',
                                                    'UTC+12:00' => 'Auckland, Wellington, Fiji, Kamchatka',
                                                    'UTC+12:45' => 'Chatham Islands',
                                                    'UTC+13:00' => 'Apia, Nukualofa',
                                                    'UTC+14:00' => 'Line Islands, Tokelau'
                                                ];
                                                foreach ($time_zones as $value => $label) {
                                                    echo '<option value="' . esc_attr($value) . '" ' . selected($saved_time_zone, $value, false) . '>' . esc_html($label) . '</option>';
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <!-- Section 4: What happens next -->
                                <div class="challenge-card" style="margin-bottom: 20px;">
                                    <h2 class="section-title" style="font-size: 18px; margin-bottom: 20px;">What happens next</h2>
                                    <ul style="list-style: none; padding: 0; color: var(--light-gray);">
                                        <li style="margin-bottom: 10px;">• We never call unless you ask</li>
                                        <li style="margin-bottom: 10px;">• Your preferences are always respected</li>
                                        <li style="margin-bottom: 10px;">• A real person responds to your request</li>
                                        <li style="margin-bottom: 10px;">• You can update this anytime</li>
                                    </ul>
                                </div>

                                <button type="submit" name="submit_help_request" class="btn-join" style="width: 100%; padding: 15px; font-size: 18px; font-weight: bold; text-transform: uppercase;">Request Help</button>

                            </form>
                            
                            <style>
                                /* Custom Checkbox & Radio */
                                .checkbox-container, .radio-container {
                                    display: flex;
                                    align-items: center;
                                    position: relative;
                                    padding-left: 35px;
                                    margin-bottom: 12px;
                                    cursor: pointer;
                                    font-size: 16px;
                                    color: #fff;
                                    user-select: none;
                                }

                                .checkbox-container input, .radio-container input {
                                    position: absolute;
                                    opacity: 0;
                                    cursor: pointer;
                                    height: 0;
                                    width: 0;
                                }

                                .checkmark {
                                    position: absolute;
                                    top: 0;
                                    left: 0;
                                    height: 25px;
                                    width: 25px;
                                    background-color: #1a1a1a;
                                    border: 2px solid #666;
                                    border-radius: 4px;
                                }
                                
                                .radio-checkmark {
                                    position: absolute;
                                    top: 0;
                                    left: 0;
                                    height: 25px;
                                    width: 25px;
                                    background-color: #1a1a1a;
                                    border: 2px solid #666;
                                    border-radius: 50%;
                                }

                                .checkbox-container:hover input ~ .checkmark,
                                .radio-container:hover input ~ .radio-checkmark {
                                    border-color: #ff0000;
                                }

                                .checkbox-container input:checked ~ .checkmark,
                                .radio-container input:checked ~ .radio-checkmark {
                                    background-color: #ff0000;
                                    border-color: #ff0000;
                                }

                                .checkmark:after, .radio-checkmark:after {
                                    content: "";
                                    position: absolute;
                                    display: none;
                                }

                                .checkbox-container input:checked ~ .checkmark:after {
                                    display: block;
                                }
                                
                                .radio-container input:checked ~ .radio-checkmark:after {
                                    display: block;
                                }

                                .checkbox-container .checkmark:after {
                                    left: 9px;
                                    top: 5px;
                                    width: 5px;
                                    height: 10px;
                                    border: solid white;
                                    border-width: 0 3px 3px 0;
                                    -webkit-transform: rotate(45deg);
                                    -ms-transform: rotate(45deg);
                                    transform: rotate(45deg);
                                }
                                
                                .radio-container .radio-checkmark:after {
                                    top: 7px;
                                    left: 7px;
                                    width: 8px;
                                    height: 8px;
                                    border-radius: 50%;
                                    background: white;
                                }
                                
                                .label-text {
                                    margin-left: 10px;
                                }
                            </style>
                            
                            <script>
                                document.addEventListener('DOMContentLoaded', function() {
                                    // Toggle appointment details
                                    const weCallRadio = document.getElementById('pref-we-call');
                                    const youCallRadio = document.getElementById('pref-you-call');
                                    const appointmentDetails = document.getElementById('appointment-details');
                                    
                                    function toggleAppointmentDetails() {
                                        if (weCallRadio && weCallRadio.checked) {
                                            appointmentDetails.style.display = 'block';
                                        } else {
                                            appointmentDetails.style.display = 'none';
                                        }
                                    }
                                    
                                    if (weCallRadio && youCallRadio) {
                                        weCallRadio.addEventListener('change', toggleAppointmentDetails);
                                        youCallRadio.addEventListener('change', toggleAppointmentDetails);
                                        
                                        // Run on load to handle pre-selected state
                                        toggleAppointmentDetails();
                                    }
                                });
                            </script>
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

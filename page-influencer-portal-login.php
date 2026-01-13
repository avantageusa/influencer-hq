<?php
/**
 * Template Name: Influencer portal login
 * Description: A custom template for displaying the influencer HQ login page.
 * This template is used to render the influencer HQ content and layout.
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package Avantage_Baccarat
 */
get_header();
?>

    <main id="primary" class="site-main">
        <section class="hero-section" style="margin-left: 0; display: flex; align-items: center; min-height: 100vh;">
            <div class="container-fluid">
                <div class="row justify-content-center">
                    <div class="col-md-6 col-lg-4">
                        <div class="challenge-builder">
                            <div class="text-center mb-4">
                                <img src="<?php echo get_template_directory_uri(); ?>/images/logo-hq.png" alt="influencerHQ" class="logo-img" style="max-width: 150px;">
                            </div>
                            
                            <h2 class="text-center mb-4 text-gradient">Login</h2>

                            <?php if ( is_user_logged_in() ) : ?>
                                <div class="text-center">
                                    <p class="text-light-gray mb-4">You are already logged in.</p>
                                    <a href="<?php echo home_url('/hq2'); ?>" class="btn-warning text-decoration-none d-inline-block">Go to HQ</a>
                                </div>
                            <?php else : ?>
                                <?php
                                $args = array(
                                    'redirect' => home_url('/hq2'), 
                                    'form_id' => 'loginform',
                                    'label_username' => __( 'Username' ),
                                    'label_password' => __( 'Password' ),
                                    'label_remember' => __( 'Remember Me' ),
                                    'label_log_in' => __( 'Log In' ),
                                    'remember' => true
                                );
                                wp_login_form( $args );
                                ?>
                                <div class="text-center mt-4">
                                    <p class="mb-0">Don't have an account? <a href="<?php echo wp_registration_url(); ?>" class="text-yellow text-decoration-none">Register here</a></p>
                                </div>
                            <?php endif; ?>
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
        
        .hero-section {
            overflow: auto;
            padding: 1rem 0;
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

    /* Login Form Specific Styles */
    #loginform p {
        margin-bottom: 15px;
    }
    #loginform label {
        display: block;
        margin-bottom: 8px;
        color: var(--light-gray);
        font-weight: 500;
    }
    #loginform input[type="text"],
    #loginform input[type="password"] {
        width: 100%;
        padding: 12px 15px;
        background: var(--card-bg);
        border: 2px solid var(--accent-gold);
        color: #fff;
        border-radius: 10px;
        transition: all 0.3s ease;
        margin-bottom: 5px;
    }
    #loginform input[type="text"]:focus,
    #loginform input[type="password"]:focus {
        outline: none;
        box-shadow: 0 0 0 0.25rem rgba(255, 149, 0, 0.25);
        border-color: var(--accent-yellow);
    }
    #loginform input[type="submit"] {
        background: linear-gradient(135deg, var(--accent-gold) 0%, var(--accent-yellow) 100%);
        border: none;
        color: #000;
        font-weight: 600;
        padding: 0.75rem 2rem;
        border-radius: 50px;
        transition: all 0.3s ease;
        box-shadow: 0 5px 20px rgba(255, 193, 7, 0.4);
        width: 100%;
        margin-top: 15px;
        cursor: pointer;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    #loginform input[type="submit"]:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 30px rgba(255, 193, 7, 0.6);
        background: linear-gradient(135deg, var(--accent-yellow) 0%, var(--accent-gold) 100%);
    }
    .login-remember {
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .login-remember input[type="checkbox"] {
        margin: 0;
    }
    .login-remember label {
        margin: 0;
        display: inline;
    }
</style>

<?php
get_footer();

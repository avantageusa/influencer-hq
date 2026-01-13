<?php
/**
 * Template Name: Terms & Conditions
 * Description: Terms and Conditions page for Avantage Entertainment
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package Avantage_Baccarat
 */

get_header();
?>

    <main id="primary" class="site-main">
        
        <section class="terms-section py-5">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-10 col-xl-8">
                        <div class="terms-content p-4 p-md-5">
                            <h1 class="text-center mb-4">INFLUENCER HQ TERMS & CONDITIONS</h1>
                            <p class="text-center version-info mb-5">Version 1.0 – 16 Nov 2025</p>
                            
                            <div class="terms-text">
                                <h2>1.0 ABOUT US</h2>
                                <p>InfluencerHQ LLC is a global creator-support platform that connects influencers with exclusive opportunities, international visibility, and long-term equity participation. We represent the creator economy within a worldwide competition ecosystem and help influencers grow their voice, their communities, and their ownership in the platforms they help build.</p>

                                <h2>2.0 TERMS OF USE</h2>
                                <p>These Terms of Use, together with our Privacy Policy (collectively, the "Terms"), govern your access to and use of the InfluencerHQ website and services ("Services"). By accessing or using our Services, you acknowledge that you have read, understood, and agreed to these Terms.</p>
                                <p>We may update these Terms periodically. Your continued use of the Services following such updates constitutes acceptance of the revised Terms. For questions, contact: <a href="mailto:support@influencerhq.co">support@influencerhq.co</a></p>

                                <h2>3.0 SERVICES PROVIDED</h2>
                                <p>InfluencerHQ provides informational, educational, marketing, communications, promotional, and creator-support resources, including under cooperative marketing agreements with selected partners. These may include educational resources, creator partnership opportunities, promotional materials, and communications regarding events or marketing initiatives.</p>
                                <p>InfluencerHQ does not provide financial services or any real-money transactional functionality.</p>

                                <h2>4.0 USER RESPONSIBILITIES</h2>
                                <p>By using our Services, you agree to use the platform solely for lawful purposes, provide accurate information when voluntarily submitting forms, refrain from infringing intellectual property or the rights of others, and not misuse, disrupt, or attempt to gain unauthorized access to the Services.</p>

                                <h2>5.0 OPTIONAL COMMUNICATIONS</h2>
                                <p>Users may voluntarily provide contact information to receive marketing resources or updates. You may request removal from such communications at any time.</p>

                                <h2>6.0 INACTIVE COMMUNICATIONS</h2>
                                <p>If you no longer engage with our content, we may discontinue outreach at our discretion.</p>

                                <h2>7.0 INTELLECTUAL PROPERTY</h2>
                                <p>All materials displayed on the InfluencerHQ website are the property of InfluencerHQ or its licensors. No part may be reproduced or used without prior written approval.</p>

                                <h2>8.0 ERRORS OR INTERRUPTIONS</h2>
                                <p>We strive to maintain uninterrupted access but cannot guarantee that the website will be free from errors or technical issues.</p>

                                <h2>9.0 LIMITATION OF LIABILITY</h2>
                                <p>InfluencerHQ is not responsible for decisions made based on information on this site, use of third-party links or resources, or losses or disruptions resulting from your use of the Services. Use of the site is at your own discretion and risk.</p>

                                <h2>10.0 GOVERNING LAW</h2>
                                <p>These Terms are governed by the laws of Nevis, and any disputes shall be resolved exclusively in the courts of Nevis.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        
    </main><!-- #main -->
<style>
    /* Base Typography and Colors */
    body, h1, h2, h3, h4, h5, h6, p, a {
        font-family: 'Source Sans Pro', Arial, Helvetica, sans-serif !important;
        color: rgb(255, 255, 252);
    }
    
    body {
        background-color: #000000;
        margin: 0;
    }

    .navbar-brand, .nav-link {
        color: rgb(255, 255, 252) !important;
    }

    a:visited {
        color: inherit;
    }

    /* Terms and Conditions Specific Styling */
    .terms-section {
        min-height: 100vh;
        background-color: #000000;
    }

    .terms-content {
        background: rgba(0, 0, 0, 0.8);
        border-radius: 10px;
        border: 1px solid rgba(255, 255, 255, 0.1);
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    }

    h1 {
        font-size: clamp(2rem, 4vw, 2.75rem) !important; /* Responsive between 2rem and 2.75rem */
        margin-bottom: 30px;
        font-weight: bold;
        color: rgb(255, 255, 252);
        line-height: 1.2; /* Better line height for titles */
    }

    .version-info {
        font-size: clamp(1rem, 2vw, 1.2rem) !important; /* Responsive version info */
        color: rgba(255, 255, 252, 0.8);
        font-style: italic;
    }

    h2 {
        font-size: clamp(1.25rem, 3vw, 1.6rem) !important; /* Responsive section headers */
        margin-top: 40px;
        margin-bottom: 20px;
        font-weight: bold;
        color: rgb(255, 255, 252);
        border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        padding-bottom: 10px;
        line-height: 1.3; /* Better readability */
    }

    p {
        font-size: clamp(0.95rem, 2vw, 1.1rem) !important; /* Responsive body text */
        line-height: 1.7; /* Improved readability - industry standard */
        margin-bottom: 16px; /* Consistent spacing */
        color: rgb(255, 255, 252);
    }
    a {
        color: #ffad00;
        text-decoration: underline;
    }

    a:hover {
        color: #fff;
        text-decoration: none;
    }

    /* Responsive Design - Fine-tuning for specific breakpoints */
    @media (max-width: 767px) {
        .terms-section {
            padding: 30px 0;
        }

        .terms-content {
            margin: 0 15px;
        }

        h2 {
            margin-top: 30px; /* Reduced spacing on mobile */
        }

        p {
            margin-bottom: 14px; /* Slightly tighter spacing on mobile */
        }
    }

    /* Additional breakpoint for better tablet experience */
    @media (max-width: 991px) and (min-width: 768px) {
        .terms-content {
            padding: 2rem; /* Balanced padding for tablets */
        }
    }
</style>
<?php
get_footer();

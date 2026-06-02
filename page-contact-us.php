<?php
/**
 * Template Name: Contact Us
 * Description: Contact us page for InfluencerHQ
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package Avantage_Baccarat
 */

get_header();
?>

<main id="primary" class="site-main">
    <section class="contact-section py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10 col-xl-8">
                    <div class="contact-content p-4 p-md-5">
                        <h1 class="text-center mb-4">CONTACT US</h1>

                        <div class="contact-text">
                            <p>
                                Welcome to InfluencerHQ! Whether you're a creator looking to collaborate or you need some technical assistance, we're here to help.
                                Please reach out to us using the emails below, and our team will get back to you within 24 hours.
                            </p>

                            <div class="contact-card">
                                <h2>For Brand &amp; Creator Collaborations</h2>
                                <p><a href="mailto:collaboration@influencerhq.co">collaboration@influencerhq.co</a></p>
                            </div>

                            <div class="contact-card">
                                <h2>For Technical Support &amp; Troubleshooting</h2>
                                <p><a href="mailto:support@influencerhq.co">support@influencerhq.co</a></p>
                            </div>

                            <div class="contact-card">
                                <h2>For any Suggestions &amp; Feedback</h2>
                                <p><a href="mailto:suggestions@influencerhq.co">suggestions@influencerhq.co</a></p>
                            </div>

                            <p class="response-time mb-0">
                                Our standard response time is 24 hours. Thank you for reaching out to InfluencerHQ!
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main><!-- #main -->

<style>
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

    footer,
    #colophon,
    .site-footer {
        display: none !important;
    }

    .contact-section {
        /* height: 100vh; */
        background-color: #000000;
    }

    .contact-content {
        background: rgba(0, 0, 0, 0.8);
        border-radius: 10px;
        border: 1px solid rgba(255, 255, 255, 0.1);
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    }

    h1 {
        font-size: clamp(2rem, 4vw, 2.75rem) !important;
        font-weight: bold;
        line-height: 1.2;
        margin-bottom: 30px;
    }

    .contact-text > p {
        font-size: clamp(1rem, 2vw, 1.15rem) !important;
        line-height: 1.7;
        margin-bottom: 24px;
    }

    .contact-card {
        background: rgba(255, 255, 255, 0.03);
        border: 1px solid rgba(255, 255, 255, 0.12);
        border-radius: 10px;
        padding: 18px 20px;
        margin-bottom: 16px;
    }

    .contact-card h2 {
        font-size: clamp(1.05rem, 2.3vw, 1.35rem) !important;
        margin: 0 0 8px;
        font-weight: 700;
        color: #f0c93a;
    }

    .contact-card p {
        margin: 0;
        font-size: clamp(1rem, 2vw, 1.1rem) !important;
    }

    a {
        color: #ffad00;
        text-decoration: underline;
        word-break: break-word;
    }

    a:hover {
        color: #ffffff;
        text-decoration: none;
    }

    .response-time {
        margin-top: 28px;
        color: rgba(255, 255, 252, 0.9);
    }

    @media (max-width: 767px) {
        .contact-section {
            padding: 30px 0;
        }

        .contact-content {
            margin: 0 15px;
        }
    }
</style>

<?php
get_footer();

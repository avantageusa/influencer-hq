<?php
/**
 * Template Name: Influencer HQ Tabs in figma
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
        
        <div class="container py-2" style="max-width: 1024px; padding-left: 20px; padding-right: 20px;">
            
            <!-- Top Search Bar -->
            <div class="row justify-content-center mb-4">
                <div class="col-12">
                    <div class="search-pill bg-white rounded-pill d-flex align-items-center px-3 py-2">
                        <span class="search-icon text-dark me-2">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                        </span>
                        <input type="text" class="form-control border-0 p-0 shadow-none" placeholder="">
                    </div>
                </div>
            </div>

            <!-- Header: Logo, Globe -->
            <div class="d-flex justify-content-between align-items-center mb-4 position-relative">
                <div style="width: 24px;"></div> <!-- Spacer -->
                <div class="logo-container text-center">
                    <img src="<?php echo get_template_directory_uri(); ?>/images/logo-hq.png" alt="influencerHQ" class="img-fluid" style="max-height: 40px;">
                </div>
                <div class="globe-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#E6CFA0" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>
                </div>
            </div>

            <!-- Navigation Tabs -->
            <ul class="nav nav-tabs border-0 justify-content-center mb-4 custom-tabs overflow-auto" id="myTab" role="tablist">
                <li class="nav-item" role="presentation"><button class="nav-link" id="home-tab" data-bs-toggle="tab" data-bs-target="#home" type="button" role="tab">Home</button></li>
                <li class="nav-item" role="presentation"><button class="nav-link" id="challenges-tab" data-bs-toggle="tab" data-bs-target="#challenges" type="button" role="tab">Challenges</button></li>
                <li class="nav-item" role="presentation"><button class="nav-link active" id="equity-tab" data-bs-toggle="tab" data-bs-target="#equity" type="button" role="tab">Equity</button></li>
                <li class="nav-item" role="presentation"><button class="nav-link" id="ranking-tab" data-bs-toggle="tab" data-bs-target="#ranking" type="button" role="tab">Ranking</button></li>
                <li class="nav-item" role="presentation"><button class="nav-link" id="network-tab" data-bs-toggle="tab" data-bs-target="#network" type="button" role="tab">Network</button></li>
                <li class="nav-item" role="presentation"><button class="nav-link" id="earnings-tab" data-bs-toggle="tab" data-bs-target="#earnings" type="button" role="tab">Earnings</button></li>
                <li class="nav-item" role="presentation"><button class="nav-link" id="tools-tab" data-bs-toggle="tab" data-bs-target="#tools" type="button" role="tab">Tools</button></li>
                <li class="nav-item" role="presentation"><button class="nav-link" id="support-tab" data-bs-toggle="tab" data-bs-target="#support" type="button" role="tab">Support</button></li>
            </ul>

            <!-- Tab Content -->
            <div class="tab-content" id="myTabContent">
                <div class="tab-pane fade show active" id="equity" role="tabpanel" aria-labelledby="equity-tab">
                    
                    <!-- Accordion -->
                    <div class="accordion custom-accordion" id="equityAccordion">
                        
                        <!-- Item 1 -->
                        <div class="accordion-item bg-transparent border-0 mb-3">
                            <h2 class="accordion-header" id="headingOne">
                                <button class="accordion-button bg-transparent text-gold shadow-none ps-0 py-2" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                    What Equity Represents
                                </button>
                            </h2>
                            <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#equityAccordion">
                                <div class="accordion-body text-white-50 ps-0 pt-0">
                                    <p class="small mb-2">Each dollar of equity is recorded at face value. $1 of equity is always recorded as $1 of ownership participation. What can change over time is how the market values that ownership.</p>
                                    <p class="small mb-2">In growing platforms, ownership participation has historically been valued at multiples of the underlying activity that created it. In some cases, those multiples have been significantly higher than the original dollar amount.</p>
                                    <p class="small mb-2">There are no guarantees, and market value is established externally. What the platform provides is a clear, transparent record of ownership earned through play.</p>
                                    <p class="small mb-0">$1 in cash is always $1. $1 of equity is ownership – and ownership may be valued at multiples over time.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Item 2 -->
                        <div class="accordion-item bg-transparent border-0 mb-3">
                            <h2 class="accordion-header" id="headingTwo">
                                <button class="accordion-button collapsed bg-transparent text-gold shadow-none ps-0 py-2" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                    How Equity Is Earned
                                </button>
                            </h2>
                            <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#equityAccordion">
                                <div class="accordion-body text-white-50 ps-0 pt-0">
                                    <p class="small">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Item 3 -->
                        <div class="accordion-item bg-transparent border-0 mb-3">
                            <h2 class="accordion-header" id="headingThree">
                                <button class="accordion-button collapsed bg-transparent text-gold shadow-none ps-0 py-2" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                    Why Play Matters
                                </button>
                            </h2>
                            <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#equityAccordion">
                                <div class="accordion-body text-white-50 ps-0 pt-0">
                                    <p class="small">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Item 4 -->
                        <div class="accordion-item bg-transparent border-0 mb-3">
                            <h2 class="accordion-header" id="headingFour">
                                <button class="accordion-button collapsed bg-transparent text-gold shadow-none ps-0 py-2" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                                    Equity vs. Earnings
                                </button>
                            </h2>
                            <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#equityAccordion">
                                <div class="accordion-body text-white-50 ps-0 pt-0">
                                    <p class="small">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Item 5 -->
                        <div class="accordion-item bg-transparent border-0 mb-3">
                            <h2 class="accordion-header" id="headingFive">
                                <button class="accordion-button collapsed bg-transparent text-gold shadow-none ps-0 py-2" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFive" aria-expanded="false" aria-controls="collapseFive">
                                    How Equity Is Valued
                                </button>
                            </h2>
                            <div id="collapseFive" class="accordion-collapse collapse" aria-labelledby="headingFive" data-bs-parent="#equityAccordion">
                                <div class="accordion-body text-white-50 ps-0 pt-0">
                                    <p class="small">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Item 6 -->
                        <div class="accordion-item bg-transparent border-0 mb-3">
                            <h2 class="accordion-header" id="headingSix">
                                <button class="accordion-button collapsed bg-transparent text-gold shadow-none ps-0 py-2" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSix" aria-expanded="false" aria-controls="collapseSix">
                                    Liquidity & Settlement
                                </button>
                            </h2>
                            <div id="collapseSix" class="accordion-collapse collapse" aria-labelledby="headingSix" data-bs-parent="#equityAccordion">
                                <div class="accordion-body text-white-50 ps-0 pt-0">
                                    <p class="small">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Item 7 -->
                        <div class="accordion-item bg-transparent border-0 mb-3">
                            <h2 class="accordion-header" id="headingSeven">
                                <button class="accordion-button collapsed bg-transparent text-gold shadow-none ps-0 py-2" type="button" data-bs-toggle="collapse" data-bs-target="#collapseSeven" aria-expanded="false" aria-controls="collapseSeven">
                                    Why Equity Is Tokenized
                                </button>
                            </h2>
                            <div id="collapseSeven" class="accordion-collapse collapse" aria-labelledby="headingSeven" data-bs-parent="#equityAccordion">
                                <div class="accordion-body text-white-50 ps-0 pt-0">
                                    <p class="small">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
                <!-- Other tabs empty -->
                <div class="tab-pane fade" id="home" role="tabpanel"><p class="text-white-50">Home Content</p></div>
                <div class="tab-pane fade" id="challenges" role="tabpanel"><p class="text-white-50">Challenges Content</p></div>
                <div class="tab-pane fade" id="ranking" role="tabpanel"><p class="text-white-50">Ranking Content</p></div>
                <div class="tab-pane fade" id="network" role="tabpanel"><p class="text-white-50">Network Content</p></div>
                <div class="tab-pane fade" id="earnings" role="tabpanel"><p class="text-white-50">Earnings Content</p></div>
                <div class="tab-pane fade" id="tools" role="tabpanel"><p class="text-white-50">Tools Content</p></div>
                <div class="tab-pane fade" id="support" role="tabpanel"><p class="text-white-50">Support Content</p></div>
            </div>
        </div>
    </main><!-- #main -->

<style>
    :root {
        --gold: #E6CFA0;
        --gold-dark: #C4A46D;
        --dark-bg: #0a0a0a;
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
    
    .page-background::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.3);
    }

    .text-gold {
        color: var(--gold) !important;
    }

    /* Custom Tabs */
    .custom-tabs .nav-link {
        color: var(--gold);
        background: transparent;
        border: none;
        font-weight: 600;
        padding: 0.5rem 0.75rem;
        font-size: 1.3rem;
        white-space: nowrap;
    }
    
    .custom-tabs .nav-link:hover {
        color: #fff;
    }

    .custom-tabs .nav-link.active {
        color: var(--gold);
        background: transparent;
        border-bottom: 2px solid var(--gold);
    }

    /* Custom Accordion */
    .custom-accordion .accordion-button {
        font-weight: 600;
        font-size: 1rem;
        display: flex;
        align-items: center;
        justify-content: flex-start;
    }
    
    .custom-accordion .accordion-button::after {
        display: none; /* Hide default bootstrap arrow */
    }

    /* Custom Arrow */
    .custom-accordion .accordion-button::before {
        content: '';
        width: 0; 
        height: 0; 
        border-top: 5px solid transparent;
        border-bottom: 5px solid transparent;
        border-left: 6px solid var(--gold);
        margin-right: 10px;
        transition: transform 0.2s;
    }

    .custom-accordion .accordion-button:not(.collapsed)::before {
        transform: rotate(90deg);
    }
    
    .custom-accordion .accordion-button:focus {
        box-shadow: none;
    }

    /* Scrollbar for tabs */
    .custom-tabs::-webkit-scrollbar {
        height: 0px;
        background: transparent;
    }
    .custom-tabs {
        -ms-overflow-style: none;  /* IE and Edge */
        scrollbar-width: none;  /* Firefox */
    }

</style>

<?php
get_footer();

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700;900&family=Be+Vietnam+Pro:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400&display=swap" rel="stylesheet">
<style>
    :root {
        --gold: #E6CFA0;
        --gold-dark: #C4A46D;
        --dark-bg: #30313e;
    }

    body {
        margin: 0;
        padding: 0;
        font-family: 'Be Vietnam Pro', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        background-color: var(--dark-bg);
        color: #fff;
    }

    .site-main {
        position: relative;
        min-height: 100vh;
        padding-bottom: 80px; /* Account for fixed footer */
    }
    
    

    .text-gold {
        color: var(--gold) !important;
    }

    /* Navigation Links */
    .navigation-wrapper {
        font-size: 1rem;
    }
    
    .nav-link-inline {
        color: #fff;
        text-decoration: none;
        padding: 0 8px;
        transition: color 0.2s;
        font-size: 20px;
        display: inline;
    }
    
    .nav-link-inline:hover {
        color: var(--gold);
    }
    
    .nav-link-inline.active {
        color: var(--gold);
        font-weight: 600;
    }
    
    .nav-separator {
        color: #666;
        margin: 0 4px;
    }
    
    /* Reduce margins for containers inside sticky elements */
    .sticky-header .container,
    .sticky-nav .container,
    .search-bar-container .container {
        margin-top: 0;
        margin-bottom: 0;
    }

    /* Shared Accordion Base (Equity Look) */
    .custom-accordion {
        background: transparent;
        padding: 0;
    }

    .custom-accordion .accordion-item {
        background: #000000;
        border: 0.888px solid #b8972f;
        border-radius: 4.438px;
        box-shadow: 0 3.55px 3.55px 0 rgba(0, 0, 0, 0.25);
        overflow: hidden;
        margin-bottom: 12px;
    }

    .custom-accordion .accordion-button {
        background: transparent;
        color: #ffffff;
        border: none;
        padding: 14px 16px 14px 36px;
        position: relative;
    }

    .custom-accordion .accordion-button:not(.collapsed) {
        background: transparent;
        color: #ffffff;
        box-shadow: none;
    }

    .custom-accordion .accordion-button:focus {
        box-shadow: none;
        border: none;
    }

    .custom-accordion .accordion-body {
        background: transparent;
        color: #ffffff;
        padding: 14px 16px 16px 36px;
    }

    .custom-accordion .accordion-button::after {
        display: none;
    }

    .custom-accordion .accordion-button::before {
        content: '›';
        position: absolute;
        left: 14px;
        top: 50%;
        transform: translateY(-50%);
        color: #b8972f;
        font-size: 28px;
        line-height: 1;
    }

    .question-number {
        font-weight: 600;
        margin-right: 15px;
        flex-shrink: 0;
    }

    .question-text {
        flex-grow: 1;
        text-align: left;
    }

    .accordion-gradient-container {
       
        padding: 20px;
        border-radius: 8px;
    }
    .the-gradient {
        background: radial-gradient(50% 50% at 50% 50%, #363847 38%, #1F2027 100%);
            }
    
    /* Hide footer */
    footer {
        display: none !important;
    }
    
    /* Search Bar */
    .search-bar-container {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        background: rgba(48, 49, 62, 0.98);
        backdrop-filter: blur(10px);
        z-index: 1001;
        padding: 15px 0;
    }
    
    .search-bar {
        position: relative;
        width: 100%;
        max-width: 850px;
        margin: 0 auto;
    }
    
    .search-icon {
        position: absolute;
        left: 20px;
        top: 50%;
        transform: translateY(-50%);
        pointer-events: none;
    }
    
    .search-input {
        width: 100%;
        padding: 12px 20px 12px 50px;
        background: #fff;
        border: none;
        border-radius: 25px;
        font-size: 15px;
        outline: none;
    }
    
    .search-input::placeholder {
        color: #999;
    }
    
    /* Fixed Header */
    .sticky-header {
        position: fixed;
        top: 60px;
        left: 0;
        width: 100%;
        background: rgba(48, 49, 62, 0.95);
        backdrop-filter: blur(10px);
        z-index: 1000;
        padding: 15px 0;
    }
    
    .sticky-nav {
        position: fixed;
        top: 173px;
        left: 0;
        width: 100%;
        background: rgba(48, 49, 62, 0.9);
        backdrop-filter: blur(10px);
        z-index: 999;
        padding: 10px 0;
        margin-bottom: 20px;
        border-top: 1px solid transparent;
        border-bottom: 1px solid transparent;
        border-image: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3) 20%, rgba(255, 255, 255, 0.3) 80%, transparent) 1;
    }
    
    /* Dealer Image */
    .dealer-image-container {
        text-align: center;
        position: relative;
    }
    
    .dealer-image {
        max-width: 100%;
        height: auto;
        border-radius: 8px;
    }

    .dealer-image-wrap {
        position: relative;
    }

    .concierge-text-above {
        color: #fff;
        font-family: 'Be Vietnam Pro', sans-serif;
        font-size: 20px;
        font-weight: 600;
        text-align: center;
        margin: 0 0 12px;
        padding: 0 16px;
    }

    .logo-container img {
        max-height: 30px;
    }
    
    /* Fixed Footer Links */
    .footer-links-fixed {
        position: fixed;
        bottom: 0;
        left: 0;
        width: 100%;
        background: rgba(48, 49, 62, 0.95);
        backdrop-filter: blur(10px);
        color: rgba(255, 255, 255, 0.6);
        font-size: 0.9rem;
        text-align: center;
        padding: 15px 0;
        z-index: 998;
    }

    /* Hamburger Menu */
    .hamburger-menu {
        z-index: 1001;
    }
    
    .hamburger-menu:hover {
        opacity: 0.8;
    }
    
    /* Hamburger Dropdown Menu */
    .hamburger-dropdown {
        position: fixed;
        top: 135px;
        left: 20px;
        width: 250px;
        background: #30313e;
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 8px;
        z-index: 1002;
        opacity: 0;
        visibility: hidden;
        transform: translateY(-10px);
        transition: all 0.3s ease;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
        display: block;
    }
    
    .hamburger-dropdown.open {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }
    
    .dropdown-menu {
        list-style: none;
        padding: 0;
        margin: 0;
        display: block;
    }
    
    .dropdown-menu li {
        margin-bottom: 0;
        display: block;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }
    
    .dropdown-menu li:last-child {
        border-bottom: none;
    }
    
    .dropdown-link {
        display: flex;
        align-items: center;
        padding: 18px 20px;
        color: #fff;
        text-decoration: none;
        transition: all 0.2s ease;
        font-size: 20px;
        font-weight: 500;
    }
    
    .dropdown-link:hover {
        background: rgba(255, 255, 255, 0.05);
    }
    
    .dropdown-link.active {
        background: rgba(255, 255, 255, 0.08);
    }
    
    .dropdown-link i {
        margin-right: 15px;
        width: 30px;
        text-align: center;
        font-size: 24px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    
    /* Icon placeholders - you can replace with actual icon fonts */
    .icon-home::before { content: '🏠'; }
    .icon-equity::before { content: '📊'; }
    .icon-challenges::before { content: '🚩'; }
    .icon-ranking::before { content: '🏆'; }
    .icon-impact::before { content: '📊'; }
    .icon-live::before { content: '📺'; }
    .icon-profile::before { content: '👤'; }
    .icon-settings::before { content: '⚙️'; }
    .icon-logout::before { content: '🚪'; }
    
    /* Footer Link Styles */
    .footer-links-fixed .footer-link {
        color: rgba(255, 255, 255, 0.6);
        text-decoration: none;
        transition: color 0.2s;
    }
    
    .footer-links-fixed .footer-link:hover {
        color: #fff;
    }
    
    .footer-links-fixed .footer-separator {
        margin: 0 8px;
    }

    /* Page Header Styles */
    .page-header {
        margin-bottom: 30px;
    }

    .page-icon {
        display: inline-block;
    }

    .page-title {
        color: #fff;
        font-size: 2.5rem;
        font-weight: 300;
        letter-spacing: 0.15em;
        margin: 0;
    }

    .section-label {
        color: rgba(255, 255, 255, 0.7);
        font-size: 0.8rem;
        font-weight: 600;
        letter-spacing: 0.1em;
        margin-bottom: 10px;
        text-transform: uppercase;
    }

    /* Portal Home Styles */
    body.page-template-page-portal-home-php .concierge-title {
        z-index: 20;
        display: block;
        position: relative;
        color: white;
        font-family: 'Be Vietnam Pro', sans-serif;
        font-size: 20px;
        font-weight: 600;
        text-align: center;
        text-decoration: underline;
        margin: -40px 0 0;
        cursor: pointer;
    }

    body.page-template-page-portal-home-php .concierge-title:hover {
        opacity: 0.8;
    }

    body.page-template-page-portal-home-php .concierge-text {
        margin-top:0;
        color: white;
        font-family: 'Be Vietnam Pro', sans-serif;
        font-size: 20px;
        font-weight: 600;
        margin-bottom: 40px;
    }
    
    body.page-template-page-portal-home-php #appointmentModal .modal-content {
        background: black;
        border: 1px solid white;
        border-radius: 5px;
        padding: 20px;
        max-width: 400px;
        margin: auto;
    }

    body.page-template-page-portal-home-php #appointmentModal .modal-header {
        border-bottom: none;
        padding-bottom: 0;
    }

    body.page-template-page-portal-home-php #appointmentModal .modal-title {
        font-family: 'Cinzel', serif;
        font-size: 14px;
        font-weight: bold;
        color: white;
        text-transform: uppercase;
    }

    body.page-template-page-portal-home-php #appointmentModal .modal-subtitle {
        font-family: 'Be Vietnam Pro', sans-serif;
        font-size: 9.22px;
        color: white;
        margin-bottom: 20px;
    }

    body.page-template-page-portal-home-php .appointment-slots-container {
        background: radial-gradient(circle at 50% 50%, #363847 37.5%, #1F2027 100%);
        border: 0.904px solid #b8972f;
        border-radius: 4.522px;
        padding: 15px;
    }

    body.page-template-page-portal-home-php .date-selector {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-bottom: 10px;
        margin-bottom: 10px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.8);
    }

    body.page-template-page-portal-home-php .date-selector .date-text {
        font-family: 'Be Vietnam Pro', sans-serif;
        font-size: 10.852px;
        color: white;
        font-weight: 300;
    }

    body.page-template-page-portal-home-php .date-nav-btn {
        background: none;
        border: none;
        color: white;
        font-size: 16px;
        cursor: pointer;
        padding: 0 10px;
    }

    body.page-template-page-portal-home-php .time-slot {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 0;
        border-bottom: 1px solid rgba(255, 255, 255, 0.8);
    }

    body.page-template-page-portal-home-php .time-slot:last-child {
        border-bottom: none;
    }

    body.page-template-page-portal-home-php .time-slot-label {
        font-family: 'Be Vietnam Pro', sans-serif;
        font-size: 10.852px;
        color: white;
        font-weight: 300;
    }

    body.page-template-page-portal-home-php .time-slot-status {
        display: flex;
        gap: 10px;
        align-items: center;
    }

    body.page-template-page-portal-home-php .status-badge {
        background: black;
        border: 1.164px solid #b8972f;
        border-radius: 5.822px;
        padding: 4px 12px;
        font-family: 'Be Vietnam Pro', sans-serif;
        font-size: 8px;
        color: white;
        text-align: center;
    }

    body.page-template-page-portal-home-php .status-badge.selected {
        background: #b8972f;
        color: black;
    }

    body.page-template-page-portal-home-php .status-badge.available {
        cursor: pointer;
    }

    body.page-template-page-portal-home-php .status-badge.available:hover {
        opacity: 0.8;
    }

    body.page-template-page-portal-home-php .cancel-link {
        font-family: 'Be Vietnam Pro', sans-serif;
        font-size: 8px;
        color: #a4a4a4;
        text-decoration: none;
        cursor: pointer;
    }

    body.page-template-page-portal-home-php .cancel-link:hover {
        color: white;
    }

    /* Portal Equity Styles */
    body.page-template-page-portal-equity-php .equity-intro {
        color: #ffffff;
        font-family: 'Be Vietnam Pro', sans-serif;
        padding:0 20px;
        font-size: 18px;
        font-weight: 400;
        line-height: 1.4;
        text-align: left;
        margin: 0 0 20px;
    }

    body.page-template-page-portal-equity-php .equity-intro p {
        margin: 0 0 14px;
        font-weight: 300;
        font-size: 16px;
    }

    body.page-template-page-portal-equity-php .equity-intro p:last-child {
        margin-bottom: 0;
    }

    body.page-template-page-portal-equity-php .equity-header {
        text-align: center;
        margin-bottom: 18px;
    }

    body.page-template-page-portal-equity-php .equity-header-top {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        margin-bottom: 12px;
    }

    body.page-template-page-portal-equity-php .equity-icon {
        width: 59px;
        height: 48px;
    }

    body.page-template-page-portal-equity-php .equity-title {
        color: #ffffff;
        font-family: 'Cinzel', serif;
        font-size: 28px;
        font-weight: 600;
        letter-spacing: 0.1em;
        margin: 0;
        text-transform: uppercase;
    }

    body.page-template-page-portal-equity-php .equity-section {
        padding: 20px;
    }

    body.page-template-page-portal-equity-php .equity-card {
        background: rgba(31, 32, 39, 1);
        border: 1px solid #D4AF37;
        border-radius: 8px;
        padding: 12px;
        color: #ffffff;
    }

    body.page-template-page-portal-equity-php .equity-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 10px;
    }

    body.page-template-page-portal-equity-php .equity-card-toggle {
        font-size: 12px;
        line-height: 1;
        opacity: 0.9;
    }

    body.page-template-page-portal-equity-php .equity-attribution-grid {
        display: grid;
        gap: 6px;
        font-family: 'Be Vietnam Pro', sans-serif;
        font-size: 12px;
    }

    body.page-template-page-portal-equity-php .equity-attribution-row {
        display: flex;
        justify-content: space-between;
        gap: 12px;
    }

    body.page-template-page-portal-equity-php .equity-attribution-row span {
        white-space: nowrap;
    }

    body.page-template-page-portal-equity-php .equity-card {
        background: #000;
        border: 1px solid #b8972f;
        border-radius: 6px;
        padding: 16px;
        margin-bottom: 16px;
    }

    body.page-template-page-portal-equity-php .equity-card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 14px;
    }

    body.page-template-page-portal-equity-php .equity-card-title {
        font-family: 'Be Vietnam Pro', sans-serif;
        font-size: 17px;
        font-weight: 700;
        color: #fff;
    }

    body.page-template-page-portal-equity-php .equity-card-toggle {
        font-size: 20px;
        color: #b8972f;
        cursor: pointer;
        transition: transform 0.2s ease;
    }

    body.page-template-page-portal-equity-php .equity-gradient-divider {
        height: 1px;
        background: linear-gradient(90deg, transparent 0%, rgba(184,151,47,0.7) 30%, rgba(184,151,47,0.7) 70%, transparent 100%);
        margin: 10px 0;
    }

    body.page-template-page-portal-equity-php .equity-filter-wrap {
        display: flex;
        flex-direction: column;
        gap: clamp(10px, 3vw, 24px);
        margin-bottom: 20px;
        align-items: stretch;
    }

    body.page-template-page-portal-equity-php .equity-filter-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(0, 1fr));
        gap: clamp(6px, 3vw, 24px);
    }

    body.page-template-page-portal-equity-php .equity-filter-item {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: clamp(5px, 1.5vw, 10px);
        cursor: pointer;
        user-select: none;
        font-family: 'Be Vietnam Pro', sans-serif;
        font-size: clamp(12px, 2vw, 15px);
        font-weight: 500;
        color: #fff;
        white-space: nowrap;
        opacity: 1;
        transition: opacity 0.2s;
        border-bottom: none !important;
    }

    body.page-template-page-portal-equity-php .equity-filter-check {
        width: clamp(20px, 4vw, 26px);
        height: clamp(20px, 4vw, 26px);
        min-width: clamp(20px, 4vw, 26px);
        border: 2px solid #b8972f;
        border-radius: 4px;
        background: transparent;
        display: inline-flex;
        flex-shrink: 0;
        transition: background 0.2s, border-color 0.2s;
    }

    body.page-template-page-portal-equity-php .equity-filter-item.active .equity-filter-check {
        background: #b8972f;
        border-color: #b8972f;
    }

    body.page-template-page-portal-equity-php .equity-filter-item:not(.active) {
        opacity: 0.6;
    }

    body.page-template-page-portal-equity-php .equity-tabs {
        display: flex;
        gap: 12px;
        font-size: 12px;
        font-weight: 600;
        letter-spacing: 0.05em;
        text-transform: uppercase;
    }

    body.page-template-page-portal-equity-php .equity-tab {
        padding: 0;
        border-bottom: none;
    }

    body.page-template-page-portal-equity-php .equity-tab.active {
        border-color: transparent;
    }

    body.page-template-page-portal-equity-php .equity-divider {
        height: 1px;
        background: rgba(212, 175, 55, 0.35);
        margin: 8px 0 10px;
    }

    body.page-template-page-portal-equity-php .equity-legend {
        display: none;
    }

    body.page-template-page-portal-equity-php .equity-legend-item {
        display: flex;
        align-items: center;
        gap: 6px;
        white-space: nowrap;
    }

    body.page-template-page-portal-equity-php .equity-legend-swatch {
        display: none;
    }

    body.page-template-page-portal-equity-php .equity-panels {
        display: grid;
        gap: 12px;
        margin-top: 12px;
    }

    body.page-template-page-portal-equity-php .equity-panel {
        background: rgba(18, 19, 25, 0.9);
        border: 1px solid rgba(212, 175, 55, 0.6);
        border-radius: 8px;
        padding: 10px;
    }

    body.page-template-page-portal-equity-php .equity-panel-title {
        font-family: 'Be Vietnam Pro', sans-serif;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        margin-bottom: 8px;
    }

    body.page-template-page-portal-equity-php .equity-panel-row {
        display: flex;
        justify-content: space-between;
        font-family: 'Be Vietnam Pro', sans-serif;
        font-size: 12px;
        padding: 2px 0;
    }

    body.page-template-page-portal-equity-php .equity-panel-divider {
        height: 1px;
        background: rgba(255, 255, 255, 0.2);
        margin: 8px 0;
    }

    body.page-template-page-portal-equity-php .equity-panel-placeholder {
        font-family: 'Be Vietnam Pro', sans-serif;
        font-size: 11px;
        opacity: 0.7;
        text-align: center;
        padding: 18px 0;
    }

    body.page-template-page-portal-equity-php .equity-tab {
        cursor: pointer;
    }

    body.page-template-page-portal-equity-php .equity-legend-item {
        cursor: pointer;
        transition: opacity 0.2s;
        user-select: none;
    }

    body.page-template-page-portal-equity-php .equity-legend-item:not(.active) {
        opacity: 0.5;
    }

    body.page-template-page-portal-equity-php .equity-chart-container {
        position: relative;
        height: 180px;
        margin-top: 4px;
    }

    body.page-template-page-portal-equity-php .equity-chart-container canvas {
        display: block;
        width: 100% !important;
        height: 100% !important;
    }

    body.page-template-page-portal-equity-php .equity-chart-loading {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-family: 'Be Vietnam Pro', sans-serif;
        font-size: 11px;
        color: rgba(255,255,255,0.5);
        background: rgba(18,19,25,0.6);
        border-radius: 4px;
        pointer-events: none;
    }

    body.page-template-page-portal-equity-php .equity-no-data {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 100%;
        font-family: 'Be Vietnam Pro', sans-serif;
        font-size: 12px;
        color: rgba(255,255,255,0.45);
        text-align: center;
        padding: 12px;
    }

    /* API Debug Block */
    body.page-template-page-portal-equity-php .equity-debug-block {
        background: rgba(10, 10, 14, 0.95);
        border: 1px solid rgba(212, 175, 55, 0.3);
        border-radius: 8px;
        padding: 12px;
        color: #ffffff;
    }

    body.page-template-page-portal-equity-php .equity-debug-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-family: 'Be Vietnam Pro', sans-serif;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: rgba(212, 175, 55, 0.8);
        margin-bottom: 6px;
    }

    body.page-template-page-portal-equity-php .equity-debug-status {
        font-size: 10px;
        font-weight: 700;
        letter-spacing: 0.08em;
        padding: 1px 6px;
        border-radius: 3px;
        border: 1px solid transparent;
    }

    body.page-template-page-portal-equity-php .equity-debug-status--ok {
        color: #53FC18;
        border-color: rgba(83, 252, 24, 0.4);
    }

    body.page-template-page-portal-equity-php .equity-debug-status--error {
        color: #ff6b6b;
        border-color: rgba(255, 107, 107, 0.4);
    }

    body.page-template-page-portal-equity-php .equity-debug-pre {
        margin: 0;
        padding: 10px;
        background: rgba(0, 0, 0, 0.4);
        border: 1px solid rgba(255, 255, 255, 0.07);
        border-radius: 4px;
        font-family: 'Courier New', Courier, monospace;
        font-size: 10px;
        line-height: 1.5;
        color: #c8e6c9;
        white-space: pre-wrap;
        word-break: break-all;
        max-height: 260px;
        overflow-y: auto;
    }

    body.page-template-page-portal-equity-php .equity-sso-btn {
        background: rgba(212, 175, 55, 0.12);
        border: 1px solid rgba(212, 175, 55, 0.5);
        border-radius: 4px;
        color: #D4AF37;
        font-family: 'Be Vietnam Pro', sans-serif;
        font-size: 11px;
        font-weight: 600;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        padding: 6px 14px;
        cursor: pointer;
        transition: background 0.2s, border-color 0.2s;
    }

    body.page-template-page-portal-equity-php .equity-sso-btn:hover {
        background: rgba(212, 175, 55, 0.22);
        border-color: #D4AF37;
    }

    body.page-template-page-portal-equity-php .equity-sso-btn:disabled {
        opacity: 0.45;
        cursor: not-allowed;
    }

    /* Go To Game button — shown in portal header across all portal pages */
    .go-to-game-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 14px;
        font-family: 'Be Vietnam Pro', sans-serif;
        font-size: 14px;
        font-weight: 700;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        color: #0a0b0f;
        background: linear-gradient(135deg, #D4AF37 0%, #f0d060 50%, #D4AF37 100%);
        background-size: 200% auto;
        border: none;
        border-radius: 6px;
        text-decoration: none;
        white-space: nowrap;
        box-shadow: 0 2px 10px rgba(212,175,55,0.45), inset 0 1px 0 rgba(255,255,255,0.25);
        transition: background-position 0.3s ease, box-shadow 0.2s ease, transform 0.1s ease;
    }
    
    .go-to-game-btn:hover {
        background-position: right center;
        box-shadow: 0 4px 16px rgba(212,175,55,0.65), inset 0 1px 0 rgba(255,255,255,0.25);
        text-decoration: none;
        color: #0a0b0f;
        transform: translateY(-1px);
    }
    .go-to-game-btn:active {
        transform: translateY(0);
        box-shadow: 0 1px 6px rgba(212,175,55,0.4);
    }
    body.page-template-page-portal-challenges-php.competition-page-wrap,
    body.page-template-page-portal-challenges-php .competition-page-wrap {
        max-width: 1024px;
        padding-left: 20px;
        padding-right: 20px;
    }

    body.page-template-page-portal-challenges-php .competition-header {
        text-align: center;
        margin-bottom: 16px;
    }

    body.page-template-page-portal-challenges-php .competition-header-top {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        margin-bottom: 12px;
    }

    body.page-template-page-portal-challenges-php .competition-icon {
        width: 52px;
        height: 52px;
    }

    body.page-template-page-portal-challenges-php .competition-title {
        color: #ffffff;
        font-family: 'Cinzel', serif;
        font-size: 28px;
        font-weight: 600;
        letter-spacing: 0.1em;
        margin: 0;
        text-transform: uppercase;
    }

    /* Competition Lead Section */
    body.page-template-page-portal-challenges-php .comp-lead {
        margin-bottom: 16px;
        overflow: hidden;
    }
    body.page-template-page-portal-challenges-php .comp-lead-sep {
        height: 2px;
        background: radial-gradient(ellipse 80% 100% at 50% 50%, rgba(184,151,47,.8) 0%, rgba(184,151,47,0) 100%);
    }
    body.page-template-page-portal-challenges-php .comp-lead-kings {
        font-family: 'Cinzel', serif;
        font-size: 28px; font-weight: 700;
        color: #fff; text-align: center;
        margin: 0; padding: 14px 16px 12px;
        letter-spacing: .04em;
    }
    body.page-template-page-portal-challenges-php .comp-lead-body {
        padding: 14px 20px;
        font-family: 'Be Vietnam Pro', sans-serif;
        font-size: 16px; color: #fff; line-height: 1.55;
    }
    body.page-template-page-portal-challenges-php .comp-lead-body p { margin: 0 0 12px; }
    body.page-template-page-portal-challenges-php .comp-lead-body p:last-child { margin-bottom: 0; }
    body.page-template-page-portal-challenges-php .comp-lead-game-title {
        font-family: 'Cinzel', serif;
        font-size: 20px; font-weight: 700;
        color: #fff; text-align: center; text-transform: uppercase;
        margin: 0; padding: 12px 16px;
        letter-spacing: .06em;
    }
    body.page-template-page-portal-challenges-php .comp-lead-avantage-head {
        display: flex; align-items: center; justify-content: center;
        gap: 10px; padding: 12px 16px;
    }
    body.page-template-page-portal-challenges-php .comp-lead-avantage-head .comp-lead-game-title { padding: 0; }
    body.page-template-page-portal-challenges-php .comp-lead-avantage-icon { width: 32px; height: 32px; object-fit: contain; }
    body.page-template-page-portal-challenges-php .comp-lead-row {
        display: flex; align-items: center; justify-content: space-between;
        padding: 10px 40px;
        font-family: 'Be Vietnam Pro', sans-serif;
        font-size: 16px; font-weight: 600; color: #fff;
    }
    body.page-template-page-portal-challenges-php .comp-lead-row--single {
        justify-content: center; padding: 10px 20px;
    }
    body.page-template-page-portal-challenges-php .comp-lead-row--stack {
        flex-direction: column; gap: 2px;
        align-items: center; justify-content: center;
        padding: 8px 20px;
    }
    body.page-template-page-portal-challenges-php .comp-lead-row-main { font-size: 16px; font-weight: 600; }
    body.page-template-page-portal-challenges-php .comp-lead-row-sub  { font-size: 12px; font-weight: 400; color: rgba(255,255,255,.75); }

    body.page-template-page-portal-challenges-php .competition-top-accordion {
        margin-bottom: 16px;
    }

    body.page-template-page-portal-challenges-php .competition-types {
        margin: 12px 0 18px;
        text-align: left;
    }

    body.page-template-page-portal-challenges-php .competition-types-label {
        font-family: 'Be Vietnam Pro', sans-serif;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        margin-bottom: 8px;
    }

    body.page-template-page-portal-challenges-php .competition-tabs {
        display: flex;
        justify-content: space-between;
        gap: 12px;
    }

    body.page-template-page-portal-challenges-php .competition-tab-btn {
        flex: 1;
        background: transparent;
        border: 1px solid rgba(184, 151, 47, 0.4);
        color: #ffffff;
        padding: 8px 6px;
        border-radius: 6px;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 6px;
        font-family: 'Be Vietnam Pro', sans-serif;
        font-size: 16px;
        cursor: pointer;
    }

    body.page-template-page-portal-challenges-php .competition-tab-btn.active {
        border-color: #b8972f;
        background: rgba(184, 151, 47, 0.1);
    }

    body.page-template-page-portal-challenges-php .competition-tab-icon {
        width: 22px;
        height: 22px;
    }

    body.page-template-page-portal-challenges-php .competition-panel {
        display: none;
    }

    body.page-template-page-portal-challenges-php .competition-panel.active {
        display: block;
    }

    body.page-template-page-portal-challenges-php .competition-card {
        background: radial-gradient(50% 50% at 50% 50%, #363847 37.5%, #1F2027 100%);
        border: 0.888px solid #b8972f;
        border-radius: 4.438px;
        box-shadow: 0 3.55px 3.55px 0 rgba(0, 0, 0, 0.25);
        padding: 12px;
        margin-bottom: 16px;
        font-family: 'Be Vietnam Pro', sans-serif;
        font-size: 16px;
        line-height: 1.4;
    }

    body.page-template-page-portal-challenges-php .competition-section-title {
        color: #ffffff;
        font-family: 'Cinzel', serif;
        font-size: 20px;
        font-weight: 600;
        text-transform: uppercase;
        text-align: center;
        margin: 16px 0 12px;
    }

    body.page-template-page-portal-challenges-php .competition-block {
        margin-bottom: 18px;
    }

    body.page-template-page-portal-challenges-php .competition-block-title {
        color: #ffffff;
        font-family: 'Be Vietnam Pro', sans-serif;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        margin-bottom: 8px;
    }

    body.page-template-page-portal-challenges-php .competition-rule-block {
        border: 1px solid rgba(184, 151, 47, 0.6);
        border-radius: 6px;
        padding: 12px;
        margin-bottom: 16px;
        background: rgba(0, 0, 0, 0.4);
    }

    body.page-template-page-portal-challenges-php .competition-rule-header {
        font-family: 'Be Vietnam Pro', sans-serif;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        margin-bottom: 10px;
    }

    body.page-template-page-portal-challenges-php .competition-rule-note {
        font-family: 'Be Vietnam Pro', sans-serif;
        font-size: 16px;
        color: rgba(255, 255, 255, 0.8);
        margin-bottom: 10px;
    }

    body.page-template-page-portal-challenges-php .competition-rule-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 10px 16px;
        font-family: 'Be Vietnam Pro', sans-serif;
        font-size: 16px;
    text-align: center;
    }

    body.page-template-page-portal-challenges-php .competition-rule-title {
        text-decoration: underline;
        margin-bottom: 4px;
    }

    body.page-template-page-portal-challenges-php .competition-rule-list {
        line-height: 1.4;
    }

    body.page-template-page-portal-challenges-php .competition-medals {
        display: flex;
        justify-content: space-around;
        align-items: center;
        gap: 10px;
        margin-bottom: 20px;
    }

    body.page-template-page-portal-challenges-php .competition-medals img,
    body.page-template-page-portal-challenges-php .competition-medals__img {
        width: 72px;
        height: 72px;
        object-fit: contain;
    }

    body.page-template-page-portal-challenges-php .competition-dropdown {
        border: 1px solid rgba(184, 151, 47, 0.6);
        border-radius: 6px;
        padding: 12px;
        margin-bottom: 16px;
        background: rgba(0, 0, 0, 0.4);
    }

    body.page-template-page-portal-challenges-php .competition-dropdown-header {
        font-family: 'Be Vietnam Pro', sans-serif;
        font-size: 16px;
        font-weight: 600;
        text-transform: uppercase;
        margin-bottom: 8px;
    }

    body.page-template-page-portal-challenges-php .competition-dropdown-body {
        display: grid;
        gap: 8px;
    }

    body.page-template-page-portal-challenges-php .competition-pill {
        background: #b8972f;
        color: #000;
        border: none;
        border-radius: 6px;
        padding: 8px 24px;
        font-size: 16px;
        font-weight: 600;
        font-family: 'Be Vietnam Pro', sans-serif;
        cursor: pointer;
    }

    body.page-template-page-portal-challenges-php .competition-pill.active {
        background: #b8972f;
    }

    body.page-template-page-portal-challenges-php .competition-pill-group {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
    }

    body.page-template-page-portal-challenges-php .competition-mini-table {
        border: 1px solid rgba(184, 151, 47, 0.6);
        border-radius: 6px;
        padding: 8px;
        display: grid;
        gap: 6px;
        font-size: 16px;
    }

    body.page-template-page-portal-challenges-php .competition-mini-row {
        display: flex;
        justify-content: space-between;
    }

    body.page-template-page-portal-challenges-php .competition-medal-counts {
        display: flex;
        justify-content: space-between;
        font-size: 16px;
        font-weight: 600;
        padding: 6px 0;
    }

    body.page-template-page-portal-challenges-php .competition-panel-card {
        border: 1px solid rgba(184, 151, 47, 0.6);
        border-radius: 6px;
        padding: 12px;
        margin-bottom: 16px;
        background: radial-gradient(50% 50% at 50% 50%, #363847 37.5%, #1F2027 100%);
    }

    body.page-template-page-portal-challenges-php .competition-panel-title {
        font-family: 'Be Vietnam Pro', sans-serif;
        font-size: 16px;
        font-weight: 600;
        text-transform: uppercase;
        margin-bottom: 6px;
    }

    body.page-template-page-portal-challenges-php .competition-panel-subtitle {
        font-family: 'Be Vietnam Pro', sans-serif;
        font-size: 16px;
        color: rgba(255, 255, 255, 0.8);
        margin-bottom: 8px;
    }

    body.page-template-page-portal-challenges-php .competition-panel-link {
        font-size: 16px;
        text-decoration: underline;
        margin-bottom: 6px;
    }

    body.page-template-page-portal-challenges-php .competition-form {
        display: flex;
        gap: 8px;
        align-items: center;
    }

    body.page-template-page-portal-challenges-php .competition-input {
        flex: 1;
        background: #30313e;
        border: 1px solid rgba(184, 151, 47, 0.6);
        border-radius: 4px;
        color: #fff;
        padding: 8px 10px;
        font-size: 16px;
    }

    body.page-template-page-portal-challenges-php .competition-btn {
        background: #30313e;
        border: 1px solid rgba(184, 151, 47, 0.8);
        color: #fff;
        border-radius: 4px;
        padding: 8px 14px;
        font-size: 16px;
        cursor: pointer;
    }

    body.page-template-page-portal-challenges-php .competition-invite-list {
        display: grid;
        gap: 8px;
    }

    body.page-template-page-portal-challenges-php .competition-invite-item {
        display: grid;
        grid-template-columns: 40px 1fr auto;
        align-items: center;
        gap: 8px;
        font-size: 16px;
    }

    body.page-template-page-portal-challenges-php .competition-invite-item img {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        object-fit: cover;
    }

    body.page-template-page-portal-challenges-php .competition-invite-item span {
        color: rgba(255, 255, 255, 0.6);
        font-size: 16px;
    }

    body.page-template-page-portal-challenges-php .competition-tag {
        border: 1px solid rgba(184, 151, 47, 0.6);
        border-radius: 4px;
        padding: 4px 8px;
        font-size: 16px;
    }

    body.page-template-page-portal-challenges-php .competition-tab-row {
        display: flex;
        gap: 6px;
        margin-bottom: 8px;
    }

    body.page-template-page-portal-challenges-php .competition-accept-list {
        display: grid;
        gap: 6px;
        font-size: 16px;
    }

    body.page-template-page-portal-challenges-php .competition-accept-item {
        display: grid;
        grid-template-columns: 1fr auto auto;
        gap: 8px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        padding-bottom: 6px;
    }

    body.page-template-page-portal-challenges-php .competition-share-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 8px;
        font-size: 16px;
        text-transform: uppercase;
    }

    body.page-template-page-portal-challenges-php .competition-schedule {
        display: grid;
        gap: 8px;
    }

    body.page-template-page-portal-challenges-php .competition-schedule-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 10px;
        font-size: 16px;
    }

    body.page-template-page-portal-challenges-php .competition-image-stack {
        display: grid;
        gap: 12px;
    }

    body.page-template-page-portal-challenges-php .competition-image-stack img {
        width: 100%;
        border-radius: 6px;
        border: 1px solid rgba(184, 151, 47, 0.4);
    }

    body.page-template-page-portal-challenges-php .competition-pill-center {
        text-align: center;
        margin-bottom: 14px;
    }

    body.page-template-page-portal-challenges-php .competition-dropdown-section {
        margin-bottom: 10px;
    }

    body.page-template-page-portal-challenges-php .competition-dropdown-section--lg {
        margin-bottom: 14px;
    }

    body.page-template-page-portal-challenges-php .competition-dropdown-label {
        font-size: 16px;
        color: rgba(255, 255, 255, 0.7);
        text-align: center;
        margin-bottom: 6px;
    }

    body.page-template-page-portal-challenges-php .competition-dropdown-select-wrap {
        position: relative;
    }

    body.page-template-page-portal-challenges-php .competition-dropdown-select {
        width: 100%;
        background: #000;
        border: 1px solid #b8972f;
        border-radius: 4px;
        color: #fff;
        padding: 8px 32px 8px 12px;
        font-size: 16px;
        appearance: none;
        -webkit-appearance: none;
        cursor: pointer;
    }

    body.page-template-page-portal-challenges-php .competition-dropdown-arrow {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        color: #b8972f;
        pointer-events: none;
    }

    body.page-template-page-portal-challenges-php .competition-mini-row--header {
        font-weight: 600;
        border-bottom: 1px solid rgba(184, 151, 47, 0.4);
        padding-bottom: 6px;
        margin-bottom: 6px;
    }

    body.page-template-page-portal-challenges-php .competition-mini-row--total {
        border-top: 1px solid rgba(255, 255, 255, 0.3);
        margin-top: 6px;
        padding-top: 6px;
        font-weight: 600;
        justify-content: flex-end;
        gap: 20px;
    }

    body.page-template-page-portal-challenges-php .competition-mini-row--api-points {
        color: #b8972f;
    }

    body.page-template-page-portal-challenges-php .competition-mini-row--api-header {
        margin-top: 10px;
        padding-top: 8px;
        border-top: 1px solid rgba(184, 151, 47, 0.5);
        font-weight: 600;
        font-size: 12px;
        color: rgba(184, 151, 47, 0.7);
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    body.page-template-page-portal-challenges-php .competition-get-points-wrap {
        text-align: center;
        margin-top: 12px;
    }

    body.page-template-page-portal-challenges-php .challenge-api-block {
        margin-bottom: 20px;
        padding-bottom: 20px;
        border-bottom: 1px solid rgba(184, 151, 47, 0.2);
    }

    body.page-template-page-portal-challenges-php .challenge-api-block:last-child {
        margin-bottom: 0;
        padding-bottom: 0;
        border-bottom: none;
    }

    body.page-template-page-portal-challenges-php .challenge-api-block-title {
        font-size: 13px;
        font-weight: 600;
        color: #b8972f;
        letter-spacing: 0.04em;
        margin-bottom: 10px;
        font-family: monospace;
    }

    body.page-template-page-portal-challenges-php .challenge-api-controls {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        align-items: center;
    }

    body.page-template-page-portal-challenges-php .challenge-api-controls .competition-input {
        flex: 1 1 160px;
    }

    body.page-template-page-portal-challenges-php .competition-debug-block {
        margin-top: 10px;
    }

    body.page-template-page-portal-challenges-php .competition-debug-label {
        font-size: 11px;
        color: rgba(255, 255, 255, 0.5);
        text-transform: uppercase;
        letter-spacing: 0.04em;
        margin-bottom: 4px;
    }

    body.page-template-page-portal-challenges-php .competition-debug-pre {
        background: rgba(0, 0, 0, 0.6);
        border: 1px solid rgba(184, 151, 47, 0.3);
        border-radius: 4px;
        color: #ccc;
        font-size: 11px;
        padding: 8px;
        white-space: pre-wrap;
        word-break: break-all;
        max-height: 200px;
        overflow-y: auto;
    }

    body.page-template-page-portal-challenges-php .competition-medal-display {
        display: flex;
        justify-content: space-around;
        align-items: flex-end;
        margin-top: 10px;
    }

    body.page-template-page-portal-challenges-php .competition-medal-item {
        text-align: center;
    }

    body.page-template-page-portal-challenges-php .competition-medal-item img {
        width: 60px;
        height: 60px;
        object-fit: contain;
    }

    body.page-template-page-portal-challenges-php .competition-medal-count {
        font-size: 16px;
        font-weight: 600;
        margin-top: 4px;
    }

    /* Portal Live Styles */
    body.page-template-page-portal-live-php .live-page-wrap {
        max-width: 1024px;
        padding-left: 20px;
        padding-right: 20px;
    }

    body.page-template-page-portal-live-php .live-page-content {
        color: #fff;
        font-family: "Be Vietnam Pro", "Inter", sans-serif;
    }

    body.page-template-page-portal-live-php .live-intro-text {
        margin-bottom: 16px;
    }

    body.page-template-page-portal-live-php .live-intro-text p {
        margin: 0 0 20px;
        color: #e5e5e5;
        font-size: 20px;
        line-height: 1.55;
    }

    body.page-template-page-portal-live-php .live-text-content p {
        margin: 0 0 20px;
        color: #e5e5e5;
        font-size: 20px;
        line-height: 1.55;
    }

    body.page-template-page-portal-live-php .live-text-content ul {
        padding-left: 1rem;
    }

    body.page-template-page-portal-live-php .live-text-content ul li {
        color: #e5e5e5;
        font-size: 20px;
        line-height: 1.55;
    }

    body.page-template-page-portal-live-php .live-intro-body {
        margin: 0 0 12px;
        color: #e5e5e5;
        font-size: 13px;
        line-height: 1.55;
    }

    body.page-template-page-portal-live-php .live-section-heading--how {
        margin-top: 18px;
        margin-bottom: 14px;
    }

    body.page-template-page-portal-live-php .live-separator {
        height: 2px;
        width: 100%;
        margin: 10px 0;
        background: radial-gradient(ellipse at center, rgba(184, 151, 47, 0.82) 0%, rgba(184, 151, 47, 0) 75%);
    }

    body.page-template-page-portal-live-php .live-separator-top {
        margin-top: 0;
    }

    body.page-template-page-portal-live-php .live-separator-white {
        margin-top: 16px;
        background: radial-gradient(ellipse at center, rgba(255, 255, 255, 0.95) 0%, rgba(255, 255, 255, 0) 78%);
    }

    body.page-template-page-portal-live-php .live-header {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        text-align: center;
        margin-bottom: 20px;
    }


    body.page-template-page-portal-live-php .live-header-icon {
        width: 52px;
        height: 40px;
        background: #b8972f;
        mask-image: url('http://localhost:3845/assets/3bb2d8671de454f79326d6bc4539806060fa6a3e.png');
        mask-repeat: no-repeat;
        mask-size: contain;
        mask-position: center;
        -webkit-mask-image: url('http://localhost:3845/assets/3bb2d8671de454f79326d6bc4539806060fa6a3e.png');
        -webkit-mask-repeat: no-repeat;
        -webkit-mask-size: contain;
        -webkit-mask-position: center;
    }

    body.page-template-page-portal-live-php .live-header-icon img {
        display: none;
    }

    body.page-template-page-portal-live-php .live-title {
        margin: 0;
        font-family: "Cinzel", serif;
        font-size: 30px;
        line-height: 1;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.1em;
    }

    body.page-template-page-portal-live-php .live-intro-list,
    body.page-template-page-portal-live-php .live-form-block,
    body.page-template-page-portal-live-php .kick-guide,
    body.page-template-page-portal-live-php .live-copy,
    body.page-template-page-portal-live-php .live-copy-muted,
    body.page-template-page-portal-live-php .live-schedule-item,
    body.page-template-page-portal-live-php .live-submit-row,
    body.page-template-page-portal-live-php .live-section-heading {
        padding-left: 0;
        padding-right: 0;
    }

    body.page-template-page-portal-live-php .live-intro-item {
        display: flex;
        align-items: flex-start;
        gap: 8px;
        margin-bottom: 10px;
        padding-left: 0;
        padding-right: 0;
    }

    body.page-template-page-portal-live-php .live-intro-item img {
        width: 13px;
        height: 16px;
        object-fit: contain;
        margin-top: 2px;
    }

    body.page-template-page-portal-live-php .live-intro-item p {
        margin: 0 0 20px;
        color: #e5e5e5;
        font-size: 20px;
        line-height: 1.55;
    }

    body.page-template-page-portal-live-php .live-intro-item-wide {
        margin-top: 10px;
        margin-bottom: 18px;
        padding-left: 0;
        padding-right: 0;
    }

    body.page-template-page-portal-live-php .live-section-heading {
        margin: 10px 0;
        font-size: 20px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        line-height: 1.2;
    }

    body.page-template-page-portal-live-php .live-label {
        margin: 20px 0 12px;
        font-size: 18px;
        font-weight: 600;
        color: #fff;
    }

    body.page-template-page-portal-live-php .live-input-row {
        display: flex;
        gap: 10px;
        margin-bottom: 10px;
        align-items: center;
    }

    body.page-template-page-portal-live-php .live-input-row-choice {
        display: flex;
        align-items: center;
        gap: 16px;
        margin-bottom: 10px;
    }

    body.page-template-page-portal-live-php .live-choice-label {
        flex: 0 0 90px;
        font-size: 16px;
        font-weight: 500;
        color: #fff;
        white-space: nowrap;
    }

    body.page-template-page-portal-live-php .live-field {
        display: flex;
        flex-direction: column;
        gap: 4px;
        flex: 1;
        min-width: 0;
    }

    body.page-template-page-portal-live-php .live-field--full {
        width: 100%;
    }

    body.page-template-page-portal-live-php .live-field-label {
        font-size: 11px;
        color: #b8972f;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        margin: 0;
    }

    body.page-template-page-portal-live-php .live-input-row-2 .live-input {
        flex: 1;
        min-width: 0;
    }

    body.page-template-page-portal-live-php .live-input {
        border: 1px solid #b8972f;
        border-radius: 4px;
        min-height: 36px;
        padding: 7px 36px 7px 14px;
        color: #fff;
        font-size: 14px;
        line-height: 1.2;
        background: #000;
    }

    body.page-template-page-portal-live-php select.live-input,
    body.page-template-page-portal-live-php input.live-input {
        background: #000;
        outline: none;
        width: 100%;
        font-family: inherit;
        -webkit-appearance: none;
        appearance: none;
        color: #fff;
        box-sizing: border-box;
    }

    body.page-template-page-portal-live-php input.live-input::placeholder {
        color: #888;
    }

    body.page-template-page-portal-live-php select.live-input option {
        background: #000;
        color: #fff;
    }

    /* Gold chevron arrow for selects */
    body.page-template-page-portal-live-php .live-field {
        position: relative;
    }

    body.page-template-page-portal-live-php .live-field select.live-input {
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%23b8972f' stroke-width='2' fill='none' stroke-linecap='round'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 12px center;
        padding-right: 36px;
    }

    body.page-template-page-portal-live-php input[type="time"].live-input::-webkit-calendar-picker-indicator,
    body.page-template-page-portal-live-php input[type="date"].live-input::-webkit-calendar-picker-indicator {
        filter: invert(0.7);
        cursor: pointer;
    }

    body.page-template-page-portal-live-php .live-status--pending {
        background: transparent;
        color: #b8972f;
    }

    body.page-template-page-portal-live-php .live-request-msg {
        margin-top: 10px;
        font-size: 12px;
        padding: 6px 10px;
        border-radius: 2px;
    }

    body.page-template-page-portal-live-php .live-request-msg--success {
        background: rgba(184, 151, 47, 0.12);
        color: #b8972f;
        border: 1px solid rgba(184, 151, 47, 0.35);
    }

    body.page-template-page-portal-live-php .live-request-msg--error {
        background: rgba(220, 60, 60, 0.1);
        color: #e07070;
        border: 1px solid rgba(200, 50, 50, 0.35);
    }

    body.page-template-page-portal-live-php .live-input-lg {
        width: 100%;
    }

    body.page-template-page-portal-live-php .live-input-md {
        width: 100%;
    }

    body.page-template-page-portal-live-php .live-status {
        display: inline-block;
        min-width: 220px;
        min-height: 44px;
        border: 1px solid #b8972f;
        border-radius: 4px;
        background: transparent;
        color: #fff;
        font-size: 16px;
        font-weight: 600;
        text-align: center;
        line-height: 42px;
        margin-bottom: 12px;
        padding: 0 16px;
    }

    body.page-template-page-portal-live-php .live-url {
        width: 100%;
        max-width: 460px;
        min-height: 30px;
        border: 1px solid #b8972f;
        border-radius: 2px;
        background: #b8972f;
        color: #000;
        font-size: 11px;
        line-height: 28px;
        padding: 0 10px;
    }

    body.page-template-page-portal-live-php .live-copy-muted {
        margin: 14px 0 6px;
        color: #fff;
        font-size: 13px;
    }

    body.page-template-page-portal-live-php .live-copy {
        margin: 6px 0 10px;
        color: #fff;
        font-size: 13px;
    }

    body.page-template-page-portal-live-php .live-schedule-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        max-width: 460px;
        margin-bottom: 10px;
        font-size: 13px;
        color: #fff;
    }

    body.page-template-page-portal-live-php .live-inline-btn {
        border: 1px solid #b8972f;
        border-radius: 2px;
        background: transparent;
        color: #c7c7c7;
        font-size: 11px;
        line-height: 1;
        padding: 5px 12px;
        cursor: pointer;
    }

    body.page-template-page-portal-live-php .live-submit-row {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-top: 4px;
    }

    body.page-template-page-portal-live-php .live-submit {
        width: 90px;
        border: 1px solid #b8972f;
        border-radius: 2px;
        background: #b8972f;
        color: #000;
        font-size: 11px;
        font-weight: 600;
        line-height: 1;
        padding: 7px 8px;
        cursor: pointer;
    }

    body.page-template-page-portal-live-php .live-link-btn {
        border: none;
        background: transparent;
        color: #c7c7c7;
        font-size: 12px;
        text-decoration: underline;
        padding: 0;
        cursor: pointer;
    }

    @media (max-width: 900px) {
        body.page-template-page-portal-live-php .live-page-content {
            max-width: 100%;
        }

        body.page-template-page-portal-live-php .live-title {
            font-size: 36px;
        }

        body.page-template-page-portal-live-php .live-intro-list,
        body.page-template-page-portal-live-php .live-form-block,
        body.page-template-page-portal-live-php .live-copy,
        body.page-template-page-portal-live-php .live-copy-muted,
        body.page-template-page-portal-live-php .live-schedule-item,
        body.page-template-page-portal-live-php .live-submit-row,
        body.page-template-page-portal-live-php .live-section-heading {
            padding-left: 20px;
            padding-right: 20px;
        }
    }

    @media (max-width: 480px) {
        body.page-template-page-portal-live-php .live-title {
            font-size: 34px;
        }

        body.page-template-page-portal-live-php .live-intro-list,
        body.page-template-page-portal-live-php .live-form-block,
        body.page-template-page-portal-live-php .live-copy,
        body.page-template-page-portal-live-php .live-copy-muted,
        body.page-template-page-portal-live-php .live-schedule-item,
        body.page-template-page-portal-live-php .live-submit-row,
        body.page-template-page-portal-live-php .live-section-heading {
            padding-left: 16px;
            padding-right: 16px;
        }

        body.page-template-page-portal-live-php .live-url,
        body.page-template-page-portal-live-php .live-input-lg,
        body.page-template-page-portal-live-php .live-input-md {
            width: 100%;
        }

        body.page-template-page-portal-live-php .live-input-row-2 {
            flex-direction: column;
            gap: 8px;
        }

        body.page-template-page-portal-live-php .live-schedule-item {
            max-width: 100%;
        }
    }

    /* ── Breakpoint: screens wider than 1024px ── */
    @media (min-width: 1025px) {

        body {
            background: black;
        }

        .the-gradient {
            background: black;
        }

        .search-bar-container {
            background: black;
            backdrop-filter: none;
        }

        .sticky-header {
            background: black;
            backdrop-filter: none;
        }

        .sticky-nav {
            background: black;
            backdrop-filter: none;
            border-top: 1px solid transparent;
            border-bottom: 1px solid transparent;
            border-image: linear-gradient(90deg, transparent, #B8972F 50%, transparent) 1;
            top: 200px;
        }

        .accordion-gradient-container {
            background: black;
        }

        .custom-accordion .accordion-item {
            background: radial-gradient(50% 50% at 50% 50%, #363847 38%, #1F2027 100%);
            border: 1px solid #b8972f;
            border-radius: 30px;
        }

        .custom-accordion .accordion-button {
            background: transparent;
            font-size: 20px;
            font-weight: 700;
            border-radius: 30px;
        }

        .custom-accordion .accordion-button:not(.collapsed) {
            background: transparent;
            border-radius: 30px 30px 0 0;
        }

        .custom-accordion .accordion-body {
            background: transparent;
            font-size: 17px;
        }

        .custom-accordion .accordion-button::before {
            display: none;
        }

        .custom-accordion .accordion-button {
            padding-left: 20px;
        }

        .dealer-gradient-overlay {
            display: none;
        }

        .logo-container img {
            max-height: 60px;
        }

        /* Concierge text — flanking the dealer image */
        .concierge-text-mobile {
            display: none;
        }

        .dealer-row {
            display: flex;
            align-items: center;
            justify-content: center;
            max-width: 1024px;
            margin: 255px auto 0;
            padding-left: 20px;
            padding-right: 20px;
        }

        .dealer-row .dealer-image-container {
            flex: 0 0 50%;
            max-width: 50%;
            margin-top: 0;
        }

        .dealer-image-wrap {
            padding-bottom: 10px;
        }

        .dealer-image-container {
            margin-top: 0;
        }

        body.page-template-page-portal-home-php .concierge-title {
            position: static;
            transform: none;
            left: auto;
            bottom: auto;
            display: block;
            text-align: center;
            margin-top: 12px;
        }

        .accordion-next-btn {
            display: block;
            margin: 20px auto 4px;
            background: none;
            border: none;
            color: white;
            font-size: 16px;
            font-weight: 600;
            text-transform: uppercase;
            text-decoration: underline;
            cursor: pointer;
            padding: 0;
        }

        .accordion-next-btn:hover {
            color: #b8972f;
        }

    }

    /* Sailboat wrap labels */
    .sailboat-label {
        position: absolute;
        left: 0;
        right: 0;
        text-align: center;
        font-family: 'Be Vietnam Pro', sans-serif;
        font-size: 2rem;
        font-weight: 400;
        color: #fff;
        letter-spacing: .08em;
        text-shadow: 0 2px 8px rgba(0,0,0,.8);
        z-index: 1;
    }

    .sailboat-label--top    { top: 14px; }
    .sailboat-label--bottom { bottom: 14px; }

    @media (max-width: 768px) {
        .sailboat-label { font-size: 1.5rem; }
        .sailboat-label--top    { top: 5px; }
        .sailboat-label--bottom { bottom: 5px; }
    }
</style>

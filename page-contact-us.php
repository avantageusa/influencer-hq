<?php
/**
 * Template Name: Contact Us
 * Description: Contact us page for InfluencerHQ
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package Avantage_Baccarat
 */

$ihq_contact_nonce = wp_create_nonce( 'ihq_contact_form_nonce' );
$ihq_contact_turnstile = '';
if ( function_exists( 'ihq_turnstile_is_configured' ) && ihq_turnstile_is_configured() && defined( 'CF_TURNSTILE_SITE_KEY' ) ) {
	$ihq_contact_turnstile = CF_TURNSTILE_SITE_KEY;
}

get_header();
?>

<?php if ( $ihq_contact_turnstile !== '' ) : ?>
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit" async defer></script>
<?php endif; ?>

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
                                Please use the contact form below and our team will get back to you within 24 hours.
                            </p>
                        </div>

                        <div class="contact-form-success" id="contact-form-success" role="status" hidden></div>

                        <form id="ihq-contact-form" class="ihq-contact-form" novalidate enctype="multipart/form-data">
                            <div class="contact-form-row contact-form-row--half">
                                <div class="contact-form-field">
                                    <label for="contact-first-name">First name <span class="contact-required">*</span></label>
                                    <input type="text" id="contact-first-name" name="first_name" autocomplete="given-name" maxlength="120">
                                    <span class="contact-field-error" id="contact-err-first_name" role="alert"></span>
                                </div>
                                <div class="contact-form-field">
                                    <label for="contact-last-name">Last name <span class="contact-required">*</span></label>
                                    <input type="text" id="contact-last-name" name="last_name" autocomplete="family-name" maxlength="120">
                                    <span class="contact-field-error" id="contact-err-last_name" role="alert"></span>
                                </div>
                            </div>

                            <div class="contact-form-field">
                                <label for="contact-email">Email <span class="contact-required">*</span></label>
                                <input type="email" id="contact-email" name="email" autocomplete="email" maxlength="254">
                                <span class="contact-field-error" id="contact-err-email" role="alert"></span>
                            </div>

                            <div class="contact-form-field">
                                <label for="contact-subject">Subject <span class="contact-required">*</span></label>
                                <select id="contact-subject" name="subject">
                                    <option value="">Select a subject</option>
                                    <option value="technical-issues">Technical issues</option>
                                    <option value="marketing">Marketing</option>
                                    <option value="collaboration">Collaboration</option>
                                </select>
                                <span class="contact-field-error" id="contact-err-subject" role="alert"></span>
                            </div>

                            <div class="contact-form-field">
                                <label for="contact-message">Message <span class="contact-required">*</span></label>
                                <textarea id="contact-message" name="message" rows="5" maxlength="5000"></textarea>
                                <span class="contact-field-error" id="contact-err-message" role="alert"></span>
                            </div>

                            <div class="contact-form-field">
                                <label for="contact-attachment">File upload <span class="contact-optional">(optional)</span></label>
                                <div class="contact-file-wrap">
                                    <input type="file" id="contact-attachment" name="attachment" accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.txt,.doc,.docx,image/*,application/pdf">
                                    <span class="contact-file-name" id="contact-file-name">No file chosen</span>
                                </div>
                                <span class="contact-field-error" id="contact-err-attachment" role="alert"></span>
                            </div>

                            <?php if ( $ihq_contact_turnstile !== '' ) : ?>
                            <div id="contact-turnstile" class="contact-turnstile" data-sitekey="<?php echo esc_attr( $ihq_contact_turnstile ); ?>"></div>
                            <?php endif; ?>

                            <p class="contact-form-global-error" id="contact-form-global-error" role="alert"></p>

                            <button type="submit" class="contact-submit-btn" id="contact-submit-btn">Submit</button>
                        </form>

                        <p class="response-time mb-0">
                            Our standard response time is 24 hours. Thank you for reaching out to InfluencerHQ!
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main><!-- #main -->

<style>
    body, h1, h2, h3, h4, h5, h6, p, a, label {
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
        background-color: #000000;
        padding: 40px 0 60px;
    }

    .contact-content {
        background: rgba(0, 0, 0, 0.8);
        border-radius: 10px;
        border: 1px solid rgba(184, 151, 47, 0.45);
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    }

    h1 {
        font-size: clamp(2rem, 4vw, 2.75rem) !important;
        font-weight: bold;
        line-height: 1.2;
        margin-bottom: 24px;
        color: #f0c93a;
        letter-spacing: 0.04em;
    }

    .contact-text > p {
        font-size: clamp(1rem, 2vw, 1.1rem) !important;
        line-height: 1.7;
        margin-bottom: 28px;
        color: rgba(255, 255, 252, 0.92);
    }

    .ihq-contact-form {
        margin-bottom: 28px;
    }

    .contact-form-row {
        display: flex;
        flex-direction: column;
        gap: 0;
        margin-bottom: 0;
    }

    .contact-form-row--half {
        gap: 0;
    }

    @media (min-width: 600px) {
        .contact-form-row--half {
            flex-direction: row;
            gap: 16px;
        }
        .contact-form-row--half .contact-form-field {
            flex: 1;
        }
    }

    .contact-form-field {
        margin-bottom: 18px;
    }

    .contact-form-field label {
        display: block;
        font-size: 0.95rem;
        font-weight: 600;
        margin-bottom: 8px;
        color: #ead9b0;
    }

    .contact-required {
        color: #f0c93a;
    }

    .contact-optional {
        font-weight: 400;
        color: rgba(255, 255, 252, 0.55);
        font-size: 0.85rem;
    }

    .ihq-contact-form input[type="text"],
    .ihq-contact-form input[type="email"],
    .ihq-contact-form select,
    .ihq-contact-form textarea {
        width: 100%;
        box-sizing: border-box;
        padding: 12px 14px;
        font-size: 1rem;
        color: #fff;
        background: rgba(0, 0, 0, 0.55);
        border: 1px solid rgba(184, 151, 47, 0.55);
        border-radius: 8px;
        outline: none;
    }

    .ihq-contact-form input:focus,
    .ihq-contact-form select:focus,
    .ihq-contact-form textarea:focus {
        border-color: #f0c93a;
        box-shadow: 0 0 0 1px rgba(240, 201, 58, 0.25);
    }

    .ihq-contact-form input.contact-input--error,
    .ihq-contact-form select.contact-input--error,
    .ihq-contact-form textarea.contact-input--error {
        border-color: #e53935;
    }

    .ihq-contact-form textarea {
        resize: vertical;
        min-height: 120px;
    }

    .ihq-contact-form select {
        cursor: pointer;
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath fill='%23f0c93a' d='M6 8L0 0h12z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 14px center;
        padding-right: 40px;
    }

    .contact-file-wrap {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 12px;
        padding: 10px 14px;
        border: 1px dashed rgba(255, 255, 255, 0.35);
        border-radius: 8px;
        background: rgba(0, 0, 0, 0.35);
    }

    .contact-file-wrap input[type="file"] {
        flex: 1;
        min-width: 160px;
        font-size: 0.9rem;
        color: #ccc;
    }

    .contact-file-name {
        font-size: 0.9rem;
        color: #53cea4;
        word-break: break-all;
    }

    .contact-field-error {
        display: block;
        min-height: 1.25em;
        margin-top: 6px;
        font-size: 0.85rem;
        color: #ff6b6b;
    }

    .contact-form-global-error {
        min-height: 1.25em;
        margin: 0 0 12px;
        font-size: 0.9rem;
        color: #ff6b6b;
    }

    .contact-form-success {
        margin-bottom: 20px;
        padding: 14px 16px;
        border-radius: 8px;
        background: rgba(83, 206, 164, 0.12);
        border: 1px solid rgba(83, 206, 164, 0.45);
        color: #7ee0bc;
        font-size: 1rem;
        line-height: 1.5;
    }

    .contact-form-success[hidden] {
        display: none !important;
    }

    .contact-turnstile {
        display: flex;
        justify-content: center;
        margin: 8px 0 16px;
    }

    .contact-submit-btn {
        display: block;
        width: 100%;
        padding: 14px 24px;
        font-size: 1.05rem;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #0a0a0a;
        background: linear-gradient(135deg, #f0c93a 0%, #d4a82a 100%);
        border: none;
        border-radius: 10px;
        cursor: pointer;
        transition: opacity 0.2s, transform 0.15s;
    }

    .contact-submit-btn:hover:not(:disabled) {
        opacity: 0.92;
        transform: translateY(-1px);
    }

    .contact-submit-btn:disabled {
        opacity: 0.55;
        cursor: not-allowed;
    }

    .response-time {
        margin-top: 8px;
        color: rgba(255, 255, 252, 0.75);
        font-size: 0.95rem;
        text-align: center;
    }

    @media (max-width: 767px) {
        .contact-content {
            margin: 0 15px;
        }
    }
</style>

<script>
(function () {
    var form = document.getElementById('ihq-contact-form');
    if (!form) {
        return;
    }

    var ajaxUrl = <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;
    var nonce = <?php echo wp_json_encode( $ihq_contact_nonce ); ?>;
    var turnstileSiteKey = <?php echo wp_json_encode( $ihq_contact_turnstile ); ?>;
    var turnstileWidgetId = null;

    var REQUIRED_MSG = <?php echo wp_json_encode( __( 'This field is required', 'avantage-baccarat' ) ); ?>;
    var EMAIL_INVALID_MSG = <?php echo wp_json_encode( __( 'Please enter a valid email address', 'avantage-baccarat' ) ); ?>;

    var fields = {
        first_name: document.getElementById('contact-first-name'),
        last_name: document.getElementById('contact-last-name'),
        email: document.getElementById('contact-email'),
        subject: document.getElementById('contact-subject'),
        message: document.getElementById('contact-message'),
        attachment: document.getElementById('contact-attachment')
    };

    var errEls = {
        first_name: document.getElementById('contact-err-first_name'),
        last_name: document.getElementById('contact-err-last_name'),
        email: document.getElementById('contact-err-email'),
        subject: document.getElementById('contact-err-subject'),
        message: document.getElementById('contact-err-message'),
        attachment: document.getElementById('contact-err-attachment')
    };

    var globalErr = document.getElementById('contact-form-global-error');
    var successBox = document.getElementById('contact-form-success');
    var submitBtn = document.getElementById('contact-submit-btn');
    var fileNameEl = document.getElementById('contact-file-name');

    var ALLOWED_SUBJECTS = ['technical-issues', 'marketing', 'collaboration'];
    var MAX_FILE_BYTES = 5242880;

    function clearErrors() {
        Object.keys(errEls).forEach(function (key) {
            if (errEls[key]) {
                errEls[key].textContent = '';
            }
            if (fields[key] && fields[key].classList) {
                fields[key].classList.remove('contact-input--error');
            }
        });
        if (globalErr) {
            globalErr.textContent = '';
        }
    }

    function setFieldError(key, msg) {
        if (errEls[key]) {
            errEls[key].textContent = msg;
        }
        if (fields[key] && fields[key].classList) {
            fields[key].classList.add('contact-input--error');
        }
    }

    function isValidEmail(value) {
        if (!value) {
            return false;
        }
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
    }

    function validateClient() {
        clearErrors();
        var valid = true;
        var first = fields.first_name ? fields.first_name.value.trim() : '';
        var last = fields.last_name ? fields.last_name.value.trim() : '';
        var email = fields.email ? fields.email.value.trim() : '';
        var subject = fields.subject ? fields.subject.value : '';
        var message = fields.message ? fields.message.value.trim() : '';

        if (!first) {
            setFieldError('first_name', REQUIRED_MSG);
            valid = false;
        }
        if (!last) {
            setFieldError('last_name', REQUIRED_MSG);
            valid = false;
        }
        if (!email) {
            setFieldError('email', REQUIRED_MSG);
            valid = false;
        } else if (!isValidEmail(email)) {
            setFieldError('email', EMAIL_INVALID_MSG);
            valid = false;
        }
        if (!subject || ALLOWED_SUBJECTS.indexOf(subject) === -1) {
            setFieldError('subject', REQUIRED_MSG);
            valid = false;
        }
        if (!message) {
            setFieldError('message', REQUIRED_MSG);
            valid = false;
        }

        if (fields.attachment && fields.attachment.files && fields.attachment.files[0]) {
            var file = fields.attachment.files[0];
            if (file.size > MAX_FILE_BYTES) {
                setFieldError('attachment', 'File must be smaller than 5 MB.');
                valid = false;
            }
        }

        return valid;
    }

    function renderTurnstile() {
        if (!turnstileSiteKey) {
            return;
        }
        var el = document.getElementById('contact-turnstile');
        if (!el || el.getAttribute('data-rendered') === '1') {
            return;
        }
        if (typeof window.turnstile === 'undefined') {
            window.setTimeout(renderTurnstile, 200);
            return;
        }
        turnstileWidgetId = window.turnstile.render(el, { sitekey: turnstileSiteKey });
        el.setAttribute('data-rendered', '1');
    }

    function getTurnstileToken() {
        if (!turnstileSiteKey || turnstileWidgetId === null || typeof window.turnstile === 'undefined') {
            return '';
        }
        return window.turnstile.getResponse(turnstileWidgetId) || '';
    }

    function resetTurnstile() {
        if (turnstileWidgetId !== null && typeof window.turnstile !== 'undefined') {
            try {
                window.turnstile.reset(turnstileWidgetId);
            } catch (eReset) {}
        }
    }

    if (fields.attachment) {
        fields.attachment.addEventListener('change', function () {
            if (errEls.attachment) {
                errEls.attachment.textContent = '';
            }
            fields.attachment.classList.remove('contact-input--error');
            if (fields.attachment.files && fields.attachment.files[0]) {
                fileNameEl.textContent = fields.attachment.files[0].name;
            } else {
                fileNameEl.textContent = 'No file chosen';
            }
        });
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        clearErrors();
        if (successBox) {
            successBox.hidden = true;
        }

        if (!validateClient()) {
            return;
        }

        if (turnstileSiteKey && !getTurnstileToken()) {
            if (globalErr) {
                globalErr.textContent = 'Please complete the security verification.';
            }
            return;
        }

        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Sending…';
        }

        var fd = new FormData(form);
        fd.append('action', 'ihq_submit_contact_form');
        fd.append('nonce', nonce);
        fd.append('cf-turnstile-response', getTurnstileToken());

        fetch(ajaxUrl, { method: 'POST', body: fd })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Submit';
                }

                if (!data.success) {
                    var payload = data.data || {};
                    if (payload.field_errors && typeof payload.field_errors === 'object') {
                        Object.keys(payload.field_errors).forEach(function (key) {
                            setFieldError(key, payload.field_errors[key]);
                        });
                    }
                    if (globalErr) {
                        globalErr.textContent = payload.message || 'Something went wrong. Please try again.';
                    }
                    resetTurnstile();
                    return;
                }

                form.reset();
                if (fileNameEl) {
                    fileNameEl.textContent = 'No file chosen';
                }
                if (successBox) {
                    successBox.textContent = (data.data && data.data.message) ? data.data.message : 'Thank you! Your message has been sent.';
                    successBox.hidden = false;
                    successBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }
                resetTurnstile();
            })
            .catch(function () {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Submit';
                }
                if (globalErr) {
                    globalErr.textContent = 'Network error. Please try again.';
                }
                resetTurnstile();
            });
    });

    window.setTimeout(renderTurnstile, 300);
})();
</script>

<?php
get_footer();

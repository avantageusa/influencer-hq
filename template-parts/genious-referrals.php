<?php
/**
 * Genious referrals tab
 */

// Get current logged-in user
$current_user = wp_get_current_user();
$user_id = $current_user->ID;
$advocate_token = '';
$referral_link = '';
$share_links = array();

// Get the token from user meta
$stored_token = get_user_meta($user_id, 'genius_token', true);

if ($stored_token) {
    $advocate_token = $stored_token;
    
    // Get share links from Genius Referrals API
    $share_links_url = 'https://api.geniusreferrals.com/accounts/dev_qc/advocates/' . $advocate_token . '/share-links';
    $share_links_args = array(
        'headers' => array(
            'X-Auth-Token' => '1a5b59cf8307b1b1f0f922aa12d4807c05ebaa10',
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        )
    );
    $share_links_response = wp_remote_get($share_links_url, $share_links_args);
    
    if (!is_wp_error($share_links_response)) {
        $share_links_body = wp_remote_retrieve_body($share_links_response);
        $share_links_data = json_decode($share_links_body, true);
        
        // Extract the personal link from normal-referrals
        if (isset($share_links_data['data']['normal-referrals']['normal-referrals']['personal'])) {
            $referral_link = $share_links_data['data']['normal-referrals']['normal-referrals']['personal'];
            $share_links = $share_links_data['data']['normal-referrals']['normal-referrals'];
        }
    }
}
?>

<div class="genious-referrals" style="max-width: 800px; margin: 0 auto;">
    
    <!-- Referral Link Section -->
    <?php if ($referral_link): ?>
    <div class="mb-5">
        <h3 class="text-18pt fw-bold text-yellow mb-4">Your Referral Link</h3>
        <div class="referral-link-container">
            <input type="text" id="referral-link" value="<?php echo esc_url($referral_link); ?>" readonly class="referral-input">
            <button onclick="copyReferralLink(event)" class="btn-copy">Copy Link</button>
        </div>
        <p class="mt-3 ">Share this link with your friends to earn rewards!</p>
        <p class="mt-2 " style="font-size: 12px;">Your Token: <code style="background: #2a2a2a; padding: 2px 6px; border-radius: 4px; color: #ffc107;"><?php echo esc_html($advocate_token); ?></code></p>
    </div>
    <?php else: ?>
    <div class="mb-5">
        <h3 class="text-18pt fw-bold text-yellow mb-4">Your Referral Link</h3>
        <p class="">No advocate account found. Please contact support.</p>
    </div>
    <?php endif; ?>
    
    <!-- Your Advocate Data -->
    <div class="mb-5">
        <h3 class="text-18pt fw-bold text-yellow mb-4">Your Advocate Data</h3>
        <?php
        if ($advocate_token) {
            // Get full advocate details by token
            $advocate_detail_url = 'https://api.geniusreferrals.com/accounts/dev_qc/advocates/' . $advocate_token;
            $advocate_detail_args = array(
                'headers' => array(
                    'X-Auth-Token' => '1a5b59cf8307b1b1f0f922aa12d4807c05ebaa10',
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                )
            );
            $advocate_detail_response = wp_remote_get($advocate_detail_url, $advocate_detail_args);
            
            if (is_wp_error($advocate_detail_response)) {
                echo '<p>Error: ' . $advocate_detail_response->get_error_message() . '</p>';
            } else {
                $advocate_detail_body = wp_remote_retrieve_body($advocate_detail_response);
                $advocate_detail_data = json_decode($advocate_detail_body, true);
                if ($advocate_detail_data !== null) {
                    $pretty = json_encode($advocate_detail_data, JSON_PRETTY_PRINT);
                    echo '<pre class="json-scroll">' . esc_html($pretty) . '</pre>';
                } else {
                    echo '<pre class="json-scroll">' . esc_html($advocate_detail_body) . '</pre>';
                }
            }
        } else {
            echo '<p class="text-muted">No advocate account found for your email.</p>';
        }
        ?>
    </div>
    
</div>

<script>
function copyReferralLink(event) {
    const linkInput = document.getElementById('referral-link');
    linkInput.select();
    linkInput.setSelectionRange(0, 99999);
    
    try {
        navigator.clipboard.writeText(linkInput.value).then(function() {
            const btn = event.target;
            const originalText = btn.textContent;
            btn.textContent = 'Copied!';
            btn.style.backgroundColor = '#28a745';
            
            setTimeout(() => {
                btn.textContent = originalText;
                btn.style.backgroundColor = '#ffc107';
            }, 2000);
        });
    } catch (err) {
        console.error('Failed to copy: ', err);
    }
}
</script>

<style>
    .referral-link-container {
        display: flex;
        gap: 10px;
        align-items: center;
    }
    
    .referral-input {
        flex: 1;
        padding: 12px;
        border: 2px solid #ffc107;
        border-radius: 8px;
        font-size: 16px;
        background: #2a2a2a;
        color: #ffc107;
    }
    
    .btn-copy {
        padding: 12px 24px;
        background: #ffc107;
        color: #000;
        border: none;
        border-radius: 8px;
        font-weight: bold;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .btn-copy:hover {
        background: #ffca28;
        transform: translateY(-2px);
    }
    
    .text-muted {
        color: #999;
        font-size: 14px;
    }
    
    .mt-3 {
        margin-top: 1rem;
    }
    
    .mt-2 {
        margin-top: 0.5rem;
    }
    
    .json-scroll {
        max-height: 350px;
        overflow: auto;
        background: #222;
        color: #ffc107;
        padding: 15px;
        border-radius: 8px;
        font-size: 14px;
        line-height: 1.5;
    }
    
    .text-18pt {
        font-size: 18pt;
    }
    
    .text-yellow {
        color: #ffc107;
    }
    
    .fw-bold {
        font-weight: bold;
    }
    
    .mb-4 {
        margin-bottom: 1.5rem;
    }
    
    .mb-5 {
        margin-bottom: 3rem;
    }
</style>

<?php
/**
 * Template part for displaying Challenges
 * Shows list of other influencers to challenge
 */

// Get current user data
$current_user = wp_get_current_user();
$user_id = $current_user->ID;

// Get all users with influencer role (excluding current user)
$influencers = get_users(array(
    'role' => 'influencer',
    'exclude' => array($user_id),
    'orderby' => 'display_name',
    'order' => 'ASC'
));
?>

<div class="challenges-section" style="max-width: 1000px; margin: 0 auto;">
    
    <!-- Welcome Message -->
    <div class="text-center mb-5">
        <h2 class="text-22pt fw-bold text-yellow mb-3">Influencer Challenges</h2>
        <p class="fs-5 text-light-gray">Challenge other influencers and compete head-to-head</p>
    </div>
    
    <!-- Influencers List -->
    <div class="row">
        <div class="col-12">
            <div class="profile-card" style="background: rgba(33, 37, 41, 0.25); border: 1px solid rgba(255, 149, 0, 0.2); backdrop-filter: blur(15px); border-radius: 15px; padding: 25px; box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);">
                <h3 class="text-18pt fw-bold text-yellow mb-4">Available Influencers</h3>
                
                <?php if (!empty($influencers)) : ?>
                    <div class="influencers-list">
                        <?php foreach ($influencers as $influencer) : 
                            $first_name = get_user_meta($influencer->ID, 'first_name', true);
                            $last_name = get_user_meta($influencer->ID, 'last_name', true);
                            $display_name = trim($first_name . ' ' . $last_name) ?: $influencer->display_name;
                            $country = get_user_meta($influencer->ID, 'billing_country', true) ?: get_user_meta($influencer->ID, 'country', true);
                        ?>
                            <div class="influencer-item mb-3 p-3" style="background: rgba(33, 37, 41, 0.4); border: 1px solid rgba(255, 149, 0, 0.1); border-radius: 10px; display: flex; align-items: center; justify-content: space-between; transition: all 0.3s ease;">
                                <div class="influencer-info" style="flex: 1;">
                                    <h4 class="text-16pt fw-bold text-light-gray mb-1"><?php echo esc_html($display_name); ?></h4>
                                    <p class="fs-6 text-light-gray mb-0">
                                        <span class="text-yellow">Email:</span> <?php echo esc_html($influencer->user_email); ?>
                                        <?php if ($country) : ?>
                                            <span class="ms-3"><span class="text-yellow">Country:</span> <?php echo esc_html($country); ?></span>
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <div class="influencer-actions d-flex align-items-center gap-2">
                                    <select class="form-select challenge-type-select" id="challenge-type-<?php echo esc_attr($influencer->ID); ?>" style="width: auto; background-color: rgba(33, 37, 41, 0.8); color: #fff; border: 1px solid rgba(255, 149, 0, 0.3);">
                                        <option value="speed">Speed Challenge</option>
                                        <option value="endurance">Endurance Challenge</option>
                                        <option value="accuracy">Accuracy Challenge</option>
                                    </select>
                                    <button class="btn btn-warning challenge-btn" 
                                            onclick="challengeInfluencer(<?php echo esc_attr($influencer->ID); ?>, '<?php echo esc_js($display_name); ?>')"
                                            style="background-color: #ffc107; border-color: #ffc107; color: #000; font-weight: bold; padding: 10px 25px; white-space: nowrap;">
                                        Challenge
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else : ?>
                    <div class="text-center py-5">
                        <p class="fs-5 text-light-gray mb-0">No other influencers available at the moment.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
</div>

<script>
function challengeInfluencer(influencerId, influencerName) {
    // Get the selected challenge type
    const challengeTypeSelect = document.getElementById('challenge-type-' + influencerId);
    const challengeType = challengeTypeSelect.value;
    
    // Disable button during submission
    const button = event.target;
    const originalText = button.textContent;
    button.disabled = true;
    button.textContent = 'Sending...';
    
    // Prepare data for AJAX
    const formData = new FormData();
    formData.append('action', 'submit_challenge');
    formData.append('nonce', '<?php echo wp_create_nonce('challenge_nonce'); ?>');
    formData.append('challenger_id', <?php echo $user_id; ?>);
    formData.append('challenged_id', influencerId);
    formData.append('challenge_type', challengeType);
    
    // Send AJAX request
    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Challenge sent successfully to ' + influencerName + '!\nChallenge Type: ' + challengeType);
            console.log('Challenge data:', data.data);
        } else {
            alert('Error: ' + (data.data.message || 'Failed to send challenge'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while sending the challenge.');
    })
    .finally(() => {
        // Re-enable button
        button.disabled = false;
        button.textContent = originalText;
    });
}
</script>

<style>
    .profile-card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    
    .profile-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 40px rgba(0, 0, 0, 0.4) !important;
    }
    
    .influencer-item {
        transition: all 0.3s ease;
    }
    
    .influencer-item:hover {
        background: rgba(33, 37, 41, 0.6) !important;
        border-color: rgba(255, 149, 0, 0.3) !important;
        transform: translateX(5px);
    }
    
    .challenge-btn:hover {
        transform: scale(1.05);
        box-shadow: 0 4px 12px rgba(255, 193, 7, 0.4);
    }
    
    @media (max-width: 767px) {
        .profile-card {
            margin-bottom: 20px;
        }
        
        .text-18pt {
            font-size: 16pt !important;
        }
        
        .text-22pt {
            font-size: 20pt !important;
        }
        
        .text-16pt {
            font-size: 14pt !important;
        }
        
        .influencer-item {
            flex-direction: column;
            align-items: flex-start !important;
        }
        
        .influencer-actions {
            width: 100%;
            margin-top: 15px;
        }
        
        .challenge-btn {
            width: 100%;
        }
    }
</style>

<?php
/**
 * Template Name: test form
 * Description: A custom template for displaying the home page of the WordPress site.
 * This template is used to render the homepage content and layout.
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package Avantage_Baccarat
 */

get_header();

$portal_leaderboards_iframe_url = 'https://qc-game-portal-client-tf-b2c.dev.ae.games/av-baccarat/external/leaderboards';
?>

    <main id="primary" class="site-main" style="padding: 50px;">
        
        <section class="">
            <div class="container">
                <div class="row">
                    <div class="col-12" style="margin:0 auto;min-height: 80vh;">
                        
                        <h2>Genius Referrals API Test</h2>

                        <div class="portal-leaderboards-iframe-wrap" style="margin-top: 1.25rem; width: 100%; max-width: 100%;">
                            <iframe
                                title="<?php echo esc_attr__( 'Avantage Baccarat leaderboards', 'avantage-baccarat' ); ?>"
                                src="<?php echo esc_url( $portal_leaderboards_iframe_url ); ?>"
                                style="display: block; width: 100%; min-height: 75vh; border: 0; background: #111;"
                                loading="lazy"
                                referrerpolicy="strict-origin-when-cross-origin"
                                allowfullscreen
                            ></iframe>
                        </div>
                        
                        <form method="post" style="margin-bottom: 20px;">
                            <input type="submit" name="list_accounts" value="List Accounts" class="btn btn-secondary">
                            <input type="submit" name="list_advocates" value="List Newest 10 Advocates for dev_qc" class="btn btn-primary" style="margin-left: 10px;">
                            <input type="submit" name="create_advocate" value="Create Advocate" class="btn btn-success" style="margin-left: 10px;">
                        </form>
                        
                        <style>
.api-response-box {
    background: #222;
    color: #ffc107;
    padding: 15px;
    border-radius: 8px;
    max-height: 350px;
    overflow: auto;
    margin-bottom: 20px;
}
.referral-link-box {
    background: #f8f9fa;
    color: #222;
    padding: 10px;
    border-radius: 8px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
}
.referral-link-box input {
    width: 60%;
    padding: 8px;
    margin-right: 10px;
}
</style>

<?php
$referral_link = '';
?>

                        <?php
                        if (isset($_POST['list_accounts'])) {
                            echo '<h3>POST Data:</h3>';
                            echo '<pre class="api-response-box">' . print_r($_POST, true) . '</pre>';
                            
                            $url = 'https://api.geniusreferrals.com/accounts';
                            $args = array(
                                'headers' => array(
                                    'X-Auth-Token' => '1a5b59cf8307b1b1f0f922aa12d4807c05ebaa10',
                                    'Content-Type' => 'application/json',
                                    'Accept' => 'application/json'
                                )
                            );
                            $response = wp_remote_get($url, $args);
                            if (is_wp_error($response)) {
                                echo '<p>Error: ' . $response->get_error_message() . '</p>';
                            } else {
                                $body = wp_remote_retrieve_body($response);
                                echo '<h3>API Response:</h3>';
                                $data = json_decode($body, true);
                                if ($data !== null) {
                                    $pretty = json_encode($data, JSON_PRETTY_PRINT);
                                    echo '<pre class="api-response-box">' . esc_html($pretty) . '</pre>';
                                } else {
                                    echo '<pre class="api-response-box">' . esc_html($body) . '</pre>';
                                }
                            }
                        }
                        
                        if (isset($_POST['list_advocates'])) {
                            echo '<h3>POST Data:</h3>';
                            echo '<pre class="api-response-box">' . print_r($_POST, true) . '</pre>';
                            
                            $url = 'https://api.geniusreferrals.com/accounts/dev_qc/advocates?limit=10&sort=-created';
                            $args = array(
                                'headers' => array(
                                    'X-Auth-Token' => '1a5b59cf8307b1b1f0f922aa12d4807c05ebaa10',
                                    'Content-Type' => 'application/json',
                                    'Accept' => 'application/json'
                                )
                            );
                            $response = wp_remote_get($url, $args);
                            if (is_wp_error($response)) {
                                echo '<p>Error: ' . $response->get_error_message() . '</p>';
                            } else {
                                $body = wp_remote_retrieve_body($response);
                                echo '<h3>API Response:</h3>';
                                $data = json_decode($body, true);
                                if ($data !== null) {
                                    $pretty = json_encode($data, JSON_PRETTY_PRINT);
                                    echo '<pre class="api-response-box">' . esc_html($pretty) . '</pre>';
                                } else {
                                    echo '<pre class="api-response-box">' . esc_html($body) . '</pre>';
                                }
                            }
                        }
                        
                        if (isset($_POST['create_advocate'])) {
                            echo '<h3>POST Data:</h3>';
                            echo '<pre class="api-response-box">' . print_r($_POST, true) . '</pre>';
                            
                            $url = 'https://api.geniusreferrals.com/accounts/dev_qc/advocates';
                            $advocate_data = array(
                                'advocate' => array(
                                    'name' => 'wordpress',
                                    'lastname' => 'advocate',
                                    'email' => 'debela.koala@gmail.com',
                                    'payout_threshold' => 20,
                                    'currency_code' => 'USD',
                                    'can_refer' => 1,
                                    'status' => 'active'
                                )
                            );
                            $args = array(
                                'method' => 'POST',
                                'headers' => array(
                                    'X-Auth-Token' => '1a5b59cf8307b1b1f0f922aa12d4807c05ebaa10',
                                    'Content-Type' => 'application/json',
                                    'Accept' => 'application/json'
                                ),
                                'body' => json_encode($advocate_data)
                            );
                            $response = wp_remote_post($url, $args);
                            if (is_wp_error($response)) {
                                echo '<p>Error: ' . $response->get_error_message() . '</p>';
                            } else {
                                $body = wp_remote_retrieve_body($response);
                                $data = json_decode($body, true);
                                echo '<h3>API Response:</h3>';
                                if ($data !== null) {
                                    $pretty = json_encode($data, JSON_PRETTY_PRINT);
                                    echo '<pre class="api-response-box">' . esc_html($pretty) . '</pre>';
                                    if (isset($data['data']['token'])) {
                                        $referral_link = 'https://bet5games.geniusreferrals.com/advocate/' . esc_attr($data['data']['token']);
                                        echo '<div class="referral-link-box"><strong>Your Referral Link:</strong> <input type="text" value="' . esc_url($referral_link) . '" readonly><button onclick="navigator.clipboard.writeText(\'' . esc_url($referral_link) . '\')" class="btn btn-success ms-2">Copy</button></div>';
                                    }
                                } else {
                                    echo '<pre class="api-response-box">' . esc_html($body) . '</pre>';
                                }
                            }
                        }
                        ?>

                    </div>
                </div>
            </div>
        </section>
    </main>
<?php
get_footer();

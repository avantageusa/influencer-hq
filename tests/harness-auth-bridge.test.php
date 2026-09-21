<?php
// Standalone boundary tests, no WordPress database or live requests.
define('ABSPATH', '/');
define('IHQ_HARNESS_STAGE', 'qc');
define('IHQ_HARNESS_ORIGIN', 'https://harness.example');
define('IHQ_HARNESS_GATEWAY_URL', 'https://gateway.example/qc');
function add_action(...$args) {}
function wp_parse_url($url) { return parse_url($url); }
function wp_json_encode($value) { return json_encode($value); }
function ihq_oauth_start_session_request_headers() { return array('x-api-key'=>'server-only'); }
function ihq_normalize_country_iso_alpha2($value) { return $value; }
function get_user_meta(...$args) { return 'NL'; }
function wp_remote_post($url, $options) { $GLOBALS['request'] = array($url, $options); return $GLOBALS['response']; }
function is_wp_error($response) { return $response === false; }
function wp_remote_retrieve_response_code($response) { return $response['status']; }
function wp_remote_retrieve_body($response) { return $response['body']; }
function ihq_extract_sso_code_from_start_session_data($data) { return is_array($data) ? ($data['ssoCode'] ?? '') : ''; }
require __DIR__ . '/../inc/harness-auth-bridge.php';
function check($actual, $expected) { if ($actual !== $expected) { throw new Exception('Assertion failed'); } }
check(ihq_harness_config(), array('origin'=>'https://harness.example','gateway'=>'https://gateway.example/qc'));
$state = '12345678-1234-1234-1234-123456789abc';
$value = $state . '.1300';
$cookie = $value . '.' . hash_hmac('sha256', $value, 'secret');
check(ihq_harness_read_request($cookie, 1000, 'secret'), $state);
foreach (array('', false, array(), $cookie.'x', 'bad.1300.x', $value.'.forged') as $bad) { check(ihq_harness_read_request($bad, 1000, 'secret'), false); }
check(ihq_harness_read_request($cookie, 1300, 'secret'), false);
check(ihq_harness_read_request($cookie, 999, 'secret'), false);
check(ihq_harness_read_request($cookie, 1000, 'wrong'), false);
$user = (object)array('ID'=>42,'first_name'=>'QC','last_name'=>'Tester','user_email'=>'qc@example.test');
$GLOBALS['response'] = array('status'=>200,'body'=>'{"success":true,"data":{"ssoCode":"code","IdToken":"never-returned"}}');
check(ihq_harness_issue_code(ihq_harness_config(), $user), 'code');
$GLOBALS['response']['status'] = 201;
check(ihq_harness_issue_code(ihq_harness_config(), $user), 'code');
check($GLOBALS['request'][0], 'https://gateway.example/qc/account/oauth/start-session');
check(json_decode($GLOBALS['request'][1]['body'],true)['payload']['id'], 'wpu-42');
check($GLOBALS['request'][1]['redirection'], 0);
check($GLOBALS['request'][1]['sslverify'], true);
foreach (array(false, array('status'=>302,'body'=>'{"success":true,"data":{"ssoCode":"code"}}'), array('status'=>199,'body'=>'{"success":true,"data":{"ssoCode":"code"}}'), array('status'=>302,'body'=>'{}'), array('status'=>200,'body'=>'{"success":false}'), array('status'=>200,'body'=>'invalid'), array('status'=>200,'body'=>'{"success":true}')) as $bad) { $GLOBALS['response']=$bad; check(ihq_harness_issue_code(ihq_harness_config(),$user), ''); }
echo "Portal bridge boundary tests passed\n";

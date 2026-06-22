<?php
/**
 * Debug panel: preview cookie intent + TEST REGISTRY (Braze + magic link).
 *
 * @package influencer-hq
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="sett-card ihq-visitor-intent-debug" style="margin-bottom:18px;border:2px dashed rgba(184,151,47,.55);padding:16px;">
	<p style="margin:0 0 10px;font-size:13px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;color:#b8972f;">
		<?php esc_html_e( 'Visitor intent (debug)', 'influencer-hq' ); ?>
	</p>
	<p style="margin:0 0 12px;font-size:13px;line-height:1.5;color:#ccc;">
		<?php esc_html_e( 'Cookie name:', 'influencer-hq' ); ?>
		<code><?php echo esc_html( ihq_visitor_intent_cookie_name() ); ?></code>
		<?php esc_html_e( '(30 days). Click TEST REGISTRY to send to Braze and generate a magic registration link.', 'influencer-hq' ); ?>
	</p>
	<button type="button" class="auth-submit-btn" id="ihq-test-registry-btn" style="margin-bottom:14px;">
		<?php esc_html_e( 'TEST REGISTRY', 'influencer-hq' ); ?>
	</button>
	<p style="margin:0 0 8px;font-size:12px;font-weight:600;color:#b8972f;">
		<?php esc_html_e( 'What will be sent to Braze:', 'influencer-hq' ); ?>
	</p>
	<pre id="ihq-visitor-intent-braze-preview" style="margin:0;padding:12px;max-height:420px;overflow:auto;font-size:11px;line-height:1.45;color:#ddd;background:rgba(0,0,0,.35);border-radius:6px;white-space:pre-wrap;word-break:break-word;">{}</pre>
</div>

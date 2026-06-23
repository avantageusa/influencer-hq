<?php
/**
 * Conversation modal markup (shared with page-lander).
 *
 * Expects variables from ihq_conversation_modal_view_data().
 *
 * @package influencer-hq
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="overlay" id="mainModal">
  <div class="modal">
    <button type="button" class="modal-x" onclick="closeModal()">✕</button>
    <div class="mstep on" id="ms1">
      <div class="modal-comm-thanks" id="modal-comm-thanks" hidden>
        <p class="modal-comm-thanks-msg"><?php esc_html_e( 'Thank you for your submission, your message was received', 'influencer-hq' ); ?></p>
      </div>
      <div id="modal-comm-form-body">
      <h3 class="m-title m-title--conversation"><?php esc_html_e( "Let's Start The Conversation", 'influencer-hq' ); ?></h3>
      <p class="m-lede"><?php esc_html_e( 'Please give us your favorite methods of communication.', 'influencer-hq' ); ?></p>
      <div class="modal-comm-pick" id="modal-comm-pick">
        <div class="modal-comm-cols" role="group" aria-label="<?php esc_attr_e( 'Favorite methods of communication', 'influencer-hq' ); ?>">
          <div class="modal-comm-col">
            <?php foreach ( $ihq_modal_comm_methods_left as $ihq_comm ) : ?>
            <label class="modal-comm-option" id="modal-comm-row-<?php echo esc_attr( $ihq_comm['key'] ); ?>" for="modal-comm-<?php echo esc_attr( $ihq_comm['key'] ); ?>">
              <input
                type="checkbox"
                id="modal-comm-<?php echo esc_attr( $ihq_comm['key'] ); ?>"
                data-comm-key="<?php echo esc_attr( $ihq_comm['key'] ); ?>"
                autocomplete="off"
                aria-label="<?php echo esc_attr( $ihq_comm['label'] ); ?>"
              >
              <span class="modal-comm-option-name"><?php echo esc_html( $ihq_comm['label'] ); ?></span>
            </label>
            <?php endforeach; ?>
          </div>
          <div class="modal-comm-col">
            <?php foreach ( $ihq_modal_comm_methods_right as $ihq_comm ) : ?>
            <label class="modal-comm-option" id="modal-comm-row-<?php echo esc_attr( $ihq_comm['key'] ); ?>" for="modal-comm-<?php echo esc_attr( $ihq_comm['key'] ); ?>">
              <input
                type="checkbox"
                id="modal-comm-<?php echo esc_attr( $ihq_comm['key'] ); ?>"
                data-comm-key="<?php echo esc_attr( $ihq_comm['key'] ); ?>"
                autocomplete="off"
                aria-label="<?php echo esc_attr( $ihq_comm['label'] ); ?>"
              >
              <span class="modal-comm-option-name"><?php echo esc_html( $ihq_comm['label'] ); ?></span>
            </label>
            <?php endforeach; ?>
          </div>
        </div>
        <p class="modal-comm-err" id="modal-comm-err" role="alert"></p>
      </div>
      <p class="modal-comm-tg-err" id="modal-comm-tg-err"></p>
      <div class="modal-comm-inputs" id="modal-comm-inputs-panel">
        <?php foreach ( $ihq_modal_comm_methods_all as $ihq_comm ) : ?>
        <div class="modal-comm-input-row" id="modal-comm-entry-<?php echo esc_attr( $ihq_comm['key'] ); ?>" hidden>
          <span class="modal-comm-input-label"><?php echo esc_html( strtoupper( $ihq_comm['label'] ) ); ?></span>
          <input
            class="modal-comm-handle-input"
            type="text"
            id="modal-comm-input-<?php echo esc_attr( $ihq_comm['key'] ); ?>"
            data-comm-key="<?php echo esc_attr( $ihq_comm['key'] ); ?>"
            placeholder="<?php echo esc_attr( $ihq_modal_comm_placeholder ); ?>"
            aria-label="<?php echo esc_attr( $ihq_comm['label'] ); ?>"
          >
        </div>
        <?php endforeach; ?>
      </div>

      <div class="modal-social-section modal-comm-deferred">
        <h4 class="modal-social-heading"><?php esc_html_e( 'Social Media You Post On', 'influencer-hq' ); ?></h4>
        <div class="social-grid" role="group" aria-label="<?php esc_attr_e( 'Social media platforms', 'influencer-hq' ); ?>">
          <?php foreach ( $ihq_modal_social_platforms as $ihq_social ) : ?>
          <button
            type="button"
            class="social-grid-item"
            id="social-grid-<?php echo esc_attr( $ihq_social['key'] ); ?>"
            data-social-key="<?php echo esc_attr( $ihq_social['key'] ); ?>"
            aria-pressed="false"
            onclick="ihqToggleSocialPlatform('<?php echo esc_js( $ihq_social['key'] ); ?>')"
          ><?php echo esc_html( $ihq_social['label'] ); ?></button>
          <?php endforeach; ?>
        </div>
        <div class="social-inputs" id="social-inputs-panel">
          <?php foreach ( $ihq_modal_social_platforms as $ihq_social ) : ?>
          <div class="social-input-row" id="social-entry-<?php echo esc_attr( $ihq_social['key'] ); ?>" hidden>
            <span class="social-input-label"><?php echo esc_html( $ihq_social['label'] ); ?></span>
            <input
              class="social-handle-input"
              type="text"
              id="social-input-<?php echo esc_attr( $ihq_social['key'] ); ?>"
              data-social-key="<?php echo esc_attr( $ihq_social['key'] ); ?>"
              placeholder="<?php echo esc_attr( $ihq_modal_social_placeholder ); ?>"
              aria-label="<?php echo esc_attr( $ihq_social['label'] ); ?>"
            >
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="modal-lower-panel modal-comm-deferred">
        <span class="m-eye"><?php esc_html_e( 'One More Thing', 'influencer-hq' ); ?></span>
        <h3 class="m-title"><?php esc_html_e( 'Glory is earned one weekend at a time.', 'influencer-hq' ); ?></h3>
        <p class="m-ctx"><?php esc_html_e( "Yours starts now. Choose your competition and we'll send you and your followers everything you need to get started.", 'influencer-hq' ); ?></p>
        <div class="ms2-quote"><?php esc_html_e( 'When your followers watch you play, cheer, and celebrate, they become part of a shared experience that unites people the same way great sports, music, and global competitions always have.', 'influencer-hq' ); ?></div>
        <div class="comp-list">
          <div class="comp-card" id="cw" onclick="pickComp('cw')"><div class="comp-chk"><span class="chk-in">✓</span></div><div class="comp-body"><span class="comp-tag"><?php esc_html_e( 'Global Stage', 'influencer-hq' ); ?></span><div class="comp-title"><?php esc_html_e( 'Influencer World Competition', 'influencer-hq' ); ?></div><div class="comp-desc"><?php esc_html_e( 'Join thousands of Influencers and their followers. Compete Thursday night through Sunday night.', 'influencer-hq' ); ?></div></div></div>
          <div class="comp-card" id="cp" onclick="pickComp('cp')"><div class="comp-chk"><span class="chk-in">✓</span></div><div class="comp-body"><span class="comp-tag"><?php esc_html_e( 'Community Competition', 'influencer-hq' ); ?></span><div class="comp-title"><?php esc_html_e( 'Community Competition', 'influencer-hq' ); ?></div><div class="comp-desc"><?php esc_html_e( 'A one week competition between your followers who choose to participate with you at the helm cheering them all on to victory.', 'influencer-hq' ); ?></div></div></div>
        </div>
      </div>

      <button type="button" class="send-btn" id="sendbtn" onclick="onModalSubmit()"><?php esc_html_e( 'Continue', 'influencer-hq' ); ?></button>

      <p class="m-note"><?php esc_html_e( 'All conversations are private and confidential.', 'influencer-hq' ); ?><br><?php esc_html_e( 'We operate across time zones. Expect a reply within minutes.', 'influencer-hq' ); ?></p>
      </div>
    </div>
  </div>
</div>

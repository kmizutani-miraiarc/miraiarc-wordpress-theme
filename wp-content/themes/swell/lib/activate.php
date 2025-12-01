<?php
namespace SWELL_Theme\Activate;

if ( ! defined( 'ABSPATH' ) ) exit;

add_action( 'current_screen', function() {
	global $hook_suffix;

	// アクティベートページのみの処理
	if ( false !== strpos( $hook_suffix, 'swell_settings_swellers' ) ) {

		// POSTチェック
		if ( isset( $_POST['swlr_activate_submit'] ) ) {

			$submit_type = $_POST['swlr_activate_submit'];
			$email       = sanitize_email( $_POST['sweller_email'] ?? '' );

			// nonceチェック
			if ( ! \SWELL_Theme::check_nonce( '_activate' ) ) {
				wp_die( esc_html__( '不正アクセスです。', 'swell' ) );
			}

			// 先にキャッシュ削除
			\SWELL_Theme\License::delete_status_cache();

			if ( 'delete' === $submit_type ) {
				// アドレス情報DBから削除
				update_option( 'sweller_email', '' );
				\SWELL_Theme\License::delete_swlr( $email );

			} elseif ( 'check' === $submit_type ) {

				// アドレス情報DBへ保存
				update_option( 'sweller_email', $email );
				\SWELL_Theme\License::check_swlr( $email, 'form' );
			}
		}

		// "自分のサイトに戻る"で返ってくる時のURL。キャッシュ削除してリダイレクト。（ auto が走って ok が再セットされるはず）
		// phpcs:ignore
		if ( isset( $_GET['cache'] ) && 'delete' === wp_unslash( $_GET['cache'] ) ) {
			\SWELL_Theme\License::delete_status_cache();
			delete_transient( \SWELL_Theme\License::$waiting_transient_key );
			delete_transient( \SWELL_Theme\License::$expired_transient_key );
			wp_safe_redirect( admin_url( 'admin.php?page=swell_settings_swellers' ) );
		}
	}
}, 99 );

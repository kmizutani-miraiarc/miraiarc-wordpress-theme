<?php
namespace SWELL_Theme;

use \SWELL_Theme as SWELL;

if ( ! defined( 'ABSPATH' ) ) exit;

class License {

	public static $status_transient_key  = 'swlr_user_status';
	public static $waiting_transient_key = 'swlr_is_waiting_activate';
	public static $expired_transient_key = 'swlr_is_expired_activate';



	/**
	 * ステータスキャッシュ削除
	 */
	public static function delete_status_cache() {
		delete_transient( self::$status_transient_key );
		// delete_transient( self::$waiting_transient_key );
		// delete_transient( self::$expired_transient_key );
	}


	/**
	 * ユーザー照合
	 */
	public static function check_swlr( $email = '', $action_type = '' ) {

		if ( 'auto' === $action_type ) {

			// まずここで waitingキャッシュ確認。waiting中はpostさせない
			$is_waiting = get_transient( self::$waiting_transient_key );
			if ( $is_waiting ) {
				SWELL::$licence_status  = 'waiting';
				SWELL::$update_dir_path = '';
				return;
			}

			// 次に expired キャッシュ確認。 一度expired確認したら少しキャッシュ
			$is_expired = get_transient( self::$expired_transient_key );
			if ( $is_expired ) {
				SWELL::$licence_status  = 'expired';
				SWELL::$update_dir_path = '';
				return;
			}
		}

		// キャッシュをチェック
		$cached_data = get_transient( self::$status_transient_key );
		if ( false !== $cached_data ) {
			// キャッシュあればそのデータセット
			SWELL::$licence_status  = $cached_data['status'] ?? '';
			SWELL::$update_dir_path = $cached_data['path'] ?? '';

			$expiration = get_option( '_transient_timeout_' . self::$status_transient_key ) - time();
		} elseif ( $email ) {
			// キャッシュがなく、email設定がある → 認証リクエスト開始
			// form送信時 or キャッシュ切れによる定期チェック

			// API接続
			$response = wp_remote_post(
				'https://users.swell-theme.com/?swlr-api=activation',
				[
					'timeout'     => 3,
					'redirection' => 5,
					'sslverify'   => false,
					'headers'     => [ 'Content-Type: application/json' ],
					'body'        => [
						'email'       => $email,
						'domain'      => str_replace( [ 'http://', 'https://' ], '', home_url() ),
						'action_type' => $action_type,
					],
				]
			);
			if ( is_wp_error( $response ) ) {
				SWELL::$licence_status  = 'response_error';
				SWELL::$update_dir_path = '';
				// echo '<pre style="margin-inline:200px;background:#fff;border:solid 2px orange">';
				// var_dump( 'is_wp_error: ' . $error_message );
				// echo '</pre>';
			} else {
				$response_data   = json_decode( $response['body'], true );
				$licence_status  = $response_data['status'] ?? '';
				$update_dir_path = $response_data['path'] ?? '';

				// ワンタイム認証待ちの時
				if ( 'waiting' === $licence_status ) {
					// 高速ページ更新で連続fetchされないように数秒のキャッシュセット。
					set_transient( self::$waiting_transient_key, 1, 10 );
				} elseif ( 'expired' === $licence_status ) {
					// ページ更新ごとに毎回fetchしないように キャッシュをセット。
					set_transient( self::$expired_transient_key, 1, DAY_IN_SECONDS );

				} elseif ( 'ok' === $licence_status ) {
					// 認証成功時、30日はキャッシュする（月に一回くらいの頻度で自動再チェック）
					set_transient( self::$status_transient_key, $response_data, 30 * DAY_IN_SECONDS );
				}

				SWELL::$licence_status  = $licence_status;
				SWELL::$update_dir_path = $update_dir_path;
			}
		} else {

			// email空の時
			SWELL::$licence_status  = '';
			SWELL::$update_dir_path = '';
		}

		// SWELL::$update_dir_path セットした上で、パス変更をチェック
		if ( self::is_change_update_dir() ) {
			delete_transient( self::$status_transient_key );
		}
	}


	/**
	 * update pathに変更がないかチェック
	 */
	public static function is_change_update_dir() {

		$dir_ver = \SWELL_Theme::get_swl_json_dir();
		// 未認証でパスが取得できていない場合
		if ( ! SWELL::$update_dir_path ) return false;

		$dir_ver = \SWELL_Theme::get_swl_json_dir();

		// /v1-hoge → /v2-foo などに変わってるかどうか
		if ( $dir_ver && false === strpos( SWELL::$update_dir_path, "/{$dir_ver}" ) ) {
			return true;
		}

		return false;
	}


	/**
	 * 認証削除
	 */
	public static function delete_swlr( $email = '' ) {

		SWELL::$licence_status  = '';
		SWELL::$update_dir_path = '';

		// SWELLERS側でも削除
		if ( $email ) {
			$response = wp_remote_post(
				'https://users.swell-theme.com/?swlr-api=deactivate',
				[
					'timeout'     => 3,
					'redirection' => 5,
					'sslverify'   => false,
					'headers'     => [ 'Content-Type: application/json' ],
					'body'        => [
						'email' => $email,
						'url'   => str_replace( [ 'http://', 'https://' ], '', home_url() ),
					],
				]
			);
		}
	}
}

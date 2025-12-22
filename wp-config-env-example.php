<?php
/**
 * WordPress設定ファイル - 環境変数設定例
 * 
 * このファイルを参考に、実際のwp-config.phpに以下を追加してください。
 * wp-config.phpの「/* 編集が必要なのはここまでです ! ウィキペディアで調べましょう。 */」の
 * 直前に追加することを推奨します。
 */

// ===========================================
// mirai-api設定
// ===========================================

// mirai-apiのURL
// 本番環境: https://api.miraiarc.co.jp
// ローカル環境: http://localhost:8000
// Docker環境: http://mirai-api:8000
if (!defined('MIRAI_API_URL')) {
    define('MIRAI_API_URL', 'https://api.miraiarc.co.jp');
}

// mirai-apiのAPIキー
if (!defined('MIRAI_API_KEY')) {
    define('MIRAI_API_KEY', 'mirai_90d82affd65fc37146b8fffb4f495a3fbda36663af76f85318e9df8252bc665b');
}

// 環境変数としても設定（既存のgetenv()呼び出しとの互換性のため）
if (!getenv('MIRAI_API_URL')) {
    putenv('MIRAI_API_URL=' . MIRAI_API_URL);
    $_ENV['MIRAI_API_URL'] = MIRAI_API_URL;
    $_SERVER['MIRAI_API_URL'] = MIRAI_API_URL;
}

if (!getenv('MIRAI_API_KEY')) {
    putenv('MIRAI_API_KEY=' . MIRAI_API_KEY);
    $_ENV['MIRAI_API_KEY'] = MIRAI_API_KEY;
    $_SERVER['MIRAI_API_KEY'] = MIRAI_API_KEY;
}






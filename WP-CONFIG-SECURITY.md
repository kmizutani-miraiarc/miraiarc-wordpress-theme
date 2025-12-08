# wp-config.php セキュリティガイド

## wp-config.phpに直接書く場合の安全性

**結論：適切に保護されていれば安全です。** WordPressの標準的な方法です。

## セキュリティ比較

| 項目 | wp-config.php直接 | .envファイル |
|------|------------------|--------------|
| WordPress標準 | ✅ 標準的な方法 | ❌ 追加実装が必要 |
| セキュリティ | ✅ 適切に保護すれば安全 | ✅ 適切に保護すれば安全 |
| バージョン管理 | ⚠️ 機密情報を含めない | ✅ 除外しやすい |
| 設定変更の容易さ | ⚠️ ファイル編集が必要 | ✅ ファイル編集のみ |
| 環境ごとの管理 | ⚠️ 条件分岐が必要 | ✅ ファイルを分けるだけ |

## 推奨：wp-config.phpに直接書く方法

### 1. wp-config.phpへの追加方法

`wp-config.php`の「/* 編集が必要なのはここまでです ! ウィキペディアで調べましょう。 */」の**直前**に以下を追加：

```php
// ===========================================
// mirai-api設定
// ===========================================

// mirai-apiのURL
if (!defined('MIRAI_API_URL')) {
    define('MIRAI_API_URL', 'https://api.miraiarc.co.jp');
}

// mirai-apiのAPIキー
if (!defined('MIRAI_API_KEY')) {
    define('MIRAI_API_KEY', 'your-api-key-here');
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
```

### 2. 環境ごとに異なる設定を使う場合

```php
// 環境判定
$is_production = (strpos($_SERVER['HTTP_HOST'], 'miraiarc.co.jp') !== false);

// 環境に応じた設定
if ($is_production) {
    define('MIRAI_API_URL', 'https://api.miraiarc.co.jp');
    define('MIRAI_API_KEY', 'production-api-key');
} else {
    define('MIRAI_API_URL', 'http://localhost:8000');
    define('MIRAI_API_KEY', 'development-api-key');
}
```

## セキュリティ対策（必須）

### 1. nginx設定でwp-config.phpへのアクセスを拒否

```nginx
server {
    # ... 他の設定 ...
    
    # wp-config.phpへの直接アクセスを拒否
    location ~ /wp-config\.php {
        deny all;
        return 404;
    }
    
    # その他の機密ファイルも保護
    location ~ /\.(env|git|svn|htaccess|htpasswd) {
        deny all;
        return 404;
    }
}
```

### 2. ファイルパーミッションの設定

```bash
# wp-config.phpのパーミッションを設定
sudo chmod 600 /var/www/miraiarc.co.jp/wp-config.php
sudo chown www-data:www-data /var/www/miraiarc.co.jp/wp-config.php

# ディレクトリのパーミッションも確認
sudo chmod 755 /var/www/miraiarc.co.jp
```

### 3. .gitignoreに追加（重要！）

```bash
# .gitignoreに追加
echo "wp-config.php" >> .gitignore
```

または、`wp-config.php`の代わりに`wp-config-sample.php`をコミットし、本番環境で`wp-config.php`を作成する方法もあります。

### 4. バージョン管理の注意点

**絶対にやってはいけないこと：**
- ❌ `wp-config.php`をGitにコミットする（機密情報が漏洩）
- ❌ 公開リポジトリに`wp-config.php`をアップロードする

**推奨される方法：**
- ✅ `wp-config-sample.php`をコミット（機密情報を削除したテンプレート）
- ✅ 実際の`wp-config.php`は`.gitignore`に追加
- ✅ 本番環境では手動で`wp-config.php`を作成

## 比較：.envファイルを使う場合

`.env`ファイルを使う場合は、`wp-config.php`の先頭に以下を追加：

```php
<?php
// .envファイルの読み込み
$env_file = __DIR__ . '/.env';
if (file_exists($env_file)) {
    $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            $value = trim($value, '"\'');
            if (!empty($key)) {
                putenv("$key=$value");
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
    }
}
```

## 結論

**`wp-config.php`に直接書く方法は安全です。** ただし、以下を必ず実施してください：

1. ✅ nginxで`wp-config.php`への直接アクセスを拒否
2. ✅ ファイルパーミッションを適切に設定（600）
3. ✅ `.gitignore`に追加してバージョン管理から除外
4. ✅ 本番環境では強力なAPIキーを使用

WordPressの標準的な方法なので、この方法で問題ありません。



# WordPress 固定ページ・カスタム投稿ページ 404エラー トラブルシューティング

## 問題の原因

固定ページやカスタム投稿ページが404エラーで表示されない主な原因は以下の通りです：

### 1. .htaccessファイルの問題（最も一般的）

**症状**: 固定ページやカスタム投稿タイプのURLにアクセスすると404エラーが発生

**原因**: 
- `.htaccess`ファイルにWordPressのリライトルールが含まれていない
- Apacheの`AllowOverride`設定が`None`になっている

**解決方法**:

#### 方法1: WordPress管理画面でパーマリンクを更新（推奨）

1. WordPress管理画面にログイン: http://localhost:8081/wp-admin
2. **設定 → パーマリンク設定** に移動
3. 現在の設定を確認（例: `/%postname%/`）
4. **「変更を保存」をクリック**（設定を変更する必要はありません）
5. これにより、WordPressが自動的に`.htaccess`ファイルを正しく生成します

#### 方法2: 手動で.htaccessファイルを修正

```bash
cd mirai-wordpress
./fix-permalinks.sh
```

### 2. Apacheの設定問題

**確認方法**:
```bash
docker exec -i mirai-wordpress grep "AllowOverride" /etc/apache2/apache2.conf
```

**修正方法**:
`AllowOverride None` を `AllowOverride All` に変更する必要がありますが、WordPressのDockerイメージでは通常は正しく設定されています。

### 3. パーマリンク構造の問題

**確認方法**:
```bash
docker exec -i mirai-wordpress-db mysql -uwordpress_user -pwordpress_password wordpress -e "SELECT option_value FROM wp_options WHERE option_name = 'permalink_structure';"
```

**修正方法**:
WordPress管理画面でパーマリンク設定を更新してください。

### 4. rewrite_rulesのキャッシュ問題

**解決方法**:
```bash
docker exec -i mirai-wordpress-db mysql -uwordpress_user -pwordpress_password wordpress -e "UPDATE wp_options SET option_value = '' WHERE option_name = 'rewrite_rules';"
```

その後、WordPress管理画面でパーマリンク設定を保存してください。

## 確認手順

### 1. .htaccessファイルの確認

```bash
docker exec -i mirai-wordpress cat /var/www/html/.htaccess
```

以下のようなリライトルールが含まれている必要があります：

```apache
# BEGIN WordPress
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteBase /
RewriteRule ^index\.php$ - [L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . /index.php [L]
</IfModule>
# END WordPress
```

### 2. mod_rewriteの確認

```bash
docker exec -i mirai-wordpress apache2ctl -M | grep rewrite
```

`rewrite_module (shared)` と表示されれば有効です。

### 3. 固定ページの確認

```bash
docker exec -i mirai-wordpress-db mysql -uwordpress_user -pwordpress_password wordpress -e "SELECT post_name, post_type, post_status FROM wp_posts WHERE post_type = 'page' AND post_status = 'publish' LIMIT 5;"
```

### 4. アクセステスト

```bash
# 固定ページのテスト
curl -I http://localhost:8081/message/

# カスタム投稿タイプのテスト
curl -I http://localhost:8081/exemple/your-post-slug/
```

## 最も確実な解決方法

1. **WordPress管理画面でパーマリンクを更新**
   - http://localhost:8081/wp-admin
   - 設定 → パーマリンク設定
   - 「変更を保存」をクリック

2. **Apacheを再起動**（必要に応じて）
   ```bash
   docker-compose restart wordpress
   ```

3. **ブラウザのキャッシュをクリア**

4. **再度アクセスして確認**

## 注意事項

- `.htaccess`ファイルはWordPressが自動的に管理するため、手動で編集すると上書きされる可能性があります
- パーマリンク設定を変更すると、WordPressが自動的に`.htaccess`を更新します
- カスタム投稿タイプの場合は、テーマやプラグインで`register_post_type`の`rewrite`パラメータが正しく設定されている必要があります



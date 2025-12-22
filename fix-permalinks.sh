#!/bin/bash

# WordPressパーマリンク修正スクリプト
# .htaccessファイルを修正し、パーマリンクを有効化します

set -e

# カラー出力
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

CONTAINER_NAME="mirai-wordpress"

echo -e "${GREEN}WordPressパーマリンク修正スクリプト${NC}"
echo "=========================================="

# 1. コンテナの起動確認
if ! docker ps | grep -q "$CONTAINER_NAME"; then
    echo -e "${RED}エラー: $CONTAINER_NAME コンテナが起動していません${NC}"
    exit 1
fi

echo -e "${GREEN}✓ WordPressコンテナが起動しています${NC}"

# 2. .htaccessファイルを修正
echo -e "${YELLOW}.htaccessファイルを修正しています...${NC}"

docker exec -i "$CONTAINER_NAME" bash << 'EOF'
cat > /var/www/html/.htaccess << 'HTACCESS_EOF'
#SITEGUARD_PLUGIN_SETTINGS_START
#==== SITEGUARD_RENAME_LOGIN_SETTINGS_START
<IfModule mod_rewrite.c>
    RewriteEngine on
    RewriteBase /
    RewriteRule ^wp-signup\.php 404-siteguard [L]
    RewriteRule ^wp-activate\.php 404-siteguard [L]
    RewriteRule ^login_66091(.*)$ wp-login.php$1 [L]
</IfModule>
#==== SITEGUARD_RENAME_LOGIN_SETTINGS_END
#SITEGUARD_PLUGIN_SETTINGS_END

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
HTACCESS_EOF

# ファイルの権限を設定
chown www-data:www-data /var/www/html/.htaccess
chmod 644 /var/www/html/.htaccess
EOF

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ .htaccessファイルを修正しました${NC}"
else
    echo -e "${RED}✗ .htaccessファイルの修正に失敗しました${NC}"
    exit 1
fi

# 3. Apacheを再起動
echo -e "${YELLOW}Apacheを再起動しています...${NC}"
docker exec "$CONTAINER_NAME" service apache2 reload 2>/dev/null || docker exec "$CONTAINER_NAME" apache2ctl graceful

echo -e "${GREEN}✓ Apacheを再起動しました${NC}"

# 4. 完了メッセージ
echo ""
echo -e "${GREEN}=========================================="
echo "パーマリンク修正が完了しました！"
echo "==========================================${NC}"
echo ""
echo "次のステップ:"
echo "1. WordPress管理画面にアクセス: http://localhost:8081/wp-admin"
echo "2. 設定 → パーマリンク設定 に移動"
echo "3. 「変更を保存」をクリック（設定を変更する必要はありません）"
echo ""
echo -e "${YELLOW}注意: これにより.htaccessファイルが自動的に更新されます${NC}"






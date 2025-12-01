#!/bin/bash

# WordPressデータベースインポートスクリプト
# ローカル環境用にURLを書き換えてインポートします

set -e

# カラー出力
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# 設定
SQL_FILE="./wp-content/mirai_arc_wp.sql"
LOCAL_URL="http://localhost:8081"
PROD_URL="https://miraiarc.co.jp"
DB_NAME="${WORDPRESS_DB_NAME:-wordpress}"
DB_USER="${WORDPRESS_DB_USER:-wordpress_user}"
DB_PASSWORD="${WORDPRESS_DB_PASSWORD:-wordpress_password}"
DB_HOST="wordpress-db"
CONTAINER_NAME="mirai-wordpress-db"

echo -e "${GREEN}WordPressデータベースインポートスクリプト${NC}"
echo "=========================================="

# 1. SQLファイルの存在確認
if [ ! -f "$SQL_FILE" ]; then
    echo -e "${RED}エラー: $SQL_FILE が見つかりません${NC}"
    exit 1
fi

echo -e "${GREEN}✓ SQLファイルが見つかりました: $SQL_FILE${NC}"

# 2. コンテナの起動確認
if ! docker ps | grep -q "$CONTAINER_NAME"; then
    echo -e "${YELLOW}警告: $CONTAINER_NAME コンテナが起動していません${NC}"
    echo "docker-compose up -d でコンテナを起動してください"
    exit 1
fi

echo -e "${GREEN}✓ データベースコンテナが起動しています${NC}"

# 3. 一時的なSQLファイルを作成（URL書き換え）
TEMP_SQL_FILE="/tmp/mirai_arc_wp_local_$(date +%s).sql"
echo -e "${YELLOW}URLを書き換えています...${NC}"

# sedでURLを書き換え（複数回実行して確実に）
sed "s|$PROD_URL|$LOCAL_URL|g" "$SQL_FILE" > "$TEMP_SQL_FILE"

# データベース名も書き換え
sed -i.bak "s|mirai_arc_wp|$DB_NAME|g" "$TEMP_SQL_FILE"
rm -f "${TEMP_SQL_FILE}.bak"

# URLの書き換え数を確認
URL_COUNT=$(grep -o "$PROD_URL" "$TEMP_SQL_FILE" | wc -l | tr -d ' ')
if [ "$URL_COUNT" -gt 0 ]; then
    echo -e "${YELLOW}警告: まだ $URL_COUNT 箇所に本番URLが残っています。再度書き換えます...${NC}"
    sed -i.bak "s|$PROD_URL|$LOCAL_URL|g" "$TEMP_SQL_FILE"
    rm -f "${TEMP_SQL_FILE}.bak"
fi

echo -e "${GREEN}✓ URL書き換え完了${NC}"
echo "  本番URL: $PROD_URL"
echo "  ローカルURL: $LOCAL_URL"

# 4. 既存のデータベースを削除（確認付き）
echo ""
echo -e "${YELLOW}既存のデータベースを削除しますか？ (y/N)${NC}"
read -r response
if [[ "$response" =~ ^([yY][eE][sS]|[yY])$ ]]; then
    echo "既存のデータベースを削除しています..."
    docker exec -i "$CONTAINER_NAME" mysql -u"$DB_USER" -p"$DB_PASSWORD" -e "DROP DATABASE IF EXISTS $DB_NAME;"
    docker exec -i "$CONTAINER_NAME" mysql -u"$DB_USER" -p"$DB_PASSWORD" -e "CREATE DATABASE $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci;"
    echo -e "${GREEN}✓ データベースを再作成しました${NC}"
else
    echo "既存のデータベースを保持します"
fi

# 5. SQLファイルをインポート
echo ""
echo -e "${YELLOW}データベースにインポートしています...${NC}"
docker exec -i "$CONTAINER_NAME" mysql -u"$DB_USER" -p"$DB_PASSWORD" "$DB_NAME" < "$TEMP_SQL_FILE"

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ インポートが完了しました${NC}"
else
    echo -e "${RED}✗ インポートに失敗しました${NC}"
    rm -f "$TEMP_SQL_FILE"
    exit 1
fi

# 6. 一時ファイルを削除
rm -f "$TEMP_SQL_FILE"
echo -e "${GREEN}✓ 一時ファイルを削除しました${NC}"

# 7. 追加のURL書き換え（全テーブル）
echo ""
echo -e "${YELLOW}データベース内のURLを確認・更新しています...${NC}"

# wp_optionsテーブルのURL更新
docker exec -i "$CONTAINER_NAME" mysql -u"$DB_USER" -p"$DB_PASSWORD" "$DB_NAME" <<EOF
-- wp_optionsテーブル
UPDATE wp_options SET option_value = REPLACE(option_value, '$PROD_URL', '$LOCAL_URL') WHERE option_name IN ('siteurl', 'home');
UPDATE wp_options SET option_value = '$LOCAL_URL' WHERE option_name = 'siteurl';
UPDATE wp_options SET option_value = '$LOCAL_URL' WHERE option_name = 'home';

-- wp_postsテーブル（投稿内容、ガイド、メタデータ）
UPDATE wp_posts SET post_content = REPLACE(post_content, '$PROD_URL', '$LOCAL_URL');
UPDATE wp_posts SET guid = REPLACE(guid, '$PROD_URL', '$LOCAL_URL');

-- wp_postmetaテーブル（カスタムフィールドなど）
UPDATE wp_postmeta SET meta_value = REPLACE(meta_value, '$PROD_URL', '$LOCAL_URL') WHERE meta_value LIKE '%$PROD_URL%';

-- wp_commentsテーブル（コメント）
UPDATE wp_comments SET comment_content = REPLACE(comment_content, '$PROD_URL', '$LOCAL_URL');

-- wp_usermetaテーブル（ユーザーメタデータ）
UPDATE wp_usermeta SET meta_value = REPLACE(meta_value, '$PROD_URL', '$LOCAL_URL') WHERE meta_value LIKE '%$PROD_URL%';
EOF

echo -e "${GREEN}✓ データベース内のURLを更新しました${NC}"

# 8. 完了メッセージ
echo ""
echo -e "${GREEN}=========================================="
echo "インポートが完了しました！"
echo "==========================================${NC}"
echo ""
echo "WordPressサイト: $LOCAL_URL"
echo "管理画面: $LOCAL_URL/wp-admin"
echo ""
echo -e "${YELLOW}注意: 初回アクセス時にパーマリンクの設定を更新する必要がある場合があります${NC}"


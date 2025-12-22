# WordPressデータベースインポートガイド

`mirai_arc_wp.sql`を使用してWordPressデータベースをローカル環境にインポートする方法です。

## 前提条件

1. Docker ComposeでWordPressコンテナが起動していること
2. `mirai_arc_wp.sql`ファイルが`wp-content/`ディレクトリに存在すること

## インポート方法

### 方法1: 自動スクリプトを使用（推奨）

```bash
cd mirai-wordpress
./import-wp-database.sh
```

このスクリプトは以下を自動で実行します：
- 本番URL（`https://miraiarc.co.jp`）をローカルURL（`http://localhost:8081`）に書き換え
- データベース名を環境変数に合わせて書き換え
- 既存データベースの削除（確認付き）
- SQLファイルのインポート
- `wp_options`テーブルのURL更新

### 方法2: 手動でインポート

#### 1. SQLファイルのURLを書き換え

```bash
cd mirai-wordpress
sed 's|https://miraiarc.co.jp|http://localhost:8081|g' wp-content/mirai_arc_wp.sql > /tmp/wp_local.sql
```

#### 2. データベースにインポート

```bash
# 既存のデータベースを削除（オプション）
docker exec -i mirai-wordpress-db mysql -uwordpress_user -pwordpress_password -e "DROP DATABASE IF EXISTS wordpress;"
docker exec -i mirai-wordpress-db mysql -uwordpress_user -pwordpress_password -e "CREATE DATABASE wordpress CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_520_ci;"

# SQLファイルをインポート
docker exec -i mirai-wordpress-db mysql -uwordpress_user -pwordpress_password wordpress < /tmp/wp_local.sql
```

#### 3. wp_optionsテーブルのURLを更新

```bash
docker exec -i mirai-wordpress-db mysql -uwordpress_user -pwordpress_password wordpress <<EOF
UPDATE wp_options SET option_value = 'http://localhost:8081' WHERE option_name = 'siteurl';
UPDATE wp_options SET option_value = 'http://localhost:8081' WHERE option_name = 'home';
EOF
```

## インポート後の確認

1. **WordPressサイトにアクセス**
   - http://localhost:8081

2. **管理画面にログイン**
   - http://localhost:8081/wp-admin
   - 本番環境と同じ管理者アカウントでログインできます

3. **パーマリンクの設定を更新**
   - 管理画面 → 設定 → パーマリンク設定
   - 「変更を保存」をクリック（設定を変更する必要はありません）

## トラブルシューティング

### 画像が表示されない場合

画像のURLも書き換える必要がある場合があります：

```bash
docker exec -i mirai-wordpress-db mysql -uwordpress_user -pwordpress_password wordpress <<EOF
UPDATE wp_posts SET post_content = REPLACE(post_content, 'https://miraiarc.co.jp', 'http://localhost:8081');
UPDATE wp_postmeta SET meta_value = REPLACE(meta_value, 'https://miraiarc.co.jp', 'http://localhost:8081');
UPDATE wp_options SET option_value = REPLACE(option_value, 'https://miraiarc.co.jp', 'http://localhost:8081');
EOF
```

### データベース接続エラー

コンテナが起動しているか確認：

```bash
docker-compose ps wordpress-db
```

### インポートが失敗する場合

SQLファイルのサイズが大きい場合、タイムアウトが発生する可能性があります。その場合は、`max_allowed_packet`を増やしてください：

```bash
docker exec -i mirai-wordpress-db mysql -uwordpress_user -pwordpress_password -e "SET GLOBAL max_allowed_packet=1073741824;"
```

## 注意事項

- インポート前に既存のデータベースをバックアップすることを推奨します
- 本番環境のデータを上書きしないよう注意してください
- ローカル環境でのみ使用してください






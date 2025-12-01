# WordPress Docker環境

このディレクトリには、WordPress用の設定ファイルとテーマ・プラグイン用のディレクトリが含まれています。

**注意**: WordPress環境はルートディレクトリの`docker-compose.yml`で管理されています。

## セットアップ

### 1. 環境変数の設定

ルートディレクトリの`env.example`をコピーして`.env`ファイルを作成し、必要に応じて設定を変更してください。

```bash
# ルートディレクトリで実行
cd ..
cp env.example .env
```

WordPress関連の環境変数は以下の通りです：
- `WORDPRESS_DB_NAME`: データベース名（デフォルト: wordpress）
- `WORDPRESS_DB_USER`: データベースユーザー名（デフォルト: wordpress_user）
- `WORDPRESS_DB_PASSWORD`: データベースパスワード（デフォルト: wordpress_password）
- `WORDPRESS_PORT`: WordPressポート（デフォルト: 8081）
- `PHPMYADMIN_PORT`: phpMyAdminポート（デフォルト: 8082）

### 2. Docker Composeで起動

ルートディレクトリで実行してください。

```bash
# ルートディレクトリで実行
cd ..
docker-compose up -d
```

### 3. WordPressの初期設定

1. ブラウザで `http://localhost:8081` にアクセス
2. WordPressの初期設定画面が表示されます
3. 言語、サイトタイトル、管理者アカウントなどを設定してください

## サービス

### WordPress
- URL: http://localhost:8081
- コンテナ名: `mirai-wordpress`

### MySQL
- ホスト: `wordpress-db`
- ポート（ホスト側）: `3307`
- コンテナ名: `mirai-wordpress-db`

### phpMyAdmin（オプション）
- URL: http://localhost:8082
- コンテナ名: `mirai-wordpress-phpmyadmin`

## ディレクトリ構造

```
mirai-wordpress/
├── uploads.ini             # PHP設定ファイル（ファイルアップロードサイズ制限など）
├── wp-content/             # WordPressテーマ・プラグイン（マウント用）
└── README.md               # このファイル

注意: docker-compose.ymlはルートディレクトリにあります
```

## よく使うコマンド

**注意**: すべてのコマンドはルートディレクトリで実行してください。

### コンテナの起動
```bash
# ルートディレクトリで実行
docker-compose up -d
```

### コンテナの停止
```bash
# ルートディレクトリで実行
docker-compose down
```

### コンテナの再起動
```bash
# ルートディレクトリで実行
docker-compose restart wordpress wordpress-db
```

### ログの確認
```bash
# すべてのサービスのログ
docker-compose logs -f

# WordPressのログのみ
docker-compose logs -f wordpress

# WordPress MySQLのログのみ
docker-compose logs -f wordpress-db
```

### コンテナ内でコマンドを実行
```bash
# WordPressコンテナ内でbashを実行
docker-compose exec wordpress bash

# WordPress MySQLコンテナ内でMySQLクライアントを実行
docker-compose exec wordpress-db mysql -u wordpress_user -p wordpress
```

### データベースのバックアップ
```bash
docker-compose exec wordpress-db mysqldump -u wordpress_user -p wordpress > backup.sql
```

### データベースの復元
```bash
docker-compose exec -T wordpress-db mysql -u wordpress_user -p wordpress < backup.sql
```

## テーマ・プラグインのインストール

`wp-content`ディレクトリにテーマやプラグインを配置することで、WordPressから使用できます。

```bash
# テーマの配置例
mkdir -p wp-content/themes/my-theme

# プラグインの配置例
mkdir -p wp-content/plugins/my-plugin
```

## トラブルシューティング

### ポートが既に使用されている場合

ルートディレクトリの`.env`ファイルでポート番号を変更してください。

```env
WORDPRESS_PORT=8081
WORDPRESS_DB_PORT=3307
PHPMYADMIN_PORT=8082
```

### データベース接続エラー

1. WordPress MySQLコンテナが起動しているか確認（ルートディレクトリで実行）
   ```bash
   docker-compose ps wordpress-db
   ```

2. 環境変数が正しく設定されているか確認（ルートディレクトリで実行）
   ```bash
   cat .env | grep WORDPRESS
   ```

3. コンテナを再起動（ルートディレクトリで実行）
   ```bash
   docker-compose restart wordpress wordpress-db
   ```

### ファイルアップロードサイズ制限

`mirai-wordpress/uploads.ini`ファイルで設定を変更できます。変更後、WordPressコンテナを再起動してください（ルートディレクトリで実行）。

```bash
docker-compose restart wordpress
```

## 注意事項

- 本番環境で使用する場合は、セキュリティ設定を適切に行ってください
- データベースのパスワードは強力なものを使用してください
- 定期的にバックアップを取得してください


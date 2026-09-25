# BookShelf 書籍レビューアプリ

## 概要

書籍の登録・閲覧、レビュー投稿、お気に入り登録などを行うための書籍レビューアプリケーションです。

基本機能では、以下の機能を実装します。

- 会員登録・ログイン・ログアウト
- 書籍の登録・閲覧・編集・削除
- レビューの投稿・編集・削除
- お気に入り登録
- レビューへのいいね
- ジャンル管理
- 書籍ランキング
- 公開API（JSON）

基本機能の実装完了後、検索・外部API連携・読書レポートなどの応用機能を追加予定です。

---

## 環境構築

本プロジェクトは、Docker DesktopとLaravel Sailを使用して動作します。

### 前提条件

以下を使用できる状態にしてください。

- Git
- Docker Desktop
- WSL2（Windowsの場合）

Docker Desktopを起動してから、WSLのターミナルで以降のコマンドを実行します。

### 1. リポジトリをクローン

```bash
git clone <GitHubリポジトリURL>
cd bookshelf-app
```

### 2. Composer依存パッケージをインストール

ローカル環境でComposerを使用できる場合は、以下を実行します。

```bash
composer install
```

Composerを使用できない場合は、Dockerを利用してインストールします。

```bash
docker run --rm \
  -u "$(id -u):$(id -g)" \
  -v "$(pwd):/var/www/html" \
  -w /var/www/html \
  -e COMPOSER_CACHE_DIR=/tmp/composer_cache \
  laravelsail/php82-composer:latest \
  composer install
```

### 3. 環境設定ファイルを作成

```bash
cp .env.example .env
```

`.env`を開き、データベース接続情報を以下のように設定します。

```env
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=sail
DB_PASSWORD=password
```

### 4. Laravel Sailを起動

```bash
./vendor/bin/sail up -d
```

コンテナの状態を確認します。

```bash
./vendor/bin/sail ps
```

以下のサービスが起動していることを確認してください。

- `laravel.test`
- `mysql`
- `phpmyadmin`

### 5. アプリケーションキーを生成

```bash
./vendor/bin/sail artisan key:generate
```

### 6. フロントエンドの依存パッケージをインストール

```bash
./vendor/bin/sail npm install
```

### 7. マイグレーションと初期データを実行

```bash
./vendor/bin/sail artisan migrate --seed
```

既存のテーブルを削除して初期状態から作り直す場合は、以下を実行します。

```bash
./vendor/bin/sail artisan migrate:fresh --seed
```

> `migrate:fresh`を実行すると、データベース内の既存データはすべて削除されます。

### 8. Vite開発サーバーを起動

```bash
./vendor/bin/sail npm run dev
```

画面を確認している間は、このコマンドを実行したままにしてください。

停止する場合は、実行中のターミナルで `Ctrl + C` を押します。

### 9. アクセス（URL）

ブラウザで以下へアクセスします。

| 画面 | URL |
| --- | --- |
| アプリケーション | `http://localhost` |
| 会員登録 | `http://localhost/register` |
| ログイン | `http://localhost/login` |
| phpMyAdmin | `http://localhost:8080` |

### 10. Sailエイリアスを設定（任意）

毎回 `./vendor/bin/sail` と入力せず、`sail`のみで実行したい場合は以下を設定します。

bashの場合：

```bash
echo "alias sail='[ -f sail ] && bash sail || bash vendor/bin/sail'" >> ~/.bashrc
source ~/.bashrc
```

zshの場合：

```bash
echo "alias sail='[ -f sail ] && bash sail || bash vendor/bin/sail'" >> ~/.zshrc
source ~/.zshrc
```

設定後は以下のように実行できます。

```bash
sail artisan migrate --seed
sail npm run dev
```

### 11. コンテナを停止

```bash
./vendor/bin/sail down
```

### Apple Silicon搭載Macについて

Apple Silicon搭載MacでSail起動時に以下のエラーが発生する場合があります。

```text
no matching manifest for linux/arm64/v8
```

その場合は、`compose.yaml`のMySQLサービスに以下を追加します。

```yaml
platform: 'linux/amd64'
```

---

## 使用技術

| 技術 | バージョン・用途 |
| --- | --- |
| HTML | 画面構造 |
| Tailwind CSS | 3.4.19 / スタイル設定 |
| Alpine.js | 3.17.4 / フロントエンド |
| PHP | 8.5.7 |
| Laravel | 10.50.3 |
| MySQL | 8.4.10 |
| Vite | フロントエンド開発環境 |
| Docker | コンテナ環境 |
| Laravel Sail | Docker開発環境の操作 |
| phpMyAdmin | データベース管理 |

※ 詳細なバージョンは開発環境確定後に更新します。

---

## ER図

テーブル設計完了後に追加します。

```mermaid
erDiagram

```

### 制約

テーブル設計完了後に記載します。

### リレーション

テーブル設計完了後に記載します。

### 外部キー

テーブル設計完了後に記載します。

### 補助テーブルについて

Laravel / Sanctum等が使用するフレームワーク用テーブルについては、業務テーブルとは分けて記載します。

---

## APIエンドポイント一覧

基本機能では、以下の公開APIを実装します。

| HTTPメソッド | URI | 概要 | 認証 |
| --- | --- | --- | --- |
| GET | `/api/v1/books` | 書籍一覧を取得 | 不要 |
| GET | `/api/v1/books/{book}` | 書籍詳細を取得 | 不要 |
| POST | `/api/v1/books` | 書籍を新規登録 | 不要 |
| PUT | `/api/v1/books/{book}` | 書籍を更新 | 不要 |
| DELETE | `/api/v1/books/{book}` | 書籍を削除 | 不要 |

基本機能では公開APIに認証を設定しません。

応用機能では、POST / PUT / DELETE の書き込み系APIにLaravel Sanctumによるトークン認証を追加予定です。

---

## ログイン情報

Seeder実装後、以下の動作確認用ユーザーを利用できるようにします。

| 名前 | メールアドレス | パスワード |
| --- | --- | --- |
| 山田太郎 | `yamada@example.com` | `password` |
| 鈴木花子 | `suzuki@example.com` | `password` |
| 田中一郎 | `tanaka@example.com` | `password` |
| 佐藤美咲 | `sato@example.com` | `password` |
| 高橋健太 | `takahashi@example.com` | `password` |

---

## 作成者

渡利明子
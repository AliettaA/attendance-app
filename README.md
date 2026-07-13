# 勤怠管理アプリ

## 概要

一般ユーザーの出勤・退勤・休憩打刻、勤怠一覧、勤怠詳細、修正申請、勤怠レポートを管理するアプリケーションです。

管理者はスタッフ別の勤怠確認、勤怠修正、修正申請の承認、CSV出力を行えます。

## 使用技術

- PHP 8.1
- Laravel 10
- Laravel Fortify
- Laravel Sanctum
- MySQL 8.4
- Laravel Sail
- Vite
- Tailwind CSS

## 環境構築

### 1. リポジトリを取得

```bash
git clone git@github.com:AliettaA/attendance-app.git
cd attendance-app
```

### 2. PHP依存パッケージをインストール

```bash
composer install
```

ローカルに Composer がない場合は、Docker を使ってインストールできます。

```bash
docker run --rm \
  -u "$(id -u):$(id -g)" \
  -v "$(pwd):/var/www/html" \
  -w /var/www/html \
  laravelsail/php85-composer:latest \
  composer install --ignore-platform-reqs
```

### 3. 環境変数ファイルを作成

```bash
cp .env.example .env
```

### 4. Dockerコンテナを起動

```bash
./vendor/bin/sail up -d
```

### 5. アプリケーションキーを作成

```bash
./vendor/bin/sail artisan key:generate
```

### 6. データベースを作成

```bash
./vendor/bin/sail artisan migrate:fresh --seed
```

### 7. フロントエンド依存パッケージをインストール

```bash
./vendor/bin/sail npm install
```

### 8. フロントエンドをビルド

```bash
./vendor/bin/sail npm run build
```

## URL

- アプリ: http://localhost
- 一般ユーザーログイン: http://localhost/login
- 一般ユーザー会員登録: http://localhost/register
- 管理者ログイン: http://localhost/admin/login
- phpMyAdmin: http://localhost:8080
- MailHog: http://localhost:8025

## テストアカウント

`migrate:fresh --seed` 実行後、以下のアカウントを利用できます。

| 権限 | メールアドレス | パスワード |
| --- | --- | --- |
| 一般ユーザー | user1@example.com | password |
| 一般ユーザー | user2@example.com | password |
| 管理者 | user3@example.com | password |

## 主な機能

### 一般ユーザー

- 会員登録
- ログイン / ログアウト
- メール認証
- 出勤 / 退勤
- 休憩開始 / 休憩終了
- 月別勤怠一覧
- 勤怠詳細表示
- 勤怠修正申請
- 修正申請一覧
- マイ勤怠レポート

### 管理者

- 管理者ログイン / ログアウト
- 日別勤怠一覧
- 勤怠詳細表示
- 勤怠修正
- スタッフ一覧
- スタッフ別勤怠一覧
- スタッフ別勤怠CSV出力
- 修正申請一覧
- 修正申請承認

## API

公開APIとして、勤怠レコードの取得・登録・更新・削除を提供しています。

| メソッド | パス | 認証 | 説明 |
| --- | --- | --- | --- |
| GET | `/api/v1/attendance-records` | 不要 | 勤怠一覧取得 |
| GET | `/api/v1/attendance-records/{attendanceRecord}` | 不要 | 勤怠詳細取得 |
| POST | `/api/v1/attendance-records` | 必要 | 勤怠登録 |
| PUT/PATCH | `/api/v1/attendance-records/{attendanceRecord}` | 必要 | 勤怠更新 |
| DELETE | `/api/v1/attendance-records/{attendanceRecord}` | 必要 | 勤怠削除 |

認証が必要なAPIでは Laravel Sanctum のトークンを使用します。

## テスト

```bash
./vendor/bin/sail artisan test
```

APIテストのみ実行する場合:

```bash
./vendor/bin/sail artisan test tests/Feature/Api
```

## ER図

ER図は別途提出資料として作成しています。

タイトル: 勤怠管理アプリ ER図（データベース設計）

## メール設定

初期設定では MailHog を使用します。メール認証メールは以下で確認できます。

```text
http://localhost:8025
```

Mailtrap を使用する場合は、`.env` の `MAIL_*` を Mailtrap の SMTP 設定に変更し、設定キャッシュをクリアしてください。

```bash
./vendor/bin/sail artisan config:clear
```

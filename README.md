# 山彦旅館 KUTUROGI

旅館の予約サイトを模したポートフォリオです。客室・プランの検索、予約、Stripe（テストモード）決済、会員機能を実装しています。

架空の施設です。実在の旅館とは関係ありません。

## 採用担当者の方へ

- 本名・住所・実在のクレジットカードは入力しないでください。
- 新規会員登録は停止しています。下記のゲストアカウントで操作できます。
- デモデータは数時間ごとに初期化されます。

| 項目 | 値 |
|---|---|
| メール | `guest@example.com` |
| パスワード | `password` |
| Stripe テストカード | `4242 4242 4242 4242` |
| 有効期限 / CVC | 将来の任意月 / 任意の 3 桁 |

決済は Stripe Test Mode（`pk_test_` / `sk_test_`）のみ受け付けます。ライブキーはアプリが拒否します。

## 技術スタック

- PHP 8.3 / Laravel 13
- Inertia.js / React / TypeScript / Tailwind CSS
- SQLite（管理画面 kuturogi-admin と共有）
- Stripe API（テストモード）

## 主な機能

- 客室・プラン一覧と空き状況に応じた予約
- 料金計算（人数・泊数・シーズン・オプション）
- クレジットカード与信（Stripe）または現地払い
- 会員ログイン・プロフィール・予約一覧とキャンセル
- 管理画面向け連携 API / Webhook
- 公開デモ向けの注意書きとゲストログイン

## セットアップ

顧客サイトは **kuturogi-admin と同じ SQLite** を使います。マイグレーションとシーダーは管理画面側だけで実行してください。こちらで `migrate --seed` するとスキーマが分かれ、予約が同期されません。

```bash
cp .env.example .env
composer install
npm install
php artisan key:generate
npm run build
php artisan serve
```

`.env` の `DB_DATABASE` を kuturogi-admin の `database/database.sqlite` に合わせてください。Stripe のテストキーはリポジトリに含めません。`.env` に設定します。

依存関係が壊れているときは `rm -rf vendor && composer install` を実行してください。Xdebug の接続警告が出る場合は `XDEBUG_MODE=off php artisan serve` で起動できます。

ローカルのメールは既定で `MAIL_MAILER=log` です。会員登録のマジックリンクは `storage/logs/laravel.log` に出ます。ログには問い合わせ本文やメールアドレスは書きません。

## 環境変数

| 変数 | 用途 |
|---|---|
| `SHARED_DATABASE` | `true` のとき管理画面と同じ DB。Webhook は送らない |
| `DB_DATABASE` | admin の SQLite パス |
| `DEMO_MODE` | 注意書き・会員登録停止・初期化案内（既定 `true`） |
| `DEMO_REFRESH_HOURS` | 初期化間隔の表示用（時間、既定 4）。実処理は admin の `php artisan demo:refresh` |
| `STRIPE_KEY` / `STRIPE_SECRET` | テストモードの公開鍵・秘密鍵 |
| `INTEGRATION_API_KEY` | 管理画面連携 API（未設定なら 503） |

`APP_DEBUG=true` はローカル専用です。公開デモでは `false` にしてください。

## 構成

```
app/Http/Controllers   画面・API
app/Http/Requests      バリデーション
app/Services           料金計算・Stripe・在庫
resources/js/Pages     Inertia ページ
resources/js/Components  DemoNotice など共通 UI
```

## 公開時の注意

Laravel のドキュメントルートは `public/` です。PHP は 8.3 以上が必要です。

1. 公開フォルダを `public` にする。変えられない場合はリポジトリ直下の `.htaccess` が `public/` へ転送します。
2. サーバで `composer install --no-dev` を実行する。
3. ローカルで `npm run build` した `public/build` をアップロードする。
4. `APP_URL` を実際のサイト URL にする。`public/hot` は置かない。
5. `storage` と `bootstrap/cache` を書き込み可能にする。

サブディレクトリ公開では `APP_URL` と `ASSET_URL` を合わせ、`public/.htaccess` の `RewriteBase` を有効にしてください。

## ライセンス

無断利用を禁じます。詳細は [LICENSE](LICENSE) を参照してください。

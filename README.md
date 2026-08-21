# 山彦旅館 KUTUROGI

旅館の予約サイトを模したポートフォリオです。Laravel + Inertia.js + React で、客室検索・予約・Stripe（テストモード）決済・会員機能を実装しています。

**採用担当者の方へ:** 本名・住所・実在のクレジットカードは入力しないでください。新規会員登録は停止しています。ログイン画面の `guest@example.com` / `password` で操作できます。デモデータは数時間ごとに初期化されます。

## デモ用アカウント

| 項目 | 値 |
|---|---|
| メール | `guest@example.com` |
| パスワード | `password` |
| Stripe テストカード | `4242 4242 4242 4242` |
| 有効期限 / CVC | 将来の任意月 / 任意の3桁 |

決済は **Stripe Test Mode** のキー（`pk_test_` / `sk_test_`）のみ受け付けます。ライブキーはアプリが拒否します。

## セットアップ

顧客サイトは **kuturogi-admin と同じ SQLite** を使います。テーブル作成は管理画面側の `php artisan migrate` だけです。こちらで `migrate --seed` すると別スキーマになり、予約がまた分かれます。

```bash
cp .env.example .env
composer install
npm install
php artisan key:generate
# DB_DATABASE は kuturogi-admin の database/database.sqlite を指す
# php artisan migrate --seed  は実行しない（admin 側で migrate / db:seed）
npm run build
php artisan serve
```

`.env` に Stripe の **テストキー** を設定してください。キーはリポジトリに含めません。

ローカルのメール送信は既定で `MAIL_MAILER=log` です。会員登録のマジックリンクは `storage/logs/laravel.log` に出ます。ログには問い合わせ本文やメールアドレスは書きません（件名と文字数、エラー時の予約 ID など識別子のみ）。

## 環境変数

| 変数 | 用途 |
|---|---|
| `SHARED_DATABASE` | `true` で管理画面と同じ DB。Webhook を送らない |
| `DB_DATABASE` | admin の SQLite パス（例: `/workspaces/kuturogi-admin/database/database.sqlite`） |
| `DEMO_MODE` | 注意書き・会員登録停止・定期初期化の案内（既定 `true`） |
| `DEMO_REFRESH_HOURS` | データ初期化間隔の表示用（時間、既定 4）。実処理は **kuturogi-admin** の `php artisan demo:refresh`（cron） |

ロリポップでは、顧客サイトではなく **管理システム（kuturogi-admin）** に cron を設定します。同じ DB を共有しているため、admin を初期化すれば予約サイトのデモ予約も戻ります。手順は kuturogi-admin の README「ロリポップでの cron 設定」を参照してください。
| `STRIPE_KEY` / `STRIPE_SECRET` | テストモードの公開鍵・秘密鍵 |
| `INTEGRATION_API_KEY` | 管理画面連携 API（未設定なら 503） |

`APP_DEBUG=true` はローカル専用です。公開デモでは `false` にしてください。

## 構成

- `app/Http/Controllers` … 画面・API
- `app/Http/Requests` … バリデーション
- `app/Services` … 料金計算・Stripe・在庫
- `resources/js/Pages` … Inertia ページ
- `resources/js/Components` … `DemoNotice` など共通 UI

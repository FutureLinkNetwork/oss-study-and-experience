# study-and-experience.jp

**地方自治体がスタディークーポン（教育バウチャー）を発行・管理するためのオープンソース管理システム**

**An open-source management system for local governments to issue and manage study coupons (education vouchers)**

---

## このプロジェクトについて / About

### 日本語

study-and-experience.jp は、地方自治体がスタディークーポン（教育バウチャー）の発行・配布・利用状況の管理を行うためのウェブベースの管理システムです。本システムは「**スタエク**」という名称でサービス提供しています（「スタエク」は Study and Experience（学びと体験）に由来する愛称です）。

経済的困難を抱える家庭の子どもたちに対し、学習塾や習い事などの教育サービスを利用できるクーポンを自治体が発行・管理することで、教育機会の格差解消を支援します。

### English

study-and-experience.jp is a web-based management system that enables local governments to issue, distribute, and track study coupons (education vouchers). The system is offered under the service name "**Sutaeku**", a nickname derived from "Study and Experience".

By allowing municipalities to issue and manage coupons for educational services such as tutoring and extracurricular activities for children from economically disadvantaged families, the system helps bridge educational opportunity gaps.

### ホームページ / Home Page

スタエクのコンセプト、調達の仕組み（“伊丹方式”）、導入パターン、背景と課題、兵庫県伊丹市での事例などは、公式ホームページでご紹介しています。

- https://www.study-and-experience.jp/

For an overview of Sutaeku's concept, procurement approach (the "Itami method"), adoption patterns, background and challenges, and the case study with Itami City (Hyogo Prefecture), please see the official home page: https://www.study-and-experience.jp/

---

## 主な機能 / Key Features

### 日本語

- クーポンの発行・配布管理
- 利用状況のリアルタイム追跡
- 利用可能事業者の登録・管理
- 利用実績のレポート・集計
- 自治体職員向けダッシュボード

### English

- Coupon issuance and distribution management
- Real-time usage tracking
- Registration and management of eligible service providers
- Usage reporting and analytics
- Dashboard for municipal staff

---

## 技術スタック / Tech Stack

- Frontend: Blade テンプレート / Tailwind CSS v4 / JavaScript
- Backend: Laravel 12
- Database: MySQL 8.0

---

## はじめに / Getting Started

### 前提条件 / Prerequisites

- PHP 8.4 以上

- MySQL 8.0 以上

- Node.js 22 以上

- Composer 2 以上

### インストール / Installation

```bash
# リポジトリをクローン / Clone the repository
git clone https://github.com/FutureLinkNetwork/oss-study-and-experience.git
cd oss-study-and-experience

# 依存関係をインストール / Install dependencies
composer install
npm install

# 環境変数を設定 / Set up environment variables
cp .env.example .env
# .env を編集してください / Edit .env with your settings
# （必須）と記載ある設定はしてください。
# APP_KEYを生成
php artisan key:generate

# データベースをセットアップ / Set up the database
php artisan migrate
php artisan db:seed

# フロントエンドをビルド / Set up the FrontEnd
npm run build
```
---

## SaaS 版について / About the SaaS Version

### 日本語

自前でのインストール・運用が難しい自治体向けに、株式会社フューチャーリンクネットワークがホスティングする SaaS 版を提供しています。SaaS 版では、インフラ管理・セキュリティアップデート・バックアップ・サポートを株式会社フューチャーリンクネットワークが担当します。

SaaS 版に関するお問い合わせ: study-and-experience@ml.futurelink.co.jp

### English

For municipalities that prefer not to self-host, Future Link Network Co.,Ltd. offers a hosted SaaS version. The SaaS version includes infrastructure management, security updates, backups, and support provided by Future Link Network Co.,Ltd.

For inquiries about the SaaS version: study-and-experience@ml.futurelink.co.jp

---

## ライセンス / License

### 日本語

本プロジェクトは **GNU Affero General Public License v3.0 (AGPL-3.0)** の下で公開されています。詳細は [LICENSE](./LICENSE) をご覧ください。

AGPL-3.0 の条件が合わない組織向けに、商用ライセンスも提供しています。商用ライセンスに関するお問い合わせは study-and-experience@ml.futurelink.co.jp までご連絡ください。

### English

This project is licensed under the **GNU Affero General Public License v3.0 (AGPL-3.0)**. See [LICENSE](./LICENSE) for details.

For organizations where the AGPL-3.0 terms are not suitable, a commercial license is also available. Please contact study-and-experience@ml.futurelink.co.jp for commercial licensing inquiries.

---

## コントリビューション / Contributing

### 日本語

コントリビューションを歓迎します。コードの提出には **Contributor License Agreement (CLA)** への署名が必要です。詳細は [CONTRIBUTING.md](./CONTRIBUTING.md) をご覧ください。

### English

Contributions are welcome! Please note that a **Contributor License Agreement (CLA)** must be signed before submitting code. See [CONTRIBUTING.md](./CONTRIBUTING.md) for details.

---

## セキュリティ / Security

### 日本語

セキュリティ上の脆弱性を発見された場合は、公開の Issue ではなく、セキュリティポリシーに従って報告してください。詳細は [SECURITY.md](./SECURITY.md) をご覧ください。

### English

If you discover a security vulnerability, please report it according to our security policy rather than opening a public issue. See [SECURITY.md](./SECURITY.md) for details.

---

## 行動規範 / Code of Conduct

### 日本語

本プロジェクトでは [Contributor Covenant v2.1](https://www.contributor-covenant.org/version/2/1/code_of_conduct/) に基づく行動規範を採用しています。詳細は [CODE_OF_CONDUCT.md](./CODE_OF_CONDUCT.md) をご覧ください。

### English

This project has adopted a Code of Conduct based on the [Contributor Covenant v2.1](https://www.contributor-covenant.org/version/2/1/code_of_conduct/). See [CODE_OF_CONDUCT.md](./CODE_OF_CONDUCT.md) for details.

---

## お問い合わせ / Contact

- 一般的な質問 / General questions: GitHub Discussions
- セキュリティ報告 / Security reports: administration@ml.futurelink.co.jp
- 商用ライセンス / Commercial licensing: study-and-experience@ml.futurelink.co.jp
- 法務関連 / Legal matters: administration@ml.futurelink.co.jp

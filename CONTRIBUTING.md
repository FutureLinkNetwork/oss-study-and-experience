# コントリビューションガイド / Contributing Guide

study-and-experience.jp へのコントリビューションに興味を持っていただきありがとうございます。本ドキュメントでは、コントリビューションの方法と手順を説明します。

Thank you for your interest in contributing to study-and-experience.jp. This document describes how to contribute to this project.

---

## 目次 / Table of Contents

- [行動規範 / Code of Conduct](#行動規範--code-of-conduct)
- [CLA（コントリビューター・ライセンス契約）/ Contributor License Agreement](#cla--contributor-license-agreement)
- [コントリビューションの種類 / Types of Contributions](#コントリビューションの種類--types-of-contributions)
- [はじめての貢献 / Your First Contribution](#はじめての貢献--your-first-contribution)
- [開発の流れ / Development Workflow](#開発の流れ--development-workflow)
- [コーディング規約 / Coding Standards](#コーディング規約--coding-standards)
- [コミットメッセージ / Commit Messages](#コミットメッセージ--commit-messages)
- [Issue の書き方 / How to Write Issues](#issue-の書き方--how-to-write-issues)
- [プルリクエストの出し方 / How to Submit Pull Requests](#プルリクエストの出し方--how-to-submit-pull-requests)
- [レビュープロセス / Review Process](#レビュープロセス--review-process)
- [ライセンス / License](#ライセンス--license)

---

## 行動規範 / Code of Conduct

### 日本語

本プロジェクトでは [Contributor Covenant v2.1](./CODE_OF_CONDUCT.md) に基づく行動規範を採用しています。プロジェクトに参加するすべての方にこの行動規範を遵守していただくことをお願いします。

### English

This project follows the [Contributor Covenant v2.1](./CODE_OF_CONDUCT.md) Code of Conduct. All participants are expected to uphold this code.

---

## CLA / Contributor License Agreement

### 日本語

コードを含むプルリクエストを提出する前に、**Contributor License Agreement (CLA)** に署名する必要があります。これは、あなたの貢献を本プロジェクトおよび商用ライセンスの両方で利用できるようにするために必要な手続きです。

**署名方法:**

1. 初めてプルリクエストを提出すると、CLA assistant（bot）が自動的にコメントします
2. bot のコメント内のリンクをクリックし、GitHub アカウントで署名します
3. 署名は一度だけで、以降の全てのプルリクエストに適用されます

**CLA の要点:**

- あなたの貢献に対する著作権ライセンスと特許ライセンスをプロジェクト受領者に付与します
- あなた自身の権利は保持されます（非独占ライセンス）
- プロジェクトは AGPL-3.0 と商用ライセンスのデュアルライセンスで提供されます
- CLA の全文は [CLA-Individual.md](./CLA-Individual.md) で確認できます

**法人としての貢献:**

所属組織の業務として貢献する場合は、組織の法人用 CLA（Corporate CLA）が必要になる場合があります。詳細は administration@ml.futurelink.co.jp までお問い合わせください。

### English

Before submitting a pull request that includes code, you must sign the **Contributor License Agreement (CLA)**. This is necessary to allow your contributions to be used in both the open-source project and the commercial license.

**How to sign:**

1. When you submit your first pull request, the CLA assistant (bot) will automatically comment
2. Click the link in the bot's comment and sign with your GitHub account
3. You only need to sign once — it applies to all future pull requests

**Key points of the CLA:**

- You grant copyright and patent licenses to the project recipient for your contributions
- You retain your own rights (non-exclusive license)
- The project is offered under a dual license: AGPL-3.0 and a commercial license
- The full CLA text is available at [CLA-Individual.md](./CLA-Individual.md)

**Contributing on behalf of an organization:**

If you are contributing as part of your employment, a Corporate CLA from your organization may be required. Please contact administration@ml.futurelink.co.jp for details.

---

## コントリビューションの種類 / Types of Contributions

### 日本語

以下のような貢献を歓迎します:

- **バグ報告**: 問題を発見したら Issue を作成してください
- **機能提案**: 新しい機能のアイデアがあれば Issue で提案してください
- **コード修正**: バグ修正や機能追加のプルリクエストを歓迎します
- **ドキュメント改善**: ドキュメントの誤り修正や追記を歓迎します
- **翻訳**: 日本語・英語以外の言語への翻訳も歓迎します

### English

We welcome the following types of contributions:

- **Bug reports**: Create an Issue when you find a problem
- **Feature proposals**: Suggest new features via Issues
- **Code fixes**: Pull requests for bug fixes and new features are welcome
- **Documentation improvements**: Corrections and additions to documentation are welcome
- **Translations**: Translations into languages other than Japanese and English are welcome

---

## はじめての貢献 / Your First Contribution

### 日本語

初めて貢献する方は、`good first issue` ラベルが付いた Issue を探してみてください。これらは比較的取り組みやすいタスクです。

着手する前に、Issue にコメントして担当を宣言してください。他の方と作業が重複するのを防ぎます。

### English

If this is your first contribution, look for Issues labeled `good first issue`. These are relatively approachable tasks.

Before starting work, please comment on the Issue to claim it. This prevents duplicate efforts.

---

## 開発の流れ / Development Workflow

### 日本語

1. このリポジトリをフォークする
2. フォークからブランチを作成する（`feature/説明的な名前` または `fix/説明的な名前`）
3. ローカルで変更を実装する
4. テストを追加・実行して、既存テストが通ることを確認する
5. コミットする（コミットメッセージの規約に従ってください）
6. フォークにプッシュする
7. 本リポジトリに対してプルリクエストを作成する

### English

1. Fork this repository
2. Create a branch from your fork (`feature/descriptive-name` or `fix/descriptive-name`)
3. Implement your changes locally
4. Add and run tests to confirm that existing tests pass
5. Commit your changes (follow the commit message conventions below)
6. Push to your fork
7. Create a pull request against this repository

---

## コーディング規約 / Coding Standards

### 日本語

{{CODING_STANDARDS_JA — プロジェクトのコーディング規約を記載してください。例:}}

- リンター・フォーマッターの設定に従ってください
- 新しい機能にはテストを追加してください
- 既存のコードスタイルに合わせてください
- コメントやドキュメントは日本語・英語どちらでも構いません

### English

{{CODING_STANDARDS_EN — Describe the project coding standards. Example:}}

- Follow the linter and formatter configuration
- Add tests for new features
- Match the existing code style
- Comments and documentation may be in Japanese or English

---

## コミットメッセージ / Commit Messages

### 日本語

[Conventional Commits](https://www.conventionalcommits.org/) の形式に従ってください:

```
<type>(<scope>): <description>

[optional body]

[optional footer(s)]
```

**type の例:**

- `feat`: 新機能
- `fix`: バグ修正
- `docs`: ドキュメントのみの変更
- `style`: コードの意味に影響しない変更（フォーマット等）
- `refactor`: バグ修正でも機能追加でもないコード変更
- `test`: テストの追加・修正
- `chore`: ビルドプロセスや補助ツールの変更

**例:**

```
feat(coupon): クーポン一括発行機能を追加

自治体職員がCSVアップロードでクーポンを一括発行できるようにした。

Closes #123
```

### English

Follow the [Conventional Commits](https://www.conventionalcommits.org/) format:

```
<type>(<scope>): <description>

[optional body]

[optional footer(s)]
```

**Example types:** `feat`, `fix`, `docs`, `style`, `refactor`, `test`, `chore`

**Example:**

```
feat(coupon): add bulk coupon issuance feature

Allow municipal staff to issue coupons in bulk via CSV upload.

Closes #123
```

---

## Issue の書き方 / How to Write Issues

### 日本語

- バグ報告には [Bug Report テンプレート](./.github/ISSUE_TEMPLATE/bug_report.md) を使ってください
- 機能提案には [Feature Request テンプレート](./.github/ISSUE_TEMPLATE/feature_request.md) を使ってください
- タイトルは具体的に書いてください（例: 「クーポン一覧画面で絞り込みが効かない」）
- 再現手順、期待する動作、実際の動作を明記してください

### English

- Use the [Bug Report template](./.github/ISSUE_TEMPLATE/bug_report.md) for bug reports
- Use the [Feature Request template](./.github/ISSUE_TEMPLATE/feature_request.md) for feature proposals
- Write specific titles (e.g., "Filter not working on coupon list page")
- Include steps to reproduce, expected behavior, and actual behavior

---

## プルリクエストの出し方 / How to Submit Pull Requests

### 日本語

- [プルリクエストテンプレート](./.github/PULL_REQUEST_TEMPLATE.md) に従って記載してください
- CLA に署名済みであることを確認してください
- 関連する Issue がある場合はリンクしてください（`Closes #番号`）
- 一つのプルリクエストでは一つの論点に絞ってください
- CI が全て通ることを確認してください

### English

- Follow the [Pull Request template](./.github/PULL_REQUEST_TEMPLATE.md)
- Ensure you have signed the CLA
- Link related Issues if applicable (`Closes #number`)
- Keep each pull request focused on a single concern
- Ensure all CI checks pass

---

## レビュープロセス / Review Process

### 日本語

- プルリクエストはメンテナーがレビューします
- レビューは週に一度程度のペースで行います（即時対応はお約束できません）
- 変更の要望がある場合はコメントでお伝えします
- 全てのプルリクエストがマージされるとは限りません。プロジェクトの方向性や品質基準に合わない場合、理由を説明した上でクローズすることがあります

### English

- Pull requests are reviewed by maintainers
- Reviews are conducted approximately once a week (immediate responses are not guaranteed)
- If changes are needed, we will communicate via comments
- Not all pull requests will be merged. If a pull request does not align with the project direction or quality standards, we will explain the reason and close it

---

## ライセンス / License

### 日本語

本プロジェクトに貢献することにより、あなたの貢献は [AGPL-3.0](./LICENSE) の下でライセンスされます。また、CLA に基づき、株式会社フューチャーリンクネットワーク が商用ライセンスの下でも提供できるようになります。

### English

By contributing to this project, your contributions are licensed under [AGPL-3.0](./LICENSE). Through the CLA, Future Link Network Co.,Ltd. may also offer them under a commercial license.

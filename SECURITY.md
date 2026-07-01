# セキュリティポリシー / Security Policy

本プロジェクトは地方自治体の業務で利用されるシステムであり、住民の個人情報を扱う可能性があります。セキュリティに関する問題の取扱いには特に慎重を期しています。

This project is a system used in municipal operations and may handle residents' personal information. We take security matters with the utmost seriousness.

---

## サポート対象バージョン / Supported Versions

### 日本語

セキュリティアップデートは以下のバージョンに対して提供されます。

| バージョン | サポート状況 |
|---|---|
| 最新 | サポート対象 |
| それ以前 | サポート対象外 |

自治体など本番環境で運用されている場合は、常に最新のサポート対象バージョンへのアップデートを推奨します。

### English

Security updates are provided for the following versions:

| Version | Supported |
|---|---|
| latest | Supported |
| Earlier versions | Not supported |

For municipalities and other production environments, we recommend always updating to the latest supported version.

---

## 脆弱性の報告方法 / Reporting a Vulnerability

### 日本語

**セキュリティ上の脆弱性は、GitHub の公開 Issue で報告しないでください。**

脆弱性を発見された場合は、以下の手順で報告してください:

1. **メールで報告**: administration@ml.futurelink.co.jp 宛てにメールを送信してください
2. **件名**: `[SECURITY] 脆弱性の概要` という形式でお願いします
3. **報告内容に含めていただきたい情報**:
   - 脆弱性の種類（例: SQLインジェクション、XSS、認証バイパス等）
   - 影響を受けるコンポーネント・ファイル・エンドポイント
   - 再現手順（可能な限り詳細に）
   - 想定される影響範囲
   - 修正案（あれば）

### English

**Please do NOT report security vulnerabilities through public GitHub Issues.**

If you discover a vulnerability, please report it as follows:

1. **Report by email**: Send an email to administration@ml.futurelink.co.jp
2. **Subject line**: Use the format `[SECURITY] Brief description of vulnerability`
3. **Information to include**:
   - Type of vulnerability (e.g., SQL injection, XSS, authentication bypass)
   - Affected components, files, or endpoints
   - Steps to reproduce (as detailed as possible)
   - Estimated scope of impact
   - Suggested fix (if any)

---

## 対応プロセス / Response Process

### 日本語

1. **受領確認**: 報告受領後、**3営業日以内**にメールで受領確認を返信します
2. **トリアージ**: 報告内容を確認し、深刻度を評価します（目安: 受領から1週間以内）
3. **修正**: 深刻度に応じて修正を実施します
   - **Critical / High**: 可能な限り早急に修正パッチをリリースします
   - **Medium**: 次回の定期リリースに含めます
   - **Low**: バックログに追加し、対応時期を検討します
4. **通知**: 修正がリリースされた時点で、報告者に通知します
5. **公開**: 修正リリース後、適切なタイミングで脆弱性情報を公開します（報告者との調整の上）

### English

1. **Acknowledgment**: We will acknowledge receipt of your report by email within **3 business days**
2. **Triage**: We will review the report and assess the severity (target: within 1 week of receipt)
3. **Fix**: Fixes are prioritized based on severity:
   - **Critical / High**: A patch will be released as soon as possible
   - **Medium**: Included in the next regular release
   - **Low**: Added to the backlog for scheduling
4. **Notification**: The reporter will be notified when the fix is released
5. **Disclosure**: Vulnerability details will be publicly disclosed at an appropriate time after the fix is released (coordinated with the reporter)

---

## 自治体向けの追加ガイダンス / Additional Guidance for Municipalities

### 日本語

本システムを自治体で運用される場合、以下の点にご留意ください。

**個人情報の保護:**

- 本システムで扱うデータには住民の個人情報が含まれる可能性があります
- 各自治体の個人情報保護条例および個人情報保護法に準拠した運用をお願いします
- 本番データを含むログやスクリーンショットをバグ報告に含めないでください

**セキュリティ要件:**

- 本番環境では必ず HTTPS を使用してください
- デフォルトの管理者パスワードは必ず変更してください
- データベースのバックアップを定期的に取得してください
- アクセスログを保存し、定期的に確認してください
- {{ADDITIONAL_SECURITY_REQUIREMENTS_JA — 追加のセキュリティ要件があれば記載}}

**インシデント対応:**

- セキュリティインシデントが発生した場合、速やかに administration@ml.futurelink.co.jp にもご連絡ください
- SaaS 版をご利用の場合は、株式会社フューチャーリンクネットワーク が初動対応を支援します

### English

When operating this system in a municipal environment, please note the following:

**Protection of personal information:**

- Data handled by this system may include residents' personal information
- Please operate in compliance with your municipality's personal information protection ordinances and Japan's Act on the Protection of Personal Information
- Do not include production data, logs, or screenshots containing personal information in bug reports

**Security requirements:**

- Always use HTTPS in production environments
- Change the default administrator password immediately
- Perform regular database backups
- Retain and periodically review access logs
- {{ADDITIONAL_SECURITY_REQUIREMENTS_EN — Add any additional security requirements}}

**Incident response:**

- In the event of a security incident, please also contact administration@ml.futurelink.co.jp promptly
- If you are using the SaaS version, Future Link Network Co.,Ltd. will assist with initial response

---


## 謝辞 / Acknowledgments

### 日本語

セキュリティ脆弱性を責任ある方法で報告してくださった方々に感謝いたします。

### English

We appreciate those who responsibly report security vulnerabilities.

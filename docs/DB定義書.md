# DB定義書

習い事バウチャー管理システム（Laravel 12 / MySQL 8.0）のデータベース定義です。  
マイグレーションを元に現時点のスキーマを記載しています。

---

## テーブル一覧

| 論理名 | 物理名 | 概要 |
|--------|--------|------|
| サブドメイン | subdomains | テナント（自治体）マスタ |
| ロール | roles | ユーザーロール定義 |
| ユーザー | users | 利用者・事業者・運営者アカウント |
| コース親分類 | course_categories_parent | 習い事の親カテゴリ |
| コース分類 | course_categories | 習い事の子カテゴリ |
| 事業者情報 | business_infos | 事業者申込・登録情報 |
| 教室情報 | classroom_infos | 教室・拠点情報 |
| コース情報 | course_infos | コース・メニュー情報 |
| お知らせ | notices | お知らせ |
| 問い合わせ（お問い合わせ） | contacts | 一般問い合わせ |
| コース申込依頼 | course_requests | 教室登録申込 |
| 銀行支店マスタ | bank_branches | 全銀協フォーマット用銀行・支店 |
| 受給者 | beneficiaries | 就学援助受給者（認定済み） |
| バウチャー | vouchers | 発行済みクーポン |
| 利用者申込 | user_applications | 利用者からの申込（申請） |
| クーポン利用 | voucher_usages | クーポン利用履歴 |
| 問い合わせ（利用者等） | inquiries | 利用者・事業者からの問い合わせ |
| 支払集計 | payment_aggregates | 月別・事業者別の利用集計 |
| 会計報告ダウンロード | accounting_report_downloads | 会計報告CSV/PDFダウンロード管理 |
| 事業者支払ダウンロード | business_payment_downloads | 事業者向け支払明細PDFダウンロード管理 |
| 管理画面ダウンロード | admin_downloads | 利用者CSV月次など、管理画面からの出力S3キー・出力概要管理 |
| パスワードリセットトークン | password_reset_tokens | Laravel標準 |
| キャッシュ | cache / cache_locks | Laravel標準 |
| セッション | sessions | Laravel標準 |
| ジョブ | jobs / job_batches / failed_jobs | Laravel標準 |

---

## 1. subdomains（サブドメイン）

| 論理名 | 物理名 | 型 | NULL | デフォルト | 備考 |
|--------|--------|-----|------|------------|------|
| ID | id | BIGINT UNSIGNED | NO | AUTO | PK |
| サブドメイン | subdomain | VARCHAR(255) | NO | - | UNIQUE |
| 名前 | name | VARCHAR(255) | NO | - | |
| 説明 | description | VARCHAR(255) | YES | NULL | |
| 有効フラグ | is_active | BOOLEAN | NO | true | |
| 設定（JSON） | settings | JSON | YES | NULL | |
| クーポン金額 | voucher_amount | INT | YES | NULL | クーポン金額 |
| 有効期限（月数） | voucher_expiry | INT | YES | NULL | |
| 発行日（1-31） | voucher_publish_date | INT | YES | NULL | |
| 消費税率（%） | tax_rate | DECIMAL(5,2) | NO | 10.0 | |
| 緯度 | latitude | DECIMAL(10,8) | YES | NULL | |
| 経度 | longitude | DECIMAL(11,8) | YES | NULL | |
| 郵便番号 | postal_code | VARCHAR(8) | YES | NULL | |
| 住所 | address | VARCHAR(255) | YES | NULL | |
| 電話番号 | phone | VARCHAR(20) | YES | NULL | |
| FAX | fax | VARCHAR(20) | YES | NULL | |
| 振込日ルール | transfer_date_rule | VARCHAR(30) | YES | NULL | current_month_end / next_month_end |
| システム名 | system_name | VARCHAR(255) | YES | NULL | ページタイトル用 |
| 全銀依頼人コード | zengin_requester_code | VARCHAR(10) | YES | NULL | |
| 全銀依頼人名 | zengin_requester_name | VARCHAR(40) | YES | NULL | |
| 全銀取引金融機関番号 | zengin_bank_code | VARCHAR(4) | YES | NULL | |
| 全銀取引金融機関名 | zengin_bank_name | VARCHAR(15) | YES | NULL | |
| 全銀取引支店番号 | zengin_branch_code | VARCHAR(3) | YES | NULL | |
| 全銀取引支店名 | zengin_branch_name | VARCHAR(15) | YES | NULL | |
| 全銀預金種目 | zengin_account_type | VARCHAR(1) | YES | NULL | 1=普通 2=当座 4=貯蓄 |
| 全銀口座番号 | zengin_account_number | VARCHAR(7) | YES | NULL | |
| 作成日時 | created_at | TIMESTAMP | YES | NULL | |
| 更新日時 | updated_at | TIMESTAMP | YES | NULL | |

**インデックス**: (subdomain, is_active)

---

## 2. roles（ロール）

| 論理名 | 物理名 | 型 | NULL | デフォルト | 備考 |
|--------|--------|-----|------|------------|------|
| ID | id | BIGINT UNSIGNED | NO | AUTO | PK |
| 名前 | name | VARCHAR(255) | NO | - | UNIQUE |
| 表示名 | display_name | VARCHAR(255) | NO | - | |
| 説明 | description | VARCHAR(255) | YES | NULL | |
| グローバルフラグ | is_global | BOOLEAN | NO | false | |
| レベル | level | INT | NO | 0 | |
| 権限（JSON） | permissions | JSON | YES | NULL | |
| 有効フラグ | is_active | TINYINT | NO | 1 | |
| 作成日時 | created_at | TIMESTAMP | YES | NULL | |
| 更新日時 | updated_at | TIMESTAMP | YES | NULL | |

**インデックス**: (name, is_global)

---

## 3. users（ユーザー）

| 論理名 | 物理名 | 型 | NULL | デフォルト | 備考 |
|--------|--------|-----|------|------------|------|
| ID | id | BIGINT UNSIGNED | NO | AUTO | PK |
| サブドメインID | subdomain_id | BIGINT UNSIGNED | YES | NULL | FK→subdomains |
| ロールID | role_id | BIGINT UNSIGNED | NO | - | FK→roles |
| ログインID | login_id | VARCHAR(255) | NO | - | UNIQUE(subdomain_id, login_id) |
| 名前 | name | VARCHAR(255) | NO | - | |
| 表示名 | display_name | VARCHAR(255) | YES | NULL | |
| メールアドレス | email | VARCHAR(255) | NO | - | |
| メール確認日時 | email_verified_at | TIMESTAMP | YES | NULL | |
| パスワード | password | VARCHAR(255) | NO | - | |
| リメンバートークン | remember_token | VARCHAR(100) | YES | NULL | |
| パスワードリセットトークン有効期限 | password_reset_token_expires_at | TIMESTAMP | YES | NULL | |
| 有効フラグ | is_active | TINYINT | NO | 1 | |
| 最終ログイン日時 | last_login_at | TIMESTAMP | YES | NULL | |
| 最終ログインIP | last_login_ip | VARCHAR(255) | YES | NULL | |
| 作成日時 | created_at | TIMESTAMP | YES | NULL | |
| 更新日時 | updated_at | TIMESTAMP | YES | NULL | |

**ユニーク**: (subdomain_id, login_id)  
**インデックス**: (subdomain_id, role_id, is_active), (email, subdomain_id), (subdomain_id, login_id)

---

## 4. course_categories_parent（コース親分類）

| 論理名 | 物理名 | 型 | NULL | デフォルト | 備考 |
|--------|--------|-----|------|------------|------|
| ID | id | BIGINT UNSIGNED | NO | AUTO | PK |
| サブドメインID | subdomain_id | BIGINT UNSIGNED | NO | - | FK→subdomains |
| 親分類名 | name | VARCHAR(100) | NO | - | |
| 表示順 | sort_order | INT | NO | 0 | |
| 有効フラグ | is_active | BOOLEAN | NO | true | |
| 作成者ID | created_user_id | BIGINT UNSIGNED | NO | - | FK→users |
| 更新者ID | updated_user_id | BIGINT UNSIGNED | NO | - | FK→users |
| 作成日時 | created_at | TIMESTAMP | YES | NULL | |
| 更新日時 | updated_at | TIMESTAMP | YES | NULL | |

**インデックス**: (subdomain_id, is_active), (subdomain_id, sort_order)

---

## 5. course_categories（コース分類）

| 論理名 | 物理名 | 型 | NULL | デフォルト | 備考 |
|--------|--------|-----|------|------------|------|
| ID | id | BIGINT UNSIGNED | NO | AUTO | PK |
| サブドメインID | subdomain_id | BIGINT UNSIGNED | NO | - | FK→subdomains |
| 親分類ID | parent_category_id | BIGINT UNSIGNED | NO | - | FK→course_categories_parent |
| 分類名 | name | VARCHAR(100) | NO | - | |
| 表示順 | sort_order | INT | NO | 0 | |
| 有効フラグ | is_active | BOOLEAN | NO | true | |
| 作成者ID | created_user_id | BIGINT UNSIGNED | NO | - | FK→users |
| 更新者ID | updated_user_id | BIGINT UNSIGNED | NO | - | FK→users |
| 作成日時 | created_at | TIMESTAMP | YES | NULL | |
| 更新日時 | updated_at | TIMESTAMP | YES | NULL | |

**インデックス**: (parent_category_id, is_active), (subdomain_id, is_active), (parent_category_id, sort_order)

---

## 6. business_infos（事業者情報）

| 論理名 | 物理名 | 型 | NULL | デフォルト | 備考 |
|--------|--------|-----|------|------------|------|
| ID | id | BIGINT UNSIGNED | NO | AUTO | PK |
| ユーザーID | user_id | BIGINT UNSIGNED | YES | NULL | FK→users |
| サブドメインID | subdomain_id | BIGINT UNSIGNED | NO | - | FK→subdomains |
| 申請者種別 | applicant_type | VARCHAR(20) | NO | - | individual / corporation / voluntary_group / government_agency |
| 暴力団排除誓約 | antisocial_forces_pledged | BOOLEAN | NO | false | |
| プライバシーポリシー同意 | privacy_policy_agreed | BOOLEAN | NO | false | |
| 事業者名 | business_name | VARCHAR(100) | NO | - | |
| 事業者名カナ | business_name_kana | VARCHAR(100) | NO | - | |
| 代表者役職名 | representative_title | VARCHAR(50) | YES | NULL | |
| 代表者姓 | representative_family_name | VARCHAR(50) | YES | NULL | |
| 代表者名 | representative_given_name | VARCHAR(50) | YES | NULL | |
| 代表者名（旧形式・移行用） | representative_name | VARCHAR(50) | NO | - | 旧: 氏名フルを1列で保持（当面移行用に残置） |
| 代表者役職名カナ | representative_title_kana | VARCHAR(50) | YES | NULL | |
| 代表者姓カナ | representative_family_name_kana | VARCHAR(50) | YES | NULL | |
| 代表者名カナ | representative_given_name_kana | VARCHAR(50) | YES | NULL | |
| 代表者名カナ（旧形式・移行用） | representative_name_kana | VARCHAR(50) | NO | - | 旧: 氏名カナフルを1列で保持（当面移行用に残置） |
| 郵便番号 | postal_code | VARCHAR(8) | NO | - | |
| 都道府県 | prefecture | VARCHAR(10) | NO | - | |
| 市区町村 | city | VARCHAR(50) | NO | - | |
| 住所1 | address1 | VARCHAR(100) | NO | - | |
| 建物名 | building_name | VARCHAR(100) | YES | NULL | |
| 電話番号 | phone | VARCHAR(20) | NO | - | |
| FAX | fax | VARCHAR(20) | YES | NULL | |
| メールアドレス | email | VARCHAR(255) | NO | - | UNIQUE |
| ウェブサイトURL | website_url | VARCHAR(255) | YES | NULL | |
| クーポン受付お知らせ | email_timing | VARCHAR(50) | NO | immediate | immediate / daily / none |
| 担当者名 | contact_person | VARCHAR(50) | YES | NULL | |
| 担当者電話 | contact_phone | VARCHAR(20) | YES | NULL | |
| 文書送付先担当者 | document_person | VARCHAR(50) | YES | NULL | |
| 文書送付先住所 | document_address | VARCHAR(255) | YES | NULL | |
| 営業時間 | business_hours | VARCHAR(255) | YES | NULL | |
| 休業日 | holiday | VARCHAR(255) | YES | NULL | |
| 銀行コード | bank_code | VARCHAR(50) | YES | NULL | |
| 支店コード | branch_code | VARCHAR(50) | YES | NULL | |
| 口座種目 | account_type | VARCHAR(10) | YES | NULL | |
| 口座番号 | account_number | VARCHAR(20) | YES | NULL | |
| 口座名義 | account_holder_name | VARCHAR(50) | YES | NULL | |
| 申請書類（JSON） | documents | JSON | YES | NULL | キー別 s3_key, original_filename 等 |
| 申込フラグ | apply | TINYINT | NO | 0 | |
| ステータス | status | VARCHAR(50) | NO | 未着手 | |
| 審査通過日 | approved_at | DATE | YES | NULL | 審査②通過／審査通過メール送信済／利用中になった日 |
| QR決済のみフラグ | qr_only | BOOLEAN | NO | false | |
| 公金振替対象 | is_public_funds_transfer_target | BOOLEAN | NO | false | 管理者のみ編集 |
| 管理者用備考 | admin_remarks | TEXT | YES | NULL | 管理者のみ編集 |
| 管理者用添付（JSON） | admin_attachments | JSON | YES | NULL | s3_key, size, original_filename, mime_type の配列、最大5件 |
| 有効フラグ | is_active | TINYINT | NO | 1 | |
| 作成者 | created_user | BIGINT UNSIGNED | YES | NULL | FK→users |
| 更新者 | updated_user | BIGINT UNSIGNED | YES | NULL | FK→users |
| 作成日時 | created_at | TIMESTAMP | YES | NULL | |
| 更新日時 | updated_at | TIMESTAMP | YES | NULL | |

**インデックス**: (subdomain_id, is_active), (apply), (status)

---

## 7. classroom_infos（教室情報）

| 論理名 | 物理名 | 型 | NULL | デフォルト | 備考 |
|--------|--------|-----|------|------------|------|
| ID | id | BIGINT UNSIGNED | NO | AUTO | PK |
| 事業者情報ID | business_info_id | BIGINT UNSIGNED | NO | - | FK→business_infos |
| 教室名 | classroom_name | VARCHAR(100) | NO | - | |
| 教室名カナ | classroom_name_kana | VARCHAR(100) | YES | NULL | |
| 教室代表者名 | classroom_representative_name | VARCHAR(50) | YES | NULL | |
| 教室代表者名カナ | classroom_representative_name_kana | VARCHAR(50) | YES | NULL | |
| 教室郵便番号 | classroom_postal_code | VARCHAR(10) | YES | NULL | |
| 教室都道府県 | classroom_prefecture | VARCHAR(20) | YES | NULL | |
| 教室市区町村 | classroom_city | VARCHAR(50) | YES | NULL | |
| 教室住所1 | classroom_address1 | VARCHAR(100) | YES | NULL | |
| 教室建物名 | classroom_building_name | VARCHAR(100) | YES | NULL | |
| 教室緯度 | classroom_latitude | DECIMAL(10,8) | YES | NULL | |
| 教室経度 | classroom_longitude | DECIMAL(11,8) | YES | NULL | |
| 地図利用フラグ | use_map | BOOLEAN | NO | true | |
| 教室電話 | classroom_phone | VARCHAR(20) | YES | NULL | |
| 教室FAX | classroom_fax | VARCHAR(20) | YES | NULL | |
| 教室メール | classroom_email | VARCHAR(255) | YES | NULL | |
| 営業時間 | business_hours | VARCHAR(200) | YES | NULL | |
| 休業日 | holiday | VARCHAR(100) | YES | NULL | |
| 教室紹介 | classroom_introduction | TEXT | YES | NULL | |
| サービス種別 | service_type | VARCHAR(50) | YES | NULL | |
| レッスンカテゴリ | lesson_category | INT | YES | NULL | |
| レッスンカテゴリその他 | lesson_category_other | VARCHAR(255) | YES | NULL | |
| 教室画像元ファイル名 | classroom_image_original_filename | VARCHAR(255) | YES | NULL | |
| 教室画像S3キー | classroom_image_s3_key | VARCHAR(255) | YES | NULL | |
| 教室画像ファイルサイズ | classroom_image_file_size | INT | YES | NULL | |
| 教室画像MIME | classroom_image_mime_type | VARCHAR(255) | YES | NULL | |
| 教室画像サムネイルS3キー | classroom_image_thumbnail_s3_key | VARCHAR(255) | YES | NULL | |
| 教室画像中サイズS3キー | classroom_image_medium_s3_key | VARCHAR(255) | YES | NULL | |
| 申込フラグ | apply | TINYINT | NO | 0 | |
| 有効フラグ | is_active | TINYINT | NO | 1 | |
| QR決済のみフラグ | qr_only | BOOLEAN | NO | false | |
| 作成者 | created_user | BIGINT UNSIGNED | YES | NULL | FK→users |
| 更新者 | updated_user | BIGINT UNSIGNED | YES | NULL | FK→users |
| 作成日時 | created_at | TIMESTAMP | YES | NULL | |
| 更新日時 | updated_at | TIMESTAMP | YES | NULL | |

**インデックス**: (business_info_id, is_active), (apply)

---

## 8. course_infos（コース情報）

| 論理名 | 物理名 | 型 | NULL | デフォルト | 備考 |
|--------|--------|-----|------|------------|------|
| ID | id | BIGINT UNSIGNED | NO | AUTO | PK |
| 事業者情報ID | business_info_id | BIGINT UNSIGNED | NO | - | FK→business_infos |
| 教室情報ID | classroom_info_id | BIGINT UNSIGNED | NO | - | FK→classroom_infos |
| コース名 | course_name | VARCHAR(100) | NO | - | |
| コース説明 | course_description | TEXT | YES | NULL | |
| 対象学年（JSON） | grades | JSON | YES | NULL | |
| 価格 | price | INT | NO | - | |
| 税区分 | tax_type | VARCHAR(255) | YES | NULL | |
| 開始日 | open_date | DATE | YES | NULL | |
| 終了日 | end_date | DATE | YES | NULL | |
| 有効フラグ | is_active | TINYINT | NO | 1 | |
| 作成者 | created_user | BIGINT UNSIGNED | YES | NULL | FK→users |
| 更新者 | updated_user | BIGINT UNSIGNED | YES | NULL | FK→users |
| 作成日時 | created_at | TIMESTAMP | YES | NULL | |
| 更新日時 | updated_at | TIMESTAMP | YES | NULL | |

**インデックス**: (business_info_id, is_active), (classroom_info_id, is_active)

---

## 9. notices（お知らせ）

| 論理名 | 物理名 | 型 | NULL | デフォルト | 備考 |
|--------|--------|-----|------|------------|------|
| ID | id | BIGINT UNSIGNED | NO | AUTO | PK |
| サブドメインID | subdomain_id | BIGINT UNSIGNED | NO | - | FK→subdomains |
| タイトル | title | VARCHAR(255) | NO | - | |
| 本文 | content | TEXT | NO | - | |
| お知らせ日 | notice_date | DATE | NO | - | |
| 公開開始日時 | publish_start_at | DATETIME | YES | NULL | |
| 公開終了日時 | publish_end_at | DATETIME | YES | NULL | |
| 住所 | address | VARCHAR(500) | YES | NULL | |
| 緯度 | latitude | DECIMAL(10,8) | YES | NULL | |
| 経度 | longitude | DECIMAL(11,8) | YES | NULL | |
| リンクURL | link_url | VARCHAR(1000) | YES | NULL | |
| 添付S3キー | attachment_s3_key | VARCHAR(255) | YES | NULL | |
| 添付元ファイル名 | attachment_original_filename | VARCHAR(255) | YES | NULL | |
| 添付ファイルサイズ | attachment_file_size | BIGINT UNSIGNED | YES | NULL | |
| 添付MIME | attachment_mime_type | VARCHAR(255) | YES | NULL | |
| 公開表示 | show_on_public | BOOLEAN | NO | false | |
| 利用者ダッシュボード表示 | show_on_user_dashboard | BOOLEAN | NO | false | |
| 事業者ダッシュボード表示 | show_on_business_dashboard | BOOLEAN | NO | false | |
| 削除フラグ | is_deleted | BOOLEAN | NO | false | |
| 作成者 | created_user | BIGINT UNSIGNED | YES | NULL | FK→users |
| 更新者 | updated_user | BIGINT UNSIGNED | YES | NULL | FK→users |
| 作成日時 | created_at | TIMESTAMP | YES | NULL | |
| 更新日時 | updated_at | TIMESTAMP | YES | NULL | |

**インデックス**: (subdomain_id, is_deleted), (notice_date), (publish_start_at, publish_end_at)

---

## 10. contacts（問い合わせ・お問い合わせ）

| 論理名 | 物理名 | 型 | NULL | デフォルト | 備考 |
|--------|--------|-----|------|------------|------|
| ID | id | BIGINT UNSIGNED | NO | AUTO | PK |
| サブドメインID | subdomain_id | BIGINT UNSIGNED | YES | NULL | FK→subdomains |
| 名前 | name | VARCHAR(100) | NO | - | |
| メール | email | VARCHAR(255) | NO | - | |
| 電話 | phone | VARCHAR(20) | NO | - | |
| 内容 | content | TEXT | NO | - | |
| IPアドレス | ip_address | VARCHAR(45) | YES | NULL | |
| 確認済みフラグ | is_confirmed | BOOLEAN | NO | false | |
| 備考 | remarks | TEXT | YES | NULL | |
| 更新者ID | updated_user_id | BIGINT UNSIGNED | YES | NULL | FK→users |
| 作成日時 | created_at | TIMESTAMP | YES | NULL | |
| 更新日時 | updated_at | TIMESTAMP | YES | NULL | |

---

## 11. course_requests（コース申込依頼）

| 論理名 | 物理名 | 型 | NULL | デフォルト | 備考 |
|--------|--------|-----|------|------------|------|
| ID | id | BIGINT UNSIGNED | NO | AUTO | PK |
| サブドメインID | subdomain_id | BIGINT UNSIGNED | YES | NULL | FK→subdomains |
| 教室名 | classroom_name | VARCHAR(100) | NO | - | |
| 住所 | address | TEXT | NO | - | |
| 電話 | phone | VARCHAR(20) | NO | - | |
| 申込者名 | requester_name | VARCHAR(100) | NO | - | |
| 申込者メール | requester_email | VARCHAR(255) | NO | - | |
| 申込者電話 | requester_phone | VARCHAR(20) | YES | NULL | |
| IPアドレス | ip_address | VARCHAR(45) | YES | NULL | |
| 確認済みフラグ | is_confirmed | BOOLEAN | NO | false | |
| 備考 | remarks | TEXT | YES | NULL | |
| 更新者ID | updated_user_id | BIGINT UNSIGNED | YES | NULL | FK→users |
| 作成日時 | created_at | TIMESTAMP | YES | NULL | |
| 更新日時 | updated_at | TIMESTAMP | YES | NULL | |

---

## 12. bank_branches（銀行支店マスタ）

| 論理名 | 物理名 | 型 | NULL | デフォルト | 備考 |
|--------|--------|-----|------|------------|------|
| ID | id | BIGINT UNSIGNED | NO | AUTO | PK |
| 管理コード | management_code | VARCHAR(8) | NO | - | |
| 銀行番号 | bank_code | VARCHAR(4) | NO | - | |
| 銀行名 | bank_name | VARCHAR(100) | NO | - | |
| 銀行名カナ | bank_name_kana | VARCHAR(100) | NO | - | |
| 支店番号 | branch_code | VARCHAR(3) | NO | - | |
| 支店名 | branch_name | VARCHAR(100) | NO | - | |
| 支店名カナ | branch_name_kana | VARCHAR(100) | NO | - | |
| 作成日時 | created_at | TIMESTAMP | YES | NULL | |
| 更新日時 | updated_at | TIMESTAMP | YES | NULL | |

**インデックス**: (bank_code, branch_code), management_code, bank_code, bank_name, bank_name_kana, branch_name, branch_name_kana

---

## 13. beneficiaries（受給者）

| 論理名 | 物理名 | 型 | NULL | デフォルト | 備考 |
|--------|--------|-----|------|------------|------|
| ID | id | BIGINT UNSIGNED | NO | AUTO | PK |
| サブドメインID | subdomain_id | BIGINT UNSIGNED | YES | NULL | FK→subdomains |
| ユーザーID | user_id | BIGINT UNSIGNED | YES | NULL | FK→users |
| こどもID | child_id | VARCHAR(100) | YES | NULL | 外部連携用 |
| 就学援助認定番号 | certification_number | VARCHAR(255) | NO | - | |
| 保護者名 | guardian_name | VARCHAR(255) | NO | - | |
| 保護者名カナ | guardian_name_kana | VARCHAR(100) | YES | NULL | |
| 保護者生年月日 | guardian_birth_date | DATE | NO | - | |
| 住所 | guardian_address | TEXT | NO | - | |
| 電話番号 | guardian_phone | VARCHAR(255) | NO | - | |
| メール | guardian_email | VARCHAR(255) | NO | - | |
| 対象児童名 | child_name | VARCHAR(255) | NO | - | |
| 対象児童名カナ | child_name_kana | VARCHAR(100) | YES | NULL | |
| 対象児童生年月日 | child_birth_date | DATE | NO | - | |
| 小学校名 | elementary_school_name | VARCHAR(255) | NO | - | |
| 学年 | grade | VARCHAR(255) | NO | - | |
| 対象児童住所 | child_address | TEXT | NO | - | |
| 申請者と同一住所 | child_address_same_as_guardian | BOOLEAN | NO | false | |
| 自治体住所登録・就学援助受給 | child_registered_in_municipality_and_receiving_scholarship | BOOLEAN | NO | false | |
| 調査同意 | survey_consent | BOOLEAN | NO | false | |
| 教室名1〜3・所在地・電話・担当者 | classroom_name_1〜3, classroom_location_1〜3, ... | VARCHAR | YES | NULL | 最大3教室 |
| 申請日 | application_date | DATE | NO | - | |
| 資格喪失日 | disqualification_date | DATE | YES | NULL | |
| ステータス | status | VARCHAR(50) | NO | 決定通知書未送信 | 例: 資格喪失 |
| システムメッセージ | system_message | TEXT | YES | NULL | 失敗理由等 |
| ラベル | labels | VARCHAR(255) | YES | NULL | カンマ区切り |
| 備考 | remarks | TEXT | YES | NULL | 運営者備考（管理者画面で編集） |
| 認定日 | certification_date | DATE | YES | NULL | 就学援助認定日 |
| 作成日時 | created_at | TIMESTAMP | YES | NULL | |
| 更新日時 | updated_at | TIMESTAMP | YES | NULL | |

**インデックス**: (subdomain_id, status), (application_date), (certification_number)

---

## 14. vouchers（バウチャー）

| 論理名 | 物理名 | 型 | NULL | デフォルト | 備考 |
|--------|--------|-----|------|------------|------|
| ID | id | BIGINT UNSIGNED | NO | AUTO | PK |
| 受給者ID | beneficiary_id | BIGINT UNSIGNED | NO | - | FK→beneficiaries |
| サブドメインID | subdomain_id | BIGINT UNSIGNED | YES | NULL | FK→subdomains |
| クーポン番号 | voucher_number | VARCHAR(255) | NO | - | UNIQUE |
| 発行日 | issue_date | DATE | NO | - | |
| 有効期限 | expiry_date | DATE | NO | - | |
| 利用金額 | amount | INT | NO | - | |
| 状態 | status | ENUM | NO | unused | unused / used / expired |
| 作成日時 | created_at | TIMESTAMP | YES | NULL | |
| 更新日時 | updated_at | TIMESTAMP | YES | NULL | |

**インデックス**: (beneficiary_id, status), (subdomain_id, status), (voucher_number)

---

## 15. user_applications（利用者申込）

| 論理名 | 物理名 | 型 | NULL | デフォルト | 備考 |
|--------|--------|-----|------|------------|------|
| ID | id | BIGINT UNSIGNED | NO | AUTO | PK |
| サブドメインID | subdomain_id | BIGINT UNSIGNED | YES | NULL | FK→subdomains |
| 認定番号 | certification_number | VARCHAR(255) | NO | - | |
| 保護者名 | guardian_name | VARCHAR(255) | NO | - | |
| 保護者名カナ | guardian_name_kana | VARCHAR(100) | YES | NULL | |
| 保護者生年月日 | guardian_birth_date | DATE | NO | - | |
| 住所 | guardian_address | TEXT | NO | - | |
| 電話番号 | guardian_phone | VARCHAR(255) | NO | - | |
| メール | guardian_email | VARCHAR(255) | NO | - | |
| 対象児童名 | child_name | VARCHAR(255) | NO | - | |
| 対象児童名カナ | child_name_kana | VARCHAR(100) | YES | NULL | |
| 対象児童生年月日 | child_birth_date | DATE | NO | - | |
| 小学校名 | elementary_school_name | VARCHAR(255) | NO | - | |
| 学年 | grade | VARCHAR(255) | NO | - | |
| 対象児童住所 | child_address | TEXT | NO | - | |
| 申請者と同一住所 | child_address_same_as_guardian | BOOLEAN | NO | false | |
| 自治体住所登録・就学援助受給 | child_registered_in_municipality_and_receiving_scholarship | BOOLEAN | NO | false | |
| 調査同意 | survey_consent | BOOLEAN | NO | false | |
| プライバシーポリシー同意 | privacy_policy_agreed | BOOLEAN | NO | false | |
| 教室名1〜3・所在地・電話・担当者 | classroom_name_1〜3, ... | VARCHAR | YES | NULL | 最大3教室 |
| 添付S3キー | document_s3_key | VARCHAR(255) | YES | NULL | |
| 添付元ファイル名 | document_original_filename | VARCHAR(255) | YES | NULL | |
| 添付ファイルサイズ | document_file_size | INT | YES | NULL | |
| 添付MIME | document_mime_type | VARCHAR(255) | YES | NULL | |
| 出力済みフラグ | is_exported | BOOLEAN | NO | false | |
| ダウンロード対象外フラグ | is_excluded_from_download | BOOLEAN | NO | false | |
| 備考（運営用） | admin_remarks | TEXT | YES | NULL | |
| 作成日時 | created_at | TIMESTAMP | YES | NULL | |
| 更新日時 | updated_at | TIMESTAMP | YES | NULL | |

**インデックス**: (subdomain_id, is_exported), (created_at)

---

## 16. voucher_usages（クーポン利用）

| 論理名 | 物理名 | 型 | NULL | デフォルト | 備考 |
|--------|--------|-----|------|------------|------|
| ID | id | BIGINT UNSIGNED | NO | AUTO | PK |
| 利用ユーザーID | user_id | BIGINT UNSIGNED | NO | - | FK→users（バウチャー利用者） |
| サブドメインID | subdomain_id | BIGINT UNSIGNED | YES | NULL | FK→subdomains |
| 事業者ID | business_info_id | BIGINT UNSIGNED | NO | - | FK→business_infos |
| 教室ID | classroom_info_id | BIGINT UNSIGNED | NO | - | FK→classroom_infos |
| コースID | course_info_id | BIGINT UNSIGNED | YES | NULL | FK→course_infos（金額指定利用時はNULL） |
| 利用金額 | amount | INT | NO | - | |
| 利用日時 | used_at | TIMESTAMP | NO | - | |
| 備考 | memo | TEXT | YES | NULL | |
| 事業者メモ | business_memo | TEXT | YES | NULL | |
| 管理者修正メモ | admin_correction_memo | TEXT | YES | NULL | |
| 管理者修正日時 | admin_corrected_at | TIMESTAMP | YES | NULL | |
| QR使用フラグ | qr_flag | BOOLEAN | NO | false | |
| キャンセルフラグ | is_cancelled | BOOLEAN | NO | false | |
| キャンセル実行者ID | cancelled_by_user_id | BIGINT UNSIGNED | YES | NULL | FK→users |
| キャンセル日時 | cancelled_at | TIMESTAMP | YES | NULL | |
| 作成日時 | created_at | TIMESTAMP | YES | NULL | |
| 更新日時 | updated_at | TIMESTAMP | YES | NULL | |

**インデックス**: (user_id, is_cancelled), (course_info_id, is_cancelled), (used_at)

---

## 17. inquiries（問い合わせ・利用者等）

| 論理名 | 物理名 | 型 | NULL | デフォルト | 備考 |
|--------|--------|-----|------|------------|------|
| ID | id | BIGINT UNSIGNED | NO | AUTO | PK |
| サブドメインID | subdomain_id | BIGINT UNSIGNED | NO | - | FK→subdomains |
| ユーザーID | user_id | BIGINT UNSIGNED | NO | - | FK→users |
| 問い合わせ種別 | inquiry_type | VARCHAR(20) | NO | - | |
| 内容 | content | TEXT | NO | - | |
| ステータス | status | VARCHAR(20) | NO | pending | |
| 備考 | remarks | TEXT | YES | NULL | |
| 作成者ID | created_user_id | BIGINT UNSIGNED | NO | - | FK→users |
| 更新者ID | updated_user_id | BIGINT UNSIGNED | YES | NULL | FK→users |
| 作成日時 | created_at | TIMESTAMP | YES | NULL | |
| 更新日時 | updated_at | TIMESTAMP | YES | NULL | |

**インデックス**: (subdomain_id, created_at), (user_id), (status)

---

## 18. payment_aggregates（支払集計）

| 論理名 | 物理名 | 型 | NULL | デフォルト | 備考 |
|--------|--------|-----|------|------------|------|
| ID | id | BIGINT UNSIGNED | NO | AUTO | PK |
| 対象月（月初日） | target_month | DATE | NO | - | |
| サブドメインID | subdomain_id | BIGINT UNSIGNED | NO | - | FK→subdomains |
| 事業者ID | business_info_id | BIGINT UNSIGNED | NO | - | FK→business_infos |
| 教室ID | classroom_info_id | BIGINT UNSIGNED | NO | - | FK→classroom_infos |
| 申込件数 | application_count | INT UNSIGNED | NO | 0 | |
| クーポン利用額合計 | total_amount | INT UNSIGNED | NO | 0 | |
| 公金振替対象 | is_public_funds_transfer_target | BOOLEAN | NO | false | 集計時点の事業者マスタ値 |
| 作成日時 | created_at | TIMESTAMP | YES | NULL | |
| 更新日時 | updated_at | TIMESTAMP | YES | NULL | |

**ユニーク**: (target_month, subdomain_id, business_info_id, classroom_info_id)  
**インデックス**: (target_month, subdomain_id), (business_info_id, target_month)

---

## 19. accounting_report_downloads（会計報告ダウンロード）

| 論理名 | 物理名 | 型 | NULL | デフォルト | 備考 |
|--------|--------|-----|------|------------|------|
| ID | id | BIGINT UNSIGNED | NO | AUTO | PK |
| サブドメインID | subdomain_id | BIGINT UNSIGNED | NO | - | FK→subdomains |
| 対象月（月初日） | target_month | DATE | NO | - | |
| CSV S3キー（公金振替対象） | csv_s3_key | VARCHAR(255) | YES | NULL | |
| PDF S3キー（公金振替対象） | pdf_s3_key | VARCHAR(255) | YES | NULL | |
| CSVダウンロード日時（公金振替対象） | csv_downloaded_at | DATETIME | YES | NULL | |
| CSVダウンロード者ID（公金振替対象） | csv_downloaded_by_user_id | BIGINT UNSIGNED | YES | NULL | FK→users |
| PDFダウンロード日時（公金振替対象） | pdf_downloaded_at | DATETIME | YES | NULL | |
| PDFダウンロード者ID（公金振替対象） | pdf_downloaded_by_user_id | BIGINT UNSIGNED | YES | NULL | FK→users |
| CSV S3キー（公金振替対象外） | csv_s3_key_non_target | VARCHAR(255) | YES | NULL | |
| PDF S3キー（公金振替対象外） | pdf_s3_key_non_target | VARCHAR(255) | YES | NULL | |
| CSVダウンロード日時（公金振替対象外） | csv_non_target_downloaded_at | DATETIME | YES | NULL | |
| CSVダウンロード者ID（公金振替対象外） | csv_non_target_downloaded_by_user_id | BIGINT UNSIGNED | YES | NULL | FK→users |
| PDFダウンロード日時（公金振替対象外） | pdf_non_target_downloaded_at | DATETIME | YES | NULL | |
| PDFダウンロード者ID（公金振替対象外） | pdf_non_target_downloaded_by_user_id | BIGINT UNSIGNED | YES | NULL | FK→users |
| 作成日時 | created_at | TIMESTAMP | YES | NULL | |
| 更新日時 | updated_at | TIMESTAMP | YES | NULL | |

**ユニーク**: (subdomain_id, target_month)

---

## 20. business_payment_downloads（事業者支払ダウンロード）

| 論理名 | 物理名 | 型 | NULL | デフォルト | 備考 |
|--------|--------|-----|------|------------|------|
| ID | id | BIGINT UNSIGNED | NO | AUTO | PK |
| サブドメインID | subdomain_id | BIGINT UNSIGNED | NO | - | FK→subdomains |
| 事業者ID | business_info_id | BIGINT UNSIGNED | NO | - | FK→business_infos |
| 支払対象月（月初日） | target_month | DATE | NO | - | |
| 初回PDFダウンロード日時 | downloaded_at | DATETIME | YES | NULL | |
| 作成日時 | created_at | TIMESTAMP | YES | NULL | |
| 更新日時 | updated_at | TIMESTAMP | YES | NULL | |

**ユニーク**: (subdomain_id, business_info_id, target_month)  
**インデックス**: (subdomain_id, business_info_id)

---

## 21. admin_downloads（管理画面ダウンロード）

| 論理名 | 物理名 | 型 | NULL | デフォルト | 備考 |
|--------|--------|-----|------|------------|------|
| ID | id | BIGINT UNSIGNED | NO | AUTO | PK |
| サブドメインID | subdomain_id | BIGINT UNSIGNED | NO | - | FK→subdomains |
| 出力日時 | exported_at | DATETIME | NO | - | バッチ実行日時（実際に出力した日時） |
| 出力概要 | summary | VARCHAR(255) | NO | - | 自動設定された説明文 |
| S3キー | s3_key | VARCHAR(255) | NO | - | 出力ファイルのS3オブジェクトキー |
| 出力種別 | download_type | VARCHAR(50) | YES | NULL | user_application / beneficiary / contact / inquiry |
| 作成日時 | created_at | TIMESTAMP | YES | NULL | |

**インデックス**: (subdomain_id, exported_at) など検索用を必要に応じて設定

---

## 更新履歴

- 初版: マイグレーション（2026年3月時点）に基づき作成
- 2026-03: admin_downloads に download_type 追加（お問い合わせCSV月次バッチ用）
- 2026-03: admin_downloads.download_type に inquiry（問い合わせ・利用者・事業者CSV月次）を追記

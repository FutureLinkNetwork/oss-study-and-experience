<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Schema statements from Navicat dump (fln_voucher.sql). MySQL / MariaDB only.
     */
    public function up(): void
    {
        if (! in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        foreach ($this->createTableStatements() as $sql) {
            DB::unprepared($sql);
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    /**
     * @return list<string>
     */
    private function createTableStatements(): array
    {
        return [
            'CREATE TABLE `accounting_report_downloads` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `subdomain_id` bigint unsigned NOT NULL,
  `target_month` date NOT NULL COMMENT \'対象月（月初日）\',
  `csv_s3_key` varchar(255) COLLATE utf8mb4_bin DEFAULT NULL COMMENT \'CSVファイルのS3キー\',
  `pdf_s3_key` varchar(255) COLLATE utf8mb4_bin DEFAULT NULL COMMENT \'PDFファイルのS3キー\',
  `csv_downloaded_at` datetime DEFAULT NULL COMMENT \'CSVダウンロード日時\',
  `csv_downloaded_by_user_id` bigint unsigned DEFAULT NULL,
  `pdf_downloaded_at` datetime DEFAULT NULL COMMENT \'PDFダウンロード日時\',
  `pdf_downloaded_by_user_id` bigint unsigned DEFAULT NULL,
  `csv_s3_key_non_target` varchar(255) COLLATE utf8mb4_bin DEFAULT NULL COMMENT \'公金振替対象外 CSV S3キー\',
  `pdf_s3_key_non_target` varchar(255) COLLATE utf8mb4_bin DEFAULT NULL COMMENT \'公金振替対象外 PDF S3キー\',
  `csv_non_target_downloaded_at` datetime DEFAULT NULL COMMENT \'公金振替対象外 CSVダウンロード日時\',
  `csv_non_target_downloaded_by_user_id` bigint unsigned DEFAULT NULL COMMENT \'公金振替対象外 CSVダウンロード者ID\',
  `pdf_non_target_downloaded_at` datetime DEFAULT NULL COMMENT \'公金振替対象外 PDFダウンロード日時\',
  `pdf_non_target_downloaded_by_user_id` bigint unsigned DEFAULT NULL COMMENT \'公金振替対象外 PDFダウンロード者ID\',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `accounting_report_dl_sub_month_uniq` (`subdomain_id`,`target_month`),
  KEY `accounting_report_downloads_csv_downloaded_by_user_id_foreign` (`csv_downloaded_by_user_id`),
  KEY `accounting_report_downloads_pdf_downloaded_by_user_id_foreign` (`pdf_downloaded_by_user_id`),
  KEY `acct_rpt_csv_nt_user_fk` (`csv_non_target_downloaded_by_user_id`),
  KEY `acct_rpt_pdf_nt_user_fk` (`pdf_non_target_downloaded_by_user_id`),
  CONSTRAINT `accounting_report_downloads_csv_downloaded_by_user_id_foreign` FOREIGN KEY (`csv_downloaded_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `accounting_report_downloads_pdf_downloaded_by_user_id_foreign` FOREIGN KEY (`pdf_downloaded_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_rpt_csv_nt_user_fk` FOREIGN KEY (`csv_non_target_downloaded_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `acct_rpt_pdf_nt_user_fk` FOREIGN KEY (`pdf_non_target_downloaded_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `accounting_report_downloads_subdomain_id_foreign` FOREIGN KEY (`subdomain_id`) REFERENCES `subdomains` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- ----------------------------
-- Table structure for admin_downloads
-- ----------------------------;',
            'CREATE TABLE `admin_downloads` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `subdomain_id` bigint unsigned NOT NULL,
  `exported_at` datetime NOT NULL COMMENT \'出力日時\',
  `summary` varchar(255) COLLATE utf8mb4_bin NOT NULL COMMENT \'出力概要\',
  `s3_key` varchar(255) COLLATE utf8mb4_bin NOT NULL COMMENT \'S3キー\',
  `download_type` varchar(50) COLLATE utf8mb4_bin DEFAULT NULL COMMENT \'出力種別: user_application / beneficiary / contact\',
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `beneficiary_export_dl_sub_exported_uniq` (`subdomain_id`,`exported_at`),
  CONSTRAINT `beneficiary_export_downloads_subdomain_id_foreign` FOREIGN KEY (`subdomain_id`) REFERENCES `subdomains` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- ----------------------------
-- Table structure for bank_branches
-- ----------------------------;',
            'CREATE TABLE `bank_branches` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT \'ID\',
  `management_code` varchar(8) COLLATE utf8mb4_bin NOT NULL COMMENT \'管理コード\',
  `bank_code` varchar(4) COLLATE utf8mb4_bin NOT NULL COMMENT \'銀行番号\',
  `bank_name` varchar(100) COLLATE utf8mb4_bin NOT NULL COMMENT \'銀行名\',
  `bank_name_kana` varchar(100) COLLATE utf8mb4_bin NOT NULL COMMENT \'銀行名カナ\',
  `branch_code` varchar(3) COLLATE utf8mb4_bin NOT NULL COMMENT \'支店番号\',
  `branch_name` varchar(100) COLLATE utf8mb4_bin NOT NULL COMMENT \'支店名\',
  `branch_name_kana` varchar(100) COLLATE utf8mb4_bin NOT NULL COMMENT \'支店名かな\',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_bank_branch_code` (`bank_code`,`branch_code`),
  KEY `idx_management_code` (`management_code`),
  KEY `idx_bank_code` (`bank_code`),
  KEY `idx_bank_name` (`bank_name`),
  KEY `idx_bank_name_kana` (`bank_name_kana`),
  KEY `idx_branch_name` (`branch_name`),
  KEY `idx_branch_name_kana` (`branch_name_kana`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- ----------------------------
-- Table structure for beneficiaries
-- ----------------------------;',
            'CREATE TABLE `beneficiaries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `subdomain_id` bigint unsigned DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `child_id` varchar(100) COLLATE utf8mb4_bin DEFAULT NULL COMMENT \'こどもID\',
  `certification_number` varchar(255) COLLATE utf8mb4_bin NOT NULL COMMENT \'就学援助認定番号\',
  `guardian_name` varchar(255) COLLATE utf8mb4_bin NOT NULL COMMENT \'就学援助認定者名（保護者名）\',
  `guardian_name_kana` varchar(100) COLLATE utf8mb4_bin DEFAULT NULL COMMENT \'就学援助認定者名カナ（保護者名）\',
  `guardian_birth_date` date NOT NULL COMMENT \'就学援助認定者生年月日\',
  `guardian_address` text COLLATE utf8mb4_bin NOT NULL COMMENT \'住所\',
  `guardian_phone` varchar(255) COLLATE utf8mb4_bin NOT NULL COMMENT \'電話番号\',
  `guardian_email` varchar(255) COLLATE utf8mb4_bin NOT NULL COMMENT \'メールアドレス\',
  `child_name` varchar(255) COLLATE utf8mb4_bin NOT NULL COMMENT \'対象児童名\',
  `child_name_kana` varchar(100) COLLATE utf8mb4_bin DEFAULT NULL COMMENT \'対象児童名カナ\',
  `child_birth_date` date NOT NULL COMMENT \'対象児童生年月日\',
  `elementary_school_name` varchar(255) COLLATE utf8mb4_bin NOT NULL COMMENT \'小学校名\',
  `grade` varchar(255) COLLATE utf8mb4_bin NOT NULL COMMENT \'学年\',
  `child_address` text COLLATE utf8mb4_bin NOT NULL COMMENT \'対象児童の住所\',
  `child_address_same_as_guardian` tinyint(1) NOT NULL DEFAULT \'0\' COMMENT \'申請者と同一の住所\',
  `child_registered_in_municipality_and_receiving_scholarship` tinyint(1) NOT NULL DEFAULT \'0\' COMMENT \'児童が住民登録があり、就学援助を受給している場合\',
  `survey_consent` tinyint(1) NOT NULL DEFAULT \'0\' COMMENT \'調査同意\',
  `classroom_name_1` varchar(255) COLLATE utf8mb4_bin DEFAULT NULL COMMENT \'教室名1\',
  `classroom_location_1` varchar(255) COLLATE utf8mb4_bin DEFAULT NULL COMMENT \'所在地1\',
  `classroom_phone_1` varchar(255) COLLATE utf8mb4_bin DEFAULT NULL COMMENT \'電話番号1\',
  `classroom_contact_person_1` varchar(255) COLLATE utf8mb4_bin DEFAULT NULL COMMENT \'担当者1\',
  `classroom_name_2` varchar(255) COLLATE utf8mb4_bin DEFAULT NULL COMMENT \'教室名2\',
  `classroom_location_2` varchar(255) COLLATE utf8mb4_bin DEFAULT NULL COMMENT \'所在地2\',
  `classroom_phone_2` varchar(255) COLLATE utf8mb4_bin DEFAULT NULL COMMENT \'電話番号2\',
  `classroom_contact_person_2` varchar(255) COLLATE utf8mb4_bin DEFAULT NULL COMMENT \'担当者2\',
  `classroom_name_3` varchar(255) COLLATE utf8mb4_bin DEFAULT NULL COMMENT \'教室名3\',
  `classroom_location_3` varchar(255) COLLATE utf8mb4_bin DEFAULT NULL COMMENT \'所在地3\',
  `classroom_phone_3` varchar(255) COLLATE utf8mb4_bin DEFAULT NULL COMMENT \'電話番号3\',
  `classroom_contact_person_3` varchar(255) COLLATE utf8mb4_bin DEFAULT NULL COMMENT \'担当者3\',
  `application_date` date NOT NULL COMMENT \'申請日\',
  `certification_date` date DEFAULT NULL COMMENT \'就学援助認定日\',
  `status` varchar(50) COLLATE utf8mb4_bin NOT NULL DEFAULT \'決定通知書未送信\' COMMENT \'ステータス\',
  `system_message` text COLLATE utf8mb4_bin COMMENT \'システムメッセージ（失敗理由等）\',
  `disqualification_date` date DEFAULT NULL COMMENT \'資格喪失日\',
  `labels` varchar(255) COLLATE utf8mb4_bin DEFAULT NULL COMMENT \'利用者ラベル（カンマ区切り）\',
  `remarks` text COLLATE utf8mb4_bin COMMENT \'運営者備考（管理者画面で編集）\',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `beneficiaries_user_id_foreign` (`user_id`),
  KEY `beneficiaries_certification_number_index` (`certification_number`),
  KEY `beneficiaries_subdomain_id_index` (`subdomain_id`) USING BTREE,
  KEY `beneficiaries_subdomain_id_status_index` (`subdomain_id`,`status`),
  CONSTRAINT `beneficiaries_subdomain_id_foreign` FOREIGN KEY (`subdomain_id`) REFERENCES `subdomains` (`id`) ON DELETE SET NULL,
  CONSTRAINT `beneficiaries_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- ----------------------------
-- Table structure for business_infos
-- ----------------------------;',
            'CREATE TABLE `business_infos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `subdomain_id` bigint unsigned NOT NULL,
  `applicant_type` varchar(20) COLLATE utf8mb4_bin NOT NULL,
  `antisocial_forces_pledged` tinyint(1) NOT NULL DEFAULT \'0\' COMMENT \'暴力団排除誓約\',
  `privacy_policy_agreed` tinyint(1) NOT NULL DEFAULT \'0\' COMMENT \'プライバシーポリシー同意\',
  `business_name` varchar(100) COLLATE utf8mb4_bin NOT NULL,
  `business_name_kana` varchar(100) COLLATE utf8mb4_bin NOT NULL,
  `representative_title` varchar(50) COLLATE utf8mb4_bin DEFAULT NULL,
  `representative_family_name` varchar(50) COLLATE utf8mb4_bin DEFAULT NULL,
  `representative_given_name` varchar(50) COLLATE utf8mb4_bin DEFAULT NULL,
  `representative_name` varchar(50) COLLATE utf8mb4_bin NOT NULL,
  `representative_name_kana` varchar(50) COLLATE utf8mb4_bin NOT NULL,
  `representative_title_kana` varchar(50) COLLATE utf8mb4_bin DEFAULT NULL,
  `representative_family_name_kana` varchar(50) COLLATE utf8mb4_bin DEFAULT NULL,
  `representative_given_name_kana` varchar(50) COLLATE utf8mb4_bin DEFAULT NULL,
  `postal_code` varchar(8) COLLATE utf8mb4_bin NOT NULL,
  `prefecture` varchar(10) COLLATE utf8mb4_bin NOT NULL,
  `city` varchar(50) COLLATE utf8mb4_bin NOT NULL,
  `address1` varchar(100) COLLATE utf8mb4_bin NOT NULL,
  `building_name` varchar(100) COLLATE utf8mb4_bin DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_bin NOT NULL,
  `fax` varchar(20) COLLATE utf8mb4_bin DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_bin NOT NULL,
  `website_url` varchar(255) COLLATE utf8mb4_bin DEFAULT NULL,
  `email_timing` varchar(50) COLLATE utf8mb4_bin NOT NULL DEFAULT \'immediate\',
  `contact_person` varchar(50) COLLATE utf8mb4_bin DEFAULT NULL,
  `contact_phone` varchar(20) COLLATE utf8mb4_bin DEFAULT NULL,
  `document_person` varchar(50) COLLATE utf8mb4_bin DEFAULT NULL,
  `document_address` varchar(255) COLLATE utf8mb4_bin DEFAULT NULL COMMENT \'文書等送付先住所\',
  `business_hours` varchar(255) COLLATE utf8mb4_bin DEFAULT NULL,
  `holiday` varchar(255) COLLATE utf8mb4_bin DEFAULT NULL,
  `bank_code` varchar(50) COLLATE utf8mb4_bin DEFAULT NULL,
  `branch_code` varchar(50) COLLATE utf8mb4_bin DEFAULT NULL,
  `account_type` varchar(10) COLLATE utf8mb4_bin DEFAULT NULL,
  `account_number` varchar(20) COLLATE utf8mb4_bin DEFAULT NULL,
  `account_holder_name` varchar(50) COLLATE utf8mb4_bin DEFAULT NULL,
  `documents` json DEFAULT NULL COMMENT \'申請書類（キー別のs3_key, original_filename等）\',
  `apply` tinyint NOT NULL DEFAULT \'0\',
  `status` varchar(50) COLLATE utf8mb4_bin NOT NULL DEFAULT \'未着手\' COMMENT \'ステータス\',
  `approved_at` date DEFAULT NULL COMMENT \'審査通過日\',
  `qr_only` tinyint(1) NOT NULL DEFAULT \'0\' COMMENT \'QR決済のみフラグ\',
  `is_public_funds_transfer_target` tinyint(1) NOT NULL DEFAULT \'0\' COMMENT \'公金振替対象\',
  `admin_remarks` text COLLATE utf8mb4_bin COMMENT \'管理者用備考\',
  `admin_attachments` json DEFAULT NULL COMMENT \'管理者用添付（s3_key, size, original_filename, mime_type の配列、最大5件）\',
  `is_active` tinyint NOT NULL DEFAULT \'1\',
  `created_user` bigint unsigned DEFAULT NULL,
  `updated_user` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `business_infos_email_unique` (`email`),
  KEY `business_infos_user_id_foreign` (`user_id`),
  KEY `business_infos_created_user_foreign` (`created_user`),
  KEY `business_infos_updated_user_foreign` (`updated_user`),
  KEY `business_infos_subdomain_id_is_active_index` (`subdomain_id`,`is_active`),
  KEY `business_infos_apply_index` (`apply`),
  KEY `business_infos_status_index` (`status`),
  CONSTRAINT `business_infos_created_user_foreign` FOREIGN KEY (`created_user`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `business_infos_subdomain_id_foreign` FOREIGN KEY (`subdomain_id`) REFERENCES `subdomains` (`id`) ON DELETE CASCADE,
  CONSTRAINT `business_infos_updated_user_foreign` FOREIGN KEY (`updated_user`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `business_infos_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- ----------------------------
-- Table structure for business_payment_downloads
-- ----------------------------;',
            'CREATE TABLE `business_payment_downloads` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `subdomain_id` bigint unsigned NOT NULL,
  `business_info_id` bigint unsigned NOT NULL,
  `target_month` date NOT NULL COMMENT \'支払対象月（月初日）\',
  `downloaded_at` datetime DEFAULT NULL COMMENT \'初回PDFダウンロード日時\',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `biz_payment_dl_sub_biz_month_uniq` (`subdomain_id`,`business_info_id`,`target_month`),
  KEY `business_payment_downloads_business_info_id_foreign` (`business_info_id`),
  KEY `business_payment_downloads_subdomain_id_business_info_id_index` (`subdomain_id`,`business_info_id`),
  CONSTRAINT `business_payment_downloads_business_info_id_foreign` FOREIGN KEY (`business_info_id`) REFERENCES `business_infos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `business_payment_downloads_subdomain_id_foreign` FOREIGN KEY (`subdomain_id`) REFERENCES `subdomains` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- ----------------------------
-- Table structure for cache
-- ----------------------------;',
            'CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_bin NOT NULL,
  `value` mediumtext COLLATE utf8mb4_bin NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- ----------------------------
-- Table structure for cache_locks
-- ----------------------------;',
            'CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_bin NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_bin NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- ----------------------------
-- Table structure for classroom_infos
-- ----------------------------;',
            'CREATE TABLE `classroom_infos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_info_id` bigint unsigned NOT NULL,
  `classroom_name` varchar(100) COLLATE utf8mb4_bin NOT NULL,
  `classroom_name_kana` varchar(100) COLLATE utf8mb4_bin DEFAULT NULL,
  `classroom_representative_name` varchar(50) COLLATE utf8mb4_bin DEFAULT NULL,
  `classroom_representative_name_kana` varchar(50) COLLATE utf8mb4_bin DEFAULT NULL,
  `classroom_postal_code` varchar(10) COLLATE utf8mb4_bin DEFAULT NULL,
  `classroom_prefecture` varchar(20) COLLATE utf8mb4_bin DEFAULT NULL,
  `classroom_city` varchar(50) COLLATE utf8mb4_bin DEFAULT NULL,
  `classroom_address1` varchar(100) COLLATE utf8mb4_bin DEFAULT NULL,
  `classroom_building_name` varchar(100) COLLATE utf8mb4_bin DEFAULT NULL,
  `classroom_latitude` decimal(10,8) DEFAULT NULL,
  `classroom_longitude` decimal(11,8) DEFAULT NULL,
  `use_map` tinyint(1) NOT NULL DEFAULT \'1\',
  `classroom_phone` varchar(20) COLLATE utf8mb4_bin DEFAULT NULL,
  `classroom_fax` varchar(20) COLLATE utf8mb4_bin DEFAULT NULL,
  `classroom_email` varchar(255) COLLATE utf8mb4_bin DEFAULT NULL,
  `business_hours` varchar(200) COLLATE utf8mb4_bin DEFAULT NULL,
  `holiday` varchar(100) COLLATE utf8mb4_bin DEFAULT NULL,
  `classroom_introduction` text COLLATE utf8mb4_bin,
  `service_type` varchar(50) COLLATE utf8mb4_bin DEFAULT NULL,
  `lesson_category` int DEFAULT NULL,
  `lesson_category_other` varchar(255) COLLATE utf8mb4_bin DEFAULT NULL,
  `classroom_image_original_filename` varchar(255) COLLATE utf8mb4_bin DEFAULT NULL COMMENT \'教室画像元ファイル名\',
  `classroom_image_s3_key` varchar(255) COLLATE utf8mb4_bin DEFAULT NULL COMMENT \'教室画像S3キー\',
  `classroom_image_file_size` int DEFAULT NULL COMMENT \'教室画像ファイルサイズ（バイト）\',
  `classroom_image_mime_type` varchar(255) COLLATE utf8mb4_bin DEFAULT NULL COMMENT \'教室画像MIMEタイプ\',
  `classroom_image_thumbnail_s3_key` varchar(255) COLLATE utf8mb4_bin DEFAULT NULL COMMENT \'教室画像サムネイルS3キー\',
  `classroom_image_medium_s3_key` varchar(255) COLLATE utf8mb4_bin DEFAULT NULL COMMENT \'教室画像中サイズS3キー\',
  `apply` tinyint NOT NULL DEFAULT \'0\',
  `is_active` tinyint NOT NULL DEFAULT \'1\',
  `disallow_amount_specified_usage` tinyint(1) NOT NULL DEFAULT \'0\' COMMENT \'金額指定利用を禁止する（利用者マイページの金額指定利用を非表示・拒否）\',
  `qr_only` tinyint(1) NOT NULL DEFAULT \'0\',
  `created_user` bigint unsigned DEFAULT NULL,
  `updated_user` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `classroom_infos_created_user_foreign` (`created_user`),
  KEY `classroom_infos_updated_user_foreign` (`updated_user`),
  KEY `classroom_infos_business_info_id_is_active_index` (`business_info_id`,`is_active`),
  KEY `classroom_infos_apply_index` (`apply`),
  CONSTRAINT `classroom_infos_business_info_id_foreign` FOREIGN KEY (`business_info_id`) REFERENCES `business_infos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `classroom_infos_created_user_foreign` FOREIGN KEY (`created_user`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `classroom_infos_updated_user_foreign` FOREIGN KEY (`updated_user`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- ----------------------------
-- Table structure for contacts
-- ----------------------------;',
            'CREATE TABLE `contacts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `subdomain_id` bigint unsigned DEFAULT NULL,
  `name` varchar(100) COLLATE utf8mb4_bin NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_bin NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_bin NOT NULL,
  `content` text COLLATE utf8mb4_bin NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_bin DEFAULT NULL,
  `is_confirmed` tinyint(1) NOT NULL DEFAULT \'0\',
  `remarks` text COLLATE utf8mb4_bin,
  `updated_user_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `contacts_updated_user_id_foreign` (`updated_user_id`),
  KEY `contacts_subdomain_id_foreign` (`subdomain_id`),
  CONSTRAINT `contacts_subdomain_id_foreign` FOREIGN KEY (`subdomain_id`) REFERENCES `subdomains` (`id`) ON DELETE SET NULL,
  CONSTRAINT `contacts_updated_user_id_foreign` FOREIGN KEY (`updated_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- ----------------------------
-- Table structure for course_categories
-- ----------------------------;',
            'CREATE TABLE `course_categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `subdomain_id` bigint unsigned NOT NULL,
  `parent_category_id` bigint unsigned NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_bin NOT NULL COMMENT \'分類名\',
  `sort_order` int NOT NULL DEFAULT \'0\' COMMENT \'表示順\',
  `is_active` tinyint(1) NOT NULL DEFAULT \'1\' COMMENT \'有効フラグ（論理削除）\',
  `created_user_id` bigint unsigned NOT NULL,
  `updated_user_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `course_categories_created_user_id_foreign` (`created_user_id`),
  KEY `course_categories_updated_user_id_foreign` (`updated_user_id`),
  KEY `idx_parent_active` (`parent_category_id`,`is_active`),
  KEY `idx_subdomain_active` (`subdomain_id`,`is_active`),
  KEY `idx_parent_sort` (`parent_category_id`,`sort_order`),
  CONSTRAINT `course_categories_created_user_id_foreign` FOREIGN KEY (`created_user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `course_categories_parent_category_id_foreign` FOREIGN KEY (`parent_category_id`) REFERENCES `course_categories_parent` (`id`) ON DELETE CASCADE,
  CONSTRAINT `course_categories_subdomain_id_foreign` FOREIGN KEY (`subdomain_id`) REFERENCES `subdomains` (`id`) ON DELETE CASCADE,
  CONSTRAINT `course_categories_updated_user_id_foreign` FOREIGN KEY (`updated_user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- ----------------------------
-- Table structure for course_categories_parent
-- ----------------------------;',
            'CREATE TABLE `course_categories_parent` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `subdomain_id` bigint unsigned NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_bin NOT NULL COMMENT \'親分類名\',
  `sort_order` int NOT NULL DEFAULT \'0\' COMMENT \'表示順\',
  `is_active` tinyint(1) NOT NULL DEFAULT \'1\' COMMENT \'有効フラグ（論理削除）\',
  `created_user_id` bigint unsigned NOT NULL,
  `updated_user_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `course_categories_parent_created_user_id_foreign` (`created_user_id`),
  KEY `course_categories_parent_updated_user_id_foreign` (`updated_user_id`),
  KEY `idx_subdomain_active` (`subdomain_id`,`is_active`),
  KEY `idx_subdomain_sort` (`subdomain_id`,`sort_order`),
  CONSTRAINT `course_categories_parent_created_user_id_foreign` FOREIGN KEY (`created_user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `course_categories_parent_subdomain_id_foreign` FOREIGN KEY (`subdomain_id`) REFERENCES `subdomains` (`id`) ON DELETE CASCADE,
  CONSTRAINT `course_categories_parent_updated_user_id_foreign` FOREIGN KEY (`updated_user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- ----------------------------
-- Table structure for course_infos
-- ----------------------------;',
            'CREATE TABLE `course_infos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `business_info_id` bigint unsigned NOT NULL,
  `classroom_info_id` bigint unsigned NOT NULL,
  `course_name` varchar(100) COLLATE utf8mb4_bin NOT NULL,
  `course_description` text COLLATE utf8mb4_bin,
  `grades` json DEFAULT NULL COMMENT \'対象学年（JSON配列）\',
  `price` int NOT NULL,
  `tax_type` varchar(255) COLLATE utf8mb4_bin DEFAULT NULL,
  `open_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `is_active` tinyint NOT NULL DEFAULT \'1\',
  `created_user` bigint unsigned DEFAULT NULL,
  `updated_user` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `course_infos_created_user_foreign` (`created_user`),
  KEY `course_infos_updated_user_foreign` (`updated_user`),
  KEY `course_infos_business_info_id_is_active_index` (`business_info_id`,`is_active`),
  KEY `course_infos_classroom_info_id_is_active_index` (`classroom_info_id`,`is_active`),
  CONSTRAINT `course_infos_business_info_id_foreign` FOREIGN KEY (`business_info_id`) REFERENCES `business_infos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `course_infos_classroom_info_id_foreign` FOREIGN KEY (`classroom_info_id`) REFERENCES `classroom_infos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `course_infos_created_user_foreign` FOREIGN KEY (`created_user`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `course_infos_updated_user_foreign` FOREIGN KEY (`updated_user`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- ----------------------------
-- Table structure for course_requests
-- ----------------------------;',
            'CREATE TABLE `course_requests` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `subdomain_id` bigint unsigned DEFAULT NULL,
  `classroom_name` varchar(100) COLLATE utf8mb4_bin NOT NULL,
  `address` text COLLATE utf8mb4_bin NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_bin NOT NULL,
  `requester_name` varchar(100) COLLATE utf8mb4_bin NOT NULL,
  `requester_email` varchar(255) COLLATE utf8mb4_bin NOT NULL,
  `requester_phone` varchar(20) COLLATE utf8mb4_bin DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_bin DEFAULT NULL,
  `is_confirmed` tinyint(1) NOT NULL DEFAULT \'0\',
  `remarks` text COLLATE utf8mb4_bin,
  `updated_user_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `course_requests_updated_user_id_foreign` (`updated_user_id`),
  KEY `course_requests_subdomain_id_foreign` (`subdomain_id`),
  CONSTRAINT `course_requests_subdomain_id_foreign` FOREIGN KEY (`subdomain_id`) REFERENCES `subdomains` (`id`) ON DELETE SET NULL,
  CONSTRAINT `course_requests_updated_user_id_foreign` FOREIGN KEY (`updated_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- ----------------------------
-- Table structure for failed_jobs
-- ----------------------------;',
            'CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_bin NOT NULL,
  `connection` text COLLATE utf8mb4_bin NOT NULL,
  `queue` text COLLATE utf8mb4_bin NOT NULL,
  `payload` longtext COLLATE utf8mb4_bin NOT NULL,
  `exception` longtext COLLATE utf8mb4_bin NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- ----------------------------
-- Table structure for inquiries
-- ----------------------------;',
            'CREATE TABLE `inquiries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `subdomain_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `inquiry_type` varchar(20) COLLATE utf8mb4_bin NOT NULL,
  `content` text COLLATE utf8mb4_bin NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_bin NOT NULL DEFAULT \'pending\',
  `remarks` text COLLATE utf8mb4_bin,
  `created_user_id` bigint unsigned NOT NULL,
  `updated_user_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `inquiries_created_user_id_foreign` (`created_user_id`),
  KEY `inquiries_updated_user_id_foreign` (`updated_user_id`),
  KEY `inquiries_subdomain_id_created_at_index` (`subdomain_id`,`created_at`),
  KEY `inquiries_user_id_index` (`user_id`),
  KEY `inquiries_status_index` (`status`),
  CONSTRAINT `inquiries_created_user_id_foreign` FOREIGN KEY (`created_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inquiries_subdomain_id_foreign` FOREIGN KEY (`subdomain_id`) REFERENCES `subdomains` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inquiries_updated_user_id_foreign` FOREIGN KEY (`updated_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `inquiries_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- ----------------------------
-- Table structure for job_batches
-- ----------------------------;',
            'CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_bin NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_bin NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_bin NOT NULL,
  `options` mediumtext COLLATE utf8mb4_bin,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- ----------------------------
-- Table structure for jobs
-- ----------------------------;',
            'CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_bin NOT NULL,
  `payload` longtext COLLATE utf8mb4_bin NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- ----------------------------
-- Table structure for notices
-- ----------------------------;',
            'CREATE TABLE `notices` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `subdomain_id` bigint unsigned NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_bin NOT NULL,
  `content` text COLLATE utf8mb4_bin NOT NULL,
  `notice_date` date NOT NULL,
  `publish_start_at` datetime DEFAULT NULL,
  `publish_end_at` datetime DEFAULT NULL,
  `address` varchar(500) COLLATE utf8mb4_bin DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `link_url` varchar(1000) COLLATE utf8mb4_bin DEFAULT NULL,
  `attachment_s3_key` varchar(255) COLLATE utf8mb4_bin DEFAULT NULL COMMENT \'お知らせ添付ファイル S3 キー\',
  `attachment_original_filename` varchar(255) COLLATE utf8mb4_bin DEFAULT NULL COMMENT \'添付ファイル元ファイル名\',
  `attachment_file_size` bigint unsigned DEFAULT NULL COMMENT \'添付ファイルサイズ（バイト）\',
  `attachment_mime_type` varchar(255) COLLATE utf8mb4_bin DEFAULT NULL COMMENT \'添付ファイル MIME タイプ\',
  `show_on_public` tinyint(1) NOT NULL DEFAULT \'0\',
  `show_on_user_dashboard` tinyint(1) NOT NULL DEFAULT \'0\',
  `show_on_business_dashboard` tinyint(1) NOT NULL DEFAULT \'0\',
  `is_deleted` tinyint(1) NOT NULL DEFAULT \'0\',
  `created_user` bigint unsigned DEFAULT NULL,
  `updated_user` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notices_created_user_foreign` (`created_user`),
  KEY `notices_updated_user_foreign` (`updated_user`),
  KEY `notices_subdomain_id_is_deleted_index` (`subdomain_id`,`is_deleted`),
  KEY `notices_notice_date_index` (`notice_date`),
  KEY `notices_publish_start_at_publish_end_at_index` (`publish_start_at`,`publish_end_at`),
  CONSTRAINT `notices_created_user_foreign` FOREIGN KEY (`created_user`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `notices_subdomain_id_foreign` FOREIGN KEY (`subdomain_id`) REFERENCES `subdomains` (`id`) ON DELETE CASCADE,
  CONSTRAINT `notices_updated_user_foreign` FOREIGN KEY (`updated_user`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- ----------------------------
-- Table structure for password_reset_tokens
-- ----------------------------;',
            'CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_bin NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_bin NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- ----------------------------
-- Table structure for payment_aggregates
-- ----------------------------;',
            'CREATE TABLE `payment_aggregates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `target_month` date NOT NULL COMMENT \'申込月（月初日）\',
  `subdomain_id` bigint unsigned NOT NULL,
  `business_info_id` bigint unsigned NOT NULL,
  `classroom_info_id` bigint unsigned NOT NULL,
  `application_count` int unsigned NOT NULL DEFAULT \'0\' COMMENT \'申込件数\',
  `total_amount` int unsigned NOT NULL DEFAULT \'0\' COMMENT \'クーポン利用額合計\',
  `is_public_funds_transfer_target` tinyint(1) NOT NULL DEFAULT \'0\' COMMENT \'公金振替対象（集計時点の事業者マスタ値）\',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pay_agg_month_sub_biz_cls_uniq` (`target_month`,`subdomain_id`,`business_info_id`,`classroom_info_id`),
  KEY `payment_aggregates_subdomain_id_foreign` (`subdomain_id`),
  KEY `payment_aggregates_classroom_info_id_foreign` (`classroom_info_id`),
  KEY `payment_aggregates_target_month_subdomain_id_index` (`target_month`,`subdomain_id`),
  KEY `payment_aggregates_business_info_id_target_month_index` (`business_info_id`,`target_month`),
  CONSTRAINT `payment_aggregates_business_info_id_foreign` FOREIGN KEY (`business_info_id`) REFERENCES `business_infos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payment_aggregates_classroom_info_id_foreign` FOREIGN KEY (`classroom_info_id`) REFERENCES `classroom_infos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payment_aggregates_subdomain_id_foreign` FOREIGN KEY (`subdomain_id`) REFERENCES `subdomains` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- ----------------------------
-- Table structure for roles
-- ----------------------------;',
            'CREATE TABLE `roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_bin NOT NULL,
  `display_name` varchar(255) COLLATE utf8mb4_bin NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_bin DEFAULT NULL,
  `is_global` tinyint(1) NOT NULL DEFAULT \'0\',
  `level` int NOT NULL DEFAULT \'0\',
  `permissions` json DEFAULT NULL,
  `is_active` tinyint NOT NULL DEFAULT \'1\',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_unique` (`name`),
  KEY `roles_name_is_global_index` (`name`,`is_global`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- ----------------------------
-- Table structure for sessions
-- ----------------------------;',
            'CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_bin NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_bin DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_bin,
  `payload` longtext COLLATE utf8mb4_bin NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- ----------------------------
-- Table structure for subdomains
-- ----------------------------;',
            'CREATE TABLE `subdomains` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `subdomain` varchar(255) COLLATE utf8mb4_bin NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_bin NOT NULL,
  `system_name` varchar(255) COLLATE utf8mb4_bin DEFAULT NULL COMMENT \'システム名（ページタイトルに使用）\',
  `description` varchar(255) COLLATE utf8mb4_bin DEFAULT NULL,
  `voucher_amount` int DEFAULT NULL COMMENT \'バウチャー金額\',
  `voucher_expiry` int DEFAULT NULL COMMENT \'有効期限（月数）\',
  `voucher_publish_date` int DEFAULT NULL COMMENT \'発行日（1-31の日付）\',
  `tax_rate` decimal(5,2) NOT NULL DEFAULT \'10.00\' COMMENT \'消費税率（%）\',
  `is_active` tinyint(1) NOT NULL DEFAULT \'1\',
  `settings` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `postal_code` varchar(8) COLLATE utf8mb4_bin DEFAULT NULL COMMENT \'郵便番号\',
  `address` varchar(255) COLLATE utf8mb4_bin DEFAULT NULL COMMENT \'住所（1フィールド）\',
  `phone` varchar(20) COLLATE utf8mb4_bin DEFAULT NULL COMMENT \'電話番号\',
  `fax` varchar(20) COLLATE utf8mb4_bin DEFAULT NULL COMMENT \'FAX番号\',
  `transfer_date_rule` varchar(30) COLLATE utf8mb4_bin DEFAULT NULL COMMENT \'振込日ルール: current_month_end=当月末, next_month_end=翌月末\',
  `zengin_requester_code` varchar(10) COLLATE utf8mb4_bin DEFAULT NULL COMMENT \'全銀ヘッダー: 依頼人コード\',
  `zengin_requester_name` varchar(40) COLLATE utf8mb4_bin DEFAULT NULL COMMENT \'全銀ヘッダー: 依頼人名\',
  `zengin_bank_code` varchar(4) COLLATE utf8mb4_bin DEFAULT NULL COMMENT \'全銀ヘッダー: 取引金融機関番号\',
  `zengin_bank_name` varchar(15) COLLATE utf8mb4_bin DEFAULT NULL COMMENT \'全銀ヘッダー: 取引金融機関名\',
  `zengin_branch_code` varchar(3) COLLATE utf8mb4_bin DEFAULT NULL COMMENT \'全銀ヘッダー: 取引支店番号\',
  `zengin_branch_name` varchar(15) COLLATE utf8mb4_bin DEFAULT NULL COMMENT \'全銀ヘッダー: 取引支店名\',
  `zengin_account_type` varchar(1) COLLATE utf8mb4_bin DEFAULT NULL COMMENT \'全銀ヘッダー: 預金種目 1=普通 2=当座 4=貯蓄\',
  `zengin_account_number` varchar(7) COLLATE utf8mb4_bin DEFAULT NULL COMMENT \'全銀ヘッダー: 口座番号\',
  PRIMARY KEY (`id`),
  UNIQUE KEY `subdomains_subdomain_unique` (`subdomain`),
  KEY `subdomains_subdomain_is_active_index` (`subdomain`,`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- ----------------------------
-- Table structure for user_applications
-- ----------------------------;',
            'CREATE TABLE `user_applications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `subdomain_id` bigint unsigned DEFAULT NULL,
  `certification_number` varchar(255) COLLATE utf8mb4_bin NOT NULL COMMENT \'就学援助認定番号\',
  `guardian_name` varchar(255) COLLATE utf8mb4_bin NOT NULL COMMENT \'就学援助認定者名（保護者名）\',
  `guardian_name_kana` varchar(100) COLLATE utf8mb4_bin DEFAULT NULL COMMENT \'就学援助認定者名カナ（保護者名）\',
  `guardian_birth_date` date NOT NULL COMMENT \'就学援助認定者生年月日\',
  `guardian_address` text COLLATE utf8mb4_bin NOT NULL COMMENT \'住所\',
  `guardian_phone` varchar(255) COLLATE utf8mb4_bin NOT NULL COMMENT \'電話番号\',
  `guardian_email` varchar(255) COLLATE utf8mb4_bin NOT NULL COMMENT \'メールアドレス\',
  `child_name` varchar(255) COLLATE utf8mb4_bin NOT NULL COMMENT \'対象児童名\',
  `child_name_kana` varchar(100) COLLATE utf8mb4_bin DEFAULT NULL COMMENT \'対象児童名カナ\',
  `child_birth_date` date NOT NULL COMMENT \'対象児童生年月日\',
  `elementary_school_name` varchar(255) COLLATE utf8mb4_bin NOT NULL COMMENT \'小学校名\',
  `grade` varchar(255) COLLATE utf8mb4_bin NOT NULL COMMENT \'学年\',
  `child_address` text COLLATE utf8mb4_bin NOT NULL COMMENT \'対象児童の住所\',
  `child_address_same_as_guardian` tinyint(1) NOT NULL DEFAULT \'0\' COMMENT \'申請者と同一の住所\',
  `child_registered_in_municipality_and_receiving_scholarship` tinyint(1) NOT NULL DEFAULT \'0\' COMMENT \'児童の住民登録があり、就学援助を受給している場合\',
  `survey_consent` tinyint(1) NOT NULL DEFAULT \'0\' COMMENT \'調査同意チェック\',
  `privacy_policy_agreed` tinyint(1) NOT NULL DEFAULT \'0\' COMMENT \'プライバシーポリシー同意\',
  `classroom_name_1` varchar(255) COLLATE utf8mb4_bin DEFAULT NULL COMMENT \'教室名1\',
  `classroom_location_1` varchar(255) COLLATE utf8mb4_bin DEFAULT NULL COMMENT \'所在地1\',
  `classroom_phone_1` varchar(255) COLLATE utf8mb4_bin DEFAULT NULL COMMENT \'電話番号1\',
  `classroom_contact_person_1` varchar(255) COLLATE utf8mb4_bin DEFAULT NULL COMMENT \'担当者1\',
  `classroom_name_2` varchar(255) COLLATE utf8mb4_bin DEFAULT NULL COMMENT \'教室名2\',
  `classroom_location_2` varchar(255) COLLATE utf8mb4_bin DEFAULT NULL COMMENT \'所在地2\',
  `classroom_phone_2` varchar(255) COLLATE utf8mb4_bin DEFAULT NULL COMMENT \'電話番号2\',
  `classroom_contact_person_2` varchar(255) COLLATE utf8mb4_bin DEFAULT NULL COMMENT \'担当者2\',
  `classroom_name_3` varchar(255) COLLATE utf8mb4_bin DEFAULT NULL COMMENT \'教室名3\',
  `classroom_location_3` varchar(255) COLLATE utf8mb4_bin DEFAULT NULL COMMENT \'所在地3\',
  `classroom_phone_3` varchar(255) COLLATE utf8mb4_bin DEFAULT NULL COMMENT \'電話番号3\',
  `classroom_contact_person_3` varchar(255) COLLATE utf8mb4_bin DEFAULT NULL COMMENT \'担当者3\',
  `document_s3_key` varchar(255) COLLATE utf8mb4_bin DEFAULT NULL COMMENT \'S3キー\',
  `document_original_filename` varchar(255) COLLATE utf8mb4_bin DEFAULT NULL COMMENT \'元のファイル名\',
  `document_file_size` int DEFAULT NULL COMMENT \'ファイルサイズ\',
  `document_mime_type` varchar(255) COLLATE utf8mb4_bin DEFAULT NULL COMMENT \'MIMEタイプ\',
  `is_exported` tinyint(1) NOT NULL DEFAULT \'0\' COMMENT \'出力済みフラグ\',
  `is_excluded_from_download` tinyint(1) NOT NULL DEFAULT \'0\' COMMENT \'ダウンロード対象外フラグ\',
  `admin_remarks` text COLLATE utf8mb4_bin COMMENT \'備考（運営用メモ）\',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_applications_subdomain_id_is_exported_index` (`subdomain_id`,`is_exported`),
  KEY `user_applications_created_at_index` (`created_at`),
  CONSTRAINT `user_applications_subdomain_id_foreign` FOREIGN KEY (`subdomain_id`) REFERENCES `subdomains` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- ----------------------------
-- Table structure for users
-- ----------------------------;',
            'CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `subdomain_id` bigint unsigned DEFAULT NULL,
  `role_id` bigint unsigned NOT NULL,
  `login_id` varchar(255) COLLATE utf8mb4_bin NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_bin NOT NULL,
  `display_name` varchar(255) COLLATE utf8mb4_bin DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_bin NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_bin NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_bin DEFAULT NULL,
  `password_reset_token_expires_at` timestamp NULL DEFAULT NULL COMMENT \'パスワードリセットトークン有効期限\',
  `is_active` tinyint NOT NULL DEFAULT \'1\',
  `last_login_at` timestamp NULL DEFAULT NULL,
  `last_login_ip` varchar(255) COLLATE utf8mb4_bin DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_subdomain_id_login_id_unique` (`subdomain_id`,`login_id`),
  KEY `users_role_id_foreign` (`role_id`),
  KEY `users_subdomain_id_role_id_is_active_index` (`subdomain_id`,`role_id`,`is_active`),
  KEY `users_email_subdomain_id_index` (`email`,`subdomain_id`),
  KEY `users_subdomain_id_login_id_index` (`subdomain_id`,`login_id`),
  CONSTRAINT `users_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `users_subdomain_id_foreign` FOREIGN KEY (`subdomain_id`) REFERENCES `subdomains` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- ----------------------------
-- Table structure for voucher_usages
-- ----------------------------;',
            'CREATE TABLE `voucher_usages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `subdomain_id` bigint unsigned DEFAULT NULL,
  `business_info_id` bigint unsigned NOT NULL,
  `classroom_info_id` bigint unsigned NOT NULL,
  `course_info_id` bigint unsigned DEFAULT NULL COMMENT \'コースID（金額指定利用の場合はNULL）\',
  `amount` int NOT NULL COMMENT \'利用金額\',
  `used_at` timestamp NOT NULL COMMENT \'利用日時\',
  `memo` text COLLATE utf8mb4_bin COMMENT \'備考\',
  `business_memo` text COLLATE utf8mb4_bin COMMENT \'事業者メモ\',
  `admin_correction_memo` text COLLATE utf8mb4_bin COMMENT \'管理者修正時のメモ\',
  `admin_corrected_at` timestamp NULL DEFAULT NULL COMMENT \'管理者の修正日時\',
  `qr_flag` tinyint(1) NOT NULL DEFAULT \'0\' COMMENT \'QRコード使用フラグ\',
  `is_cancelled` tinyint(1) NOT NULL DEFAULT \'0\' COMMENT \'キャンセルフラグ\',
  `cancelled_by_user_id` bigint unsigned DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL COMMENT \'キャンセル日時\',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `voucher_usages_business_info_id_foreign` (`business_info_id`),
  KEY `voucher_usages_classroom_info_id_foreign` (`classroom_info_id`),
  KEY `voucher_usages_cancelled_by_user_id_foreign` (`cancelled_by_user_id`),
  KEY `voucher_usages_user_id_is_cancelled_index` (`user_id`,`is_cancelled`),
  KEY `voucher_usages_course_info_id_is_cancelled_index` (`course_info_id`,`is_cancelled`),
  KEY `voucher_usages_used_at_index` (`used_at`),
  KEY `voucher_usages_subdomain_id_foreign` (`subdomain_id`),
  CONSTRAINT `voucher_usages_business_info_id_foreign` FOREIGN KEY (`business_info_id`) REFERENCES `business_infos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `voucher_usages_cancelled_by_user_id_foreign` FOREIGN KEY (`cancelled_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `voucher_usages_classroom_info_id_foreign` FOREIGN KEY (`classroom_info_id`) REFERENCES `classroom_infos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `voucher_usages_course_info_id_foreign` FOREIGN KEY (`course_info_id`) REFERENCES `course_infos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `voucher_usages_subdomain_id_foreign` FOREIGN KEY (`subdomain_id`) REFERENCES `subdomains` (`id`) ON DELETE SET NULL,
  CONSTRAINT `voucher_usages_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;

-- ----------------------------
-- Table structure for vouchers
-- ----------------------------;',
            'CREATE TABLE `vouchers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `beneficiary_id` bigint unsigned NOT NULL,
  `subdomain_id` bigint unsigned DEFAULT NULL,
  `voucher_number` varchar(255) COLLATE utf8mb4_bin NOT NULL COMMENT \'バウチャー番号\',
  `issue_date` date NOT NULL COMMENT \'発行日\',
  `expiry_date` date NOT NULL COMMENT \'有効期限\',
  `amount` int NOT NULL COMMENT \'利用金額\',
  `status` enum(\'unused\',\'used\',\'expired\') COLLATE utf8mb4_bin NOT NULL DEFAULT \'unused\' COMMENT \'利用状態\',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `vouchers_voucher_number_unique` (`voucher_number`),
  KEY `vouchers_beneficiary_id_status_index` (`beneficiary_id`,`status`),
  KEY `vouchers_subdomain_id_status_index` (`subdomain_id`,`status`),
  KEY `vouchers_voucher_number_index` (`voucher_number`),
  CONSTRAINT `vouchers_beneficiary_id_foreign` FOREIGN KEY (`beneficiary_id`) REFERENCES `beneficiaries` (`id`) ON DELETE CASCADE,
  CONSTRAINT `vouchers_subdomain_id_foreign` FOREIGN KEY (`subdomain_id`) REFERENCES `subdomains` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin;',
        ];
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::statement('DROP TABLE IF EXISTS `vouchers`');
        DB::statement('DROP TABLE IF EXISTS `voucher_usages`');
        DB::statement('DROP TABLE IF EXISTS `users`');
        DB::statement('DROP TABLE IF EXISTS `user_applications`');
        DB::statement('DROP TABLE IF EXISTS `subdomains`');
        DB::statement('DROP TABLE IF EXISTS `sessions`');
        DB::statement('DROP TABLE IF EXISTS `roles`');
        DB::statement('DROP TABLE IF EXISTS `payment_aggregates`');
        DB::statement('DROP TABLE IF EXISTS `password_reset_tokens`');
        DB::statement('DROP TABLE IF EXISTS `notices`');
        DB::statement('DROP TABLE IF EXISTS `jobs`');
        DB::statement('DROP TABLE IF EXISTS `job_batches`');
        DB::statement('DROP TABLE IF EXISTS `inquiries`');
        DB::statement('DROP TABLE IF EXISTS `failed_jobs`');
        DB::statement('DROP TABLE IF EXISTS `course_requests`');
        DB::statement('DROP TABLE IF EXISTS `course_infos`');
        DB::statement('DROP TABLE IF EXISTS `course_categories_parent`');
        DB::statement('DROP TABLE IF EXISTS `course_categories`');
        DB::statement('DROP TABLE IF EXISTS `contacts`');
        DB::statement('DROP TABLE IF EXISTS `classroom_infos`');
        DB::statement('DROP TABLE IF EXISTS `cache_locks`');
        DB::statement('DROP TABLE IF EXISTS `cache`');
        DB::statement('DROP TABLE IF EXISTS `business_payment_downloads`');
        DB::statement('DROP TABLE IF EXISTS `business_infos`');
        DB::statement('DROP TABLE IF EXISTS `beneficiaries`');
        DB::statement('DROP TABLE IF EXISTS `bank_branches`');
        DB::statement('DROP TABLE IF EXISTS `admin_downloads`');
        DB::statement('DROP TABLE IF EXISTS `accounting_report_downloads`');
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
};

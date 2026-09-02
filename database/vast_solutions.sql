-- =====================================================================
--  VAST SOLUTIONS — Cabinet Project Management System
--  Full database schema (MySQL / MariaDB, XAMPP)
--
--  Covers every function in the system:
--    Auth & users .......... users
--    Customers ............. customers
--    Quote requests ........ project_requests, request_files
--    Quotations / costing .. quotations, quotation_items
--    Projects & monitoring . projects, project_updates, project_materials
--    Cutting-list summary .. summ_batches, summ_items
--    Material libraries .... material_library, edging_library
--    Notifications ......... notifications  (delay-alert bell)
--    Audit trail ........... audit_logs
--    Company settings ...... company_settings
--
--  Import:  mysql -u root vast_solutions < vast_solutions.sql
--       or  import via phpMyAdmin.
-- =====================================================================

CREATE DATABASE IF NOT EXISTS `vast_solutions`
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `vast_solutions`;

SET FOREIGN_KEY_CHECKS = 0;

-- Drop in reverse-dependency order so re-import is clean.
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `audit_logs`;
DROP TABLE IF EXISTS `summ_items`;
DROP TABLE IF EXISTS `summ_batches`;
DROP TABLE IF EXISTS `project_materials`;
DROP TABLE IF EXISTS `project_updates`;
DROP TABLE IF EXISTS `quotation_items`;
DROP TABLE IF EXISTS `quotations`;
DROP TABLE IF EXISTS `request_files`;
DROP TABLE IF EXISTS `project_requests`;
DROP TABLE IF EXISTS `projects`;
DROP TABLE IF EXISTS `customers`;
DROP TABLE IF EXISTS `edging_library`;
DROP TABLE IF EXISTS `material_library`;
DROP TABLE IF EXISTS `company_settings`;
DROP TABLE IF EXISTS `users`;

SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
-- users  — system accounts (Admin / Staff) and client accounts.
--          Client sign-up (signup.php) creates role='Client'.
--          Admin/Staff are managed in admin/user-management.php.
-- ---------------------------------------------------------------------
CREATE TABLE `users` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `full_name`     VARCHAR(120) NOT NULL,
  `email`         VARCHAR(150) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `phone`         VARCHAR(40)  DEFAULT NULL,
  `location`      VARCHAR(150) DEFAULT NULL,
  `role`          ENUM('Super Admin','Admin','Staff','Client') NOT NULL DEFAULT 'Client',
  `status`        ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
  `email_verified` TINYINT(1) NOT NULL DEFAULT 0,  -- client signup verifies via OTP
  `verified_at`   DATETIME DEFAULT NULL,
  `avatar`        VARCHAR(255) DEFAULT NULL,
  `last_login`    DATETIME DEFAULT NULL,
  `is_archived`   TINYINT(1) NOT NULL DEFAULT 0,
  `archived_at`   DATETIME DEFAULT NULL,
  `archived_by`   INT UNSIGNED DEFAULT NULL,
  `archive_reason` VARCHAR(255) DEFAULT NULL,
  `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_users_email` (`email`),
  KEY `ix_users_role` (`role`),
  KEY `ix_users_archived` (`is_archived`),
  CONSTRAINT `fk_users_archived_by` FOREIGN KEY (`archived_by`)
    REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- otp_codes — one-time codes for client sign-up email verification.
-- ---------------------------------------------------------------------
CREATE TABLE `otp_codes` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`     INT UNSIGNED DEFAULT NULL,
  `email`       VARCHAR(150) NOT NULL,
  `code`        VARCHAR(6)   NOT NULL,
  `purpose`     ENUM('signup') NOT NULL DEFAULT 'signup',
  `expires_at`  DATETIME NOT NULL,
  `consumed_at` DATETIME DEFAULT NULL,
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ix_otp_email_purpose` (`email`, `purpose`),
  CONSTRAINT `fk_otp_user` FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- customers  — companies/clients that projects belong to.
--              May optionally be linked to a login account (user_id).
-- ---------------------------------------------------------------------
CREATE TABLE `customers` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`        INT UNSIGNED DEFAULT NULL,          -- linked client login, if any
  `name`           VARCHAR(150) NOT NULL,              -- company / customer name
  `contact_person` VARCHAR(120) DEFAULT NULL,
  `email`          VARCHAR(150) DEFAULT NULL,
  `phone`          VARCHAR(40)  DEFAULT NULL,
  `address`        VARCHAR(255) DEFAULT NULL,
  `industry`       VARCHAR(80)  DEFAULT NULL,          -- Real Estate, Interior Design, Hospitality, Architecture...
  `is_archived`    TINYINT(1) NOT NULL DEFAULT 0,
  `archived_at`    DATETIME DEFAULT NULL,
  `archived_by`    INT UNSIGNED DEFAULT NULL,
  `archive_reason` VARCHAR(255) DEFAULT NULL,
  `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ix_customers_name` (`name`),
  KEY `ix_customers_archived` (`is_archived`),
  CONSTRAINT `fk_customers_user` FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_customers_archived_by` FOREIGN KEY (`archived_by`)
    REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- project_requests  — client-submitted "Request a Quote" (REQ-YYYY-NNN).
--                     Source: user-dashboard/request_quote.php.
-- ---------------------------------------------------------------------
CREATE TABLE `project_requests` (
  `id`               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `request_code`     VARCHAR(30) NOT NULL,             -- REQ-2026-015
  `customer_id`      INT UNSIGNED DEFAULT NULL,
  `submitted_by`     INT UNSIGNED DEFAULT NULL,        -- client user who submitted
  `project_name`     VARCHAR(150) NOT NULL,
  `category`         VARCHAR(80)  DEFAULT NULL,        -- Wardrobe, Kitchen Cabinets, ...
  `material_type`    VARCHAR(80)  DEFAULT NULL,        -- Plywood, MDF, Aluminum, ...
  `dimensions`       VARCHAR(120) DEFAULT NULL,        -- optional W x H x D, e.g. "2400 x 720 x 600 mm"
  `budget`           DECIMAL(12,2) DEFAULT NULL,
  `installation_address` VARCHAR(255) DEFAULT NULL,
  `target_completion` DATE DEFAULT NULL,
  `reference_design` VARCHAR(120) DEFAULT NULL,        -- cabinet_imageN.jpg from catalog
  `notes`            TEXT DEFAULT NULL,
  `status`           ENUM('Requesting Quotation','Quotation Sent','Closed')
                       NOT NULL DEFAULT 'Requesting Quotation',
  `date_submitted`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_request_code` (`request_code`),
  KEY `ix_req_customer` (`customer_id`),
  KEY `ix_req_status` (`status`),
  CONSTRAINT `fk_req_customer` FOREIGN KEY (`customer_id`)
    REFERENCES `customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_req_submitted_by` FOREIGN KEY (`submitted_by`)
    REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- request_files  — design files uploaded with a quote request
--                  (design_files[] : pdf, dwg, skp, jpg...).
-- ---------------------------------------------------------------------
CREATE TABLE `request_files` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `request_id`  INT UNSIGNED NOT NULL,
  `file_name`   VARCHAR(255) NOT NULL,                 -- original name shown to user
  `file_path`   VARCHAR(255) NOT NULL,                 -- stored path on disk
  `file_type`   VARCHAR(30)  DEFAULT NULL,             -- extension / mime hint
  `file_size`   INT UNSIGNED DEFAULT NULL,             -- bytes
  `uploaded_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ix_files_request` (`request_id`),
  CONSTRAINT `fk_files_request` FOREIGN KEY (`request_id`)
    REFERENCES `project_requests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- projects  — an active project (PRJ-YYYY-NNN) tracked through the 10
--             monitoring phases. Source: admin/monitoring.php,
--             user-dashboard/my_projects.php. Status vocabulary MUST
--             match includes/project_status.php.
-- ---------------------------------------------------------------------
CREATE TABLE `projects` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_code`   VARCHAR(30) NOT NULL,               -- PRJ-2026-042
  `customer_id`    INT UNSIGNED DEFAULT NULL,
  `request_id`     INT UNSIGNED DEFAULT NULL,          -- originating quote request, if any
  `project_name`   VARCHAR(150) NOT NULL,
  `category`       VARCHAR(80)  DEFAULT NULL,
  `description`    TEXT DEFAULT NULL,                  -- "details" in monitoring
  `installation_address` VARCHAR(255) DEFAULT NULL,
  `status`         ENUM('quote_submitted','approved','production','mockup','delivery',
                        'installation','quality_check','punchlist','final_approval',
                        'completed','on_hold','rejected')
                     NOT NULL DEFAULT 'quote_submitted',
  `progress`       TINYINT UNSIGNED NOT NULL DEFAULT 0, -- 0-100
  `start_date`     DATE DEFAULT NULL,
  `target_completion` DATE DEFAULT NULL,
  `approver`       VARCHAR(120) DEFAULT NULL,
  `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_project_code` (`project_code`),
  KEY `ix_proj_customer` (`customer_id`),
  KEY `ix_proj_status` (`status`),
  CONSTRAINT `fk_proj_customer` FOREIGN KEY (`customer_id`)
    REFERENCES `customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_proj_request` FOREIGN KEY (`request_id`)
    REFERENCES `project_requests` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- quotations  — cost quotation document (QT-YYYY-NNN) with costing
--               parameters. Source: admin/quotations.php + the
--               "Create Cost Quotation" modal in project-requests.php.
-- ---------------------------------------------------------------------
CREATE TABLE `quotations` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `quote_code`     VARCHAR(30) NOT NULL,               -- QT-2026-042
  `customer_id`    INT UNSIGNED DEFAULT NULL,
  `request_id`     INT UNSIGNED DEFAULT NULL,
  `project_id`     INT UNSIGNED DEFAULT NULL,          -- set once a project exists
  `project_name`   VARCHAR(150) DEFAULT NULL,
  `category`       VARCHAR(80)  DEFAULT NULL,
  `installation_address` VARCHAR(255) DEFAULT NULL,
  `date_created`   DATE NOT NULL,
  `valid_until`    DATE DEFAULT NULL,                  -- typically date_created + 30 days
  -- Lifecycle: Sent (to client) -> Accepted (by client) -> Approved (by admin) / Rejected
  `status`         ENUM('Sent','Accepted','Approved','Rejected')
                     NOT NULL DEFAULT 'Sent',
  -- costing inputs (from the Costing Preview panel)
  `qty_boards`     INT DEFAULT 0,                        -- retained (legacy); no longer collected
  `qty_glass`      INT DEFAULT 0,                        -- retained (legacy); no longer collected
  `markup_pct`     DECIMAL(5,2) DEFAULT 15.00,
  `contingency_pct` DECIMAL(5,2) DEFAULT 5.00,
  `service_pct`    DECIMAL(5,2) DEFAULT 10.00,
  `protection_pct` DECIMAL(5,2) DEFAULT 3.00,
  `labor_cost`     DECIMAL(12,2) NOT NULL DEFAULT 0.00,  -- 50% of material_total
  `substrate`      DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `out_of_town_pct` DECIMAL(5,2) NOT NULL DEFAULT 0.00,  -- % of material_total
  `special_works`  DECIMAL(12,2) DEFAULT 0.00,
  `accessories`    DECIMAL(12,2) DEFAULT 0.00,
  -- computed totals
  `material_total` DECIMAL(12,2) DEFAULT 0.00,
  `total_amount`   DECIMAL(12,2) DEFAULT 0.00,
  `notes`          TEXT DEFAULT NULL,
  `created_by`     INT UNSIGNED DEFAULT NULL,
  `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_quote_code` (`quote_code`),
  KEY `ix_quo_customer` (`customer_id`),
  KEY `ix_quo_project` (`project_id`),
  KEY `ix_quo_status` (`status`),
  CONSTRAINT `fk_quo_customer` FOREIGN KEY (`customer_id`)
    REFERENCES `customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_quo_request` FOREIGN KEY (`request_id`)
    REFERENCES `project_requests` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_quo_project` FOREIGN KEY (`project_id`)
    REFERENCES `projects` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_quo_created_by` FOREIGN KEY (`created_by`)
    REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- quotation_items  — line items of a quotation / costing preview.
-- ---------------------------------------------------------------------
CREATE TABLE `quotation_items` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `quotation_id` INT UNSIGNED NOT NULL,
  `item`         VARCHAR(150) NOT NULL,                -- "Panel - Base Cabinet"
  `description`  VARCHAR(255) DEFAULT NULL,            -- "18mm Melamine White"
  `qty`          DECIMAL(12,2) NOT NULL DEFAULT 0,
  `unit_cost`    DECIMAL(12,2) NOT NULL DEFAULT 0,
  `line_total`   DECIMAL(12,2) NOT NULL DEFAULT 0,
  `sort_order`   INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `ix_qitem_quotation` (`quotation_id`),
  CONSTRAINT `fk_qitem_quotation` FOREIGN KEY (`quotation_id`)
    REFERENCES `quotations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- project_updates  — timeline posts on a project (admin/monitoring.php
--                    "Post an update"). Shown to client in project_detail.
-- ---------------------------------------------------------------------
CREATE TABLE `project_updates` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id`  INT UNSIGNED NOT NULL,
  `author_id`   INT UNSIGNED DEFAULT NULL,
  `author_name` VARCHAR(120) DEFAULT NULL,             -- denormalised for display
  `update_text` TEXT NOT NULL,
  `attachment_path` VARCHAR(255) DEFAULT NULL,          -- optional uploaded image
  `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ix_upd_project` (`project_id`),
  CONSTRAINT `fk_upd_project` FOREIGN KEY (`project_id`)
    REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_upd_author` FOREIGN KEY (`author_id`)
    REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- project_materials  — materials required for a project (monitoring
--                     "Materials" modal). status: available/ordered/low.
-- ---------------------------------------------------------------------
CREATE TABLE `project_materials` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id`    INT UNSIGNED NOT NULL,
  `material`      VARCHAR(150) NOT NULL,
  `specification` VARCHAR(255) DEFAULT NULL,
  `qty`           DECIMAL(12,2) NOT NULL DEFAULT 0,
  `unit`          VARCHAR(30)  DEFAULT NULL,           -- sheets, pcs, pairs, rolls...
  `status`        ENUM('available','ordered','low') NOT NULL DEFAULT 'available',
  `sort_order`    INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `ix_pmat_project` (`project_id`),
  CONSTRAINT `fk_pmat_project` FOREIGN KEY (`project_id`)
    REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- summ_batches  — one uploaded cutting-list (.cut) processed run.
--                 Source: admin/summarization.php (ported CVProcess).
-- ---------------------------------------------------------------------
CREATE TABLE `summ_batches` (
  `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id`      INT UNSIGNED DEFAULT NULL,
  `project_name`    VARCHAR(150) DEFAULT NULL,         -- selected project label
  `source_filename` VARCHAR(255) NOT NULL,             -- uploaded .cut file name
  `uploaded_by`     INT UNSIGNED DEFAULT NULL,
  `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ix_batch_project` (`project_id`),
  CONSTRAINT `fk_batch_project` FOREIGN KEY (`project_id`)
    REFERENCES `projects` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_batch_uploaded_by` FOREIGN KEY (`uploaded_by`)
    REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- summ_items  — summarized parts of a batch, deduped & summed, split by
--               opti into: wood (panels), alu (edges), hw (hardware).
-- ---------------------------------------------------------------------
CREATE TABLE `summ_items` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `batch_id`   INT UNSIGNED NOT NULL,
  `category`   ENUM('wood','alu','hw') NOT NULL,       -- opti 6 / 5 / 0
  `partname`   VARCHAR(150) DEFAULT NULL,
  `material`   VARCHAR(150) DEFAULT NULL,
  `qty`        INT NOT NULL DEFAULT 0,
  `width`      VARCHAR(30)  DEFAULT NULL,
  `length`     VARCHAR(30)  DEFAULT NULL,
  `edging`     VARCHAR(60)  DEFAULT NULL,
  `comment`    VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ix_sitem_batch` (`batch_id`),
  KEY `ix_sitem_category` (`category`),
  CONSTRAINT `fk_sitem_batch` FOREIGN KEY (`batch_id`)
    REFERENCES `summ_batches` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- material_library  — normalization overrides for raw material codes.
--                     Migrated from legacy material.dbf. Editable via
--                     summarization Page 2 "Custom Material Library".
-- ---------------------------------------------------------------------
CREATE TABLE `material_library` (
  `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code`            VARCHAR(60) NOT NULL,              -- raw material code
  `normalized_name` VARCHAR(120) NOT NULL,             -- friendly / grouped name
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_material_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- edging_library  — normalization overrides for raw edging codes.
--                   Migrated from legacy edging.dbf. Editable via
--                   summarization Page 2 "Custom Edge Library".
-- ---------------------------------------------------------------------
CREATE TABLE `edging_library` (
  `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `code`            VARCHAR(60) NOT NULL,              -- raw edge code, e.g. 2WE2LE
  `normalized_name` VARCHAR(120) NOT NULL,             -- grouped code, e.g. 2W2L (may be blank)
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_edging_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- audit_logs  — activity trail (admin/audit.php).
-- ---------------------------------------------------------------------
CREATE TABLE `audit_logs` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`    INT UNSIGNED DEFAULT NULL,
  `user_name`  VARCHAR(120) DEFAULT NULL,              -- denormalised for display
  `role`       VARCHAR(30)  DEFAULT NULL,              -- Admin / Staff / Client
  `action`     VARCHAR(255) NOT NULL,                  -- e.g. "Approved quotation QT-2026-041"
  `module`     VARCHAR(50)  DEFAULT NULL,              -- Quotations, Project Requests, Customers, Auth...
  `details`    TEXT DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ix_audit_user` (`user_id`),
  KEY `ix_audit_module` (`module`),
  KEY `ix_audit_created` (`created_at`),
  CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- notifications  — backs the topbar "Delay Alerts" bell
--                  (includes/notif_bell.php). One row per alert.
--   * Target a single user (user_id) OR a whole role (target_role).
--   * `type` distinguishes delay alerts from quote-decision prompts, etc.
--   * `severity` drives the dot colour (danger = overdue, warning = at-risk).
--   * `is_read` / unread count drives the badge number.
--   Delay alerts can be (re)generated from projects whose target_completion
--   has passed while status NOT IN ('completed','rejected'); quote-decision
--   alerts from quotations still 'Waiting Approval'.
-- ---------------------------------------------------------------------
CREATE TABLE `notifications` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`      INT UNSIGNED DEFAULT NULL,            -- recipient; NULL = role broadcast
  `target_role`  ENUM('Admin','Staff','Client') DEFAULT NULL,
  `type`         ENUM('delay','quote_decision','quote_approved','quote_rejected',
                      'status_update','request','system')
                   NOT NULL DEFAULT 'system',
  `title`        VARCHAR(150) NOT NULL,                -- "PRJ-2026-037 · Pantry Cabinets"
  `message`      VARCHAR(255) DEFAULT NULL,            -- "Overdue — target was Mar 01, 2026"
  `link`         VARCHAR(255) DEFAULT NULL,            -- where the item navigates
  `severity`     ENUM('info','warning','danger') NOT NULL DEFAULT 'warning',
  `project_id`   INT UNSIGNED DEFAULT NULL,
  `quotation_id` INT UNSIGNED DEFAULT NULL,
  `is_read`      TINYINT(1) NOT NULL DEFAULT 0,
  `read_at`      DATETIME DEFAULT NULL,
  `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `ix_notif_user` (`user_id`),
  KEY `ix_notif_role` (`target_role`),
  KEY `ix_notif_unread` (`is_read`),
  CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_notif_project` FOREIGN KEY (`project_id`)
    REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_notif_quotation` FOREIGN KEY (`quotation_id`)
    REFERENCES `quotations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- company_settings  — single-row company profile + default costing %
--                     (admin/settings.php).
-- ---------------------------------------------------------------------
CREATE TABLE `company_settings` (
  `id`               TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `company_name`     VARCHAR(150) DEFAULT 'Vast Solutions',
  `email`            VARCHAR(150) DEFAULT NULL,
  `contact_number`   VARCHAR(40)  DEFAULT NULL,
  `address`          VARCHAR(255) DEFAULT NULL,
  `logo_path`        VARCHAR(255) DEFAULT NULL,
  `web_email`        VARCHAR(150) DEFAULT NULL,
  `web_phone`        VARCHAR(40)  DEFAULT NULL,
  `web_location`     VARCHAR(255) DEFAULT NULL,
  `default_markup_pct`      DECIMAL(5,2) NOT NULL DEFAULT 15.00,
  `default_contingency_pct` DECIMAL(5,2) NOT NULL DEFAULT 5.00,
  `default_service_pct`     DECIMAL(5,2) NOT NULL DEFAULT 10.00,
  `default_protection_pct`  DECIMAL(5,2) NOT NULL DEFAULT 3.00,
  `updated_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `chk_settings_singleton` CHECK (`id` = 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------
--  gallery_images — design gallery shown on index.php + request_quote.php,
--  managed from admin Settings (Design Gallery card).
-- ---------------------------------------------------------------------
CREATE TABLE `gallery_images` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `file_path`  VARCHAR(255) NOT NULL,             -- relative to project root
  `label`      VARCHAR(150) DEFAULT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ---------------------------------------------------------------------
--  legal_documents — editable Terms & Conditions and Privacy Policy.
--  Edited by Super Admin (admin/settings.php). `version` bumps on every
--  content change, which forces clients to re-accept (user_agreements).
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `user_agreements`;
DROP TABLE IF EXISTS `legal_documents`;

CREATE TABLE `legal_documents` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `doc_key`    ENUM('terms','privacy') NOT NULL,
  `title`      VARCHAR(150) NOT NULL,
  `body`       LONGTEXT NOT NULL,                 -- simple markup: # heading, - bullet, -- sub-bullet
  `version`    INT UNSIGNED NOT NULL DEFAULT 1,
  `updated_by` INT UNSIGNED DEFAULT NULL,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_legal_doc_key` (`doc_key`),
  CONSTRAINT `fk_legal_updated_by` FOREIGN KEY (`updated_by`)
    REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
--  user_agreements — which legal document version each user has accepted.
--  A client is "up to date" when a row exists for the current version of
--  every legal_documents row; otherwise they hit the acceptance gate.
-- ---------------------------------------------------------------------
CREATE TABLE `user_agreements` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id`     INT UNSIGNED NOT NULL,
  `doc_key`     ENUM('terms','privacy') NOT NULL,
  `version`     INT UNSIGNED NOT NULL,
  `ip_address`  VARCHAR(45) DEFAULT NULL,
  `accepted_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_doc_ver` (`user_id`, `doc_key`, `version`),
  KEY `ix_agree_user` (`user_id`),
  CONSTRAINT `fk_agree_user` FOREIGN KEY (`user_id`)
    REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =====================================================================
--  SEED DATA
-- =====================================================================

-- Default back-office accounts (pre-verified). Passwords in comments.
-- Change these after first login.
--   admin@vastsolutions.com  / admin123  (Super Admin)
--   admin2@vastsolutions.com / admin123  (Admin)
--   staff@vastsolutions.com  / admin123  (Staff)
INSERT INTO `users` (`full_name`, `email`, `password_hash`, `role`, `status`, `email_verified`)
VALUES ('Admin User', 'admin@vastsolutions.com',
        '$2y$10$6X4e8BYtjB.arLlyNVYo1OnHXCGkhW0g6qvJE0r1ouXxpnvezfiGK', 'Super Admin', 'Active', 1);

INSERT INTO `users` (`full_name`, `email`, `password_hash`, `role`, `status`, `email_verified`)
VALUES ('Alex Admin', 'admin2@vastsolutions.com',
        '$2y$10$6X4e8BYtjB.arLlyNVYo1OnHXCGkhW0g6qvJE0r1ouXxpnvezfiGK', 'Admin', 'Active', 1);

INSERT INTO `users` (`full_name`, `email`, `password_hash`, `role`, `status`, `email_verified`)
VALUES ('Sam Staff', 'staff@vastsolutions.com',
        '$2y$10$6X4e8BYtjB.arLlyNVYo1OnHXCGkhW0g6qvJE0r1ouXxpnvezfiGK', 'Staff', 'Active', 1);

-- Company profile (single row).
INSERT INTO `company_settings`
  (`id`, `company_name`, `email`, `contact_number`, `address`)
VALUES
  (1, 'Vast Solutions', 'inquiries@vastsolutionsmanila.com', '+639178850408',
   'B34 L1, Hibiscus St. Ceris 1, Calamba, Laguna');

-- Material library (migrated from legacy material.dbf: code -> normalized name).
INSERT INTO `material_library` (`code`, `normalized_name`) VALUES
  ('16777727', 'MP18 COLORED'),
  ('16777726', 'MP18 WHITE'),
  ('16777710', 'PB18 WHITE'),
  ('16777708', 'PB06 WHITE'),
  ('16777709', 'PB15 WHITE'),
  ('16777755', 'SE18 COLORED'),
  ('16777772', 'SE06 COLORED'),
  ('16777782', 'MT18'),
  ('16777713', 'PB18 COLORED'),
  ('16777701', 'CG09'),
  ('16777700', 'CG05'),
  ('16777715', 'PB06 COLORED'),
  ('16777712', 'PB25 COLORED'),
  ('16777714', 'PB15 COLORED'),
  ('16777699', 'MIR05'),
  ('16777765', 'PB09 COLORED'),
  ('16777624', 'HG18 COLORED'),
  ('16777797', 'MP18 HPL'),
  ('16777798', 'PB15 HPL');

-- Edging library — intentionally left EMPTY so edge codes pass through raw
-- (e.g. 2WE2LE, 2WC2LE), matching the legacy CVProcess .xls output.
-- The table is kept so admins can add groupings via the Custom Edge Library
-- (summarization Page 2); any code with no row here is exported unchanged.
-- Reference: the legacy edging.dbf mapped 2WE2LE->2W2L, 2WC2LE->2L,
-- 2WG2LG->'', etc. Re-add those rows only if grouped edges are wanted.

-- ---------------------------------------------------------------------
--  Sample dev data — supports the DEV_MODE "View as Client" identity
--  (see includes/auth.php) and gives DB-backed views something to show.
--  Client login (once real auth exists): client@demo.test / client123
-- ---------------------------------------------------------------------
INSERT INTO `users` (`full_name`, `email`, `password_hash`, `phone`, `role`, `status`, `email_verified`)
VALUES ('Juan Dela Cruz', 'client@demo.test',
        '$2y$10$AqjiskjZF/jj4sT6q9Y8PeVGcctY8nl1RMFwnOjVl4RTO1dK6oQwq',
        '+63 917 555 0110', 'Client', 'Active', 1);
SET @client_user_id = LAST_INSERT_ID();

INSERT INTO `customers` (`user_id`, `name`, `contact_person`, `email`, `phone`, `address`, `industry`)
VALUES (@client_user_id, 'Chua Residence', 'Juan Dela Cruz', 'client@demo.test',
        '+63 917 555 0110', '12 Narra St., Quezon City', 'Real Estate');
SET @client_customer_id = LAST_INSERT_ID();

INSERT INTO `projects`
    (`project_code`, `customer_id`, `project_name`, `category`, `description`,
     `installation_address`, `status`, `progress`, `start_date`, `target_completion`, `approver`)
VALUES
    ('PRJ-2026-001', @client_customer_id, 'Chua Kitchen', 'Kitchen Cabinets',
     'Main kitchen cabinet set with island.', '12 Narra St., Quezon City',
     'approved', 10, '2026-07-01', '2026-09-15', 'Engr. Marco Reyes'),
    ('PRJ-2026-002', @client_customer_id, 'Master Bedroom Wardrobe', 'Wardrobe',
     'Walk-in closet, soft-close doors.', '12 Narra St., Quezon City',
     'production', 60, '2026-06-15', '2026-08-30', 'Engr. Marco Reyes');

-- Legal documents (Terms & Conditions, Privacy Policy). Editable in admin Settings.
INSERT INTO `legal_documents` (`doc_key`, `title`, `body`, `version`) VALUES
  ('terms', 'Terms & Conditions',
   '# 1. Introduction\nThese Terms & Conditions (\"Terms\") govern your use of the Vast Solutions online platform (the \"Platform\") and all design services, fabrication work, modular cabinet products, and related offerings provided by Vast Solutions (\"Company\", \"we\", \"our\"). By creating an account, engaging our services, or purchasing our products, you (\"you\", \"your\", the \"Client\") agree to these Terms.\n\n# 2. Accounts & Registration\n- You must provide accurate, current, and complete information when creating your account, and keep it up to date.\n- You are responsible for keeping your login credentials confidential and for all activity under your account.\n- Accounts are verified by email. We may suspend or archive accounts that are inactive, misused, or created with false information.\n\n# 3. Scope of Work\n- All design, fabrication, and installation work will follow the approved drawings, specifications, and quotations.\n- Any revisions requested after approval may result in additional fees and adjusted timelines.\n- Vast Solutions reserves the right to decline revisions that compromise structural integrity, safety, or feasibility.\n\n# 4. Quotations & Pricing\n- Quotations are valid for 30 days unless otherwise stated.\n- Pricing may change due to material availability, supplier adjustments, or design changes initiated by the Client.\n- A down payment is required before production begins.\n- Quotations issued through the Platform show the project total; internal costing breakdowns remain the property of the Company.\n\n# 5. Payments\n- Standard payment terms:\n-- 50% upon project confirmation\n-- 25% upon delivery and start of installation\n-- 15% after installation\n-- 10% after punchlist and turnover\n- Payments are non-refundable once fabrication has started.\n- Late payments may delay production and delivery schedules.\n\n# 6. Lead Time & Delivery\n- Lead time is 4-6 weeks after the deposit has been made, but may vary depending on project complexity, material availability, and production queue.\n- Delivery dates provided are estimates and may shift due to unforeseen circumstances.\n- Vast Solutions is not liable for delays caused by suppliers, logistics partners, or force majeure events.\n\n# 7. Installation\n- Installation fees are included only if stated in the quotation.\n- The Client must ensure the site is ready, accessible, and free from obstructions.\n\n# 8. Materials & Warranty\n- Vast Solutions uses the materials specified in the approved quotation.\n- Natural variations in wood, laminates, and finishes are expected and are not considered defects.\n- Warranty coverage:\n-- 1-year warranty on workmanship\n-- Manufacturer warranty applies to hardware and accessories\n- Warranty does not cover misuse, water damage, improper cleaning, or unauthorized modifications.\n\n# 9. Design Ownership & Intellectual Property\n- All design concepts, drawings, and 3D renders remain the intellectual property of Vast Solutions.\n- Clients may not reproduce, distribute, or use the designs for fabrication by another party without written consent.\n- The Platform, including its layout, content, and software, is owned by Vast Solutions and may not be copied or reused without permission.\n\n# 10. Acceptable Use of the Platform\n- You agree to use the Platform only for lawful purposes related to your own projects.\n- You may not upload malicious files, attempt to gain unauthorized access, or disrupt the service.\n- Files you upload (drawings, measurements, site photos) must be yours to share.\n\n# 11. Cancellations\n- Cancellation before fabrication may incur design and administrative fees.\n- Cancellation after fabrication begins is not eligible for a refund.\n\n# 12. Liability\n- Vast Solutions is not responsible for damages caused by improper use, unauthorized repairs, or external contractors.\n- Our liability is limited to the value of the project stated in the quotation.\n\n# 13. Amendments\nWe may update these Terms from time to time. When we do, you will be asked to review and accept the updated Terms the next time you sign in. Continued engagement with our services constitutes acceptance of the updated Terms.\n\n# 14. Contact\nFor questions about these Terms, contact us through our official business channels.', 1),
  ('privacy', 'Privacy Policy',
   '# 1. Introduction\nThis Privacy Policy explains how Vast Solutions (\"we\", \"our\", \"us\") collects, uses, and protects personal information provided by clients, website visitors, and project partners through our online platform and services.\n\n# 2. Information We Collect\nWe may collect the following information:\n- Name, contact number, and email address\n- Account credentials (passwords are stored only in encrypted form)\n- Project details, measurements, and site photos you upload\n- Quotation, billing, and payment information\n- Communication and activity records (messages, notifications, and system logs)\n\n# 3. How We Use Your Information\nWe use collected information to:\n- Provide design, fabrication, and installation services\n- Create and manage your account and client portal\n- Prepare quotations, drawings, and project documentation\n- Communicate updates, schedules, and revisions\n- Improve our products, services, and customer experience\n- Maintain internal records, audit trails, and accounting\n\n# 4. Data Protection\n- We implement reasonable security measures to protect your information.\n- Passwords are hashed and are never stored in plain text.\n- Only authorized personnel have access to client data, based on their role.\n- We do not sell, rent, or share your personal information with third parties except as required to complete your project (e.g., suppliers, logistics partners).\n\n# 5. Cookies & Sessions\n- The Platform uses session cookies to keep you signed in and to operate securely.\n- These cookies are essential to the service and are not used for advertising.\n\n# 6. Third-Party Services\nWe may work with external suppliers, delivery partners, or subcontractors. These parties receive only the information necessary to perform their function and are expected to maintain confidentiality.\n\n# 7. Data Retention\nWe retain project and client information for as long as necessary to:\n- Complete the project\n- Comply with legal and accounting requirements\n- Support warranty claims and after-sales service\n\n# 8. Your Rights\nYou may request:\n- Access to your personal data\n- Correction of inaccurate information\n- Deletion of data that is no longer needed\nRequests can be made through our official contact channels.\n\n# 9. Updates to This Policy\nWe may update this Privacy Policy periodically. When we make significant changes, you will be asked to review and accept the updated policy the next time you sign in. Continued use of our services indicates acceptance of the updated policy.\n\n# 10. Contact Information\nFor questions or requests related to privacy or data handling, you may contact us through our official business channels.', 1);

-- =====================================================================
--  END
-- =====================================================================

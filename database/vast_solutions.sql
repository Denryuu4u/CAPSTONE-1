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
  `role`          ENUM('Admin','Staff','Client') NOT NULL DEFAULT 'Client',
  `status`        ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
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
  `status`         ENUM('Waiting Approval','Approved','Rejected')
                     NOT NULL DEFAULT 'Waiting Approval',
  -- costing inputs (from the Costing Preview panel)
  `qty_boards`     INT DEFAULT 0,
  `qty_glass`      INT DEFAULT 0,
  `markup_pct`     DECIMAL(5,2) DEFAULT 15.00,
  `contingency_pct` DECIMAL(5,2) DEFAULT 5.00,
  `service_pct`    DECIMAL(5,2) DEFAULT 10.00,
  `protection_pct` DECIMAL(5,2) DEFAULT 3.00,
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
  `default_markup_pct`      DECIMAL(5,2) NOT NULL DEFAULT 15.00,
  `default_contingency_pct` DECIMAL(5,2) NOT NULL DEFAULT 5.00,
  `default_service_pct`     DECIMAL(5,2) NOT NULL DEFAULT 10.00,
  `default_protection_pct`  DECIMAL(5,2) NOT NULL DEFAULT 3.00,
  `updated_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `chk_settings_singleton` CHECK (`id` = 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- =====================================================================
--  SEED DATA
-- =====================================================================

-- Default admin account.  Password = "admin123"  (bcrypt hash below).
-- Change this after first login.
INSERT INTO `users` (`full_name`, `email`, `password_hash`, `role`, `status`)
VALUES ('Admin User', 'admin@vastsolutions.com',
        '$2y$10$6X4e8BYtjB.arLlyNVYo1OnHXCGkhW0g6qvJE0r1ouXxpnvezfiGK', 'Admin', 'Active');

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

-- Edging library (migrated from legacy edging.dbf: raw code -> grouped code).
INSERT INTO `edging_library` (`code`, `normalized_name`) VALUES
  ('2WE2LE', '2W2L'),
  ('2LE',    '2L'),
  ('2WE',    '2W'),
  ('2WE1LE', '2W1L'),
  ('1WE2LE', '1W2L'),
  ('1WE',    '1W'),
  ('1LE',    '1L'),
  ('2LE2WE', '2W2L'),
  ('1LE1WE', '1W1L'),
  ('1WE1LE', '1W1L'),
  ('2LE1WE', '1W2L'),
  ('2WG2LG', ''),
  ('2WC2LE', '2L'),
  ('2WJ2LJ', ''),
  ('2WH2LB', '2L'),
  ('2WB2LB', '2W2L'),
  ('2WC2LC', '2L');

-- =====================================================================
--  END
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS=0;

CREATE TABLE IF NOT EXISTS plans (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(100) NOT NULL,
 slug VARCHAR(80) NOT NULL UNIQUE,
 price_monthly DECIMAL(10,2) NOT NULL DEFAULT 0,
 patient_limit INT NULL,
 user_limit INT NULL,
 ai_enabled TINYINT(1) NOT NULL DEFAULT 0,
 features_json JSON NULL,
 active TINYINT(1) NOT NULL DEFAULT 1,
 created_at DATETIME NOT NULL,
 updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tenants (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(160) NOT NULL,
 slug VARCHAR(100) NOT NULL UNIQUE,
 document VARCHAR(30) NULL,
 phone VARCHAR(30) NULL,
 email VARCHAR(190) NULL,
 status ENUM('active','suspended','cancelled') NOT NULL DEFAULT 'active',
 created_at DATETIME NOT NULL,
 updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS subscriptions (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 tenant_id BIGINT UNSIGNED NOT NULL,
 plan_id BIGINT UNSIGNED NOT NULL,
 status ENUM('active','past_due','suspended','cancelled') NOT NULL DEFAULT 'active',
 billing_mode VARCHAR(30) NOT NULL DEFAULT 'manual',
 current_period_start DATE NULL,
 current_period_end DATE NULL,
 created_at DATETIME NOT NULL,
 updated_at DATETIME NOT NULL,
 INDEX idx_sub_tenant (tenant_id),
 CONSTRAINT fk_sub_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id),
 CONSTRAINT fk_sub_plan FOREIGN KEY (plan_id) REFERENCES plans(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS roles (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(100) NOT NULL,
 slug VARCHAR(80) NOT NULL UNIQUE,
 created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS permissions (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(120) NOT NULL,
 slug VARCHAR(120) NOT NULL UNIQUE,
 created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS role_permissions (
 role_id BIGINT UNSIGNED NOT NULL,
 permission_id BIGINT UNSIGNED NOT NULL,
 PRIMARY KEY(role_id,permission_id),
 CONSTRAINT fk_rp_role FOREIGN KEY(role_id) REFERENCES roles(id) ON DELETE CASCADE,
 CONSTRAINT fk_rp_permission FOREIGN KEY(permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS users (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 tenant_id BIGINT UNSIGNED NOT NULL,
 role_id BIGINT UNSIGNED NOT NULL,
 supervisor_id BIGINT UNSIGNED NULL,
 name VARCHAR(160) NOT NULL,
 email VARCHAR(190) NOT NULL,
 password_hash VARCHAR(255) NOT NULL,
 professional_crp VARCHAR(40) NULL,
 phone VARCHAR(30) NULL,
 active TINYINT(1) NOT NULL DEFAULT 1,
 last_login_at DATETIME NULL,
 created_at DATETIME NOT NULL,
 updated_at DATETIME NOT NULL,
 UNIQUE KEY uq_user_email (email),
 INDEX idx_user_tenant (tenant_id),
 CONSTRAINT fk_user_tenant FOREIGN KEY(tenant_id) REFERENCES tenants(id),
 CONSTRAINT fk_user_role FOREIGN KEY(role_id) REFERENCES roles(id),
 CONSTRAINT fk_user_supervisor FOREIGN KEY(supervisor_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS patients (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 tenant_id BIGINT UNSIGNED NOT NULL,
 primary_professional_id BIGINT UNSIGNED NOT NULL,
 code VARCHAR(30) NOT NULL,
 name VARCHAR(160) NOT NULL,
 preferred_name VARCHAR(120) NULL,
 cpf VARCHAR(20) NULL,
 birth_date DATE NULL,
 email VARCHAR(190) NULL,
 phone VARCHAR(30) NULL,
 emergency_contact VARCHAR(190) NULL,
 approach VARCHAR(80) NULL,
 status ENUM('active','inactive','discharged') NOT NULL DEFAULT 'active',
 notes_private_enc MEDIUMTEXT NULL,
 created_at DATETIME NOT NULL,
 updated_at DATETIME NOT NULL,
 UNIQUE KEY uq_patient_code (tenant_id,code),
 INDEX idx_patient_tenant_prof (tenant_id,primary_professional_id),
 CONSTRAINT fk_patient_tenant FOREIGN KEY(tenant_id) REFERENCES tenants(id),
 CONSTRAINT fk_patient_prof FOREIGN KEY(primary_professional_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS appointments (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 tenant_id BIGINT UNSIGNED NOT NULL,
 patient_id BIGINT UNSIGNED NOT NULL,
 professional_id BIGINT UNSIGNED NOT NULL,
 starts_at DATETIME NOT NULL,
 ends_at DATETIME NOT NULL,
 status ENUM('pending','confirmed','completed','cancelled','no_show') NOT NULL DEFAULT 'pending',
 modality ENUM('in_person','online') NOT NULL DEFAULT 'in_person',
 meeting_url VARCHAR(500) NULL,
 fee DECIMAL(10,2) NULL,
 recurrence_group VARCHAR(64) NULL,
 public_token CHAR(64) NULL,
 reminder_24h_at DATETIME NULL,
 reminder_2h_at DATETIME NULL,
 notes VARCHAR(500) NULL,
 created_at DATETIME NOT NULL,
 updated_at DATETIME NOT NULL,
 INDEX idx_appointment_tenant_date (tenant_id,starts_at),
 UNIQUE KEY uq_appointment_public_token (public_token),
 CONSTRAINT fk_appt_tenant FOREIGN KEY(tenant_id) REFERENCES tenants(id),
 CONSTRAINT fk_appt_patient FOREIGN KEY(patient_id) REFERENCES patients(id),
 CONSTRAINT fk_appt_prof FOREIGN KEY(professional_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS clinical_records (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 tenant_id BIGINT UNSIGNED NOT NULL,
 patient_id BIGINT UNSIGNED NOT NULL,
 professional_id BIGINT UNSIGNED NOT NULL,
 supervisor_id BIGINT UNSIGNED NULL,
 record_type ENUM('soap','narrative','anamnesis') NOT NULL DEFAULT 'soap',
 content_enc MEDIUMTEXT NOT NULL,
 status ENUM('draft','pending_approval','approved','signed') NOT NULL DEFAULT 'draft',
 signed_at DATETIME NULL,
 approved_by BIGINT UNSIGNED NULL,
 created_at DATETIME NOT NULL,
 updated_at DATETIME NOT NULL,
 INDEX idx_record_patient (tenant_id,patient_id,created_at),
 CONSTRAINT fk_record_tenant FOREIGN KEY(tenant_id) REFERENCES tenants(id),
 CONSTRAINT fk_record_patient FOREIGN KEY(patient_id) REFERENCES patients(id),
 CONSTRAINT fk_record_prof FOREIGN KEY(professional_id) REFERENCES users(id),
 CONSTRAINT fk_record_supervisor FOREIGN KEY(supervisor_id) REFERENCES users(id) ON DELETE SET NULL,
 CONSTRAINT fk_record_approved FOREIGN KEY(approved_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS clinical_documents (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 tenant_id BIGINT UNSIGNED NOT NULL,
 patient_id BIGINT UNSIGNED NOT NULL,
 professional_id BIGINT UNSIGNED NOT NULL,
 supervisor_id BIGINT UNSIGNED NULL,
 approved_by BIGINT UNSIGNED NULL,
 document_type ENUM('declaracao','atestado','relatorio','laudo','parecer') NOT NULL,
 title VARCHAR(190) NOT NULL,
 content_enc MEDIUMTEXT NOT NULL,
 status ENUM('draft','pending_approval','approved','signed') NOT NULL DEFAULT 'draft',
 compliance_status ENUM('not_checked','attention','reviewed') NOT NULL DEFAULT 'not_checked',
 signed_at DATETIME NULL,
 validation_token CHAR(64) NULL,
 created_at DATETIME NOT NULL,
 updated_at DATETIME NOT NULL,
 INDEX idx_doc_patient (tenant_id,patient_id),
 UNIQUE KEY uq_doc_validation_token (validation_token),
 CONSTRAINT fk_doc_tenant FOREIGN KEY(tenant_id) REFERENCES tenants(id),
 CONSTRAINT fk_doc_patient FOREIGN KEY(patient_id) REFERENCES patients(id),
 CONSTRAINT fk_doc_prof FOREIGN KEY(professional_id) REFERENCES users(id),
 CONSTRAINT fk_doc_supervisor FOREIGN KEY(supervisor_id) REFERENCES users(id) ON DELETE SET NULL,
 CONSTRAINT fk_doc_approved FOREIGN KEY(approved_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS financial_transactions (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 tenant_id BIGINT UNSIGNED NOT NULL,
 patient_id BIGINT UNSIGNED NULL,
 appointment_id BIGINT UNSIGNED NULL,
 professional_id BIGINT UNSIGNED NULL,
 created_by BIGINT UNSIGNED NOT NULL,
 type ENUM('income','expense') NOT NULL,
 category VARCHAR(100) NOT NULL,
 description VARCHAR(190) NOT NULL,
 amount DECIMAL(12,2) NOT NULL,
 due_date DATE NULL,
 paid_at DATETIME NULL,
 status ENUM('pending','paid','cancelled','courtesy') NOT NULL DEFAULT 'pending',
 payer_document VARCHAR(30) NULL,
 receipt_code VARCHAR(60) NULL,
 created_at DATETIME NOT NULL,
 updated_at DATETIME NOT NULL,
 INDEX idx_fin_tenant_date (tenant_id,due_date),
 UNIQUE KEY uq_fin_appointment (tenant_id,appointment_id),
 CONSTRAINT fk_fin_tenant FOREIGN KEY(tenant_id) REFERENCES tenants(id),
 CONSTRAINT fk_fin_patient FOREIGN KEY(patient_id) REFERENCES patients(id) ON DELETE SET NULL,
 CONSTRAINT fk_fin_appt FOREIGN KEY(appointment_id) REFERENCES appointments(id) ON DELETE SET NULL,
 CONSTRAINT fk_fin_prof FOREIGN KEY(professional_id) REFERENCES users(id) ON DELETE SET NULL,
 CONSTRAINT fk_fin_user FOREIGN KEY(created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS therapeutic_tasks (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 tenant_id BIGINT UNSIGNED NOT NULL,
 patient_id BIGINT UNSIGNED NOT NULL,
 professional_id BIGINT UNSIGNED NOT NULL,
 title VARCHAR(180) NOT NULL,
 instructions TEXT NULL,
 due_date DATE NULL,
 status ENUM('open','done','cancelled') NOT NULL DEFAULT 'open',
 created_at DATETIME NOT NULL,
 updated_at DATETIME NOT NULL,
 CONSTRAINT fk_task_tenant FOREIGN KEY(tenant_id) REFERENCES tenants(id),
 CONSTRAINT fk_task_patient FOREIGN KEY(patient_id) REFERENCES patients(id),
 CONSTRAINT fk_task_prof FOREIGN KEY(professional_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS mood_logs (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 tenant_id BIGINT UNSIGNED NOT NULL,
 patient_id BIGINT UNSIGNED NOT NULL,
 mood_score TINYINT UNSIGNED NOT NULL,
 anxiety_score TINYINT UNSIGNED NULL,
 triggers TEXT NULL,
 thoughts TEXT NULL,
 created_at DATETIME NOT NULL,
 INDEX idx_mood_patient (tenant_id,patient_id,created_at),
 CONSTRAINT fk_mood_tenant FOREIGN KEY(tenant_id) REFERENCES tenants(id),
 CONSTRAINT fk_mood_patient FOREIGN KEY(patient_id) REFERENCES patients(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS patient_portal_users (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 tenant_id BIGINT UNSIGNED NOT NULL,
 patient_id BIGINT UNSIGNED NOT NULL,
 email VARCHAR(190) NOT NULL,
 password_hash VARCHAR(255) NOT NULL,
 active TINYINT(1) NOT NULL DEFAULT 1,
 last_login_at DATETIME NULL,
 created_at DATETIME NOT NULL,
 updated_at DATETIME NOT NULL,
 UNIQUE KEY uq_portal_email (email),
 UNIQUE KEY uq_portal_patient (tenant_id,patient_id),
 CONSTRAINT fk_portal_tenant FOREIGN KEY(tenant_id) REFERENCES tenants(id),
 CONSTRAINT fk_portal_patient FOREIGN KEY(patient_id) REFERENCES patients(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS knowledge_chunks (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 tenant_id BIGINT UNSIGNED NULL,
 title VARCHAR(220) NOT NULL,
 source_url VARCHAR(600) NULL,
 source_type VARCHAR(60) NOT NULL DEFAULT 'manual',
 content MEDIUMTEXT NOT NULL,
 is_official TINYINT(1) NOT NULL DEFAULT 0,
 active TINYINT(1) NOT NULL DEFAULT 1,
 created_at DATETIME NOT NULL,
 updated_at DATETIME NOT NULL,
 INDEX idx_knowledge_tenant (tenant_id,active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ai_interactions (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 tenant_id BIGINT UNSIGNED NOT NULL,
 user_id BIGINT UNSIGNED NOT NULL,
 patient_id BIGINT UNSIGNED NULL,
 mode VARCHAR(40) NOT NULL,
 prompt_hash CHAR(64) NOT NULL,
 response_enc MEDIUMTEXT NULL,
 sources_json JSON NULL,
 created_at DATETIME NOT NULL,
 INDEX idx_ai_tenant_user (tenant_id,user_id,created_at),
 CONSTRAINT fk_ai_tenant FOREIGN KEY(tenant_id) REFERENCES tenants(id),
 CONSTRAINT fk_ai_user FOREIGN KEY(user_id) REFERENCES users(id),
 CONSTRAINT fk_ai_patient FOREIGN KEY(patient_id) REFERENCES patients(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notification_queue (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 tenant_id BIGINT UNSIGNED NOT NULL,
 appointment_id BIGINT UNSIGNED NULL,
 channel ENUM('whatsapp','email') NOT NULL,
 destination VARCHAR(190) NOT NULL,
 template VARCHAR(80) NOT NULL,
 payload_json JSON NULL,
 scheduled_at DATETIME NOT NULL,
 sent_at DATETIME NULL,
 status ENUM('queued','sent','failed') NOT NULL DEFAULT 'queued',
 error_message VARCHAR(500) NULL,
 created_at DATETIME NOT NULL,
 INDEX idx_notification_due (status,scheduled_at),
 CONSTRAINT fk_nq_tenant FOREIGN KEY(tenant_id) REFERENCES tenants(id),
 CONSTRAINT fk_nq_appt FOREIGN KEY(appointment_id) REFERENCES appointments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_sessions (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 tenant_id BIGINT UNSIGNED NOT NULL,
 user_id BIGINT UNSIGNED NOT NULL,
 token_hash CHAR(64) NOT NULL,
 ip_address VARCHAR(45) NULL,
 user_agent VARCHAR(255) NULL,
 last_seen_at DATETIME NULL,
 revoked_at DATETIME NULL,
 created_at DATETIME NOT NULL,
 INDEX idx_sessions_user (tenant_id,user_id,revoked_at),
 CONSTRAINT fk_session_tenant FOREIGN KEY(tenant_id) REFERENCES tenants(id),
 CONSTRAINT fk_session_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audit_logs (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 tenant_id BIGINT UNSIGNED NOT NULL,
 user_id BIGINT UNSIGNED NULL,
 action VARCHAR(120) NOT NULL,
 entity_type VARCHAR(80) NOT NULL,
 entity_id BIGINT UNSIGNED NULL,
 metadata_json JSON NULL,
 ip_address VARCHAR(45) NULL,
 user_agent VARCHAR(255) NULL,
 previous_hash CHAR(64) NOT NULL,
 event_hash CHAR(64) NOT NULL,
 created_at DATETIME NOT NULL,
 INDEX idx_audit_tenant_date (tenant_id,created_at),
 CONSTRAINT fk_audit_tenant FOREIGN KEY(tenant_id) REFERENCES tenants(id),
 CONSTRAINT fk_audit_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS saas_admins (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(160) NOT NULL,
 email VARCHAR(190) NOT NULL UNIQUE,
 password_hash VARCHAR(255) NOT NULL,
 active TINYINT(1) NOT NULL DEFAULT 1,
 created_at DATETIME NOT NULL,
 updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tenant_features (
 tenant_id BIGINT UNSIGNED NOT NULL,
 feature_key VARCHAR(100) NOT NULL,
 enabled TINYINT(1) NOT NULL DEFAULT 0,
 updated_by BIGINT UNSIGNED NULL,
 updated_at DATETIME NOT NULL,
 PRIMARY KEY (tenant_id, feature_key),
 CONSTRAINT fk_feature_tenant FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
 CONSTRAINT fk_feature_user FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Defesa em profundidade contra referências cruzadas entre tenants.
-- As FKs simples continuam úteis, e estas FKs compostas garantem que o objeto relacionado pertença ao mesmo tenant.
ALTER TABLE users ADD UNIQUE KEY uq_users_tenant_id (tenant_id,id);
ALTER TABLE patients ADD UNIQUE KEY uq_patients_tenant_id (tenant_id,id);
ALTER TABLE appointments ADD UNIQUE KEY uq_appointments_tenant_id (tenant_id,id);

ALTER TABLE users ADD CONSTRAINT fk_user_supervisor_tenant FOREIGN KEY (tenant_id,supervisor_id) REFERENCES users(tenant_id,id);
ALTER TABLE patients ADD CONSTRAINT fk_patient_prof_tenant FOREIGN KEY (tenant_id,primary_professional_id) REFERENCES users(tenant_id,id);
ALTER TABLE appointments ADD CONSTRAINT fk_appt_patient_tenant FOREIGN KEY (tenant_id,patient_id) REFERENCES patients(tenant_id,id);
ALTER TABLE appointments ADD CONSTRAINT fk_appt_prof_tenant FOREIGN KEY (tenant_id,professional_id) REFERENCES users(tenant_id,id);
ALTER TABLE clinical_records ADD CONSTRAINT fk_record_patient_tenant FOREIGN KEY (tenant_id,patient_id) REFERENCES patients(tenant_id,id);
ALTER TABLE clinical_records ADD CONSTRAINT fk_record_prof_tenant FOREIGN KEY (tenant_id,professional_id) REFERENCES users(tenant_id,id);
ALTER TABLE clinical_records ADD CONSTRAINT fk_record_supervisor_tenant FOREIGN KEY (tenant_id,supervisor_id) REFERENCES users(tenant_id,id);
ALTER TABLE clinical_records ADD CONSTRAINT fk_record_approved_tenant FOREIGN KEY (tenant_id,approved_by) REFERENCES users(tenant_id,id);
ALTER TABLE clinical_documents ADD CONSTRAINT fk_doc_patient_tenant FOREIGN KEY (tenant_id,patient_id) REFERENCES patients(tenant_id,id);
ALTER TABLE clinical_documents ADD CONSTRAINT fk_doc_prof_tenant FOREIGN KEY (tenant_id,professional_id) REFERENCES users(tenant_id,id);
ALTER TABLE clinical_documents ADD CONSTRAINT fk_doc_supervisor_tenant FOREIGN KEY (tenant_id,supervisor_id) REFERENCES users(tenant_id,id);
ALTER TABLE clinical_documents ADD CONSTRAINT fk_doc_approved_tenant FOREIGN KEY (tenant_id,approved_by) REFERENCES users(tenant_id,id);
ALTER TABLE financial_transactions ADD CONSTRAINT fk_fin_patient_tenant FOREIGN KEY (tenant_id,patient_id) REFERENCES patients(tenant_id,id);
ALTER TABLE financial_transactions ADD CONSTRAINT fk_fin_appt_tenant FOREIGN KEY (tenant_id,appointment_id) REFERENCES appointments(tenant_id,id);
ALTER TABLE financial_transactions ADD CONSTRAINT fk_fin_prof_tenant FOREIGN KEY (tenant_id,professional_id) REFERENCES users(tenant_id,id);
ALTER TABLE financial_transactions ADD CONSTRAINT fk_fin_created_by_tenant FOREIGN KEY (tenant_id,created_by) REFERENCES users(tenant_id,id);
ALTER TABLE therapeutic_tasks ADD CONSTRAINT fk_task_patient_tenant FOREIGN KEY (tenant_id,patient_id) REFERENCES patients(tenant_id,id);
ALTER TABLE therapeutic_tasks ADD CONSTRAINT fk_task_prof_tenant FOREIGN KEY (tenant_id,professional_id) REFERENCES users(tenant_id,id);
ALTER TABLE mood_logs ADD CONSTRAINT fk_mood_patient_tenant FOREIGN KEY (tenant_id,patient_id) REFERENCES patients(tenant_id,id);
ALTER TABLE patient_portal_users ADD CONSTRAINT fk_portal_patient_tenant FOREIGN KEY (tenant_id,patient_id) REFERENCES patients(tenant_id,id);
ALTER TABLE ai_interactions ADD CONSTRAINT fk_ai_user_tenant FOREIGN KEY (tenant_id,user_id) REFERENCES users(tenant_id,id);
ALTER TABLE ai_interactions ADD CONSTRAINT fk_ai_patient_tenant FOREIGN KEY (tenant_id,patient_id) REFERENCES patients(tenant_id,id);
ALTER TABLE notification_queue ADD CONSTRAINT fk_nq_appt_tenant FOREIGN KEY (tenant_id,appointment_id) REFERENCES appointments(tenant_id,id);
ALTER TABLE user_sessions ADD CONSTRAINT fk_session_user_tenant FOREIGN KEY (tenant_id,user_id) REFERENCES users(tenant_id,id);
ALTER TABLE audit_logs ADD CONSTRAINT fk_audit_user_tenant FOREIGN KEY (tenant_id,user_id) REFERENCES users(tenant_id,id);
ALTER TABLE tenant_features ADD CONSTRAINT fk_feature_user_tenant FOREIGN KEY (tenant_id,updated_by) REFERENCES users(tenant_id,id);

SET FOREIGN_KEY_CHECKS=1;

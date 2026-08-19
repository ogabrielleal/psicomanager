-- PsicoManager AI v0.2.0 - defesa multi-tenant e feature flags
-- Execute UMA VEZ em instalações v0.1.x via phpMyAdmin/console MySQL.

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

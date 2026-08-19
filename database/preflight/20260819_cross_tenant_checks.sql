-- Execute antes da migration v0.2.0. Todas as contagens devem ser ZERO.
SELECT 'patients.primary_professional' check_name, COUNT(*) violations FROM patients p JOIN users u ON u.id=p.primary_professional_id WHERE p.tenant_id<>u.tenant_id
UNION ALL SELECT 'users.supervisor', COUNT(*) FROM users u JOIN users s ON s.id=u.supervisor_id WHERE u.tenant_id<>s.tenant_id
UNION ALL SELECT 'appointments.patient', COUNT(*) FROM appointments a JOIN patients p ON p.id=a.patient_id WHERE a.tenant_id<>p.tenant_id
UNION ALL SELECT 'appointments.professional', COUNT(*) FROM appointments a JOIN users u ON u.id=a.professional_id WHERE a.tenant_id<>u.tenant_id
UNION ALL SELECT 'clinical_records.patient', COUNT(*) FROM clinical_records r JOIN patients p ON p.id=r.patient_id WHERE r.tenant_id<>p.tenant_id
UNION ALL SELECT 'clinical_records.professional', COUNT(*) FROM clinical_records r JOIN users u ON u.id=r.professional_id WHERE r.tenant_id<>u.tenant_id
UNION ALL SELECT 'clinical_records.supervisor', COUNT(*) FROM clinical_records r JOIN users u ON u.id=r.supervisor_id WHERE r.supervisor_id IS NOT NULL AND r.tenant_id<>u.tenant_id
UNION ALL SELECT 'clinical_documents.patient', COUNT(*) FROM clinical_documents d JOIN patients p ON p.id=d.patient_id WHERE d.tenant_id<>p.tenant_id
UNION ALL SELECT 'clinical_documents.professional', COUNT(*) FROM clinical_documents d JOIN users u ON u.id=d.professional_id WHERE d.tenant_id<>u.tenant_id
UNION ALL SELECT 'financial.patient', COUNT(*) FROM financial_transactions f JOIN patients p ON p.id=f.patient_id WHERE f.patient_id IS NOT NULL AND f.tenant_id<>p.tenant_id
UNION ALL SELECT 'financial.appointment', COUNT(*) FROM financial_transactions f JOIN appointments a ON a.id=f.appointment_id WHERE f.appointment_id IS NOT NULL AND f.tenant_id<>a.tenant_id
UNION ALL SELECT 'tasks.patient', COUNT(*) FROM therapeutic_tasks x JOIN patients p ON p.id=x.patient_id WHERE x.tenant_id<>p.tenant_id
UNION ALL SELECT 'mood.patient', COUNT(*) FROM mood_logs m JOIN patients p ON p.id=m.patient_id WHERE m.tenant_id<>p.tenant_id
UNION ALL SELECT 'portal.patient', COUNT(*) FROM patient_portal_users pu JOIN patients p ON p.id=pu.patient_id WHERE pu.tenant_id<>p.tenant_id
UNION ALL SELECT 'ai.user', COUNT(*) FROM ai_interactions a JOIN users u ON u.id=a.user_id WHERE a.tenant_id<>u.tenant_id
UNION ALL SELECT 'sessions.user', COUNT(*) FROM user_sessions s JOIN users u ON u.id=s.user_id WHERE s.tenant_id<>u.tenant_id
UNION ALL SELECT 'audit.user', COUNT(*) FROM audit_logs a JOIN users u ON u.id=a.user_id WHERE a.user_id IS NOT NULL AND a.tenant_id<>u.tenant_id;

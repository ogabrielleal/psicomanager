<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){fwrite(STDERR,"Execute somente via CLI.\n");exit(2);}require dirname(__DIR__).'/app/bootstrap.php';
$checks=[
 'tenant_features table'=>"SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name='tenant_features'",
 'users composite index'=>"SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema=DATABASE() AND table_name='users' AND index_name='uq_users_tenant_id'",
 'patients same-tenant FK'=>"SELECT COUNT(*) FROM information_schema.table_constraints WHERE constraint_schema=DATABASE() AND table_name='patients' AND constraint_name='fk_patient_prof_tenant' AND constraint_type='FOREIGN KEY'",
 'appointments patient FK'=>"SELECT COUNT(*) FROM information_schema.table_constraints WHERE constraint_schema=DATABASE() AND table_name='appointments' AND constraint_name='fk_appt_patient_tenant' AND constraint_type='FOREIGN KEY'"
];$failed=0;foreach($checks as $name=>$sql){$ok=(int)db()->query($sql)->fetchColumn()>0;echo ($ok?'[OK] ':'[MISSING] ').$name.PHP_EOL;if(!$ok)$failed++;}exit($failed?1:0);

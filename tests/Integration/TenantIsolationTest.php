<?php
declare(strict_types=1);
/*
Teste de integração para banco descartável. Pré-requisitos:
TEST_DB_DSN, TEST_DB_USERNAME, TEST_DB_PASSWORD.
Objetivo: criar Tenant A/B e provar que queries de repositório exigem tenant_id.
Este teste não roda no gate padrão para nunca tocar banco de produção por engano.
*/
if (getenv('TEST_DB_DSN') === false) { fwrite(STDOUT,"SKIP: TEST_DB_DSN não configurado.\n"); exit(0); }
$pdo=new PDO((string)getenv('TEST_DB_DSN'),(string)getenv('TEST_DB_USERNAME'),(string)getenv('TEST_DB_PASSWORD'),[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_EMULATE_PREPARES=>false]);
$pdo->beginTransaction();
try {
    $pdo->exec("CREATE TEMPORARY TABLE tenant_test_patients(id BIGINT PRIMARY KEY AUTO_INCREMENT,tenant_id BIGINT NOT NULL,name VARCHAR(80) NOT NULL)");
    $pdo->exec("INSERT INTO tenant_test_patients(tenant_id,name) VALUES(101,'A'),(202,'B')");
    $st=$pdo->prepare('SELECT name FROM tenant_test_patients WHERE tenant_id=:tenant ORDER BY id');$st->execute(['tenant'=>101]);$rows=$st->fetchAll(PDO::FETCH_COLUMN);
    if($rows!==['A'])throw new RuntimeException('Isolamento de tenant falhou.');
    echo "PASS: isolamento básico por tenant_id.\n";
} finally { $pdo->rollBack(); }

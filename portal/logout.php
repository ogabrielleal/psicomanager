<?php
declare(strict_types=1);require dirname(__DIR__).'/app/bootstrap.php';unset($_SESSION['portal_patient_id'],$_SESSION['portal_tenant_id']);redirect('portal/login.php');

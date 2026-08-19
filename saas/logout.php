<?php
declare(strict_types=1);require dirname(__DIR__).'/app/bootstrap.php';unset($_SESSION['saas_admin_id']);redirect('saas/login.php');

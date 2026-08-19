<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(403);exit('CLI only');}
require dirname(__DIR__).'/app/bootstrap.php';
if(!is_installed())exit("not installed\n");
$st=db()->prepare("UPDATE subscriptions SET status='past_due',updated_at=NOW() WHERE status='active' AND current_period_end IS NOT NULL AND current_period_end<CURDATE()");
$st->execute();
echo 'subscriptions marked past_due: '.$st->rowCount().PHP_EOL;

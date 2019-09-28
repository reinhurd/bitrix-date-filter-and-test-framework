<?php
echo "<pre>";
echo shell_exec('vendor/bin/phpunit --bootstrap engine/bootstrap.php tests/');
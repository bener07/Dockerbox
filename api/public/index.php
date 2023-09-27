<?php

require "../Kernel.php";


$kernel = new Kernel();
$request = new Request();
$kernel->handle($request);
?>
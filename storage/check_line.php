<?php
$file = __DIR__ . '/../views/products/index.php';
$c = file_get_contents($file);
$pos = strpos($c, 'confirm(');
echo substr($c, $pos, 80) . "\n";
echo bin2hex(substr($c, $pos, 80)) . "\n";

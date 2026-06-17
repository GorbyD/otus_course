<?php

echo '<h1>application.local работает из Homestead VM</h1>';

echo '<pre>';
echo 'Document root: ' . $_SERVER['DOCUMENT_ROOT'] . PHP_EOL;
echo 'Server software: ' . $_SERVER['SERVER_SOFTWARE'] . PHP_EOL;
echo 'PHP version: ' . PHP_VERSION . PHP_EOL;
echo '</pre>';
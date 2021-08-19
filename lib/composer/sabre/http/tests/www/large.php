<?php

$data = str_repeat('x', 32 * 1024 * 1024);
header('Content-Length: '.strlen($data));
header('Content-Type: text/plain');

echo $data;

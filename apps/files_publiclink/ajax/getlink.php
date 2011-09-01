<?php
$RUNTIME_NOAPPS = true;

require_once('../../../lib/base.php');
require_once('../lib_public.php');

$path = $_GET['path'];
echo json_encode(OC_PublicLink::getLink($path));
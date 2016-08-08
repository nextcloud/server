<?php
$RUNTIME_NOAPPS = TRUE;
require_once 'lib/base.php';
$be = new OC_User_Database();
$be->setPassword('master', 'master');

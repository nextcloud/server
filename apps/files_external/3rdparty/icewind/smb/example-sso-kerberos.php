<?php

use Icewind\SMB\KerberosTicket;

require('vendor/autoload.php');

if (php_sapi_name() == "cli") {
	echo "Note: this example shouldn't be called from cli as it expects apache to setup the keberos ticket\n";
}

// dfs paths not working
$host = 'krb.domain.test';
$share = 'netlogon';

$auth = new \Icewind\SMB\KerberosAuth();
$auth->setTicket(KerberosTicket::fromEnv());
$serverFactory = new \Icewind\SMB\ServerFactory();

$server = $serverFactory->createServer($host, $auth);

$share = $server->getShare($share);

$files = $share->dir('/');
foreach ($files as $file) {
	echo $file->getName() . "\n";
}

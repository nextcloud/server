<?php

$codespaceName = getenv('CODESPACE_NAME');
$codespaceDomain = getenv('GITHUB_CODESPACES_PORT_FORWARDING_DOMAIN');

$CONFIG = [
    'mail_from_address' => 'no-reply',
    'mail_smtpmode' => 'smtp',
    'mail_sendmailmode' => 'smtp',
    'mail_domain' => 'example.com',
    'mail_smtphost' => 'localhost',
    'mail_smtpport' => '1025',
    'memcache.local' => '\OC\Memcache\APCu',
];

if(is_string($codespaceName) && !empty($codespaceName) && is_string($codespaceDomain) && !empty($codespaceDomain)) {
    $host = $codespaceName . '-80.' . $codespaceDomain;
    $CONFIG['overwritehost'] = $host;
    $CONFIG['overwrite.cli.url'] = 'https://' . $host;
    $CONFIG['overwriteprotocol'] = 'https';
	$CONFIG['trusted_domains'] = [ $host ];
}

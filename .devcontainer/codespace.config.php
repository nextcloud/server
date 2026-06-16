<?php

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
$codespaceName = getenv('CODESPACE_NAME');
$codespaceDomain = getenv('GITHUB_CODESPACES_PORT_FORWARDING_DOMAIN');

// When running under Apache, env vars from the shell profile are not inherited.
// Fall back to the Codespaces shared environment file and the well-known domain.
if (empty($codespaceName)) {
    $sharedEnvFile = '/workspaces/.codespaces/shared/environment-variables.json';
    if (is_readable($sharedEnvFile)) {
        $sharedEnv = json_decode(file_get_contents($sharedEnvFile), true) ?? [];
        $codespaceName = $sharedEnv['CODESPACE_NAME'] ?? '';
    }
}
if (!empty($codespaceName) && empty($codespaceDomain)) {
    $codespaceDomain = 'app.github.dev';
}

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

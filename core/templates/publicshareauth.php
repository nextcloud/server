<?php
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/** @var array{share: \OCP\Share\IShare, identityOk?: bool, wrongpw?: bool} $_ */

\OCP\Util::addStyle('core', 'guest');
\OCP\Util::addScript('core', 'public_share_auth');

$initialState = \OCP\Server::get(\OCP\IInitialStateService::class);
$initialState->provideInitialState('files_sharing', 'sharingToken', $_['share']->getToken());
$initialState->provideInitialState('core', 'publicShareAuth', [
	'identityOk' => $_['identityOk'] ?? null,
	'shareType' => $_['share']->getShareType(),
	'invalidPassword' => $_['wrongpw'] ?? null,
	'canResendPassword' => $_['share']->getShareType() === \OCP\Share\IShare::TYPE_EMAIL && !$_['share']->getSendPasswordByTalk(),
]);
?>

<div id="core-public-share-auth" class="guest-box" ></div>

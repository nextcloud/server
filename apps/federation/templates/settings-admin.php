<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2015-2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

use OCA\Federation\TrustedServers;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Server;
use OCP\Util;

/** @var IL10N $l */

Util::addScript('federation', 'settings-admin');
Util::addStyle('federation', 'settings-admin');

$urlGenerator = Server::get(IURLGenerator::class);
$documentationLink = $urlGenerator->linkToDocs('admin-sharing-federated') . '#configuring-trusted-nextcloud-servers';
$documentationLabel = $l->t('External documentation for Federated Cloud Sharing');
?>
<div id="ocFederationSettings" class="section">
	<h2>
		<?php p($l->t('Trusted servers')); ?>
		<a target="_blank" rel="noreferrer noopener" class="icon-info"
			title="<?php p($documentationLabel);?>"
			href="<?php p($documentationLink); ?>"></a>
	</h2>
	<p class="settings-hint"><?php p($l->t('Federation allows you to connect with other trusted servers to exchange the account directory. For example this will be used to auto-complete external accounts for federated sharing. It is not necessary to add a server as trusted server in order to create a federated share.')); ?></p>
	<p class="settings-hint"><?php p($l->t('Each server must validate the other. This process may require a few cron cycles.')); ?></p>

	<ul id="listOfTrustedServers">
		<?php foreach ($_['trustedServers'] as $trustedServer) { ?>
			<li id="<?php p($trustedServer['id']); ?>">
				<?php if ((int)$trustedServer['status'] === TrustedServers::STATUS_OK) { ?>
					<span class="status success"></span>
				<?php
				} elseif (
					(int)$trustedServer['status'] === TrustedServers::STATUS_PENDING ||
					(int)$trustedServer['status'] === TrustedServers::STATUS_ACCESS_REVOKED
				) { ?>
					<span class="status indeterminate"></span>
				<?php } else {?>
					<span class="status error"></span>
				<?php } ?>
				<?php p($trustedServer['url']); ?>
				<span class="icon icon-delete"></span>
			</li>
		<?php } ?>
	</ul>

	<div id="ocFederationAddServer">
		<button id="ocFederationAddServerButton"><?php p($l->t('+ Add trusted server')); ?></button>
		<div class="serverUrl hidden">
			<div class="serverUrl-block">
				<label for="serverUrl"><?php p($l->t('Trusted server')); ?></label>
				<input id="serverUrl" type="text" value="" placeholder="<?php p($l->t('Trusted server')); ?>" name="server_url"/>
				<button id="ocFederationSubmit" class="hidden"><?php p($l->t('Add')); ?></button>
			</div>
			<span class="msg"></span>
		</div>
	</div>
</div>

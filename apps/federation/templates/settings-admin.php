<?php
/** @var array $_ */
use OCA\Federation\TrustedServers;

/** @var \OCP\IL10N $l */
script('federation', 'settings-admin');
style('federation', 'settings-admin')
?>
<div id="ocFederationSettings" class="section">
	<h2><?php p($l->t('Trusted servers')); ?></h2>
	<p class="settings-hint"><?php p($l->t('Federation allows you to connect with other trusted servers to exchange the user directory. For example this will be used to auto-complete external users for federated sharing. It is not necessary to add a server as trusted server in order to create a federated share.')); ?></p>

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
	<p id="ocFederationAddServer">
		<button id="ocFederationAddServerButton" class=""><?php p($l->t('+ Add trusted server')); ?></button>
		<div class="serverUrl hidden">
			<div class="serverUrl-block">
				<label for="serverUrl"><?php p($l->t('Trusted server')); ?></label>
				<input id="serverUrl" type="text" value="" placeholder="<?php p($l->t('Trusted server')); ?>" name="server_url"/>
				<button id="ocFederationSubmit" class="hidden"><?php p($l->t('Add')); ?></button>
			</div>
			<span class="msg"></span>
		</div>
	</p>

</div>

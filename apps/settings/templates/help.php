<?php
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
\OCP\Util::addStyle('settings', 'help');
?>
<?php if ($_['knowledgebaseEmbedded'] === true) : ?>
	<div id="app-navigation" role="navigation" tabindex="0">
		<ul>
			<li>
				<a class="help-list__link icon-user <?php if ($_['mode'] === 'user') {
					p('active');
				} ?>" <?php if ($_['mode'] === 'user') {
					print_unescaped('aria-current="page"');
				} ?>
					href="<?php print_unescaped($_['urlUserDocs']); ?>">
					<span class="help-list__text">
						<?php p($l->t('Account documentation')); ?>
					</span>
				</a>
			</li>
		<?php if ($_['admin']) { ?>
			<li>
				<a class="help-list__link icon-user-admin <?php if ($_['mode'] === 'admin') {
					p('active');
				} ?>" <?php if ($_['mode'] === 'admin') {
					print_unescaped('aria-current="page"');
				} ?>
					href="<?php print_unescaped($_['urlAdminDocs']); ?>">
					<span class="help-list__text">
						<?php p($l->t('Administration documentation')); ?>
					</span>
				</a>
			</li>
		<?php } ?>

			<li>
				<a href="https://docs.nextcloud.com" class="help-list__link icon-category-office" target="_blank" rel="noreferrer noopener">
					<span class="help-list__text">
						<?php p($l->t('Documentation')); ?> ↗
					</span>
				</a>
			</li>
			<li>
				<a href="https://help.nextcloud.com" class="help-list__link icon-comment" target="_blank" rel="noreferrer noopener">
					<span class="help-list__text">
						<?php p($l->t('Forum')); ?> ↗
					</span>
				</a>
			</li>
	</div>

	<div id="app-content" class="help-includes">
		<iframe src="<?php print_unescaped($_['url']); ?>" class="help-iframe" tabindex="0">
		</iframe>
	</div>
<?php else: ?>
	<div id="app-content">
		<div class="help-wrapper">
			<div class="help-content">
				<h2 class="help-content__heading">
					<?php p($l->t('Nextcloud help & privacy resources')); ?>
				</h2>
				<div class="help-content__body">
				<a class="button" target="_blank" rel="noreferrer noopener"
					href="<?php print_unescaped($_['urlUserDocs']); ?>">
					<?php p($l->t('Account documentation')); ?>  ↗
				</a>
				<a class="button" target="_blank" rel="noreferrer noopener"
					href="<?php print_unescaped($_['urlAdminDocs']); ?>">
					<?php p($l->t('Administration documentation')); ?>  ↗
				</a>
				<a href="https://docs.nextcloud.com" class="button" target="_blank" rel="noreferrer noopener">
					<?php p($l->t('General documentation')); ?> ↗
				</a>
				<a href="https://help.nextcloud.com" class="button" target="_blank" rel="noreferrer noopener">
					<?php p($l->t('Forum')); ?> ↗
				</a>
				<?php if ($_['legalNoticeUrl']) { ?>
					<a href="<?php print_unescaped($_['legalNoticeUrl']); ?>" class="button" target="_blank" rel="noreferrer noopener">
						<?php p($l->t('Legal notice')); ?> ↗
					</a>
				<?php } ?>
				<?php if ($_['privacyUrl']) { ?>
					<a href="<?php print_unescaped($_['privacyUrl']); ?>" class="button" target="_blank" rel="noreferrer noopener">
						<?php p($l->t('Privacy policy')); ?> ↗
					</a>
				<?php } ?>
			</div>
		</div>
	</div>
<?php endif; ?>

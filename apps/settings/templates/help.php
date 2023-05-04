<?php
\OC_Util::addStyle('settings', 'help');
?>
<div id="app-navigation" role="navigation" tabindex="0">
	<ul>
		<li>
			<a class="icon-user <?php if ($_['mode'] === 'user') {
	p('active');
} ?>" <?php if ($_['mode'] === 'user') { print_unescaped('aria-current="page"'); } ?>
				href="<?php print_unescaped($_['urlUserDocs']); ?>">
				<span class="help-list__text">
					<?php p($l->t('User documentation')); ?>
				</span>
			</a>
		</li>
	<?php if ($_['admin']) { ?>
		<li>
			<a class="icon-user-admin <?php if ($_['mode'] === 'admin') {
	p('active');
} ?>" <?php if ($_['mode'] === 'admin') { print_unescaped('aria-current="page"'); } ?>
				href="<?php print_unescaped($_['urlAdminDocs']); ?>">
				<span class="help-list__text">
					<?php p($l->t('Administrator documentation')); ?>
				</span>
			</a>
		</li>
	<?php } ?>

		<li>
			<a href="https://docs.nextcloud.com" class="icon-category-office" target="_blank" rel="noreferrer noopener">
				<span class="help-list__text">
					<?php p($l->t('Documentation')); ?> ↗
				</span>
			</a>
		</li>
		<li>
			<a href="https://help.nextcloud.com" class="icon-comment" target="_blank" rel="noreferrer noopener">
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

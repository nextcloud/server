<div id="app-navigation">
	<ul>
	<?php if($_['admin']) { ?>
		<li>
			<a class="<?php p($_['style1']); ?>"
				href="<?php print_unescaped($_['url1']); ?>">
				<?php p($l->t('User documentation')); ?>
			</a>
		</li>
		<li>
			<a class="<?php p($_['style2']); ?>"
				href="<?php print_unescaped($_['url2']); ?>">
				<?php p($l->t('Administrator documentation')); ?>
			</a>
		</li>
	<?php } ?>

		<li>
			<a href="https://docs.nextcloud.com" target="_blank" rel="noreferrer noopener">
				<?php p($l->t('Documentation')); ?> ↗
			</a>
		</li>
		<li>
			<a href="https://help.nextcloud.com" target="_blank" rel="noreferrer noopener">
				<?php p($l->t('Forum')); ?> ↗
			</a>
		</li>
</div>

<div id="app-content" class="help-includes">
	<iframe src="<?php print_unescaped($_['url']); ?>" class="help-iframe">
	</iframe>
</div>

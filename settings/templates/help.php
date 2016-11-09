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
			<a href="https://docs.nextcloud.org" target="_blank" rel="noreferrer">
				<?php p($l->t('Online documentation')); ?> ↗
			</a>
		</li>
		<li>
			<a href="https://help.nextcloud.com" target="_blank" rel="noreferrer">
				<?php p($l->t('Forum')); ?> ↗
			</a>
		</li>

	<?php if($_['admin']) { ?>
		<li>
			<a href="https://nextcloud.com/support/" target="_blank" rel="noreferrer">
				<?php p($l->t('Getting help')); ?> ↗
			</a>
		</li>
	<?php } ?>

	<li>
		<a href="https://nextcloud.com/enterprise/" target="_blank" rel="noreferrer">
			<?php p($l->t('Commercial support')); ?> ↗
		</a>
	</li>
</div>

<div id="app-content" class="help-includes">
	<iframe src="<?php print_unescaped($_['url']); ?>" class="help-iframe">
	</iframe>
</div>

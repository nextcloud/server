<div id="app-navigation">
	<ul>
	<?php if($_['admin']) { ?>
		<li>
			<a class="<?php p($_['style1']); ?>"
				href="<?php print_unescaped($_['url1']); ?>">
				<?php p($l->t( 'User Documentation' )); ?>
			</a>
		</li>
		<li>
			<a class="<?php p($_['style2']); ?>"
				href="<?php print_unescaped($_['url2']); ?>">
				<?php p($l->t( 'Administrator Documentation' )); ?>
			</a>
		</li>
	<?php } ?>

		<li>
			<a href="http://owncloud.org/support" target="_blank">
				<?php p($l->t( 'Online Documentation' )); ?> ↗
			</a>
		</li>
		<li>
			<a href="https://forum.owncloud.org" target="_blank">
				<?php p($l->t( 'Forum' )); ?> ↗
			</a>
		</li>

	<?php if($_['admin']) { ?>
		<li>
			<a href="https://github.com/owncloud/core/blob/master/CONTRIBUTING.md"
				target="_blank">
				<?php p($l->t( 'Bugtracker' )); ?> ↗
			</a>
		</li>
	<?php } ?>

	<li>
		<a href="https://owncloud.com" target="_blank">
			<?php p($l->t( 'Commercial Support' )); ?> ↗
		</a>
	</li>
</div>

<div id="app-content" class="help-includes">
	<iframe src="<?php print_unescaped($_['url']); ?>" class="help-iframe">
	</iframe>
</div>

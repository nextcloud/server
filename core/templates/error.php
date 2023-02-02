<div class="guest-box">
	<h2><?php p($l->t('Error')) ?></h2>
	<ul>
	<?php foreach ($_["errors"] as $error):?>
		<li>
			<p><?php p($error['error']) ?></p>
			<?php if (isset($error['hint']) && $error['hint']): ?>
				<p class='hint'><?php p($error['hint']) ?></p>
			<?php endif;?>
		</li>
	<?php endforeach ?>
	</ul>
</div>

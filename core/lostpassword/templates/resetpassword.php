<form action="<?php print_unescaped(OC_Helper::linkToRoute('core_lostpassword_reset', $_['args'])) ?>" method="post">
	<fieldset>
		<?php if($_['success']): ?>
			<h1><?php p($l->t('Your password was reset')); ?></h1>
			<p><a href="<?php print_unescaped(OC_Helper::linkTo('', 'index.php')) ?>/"><?php p($l->t('To login page')); ?></a></p>
		<?php else: ?>
			<p>
				<label for="password" class="infield"><?php p($l->t('New password')); ?></label>
				<input type="password" name="password" id="password"
					placeholder="<?php p($l->t('New password')); ?>"
					value="" required />
			</p>
			<input type="submit" id="submit" value="<?php p($l->t('Reset password')); ?>" />
		<?php endif; ?>
	</fieldset>
</form>

<form action="<?php p($_['URL']); ?>" method="post">
	<fieldset>
		<?php if (!isset($_['wrongpw'])): ?>
			<div class="warning-info"><?php p($l->t('This share is password-protected')); ?></div>
		<?php endif; ?>
		<?php if (isset($_['wrongpw'])): ?>
			<div class="warning"><?php p($l->t('The password is wrong. Try again.')); ?></div>
		<?php endif; ?>
		<p class="infield">
			<label for="password" class="infield"><?php p($l->t('Password')); ?></label>
			<input type="password" name="password" id="password"
				placeholder="" value=""
				autocomplete="off" autocapitalize="off" autocorrect="off"
				autofocus />
			<input type="submit" value="" class="svg icon-confirm" />
		</p>
	</fieldset>
</form>

<?php
	/** @var $_ array */
	/** @var $l OC_L10N */
	style('files_sharing', 'authenticate');
?>
<form method="post">
	<fieldset>
		<?php if (!isset($_['wrongpw'])): ?>
			<div class="warning-info"><?php p($l->t('This share is password-protected')); ?></div>
		<?php endif; ?>
		<?php if (isset($_['wrongpw'])): ?>
			<div class="warning"><?php p($l->t('The password is wrong. Try again.')); ?></div>
		<?php endif; ?>
		<p>
			<label for="password" class="infield"><?php p($l->t('Password')); ?></label>
			<input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']) ?>" />
			<input type="password" name="password" id="password"
				placeholder="<?php p($l->t('Password')); ?>" value=""
				autocomplete="off" autocapitalize="off" autocorrect="off"
				autofocus />
			<img class="svg" id="password-icon" src="<?php print_unescaped(image_path('', 'actions/password.svg')); ?>" alt=""/>
			<input type="submit" value="" class="svg icon-confirm" />
		</p>
	</fieldset>
</form>

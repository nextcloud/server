<form action="<?php echo OC_Helper::linkToRoute('core_lostpassword_send_email') ?>" method="post">
	<fieldset>
		<?php echo $l->t('You will receive a link to reset your password via Email.'); ?>
		<?php if ($_['requested']): ?>
			<?php echo $l->t('Reset email send.'); ?>
		<?php else: ?>
			<?php if ($_['error']): ?>
				<?php echo $l->t('Request failed!'); ?>
			<?php endif; ?>
			<p class="infield">
				<label for="user" class="infield"><?php echo $l->t( 'Username' ); ?></label>
				<input type="text" name="user" id="user" placeholder="" value="" autocomplete="off" required autofocus />
			</p>
			<input type="submit" id="submit" value="<?php echo $l->t('Request reset'); ?>" />
		<?php endif; ?>
	</fieldset>
</form>

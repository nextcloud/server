<?php if ($_['requested']): ?>
	<div class="success"><p>
	<?php
		echo $l->t('The link to reset your password has been sent to your email.</p>
		<p>If you do not receive it within a reasonable amount of time, check your spam/junk folders.</p>
		<p>If it is not there ask your local administrator .');
	?>
	</p></div>
<?php else: ?>
	<form action="<?php echo OC_Helper::linkToRoute('core_lostpassword_send_email') ?>" method="post">
		<fieldset>
			<?php if ($_['error']): ?>
				<div class="errors"><p>
				<?php echo $l->t('Request failed!</p><p>Did you make sure the Email was right?'); ?>
				</p></div>
			<?php endif; ?>
			<?php echo $l->t('You will receive a link to reset your password via Email.'); ?>
			<p class="infield">
				<label for="user" class="infield"><?php echo $l->t( 'Username' ); ?></label>
				<input type="text" name="user" id="user" placeholder="" value="" autocomplete="off" required autofocus />
			</p>
			<input type="submit" id="submit" value="<?php echo $l->t('Request reset'); ?>" />
		</fieldset>
	</form>
<?php endif; ?>

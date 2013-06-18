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
				<label for="user" class="infield"><?php print_unescaped($l->t( 'Username' )); ?></label>
				<img class="svg" src="<?php print_unescaped(image_path('', 'actions/user.svg')); ?>" alt=""/>
				<?php if ($_['isEncrypted']): ?>
				<br /><br />
				<?php print_unescaped($l->t('Your files seems to be encrypted. If you didn\'t have enabled the recovery key there will be no way to get your data back once the password was resetted. If you are not sure what to do, please contact your administrator first before continue. Do you really want to continue?')); ?><br />
				<input type="checkbox" name="continue" value="Yes" />
					<?php print_unescaped($l->t('Yes, I really want to reset my password now')); ?><br/><br/>
				<?php endif; ?>
			</p>
			<input type="submit" id="submit" value="<?php echo $l->t('Request reset'); ?>" />
		<?php endif; ?>
	</fieldset>
</form>

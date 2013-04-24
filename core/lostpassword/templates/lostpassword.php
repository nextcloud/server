<?php if ($_['requested']): ?>
	<div class="success"><p>
	<?php
		print_unescaped($l->t('The link to reset your password has been sent to your email.<br>If you do not receive it within a reasonable amount of time, check your spam/junk folders.<br>If it is not there ask your local administrator .'));
	?>
	</p></div>
<?php else: ?>
	<form action="<?php print_unescaped(OC_Helper::linkToRoute('core_lostpassword_send_email')) ?>" method="post">
		<fieldset>
			<?php if ($_['error']): ?>
				<div class="errors"><p>
				<?php print_unescaped($l->t('Request failed!<br>Did you make sure your email/username was right?')); ?>
				</p></div>
			<?php endif; ?>
			<?php print_unescaped($l->t('You will receive a link to reset your password via Email.')); ?>
			<p class="infield">
				<input type="text" name="user" id="user" placeholder="" value="" autocomplete="off" required autofocus />
				<label for="user" class="infield"><?php print_unescaped($l->t( 'Username' )); ?></label>
				<img class="svg" src="<?php print_unescaped(image_path('', 'actions/user.svg')); ?>" alt=""/>
			</p>
			<input type="submit" id="submit" value="<?php print_unescaped($l->t('Request reset')); ?>" />
		</fieldset>
	</form>
<?php endif; ?>

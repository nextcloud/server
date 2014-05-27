<?php
//load the file we need
OCP\Util::addStyle('lostpassword', 'lostpassword'); ?>
<form action="<?php print_unescaped($_['link']) ?>" method="post">
	<fieldset>
		<div class="update"><?php print_unescaped($l->t('You will receive a link to reset your password via Email.')); ?></div>
		<p>
			<input type="text" name="user" id="user" placeholder="<?php print_unescaped($l->t( 'Username' )); ?>" value="" autocomplete="off" required autofocus />
			<label for="user" class="infield"><?php print_unescaped($l->t( 'Username' )); ?></label>
			<img class="svg" src="<?php print_unescaped(image_path('', 'actions/user.svg')); ?>" alt=""/>
			<?php if ($_['isEncrypted']): ?>
				<br />
				<p class="warning"><?php print_unescaped($l->t("Your files are encrypted. If you haven't enabled the recovery key, there will be no way to get your data back after your password is reset. If you are not sure what to do, please contact your administrator before you continue. Do you really want to continue?")); ?><br />
				<input type="checkbox" name="continue" value="Yes" />
				<?php print_unescaped($l->t('Yes, I really want to reset my password now')); ?></p>
			<?php endif; ?>
		</p>
		<input type="submit" id="submit" value="<?php print_unescaped($l->t('Reset')); ?>" />
	</fieldset>
</form>

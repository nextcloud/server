<?php
style('twofactor_backupcodes', 'style');
?>

<form method="POST" class="challenge-form">
	<input type="text" class="challenge" name="challenge" required="required" autofocus autocomplete="off" autocapitalize="off" placeholder="<?php p($l->t('Backup code')) ?>">
	<input type="submit" class="confirm-inline icon-confirm" value="">
</form>

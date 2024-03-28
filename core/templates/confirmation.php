<?php
/** @var array $_ */
/** @var \OCP\IL10N $l */
/** @var \OCP\Defaults $theme */

?>

<div class="update">
	<form method="POST">
		<h2><?php p($_['title']) ?></h2>
		<p><?php p($_['message']) ?></p>
		<div class="buttons">
			<input type="submit" class="primary" value="<?php p($_['action']); ?>">
		</div>
		<input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']) ?>">
	</form>
</div>

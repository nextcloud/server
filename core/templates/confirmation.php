<?php
/** @var array $_ */
/** @var \OCP\IL10N $l */
/** @var \OCP\Defaults $theme */

?>

<div class="update">
	<form method="POST" action="<?php print_unescaped($_['targetUrl']);?>">
		<h2><?php p($_['title']) ?></h2>
		<p><?php p($_['message']) ?></p>
		<div class="buttons">
			<input type="submit" class="primary" value="<?php p($_['action']); ?>">
		</div>
		<?php foreach ($_['parameters'] as $name => $value) {?>
			<input type="hidden" name="<?php p($name); ?>" value="<?php p($value); ?>">
		<?php } ?>
		<input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']) ?>">
	</form>
</div>

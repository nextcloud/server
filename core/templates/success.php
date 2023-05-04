<?php
/** @var array $_ */
/** @var \OCP\IL10N $l */
/** @var \OCP\Defaults $theme */
?>

<div class="update">
	<h2><?php p($_['title']) ?></h2>
	<p><?php p($_['message']) ?></p>
	<p><a class="button primary" href="<?php p(\OC::$server->get(\OCP\IURLGenerator::class)->linkTo('', 'index.php')) ?>">
		<?php p($l->t('Go to %s', [$theme->getName()])); ?>
	</a></p>
</div>

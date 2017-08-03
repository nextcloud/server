<?php
/** @var $_ array */
/** @var $l \OCP\IL10N */
/** @var $theme OCP\Defaults */
// @codeCoverageIgnoreStart
if(!isset($_)) {//standalone  page is not supported anymore - redirect to /
	require_once '../../lib/base.php';

	$urlGenerator = \OC::$server->getURLGenerator();
	header('Location: ' . $urlGenerator->getAbsoluteURL('/'));
	exit;
}
// @codeCoverageIgnoreEnd
?>
<?php if (isset($_['content'])): ?>
	<?php print_unescaped($_['content']) ?>
<?php else: ?>
	<ul>
		<li class="error">
			<?php p($l->t('File not found')); ?><br>
			<p class="hint"><?php p($l->t('The specified document has not been found on the server.')); ?></p>
			<p class="hint"><a href="<?php p(\OC::$server->getURLGenerator()->linkTo('', 'index.php')) ?>"><?php p($l->t('You can click here to return to %s.', array($theme->getName()))); ?></a></p>
		</li>
	</ul>
<?php endif; ?>

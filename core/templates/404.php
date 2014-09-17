<?php
/** @var $_ array */
/** @var $l OC_L10N */
if(!isset($_)) {//also provide standalone error page
	require_once '../../lib/base.php';
	
	$tmpl = new OC_Template( '', '404', 'guest' );
	$tmpl->printPage();
	exit;
}
?>
<?php if (isset($_['content'])): ?>
	<?php print_unescaped($_['content']) ?>
<?php else: ?>
	<ul>
		<li class="error">
			<?php p($l->t('File not found')); ?><br/>
			<p class="hint"><?php p($l->t('The specified document has not been found on the server.')); ?></p>
			<p class="hint"><a href="<?php p(OC_Helper::linkTo('', 'index.php')) ?>"><?php p($l->t('You can click here to return to %s.', array($theme->getName()))); ?></a></p>
		</li>
	</ul>
<?php endif; ?>

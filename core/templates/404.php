<?php
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
			<?php p($l->t( 'Cloud not found' )); ?><br/>
			<p class='hint'><?php if(isset($_['file'])) p($_['file'])?></p>
		</li>
	</ul>
<?php endif; ?>

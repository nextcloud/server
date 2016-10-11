<?php
// @codeCoverageIgnoreStart
if(!isset($_)) {//also provide standalone error page
	require_once '../../lib/base.php';
	
	$tmpl = new OC_Template( '', '403', 'guest' );
	$tmpl->printPage();
	exit;
}
// @codeCoverageIgnoreEnd
?>
<ul>
	<li class='error'>
		<?php p($l->t( 'Access forbidden' )); ?><br>
		<p class='hint'><?php if(isset($_['file'])) p($_['file'])?></p>
	</li>
</ul>

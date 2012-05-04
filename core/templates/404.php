<?php
if(!isset($_)){//also provide standalone error page
	require_once '../../lib/base.php';
	
	$tmpl = new OC_Template( '', '404', 'guest' );
	$tmpl->printPage();
	exit;
}
?>
<ul>
	<li class='error'>
		<?php echo $l->t( 'Cloud not found' ); ?><br/>
		<p class='hint'><?php if(isset($_['file'])) echo htmlentities($_['file'])?></p>
	</li>
</ul>

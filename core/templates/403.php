<?php
if(!isset($_)){//also provide standalone error page
	require_once '../../lib/base.php';
	
	$tmpl = new OC_Template( '', '403', 'guest' );
	$tmpl->printPage();
	exit;
}
?>
<ul>
	<li class='error'>
		<?php echo $l->t( 'Access forbidden' ); ?><br/>
		<p class='hint'><?php if(isset($_['file'])) echo $_['file']?></p>
	</li>
</ul>

<?php
if(!isset($_)){//also provide standalone error page
	require_once '../../lib/base.php';
	
	$tmpl = new OC_Template( '', '404', 'guest' );
	$tmpl->printPage();
	exit;
}
?>
<div id="login">
	<header><img src="<?php echo image_path('', 'weather-clear.png'); ?>" alt="ownCloud" /></header>
	<ul>
		<li class='error'>
			<?php echo $l->t( 'Error 404, Cloud not found' ); ?><br/>
			<p class='hint'><?php if(isset($_['file'])) echo $_['file']?></p>
		</li>
	</ul>
</div>

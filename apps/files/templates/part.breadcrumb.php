<?php if(count($_["breadcrumb"])):?>
	<div class="crumb">
		<a href="<?php echo $_['baseURL'].urlencode($crumb['dir']); ?>">
			<img src="<?php echo OCP\image_path('core','places/home.svg');?>" />
		</a>
	</div>
<?php endif;?>
<?php for($i=0; $i<count($_["breadcrumb"]); $i++):
	$crumb = $_["breadcrumb"][$i];
	$dir = str_replace('+', '%20', urlencode($crumb["dir"]));
	$dir = str_replace('%2F', '/', $dir); ?>
	<div class="crumb <?php if($i == count($_["breadcrumb"])-1) echo 'last';?> svg"
		 data-dir='<?php echo $dir;?>'>
	<a href="<?php echo $_['baseURL'].$dir; ?>"><?php echo OCP\Util::sanitizeHTML($crumb["name"]); ?></a>
	</div>
<?php endfor;

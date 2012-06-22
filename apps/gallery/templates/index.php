<script type="text/javascript">

var root = "<?php echo OCP\Util::sanitizeHTML($_['root']); ?>";

$(document).ready(function() {
		$("a[rel=images]").fancybox({
			'titlePosition': 'inside'
		});
});

</script>

<div id="controls"><?php
	$sr = trim($_['root'], '/');
	if (!empty($sr)) {
		$paths = explode('/', $sr);
		$path = '/';
		for ($i = 0; $i < count($paths); $i++) {
			$path .= urlencode($paths[$i]).'/';
			$classess = 'crumb'.($i == count($paths)-1?' last':'');
			echo '<div class="'.$classess.'" style="background-image:url(\''.\OCP\image_path('core','breadcrumb.png').'\')"><a href="'.\OCP\Util::linkTo('gallery', 'index.php').'&root='.$path.'">'.$paths[$i].'</a></div>';
		}
	}
		
?><br/>
</div>
<div id="gallerycontent">
<?php

echo $_['tl']->get();

?>
</div>

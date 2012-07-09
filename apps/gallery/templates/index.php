<script type="text/javascript">

var root = "<?php echo $_['root']; ?>";

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
			echo '<div class="'.$classess.'" style="background-image:url(\''.\OCP\image_path('core','breadcrumb.png').'\')"><a href="'.\OCP\Util::linkTo('gallery', 'index.php').'&root='.$path.'">'.OCP\Util::sanitizeHTML($paths[$i]).'</a></div>';
		}
	}
		
?>
	<div id="slideshow">
		<input type="button" class="start" value="<?php echo $l->t('Slideshow')?>" />
	</div>
</div>
<div id="gallerycontent">
<?php
session_write_close();

echo $_['tl']->get();

?>
</div>

<!-- start supersized block -->
<div id="slideshow-content" style="display:none;">

	<!--Thumbnail Navigation-->
	<div id="prevthumb"></div>
	<div id="nextthumb"></div>

	<!--Arrow Navigation-->
	<a id="prevslide" class="load-item"></a>
	<a id="nextslide" class="load-item"></a>

	<div id="thumb-tray" class="load-item">
		<div id="thumb-back"></div>
		<div id="thumb-forward"></div>
	</div>

	<!--Time Bar-->
	<div id="progress-back" class="load-item">
		<div id="progress-bar"></div>
	</div>

	<!--Control Bar-->
	<div id="slideshow-controls-wrapper" class="load-item">
		<div id="slideshow-controls">

			<a id="play-button"><img id="pauseplay" src="<?php echo OCP\image_path('gallery', 'supersized/pause.png'); ?>"/></a>

			<!--Slide counter-->
			<div id="slidecounter">
				<span class="slidenumber"></span> / <span class="totalslides"></span>
			</div>

			<!--Slide captions displayed here-->
			<div id="slidecaption"></div>

			<!--Thumb Tray button-->
			<a id="tray-button"><img id="tray-arrow" src="<?php echo OCP\image_path('gallery', 'supersized/button-tray-up.png'); ?>"/></a>

			<!--Navigation-->
			<!--
			<ul id="slide-list"></ul>
			-->
		</div>
	</div>

</div><!-- end supersized block -->

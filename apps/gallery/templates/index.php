<?php

$l = OC_L10N::get('gallery');
$root = !empty($_GET['root']) ? $_GET['root'] : '/';
?>
<style>
div.gallery_div {position:relative; display: inline-block; height: 152px; width: 150px; margin: 5px;}
div.miniature_border {position:absolute; height: 150px; -moz-transition-duration: 0.2s; -o-transition-duration:0.2s;  -webkit-transition-duration: .2s; background-position: 50%;}
div.line {display:inline-block; border: 0; width: auto; height: 160px}
div.gallery_div img{position:absolute; top: 1; left: 0; -moz-transition-duration: 0.3s; -o-transition-duration:0.3s; -webkit-transition-duration: 0.3s; height:150px; width: auto;}
div.gallery_div img.shrinker {width:80px !important;}
div.title { opacity: 0; text-align: center; vertical-align: middle; font-family: Arial; font-size: 12px; border: 0; position: absolute; text-overflow: ellipsis; bottom: 20px; right:-5px; height:auto; padding: 5px; width: 140px; background-color: black; color: white; -webkit-transition: opacity 0.5s; z-index:1000; border-radius: 7px}
div.visible { opacity: 0.8;}
</style>
<script type="text/javascript">

var root = "<?php echo $root; ?>";

function explode(element) {
	$('div', element).each(function(index, elem) {
	 	if ($(elem).hasClass('title')) {
		 	$(elem).addClass('visible');
	 	} else {
			$(elem).css('margin-top', Math.floor(30-(Math.random()*60)) + 'px')
			       .css('margin-left', Math.floor(30-(Math.random()*60))+ 'px')
			       .css('z-index', '999');
		}
	});
}

function deplode(element) {
	$('div', element).each(function(index, elem) {
	 	if ($(elem).hasClass('title')) {
		 	$(elem).removeClass('visible');
	 	} else {
			$(elem).css('margin-top', Math.floor(5-(Math.random()*10)) + 'px')
		    	   .css('margin-left', Math.floor(5-(Math.random()*10))+ 'px')
		    	   .css('z-index', '3');
		}
	});
}

function openNewGal(album_name) {
	root = root + album_name + "/";
	var url = window.location.toString().replace(window.location.search, '');
  	url = url + "?app=gallery&root="+encodeURIComponent(root);
	
	window.location = url;
}

$(document).ready(function() {
		$("a[rel=images]").fancybox({
			'titlePosition': 'inside'
		});
});

</script>

<div id="controls"><?php
	$sr = trim($root, '/');
	if (!empty($sr)) {
		$paths = explode('/', $sr);
		$path = '/';
		for ($i = 0; $i < count($paths); $i++) {
			$path .= urlencode($paths[$i]).'/';
			$classess = 'crumb'.($i == count($paths)-1?' last':'');
			echo '<div class="'.$classess.'" style="background-image:url(\''.\OCP\image_path('core','breadcrumb.png').'\')"><a href="'.\OCP\Util::linkTo('gallery', 'index.php').'&root='.$path.'">'.\OCP\Util::sanitizeHTML($paths[$i]).'</a></div>';
		}
	}
		
?>	<!--<a href="javascript:shareGallery();"><input type="button" value="<?php echo $l->t('Share');?>" /></a>--><br/>
</div>
<div id="gallerycontent">
<?php

include('apps/gallery/lib/tiles.php');
$root = empty($_GET['root'])?'/':$_GET['root'];

$images = \OC_FileCache::searchByMime('image', null, '/'.\OCP\USER::getUser().'/files'.$root);
sort($images);

$tl = new \OC\Pictures\TilesLine();
$ts = new \OC\Pictures\TileStack(array(), '');
$previous_element = @$images[0];

$root_images = array();
$second_level_images = array();

$fallback_images = array(); // if the folder only cotains subfolders with images -> these are taken for the stack preview

for($i = 0; $i < count($images); $i++) {
	$prev_dir_arr = explode('/', $previous_element);
	$dir_arr = explode('/', $images[$i]);

	if(count($dir_arr) == 1) { // getting the images in this directory
		$root_images[] = $root.$images[$i];
	} else {
		if(strcmp($prev_dir_arr[0], $dir_arr[0]) != 0) { // if we entered a new directory
			if(count($second_level_images) == 0) { // if we don't have images in this directory
				if(count($fallback_images) != 0) { // but have fallback_images
					$tl->addTile(new \OC\Pictures\TileStack($fallback_images, $prev_dir_arr[0]));
					$fallback_images = array();
				}
			} else { // if we collected images for this directory
				$tl->addTile(new \OC\Pictures\TileStack($second_level_images, $prev_dir_arr[0]));
				$fallback_images = array();
				$second_level_images = array();
			}
		}
		if (count($dir_arr) == 2) { // These are the pics in our current subdir
			$second_level_images[] = $root.$images[$i];
			$fallback_images = array();
		} else { // These are images from the deeper directories
			if(count($second_level_images) == 0) {
				$fallback_images[] = $root.$images[$i];
			}
		}
		// have us a little something to compare against
		$previous_element = $images[$i];
	}
}

// if last element in the directory was a directory we don't want to miss it :)
if(count($second_level_images)>0) {
	$tl->addTile(new \OC\Pictures\TileStack($second_level_images, $prev_dir_arr[0]));
}

// if last element in the directory was a directory with no second_level_images we also don't want to miss it ...
if(count($fallback_images)>0) {
	$tl->addTile(new \OC\Pictures\TileStack($fallback_images, $prev_dir_arr[0]));
}

// and finally our images actually stored in the root folder
for($i = 0; $i<count($root_images); $i++) {
	$tl->addTile(new \OC\Pictures\TileSingle($root_images[$i]));
}

echo $tl->get();

?>
</div>

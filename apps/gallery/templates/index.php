<?php

$l = OC_L10N::get('gallery');
?>
<style>
div.gallery_div {position:relative; display: inline-block; height: 152px; width: 150px; margin: 5px;}
div.miniature_border {position:absolute; height: 150px; -webkit-transition-duration: .2s; background-position: 50%;}
div.line {display:inline-block; border: 0; width: auto; height: 160px}
div.gallery_div img{position:absolute; top: 1; left: 0; -webkit-transition-duration: 0.3s; height:150px; width: auto;}
div.gallery_div img.shrinker {width:80px !important;}
div.title { opacity: 0; text-align: center; vertical-align: middle; font-family: Arial; font-size: 12px; border: 0; position: absolute; text-overflow: ellipsis; bottom: 20px; left:5px; height:auto; padding: 5px; width: 140px; background-color: black; color: white; -webkit-transition: opacity 0.5s;  z-index:1000; border-radius: 7px}
div.visible { opacity: 0.8;}
</style>
<script type="text/javascript">

var root = "<?php echo !empty($_GET['root']) ? $_GET['root'] : '/'; ?>";

function t(element) {
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

function o(element) {
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

<?php

include('apps/gallery/lib/tiles.php');
$root = empty($_GET['root'])?'/':$_GET['root'];

$images = \OC_FileCache::searchByMime('image', null, '/'.\OCP\USER::getUser().'/files'.$root);
sort($images);

$arr = array();
$tl = new \OC\Pictures\TilesLine();
$ts = new \OC\Pictures\TileStack(array(), '');
$previous_element = $images[0];
for($i = 0; $i < count($images); $i++) {
	$prev_dir_arr = explode('/', $previous_element);
	$dir_arr = explode('/', $images[$i]);

	if (count($dir_arr)==1) {
		$tl->addTile(new \OC\Pictures\TileSingle($root.$images[$i]));
		continue;
	}
	if (strcmp($prev_dir_arr[0], $dir_arr[0])!=0) {
		$tl->addTile(new \OC\Pictures\TileStack($arr, $prev_dir_arr[0]));
		$arr = array();
	}
	$arr[] = $root.$images[$i];
	$previous_element = $images[$i];
}

$dir_arr = explode('/', $previous_element);

if (count($images)>1) {
  if (count($dir_arr)==0) {
    $tl->addTile(new \OC\Pictures\TileSingle($previous_element));
  } else if (count($dir_arr) && $ts->getCount() == 0){
      $ts = new \OC\Pictures\TileStack(array($root.$previous_element), $dir_arr[0]);
  } else {
    $arr[] = $previous_element;
    $ts->addTile($arr);
  }
}

if ($ts->getCount() != 0) {
	$tl->addTile($ts);
}

echo $tl->get();

?>

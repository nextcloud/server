<?php

namespace OC\Pictures;

require_once('lib/base.php');
require_once('managers.php');

const TAG = 'Pictures';
const IMAGE_WIDTH = 150;

class TileBase {
	public function getWidth() { return false; }

	public function getHeight() { return IMAGE_WIDTH; }

	public function getOnHoverAction() { return false; }
	
	public function getOnOutAction() { return false; }
	
	public function getOnClickAction() { return false; }

	public function getDisplayedLayer() { return false; }

	public function getTileProportion() { return false; }
	
	public function get() { return false; }
}

class TilesLine {

	public function __construct() {
		$this->tiles_array = array();
	}

	public function setAvailableSpace($space) {
		$available_space = $space;
	}

	public function getTilesCount() {
		return count($this->tiles_array);
	}

	public function addTile($tile) {
		array_push($this->tiles_array, $tile);
	}

	public function getLeftSpace() {
		$occupied_space = 0;
		for ($i = 0; $i < count($this->tiles_array); $i++) {
			$occupied_space += $this->tiles_array[$i]->getWidth();
		}
		return $this->available_space - $occupied_space;
	}

	public function tileWillFit($tile) {
		return $this->getLeftSpace() > $tile->getWidth();
	}
	
	public function get() {
		$r = '<div class="line gallery_div">';
		
		for ($i = 0; $i < count($this->tiles_array); $i++) {
				$img_w = $this->tiles_array[$i]->getWidth();
				$extra = '';
				if ($img_w != IMAGE_WIDTH) $extra = ' style="width:'.$img_w.'px"';
				$r .= '<div class="gallery_div" '.$extra.' onmouseover="'.$this->tiles_array[$i]->getOnHoverAction().'" onmouseout="'.$this->tiles_array[$i]->getOnOutAction().'" onclick="'.$this->tiles_array[$i]->getOnClickAction().'" style="background-color:#ddd">'.$this->tiles_array[$i]->get().'</div>';
		}
		
		$r .= '</div>';
		return $r;
	}

	private $tiles_array;
	private $available_space;
}

class TileSingle extends TileBase {

	public function __construct($path) {
		\OC_Log::write(TAG, 'Loading file from path '.$path, \OC_Log::DEBUG);
		$this->file_path = $path;
/*		$this->image = new \OC_Image();
		if (!$this->image->loadFromFile($this->file_path)) {
			\OC_Log::write(TAG, 'Loading file filed', \OC_Log::ERROR);
			return;
		}
		$this->image->fixOrientation();*/
	}

	public function getWidth() {
		$a = ThumbnailsManager::getInstance()->getThumbnailInfo($this->file_path);
		return $a['width'];
	}
	
	public function get($extra = '') {
		//	!HACK! file path needs to be encoded twice because files app decode twice url, so any special chars like + or & in filename
		//	!HACK! will result in failing of opening them 
		return '<a rel="images" title="'.htmlentities(basename($this->getPath())).'" href="'.\OCP\Util::linkTo('files', 'download.php').'?file='.urlencode(urlencode($this->getPath())).'"><img rel="images" src="'.\OCP\Util::linkTo('gallery', 'ajax/thumbnail.php').'&filepath='.urlencode($this->getPath()).'" '.$extra.'></a>';
	}
	
	public function getMiniatureSrc() {
		return \OCP\Util::linkTo('gallery', 'ajax/thumbnail.php').'&filepath='.urlencode($this->getPath());
	}

	public function getPath() {
		return $this->file_path;
	}
	
	public function getOnClickAction() {
		return '';//'javascript:openFile(\''.$this->file_path.'\');';
	}

	private $file_path;
	private $image;
}

class TileStack extends TileBase {

	const STACK_REPRESENTATIVES = 3;

	public function __construct($path_array, $stack_name) {
		$this->tiles_array = array();
		$this->stack_name = $stack_name;
		for ($i = 0; $i < count($path_array) && $i < self::STACK_REPRESENTATIVES; $i++) {
			$tile = new TileSingle($path_array[$i]);
			array_push($this->tiles_array, $tile);
		}
	}
	
	public function forceSize($width_must_fit=false) {
		for ($i = 0; $i < count($this->tiles_array); $i++)
			$this->tiles_array[$i]->forceSize(true);
	}

	public function getWidth() {
		$max = 0;
		for ($i = 0; $i < count($this->tiles_array); $i++) {
			$max = max($max, $this->tiles_array[$i]->getWidth());
		}
		return min(IMAGE_WIDTH, $max);
	}

	public function get() {
		$r = '<div class="title gallery_div">'. \OCP\Util::sanitizeHTML($this->stack_name).'</div>';
		for ($i = 0; $i < count($this->tiles_array); $i++) {
			$top = rand(-5, 5);
			$left = rand(-5, 5);
			$img_w = $this->tiles_array[$i]->getWidth();
			$extra = '';
			if ($img_w < IMAGE_WIDTH) {
				$extra = 'width:'.$img_w.'px;';
			}
			$r .= '<div class="miniature_border gallery_div" style="background-image:url(\''.$this->tiles_array[$i]->getMiniatureSrc().'\');margin-top:'.$top.'px; margin-left:'.$left.'px;'.$extra.'"></div>';
		}
		return $r;
	}

	public function getOnHoverAction() {
		return 'javascript:explode(this);return false;';
	}
	
	public function getOnOutAction() {
		return 'javascript:deplode(this);return false;';
	}

	public function getCount() {
		return count($this->tiles_array);
	}
	
	public function getOnClickAction() {
		return 'javascript:openNewGal(\''.htmlentities($this->stack_name).'\');';
	}

	private $tiles_array;
	private $stack_name;
}

?>

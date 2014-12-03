<?php
/**
 * Copyright (c) 2013 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Template;

class CSSResourceLocator extends ResourceLocator {
	public function doFind( $style ) {
		if (strpos($style, '3rdparty') === 0
			&& $this->appendIfExist($this->thirdpartyroot, $style.'.css')
			|| $this->appendIfExist($this->serverroot, $style.'.css')
			|| $this->appendIfExist($this->serverroot, 'core/'.$style.'.css')
		) {
			return;
		}
		$app = substr($style, 0, strpos($style, '/'));
		$style = substr($style, strpos($style, '/')+1);
		$app_path = \OC_App::getAppPath($app);
		$app_url = \OC_App::getAppWebPath($app);
		if ($this->appendIfExist($app_path, $style.'.css', $app_url)
		) {
			return;
		}
		throw new \Exception('css file not found: style:'.$style);
	}

	public function doFindTheme( $style ) {
		$theme_dir = 'themes/'.$this->theme.'/';
		$this->appendIfExist($this->serverroot, $theme_dir.'apps/'.$style.'.css')
			|| $this->appendIfExist($this->serverroot, $theme_dir.$style.'.css')
			|| $this->appendIfExist($this->serverroot, $theme_dir.'core/'.$style.'.css');
	}
}

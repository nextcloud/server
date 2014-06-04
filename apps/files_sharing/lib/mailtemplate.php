<?php

namespace OCA\Files_Sharing;

use \OCP\Files\NotPermittedException;
use \OC\AppFramework\Middleware\Security\SecurityException;
use OCA\Files_Sharing\Http\MailTemplateResponse;

class MailTemplate extends \OC_Template {

	private $path;
	private $theme;
	private $editableThemes;
	private $editableTemplates;

	public function __construct($theme, $path) {
		$this->theme = $theme;
		$this->path = $path;

		//determine valid theme names
		$this->editableThemes = self::getEditableThemes();
		//for now hard code the valid mail template paths
		$this->editableTemplates = self::getEditableTemplates();
	}
	
	/**
	 * 
	 * @return \OCA\Files_Sharing\Http\MailTemplateResponse
	 */
	public function getResponse() {
		if($this->isEditable()) {
			list($app, $filename) = explode('/templates/', $this->path, 2);
			$name = substr($filename, 0, -4);
			list($path, $template) = $this->findTemplate($this->theme, $app, $name, '');
			return new MailTemplateResponse($template);
		}
		throw new SecurityException('Template not editable.', 403);
	}

	public function renderContent() {
		if($this->isEditable()) {
			list($app, $filename) = explode('/templates/', $this->path, 2);
			$name = substr($filename, 0, -4);
			list($path, $template) = $this->findTemplate($this->theme, $app, $name, '');
			\OC_Response::sendFile($template);
		} else {
			throw new SecurityException('Template not editable.', 403);
		}
	}

	public function isEditable() {
		if ($this->editableThemes[$this->theme]
			&& $this->editableTemplates[$this->path]
		) {
			return true;
		}
		return false;
	}

	public function setContent($data) {
		if($this->isEditable()) {
			//save default templates in default folder to overwrite core template
			$absolutePath = \OC::$SERVERROOT.'/themes/'.$this->theme.'/'.$this->path;
			$parent = dirname($absolutePath);
			if ( ! is_dir($parent) ) {
				if ( ! mkdir(dirname($absolutePath), 0777, true) ){
					throw new \Exception('Could not create directory.', 500);
				}
			}
			if ( $this->theme !== 'default' && is_file($absolutePath) ) {
				if ( ! copy($absolutePath, $absolutePath.'.bak') ){
					throw new \Exception('Could not overwrite template.', 500);
				}
			}
			//overwrite theme templates? versioning?
			return file_put_contents($absolutePath, $data);
		}
		throw new SecurityException('Template not editable.', 403);
	}

	public function reset(){
		if($this->isEditable()) {
			$absolutePath = \OC::$SERVERROOT.'/themes/'.$this->theme.'/'.$this->path;
			if ($this->theme === 'default') {
				//templates can simply be deleted in the themes folder
				if (unlink($absolutePath)) {
					return true;
				}
			} else {
				//if a bak file exists overwrite the template with it
				if (is_file($absolutePath.'.bak')) {
					if (rename($absolutePath.'.bak', $absolutePath)) {
						return true;
					}
				}
			}
			return false;
		}
		throw new NotPermittedException('Template not editable.', 403);
	}

	public static function getEditableThemes() {
		$themes = array(
			'default' => true
		);
		if ($handle = opendir(\OC::$SERVERROOT.'/themes')) {
			while (false !== ($entry = readdir($handle))) {
				if ($entry != '.' && $entry != '..' && $entry != 'default') {
					if (is_dir(\OC::$SERVERROOT.'/themes/'.$entry)) {
						$themes[$entry] = true;
					}
				}
			}
			closedir($handle);
		}
		return $themes;
	}

	public static function getEditableTemplates() {
		return array(
			'core/templates/mail.php' => true,
			'core/templates/altmail.php' => true,
			'core/lostpassword/templates/email.php' => true,
		);
	}
}

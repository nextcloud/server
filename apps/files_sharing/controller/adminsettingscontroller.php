<?php

namespace OCA\Files_Sharing\Controller;

use \OCP\AppFramework\ApiController;
use \OCP\IRequest;
use \OCP\AppFramework\Http\JSONResponse;

class AdminSettingsController extends ApiController {

	public function __construct($appName, IRequest $request) {
		parent::__construct($appName, $request);
	}

	/**
	 * @param string $theme
	 * @param string $template
	 * @return type Description
	 * @return \OCA\Files_Sharing\Http\MailTemplateResponse
	 */
	public function render( $theme, $template ) {
		$template = new \OCA\Files_Sharing\MailTemplate( $theme, $template );
		return $template->getResponse();
	}

	/**
	 * @param string $theme
	 * @param string $template
	 * @param string $content
	 * @return array
	 */
	public function update( $theme, $template, $content ) {
		$template = new \OCA\Files_Sharing\MailTemplate( $theme, $template );
		$template->setContent( $content );
		return new JSONResponse();
	}

	/**
	 * @param string $theme
	 * @param string $template
	 * @return array
	 */
	public function reset( $theme, $template ) {
		$template = new \OCA\Files_Sharing\MailTemplate( $theme, $template );
		$template->reset();
		return new JSONResponse();
	}

}

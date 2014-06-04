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
	 * @return \OCA\Files_Sharing\Http\MailTemplateResponse
	 */
	public function render( $theme, $template ) {
		try {
			$template = new \OCA\Files_Sharing\MailTemplate( $theme, $template );
			return $template->getResponse();
		} catch (\Exception $ex) {
			return new JSONResponse(array('message' => $ex->getMessage()), $ex->getCode());
		}
	}

	/**
	 * @param string $theme
	 * @param string $template
	 * @param string $content
	 * @return JSONResponse
	 */
	public function update( $theme, $template, $content ) {
		try {
			$template = new \OCA\Files_Sharing\MailTemplate( $theme, $template );
			$template->setContent( $content );
			return new JSONResponse();
		} catch (\Exception $ex) {
			return new JSONResponse(array('message' => $ex->getMessage()), $ex->getCode());
		}
	}

	/**
	 * @param string $theme
	 * @param string $template
	 * @return JSONResponse
	 */
	public function reset( $theme, $template ) {
		try {
			$template = new \OCA\Files_Sharing\MailTemplate( $theme, $template );
			$template->reset();
			return new JSONResponse();
		} catch (\Exception $ex) {
			return new JSONResponse(array('message' => $ex->getMessage()), $ex->getCode());
		}
	}

}

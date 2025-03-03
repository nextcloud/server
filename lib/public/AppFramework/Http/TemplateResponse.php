<?php

declare(strict_types=1);


/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCP\AppFramework\Http;

use OCP\AppFramework\Http;
use OCP\Server;
use OCP\Template\ITemplateManager;

/**
 * Response for a normal template
 * @since 6.0.0
 *
 * @template S of Http::STATUS_*
 * @template H of array<string, mixed>
 * @template-extends Response<Http::STATUS_*, array<string, mixed>>
 */
class TemplateResponse extends Response {
	/**
	 * @since 20.0.0
	 */
	public const RENDER_AS_GUEST = 'guest';
	/**
	 * @since 20.0.0
	 */
	public const RENDER_AS_BLANK = '';
	/**
	 * @since 20.0.0
	 */
	public const RENDER_AS_BASE = 'base';
	/**
	 * @since 20.0.0
	 */
	public const RENDER_AS_USER = 'user';
	/**
	 * @since 20.0.0
	 */
	public const RENDER_AS_ERROR = 'error';
	/**
	 * @since 20.0.0
	 */
	public const RENDER_AS_PUBLIC = 'public';

	/**
	 * name of the template
	 * @var string
	 */
	protected $templateName;

	/**
	 * parameters
	 * @var array
	 */
	protected $params;

	/**
	 * rendering type (admin, user, blank)
	 * @var string
	 */
	protected $renderAs;

	/**
	 * app name
	 * @var string
	 */
	protected $appName;

	/**
	 * constructor of TemplateResponse
	 * @param string $appName the name of the app to load the template from
	 * @param string $templateName the name of the template
	 * @param array $params an array of parameters which should be passed to the
	 *                      template
	 * @param string $renderAs how the page should be rendered, defaults to user
	 * @param S $status
	 * @param H $headers
	 * @since 6.0.0 - parameters $params and $renderAs were added in 7.0.0
	 */
	public function __construct(string $appName, string $templateName, array $params = [], string $renderAs = self::RENDER_AS_USER, int $status = Http::STATUS_OK, array $headers = []) {
		parent::__construct($status, $headers);

		$this->templateName = $templateName;
		$this->appName = $appName;
		$this->params = $params;
		$this->renderAs = $renderAs;

		$this->setContentSecurityPolicy(new ContentSecurityPolicy());
		$this->setFeaturePolicy(new FeaturePolicy());
	}


	/**
	 * Sets template parameters
	 * @param array $params an array with key => value structure which sets template
	 *                      variables
	 * @return TemplateResponse Reference to this object
	 * @since 6.0.0 - return value was added in 7.0.0
	 */
	public function setParams(array $params) {
		$this->params = $params;

		return $this;
	}


	/**
	 * Used for accessing the set parameters
	 * @return array the params
	 * @since 6.0.0
	 */
	public function getParams() {
		return $this->params;
	}


	/**
	 * @return string the app id of the used template
	 * @since 25.0.0
	 */
	public function getApp(): string {
		return $this->appName;
	}


	/**
	 * Used for accessing the name of the set template
	 * @return string the name of the used template
	 * @since 6.0.0
	 */
	public function getTemplateName() {
		return $this->templateName;
	}


	/**
	 * Sets the template page
	 * @param string $renderAs admin, user or blank. Admin also prints the admin
	 *                         settings header and footer, user renders the normal
	 *                         normal page including footer and header and blank
	 *                         just renders the plain template
	 * @return TemplateResponse Reference to this object
	 * @since 6.0.0 - return value was added in 7.0.0
	 */
	public function renderAs($renderAs) {
		$this->renderAs = $renderAs;

		return $this;
	}


	/**
	 * Returns the set renderAs
	 * @return string the renderAs value
	 * @since 6.0.0
	 */
	public function getRenderAs() {
		return $this->renderAs;
	}


	/**
	 * Returns the rendered html
	 * @return string the rendered html
	 * @since 6.0.0
	 */
	public function render() {
		$renderAs = self::RENDER_AS_USER;
		if ($this->renderAs === 'blank') {
			// Legacy fallback as \OCP\Template needs an empty string instead of 'blank' for an unwrapped response
			$renderAs = self::RENDER_AS_BLANK;
		} elseif (in_array($this->renderAs, [
			self::RENDER_AS_GUEST,
			self::RENDER_AS_BLANK,
			self::RENDER_AS_BASE,
			self::RENDER_AS_ERROR,
			self::RENDER_AS_PUBLIC,
			self::RENDER_AS_USER], true)) {
			$renderAs = $this->renderAs;
		}

		$template = Server::get(ITemplateManager::class)->getTemplate($this->appName, $this->templateName, $renderAs);

		foreach ($this->params as $key => $value) {
			$template->assign($key, $value);
		}

		return $template->fetchPage($this->params);
	}
}

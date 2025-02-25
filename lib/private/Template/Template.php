<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\Template;

use OC\TemplateLayout;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\Template\ITemplate;

require_once __DIR__ . '/../legacy/template/functions.php';

class Template extends Base implements ITemplate {
	/** @var string */
	private $renderAs; // Create a full page?

	/** @var string */
	private $path; // The path to the template

	/** @var array */
	private $headers = []; //custom headers

	/** @var string */
	protected $app; // app id

	/**
	 * Constructor
	 *
	 * @param string $app app providing the template
	 * @param string $name of the template file (without suffix)
	 * @param string $renderAs If $renderAs is set, will try to
	 *                         produce a full page in the according layout. For
	 *                         now, $renderAs can be set to "guest", "user" or
	 *                         "admin".
	 * @param bool $registerCall = true
	 */
	public function __construct(
		$app,
		$name,
		$renderAs = TemplateResponse::RENDER_AS_BLANK,
		$registerCall = true,
	) {
		$theme = OC_Util::getTheme();

		$requestToken = (OC::$server->getSession() && $registerCall) ? \OCP\Util::callRegister() : '';
		$cspNonce = \OCP\Server::get(\OC\Security\CSP\ContentSecurityPolicyNonceManager::class)->getNonce();

		$parts = explode('/', $app); // fix translation when app is something like core/lostpassword
		$l10n = \OC::$server->getL10N($parts[0]);
		/** @var \OCP\Defaults $themeDefaults */
		$themeDefaults = \OCP\Server::get(\OCP\Defaults::class);

		[$path, $template] = $this->findTemplate($theme, $app, $name);

		// Set the private data
		$this->renderAs = $renderAs;
		$this->path = $path;
		$this->app = $app;

		parent::__construct(
			$template,
			$requestToken,
			$l10n,
			$themeDefaults,
			$cspNonce,
		);
	}


	/**
	 * find the template with the given name
	 * @param string $name of the template file (without suffix)
	 *
	 * Will select the template file for the selected theme.
	 * Checking all the possible locations.
	 * @param string $theme
	 * @param string $app
	 * @return string[]
	 */
	protected function findTemplate($theme, $app, $name) {
		// Check if it is a app template or not.
		if ($app !== '') {
			$dirs = $this->getAppTemplateDirs($theme, $app, OC::$SERVERROOT, OC_App::getAppPath($app));
		} else {
			$dirs = $this->getCoreTemplateDirs($theme, OC::$SERVERROOT);
		}
		$locator = new \OC\Template\TemplateFileLocator($dirs);
		$template = $locator->find($name);
		$path = $locator->getPath();
		return [$path, $template];
	}

	/**
	 * Add a custom element to the header
	 * @param string $tag tag name of the element
	 * @param array $attributes array of attributes for the element
	 * @param string $text the text content for the element. If $text is null then the
	 *                     element will be written as empty element. So use "" to get a closing tag.
	 */
	public function addHeader($tag, $attributes, $text = null) {
		$this->headers[] = [
			'tag' => $tag,
			'attributes' => $attributes,
			'text' => $text
		];
	}

	/**
	 * Process the template
	 * @return string
	 *
	 * This function process the template. If $this->renderAs is set, it
	 * will produce a full page.
	 */
	public function fetchPage($additionalParams = null) {
		$data = parent::fetchPage($additionalParams);

		if ($this->renderAs) {
			$page = new TemplateLayout($this->renderAs, $this->app);

			if (is_array($additionalParams)) {
				foreach ($additionalParams as $key => $value) {
					$page->assign($key, $value);
				}
			}

			// Add custom headers
			$headers = '';
			foreach (OC_Util::$headers as $header) {
				$headers .= '<' . \OCP\Util::sanitizeHTML($header['tag']);
				if (strcasecmp($header['tag'], 'script') === 0 && in_array('src', array_map('strtolower', array_keys($header['attributes'])))) {
					$headers .= ' defer';
				}
				foreach ($header['attributes'] as $name => $value) {
					$headers .= ' ' . \OCP\Util::sanitizeHTML($name) . '="' . \OCP\Util::sanitizeHTML($value) . '"';
				}
				if ($header['text'] !== null) {
					$headers .= '>' . \OCP\Util::sanitizeHTML($header['text']) . '</' . \OCP\Util::sanitizeHTML($header['tag']) . '>';
				} else {
					$headers .= '/>';
				}
			}

			$page->assign('headers', $headers);

			$page->assign('content', $data);
			return $page->fetchPage($additionalParams);
		}

		return $data;
	}

	/**
	 * Include template
	 *
	 * @param string $file
	 * @param array|null $additionalParams
	 * @return string returns content of included template
	 *
	 * Includes another template. use <?php echo $this->inc('template'); ?> to
	 * do this.
	 */
	public function inc($file, $additionalParams = null) {
		return $this->load($this->path . $file . '.php', $additionalParams);
	}
}

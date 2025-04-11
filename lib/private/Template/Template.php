<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OC\Template;

use OC\Security\CSP\ContentSecurityPolicyNonceManager;
use OC\TemplateLayout;
use OCP\App\AppPathNotFoundException;
use OCP\App\IAppManager;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\Defaults;
use OCP\Server;
use OCP\Template\ITemplate;
use OCP\Template\TemplateNotFoundException;
use OCP\Util;

class Template extends Base implements ITemplate {
	private string $path;
	private array $headers = [];

	/**
	 * @param string $app app providing the template
	 * @param string $name of the template file (without suffix)
	 * @param TemplateResponse::RENDER_AS_* $renderAs If $renderAs is set, will try to
	 *                                                produce a full page in the according layout.
	 * @throws TemplateNotFoundException
	 */
	public function __construct(
		protected string $app,
		string $name,
		private string $renderAs = TemplateResponse::RENDER_AS_BLANK,
		bool $registerCall = true,
	) {
		$theme = \OC_Util::getTheme();

		$requestToken = ($registerCall ? Util::callRegister() : '');
		$cspNonce = Server::get(ContentSecurityPolicyNonceManager::class)->getNonce();

		// fix translation when app is something like core/lostpassword
		$parts = explode('/', $app);
		$l10n = Util::getL10N($parts[0]);

		[$path, $template] = $this->findTemplate($theme, $app, $name);

		$this->path = $path;

		parent::__construct(
			$template,
			$requestToken,
			$l10n,
			Server::get(Defaults::class),
			$cspNonce,
		);
	}


	/**
	 * find the template with the given name
	 *
	 * Will select the template file for the selected theme.
	 * Checking all the possible locations.
	 *
	 * @param string $name of the template file (without suffix)
	 * @return array{string,string} Directory path and filename
	 * @throws TemplateNotFoundException
	 */
	protected function findTemplate(string $theme, string $app, string $name): array {
		// Check if it is a app template or not.
		if ($app !== '') {
			try {
				$appDir = Server::get(IAppManager::class)->getAppPath($app);
			} catch (AppPathNotFoundException) {
				$appDir = false;
			}
			$dirs = $this->getAppTemplateDirs($theme, $app, \OC::$SERVERROOT, $appDir);
		} else {
			$dirs = $this->getCoreTemplateDirs($theme, \OC::$SERVERROOT);
		}
		$locator = new TemplateFileLocator($dirs);
		return $locator->find($name);
	}

	/**
	 * Add a custom element to the header
	 * @param string $tag tag name of the element
	 * @param array $attributes array of attributes for the element
	 * @param string $text the text content for the element. If $text is null then the
	 *                     element will be written as empty element. So use "" to get a closing tag.
	 */
	public function addHeader(string $tag, array $attributes, ?string $text = null): void {
		$this->headers[] = [
			'tag' => $tag,
			'attributes' => $attributes,
			'text' => $text
		];
	}

	/**
	 * Process the template
	 *
	 * This function process the template. If $this->renderAs is set, it
	 * will produce a full page.
	 */
	public function fetchPage(?array $additionalParams = null): string {
		$data = parent::fetchPage($additionalParams);

		if ($this->renderAs) {
			$page = Server::get(TemplateLayout::class)->getPageTemplate($this->renderAs, $this->app);

			if (is_array($additionalParams)) {
				foreach ($additionalParams as $key => $value) {
					$page->assign($key, $value);
				}
			}

			// Add custom headers
			$headers = '';
			foreach (\OC_Util::$headers as $header) {
				$headers .= '<' . Util::sanitizeHTML($header['tag']);
				if (strcasecmp($header['tag'], 'script') === 0 && in_array('src', array_map('strtolower', array_keys($header['attributes'])))) {
					$headers .= ' defer';
				}
				foreach ($header['attributes'] as $name => $value) {
					$headers .= ' ' . Util::sanitizeHTML($name) . '="' . Util::sanitizeHTML($value) . '"';
				}
				if ($header['text'] !== null) {
					$headers .= '>' . Util::sanitizeHTML($header['text']) . '</' . Util::sanitizeHTML($header['tag']) . '>';
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
	 * @return string returns content of included template
	 *
	 * Includes another template. use <?php echo $this->inc('template'); ?> to
	 * do this.
	 */
	public function inc(string $file, ?array $additionalParams = null): string {
		return $this->load($this->path . $file . '.php', $additionalParams);
	}
}

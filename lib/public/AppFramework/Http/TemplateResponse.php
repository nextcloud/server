<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Bernhard Posselt <dev@bernhard-posselt.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Thomas Tanghus <thomas@tanghus.net>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

/**
 * Public interface of ownCloud for apps to use.
 * AppFramework\HTTP\TemplateResponse class
 */

namespace OCP\AppFramework\Http;


/**
 * Response for a normal template
 * @since 6.0.0
 */
class TemplateResponse extends Response {

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
	 * styles (vendor, app)
	 * @var array
	 */
	protected $styles;

	/**
	 * scripts (vendor, app)
	 * @var array
	 */
	protected $scripts;

	/**
	 * constructor of TemplateResponse
	 * @param string $appName the name of the app to load the template from
	 * @param string $templateName the name of the template
	 * @param array $params an array of parameters which should be passed to the
	 * template
	 * @param string $renderAs how the page should be rendered, defaults to user
	 * @param array $styles an array of styles which sould be added to the template
	 * @param array $scripts an array of scripts which should be added to the template
	 * @since 6.0.0 - parameters $params and $renderAs were added in 7.0.0 - parameters $styles and $scripts were added in 11.0.0
	 */
	public function __construct($appName, $templateName, array $params=array(),
	                            $renderAs='user', array $styles=array(), array $scripts=array()) {
		$this->templateName = $templateName;
		$this->appName = $appName;
		$this->params = $params;
		$this->renderAs = $renderAs;
		$this->styles = $styles;
		$this->scripts = $scripts;
	}


	/**
	 * Sets template parameters
	 * @param array $params an array with key => value structure which sets template
	 *                      variables
	 * @return TemplateResponse Reference to this object
	 * @since 6.0.0 - return value was added in 7.0.0
	 */
	public function setParams(array $params){
		$this->params = $params;

		return $this;
	}


	/**
	 * Used for accessing the set parameters
	 * @return array the params
	 * @since 6.0.0
	 */
	public function getParams(){
		return $this->params;
	}


	/**
	 * Used for accessing the name of the set template
	 * @return string the name of the used template
	 * @since 6.0.0
	 */
	public function getTemplateName(){
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
	public function renderAs($renderAs){
		$this->renderAs = $renderAs;

		return $this;
	}


	/**
	 * Returns the set renderAs
	 * @return string the renderAs value
	 * @since 6.0.0
	 */
	public function getRenderAs(){
		return $this->renderAs;
	}


	/**
	 * Returns the rendered html
	 * @return string the rendered html
	 * @since 6.0.0
	 */
	public function render(){
		// \OCP\Template needs an empty string instead of 'blank' for an unwrapped response
		$renderAs = $this->renderAs === 'blank' ? '' : $this->renderAs;

		$template = new \OCP\Template($this->appName, $this->templateName, $renderAs);

		foreach($this->params as $key => $value){
			$template->assign($key, $value);
		}

		$this->addScriptsAndStyles();

		return $template->fetchPage();
	}
	

	/**
	 * adds the vendor and app scripts and styles to the templateresponse
	 * @since 11.0.0
	 */
	private function addScriptsAndStyles(){
		if (array_key_exists('vendor', $this->scripts)){
			foreach ($this->scripts['vendor'] as $application){
				\OC_Util::addVendorScript($application);
			}
		}

		if (array_key_exists('app', $this->scripts)) {
			foreach ($this->scripts['app'] as $appName => $files) {
				foreach ($files as $file) {
					\OC_Util::addScript($appName, $file);
				}
			}
		}

		if (array_key_exists('vendor', $this->styles)) {
			foreach ($this->styles['vendor'] as $application) {
				\OC_Util::addVendorStyle($application);
			}
		}

		if (array_key_exists('app', $this->styles)) {
			foreach ($this->styles['app'] as $appName => $files) {
				foreach ($files as $file) {
					\OC_Util::addStyle($appName, $file);
				}
			}
		}
	}

}

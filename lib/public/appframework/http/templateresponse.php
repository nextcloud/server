<?php
/**
 * ownCloud - App Framework
 *
 * @author Bernhard Posselt
 * @copyright 2012 Bernhard Posselt <dev@bernhard-posselt.com>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

/**
 * Public interface of ownCloud for apps to use.
 * AppFramework\HTTP\TemplateResponse class
 */

namespace OCP\AppFramework\Http;


/**
 * Response for a normal template
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
	 * constructor of TemplateResponse
	 * @param string $appName the name of the app to load the template from
	 * @param string $templateName the name of the template
	 * @param array $params an array of parameters which should be passed to the
	 * template
	 * @param string $renderAs how the page should be rendered, defaults to user
	 */
	public function __construct($appName, $templateName, array $params=array(),
	                            $renderAs='user') {
		$this->templateName = $templateName;
		$this->appName = $appName;
		$this->params = $params;
		$this->renderAs = $renderAs;
	}


	/**
	 * Sets template parameters
	 * @param array $params an array with key => value structure which sets template
	 *                      variables
	 * @return TemplateResponse Reference to this object
	 */
	public function setParams(array $params){
		$this->params = $params;

		return $this;
	}


	/**
	 * Used for accessing the set parameters
	 * @return array the params
	 */
	public function getParams(){
		return $this->params;
	}


	/**
	 * Used for accessing the name of the set template
	 * @return string the name of the used template
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
	 */
	public function renderAs($renderAs){
		$this->renderAs = $renderAs;

		return $this;
	}


	/**
	 * Returns the set renderAs
	 * @return string the renderAs value
	 */
	public function getRenderAs(){
		return $this->renderAs;
	}


	/**
	 * Returns the rendered html
	 * @return string the rendered html
	 */
	public function render(){
		// \OCP\Template needs an empty string instead of 'blank' for an unwrapped response
		$renderAs = $this->renderAs === 'blank' ? '' : $this->renderAs;

		$template = new \OCP\Template($this->appName, $this->templateName, $renderAs);

		foreach($this->params as $key => $value){
			$template->assign($key, $value);
		}

		return $template->fetchPage();
	}

}

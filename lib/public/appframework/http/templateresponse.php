<?php

/**
 * ownCloud - App Framework
 *
 * @author Bernhard Posselt
 * @copyright 2012 Bernhard Posselt nukeawhale@gmail.com
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


namespace OCP\AppFramework\Http;

use OC\AppFramework\Core\API;


/**
 * Response for a normal template
 */
class TemplateResponse extends Response {

	protected $templateName;
	protected $params;
	protected $api;
	protected $renderAs;
	protected $appName;

	/**
	 * @param API $api an API instance
	 * @param string $templateName the name of the template
	 * @param string $appName optional if you want to include a template from
	 *                        a different app
	 */
	public function __construct(API $api, $templateName, $appName=null) {
		$this->templateName = $templateName;
		$this->appName = $appName;
		$this->api = $api;
		$this->params = array();
		$this->renderAs = 'user';
	}


	/**
	 * Sets template parameters
	 * @param array $params an array with key => value structure which sets template
	 *                      variables
	 */
	public function setParams(array $params){
		$this->params = $params;
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
	 */
	public function renderAs($renderAs){
		$this->renderAs = $renderAs;
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

		if($this->appName !== null){
			$appName = $this->appName;
		} else {
			$appName = $this->api->getAppName();
		}

		$template = $this->api->getTemplate($this->templateName, $this->renderAs, $appName);

		foreach($this->params as $key => $value){
			$template->assign($key, $value);
		}

		return $template->fetchPage();
	}

}

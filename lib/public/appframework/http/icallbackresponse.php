<?php
/**
 * @author Bernhard Posselt
 * @copyright 2015 Bernhard Posselt <dev@bernhard-posselt.com>
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCP\AppFramework\Http;


/**
 * Interface ICallbackResponse
 *
 * @package OCP\AppFramework\Http
 */
interface ICallbackResponse {

	/**
	 * Outputs the content that should be printed
	 *
	 * @param IOutput $output a small wrapper that handles output
	 */
	function callback(IOutput $output);

}

<?php


namespace OCA\Files_Sharing\Controllers;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\OCSResponse;

/**
 * Class ExternalSharesController
 *
 * @package OCA\Files_Sharing\Controllers
 */
class FooController extends Controller {

	/**
	 * @NoCSRFRequired
	 * @return OCSResponse
	 */
	public function bar() {
		return new OCSResponse('json', 100, ['foo']);
	}
}
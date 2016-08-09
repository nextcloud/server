<?php

namespace OC\Core\Controller;

use OC\CapabilitiesManager;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;

class OCSController extends \OCP\AppFramework\OCSController {

	/** @var CapabilitiesManager */
	private $capabilitiesManager;

	/**
	 * OCSController constructor.
	 *
	 * @param string $appName
	 * @param IRequest $request
	 * @param CapabilitiesManager $capabilitiesManager
	 */
	public function __construct($appName,
								IRequest $request,
								CapabilitiesManager $capabilitiesManager) {
		parent::__construct($appName, $request);

		$this->capabilitiesManager = $capabilitiesManager;
	}

	public function getCapabilities() {
		$result = [];
		list($major, $minor, $micro) = \OCP\Util::getVersion();
		$result['version'] = array(
			'major' => $major,
			'minor' => $minor,
			'micro' => $micro,
			'string' => \OC_Util::getVersionString(),
			'edition' => \OC_Util::getEditionString(),
		);

		$result['capabilities'] = $this->capabilitiesManager->getCapabilities();

		return new DataResponse(['data' => $result]);
	}
}
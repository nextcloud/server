<?php

namespace OC\SetupCheck;

use OC\AppFramework\Bootstrap\Coordinator;
use OCP\Server;
use OCP\SetupCheck\ISetupCheck;

class SetupCheckManager {
	private Coordinator $coordinator;

	public function __construct(Coordinator $coordinator) {
		$this->coordinator = $coordinator;
	}

	public function runAll(): array {
		$results = [];
		$setupChecks = $this->coordinator->getRegistrationContext()->getSetupChecks();
		foreach ($setupChecks as $setupCheck) {
			/** @var ISetupCheck $setupCheckObject */
			$setupCheckObject = Server::get($setupCheck->getService());
			$setupResult = $setupCheckObject->run();
			$category = $setupCheckObject->getCategory();
			if (!isset($results[$category])) {
				$results[$category] = [];
			}
			$results[$category][$setupCheckObject->getName()] = $setupResult;
		}
		return $results;
	}
}

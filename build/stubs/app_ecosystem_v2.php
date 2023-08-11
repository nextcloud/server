<?php

namespace OCA\AppEcosystemV2\Service;

use OCP\IRequest;

class AppEcosystemV2Service {
	/**
	 * @param IRequest $request
	 * @param bool $isDav
	 *
	 * @return bool
	 */
	public function validateExAppRequestToNC(IRequest $request, bool $isDav = false): bool {}
}

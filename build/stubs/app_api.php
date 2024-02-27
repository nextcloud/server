<?php

namespace OCA\AppAPI\Service;

use OCP\IRequest;

class AppAPIService {
	/**
	 * @param IRequest $request
	 * @param bool $isDav
	 *
	 * @return bool
	 */
	public function validateExAppRequestToNC(IRequest $request, bool $isDav = false): bool {}
}

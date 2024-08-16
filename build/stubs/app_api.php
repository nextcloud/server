<?php

namespace OCA\AppAPI\Service {
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
}

namespace OCA\AppAPI {

	use OCP\IRequest;
	use OCP\Http\Client\IPromise;
	use OCP\Http\Client\IResponse;

	class PublicFunctions {

		public function __construct(
			private readonly ExAppService  $exAppService,
			private readonly AppAPIService $service,
		) {
		}

		/**
		 * Request to ExApp with AppAPI auth headers
		 */
		public function exAppRequest(
			string $appId,
			string $route,
			?string $userId = null,
			string $method = 'POST',
			array $params = [],
			array $options = [],
			?IRequest $request = null,
		): array|IResponse {
			$exApp = $this->exAppService->getExApp($appId);
			if ($exApp === null) {
				return ['error' => sprintf('ExApp `%s` not found', $appId)];
			}
			return $this->service->requestToExApp($exApp, $route, $userId, $method, $params, $options, $request);
		}

		/**
		 * Async request to ExApp with AppAPI auth headers
		 *
		 * @throws \Exception if ExApp not found
		 */
		public function asyncExAppRequest(
			string $appId,
			string $route,
			?string $userId = null,
			string $method = 'POST',
			array $params = [],
			array $options = [],
			?IRequest $request = null,
		): IPromise {
			$exApp = $this->exAppService->getExApp($appId);
			if ($exApp === null) {
				throw new \Exception(sprintf('ExApp `%s` not found', $appId));
			}
			return $this->service->requestToExAppAsync($exApp, $route, $userId, $method, $params, $options, $request);
		}

		/**
		 * Get basic ExApp info by appid
		 *
		 * @param string $appId
		 *
		 * @return array|null ExApp info (appid, version, name, enabled) or null if no ExApp found
		 */
		public function getExApp(string $appId): ?array {
			$exApp = $this->exAppService->getExApp($appId);
			if ($exApp !== null) {
				$info = $exApp->jsonSerialize();
				return [
					'appid' => $info['appid'],
					'version' => $info['version'],
					'name' => $info['name'],
					'enabled' => $info['enabled'],
				];
			}
			return null;
		}
	}
}

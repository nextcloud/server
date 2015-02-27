<?php
/**
 * @author Georg Ehrke
 * @copyright 2014 Georg Ehrke <georg@ownCloud.com>
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Settings\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\DataDownloadResponse;
use OCP\IL10N;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IRequest;
use OCP\IConfig;

/**
 * Class LogSettingsController
 *
 * @package OC\Settings\Controller
 */
class LogSettingsController extends Controller {
	/**
	 * @var \OCP\IConfig
	 */
	private $config;

	/**
	 * @var \OCP\IL10N
	 */
	private $l10n;

	/**
	 * @var \OCP\ITimeFactory
	 */
	private $timefactory;

	/**
	 * @param string $appName
	 * @param IRequest $request
	 * @param IConfig $config
	 */
	public function __construct($appName,
								IRequest $request,
								IConfig $config,
								IL10N $l10n,
								ITimeFactory $timeFactory) {

		parent::__construct($appName, $request);
		$this->config = $config;
		$this->l10n = $l10n;
		$this->timefactory = $timeFactory;
	}

	/**
	 * set log level for logger
	 *
	 * @param int $level
	 * @return JSONResponse
	 */
	public function setLogLevel($level) {
		if ($level < 0 || $level > 4) {
			return new JSONResponse([
				'message' => (string) $this->l10n->t('log-level out of allowed range'),
			], Http::STATUS_BAD_REQUEST);
		}

		$this->config->setSystemValue('loglevel', $level);
		return new JSONResponse([
			'level' => $level,
		]);
	}

	/**
	 * get log entries from logfile
	 *
	 * @param int $count
	 * @param int $offset
	 * @return JSONResponse
	 */
	public function getEntries($count=50, $offset=0) {
		return new JSONResponse([
			'data' => \OC_Log_Owncloud::getEntries($count, $offset),
			'remain' => count(\OC_Log_Owncloud::getEntries(1, $offset + $count)) !== 0,
		]);
	}

	/**
	 * download logfile
	 *
	 * @NoCSRFRequired
	 *
	 * @return DataDownloadResponse
	 */
	public function download() {
		return new DataDownloadResponse(
			json_encode(\OC_Log_Owncloud::getEntries(null, null)),
			$this->getFilenameForDownload(),
			'application/json'
		);
	}

	/**
	 * get filename for the logfile that's being downloaded
	 *
	 * @param int $timestamp (defaults to time())
	 * @return string
	 */
	private function getFilenameForDownload($timestamp=null) {
		$instanceId = $this->config->getSystemValue('instanceid');

		$filename = implode([
			'ownCloud',
			$instanceId,
			(!is_null($timestamp)) ? $timestamp : $this->timefactory->getTime()
		], '-');
		$filename .= '.log';

		return $filename;
	}
}

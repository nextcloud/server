<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Georg Ehrke <oc.list@georgehrke.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Kate Döen <kate.doeen@nextcloud.com>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\Settings\Controller;

use OC\Log;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\StreamResponse;
use OCP\IRequest;

class LogSettingsController extends Controller {

	/** @var Log */
	private $log;

	public function __construct(string $appName, IRequest $request, Log $logger) {
		parent::__construct($appName, $request);
		$this->log = $logger;
	}

	/**
	 * download logfile
	 *
	 * @NoCSRFRequired
	 *
	 * @psalm-suppress MoreSpecificReturnType The value of Content-Disposition is not relevant
	 * @psalm-suppress LessSpecificReturnStatement The value of Content-Disposition is not relevant
	 * @return StreamResponse<Http::STATUS_OK, array{Content-Type: 'application/octet-stream', 'Content-Disposition': string}>
	 *
	 * 200: Logfile returned
	 */
	public function download() {
		if (!$this->log instanceof Log) {
			throw new \UnexpectedValueException('Log file not available');
		}
		$resp = new StreamResponse($this->log->getLogPath());
		$resp->setHeaders([
			'Content-Type' => 'application/octet-stream',
			'Content-Disposition' => 'attachment; filename="nextcloud.log"',
		]);
		return $resp;
	}
}

<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <vincent@nextcloud.com>
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
namespace OCA\DAV\Connector\Sabre;

use OCA\DAV\Connector\Sabre\Exception\FileLocked;
use OCA\DAV\Connector\Sabre\Exception\PasswordLoginForbidden;
use OCA\DAV\Exception\ServerMaintenanceMode;
use OCP\Files\StorageNotAvailableException;
use Psr\Log\LoggerInterface;
use Sabre\DAV\Exception\BadRequest;
use Sabre\DAV\Exception\Conflict;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\InvalidSyncToken;
use Sabre\DAV\Exception\MethodNotAllowed;
use Sabre\DAV\Exception\NotAuthenticated;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\Exception\NotImplemented;
use Sabre\DAV\Exception\PreconditionFailed;
use Sabre\DAV\Exception\RequestedRangeNotSatisfiable;
use Sabre\DAV\Exception\ServiceUnavailable;

class ExceptionLoggerPlugin extends \Sabre\DAV\ServerPlugin {
	protected $nonFatalExceptions = [
		NotAuthenticated::class => true,
		// If tokenauth can throw this exception (which is basically as
		// NotAuthenticated. So not fatal.
		PasswordLoginForbidden::class => true,
		// basically a NotAuthenticated
		InvalidSyncToken::class => true,
		// the sync client uses this to find out whether files exist,
		// so it is not always an error, log it as debug
		NotFound::class => true,
		// the sync client messed up their request
		// (e.g. propfind for tags with string instead of int)
		// so it is not always an error, log it as debug
		BadRequest::class => true,
		// this one mostly happens when the same file is uploaded at
		// exactly the same time from two clients, only one client
		// wins, the second one gets "Precondition failed"
		PreconditionFailed::class => true,
		// forbidden can be expected when trying to upload to
		// read-only folders for example
		Forbidden::class => true,
		// Happens when an external storage or federated share is temporarily
		// not available
		StorageNotAvailableException::class => true,
		// happens if some a client uses the wrong method for a given URL
		// the error message itself is visible on the client side anyways
		NotImplemented::class => true,
		// happens when the parent directory is not present (for example when a
		// move is done to a non-existent directory)
		Conflict::class => true,
		// happens when a certain method is not allowed to be called
		// for example creating a folder that already exists
		MethodNotAllowed::class => true,
		// A locked file is perfectly valid and can happen in various cases
		FileLocked::class => true,
		// An invalid range is requested
		RequestedRangeNotSatisfiable::class => true,
		ServerMaintenanceMode::class => true,
	];

	private string $appName;
	private LoggerInterface $logger;

	/**
	 * @param string $loggerAppName app name to use when logging
	 */
	public function __construct(string $loggerAppName, LoggerInterface $logger) {
		$this->appName = $loggerAppName;
		$this->logger = $logger;
	}

	/**
	 * This initializes the plugin.
	 *
	 * This function is called by \Sabre\DAV\Server, after
	 * addPlugin is called.
	 *
	 * This method should set up the required event subscriptions.
	 *
	 * @param \Sabre\DAV\Server $server
	 * @return void
	 */
	public function initialize(\Sabre\DAV\Server $server) {
		$server->on('exception', [$this, 'logException'], 10);
	}

	/**
	 * Log exception
	 */
	public function logException(\Throwable $ex) {
		$exceptionClass = get_class($ex);
		if (isset($this->nonFatalExceptions[$exceptionClass])) {
			$this->logger->debug($ex->getMessage(), [
				'app' => $this->appName,
				'exception' => $ex,
			]);
			return;
		}

		$this->logger->critical($ex->getMessage(), [
			'app' => $this->appName,
			'exception' => $ex,
		]);
	}
}

<?php
/**
 * @author Victor Dubiniuk <dubiniuk@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OC\Core\Controller;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OC\Console\Application;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;

class OccController extends Controller {
	private $allowedCommands = ['status', 'config:list'];

	public function __construct($appName, IRequest $request) {
		parent::__construct($appName, $request);
	}

	/**
	 * @NoCSRFRequired
	 *
	 * Execute occ command
	 *
	 * @param string $command
	 * @param $token
	 *
	 * @return JSONResponse
	 * @throws \Exception
	 */
	public function execute($command, $token) {
		try {
			$this->validateRequest($command, $token);

			$input = new StringInput(
				sprintf("%s --output=json --no-warnings", $command)
			);
			$output = new BufferedOutput();
			$formatter = $output->getFormatter();
			$formatter->setDecorated(false);
			$application = new Application(\OC::$server->getConfig(), \OC::$server->getEventDispatcher(), $this->request);
			$application->setAutoExit(false);
			$application->loadCommands($input, $output);

			$exitCode = $application->run($input, $output);
			$response = $output->fetch();

			$json = [
				'exitCode' => $exitCode,
				'response' => $response
			];

		} catch (\UnexpectedValueException $e){
			$json = [
				'exitCode' => 126,
				'response' => 'Not allowed',
				'details' => $e->getMessage()
			];
		}
		return new JSONResponse($json);
	}

	protected function validateRequest($command, $token){
		if (!in_array($command, $this->allowedCommands)) {
			throw new \UnexpectedValueException(sprintf('Command "%s" is not allowed to run via web request', $command));
		}

		$coreToken = \OC::$server->getConfig()->getSystemValue('updater.secret', '');
		if ($coreToken === '') {
			throw new \UnexpectedValueException(
				'updater.secret is undefined in config/config.php. Either browse the admin settings in your ownCloud and click "Open updater" or define a strong secret using <pre>php -r \'echo password_hash("MyStrongSecretDoUseYourOwn!", PASSWORD_DEFAULT)."\n";\'</pre> and set this in the config.php.'
			);
		}

		if (!password_verify($token, $coreToken)) {
			throw new \UnexpectedValueException(
				'updater.secret does not match the provided token'
			);
		}
	}
}

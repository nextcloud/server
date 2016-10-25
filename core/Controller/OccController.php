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
use OC\Console\Application;
use OCP\IConfig;
use OCP\IRequest;
use OCP\ILogger;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class OccController extends Controller {
	
	/** @var array  */
	private $allowedCommands = [
		'app:disable',
		'app:enable',
		'app:getpath',
		'app:list',
		'check',
		'config:list',
		'maintenance:mode',
		'integrity:check-core',
		'status',
		'upgrade'
	];

	/** @var IConfig */
	private $config;
	/** @var Application */
	private $console;
	/** @var ILogger */
	private $logger;

	/**
	 * OccController constructor.
	 *
	 * @param string $appName
	 * @param IRequest $request
	 * @param IConfig $config
	 * @param Application $console
	 * @param ILogger $logger
	 */
	public function __construct($appName, IRequest $request,
								IConfig $config, Application $console, ILogger $logger) {
		parent::__construct($appName, $request);
		$this->config = $config;
		$this->console = $console;
		$this->logger = $logger;
	}

	/**
	 * @PublicPage
	 * @NoCSRFRequired
	 *
	 * Execute occ command
	 * Sample request
	 *	POST http://domain.tld/index.php/occ/status',
	 * 		{
	 *			'params': {
	 * 					'--no-warnings':'1',
	 *		 			'--output':'json'
	 * 			},
	 * 			'token': 'someToken'
	 * 		}
	 *
	 * @param string $command
	 * @param string $token
	 * @param array $params
	 *
	 * @return JSONResponse
	 * @throws \Exception
	 */
	public function execute($command, $token, $params = []) {
		try {
			$this->validateRequest($command, $token);

			$output = new BufferedOutput();
			$formatter = $output->getFormatter();
			$formatter->setDecorated(false);
			$this->console->setAutoExit(false);
			$this->console->loadCommands(new ArrayInput([]), $output);

			$inputArray = array_merge(['command' => $command], $params);
			$input = new ArrayInput($inputArray);

			$exitCode = $this->console->run($input, $output);
			$response = $output->fetch();

			$json = [
				'exitCode' => $exitCode,
				'response' => $response
			];

		} catch (\UnexpectedValueException $e){
			$this->logger->warning(
				'Invalid request to occ controller. Details: "{details}"',
				[
					'app' => 'core',
					'details' => $e->getMessage()
				]
			);
			$json = [
				'exitCode' => 126,
				'response' => 'Not allowed',
				'details' => $e->getMessage()
			];
		}
		return new JSONResponse($json);
	}

	/**
	 * Check if command is allowed and has a valid security token
	 * @param $command
	 * @param $token
	 */
	protected function validateRequest($command, $token){
		$allowedHosts = ['::1', '127.0.0.1', 'localhost'];
		if (isset($this->request->server['SERVER_ADDR'])){
			array_push($allowedHosts, $this->request->server['SERVER_ADDR']);
		}

		if (!in_array($this->request->getRemoteAddress(), $allowedHosts)) {
			throw new \UnexpectedValueException('Web executor is not allowed to run from a host ' . $this->request->getRemoteAddress());
		}

		if (!in_array($command, $this->allowedCommands)) {
			throw new \UnexpectedValueException(sprintf('Command "%s" is not allowed to run via web request', $command));
		}

		$coreToken = $this->config->getSystemValue('updater.secret', '');
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

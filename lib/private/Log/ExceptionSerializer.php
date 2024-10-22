<?php
/**
 * @copyright Copyright (c) 2018 Robin Appelman <robin@icewind.nl>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Vincent Petry <vincent@nextcloud.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OC\Log;

use OC\Core\Controller\SetupController;
use OC\Http\Client\Client;
use OC\Security\IdentityProof\Key;
use OC\Setup;
use OC\SystemConfig;
use OCA\Encryption\Controller\RecoveryController;
use OCA\Encryption\Controller\SettingsController;
use OCA\Encryption\Crypto\Crypt;
use OCA\Encryption\Crypto\Encryption;
use OCA\Encryption\Hooks\UserHooks;
use OCA\Encryption\KeyManager;
use OCA\Encryption\Session;
use OCP\HintException;

class ExceptionSerializer {
	public const SENSITIVE_VALUE_PLACEHOLDER = '*** sensitive parameters replaced ***';

	public const methodsWithSensitiveParameters = [
		// Session/User
		'completeLogin',
		'login',
		'checkPassword',
		'checkPasswordNoLogging',
		'loginWithPassword',
		'updatePrivateKeyPassword',
		'validateUserPass',
		'loginWithToken',
		'{closure}',
		'createSessionToken',

		// Provisioning
		'addUser',

		// TokenProvider
		'getToken',
		'isTokenPassword',
		'getPassword',
		'decryptPassword',
		'logClientIn',
		'generateToken',
		'validateToken',

		// TwoFactorAuth
		'solveChallenge',
		'verifyChallenge',

		// ICrypto
		'calculateHMAC',
		'encrypt',
		'decrypt',

		// LoginController
		'tryLogin',
		'confirmPassword',

		// LDAP
		'bind',
		'areCredentialsValid',
		'invokeLDAPMethod',

		// Encryption
		'storeKeyPair',
		'setupUser',
		'checkSignature',

		// files_external: OCA\Files_External\MountConfig
		'getBackendStatus',

		// files_external: UserStoragesController
		'update',

		// Preview providers, don't log big data strings
		'imagecreatefromstring',

		// text: PublicSessionController, SessionController and ApiService
		'create',
		'close',
		'push',
		'sync',
		'updateSession',
		'mention',
		'loginSessionUser',

	];

	public function __construct(
		private SystemConfig $systemConfig,
	) {
	}

	protected array $methodsWithSensitiveParametersByClass = [
		SetupController::class => [
			'run',
			'display',
			'loadAutoConfig',
		],
		Setup::class => [
			'install'
		],
		Key::class => [
			'__construct'
		],
		Client::class => [
			'request',
			'delete',
			'deleteAsync',
			'get',
			'getAsync',
			'head',
			'headAsync',
			'options',
			'optionsAsync',
			'patch',
			'post',
			'postAsync',
			'put',
			'putAsync',
		],
		\Redis::class => [
			'auth'
		],
		\RedisCluster::class => [
			'__construct'
		],
		Crypt::class => [
			'symmetricEncryptFileContent',
			'encrypt',
			'generatePasswordHash',
			'encryptPrivateKey',
			'decryptPrivateKey',
			'isValidPrivateKey',
			'symmetricDecryptFileContent',
			'checkSignature',
			'createSignature',
			'decrypt',
			'multiKeyDecrypt',
			'multiKeyEncrypt',
		],
		RecoveryController::class => [
			'adminRecovery',
			'changeRecoveryPassword'
		],
		SettingsController::class => [
			'updatePrivateKeyPassword',
		],
		Encryption::class => [
			'encrypt',
			'decrypt',
		],
		KeyManager::class => [
			'checkRecoveryPassword',
			'storeKeyPair',
			'setRecoveryKey',
			'setPrivateKey',
			'setFileKey',
			'setAllFileKeys',
		],
		Session::class => [
			'setPrivateKey',
			'prepareDecryptAll',
		],
		\OCA\Encryption\Users\Setup::class => [
			'setupUser',
		],
		UserHooks::class => [
			'login',
			'postCreateUser',
			'postDeleteUser',
			'prePasswordReset',
			'postPasswordReset',
			'preSetPassphrase',
			'setPassphrase',
		],
	];

	private function editTrace(array &$sensitiveValues, array $traceLine): array {
		if (isset($traceLine['args'])) {
			$sensitiveValues = array_merge($sensitiveValues, $traceLine['args']);
		}
		$traceLine['args'] = [self::SENSITIVE_VALUE_PLACEHOLDER];
		return $traceLine;
	}

	private function filterTrace(array $trace) {
		$sensitiveValues = [];
		$trace = array_map(function (array $traceLine) use (&$sensitiveValues) {
			$className = $traceLine['class'] ?? '';
			if ($className && isset($this->methodsWithSensitiveParametersByClass[$className])
				&& in_array($traceLine['function'], $this->methodsWithSensitiveParametersByClass[$className], true)) {
				return $this->editTrace($sensitiveValues, $traceLine);
			}
			foreach (self::methodsWithSensitiveParameters as $sensitiveMethod) {
				if (str_contains($traceLine['function'], $sensitiveMethod)) {
					return $this->editTrace($sensitiveValues, $traceLine);
				}
			}
			return $traceLine;
		}, $trace);
		return array_map(function (array $traceLine) use ($sensitiveValues) {
			if (isset($traceLine['args'])) {
				$traceLine['args'] = $this->removeValuesFromArgs($traceLine['args'], $sensitiveValues);
			}
			return $traceLine;
		}, $trace);
	}

	private function removeValuesFromArgs($args, $values): array {
		$workArgs = [];
		foreach ($args as $key => $arg) {
			if (in_array($arg, $values, true)) {
				$arg = self::SENSITIVE_VALUE_PLACEHOLDER;
			} elseif (is_array($arg)) {
				$arg = $this->removeValuesFromArgs($arg, $values);
			}
			$workArgs[$key] = $arg;
		}
		return $workArgs;
	}

	private function encodeTrace($trace) {
		$trace = array_map(function (array $line) {
			if (isset($line['args'])) {
				$line['args'] = array_map([$this, 'encodeArg'], $line['args']);
			}
			return $line;
		}, $trace);
		return $this->filterTrace($trace);
	}

	private function encodeArg($arg, $nestingLevel = 5) {
		if (is_object($arg)) {
			if ($nestingLevel === 0) {
				return [
					'__class__' => get_class($arg),
					'__properties__' => 'Encoding skipped as the maximum nesting level was reached',
				];
			}

			$objectInfo = [ '__class__' => get_class($arg) ];
			$objectVars = get_object_vars($arg);
			return array_map(function ($arg) use ($nestingLevel) {
				return $this->encodeArg($arg, $nestingLevel - 1);
			}, array_merge($objectInfo, $objectVars));
		}

		if (is_array($arg)) {
			if ($nestingLevel === 0) {
				return ['Encoding skipped as the maximum nesting level was reached'];
			}

			// Only log the first 5 elements of an array unless we are on debug
			if ((int)$this->systemConfig->getValue('loglevel', 2) !== 0) {
				$elemCount = count($arg);
				if ($elemCount > 5) {
					$arg = array_slice($arg, 0, 5);
					$arg[] = 'And ' . ($elemCount - 5) . ' more entries, set log level to debug to see all entries';
				}
			}
			return array_map(function ($e) use ($nestingLevel) {
				return $this->encodeArg($e, $nestingLevel - 1);
			}, $arg);
		}

		return $arg;
	}

	public function serializeException(\Throwable $exception): array {
		$data = [
			'Exception' => get_class($exception),
			'Message' => $exception->getMessage(),
			'Code' => $exception->getCode(),
			'Trace' => $this->encodeTrace($exception->getTrace()),
			'File' => $exception->getFile(),
			'Line' => $exception->getLine(),
		];

		if ($exception instanceof HintException) {
			$data['Hint'] = $exception->getHint();
		}

		if ($exception->getPrevious()) {
			$data['Previous'] = $this->serializeException($exception->getPrevious());
		}

		return $data;
	}

	public function enlistSensitiveMethods(string $class, array $methods): void {
		if (!isset($this->methodsWithSensitiveParametersByClass[$class])) {
			$this->methodsWithSensitiveParametersByClass[$class] = [];
		}
		$this->methodsWithSensitiveParametersByClass[$class] = array_merge($this->methodsWithSensitiveParametersByClass[$class], $methods);
	}
}

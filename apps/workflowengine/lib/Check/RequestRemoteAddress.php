<?php
/**
 * @copyright Copyright (c) 2016 Joas Schilling <coding@schilljs.com>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\WorkflowEngine\Check;


use OCP\Files\Storage\IStorage;
use OCP\IL10N;
use OCP\IRequest;
use OCP\WorkflowEngine\ICheck;

class RequestRemoteAddress implements ICheck {

	/** @var IL10N */
	protected $l;

	/** @var IRequest */
	protected $request;

	/**
	 * @param IL10N $l
	 * @param IRequest $request
	 */
	public function __construct(IL10N $l, IRequest $request) {
		$this->l = $l;
		$this->request = $request;
	}

	/**
	 * @param IStorage $storage
	 * @param string $path
	 */
	public function setFileInfo(IStorage $storage, $path) {
		// A different path doesn't change time, so nothing to do here.
	}

	/**
	 * @param string $operator
	 * @param string $value
	 * @return bool
	 */
	public function executeCheck($operator, $value) {
		$actualValue = $this->request->getRemoteAddress();
		$decodedValue = explode('/', $value);

		if ($operator === 'matchesIPv4') {
			return $this->matchIPv4($actualValue, $decodedValue[0], $decodedValue[1]);
		} else if ($operator === '!matchesIPv4') {
			return !$this->matchIPv4($actualValue, $decodedValue[0], $decodedValue[1]);
		} else if ($operator === 'matchesIPv6') {
			return $this->matchIPv6($actualValue, $decodedValue[0], $decodedValue[1]);
		} else {
			return !$this->matchIPv6($actualValue, $decodedValue[0], $decodedValue[1]);
		}
	}

	/**
	 * @param string $operator
	 * @param string $value
	 * @throws \UnexpectedValueException
	 */
	public function validateCheck($operator, $value) {
		if (!in_array($operator, ['matchesIPv4', '!matchesIPv4', 'matchesIPv6', '!matchesIPv6'])) {
			throw new \UnexpectedValueException($this->l->t('The given operator is invalid'), 1);
		}

		$decodedValue = explode('/', $value);
		if (sizeof($decodedValue) !== 2) {
			throw new \UnexpectedValueException($this->l->t('The given IP range is invalid'), 2);
		}

		if (in_array($operator, ['matchesIPv4', '!matchesIPv4'])) {
			if (!filter_var($decodedValue[0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
				throw new \UnexpectedValueException($this->l->t('The given IP range is not valid for IPv4'), 3);
			}
			if ($decodedValue[1] > 32 || $decodedValue[1] <= 0) {
				throw new \UnexpectedValueException($this->l->t('The given IP range is not valid for IPv4'), 4);
			}
		} else {
			if (!filter_var($decodedValue[0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
				throw new \UnexpectedValueException($this->l->t('The given IP range is not valid for IPv6'), 3);
			}
			if ($decodedValue[1] > 128 || $decodedValue[1] <= 0) {
				throw new \UnexpectedValueException($this->l->t('The given IP range is not valid for IPv6'), 4);
			}
		}
	}

	/**
	 * Based on http://stackoverflow.com/a/594134
	 * @param string $ip
	 * @param string $rangeIp
	 * @param int $bits
	 * @return bool
	 */
	protected function matchIPv4($ip, $rangeIp, $bits) {
		$rangeDecimal = ip2long($rangeIp);
		$ipDecimal = ip2long($ip);
		$mask = -1 << (32 - $bits);
		return ($ipDecimal & $mask) === ($rangeDecimal & $mask);
	}

	/**
	 * Based on http://stackoverflow.com/a/7951507
	 * @param string $ip
	 * @param string $rangeIp
	 * @param int $bits
	 * @return bool
	 */
	protected function matchIPv6($ip, $rangeIp, $bits) {
		$ipNet = inet_pton($ip);
		$binaryIp = $this->ipv6ToBits($ipNet);
		$ipNetBits = substr($binaryIp, 0, $bits);

		$rangeNet = inet_pton($rangeIp);
		$binaryRange = $this->ipv6ToBits($rangeNet);
		$rangeNetBits = substr($binaryRange, 0, $bits);

		return $ipNetBits === $rangeNetBits;
	}

	/**
	 * Based on http://stackoverflow.com/a/7951507
	 * @param string $packedIp
	 * @return string
	 */
	protected function ipv6ToBits($packedIp) {
		$unpackedIp = unpack('A16', $packedIp);
		$unpackedIp = str_split($unpackedIp[1]);
		$binaryIp = '';
		foreach ($unpackedIp as $char) {
			$binaryIp .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
		}
		return str_pad($binaryIp, 128, '0', STR_PAD_RIGHT);
	}
}

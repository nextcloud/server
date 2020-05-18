<?php

declare(strict_types=1);

/**
 * @copyright 2018 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OC\Authentication\TwoFactorAuth;

use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IUser;

class MandatoryTwoFactor {

	/** @var IConfig */
	private $config;

	/** @var IGroupManager */
	private $groupManager;

	public function __construct(IConfig $config, IGroupManager $groupManager) {
		$this->config = $config;
		$this->groupManager = $groupManager;
	}

	/**
	 * Get the state of enforced two-factor auth
	 */
	public function getState(): EnforcementState {
		return new EnforcementState(
			$this->config->getSystemValue('twofactor_enforced', 'false') === 'true',
			$this->config->getSystemValue('twofactor_enforced_groups', []),
			$this->config->getSystemValue('twofactor_enforced_excluded_groups', []),
			$this->config->getSystemValue('twofactor_enforced_networks', []),
			$this->config->getSystemValue('twofactor_enforced_excluded_networks', [])
		);
	}

	/**
	 * Set the state of enforced two-factor auth
	 */
	public function setState(EnforcementState $state) {
		$this->config->setSystemValue('twofactor_enforced', $state->isEnforced() ? 'true' : 'false');
		$this->config->setSystemValue('twofactor_enforced_groups', $state->getEnforcedGroups());
		$this->config->setSystemValue('twofactor_enforced_excluded_groups', $state->getExcludedGroups());
		$this->config->setSystemValue('twofactor_enforced_networks', $state->getEnforcedNetworks());
		$this->config->setSystemValue('twofactor_enforced_excluded_networks', $state->getExcludedNetworks());
	}

	/**
	 * Checks if an IPv4 or IPv6 address is contained in the list of given IPs or subnets.
	 *
	 * @param string       $requestIp IP to check
	 * @param string|array $ips       List of IPs or subnets (can be a string if only a single one)
	 *
	 * @return bool Whether the IP is valid
	 *
	 * @copyright Copyright (c) 2004-2016 Fabien Potencier
	 * @license MIT
	 */
	public static function checkIp($requestIp, $ips)
	{
		if (!is_array($ips)) {
			$ips = array($ips);
		}

		$method = substr_count($requestIp, ':') > 1 ? 'checkIp6' : 'checkIp4';

		foreach ($ips as $ip) {
			if (self::$method($requestIp, $ip)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Compares two IPv4 addresses.
	 * In case a subnet is given, it checks if it contains the request IP.
	 *
	 * @param string $requestIp IPv4 address to check
	 * @param string $ip        IPv4 address or subnet in CIDR notation
	 *
	 * @return bool Whether the request IP matches the IP, or whether the request IP is within the CIDR subnet
	 *
	 * @copyright Copyright (c) 2004-2016 Fabien Potencier
	 * @license MIT
	 */
	public static function checkIp4($requestIp, $ip)
	{
		if (false !== strpos($ip, '/')) {
			list($address, $netmask) = explode('/', $ip, 2);

			if ($netmask === '0') {
				// Ensure IP is valid - using ip2long below implicitly validates, but we need to do it manually here
				return filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
			}

			if ($netmask < 0 || $netmask > 32) {
				return false;
			}
		} else {
			$address = $ip;
			$netmask = 32;
		}

		return 0 === substr_compare(sprintf('%032b', ip2long($requestIp)), sprintf('%032b', ip2long($address)), 0, (int)$netmask);
	}

	/**
	 * Compares two IPv6 addresses.
	 * In case a subnet is given, it checks if it contains the request IP.
	 *
	 * @author David Soria Parra <dsp at php dot net>
	 *
	 * @see https://github.com/dsp/v6tools
	 *
	 * @param string $requestIp IPv6 address to check
	 * @param string $ip        IPv6 address or subnet in CIDR notation
	 *
	 * @return bool Whether the IP is valid
	 *
	 * @throws \RuntimeException When IPV6 support is not enabled
	 *
	 * @copyright Copyright (c) 2004-2016 Fabien Potencier
	 * @license MIT
	 */
	public static function checkIp6($requestIp, $ip)
	{
		if (!((extension_loaded('sockets') && defined('AF_INET6')) || @inet_pton('::1'))) {
			throw new \RuntimeException('Unable to check Ipv6. Check that PHP was not compiled with option "disable-ipv6".');
		}

		if (false !== strpos($ip, '/')) {
			list($address, $netmask) = explode('/', $ip, 2);

			if ($netmask < 1 || $netmask > 128) {
				return false;
			}
		} else {
			$address = $ip;
			$netmask = 128;
		}

		$bytesAddr = unpack('n*', @inet_pton($address));
		$bytesTest = unpack('n*', @inet_pton($requestIp));

		if (!$bytesAddr || !$bytesTest) {
			return false;
		}

		for ($i = 1, $ceil = ceil($netmask / 16); $i <= $ceil; ++$i) {
			$left = $netmask - 16 * ($i - 1);
			$left = ($left <= 16) ? $left : 16;
			$mask = ~(0xffff >> $left) & 0xffff;
			if (($bytesAddr[$i] & $mask) != ($bytesTest[$i] & $mask)) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Check if two-factor auth is enforced for a specific network
	 *
	 * The admin(s) can enforce two-factor auth system-wide, for certain networks only
	 * and also have the option to exclude networks. This method will check a specfific
	 * network.
	 *
	 * @param String $ip
	 *
	 * @return bool
	 */
	public function isEnforcedForNetwork(String $ip): bool {
		$state = $this->getState();
		if (!$state->isEnforced()) {
			return false;
		}

		/*
		 * If there is a list of enforced networks, we only enforce 2FA for clients in these networks.
		 * For all the other networks it is not enforced (overruling the excluded groups list).
		 */
		if (!empty($state->getEnforcedNetworks())) {
			foreach ($state->getEnforcedNetworks() as $network) {
				if (self::checkIp($ip, $network)) {
					return true;
				}
			}
			// Client not in one of these networks -> no 2FA enforced
			return false;
		}

		/**
		 * If the client ip is in the range of excluded networks, 2FA won't be enforced.
		 */
		foreach ($state->getExcludedNetworks() as $network) {
			if (self::checkIp($ip, $network)) {
				return false;
			}
		}

		/**
		 * No enforced network configured and client ip not in range aif excluded networks,
		 * so 2FA is enforced.
		 */
		return true;
	}

	/**
	 * Check if two-factor auth is enforced for a specific user
	 *
	 * The admin(s) can enforce two-factor auth system-wide, for certain groups only
	 * and also have the option to exclude users of certain groups. This method will
	 * check their membership of those groups.
	 *
	 * @param IUser $user
	 *
	 * @return bool
	 */
	public function isEnforcedFor(IUser $user): bool {
		$state = $this->getState();
		if (!$state->isEnforced()) {
			return false;
		}
		$uid = $user->getUID();

		/*
		 * If there is a list of enforced groups, we only enforce 2FA for members of those groups.
		 * For all the other users it is not enforced (overruling the excluded groups list).
		 */
		if (!empty($state->getEnforcedGroups())) {
			foreach ($state->getEnforcedGroups() as $group) {
				if ($this->groupManager->isInGroup($uid, $group)) {
					return true;
				}
			}
			// Not a member of any of these groups -> no 2FA enforced
			return false;
		}

		/**
		 * If the user is member of an excluded group, 2FA won't be enforced.
		 */
		foreach ($state->getExcludedGroups() as $group) {
			if ($this->groupManager->isInGroup($uid, $group)) {
				return false;
			}
		}

		/**
		 * No enforced groups configured and user not member of an excluded groups,
		 * so 2FA is enforced.
		 */
		return true;
	}


}

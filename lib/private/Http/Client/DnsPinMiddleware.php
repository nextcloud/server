<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2021, Lukas Reschke <lukas@statuscode.ch>
 *
 * @author Lukas Reschke <lukas@statuscode.ch>
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
namespace OC\Http\Client;

use Psr\Http\Message\RequestInterface;

class DnsPinMiddleware {
	/** @var NegativeDnsCache */
	private $negativeDnsCache;
	/** @var LocalAddressChecker */
	private $localAddressChecker;

	public function __construct(
		NegativeDnsCache $negativeDnsCache,
		LocalAddressChecker $localAddressChecker
	) {
		$this->negativeDnsCache = $negativeDnsCache;
		$this->localAddressChecker = $localAddressChecker;
	}

	/**
	 * Fetch soa record for a target
	 *
	 * @param string $target
	 * @return array|null
	 */
	private function soaRecord(string $target): ?array {
		$labels = explode('.', $target);

		$top = count($labels) >= 2 ? array_pop($labels) : '';
		$second = array_pop($labels);

		$hostname = $second . '.' . $top;
		$responses = dns_get_record($hostname, DNS_SOA);

		if ($responses === false || count($responses) === 0) {
			return null;
		}

		return reset($responses);
	}

	private function dnsResolve(string $target, int $recursionCount) : array {
		if ($recursionCount >= 10) {
			return [];
		}

		$recursionCount++;
		$targetIps = [];

		$soaDnsEntry = $this->soaRecord($target);
		$dnsNegativeTtl = $soaDnsEntry['minimum-ttl'] ?? null;

		$dnsTypes = [DNS_A, DNS_AAAA, DNS_CNAME];
		foreach ($dnsTypes as $dnsType) {
			if ($this->negativeDnsCache->isNegativeCached($target, $dnsType)) {
				continue;
			}

			// Don't throw an error if dns_get_report does not work and continue
			try {
				$dnsResponses = dns_get_record($target, $dnsType);
			} catch (\Exception $e) {
				$dnsResponses = false;
			}

			$canHaveCnameRecord = true;
			if ($dnsResponses !== false && count($dnsResponses) > 0) {
				foreach ($dnsResponses as $dnsResponse) {
					if (isset($dnsResponse['ip'])) {
						$targetIps[] = $dnsResponse['ip'];
						$canHaveCnameRecord = false;
					} elseif (isset($dnsResponse['ipv6'])) {
						$targetIps[] = $dnsResponse['ipv6'];
						$canHaveCnameRecord = false;
					} elseif (isset($dnsResponse['target']) && $canHaveCnameRecord) {
						$targetIps = array_merge($targetIps, $this->dnsResolve($dnsResponse['target'], $recursionCount));
						$canHaveCnameRecord = true;
					}
				}
			} elseif ($dnsNegativeTtl !== null) {
				$this->negativeDnsCache->setNegativeCacheForDnsType($target, $dnsType, $dnsNegativeTtl);
			}
		}

		return $targetIps;
	}

	public function addDnsPinning() {
		return function (callable $handler) {
			return function (
				RequestInterface $request,
				array $options
			) use ($handler) {
				if ($options['nextcloud']['allow_local_address'] === true) {
					return $handler($request, $options);
				}

				$hostName = (string)$request->getUri()->getHost();
				$port = $request->getUri()->getPort();

				$ports = [
					'80',
					'443',
				];

				if ($port !== null) {
					$ports[] = (string)$port;
				}

				$targetIps = $this->dnsResolve($hostName, 0);

				$curlResolves = [];

				foreach ($ports as $port) {
					$curlResolves["$hostName:$port"] = [];

					foreach ($targetIps as $ip) {
						$this->localAddressChecker->ThrowIfLocalIp($ip);
						$curlResolves["$hostName:$port"][] = $ip;
					}
				}

				// Coalesce the per-host:port ips back into a comma separated list
				foreach ($curlResolves as $hostport => $ips) {
					$options['curl'][CURLOPT_RESOLVE][] = "$hostport:" . implode(',', $ips);
				}

				return $handler($request, $options);
			};
		};
	}
}

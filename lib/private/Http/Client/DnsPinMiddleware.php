<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Http\Client;

use OC\Net\IpAddressClassifier;
use OCP\Http\Client\LocalServerException;
use Psr\Http\Message\RequestInterface;

class DnsPinMiddleware {
	/** @var NegativeDnsCache */
	private $negativeDnsCache;
	private IpAddressClassifier $ipAddressClassifier;

	public function __construct(
		NegativeDnsCache $negativeDnsCache,
		IpAddressClassifier $ipAddressClassifier,
	) {
		$this->negativeDnsCache = $negativeDnsCache;
		$this->ipAddressClassifier = $ipAddressClassifier;
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
		$responses = $this->dnsGetRecord($hostname, DNS_SOA);

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
		// we only bother with A/AAAA records (not CNAME) because get_dns_record in PHP loops through to return the final A for a CNAME by default
		// see https://github.com/php/php-src/blob/23afe57f01e0915eef246eba60e60fda74fd2dcf/ext/standard/dns.c#L873-L874
		// also avoids some problematic retrieval attempts of CNAME for localhost in some (buggy) environments and other weirdness
		// we may also want to consider suppressing @dns_get_record to avoid filling logs during transient issues since we already check for the false return value
		// we can always add more logging later if desired
		// alternatively: we modify dnsGetRecord() to call dns_check_record()/checkdnsrr() to confirm existence before querying for the actual RRs. This avoids calling
		// the noisier dns_get_record() (which returns E_WARNINGs in addition to false when there are problems)
		$dnsTypes = \defined('AF_INET6') || @inet_pton('::1')
			? [DNS_A, DNS_AAAA]
			: [DNS_A];
		foreach ($dnsTypes as $dnsType) {
			if ($this->negativeDnsCache->isNegativeCached($target, $dnsType)) {
				continue;
			}

			$dnsResponses = $this->dnsGetRecord($target, $dnsType);
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

	/**
	 * Wrapper for dns_get_record
	 */
	protected function dnsGetRecord(string $hostname, int $type): array|false {
		// don't bother retreiving RRs via the noiser dns_get_record() if there aren't any to retrieve
		if (\checkdnsrr($hostname, "ANY") !== false) {
			return \dns_get_record($hostname, $type);
		} else {
			return false;
		}
	}

	public function addDnsPinning() {
		return function (callable $handler) {
			return function (
				RequestInterface $request,
				array $options,
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

				$targetIps = $this->dnsResolve(idn_to_utf8($hostName), 0);

				if (empty($targetIps)) {
					throw new LocalServerException('No DNS record found for ' . $hostName);
				}

				$curlResolves = [];

				foreach ($ports as $port) {
					$curlResolves["$hostName:$port"] = [];

					foreach ($targetIps as $ip) {
						if ($this->ipAddressClassifier->isLocalAddress($ip)) {
							// TODO: continue with all non-local IPs?
							throw new LocalServerException('Host "' . $ip . '" (' . $hostName . ':' . $port . ') violates local access rules');
						}
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

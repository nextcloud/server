<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2022, Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IDBConnection;
use Psr\Log\LoggerInterface;

class HSTSStore {

	private IDBConnection $db;
	private ITimeFactory $timeFactory;
	private LoggerInterface $logger;

	public function __construct(IDBConnection $db, ITimeFactory $timeFactory, LoggerInterface $logger) {
		$this->db = $db;
		$this->timeFactory = $timeFactory;
		$this->logger = $logger;
	}

	private function checkHost(string $host, bool $includeSubdomain) {
		// Look for the domain as is if we can't find it remove a subdomain and go up

		$this->logger->warning("Checking for host " . $host);

		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
		->from('hsts')
		->where($qb->expr()->eq('host', $qb->createNamedParameter($host)));

		$cursor = $qb->executeQuery();
		$data = $cursor->fetch();
		$cursor->closeCursor();

		if ($data !== false) {
			$this->logger->warning("GOT DATA");
			$this->logger->warning(json_encode($data));
		}

		if ($data !== false 
			&& $this->timeFactory->getTime() < $data['expires']
			&& (!$includeSubdomain || ($includeSubdomain && $data['includeSubdomains']))
			) {
			$this->logger->warning("REWRITE");
			return true;
		}

		return false;
	}

	private function checkSuperHost(string $host): bool {
		$labels = explode('.', $host);

		$labelCount = count($labels);

		for ($i = 1; $i < $labelCount; $i++) {
			$domainName = implode('.', array_slice($labels, $labelCount - $i));

            if ($this->checkHost($domainName, true)) {
                return true;
            }
		}
		
		return false;
	}

	public function hasHSTS(string $host): bool {
		return $this->checkHost($host, false) || $this->checkSuperHost($host);
	}

	public function setHSTS(string $host, string $header): void {
		$directives = explode(';', $header);

		$maxAge = 0;
		$includeSubdomains = false;

		foreach ($directives as $directive) {
			$directive = trim($directive);

			if ($directive === 'includeSubDomains') {
				$includeSubdomains = true;
			} elseif ($directive === 'preload') {
				// We just ignore this
			} else {
				$data = explode('=', $directive);
				if (count($data) === 2 && trim($data[0]) === 'max-age' && is_numeric(trim($data[1]))) {
					$maxAge = max(0, (int)$data[1]);
				}
			}
		}

		if ($maxAge <= 0) {
			return;
		}

		$this->logger->warning("TIME TO SET HSTS");

		$expires = $this->timeFactory->getTime() + $maxAge;

		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from('hsts')
			->where($qb->expr()->eq('host', $qb->createNamedParameter($host)));
		
		$cursor = $qb->executeQuery();
		$data = $cursor->fetchOne();
		$cursor->closeCursor();


		$this->logger->warning("Q1");

		if ($data === false) {
			// No entry yet insert
			$qb = $this->db->getQueryBuilder();
			$qb->insert('hsts')
			->values([
				'host' => $qb->createNamedParameter($host),
				'expires' => $qb->createNamedParameter($expires),
				'includeSubdomains' => $qb->createNamedParameter($includeSubdomains)
			]);
			$this->logger->warning($qb->getSQL());
			$qb->executeStatement();
		} else {
			// Already set just update
			// No entry yet insert
			$qb = $this->db->getQueryBuilder();
			$qb->update('hsts')
			->set('expires', $qb->createNamedParameter($expires))
			->set('includeSubdomains', $qb->createNamedParameter($includeSubdomains))
			->where($qb->expr()->eq('host', $qb->createNamedParameter($host)));
			$qb->executeStatement();
		}
	}
}

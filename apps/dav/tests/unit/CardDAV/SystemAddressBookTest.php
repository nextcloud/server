<?php

declare(strict_types=1);

/*
 * @copyright 2023 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2023 Christoph Wurst <christoph@winzerhof-wurst.at>
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
 */

namespace OCA\DAV\Tests\unit\CardDAV;

use OC\AppFramework\Http\Request;
use OCA\DAV\CardDAV\SystemAddressbook;
use OCA\Federation\TrustedServers;
use OCP\Accounts\IAccountManager;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IRequest;
use PHPUnit\Framework\MockObject\MockObject;
use Sabre\CardDAV\Backend\BackendInterface;
use Sabre\VObject\Component\VCard;
use Sabre\VObject\Reader;
use Test\TestCase;

class SystemAddressBookTest extends TestCase {

	private MockObject|BackendInterface $cardDavBackend;
	private array $addressBookInfo;
	private IL10N|MockObject $l10n;
	private IConfig|MockObject $config;
	private IRequest|MockObject $request;
	private array $server;
	private TrustedServers|MockObject $trustedServers;
	private SystemAddressbook $addressBook;

	protected function setUp(): void {
		parent::setUp();

		$this->cardDavBackend = $this->createMock(BackendInterface::class);
		$this->addressBookInfo = [
			'id' => 123,
			'{DAV:}displayname' => 'Accounts',
			'principaluri' => 'principals/system/system',
		];
		$this->l10n = $this->createMock(IL10N::class);
		$this->config = $this->createMock(IConfig::class);
		$this->request = $this->createMock(Request::class);
		$this->server = [
			'PHP_AUTH_USER' => 'system',
			'PHP_AUTH_PW' => 'shared123',
		];
		$this->request->method('__get')->with('server')->willReturn($this->server);
		$this->trustedServers = $this->createMock(TrustedServers::class);

		$this->addressBook = new SystemAddressbook(
			$this->cardDavBackend,
			$this->addressBookInfo,
			$this->l10n,
			$this->config,
			$this->request,
			$this->trustedServers,
		);
	}

	public function testGetFilteredChildForFederation(): void {
		$this->trustedServers->expects(self::once())
			->method('getServers')
			->willReturn([
				[
					'shared_secret' => 'shared123',
				],
			]);
		$vcfWithScopes = <<<VCF
BEGIN:VCARD
VERSION:3.0
PRODID:-//Sabre//Sabre VObject 4.4.2//EN
UID:admin
FN;X-NC-SCOPE=v2-federated:admin
N;X-NC-SCOPE=v2-federated:admin;;;;
ADR;TYPE=OTHER;X-NC-SCOPE=v2-local:Testing test test test;;;;;;
EMAIL;TYPE=OTHER;X-NC-SCOPE=v2-federated:miau_lalala@gmx.net
TEL;TYPE=OTHER;X-NC-SCOPE=v2-local:+435454454544
CLOUD:admin@http://localhost
END:VCARD
VCF;
		$originalCard = [
			'carddata' => $vcfWithScopes,
		];
		$this->cardDavBackend->expects(self::once())
			->method('getCard')
			->with(123, 'user.vcf')
			->willReturn($originalCard);

		$card = $this->addressBook->getChild("user.vcf");

		/** @var VCard $vCard */
		$vCard = Reader::read($card->get());
		foreach ($vCard->children() as $child) {
			$scope = $child->offsetGet('X-NC-SCOPE');
			if ($scope !== null) {
				self::assertNotEquals(IAccountManager::SCOPE_PRIVATE, $scope->getValue());
				self::assertNotEquals(IAccountManager::SCOPE_LOCAL, $scope->getValue());
			}
		}
	}

}

<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2021, Joas Schilling <coding@schilljs.com>
 *
 * @author Joas Schilling <coding@schilljs.com>
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

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use PHPUnit\Framework\Assert;

require __DIR__ . '/../../vendor/autoload.php';

class CollaborationContext implements Context {
	use Provisioning;
	use AppConfiguration;

	/**
	 * @Then /^get autocomplete for "([^"]*)"$/
	 * @param TableNode|null $formData
	 */
	public function getAutocomplete(string $search, TableNode $formData): void {
		$query = $search === 'null' ? null : $search;

		$this->sendRequestForJSON('GET', '/core/autocomplete/get?itemType=files&itemId=123&search=' . $query, [
			'itemType' => 'files',
			'itemId' => '123',
			'search' => $query,
		]);
		$this->theHTTPStatusCodeShouldBe(200);

		$data = json_decode($this->response->getBody()->getContents(), true);
		$suggestions = $data['ocs']['data'];

		Assert::assertCount(count($formData->getHash()), $suggestions, 'Suggestion count does not match');
		Assert::assertEquals($formData->getHash(), array_map(static function ($suggestion, $expected) {
			$data = [];
			if (isset($expected['id'])) {
				$data['id'] = $suggestion['id'];
			}
			if (isset($expected['source'])) {
				$data['source'] = $suggestion['source'];
			}
			return $data;
		}, $suggestions, $formData->getHash()));
	}

	protected function resetAppConfigs(): void {
		$this->deleteServerConfig('core', 'shareapi_allow_share_dialog_user_enumeration');
		$this->deleteServerConfig('core', 'shareapi_restrict_user_enumeration_to_group');
		$this->deleteServerConfig('core', 'shareapi_restrict_user_enumeration_to_phone');
		$this->deleteServerConfig('core', 'shareapi_restrict_user_enumeration_full_match');
		$this->deleteServerConfig('core', 'shareapi_only_share_with_group_members');
	}
}

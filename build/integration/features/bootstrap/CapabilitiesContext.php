<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
use Behat\Behat\Context\Context;
use Behat\Behat\Context\SnippetAcceptingContext;
use PHPUnit\Framework\Assert;

require __DIR__ . '/../../vendor/autoload.php';

/**
 * Capabilities context.
 */
class CapabilitiesContext implements Context, SnippetAcceptingContext {
	use BasicStructure;
	use AppConfiguration;

	/**
	 * @Then /^fields of capabilities match with$/
	 * @param \Behat\Gherkin\Node\TableNode|null $formData
	 */
	public function checkCapabilitiesResponse(\Behat\Gherkin\Node\TableNode $formData) {
		$capabilitiesXML = simplexml_load_string($this->response->getBody());
		Assert::assertNotFalse($capabilitiesXML, 'Failed to fetch capabilities');
		$capabilitiesXML = $capabilitiesXML->data->capabilities;

		foreach ($formData->getHash() as $row) {
			$path_to_element = explode('@@@', $row['path_to_element']);
			$answeredValue = $capabilitiesXML->{$row['capability']};
			for ($i = 0; $i < count($path_to_element); $i++) {
				$answeredValue = $answeredValue->{$path_to_element[$i]};
			}
			$answeredValue = (string)$answeredValue;
			Assert::assertEquals(
				$row['value'] === 'EMPTY' ? '' : $row['value'],
				$answeredValue,
				'Failed field ' . $row['capability'] . ' ' . $row['path_to_element']
			);
		}
	}

	protected function resetAppConfigs() {
		$this->deleteServerConfig('core', 'shareapi_enabled');
		$this->deleteServerConfig('core', 'shareapi_allow_links');
		$this->deleteServerConfig('core', 'shareapi_allow_public_upload');
		$this->deleteServerConfig('core', 'shareapi_allow_resharing');
		$this->deleteServerConfig('files_sharing', 'outgoing_server2server_share_enabled');
		$this->deleteServerConfig('files_sharing', 'incoming_server2server_share_enabled');
		$this->deleteServerConfig('core', 'shareapi_enforce_links_password');
		$this->deleteServerConfig('core', 'shareapi_allow_public_notification');
		$this->deleteServerConfig('core', 'shareapi_default_expire_date');
		$this->deleteServerConfig('core', 'shareapi_enforce_expire_date');
		$this->deleteServerConfig('core', 'shareapi_allow_group_sharing');
	}
}

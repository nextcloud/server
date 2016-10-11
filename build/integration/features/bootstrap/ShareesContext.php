<?php
/**

 *
 * @author Joas Schilling <coding@schilljs.com>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
use Behat\Behat\Context\Context;
use Behat\Behat\Context\SnippetAcceptingContext;
use GuzzleHttp\Message\ResponseInterface;

require __DIR__ . '/../../vendor/autoload.php';


/**
 * Features context.
 */
class ShareesContext implements Context, SnippetAcceptingContext {
	use Provisioning;
	use AppConfiguration;

	/**
	 * @When /^getting sharees for$/
	 * @param \Behat\Gherkin\Node\TableNode $body
	 */
	public function whenGettingShareesFor($body) {
		$url = '/apps/files_sharing/api/v1/sharees';
		if ($body instanceof \Behat\Gherkin\Node\TableNode) {
			$parameters = [];
			foreach ($body->getRowsHash() as $key => $value) {
				$parameters[] = $key . '=' . $value;
			}
			if (!empty($parameters)) {
				$url .= '?' . implode('&', $parameters);
			}
		}

		$this->sendingTo('GET', $url);
	}

	/**
	 * @Then /^"([^"]*)" sharees returned (are|is empty)$/
	 * @param string $shareeType
	 * @param string $isEmpty
	 * @param \Behat\Gherkin\Node\TableNode|null $shareesList
	 */
	public function thenListOfSharees($shareeType, $isEmpty, $shareesList = null) {
		if ($isEmpty !== 'is empty') {
			$sharees = $shareesList->getRows();
			$respondedArray = $this->getArrayOfShareesResponded($this->response, $shareeType);
			PHPUnit_Framework_Assert::assertEquals($sharees, $respondedArray);
		} else {
			$respondedArray = $this->getArrayOfShareesResponded($this->response, $shareeType);
			PHPUnit_Framework_Assert::assertEmpty($respondedArray);
		}
	}

	public function getArrayOfShareesResponded(ResponseInterface $response, $shareeType) {
		$elements = $response->xml()->data;
		$elements = json_decode(json_encode($elements), 1);
		if (strpos($shareeType, 'exact ') === 0) {
			$elements = $elements['exact'];
			$shareeType = substr($shareeType, 6);
		}

		$sharees = [];
		foreach ($elements[$shareeType] as $element) {
			$sharees[] = [$element['label'], $element['value']['shareType'], $element['value']['shareWith']];
		}
		return $sharees;
	}

	protected function resetAppConfigs() {
		$this->modifyServerConfig('core', 'shareapi_only_share_with_group_members', 'no');
		$this->modifyServerConfig('core', 'shareapi_allow_share_dialog_user_enumeration', 'yes');
		$this->modifyServerConfig('core', 'shareapi_allow_group_sharing', 'yes');
	}
}

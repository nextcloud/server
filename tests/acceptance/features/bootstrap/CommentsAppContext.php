<?php
/**
 * @copyright Copyright (c) 2017 Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
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

class CommentsAppContext implements Context, ActorAwareInterface {
	use ActorAware;


	/**
	 * @When /^I create a new comment with "([^"]*)" as message$/
	 */
	public function iCreateANewCommentWithAsMessage($commentText) {
		$this->actor->find(self::newCommentField(), 2)->setValue($commentText);
		$this->actor->find(self::submitNewCommentButton(), 2)->click();
	}

	/**
	 * @Then /^I see that a comment was added$/
	 */
	public function iSeeThatACommentWasAdded() {
		$self = $this;

		$result = Utils::waitFor(function () use ($self) {
			return $self->isCommentAdded();
		}, 5, 0.5);

		PHPUnit_Framework_Assert::assertTrue($result);
	}

	public function isCommentAdded() {
		try {
				$locator = self::commentFields();
			$comments = $this->actor->getSession()->getPage()->findAll($locator->getSelector(), $locator->getLocator());
			PHPUnit_Framework_Assert::assertSame(1, count($comments));
		} catch (PHPUnit_Framework_ExpectationFailedException $e) {
			return false;
		}
		return true;
	}

	/**
	 * @return Locator
	 */
	public static function newCommentField() {
		return Locator::forThe()->css("div.newCommentRow .message")->descendantOf(FilesAppContext::currentSectionDetailsView())->
		describedAs("New comment field in the details view in Files app");
	}

	public static function commentFields() {
		return Locator::forThe()->css(".comments .comment .message")->descendantOf(FilesAppContext::currentSectionDetailsView())->
		describedAs("Comment fields in the details view in Files app");
	}

	/**
	 * @return Locator
	 */
	public static function submitNewCommentButton() {
		return Locator::forThe()->css("div.newCommentRow .submit")->descendantOf(FilesAppContext::currentSectionDetailsView())->
		describedAs("Submit new comment button in the details view in Files app");
	}
}

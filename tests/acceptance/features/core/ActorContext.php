<?php

/**
 *
 * @copyright Copyright (c) 2017, Daniel Calviño Sánchez (danxuliu@gmail.com)
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

use Behat\Behat\Hook\Scope\BeforeStepScope;
use Behat\MinkExtension\Context\RawMinkContext;

/**
 * Behat context to set the actor used in sibling contexts.
 *
 * This helper context provides a step definition ("I act as XXX") to change the
 * current actor of the scenario, which makes possible to use different browser
 * sessions in the same scenario.
 *
 * Sibling contexts that want to have access to the current actor of the
 * scenario must implement the ActorAwareInterface; this can be done just by
 * using the ActorAware trait.
 *
 * Besides updating the current actor in sibling contexts the ActorContext also
 * propagates its inherited "base_url" Mink parameter to the Actors as needed.
 *
 * By default no multiplier for the find timeout is set in the Actors. However,
 * it can be customized using the "actorFindTimeoutMultiplier" parameter of the
 * ActorContext in "behat.yml".
 *
 * Every actor used in the scenarios must have a corresponding Mink session
 * declared in "behat.yml" with the same name as the actor. All used sessions
 * are stopped after each scenario is run.
 */
class ActorContext extends RawMinkContext {

	/**
	 * @var array
	 */
	private $actors;

	/**
	 * @var array
	 */
	private $sharedNotebook;

	/**
	 * @var Actor
	 */
	private $currentActor;

	/**
	 * @var float
	 */
	private $actorFindTimeoutMultiplier;

	/**
	 * Creates a new ActorContext.
	 *
	 * @param float $actorFindTimeoutMultiplier the find timeout multiplier to
	 *        set in the Actors.
	 */
	public function __construct($actorFindTimeoutMultiplier = 1) {
		$this->actorFindTimeoutMultiplier = $actorFindTimeoutMultiplier;
	}

	/**
	 * Sets a Mink parameter.
	 *
	 * When the "base_url" parameter is set its value is propagated to all the
	 * Actors.
	 *
	 * @param string $name the name of the parameter.
	 * @param string $value the value of the parameter.
	 */
	public function setMinkParameter($name, $value) {
		parent::setMinkParameter($name, $value);

		if ($name === "base_url") {
			foreach ($this->actors as $actor) {
				$actor->setBaseUrl($value);
			}
		}
	}

	/**
	 * @BeforeScenario
	 *
	 * Initializes the Actors for the new Scenario with the default Actor.
	 *
	 * Other Actors are added (and their Mink Sessions started) only when they
	 * are used in an "I act as XXX" step.
	 */
	public function initializeActors() {
		$this->actors = array();
		$this->sharedNotebook = array();

		$this->actors["default"] = new Actor($this->getSession(), $this->getMinkParameter("base_url"), $this->sharedNotebook);
		$this->actors["default"]->setFindTimeoutMultiplier($this->actorFindTimeoutMultiplier);

		$this->currentActor = $this->actors["default"];
	}

	/**
	 * @BeforeStep
	 */
	public function setCurrentActorInSiblingActorAwareContexts(BeforeStepScope $scope) {
		$environment = $scope->getEnvironment();

		foreach ($environment->getContexts() as $context) {
			if ($context instanceof ActorAwareInterface) {
				$context->setCurrentActor($this->currentActor);
			}
		}
	}

	/**
	 * @Given I act as :actorName
	 */
	public function iActAs($actorName) {
		if (!array_key_exists($actorName, $this->actors)) {
			$this->actors[$actorName] = new Actor($this->getSession($actorName), $this->getMinkParameter("base_url"), $this->sharedNotebook);
			$this->actors[$actorName]->setFindTimeoutMultiplier($this->actorFindTimeoutMultiplier);
		}

		$this->currentActor = $this->actors[$actorName];
	}

	/**
	 * @AfterScenario
	 *
	 * Stops all the Mink Sessions used in the last Scenario.
	 */
	public function cleanUpSessions() {
		foreach ($this->actors as $actor) {
			$actor->getSession()->stop();
		}
	}

}

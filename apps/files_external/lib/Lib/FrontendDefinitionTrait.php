<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Robin Appelman <robin@icewind.nl>
 * @author Robin McCorkell <robin@mccorkell.me.uk>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
namespace OCA\Files_External\Lib;

/**
 * Trait for objects that have a frontend representation
 */
trait FrontendDefinitionTrait {

	/** @var string human-readable mechanism name */
	private string $text = "";

	/** @var array<string, DefinitionParameter> parameters for mechanism */
	private array $parameters = [];

	/** @var string[] custom JS */
	private array $customJs = [];

	public function getText(): string {
		return $this->text;
	}

	public function setText(string $text): self {
		$this->text = $text;
		return $this;
	}

	public static function lexicalCompare(IFrontendDefinition $a, IFrontendDefinition $b): int {
		return strcmp($a->getText(), $b->getText());
	}

	/**
	 * @return array<string, DefinitionParameter>
	 */
	public function getParameters(): array {
		return $this->parameters;
	}

	/**
	 * @param list<DefinitionParameter> $parameters
	 */
	public function addParameters(array $parameters): self {
		foreach ($parameters as $parameter) {
			$this->addParameter($parameter);
		}
		return $this;
	}

	public function addParameter(DefinitionParameter $parameter): self {
		$this->parameters[$parameter->getName()] = $parameter;
		return $this;
	}

	/**
	 * @return string[]
	 */
	public function getCustomJs(): array {
		return $this->customJs;
	}

	/**
	 * @param string $custom
	 * @return self
	 */
	public function addCustomJs(string $custom): self {
		$this->customJs[] = $custom;
		return $this;
	}

	/**
	 * Serialize into JSON for client-side JS
	 */
	public function jsonSerializeDefinition(): array {
		$configuration = [];
		foreach ($this->getParameters() as $parameter) {
			$configuration[$parameter->getName()] = $parameter;
		}

		$data = [
			'name' => $this->getText(),
			'configuration' => $configuration,
			'custom' => $this->getCustomJs(),
		];
		return $data;
	}

	/**
	 * Check if parameters are satisfied in a StorageConfig
	 */
	public function validateStorageDefinition(StorageConfig $storage): bool {
		foreach ($this->getParameters() as $name => $parameter) {
			$value = $storage->getBackendOption($name);
			if (!is_null($value) || !$parameter->isOptional()) {
				if (!$parameter->validateValue($value)) {
					return false;
				}
				$storage->setBackendOption($name, $value);
			}
		}
		return true;
	}
}

<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
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
 * Trait for objects requiring an identifier (and/or identifier aliases)
 * Also supports deprecation to a different object, linking the objects
 */
trait IdentifierTrait {

	protected string $identifier = '';

	/** @var string[] */
	protected array $identifierAliases = [];
	protected ?IIdentifier $deprecateTo = null;

	public function getIdentifier(): string {
		return $this->identifier;
	}

	public function setIdentifier(string $identifier): self {
		$this->identifier = $identifier;
		$this->identifierAliases[] = $identifier;
		return $this;
	}

	/**
	 * @return string[]
	 */
	public function getIdentifierAliases(): array {
		return $this->identifierAliases;
	}

	public function addIdentifierAlias(string $alias): self {
		$this->identifierAliases[] = $alias;
		return $this;
	}

	public function getDeprecateTo(): ?IIdentifier {
		return $this->deprecateTo;
	}

	public function deprecateTo(IIdentifier $destinationObject): self {
		$this->deprecateTo = $destinationObject;
		return $this;
	}

	public function jsonSerializeIdentifier(): array {
		$data = [
			'identifier' => $this->identifier,
			'identifierAliases' => $this->identifierAliases,
		];
		if ($this->deprecateTo) {
			$data['deprecateTo'] = $this->deprecateTo->getIdentifier();
		}
		return $data;
	}
}

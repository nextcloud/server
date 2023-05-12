<?php
/**
 * @copyright 2022 Carl Schwan <carl@carlschwan.eu>
 *
 * @author Carl Schwan <carl@carlschwan.eu>
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
namespace OCA\Files_External\Lib;

interface IFrontendDefinition {

	public function getText(): string;

	public function setText(string $text): self;

	/**
	 * @return array<string, DefinitionParameter>
	 */
	public function getParameters(): array;

	/**
	 * @param list<DefinitionParameter> $parameters
	 */
	public function addParameters(array $parameters): self;

	public function addParameter(DefinitionParameter $parameter): self;

	/**
	 * @return string[]
	 */
	public function getCustomJs(): array;

	public function addCustomJs(string $custom): self;

	/**
	 * Serialize into JSON for client-side JS
	 */
	public function jsonSerializeDefinition(): array;

	/**
	 * Check if parameters are satisfied in a StorageConfig
	 */
	public function validateStorageDefinition(StorageConfig $storage): bool;
}

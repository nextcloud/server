<?php
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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

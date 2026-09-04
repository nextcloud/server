<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files\Command;

use DateTimeImmutable;
use OC\Core\Command\Info\FileUtils;
use OCP\Console\Attribute\Argument;
use OCP\Console\Attribute\AsCommand;
use OCP\Console\ExitCode;
use OCP\Console\IOutput;
use OCP\Files\Folder;
use OCP\Files\Node;
use OCP\IUserManager;

#[AsCommand(
	name: 'files:list',
	description: 'List the contents of a folder',
	supportsOutputFormat: true,
)]
class ListCommand {
	public function __construct(
		private readonly FileUtils $fileUtils,
		private readonly IUserManager $userManager,
	) {
	}

	public function __invoke(
		IOutput $output,
		#[Argument(description: 'Nextcloud path or fileid of the folder to list', suggestedValues: [self::class, 'suggestPaths'])]
		string $file,
	): ExitCode {
		$node = $this->fileUtils->getNode($file);

		if (!$node) {
			$output->writeln("<error>file $file not found</error>");
			return ExitCode::Failure;
		}

		if (!($node instanceof Folder)) {
			$output->writeln("<error>$file is not a folder, use <info>occ info:file $file</info> instead</error>");
			return ExitCode::Failure;
		}

		$children = $node->getDirectoryListing();
		usort($children, static fn (Node $a, Node $b) => $a->getName() <=> $b->getName());

		$rows = array_map(function (Node $child): array {
			return [
				'fileid' => $child->getId(),
				'name' => $child->getName(),
				'type' => $child instanceof Folder ? 'folder' : $child->getMimetype(),
				'size' => $child->getSize(),
				'mtime' => (new DateTimeImmutable('@' . $child->getMTime()))->format(DATE_ATOM),
				'permissions' => $this->fileUtils->formatPermissions($child->getType(), $child->getPermissions()),
			];
		}, $children);

		$output->writeTableInOutputFormat($rows);

		return ExitCode::Success;
	}

	/**
	 * Suggests usernames for the first path segment, folder children for the rest.
	 *
	 * @return string[]
	 */
	public function suggestPaths(string $currentWord): array {
		$lastSlash = strrpos($currentWord, '/');
		if ($lastSlash === false) {
			$suggestions = [];
			foreach ($this->userManager->search($currentWord) as $user) {
				$suggestions[] = $user->getUID() . '/';
			}
			return $suggestions;
		}

		$prefix = substr($currentWord, 0, $lastSlash + 1);
		$partial = substr($currentWord, $lastSlash + 1);

		$parent = $this->fileUtils->getNode(rtrim($prefix, '/'));
		if (!($parent instanceof Folder)) {
			return [];
		}

		$suggestions = [];
		foreach ($parent->getDirectoryListing() as $child) {
			if ($partial === '' || str_starts_with($child->getName(), $partial)) {
				$suggestions[] = $prefix . $child->getName() . ($child instanceof Folder ? '/' : '');
			}
		}
		return $suggestions;
	}
}

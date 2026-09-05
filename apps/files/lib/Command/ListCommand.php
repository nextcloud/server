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

		$entries = [];
		foreach ($node->getDirectoryListing() as $child) {
			$entries[$child->getName()] = $child;
		}
		ksort($entries);

		if ($node->getPath() === '/') {
			// User homes are separate mounts set up on demand, not children of the root
			// storage itself, so getDirectoryListing() alone would miss them. Listed after
			// the root's own entries (e.g. appdata) rather than sorted in among them.
			$userHomes = [];
			foreach ($this->userManager->search('') as $user) {
				$home = $this->fileUtils->getNode($user->getUID() . '/files');
				if ($home instanceof Folder) {
					$userHomes[$user->getUID()] = $home;
				}
			}
			ksort($userHomes);
			$entries += $userHomes;
		}

		$output->writeTableInOutputFormat(array_map($this->nodeToRow(...), array_keys($entries), $entries));

		return ExitCode::Success;
	}

	private function nodeToRow(string $name, Node $node): array {
		return [
			'fileid' => $node->getId(),
			'name' => $name,
			'type' => $node instanceof Folder ? 'folder' : $node->getMimetype(),
			'size' => $node->getSize(),
			'mtime' => (new DateTimeImmutable('@' . $node->getMTime()))->format(DATE_ATOM),
			'permissions' => $this->fileUtils->formatPermissions($node->getType(), $node->getPermissions()),
		];
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

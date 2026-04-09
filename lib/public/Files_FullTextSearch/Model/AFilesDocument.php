<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Files_FullTextSearch\Model;

use OC\FullTextSearch\Model\IndexDocument;
use OCP\FullTextSearch\Model\IIndexDocument;

/**
 * Abstract Class AFilesDocument
 *
 * This is mostly used by 3rd party apps that want to complete the IIndexDocument
 * with more information about a file before its index:
 *
 *    \OCP\Server::get(IEventDispatcher::class)->addListener(
 *        '\OCA\Files_FullTextSearch::onFileIndexing',
 *        function(GenericEvent $e) {
 *            //@var \OCP\Files\Node $file
 *            $file = $e->getArgument('file');
 *
 *            // @var \OCP\Files_FullTextSearch\Model\AFilesDocument $document
 *            $document = $e->getArgument('document');
 *        }
 *    );
 *
 * @since 15.0.0
 *
 */
abstract class AFilesDocument extends IndexDocument {
	/**
	 * Returns the owner of the document/file.
	 *
	 * @since 15.0.0
	 *
	 * @return string
	 */
	abstract public function getOwnerId(): string;


	/**
	 * Returns the current viewer of the document/file.
	 *
	 * @since 15.0.0
	 *
	 * @return string
	 */
	abstract public function getViewerId(): string;


	/**
	 * Returns the type of the document/file.
	 *
	 * @since 15.0.0
	 *
	 * @return string \OCP\Files\FileInfo::TYPE_FILE|\OCP\Files\FileInfo::TYPE_FOLDER
	 */
	abstract public function getType(): string;


	/**
	 * Returns the mimetype of the document/file.
	 *
	 * @since 15.0.0
	 *
	 * @return string
	 */
	abstract public function getMimetype(): string;

	/**
	 * Returns the path of the document/file.
	 *
	 * @since 15.0.0
	 *
	 * @return string
	 */
	abstract public function getPath(): string;
}

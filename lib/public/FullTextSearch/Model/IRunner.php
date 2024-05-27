<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\FullTextSearch\Model;

/**
 * Interface IRunner
 *
 * The indexing process can be long and heavy, and because errors can
 * be encountered the process is wrapped using this interface.
 * It allows the any extension of FullTextSearch to communicate with the process.
 *
 * The IRunner is coming with some methods so the Search Platform can
 * returns important information and errors to be displayed to the admin.
 *
 * @since 15.0.0
 *
 */
interface IRunner {
	/**
	 * @since 15.0.0
	 */
	public const RESULT_TYPE_SUCCESS = 1;

	/**
	 * @since 15.0.0
	 */
	public const RESULT_TYPE_WARNING = 4;

	/**
	 * @since 15.0.0
	 */
	public const RESULT_TYPE_FAIL = 9;


	/**
	 * Info are displayed in the user interface when an admin execute the
	 * ./occ fulltextsearch:index command.
	 *
	 * quick list of info that can be edited:
	 *   'documentId', 'info', 'title', 'resultIndex', 'resultStatus',
	 *   'content', 'documentCurrent', 'documentTotal', 'progressStatus',
	 *   'errorCurrent', 'errorException', 'errorIndex'.
	 *
	 * List of all editable info can be find in the Command\Index.php of the
	 * FullTextSearch app.
	 * (look for a comment 'full list of info that can be edited')
	 *
	 * @since 15.0.0
	 *
	 * @param string $info
	 * @param string $value
	 */
	public function setInfo(string $info, string $value);


	/**
	 * This method should be used when editing multiple info to avoid too many
	 * refresh of the interface.
	 *
	 * @since 15.0.0
	 *
	 * @param array $data
	 */
	public function setInfoArray(array $data);


	/**
	 * Method used to update the current Action when an index is running.
	 *
	 * This method should be used instead of manually update the 'action' using
	 * setInfo()/setInfoArray() as it is also used to keep the process alive,
	 * manage the input, and some statistics of the load of the process.
	 *
	 * $action is a string with no space
	 * $force should be set to true if the action is heavy while being executed
	 * multiple times
	 *
	 * @since 15.0.0
	 *
	 * @param string $action
	 * @param bool $force
	 *
	 * @return string
	 * @throws \Exception
	 */
	public function updateAction(string $action = '', bool $force = false): string;


	/**
	 * Call this method in a Search Platform or Content Provider if there is an
	 * issue while generating a document or while indexing the current document.
	 * This is used to store and display errors in the UI during an index to help
	 * admin to keep track of errors.
	 *
	 * @since 15.0.0
	 *
	 * @param IIndex $index
	 * @param string $message
	 * @param string $class
	 * @param int $sev
	 */
	public function newIndexError(IIndex $index, string $message, string $class = '', int $sev = 3);


	/**
	 * Call this method only in a Search Platform after an index of a document.
	 * This is used to store and display results (good or bad) in the UI during
	 * an index to help admin to keep track of fail and successful indexes.
	 *
	 * @since 15.0.0
	 *
	 * @param IIndex $index
	 * @param string $message
	 * @param string $status
	 * @param int $type
	 */
	public function newIndexResult(IIndex $index, string $message, string $status, int $type);
}

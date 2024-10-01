<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\FullTextSearch\Model;

/**
 * Interface IDocumentAccess
 *
 * This object is used as a data transfer object when
 *
 * - indexing a document,
 * - generating a search request.
 *
 * During the index, it is used to define which users, groups, circles, ...
 * have access to the IIndexDocument
 *
 * During the search, it is internally use to define to which group, circles, ...
 * a user that perform the search belongs to.
 *
 * @see IIndexDocument::setAccess
 *
 * @since 16.0.0
 *
 */
interface IDocumentAccess {
	/**
	 * Owner of the document can be set at the init of the object.
	 *
	 * @since 16.0.0
	 *
	 * IDocumentAccess constructor.
	 *
	 * @param string $ownerId
	 */
	public function __construct(string $ownerId = '');


	/**
	 * Set the Owner of the document.
	 *
	 * @since 16.0.0
	 *
	 * @param string $ownerId
	 *
	 * @return IDocumentAccess
	 */
	public function setOwnerId(string $ownerId): IDocumentAccess;

	/**
	 * Get the Owner of the document.
	 *
	 * @since 16.0.0
	 *
	 * @return string
	 */
	public function getOwnerId(): string;


	/**
	 * Set the viewer of the document.
	 *
	 * @since 16.0.0
	 *
	 * @param string $viewerId
	 *
	 * @return IDocumentAccess
	 */
	public function setViewerId(string $viewerId): IDocumentAccess;

	/**
	 * Get the viewer of the document.
	 *
	 * @since 16.0.0
	 *
	 * @return string
	 */
	public function getViewerId(): string;


	/**
	 * Set the list of users that have read access to the document.
	 *
	 * @since 16.0.0
	 *
	 * @param array $users
	 *
	 * @return IDocumentAccess
	 */
	public function setUsers(array $users): IDocumentAccess;

	/**
	 * Add an entry to the list of users that have read access to the document.
	 *
	 * @since 16.0.0
	 *
	 * @param string $user
	 *
	 * @return IDocumentAccess
	 */
	public function addUser(string $user): IDocumentAccess;

	/**
	 * Add multiple entries to the list of users that have read access to the
	 * document.
	 *
	 * @since 16.0.0
	 *
	 * @param array $users
	 *
	 * @return IDocumentAccess
	 */
	public function addUsers($users): IDocumentAccess;

	/**
	 * Get the complete list of users that have read access to the document.
	 *
	 * @since 16.0.0
	 *
	 * @return array
	 */
	public function getUsers(): array;


	/**
	 * Set the list of groups that have read access to the document.
	 *
	 * @since 16.0.0
	 *
	 * @param array $groups
	 *
	 * @return IDocumentAccess
	 */
	public function setGroups(array $groups): IDocumentAccess;

	/**
	 * Add an entry to the list of groups that have read access to the document.
	 *
	 * @since 16.0.0
	 *
	 * @param string $group
	 *
	 * @return IDocumentAccess
	 */
	public function addGroup(string $group): IDocumentAccess;

	/**
	 * Add multiple entries to the list of groups that have read access to the
	 * document.
	 *
	 * @since 16.0.0
	 *
	 * @param array $groups
	 *
	 * @return IDocumentAccess
	 */
	public function addGroups(array $groups);

	/**
	 * Get the complete list of groups that have read access to the document.
	 *
	 * @since 16.0.0
	 *
	 * @return array
	 */
	public function getGroups(): array;


	/**
	 * Set the list of circles that have read access to the document.
	 *
	 * @since 16.0.0
	 *
	 * @param array $circles
	 *
	 * @return IDocumentAccess
	 */
	public function setCircles(array $circles): IDocumentAccess;

	/**
	 * Add an entry to the list of circles that have read access to the document.
	 *
	 * @since 16.0.0
	 *
	 * @param string $circle
	 *
	 * @return IDocumentAccess
	 */
	public function addCircle(string $circle): IDocumentAccess;

	/**
	 * Add multiple entries to the list of groups that have read access to the
	 * document.
	 *
	 * @since 16.0.0
	 *
	 * @param array $circles
	 *
	 * @return IDocumentAccess
	 */
	public function addCircles(array $circles): IDocumentAccess;

	/**
	 * Get the complete list of circles that have read access to the document.
	 *
	 * @since 16.0.0
	 *
	 * @return array
	 */
	public function getCircles(): array;


	/**
	 * Set the list of links that have read access to the document.
	 *
	 * @since 16.0.0
	 *
	 * @param array $links
	 *
	 * @return IDocumentAccess
	 */
	public function setLinks(array $links): IDocumentAccess;

	/**
	 * Get the list of links that have read access to the document.
	 *
	 * @since 16.0.0
	 *
	 * @return array
	 */
	public function getLinks(): array;
}

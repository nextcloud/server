<?php

namespace OCP\Contacts\ContactsMenu;

use OCP\IUser;

interface IContactsStore {


	/**
	 * @param IUser $user
	 * @param $filter
	 * @return IEntry[]
	 */
	public function getContacts(IUser $user, $filter);

	/**
	 * @brief finds a contact by specifying the property to search on ($shareType) and the value ($shareWith)
	 * @param IUser $user
	 * @param integer $shareType
	 * @param string $shareWith
	 * @return IEntry|null
	 */
	public function findOne(IUser $user, $shareType, $shareWith);

}
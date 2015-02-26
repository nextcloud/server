<?php
 /**
 * ownCloud
 *
 * @author Thomas Müller
 * @copyright 2014 Thomas Müller >deepdiver@owncloud.com>
 *
 */

namespace OC\Contacts;

class LocalAddressBook implements \OCP\IAddressBook {

	/**
	 * @var \OCP\IUserManager
	 */
	private $userManager;

	/**
	 * @param $userManager
	 */
	public function __construct($userManager) {
		$this->userManager = $userManager;
	}

	/**
	 * @return string defining the technical unique key
	 */
	public function getKey() {
		return 'local';
	}

	/**
	 * In comparison to getKey() this function returns a human readable (maybe translated) name
	 *
	 * @return mixed
	 */
	public function getDisplayName() {
		return "Local users";
	}

	/**
	 * @param string $pattern which should match within the $searchProperties
	 * @param array $searchProperties defines the properties within the query pattern should match
	 * @param array $options - for future use. One should always have options!
	 * @return array an array of contacts which are arrays of key-value-pairs
	 */
	public function search($pattern, $searchProperties, $options) {
		$users = array();
		if($pattern == '') {
			// Fetch all contacts
			$users = $this->userManager->search('');
		} else {
			foreach($searchProperties as $property) {
				$result = array();
				if($property === 'FN') {
					$result = $this->userManager->searchDisplayName($pattern);
				} else if ($property === 'id') {
					$result = $this->userManager->search($pattern);
				}
				if (is_array($result)) {
					$users = array_merge($users, $result);
				}
			}
		}

		$contacts = array();
		foreach($users as $user){
			$contact = array(
				"id" => $user->getUID(),
				"FN" => $user->getDisplayname(),
				"EMAIL" => array(),
				"IMPP" => array(
						"x-owncloud-handle:" . $user->getUID()
						)
					);
			$contacts[] = $contact;
		}
		return $contacts;
	}

	/**
	 * @param array $properties this array if key-value-pairs defines a contact
	 * @return array an array representing the contact just created or updated
	 */
	public function createOrUpdate($properties) {
		return array();
	}

	/**
	 * @return int
	 */
	public function getPermissions() {
		return \OCP\Constants::PERMISSION_READ;
	}

	/**
	 * @param object $id the unique identifier to a contact
	 * @return bool successful or not
	 */
	public function delete($id) {
		return false;
	}
}

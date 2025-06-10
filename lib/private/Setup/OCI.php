<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Setup;

class OCI extends AbstractDatabase {
	public $dbprettyname = 'Oracle';

	protected $dbtablespace;

	public function initialize($config) {
		parent::initialize($config);
		if (array_key_exists('dbtablespace', $config)) {
			$this->dbtablespace = $config['dbtablespace'];
		} else {
			$this->dbtablespace = 'USERS';
		}
		// allow empty hostname for oracle
		$this->dbHost = $config['dbhost'];

		$this->config->setValues([
			'dbhost' => $this->dbHost,
			'dbtablespace' => $this->dbtablespace,
		]);
	}

	public function validate($config) {
		$errors = [];
		if (empty($config['dbuser']) && empty($config['dbname'])) {
			$errors[] = $this->trans->t('Enter the database Login and name for %s', [$this->dbprettyname]);
		} elseif (empty($config['dbuser'])) {
			$errors[] = $this->trans->t('Enter the database Login for %s', [$this->dbprettyname]);
		} elseif (empty($config['dbname'])) {
			$errors[] = $this->trans->t('Enter the database name for %s', [$this->dbprettyname]);
		}
		return $errors;
	}

	public function setupDatabase() {
		try {
			$this->connect();
		} catch (\Exception $e) {
			$errorMessage = $this->getLastError();
			if ($errorMessage) {
				throw new \OC\DatabaseSetupException($this->trans->t('Oracle connection could not be established'),
					$errorMessage . ' Check environment: ORACLE_HOME=' . getenv('ORACLE_HOME')
					. ' ORACLE_SID=' . getenv('ORACLE_SID')
					. ' LD_LIBRARY_PATH=' . getenv('LD_LIBRARY_PATH')
					. ' NLS_LANG=' . getenv('NLS_LANG')
					. ' tnsnames.ora is ' . (is_readable(getenv('ORACLE_HOME') . '/network/admin/tnsnames.ora') ? '' : 'not ') . 'readable', 0, $e);
			}
			throw new \OC\DatabaseSetupException($this->trans->t('Oracle Login and/or password not valid'),
				'Check environment: ORACLE_HOME=' . getenv('ORACLE_HOME')
				. ' ORACLE_SID=' . getenv('ORACLE_SID')
				. ' LD_LIBRARY_PATH=' . getenv('LD_LIBRARY_PATH')
				. ' NLS_LANG=' . getenv('NLS_LANG')
				. ' tnsnames.ora is ' . (is_readable(getenv('ORACLE_HOME') . '/network/admin/tnsnames.ora') ? '' : 'not ') . 'readable', 0, $e);
		}

		$this->config->setValues([
			'dbuser' => $this->dbUser,
			'dbname' => $this->dbName,
			'dbpassword' => $this->dbPassword,
		]);
	}

	/**
	 * @param resource $connection
	 * @return string
	 */
	protected function getLastError($connection = null) {
		if ($connection) {
			$error = oci_error($connection);
		} else {
			$error = oci_error();
		}
		foreach (['message', 'code'] as $key) {
			if (isset($error[$key])) {
				return $error[$key];
			}
		}
		return '';
	}
}

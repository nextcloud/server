<?php
/**
 * @copyright Copyright (c) 2017 Lukas Reschke <lukas@statuscode.ch>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

$builder = \OC::$server->getDatabaseConnection()->getQueryBuilder();
$builder->select('*')->from('ado')->where($this->qb->expr()->eq('asdf', $_GET['asdf']));

class SqlInjectionCheckerTest {
	private $qb;

	public function __construct(\OCP\IDBConnection $dbConnection) {
		$this->qb = $dbConnection->getQueryBuilder();
	}

	public function testEqAndNeq() {
		$this->qb->select('*')->from('ado')->where($this->qb->expr()->eq('asdf', $this->qb->expr()->literal('myString')));
		$this->qb->select('*')->from('ado')->where($this->qb->expr()->eq('asdf', $this->qb->expr()->literal(0)));
		$this->qb->select('*')->from('ado')->where($this->qb->expr()->eq('asdf', $this->qb->expr()->literal($_GET['bar'])));
		$asdf = '123';
		$this->qb->select('*')->from('ado')->where($this->qb->expr()->eq('asdf', $this->qb->expr()->literal($asdf)));
		$asdf = 1;
		$this->qb->select('*')->from('ado')->where($this->qb->expr()->neq('asdf', $asdf));
		$asdf = '123';
		$this->qb->select('*')->from('ado')->where($this->qb->expr()->lt('asdf', $asdf));
		$this->qb->select('*')->from('ado')->where($this->qb->expr()->eq('s.resourceid', 'a.id'));
		$this->qb->select('*')->from('ado')->andWhere($this->qb->expr()->gte('asdf', $_GET['asdf']));
		$this->qb->select('*')
			->from('ado')
			->where($this->qb->expr()->eq('asdf', $this->qb->createNamedParameter('asdf')));
		$this->qb->select('*')
			->from('ado')
			->where($this->qb->expr()->eq('asdf', $this->qb->createPositionalParameter('asdf')));
	}

	public function testInstantiatingDatabaseConnection() {
		$qb = \OC::$server->getDatabaseConnection();
		$qb->getQueryBuilder()->select('*')->from('ado')->where($this->qb->expr()->eq('asdf', $_GET['asdf']));
	}

	public function testSet() {
		$this->qb->update('file_locks')->set('lock', $this->qb->createNamedParameter('lukaslukaslukas'));
		$this->qb->update('file_locks')->set('lock', '1234');
		$asdf = '1234';
		$this->qb->update('file_locks')->set('lock', $asdf);
		$this->qb->update('file_locks')->set('lock', $_GET['asdf']);
	}

	public function testSetValue() {
		$this->qb->update('file_locks')->setValue('lock', $this->qb->createNamedParameter('lukaslukaslukas'));
		$this->qb->update('file_locks')->setValue('lock', '1234');
		$asdf = '1234';
		$this->qb->update('file_locks')->setValue('lock', $asdf);
		$this->qb->update('file_locks')->setValue('lock', $_GET['asdf']);
	}
}
<?php
declare(strict_types=1);


/**
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 * @copyright 2019, Maxence Lange <maxence@artificial-owl.com>
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


namespace OC\Core\Migrations;


use Closure;
use Doctrine\DBAL\Types\Type;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;


/**
 *
 */
class Version17000Date20190417091242 extends SimpleMigrationStep {


	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 *
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options
	): ISchemaWrapper {

		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();


		/**
		 * entities
		 *
		 * - id             string    - a unique uuid to ident the entity
		 * - type           string    - type of the entity: no_member, unique, group, admin_group
		 * - owner_id       string    - id from 'entitied_accounts' for the owner
		 * - visibility     small int - visible to all, visible to owner only, visible to members
		 * - access         small int - free, invite only, request needed
		 * - name           string    - name of the entity
		 * - creation       datetime
		 */
		$table = $schema->createTable('entities');
		$table->addColumn(
			'id', Type::STRING,
			[
				'notnull' => true,
				'length'  => 11,
			]
		);
		$table->addColumn(
			'type', Type::STRING,
			[
				'notnull' => true,
				'length'  => 15
			]
		);
		$table->addColumn(
			'owner_id', Type::STRING,
			[
				'notnull' => true,
				'length'  => 11,
			]
		);
		$table->addColumn(
			'visibility', Type::SMALLINT,
			[
				'notnull' => true,
				'length'  => 1,
			]
		);
		$table->addColumn(
			'access', Type::SMALLINT,
			[
				'notnull' => true,
				'length'  => 1,
			]
		);
		$table->addColumn(
			'name', Type::STRING,
			[
				'notnull' => true,
				'length'  => 63
			]
		);
		$table->addColumn(
			'creation', Type::DATETIME,
			[
				'notnull' => true
			]
		);
		$table->setPrimaryKey(['id']);


		/**
		 * entities_accounts
		 *
		 * - id             string    - a unique uuid to ident the account
		 * - type           string    - local_user, mail_address, guest_user
		 * - account        string    - account/user_id
		 * - creation       datetime
		 */
		$table = $schema->createTable('entities_accounts');
		$table->addColumn(
			'id', Type::STRING,
			[
				'notnull' => true,
				'length'  => 11,
			]
		);
		$table->addColumn(
			'type', Type::STRING,
			[
				'notnull' => true,
				'length'  => 15
			]
		);
		$table->addColumn(
			'account', Type::STRING,
			[
				'notnull' => true,
				'length'  => 127
			]
		);
		$table->addColumn(
			'delete_on', Type::INTEGER,
			[
				'notnull' => true,
				'length'  => 12
			]
		);
		$table->addColumn(
			'creation', Type::DATETIME,
			[
				'notnull' => true
			]
		);
		$table->setPrimaryKey(['id']);


		/**
		 * entities_members
		 *
		 * - id               string    - a unique uuid
		 * - entity_id        string    - id from 'entities'
		 * - account_id       string    - id from 'entities_accounts'
		 * - slave_entity_id  string    - id from 'entities'
		 * - status           string    - invited, requesting, member
		 * - level            small int - 1=member, 4=moderator, 8=admin
		 * - creation         datetime
		 */
		$table = $schema->createTable('entities_members');
		$table->addColumn(
			'id', Type::STRING,
			[
				'notnull' => true,
				'length'  => 11,
			]
		);
		$table->addColumn(
			'entity_id', Type::STRING,
			[
				'notnull' => true,
				'length'  => 11
			]
		);
		$table->addColumn(
			'account_id', Type::STRING,
			[
				'notnull' => false,
				'length'  => 11
			]
		);
		$table->addColumn(
			'slave_entity_id', Type::STRING,
			[
				'notnull' => false,
				'length'  => 11
			]
		);
		$table->addColumn(
			'status', Type::STRING,
			[
				'notnull' => true,
				'length'  => 15
			]
		);
		$table->addColumn(
			'level', Type::SMALLINT,
			[
				'notnull'  => true,
				'length'   => 1,
				'unsigned' => true
			]
		);
		$table->addColumn(
			'creation', Type::DATETIME,
			[
				'notnull' => true
			]
		);
		$table->setPrimaryKey(['id']);


		/**
		 * entities_types
		 *
		 * - id            int            - incremented and primary key, nothing more
		 * - type          string        - string that define the type
		 * - interface     string        - type of the type(sic)
		 * - class         string        - class to be called to manage the service
		 */
		$table = $schema->createTable('entities_types');
		$table->addColumn(
			'id', Type::INTEGER,
			[
				'autoincrement' => true,
				'notnull'       => true,
				'length'        => 3,
				'unsigned'      => true,
			]
		);
		$table->addColumn(
			'type', Type::STRING,
			[
				'notnull' => true,
				'length'  => 15
			]
		);
		$table->addColumn(
			'interface', Type::STRING,
			[
				'notnull' => true,
				'length'  => 31
			]
		);
		$table->addColumn(
			'class', Type::STRING,
			[
				'notnull' => true,
				'length'  => 127
			]
		);
		$table->setPrimaryKey(['id']);


		return $schema;
	}
}

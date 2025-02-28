<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\DB\Types;
use OCP\IDBConnection;
use OCP\Migration\Attributes\AddColumn;
use OCP\Migration\Attributes\ColumnType;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

#[AddColumn(table: 'calendars', name: 'enabled', type: ColumnType::BOOLEAN)]
#[AddColumn(table: 'calendarsubscriptions', name: 'enabled', type:  ColumnType::BOOLEAN)]
class Version1032Date20250226000000 extends SimpleMigrationStep {

	public function __construct(
		private readonly IDBConnection $db,
	) {
	}

	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		$tableCalendars = $schema->getTable('calendars');

		if (!$tableCalendars->hasColumn('enabled')) {
			$tableCalendars->addColumn('enabled', Types::BOOLEAN, [
				'notnull' => false,
				'default' => true
			]);
		}

		$tableCalendarSubscriptions = $schema->getTable('calendarsubscriptions');

		if (!$tableCalendarSubscriptions->hasColumn('enabled')) {
			$tableCalendarSubscriptions->addColumn('enabled', Types::BOOLEAN, [
				'notnull' => false,
				'default' => true
			]);
		}

		return $schema;
	}

	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if (!$schema->hasTable('calendars') || !$schema->hasTable('calendarsubscriptions')) {
			return;
		}

		if (!$schema->getTable('calendars')->hasColumn('enabled') || !$schema->getTable('calendarsubscriptions')->hasColumn('enabled')) {
			return;
		}

		// calendars table
		// principleuri: principals/users/user1 (static/static/userId)
		// uri: personal
		//
		// properties table
		// propertypath: calendars/user1/personal (static/userId/calendarId)
		// userid: user1
		/*
			UPDATE oc_calendars
			SET oc_calendars.enabled = (
				SELECT propertyvalue
				FROM oc_properties
				WHERE propertyname = '{http://owncloud.org/ns}calendar-enabled'
				AND propertypath = CONCAT(
					'calendars/',
					SUBSTRING(oc_calendars.principaluri, 18),
					'/',
					oc_calendars.uri
				)
			)
			WHERE oc_calendars.principaluri LIKE 'principals/users/%';
		*/
		
		$queryOuter = $this->db->getQueryBuilder();
		$queryInner = $this->db->getQueryBuilder();

		$queryInner->select('propertyvalue')
			->from('properties')
			->where($queryInner->expr()->eq(
				'propertyname',
				$queryInner->createParameter('property_name')
			))
			->andWhere($queryInner->expr()->eq(
				'propertypath',
				$queryInner->func()->concat(
					$queryInner->createParameter('uri_prefix'),
					$queryInner->func()->substring(
						$queryInner->prefixTableName('calendars') . '.principaluri',
						$queryInner->createParameter('principle_prefix_length')
					),
					$queryInner->createParameter('uri_separator'),
					$queryInner->prefixTableName('calendars') . '.uri'
				)
			));
		$queryOuter->update('calendars')
			->set(
				$queryOuter->prefixTableName('calendars') . '.enabled',
				$queryOuter->createFunction($queryInner->getSQL())
			)
			->where($queryOuter->expr()->like(
				$queryOuter->prefixTableName('calendars') . '.principaluri',
				$queryOuter->createParameter('principle_prefix_match')
			));
		$queryOuter->setParameter('property_name', '{http://owncloud.org/ns}calendar-enabled', IQueryBuilder::PARAM_STR);
		$queryOuter->setParameter('uri_prefix', 'calendars/', IQueryBuilder::PARAM_STR);
		$queryOuter->setParameter('uri_separator', '/', IQueryBuilder::PARAM_STR);
		$queryOuter->setParameter('principle_prefix_length', 18, IQueryBuilder::PARAM_INT);
		$queryOuter->setParameter('principle_prefix_match', 'principals/users/%', IQueryBuilder::PARAM_STR);

		$queryOuter->executeStatement();
		
	}

}

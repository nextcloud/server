<?php

declare(strict_types=1);

namespace OCA\SocialLogin\Migration;

use Closure;
use Doctrine\DBAL\Types\Types;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version040400Date20210410094126 extends SimpleMigrationStep {

    /**
     * @param IOutput $output
     * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
     * @param array $options
     */
    public function preSchemaChange(IOutput $output, Closure $schemaClosure, array $options) {
    }

    /**
     * @param IOutput $output
     * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
     * @param array $options
     * @return null|ISchemaWrapper
     */
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        if (!$schema->hasTable('sociallogin_connect')) {
            $table = $schema->createTable('sociallogin_connect');
            $table->addColumn('id', Types::BIGINT, [
				'autoincrement' => true,
				'unsigned' => true,
			]);
            $table->addColumn('uid', Types::STRING, [
                'notnull' => true,
            ]);
            $table->addColumn('identifier', Types::STRING, [
                'notnull' => true,
                'length' => 190,
            ]);
            $table->setPrimaryKey(['id']);
            $table->addUniqueIndex(['identifier'], 'sociallogin_connect_id');
        }
        return $schema;
    }

    /**
     * @param IOutput $output
     * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
     * @param array $options
     */
    public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options) {
    }
}

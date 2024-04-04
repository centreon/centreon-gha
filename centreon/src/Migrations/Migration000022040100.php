<?php

/*
 * Copyright 2005 - 2023 Centreon (https://www.centreon.com/)
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * https://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * For more information : contact@centreon.com
 *
 */

declare(strict_types=1);

namespace Migrations;

require_once __DIR__  . '/../../www/class/centreonLog.class.php';

use Centreon\Domain\Log\LoggerTrait;
use Core\Migration\Application\Repository\LegacyMigrationInterface;
use Core\Migration\Infrastructure\Repository\AbstractCoreMigration;
use Pimple\Container;

class Migration000022040100 extends AbstractCoreMigration implements LegacyMigrationInterface
{
    use LoggerTrait;

    private const VERSION = '22.04.1';

    public function __construct(
        private readonly Container $dependencyInjector,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function getVersion(): string
    {
        return self::VERSION;
    }

    /**
     * {@inheritDoc}
     */
    public function getDescription(): string
    {
        return sprintf(_('Update to %s'), self::VERSION);
    }

    /**
     * {@inheritDoc}
     */
    public function up(): void
    {
        $pearDB = $this->dependencyInjector['configuration_db'];
        $pearDBO = $this->dependencyInjector['realtime_db'];


        /* Update-CSTG-22.04.1.sql */

        $pearDBO->query(
            <<<'SQL'
                ALTER TABLE services
                MODIFY COLUMN `action_url` VARCHAR(2048) DEFAULT NULL,
                MODIFY COLUMN `notes_url` VARCHAR(2048) DEFAULT NULL,
                MODIFY COLUMN `notes` VARCHAR(512) DEFAULT NULL
                SQL
        );

        $pearDBO->query(
            <<<'SQL'
                ALTER TABLE hosts
                MODIFY COLUMN `action_url` VARCHAR(2048) DEFAULT NULL,
                MODIFY COLUMN `notes_url` VARCHAR(2048) DEFAULT NULL,
                MODIFY COLUMN `notes` VARCHAR(512) DEFAULT NULL
                SQL
        );

        $pearDBO->query(
            <<<'SQL'
                ALTER TABLE resources
                MODIFY COLUMN `action_url` VARCHAR(2048) DEFAULT NULL,
                MODIFY COLUMN `notes_url` VARCHAR(2048) DEFAULT NULL,
                MODIFY COLUMN `notes` VARCHAR(512) DEFAULT NULL
                SQL
        );

        /* Update-22.04.1.php */

        $centreonLog = new \CentreonLog();

        //error specific content
        $versionOfTheUpgrade = 'UPGRADE - 22.04.1: ';
        $errorMessage = '';

        try {
            $pearDB->beginTransaction();

            $errorMessage = "Unable to update 'custom_configuration' column on 'provider_configuration' table";
            updateOpenIdConfiguration($pearDB);
            $pearDB->commit();

            $errorMessage = "Unable to create 'security_provider_access_group_relation' table";
            $pearDB->query("CREATE TABLE IF NOT EXISTS `security_provider_access_group_relation` (
                `claim_value` VARCHAR(255) NOT NULL,
                `access_group_id` int(11) NOT NULL,
                `provider_configuration_id` int(11) NOT NULL,
                PRIMARY KEY (`claim_value`, `access_group_id`, `provider_configuration_id`),
                CONSTRAINT `security_provider_access_group_id`
                    FOREIGN KEY (`access_group_id`)
                    REFERENCES `acl_groups` (`acl_group_id`) ON DELETE CASCADE,
                CONSTRAINT `security_provider_provider_configuration_id`
                    FOREIGN KEY (`provider_configuration_id`)
                    REFERENCES `provider_configuration` (`id`) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
            ");
        } catch (\Exception $e) {
            if ($pearDB->inTransaction()) {
                $pearDB->rollBack();
            }

            $centreonLog->insertLog(
                4,
                $versionOfTheUpgrade . $errorMessage .
                " - Code : " . (int)$e->getCode() .
                " - Error : " . $e->getMessage() .
                " - Trace : " . $e->getTraceAsString()
            );

            throw new \Exception($versionOfTheUpgrade . $errorMessage, (int)$e->getCode(), $e);
        }

        /**
         * Update OpenID Configuration with Auto Import options
         *
         * @param \CentreonDB $pearDB
         * @throws \Exception
         */
        function updateOpenIdConfiguration(\CentreonDB $pearDB): void
        {
            $statement = $pearDB->query("SELECT custom_configuration FROM provider_configuration WHERE name='openid'");
            if ($statement !== false && $result = $statement->fetch(\PDO::FETCH_ASSOC)) {
                $defaultContactGroupId = createOpenIdDefaultContactGroup($pearDB);
                $openIdCustomConfiguration = json_decode($result['custom_configuration'], true);
                $openIdCustomConfiguration["auto_import"] = false;
                $openIdCustomConfiguration["contact_template_id"] = null;
                $openIdCustomConfiguration["email_bind_attribute"] = null;
                $openIdCustomConfiguration["fullname_bind_attribute"] = null;
                $openIdCustomConfiguration["contact_group_id"] = $defaultContactGroupId;
                $openIdCustomConfiguration["claim_name"] = 'groups';

                $statement = $pearDB->prepare(
                    "UPDATE provider_configuration SET custom_configuration = :customConfiguration
                    WHERE name='openid'"
                );
                $statement->bindValue(':customConfiguration', json_encode($openIdCustomConfiguration), \PDO::PARAM_STR);
                $statement->execute();
            } else {
                throw new \Exception('No custom_configuration for open_id has been found');
            }
        }

        /**
         * create Default Contact Group for OpenId Configuration
         * Return its id to be inserted as contact_group_id in the openid custom configuration
         *
         * @param \CentreonDB $pearDB
         * @return int id of the created contact group
         */
        function createOpenIdDefaultContactGroup(\CentreonDB $pearDB)
        {
            $pearDB->query("INSERT INTO contactgroup (cg_name, cg_alias, cg_activate)
                VALUES ('OpenId Default', 'OpenId Default ContactGroup', '1')
            ");

            return $pearDB->lastInsertId();
        }


        /* Update-DB-22.04.1.sql */

        $pearDB->query(
            <<<'SQL'
                DELETE FROM topology
                WHERE topology_parent = 60901
                SQL
        );
    }

    /**
     * {@inheritDoc}
     */
    public function down(): void
    {
        // nothing
    }
}

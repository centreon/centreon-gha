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

class Migration000019100300 extends AbstractCoreMigration implements LegacyMigrationInterface
{
    use LoggerTrait;
    private const VERSION = '19.10.3';

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

        // Update-19.10.3.php

        $centreonLog = new \CentreonLog();

        // remove ldap users missing contact name
        // these users have been added using the auto-import ldap feature
        // and will be re-imported at their next login.
        try {
            $pearDB->query(
                'DELETE FROM contact WHERE contact_name = ""'
            );
        } catch (\PDOException $e) {
            $centreonLog->insertLog(
                2,
                'UPGRADE : 19.10.3 Unable to delete ldap auto-imported users with empty contact_name'
            );

            throw $e;
        }

        // correct the DN of manually imported users from an LDAP
        try {
            // finding the data of contacts linked to an LDAP
            $stmt = $pearDB->query(
                'SELECT contact_id, contact_name, contact_ldap_dn FROM contact WHERE ar_id is NOT NULL'
            );
            $updateDB = $pearDB->prepare(
                'UPDATE contact SET contact_ldap_dn = :newDn WHERE contact_id = :contactId'
            );
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                // removing the double slashes if needed and saving the corrected data
                if (mb_strpos($row['contact_ldap_dn'], '\\\\')) {
                    $newDn = str_replace('\\\\', '\\', $row['contact_ldap_dn']);

                    $updateDB->bindValue(':newDn', $newDn, \PDO::PARAM_STR);
                    $updateDB->bindValue(':contactId', $row['contact_id'], \PDO::PARAM_INT);
                    $updateDB->execute();
                }
            }
        } catch (\PDOException $e) {
            $centreonLog->insertLog(
                2,
                'UPGRADE : 19.10.3 Unable to correct the LDAP DN data'
            );

            throw $e;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function down(): void
    {
        // nothing
    }
}

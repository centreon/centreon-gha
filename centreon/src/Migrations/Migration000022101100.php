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

class Migration000022101100 extends AbstractCoreMigration implements LegacyMigrationInterface
{
    use LoggerTrait;

    private const VERSION = '22.10.11';

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
        $pearDBO = $this->dependencyInjector['realtime_db'];


        /* Update-22.10.11.php */

        $centreonLog = new \CentreonLog();

        //error specific content
        $versionOfTheUpgrade = 'UPGRADE - 22.10.11: ';
        $errorMessage = '';

        $alterMetricsTable = function(\CentreonDB $pearDBO) {
            $pearDBO->query(
                <<<'SQL'
                    ALTER TABLE `metrics`
                    MODIFY COLUMN `metric_name` VARCHAR(1021) CHARACTER SET utf8 COLLATE utf8_bin DEFAULT NULL
                    SQL
            );
        };

        try {
            $errorMessage = 'Impossible to alter metrics table';
            $alterMetricsTable($pearDBO);
        } catch (\Exception $e) {
            $centreonLog->insertLog(
                4,
                $versionOfTheUpgrade . $errorMessage
                . ' - Code : ' . (int) $e->getCode()
                . ' - Error : ' . $e->getMessage()
                . ' - Trace : ' . $e->getTraceAsString()
            );

            throw new \Exception($versionOfTheUpgrade . $errorMessage, (int) $e->getCode(), $e);
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

<?php
<<<<<<< HEAD

/*
 * Copyright 2005 - 2022 Centreon (https://www.centreon.com/)
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
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

=======
>>>>>>> centreon/dev-21.10.x
namespace CentreonRemote\Domain\Resources\DefaultConfig;

/**
 * Get broker configuration template
 */
class CfgNagiosBrokerModule
{
    /**
     * Get template configuration
     * @todo move it as yml
     *
<<<<<<< HEAD
     * @return array<int, array<string,int|string>> the configuration template
=======
     * @return array the configuration template
>>>>>>> centreon/dev-21.10.x
     */
    public static function getConfiguration(): array
    {
        return [
            [
                'cfg_nagios_id' => 1,
                'broker_module' => '@centreon_engine_lib@/externalcmd.so',
            ],
            [
                'cfg_nagios_id' => 1,
                'broker_module' => '@centreonbroker_cbmod@ @centreonbroker_etc@/central-module.json',
            ],
        ];
    }
}

<?php

/*
 * Copyright 2005 - 2024 Centreon (https://www.centreon.com/)
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

namespace CentreonRemote\Domain\Resources\DefaultConfig;

/**
 * Get broker configuration template.
 */
class CfgResource
{
    /**
     * Get template configuration.
     *
     * @todo move it as yml
     *
     * @return array<int, string[]> the configuration template
     */
    public static function getConfiguration(): array
    {
        return [
            [
                'resource_name' => '$USER1$',
                'resource_line' => '@plugin_dir@',
                'resource_comment' => 'Nagios Plugins Path',
                'resource_activate' => '1',
            ],
            [
                'resource_name' => '$CENTREONPLUGINS$',
                'resource_line' => '@centreonplugins@',
                'resource_comment' => 'Centreon Plugins Path',
                'resource_activate' => '1',
            ],
        ];
    }
}

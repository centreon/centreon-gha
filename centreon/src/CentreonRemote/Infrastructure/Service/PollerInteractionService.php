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
namespace CentreonRemote\Infrastructure\Service;

use Pimple\Container;

class PollerInteractionService
{
<<<<<<< HEAD
=======

>>>>>>> centreon/dev-21.10.x
    /** @var Container */
    private $di;

    /** @var \CentreonDB */
    private $db;

    /**
     * @var \Centreon
     */
    private $centreon;


    public function __construct(Container $di)
    {
        global $centreon;

        $this->di = $di;
        $this->db = $di[\Centreon\ServiceProvider::CENTREON_DB_MANAGER]
            ->getAdapter('configuration_db')
            ->getCentreonDBInstance();

        $this->centreon = $centreon;
    }


<<<<<<< HEAD
    /**
     * @param int[] $pollers
     */
    public function generateAndExport($pollers): void
=======
    public function generateAndExport($pollers)
>>>>>>> centreon/dev-21.10.x
    {
        $pollers = (array) $pollers;

        $this->generateConfiguration($pollers);
        $this->moveConfigurationFiles($pollers);
        $this->restartPoller($pollers);
    }

<<<<<<< HEAD
    /**
     * @throws \Exception
     * @param int[] $pollerIDs
     */
    private function generateConfiguration(array $pollerIDs): void
=======
    private function generateConfiguration(array $pollerIDs)
>>>>>>> centreon/dev-21.10.x
    {
        $username = 'unknown';

        if (isset($this->centreon->user->name)) {
            $username = $this->centreon->user->name;
        }

        try {
            // Sync contact groups with ldap
            $contactGroupObject = new \CentreonContactgroup($this->db);
            $contactGroupObject->syncWithLdap();

            // Generate configuration
            $configGenerateObject = new \Generate($this->di);

            foreach ($pollerIDs as $pollerID) {
                $configGenerateObject->reset();
                $configGenerateObject->configPollerFromId($pollerID, $username);
            }
        } catch (\Exception $e) {
            throw new \Exception('There was an error generating the configuration for a poller.');
        }
    }

<<<<<<< HEAD
    /**
     * @throws \Exception
     * @param int[] $pollerIDs
     */
    private function moveConfigurationFiles(array $pollerIDs): void
=======
    private function moveConfigurationFiles(array $pollerIDs)
>>>>>>> centreon/dev-21.10.x
    {
        $centreonBrokerPath = _CENTREON_CACHEDIR_ . '/config/broker/';

        if (defined('_CENTREON_VARLIB_')) {
            $centCorePipe = _CENTREON_VARLIB_ . '/centcore.cmd';
        } else {
            $centCorePipe = '/var/lib/centreon/centcore.cmd';
        }

        $tabServer = [];
        $tabs = $this->centreon->user->access->getPollerAclConf([
            'fields'     => ['name', 'id', 'localhost'],
            'order'      => ['name'],
            'conditions' => ['ns_activate' => '1'],
            'keys'       => ['id']
        ]);

        foreach ($tabs as $tab) {
            if (in_array($tab['id'], $pollerIDs)) {
                $tabServer[$tab['id']] = [
                    'id'        => $tab['id'],
                    'name'      => $tab['name'],
                    'localhost' => $tab['localhost']
                ];
            }
        }

        foreach ($tabServer as $host) {
            if (in_array($host['id'], $pollerIDs)) {
                $listBrokerFile = glob($centreonBrokerPath . $host['id'] . "/*.{xml,cfg,sql}", GLOB_BRACE);

                passthru("echo 'SENDCFGFILE:{$host['id']}' >> {$centCorePipe}", $return);

                if ($return) {
                    throw new \Exception(_('Could not write into centcore.cmd. Please check file permissions.'));
                }

                if (count($listBrokerFile) > 0) {
                    passthru("echo 'SENDCBCFG:" . $host['id'] . "' >> $centCorePipe", $return);

                    if ($return) {
                        throw new \Exception(_('Could not write into centcore.cmd. Please check file permissions.'));
                    }
                }
            }
        }
    }

<<<<<<< HEAD
    /**
     * @throws \Exception
     * @param int[] $pollerIDs
     */
    private function restartPoller(array $pollerIDs): void
=======
    private function restartPoller(array $pollerIDs)
>>>>>>> centreon/dev-21.10.x
    {
        $tabServers = [];

        if (defined('_CENTREON_VARLIB_')) {
            $centCorePipe = _CENTREON_VARLIB_ . '/centcore.cmd';
        } else {
            $centCorePipe = '/var/lib/centreon/centcore.cmd';
        }

        $tabs = $this->centreon->user->access->getPollerAclConf([
            'fields'     => ['name', 'id', 'localhost', 'engine_restart_command'],
            'order'      => ['name'],
            'conditions' => ['ns_activate' => '1'],
            'keys'       => ['id']
        ]);

        $broker = new \CentreonBroker($this->db);
        $broker->reload();

        foreach ($tabs as $tab) {
            if (in_array($tab['id'], $pollerIDs)) {
                $tabServers[$tab['id']] = [
                    'id'          => $tab['id'],
                    'name'        => $tab['name'],
                    'localhost'   => $tab['localhost'],
                    'engine_restart_command' => $tab['engine_restart_command']
                ];
            }
        }

        foreach ($tabServers as $poller) {
            if (isset($poller['localhost']) && $poller['localhost'] == 1) {
                shell_exec("sudo {$poller['engine_restart_command']}");
            } else {
                if ($fh = @fopen($centCorePipe, 'a+')) {
                    fwrite($fh, 'RESTART:' . $poller['id'] . "\n");
                    fclose($fh);
                } else {
                    throw new \Exception(_('Could not write into centcore.cmd. Please check file permissions.'));
                }
            }

            $restartTimeQuery = "UPDATE `nagios_server` 
                SET `last_restart` = '" . time() . "' 
                WHERE `id` = '{$poller['id']}'";
            $this->db->query($restartTimeQuery);
        }

        // Find restart actions in modules
        foreach ($this->centreon->modules as $key => $value) {
            $moduleFiles = glob(_CENTREON_PATH_ . 'www/modules/' . $key . '/restart_pollers/*.php');

            if ($value['restart'] && $moduleFiles) {
                foreach ($moduleFiles as $fileName) {
                    include $fileName;
                }
            }
        }
    }
}

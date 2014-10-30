<?php
/*
 * Copyright 2005-2014 MERETHIS
 * Centreon is developped by : Julien Mathis and Romain Le Merlus under
 * GPL Licence 2.0.
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License as published by the Free Software
 * Foundation ; either version 2 of the License.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
 * PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * this program; if not, see <http://www.gnu.org/licenses>.
 *
 * Linking this program statically or dynamically with other modules is making a
 * combined work based on this program. Thus, the terms and conditions of the GNU
 * General Public License cover the whole combination.
 *
 * As a special exception, the copyright holders of this program give MERETHIS
 * permission to link this program with independent modules to produce an executable,
 * regardless of the license terms of these independent modules, and to copy and
 * distribute the resulting executable under terms of MERETHIS choice, provided that
 * MERETHIS also meet, for each linked independent module, the terms  and conditions
 * of the license of that module. An independent module is a module which is not
 * derived from this program. If you modify this program, you may extend this
 * exception to your version of the program, but you are not obliged to do so. If you
 * do not wish to do so, delete this exception statement from your version.
 *
 * For more information : contact@centreon.com
 *
 */

namespace CentreonBroker\Repository;

use Centreon\Internal\Di;
use CentreonAdministration\Repository\OptionRepository;
use CentreonConfiguration\Internal\Poller\Template\Manager as PollerTemplateManager;

/**
 * @author Sylvestre Ho <sho@merethis.com>
 * @package CentreonEngine
 * @subpackage Repository
 */
class BrokerRepository
{
    /**
     * Save broker parameters of a node
     *
     * @param int $pollerId
     * @param array $params
     */
    public static function save($pollerId, $params)
    {
        $db = Di::getDefault()->get('db_centreon');

        $arr = array();
        foreach ($params as $k => $v) {
            $arr[$k] = $v;
        }

        /* Save paths */
        /* Test if exists in db */
        $query = "SELECT COUNT(poller_id) as poller
            FROM cfg_centreonbroker_paths
            WHERE poller_id = :poller_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':poller_id', $pollerId, \PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch();
        $stmt->closeCursor();
        if ($row['poller'] > 0) {
            /* Update */
            $query = "UPDATE cfg_centreonbroker_paths SET 
                directory_config = :broker_etc_directory,
                directory_modules = :broker_module_directory,
                directory_logs = :broker_logs_directory,
                directory_data = :broker_data_directory,
                init_script = :init_script
                WHERE poller_id = :poller_id";
        } else {
            /* Insert */
            $query = "INSERT INTO cfg_centreonbroker_paths
                (poller_id, directory_config, directory_modules, directory_logs, directory_data, init_script) VALUES
                (:poller_id, :broker_etc_directory, :broker_module_directory, :broker_logs_directory, :broker_data_directory, :init_script)";
        }
        $stmt = $db->prepare($query);
        $stmt->bindParam(':poller_id', $pollerId);
        $stmt->bindParam(':broker_etc_directory', $arr['broker_etc_directory'], \PDO::PARAM_STR);
        $stmt->bindParam(':broker_module_directory', $arr['broker_module_directory'], \PDO::PARAM_STR);
        $stmt->bindParam(':broker_logs_directory', $arr['broker_logs_directory'], \PDO::PARAM_STR);
        $stmt->bindParam(':broker_data_directory', $arr['broker_data_directory'], \PDO::PARAM_STR);
        $stmt->bindParam(':init_script', $arr['broker_init_script'], \PDO::PARAM_STR);
        $stmt->execute();

        /* Save extract params */
        $listTpl = PollerTemplateManager::buildTemplatesList();
        $tmpl = $params['poller_tmpl'];
        if (!isset($listTpl[$tmpl])) {
            return;
        }
        $fileTpl = $listTpl[$tmpl]->getBrokerPath();
        $information = json_decode(file_get_contents($fileTpl), true);
        $listType = array('output', 'input', 'logger');
        /* setup */
        foreach ($information['content']['broker']['setup']  as $setup) {
            /* mode */
            foreach ($setup['params']['mode'] as $mode) {
                /* type */
                foreach ($mode as $type => $config) {
                    /* @todo one peer retention */
                    if ($type == 'normal') {
                        /* module */
                        foreach ($config as $module) {
                            static::insertConfig($pollerId, $module['general']['name']);
                            foreach ($listType as $type) {
                                if (isset($module[$type])) {
                                    $groupNb = 1;
                                    foreach ($module[$type] as $typeInfo) {
                                        /* Key */
                                        foreach ($typeInfo as $key => $value) {
                                            if (preg_match("/%([\w_]+)%/", $value, $matches)) {
                                                if (isset($params[$matches[1]]) && trim($params[$matches[1]]) !== "") {
                                                    static::insertPollerInfo($pollerId, $matches[1], $params[$matches[1]]);
                                                }
                                            }
                                        }
                                        $groupNb++;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Add a configuration for a module
     *
     * @param int $pollerId The poller id
     * @param string $configName The configuration module name
     */
    public static function insertConfig($pollerId, $configName) {
        $dbconn = Di::getDefault()->get('db_centreon');
        /* Test if the configuration is in database */
        $query = "SELECT config_id
            FROM cfg_centreonbroker
            WHERE poller_id = :poller_id
            AND config_name = :config_name";
        $stmt = $dbconn->prepare($query);
        $stmt->bindParam(':poller_id', $pollerId, \PDO::PARAM_INT);
        $stmt->bindParam(':config_name', $configName, \PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch();
        $stmt->closeCursor();
        if (false !== $row) {
            return;
        }
        $queryInsert = "INSERT INTO cfg_centreonbroker
            (poller_id, config_name) VALUES
            (:poller_id, :config_name)";
        $stmt = $dbconn->prepare($queryInsert);
        $stmt->bindParam(':poller_id', $pollerId, \PDO::PARAM_INT);
        $stmt->bindParam(':config_name', $configName, \PDO::PARAM_STR);
        $stmt->execute();
    }

    /**
     * Add or update a custom information for Centreon Broker set by a user
     *
     * @param int $pollerId The poller id
     * @param string $group The group name
     * @param int $groupId The group id
     * @param string $key The configuration name
     * @param string $value The configuration value
     */
    public static function insertUserInfo($configId, $group, $groupId, $key, $value)
    {
        $dbconn = Di::getDefault()->get('db_centreon');
        /* Test if the information is already in database */
        $query = "SELECT COUNT(*) as nb
            FROM cfg_centreonbroker_info
            WHERE config_id = :config_id
                AND config_key = :config_key
                AND config_group = :config_group
                AND config_group_id = :config_group_id";
        $stmt = $dbconn->prepare($query);
        $stmt->bindParam(':config_id', $configId, \PDO::PARAM_INT);
        $stmt->bindParam(':config_key', $key, \PDO::PARAM_STR);
        $stmt->bindParam(':config_group', $group, \PDO::PARAM_STR);
        $stmt->bindParam(':config_group_id', $groupId, \PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch();
        if ($row['nb'] > 0) {
            $query = "UPDATE cfg_centreonbroker_info SET
                config_value = :config_value,
                WHERE config_id = :config_id
                    AND config_key = :config_key
                    AND config_group = :config_group,
                    AND config_group_id = :config_group_id";
        } else {
            $query = "INSERT INTO cfg_centreonbroker_info
                (config_id, config_key, config_value, config_group, config_group_id) VALUES
                (:config_id, :config_key, :config_value, :config_group, :config_group_id)";
        }
        $stmt = $dbconn->prepare($query);
        $stmt->bindParam(':config_id', $configId, \PDO::PARAM_INT);
        $stmt->bindParam(':config_key', $key, \PDO::PARAM_STR);
        $stmt->bindParam(':config_value', $value, \PDO::PARAM_STR);
        $stmt->bindParam(':config_group', $group, \PDO::PARAM_STR);
        $stmt->bindParam(':config_group_id', $groupId, \PDO::PARAM_INT);
        $stmt->execute();
    }

    /**
     * Insert a custom information for a poller
     *
     * @param int $pollerId The poller id
     * @param string $key The name of configuration
     * @param string $value The value of configuration
     */
    public static function insertPollerInfo($pollerId, $key, $value)
    {
        $dbconn = Di::getDefault()->get('db_centreon');
        /* Test if the information is in database */
        $query = "SELECT COUNT(*) as nb
            FROM cfg_centreonbroker_pollervalues
            WHERE poller_id = :poller_id
                AND name = :name";
        $stmt = $dbconn->prepare($query);
        $stmt->bindParam(':poller_id', $pollerId, \PDO::PARAM_INT);
        $stmt->bindParam(':name', $key, \PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch();
        if ($row['nb'] > 0) {
            $query = "UPDATE cfg_centreonbroker_pollervalues SET
                value = :value
                WHERE poller_id = :poller_id
                    AND name = :name";
        } else {
            $query = "INSERT INTO cfg_centreonbroker_pollervalues
                (poller_id, name, value) VALUES
                (:poller_id, :name, :value)";
        }
        $stmt = $dbconn->prepare($query);
        $stmt->bindParam(':poller_id', $pollerId, \PDO::PARAM_INT);
        $stmt->bindParam(':name', $key, \PDO::PARAM_INT);
        $stmt->bindParam(':value', $value, \PDO::PARAM_INT);
        $stmt->execute();
    }

    /**
     * Get the paths for a Centreon Broker poller
     * 
     * @param int $pollerId
     * @return array
     */
    public static function getPathsFromPollerId($pollerId)
    {
        $db = Di::getDefault()->get('db_centreon');
        $sql = "SELECT directory_modules, directory_config, directory_logs, directory_data
            FROM cfg_centreonbroker_paths
            WHERE poller_id = :poller_id";
        $stmt = $db->prepare($sql);
        $stmt->execute(array(
            ':poller_id' => $pollerId
        ));
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $row;
    }

    /**
     * Load custom configuration values for Centreon Broker
     *
     * @param int $pollerId The poller id
     * @return array
     */
    public static function loadValues($pollerId)
    {
        $dbconn = Di::getDefault()->get('db_centreon');
        $query = "SELECT name, value
            FROM cfg_centreonbroker_pollervalues
            WHERE poller_id = :poller_id";
        $stmt = $dbconn->prepare($query);
        $stmt->bindParam(':poller_id', $pollerId, \PDO::PARAM_INT);
        $stmt->execute();
        $values = array();
        while ($row = $stmt->fetch()) {
            $values[$row['name']] = $row['value'];
        }
        return $values;
    }
    
    public static function getGlobalValues()
    {
        $globalOptions = array();
        
        $defaultOptionskeys = array(
            'rrd_metric_path',
            'rrd_status_path',
            'rrd_path',
            'rrd_port',
            'storage_interval',
            'broker_modules_directory',
            'broker_data_directory',
        );
        $defaultOptionsValues = OptionRepository::get('default', $defaultOptionskeys);
        
        
        $defaultOptionsValuesKeys = array_keys($defaultOptionsValues);
        foreach ($defaultOptionsValuesKeys as &$optValue) {
            switch($optValue) {
                default:
                    break;
                    
                case 'rrd_metric_path':
                    $optValue = 'rrd_metrics';
                    break;
                
                case 'rrd_status_path':
                    $optValue = 'rrd_status';
                    break;
                
                case 'storage_interval':
                    $optValue = 'interval';
                    break;
            }
            $optValue = 'global_' . $optValue;
        }
        $globalOptions = array_combine($defaultOptionsValuesKeys, array_values($defaultOptionsValues));
        
        return $globalOptions;
    }
}

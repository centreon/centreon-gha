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

namespace CentreonAdministration\Repository;

use CentreonAdministration\Models\Domain;
use CentreonRealtime\Repository\ServiceRepository;
use CentreonRealtime\Repository\MetricRepository;
use Centreon\Internal\Utils\Status as StatusUtils;
use Centreon\Internal\Utils\Tree as TreeUtils;
use Centreon\Internal\Di;

/**
 * @author Lionel Assepo <lassepo@merethis.com>
 * @package Centreon
 * @subpackage Repository
 */
class DomainRepository extends \CentreonAdministration\Repository\Repository
{
    /**
     *
     * @var string
     */
    public static $tableName = 'cfg_domains';
    
    /**
     *
     * @var string
     */
    public static $objectName = 'Domain';
    
    const DOMAIN_SYSTEM = 'System';
    const DOMAIN_HARDWARE = 'Hardware';
    const DOMAIN_NETWORK = 'Network';
    const DOMAIN_APPLICATION = 'Application';
    
    /**
     * Generic create action
     *
     * @param array $givenParameters
     * @return int id of created object
     */
    public static function create($givenParameters)
    {
        $givenParameters['parent_id'] = Domain::getIdByParameter('name', array('Application'));
        $givenParameters['isroot'] = 0;
        parent::create($givenParameters);
    }
    
    /**
     * 
     * @param type $domain
     */
    public static function getParent($domain)
    {
        if (is_string($domain)) {
            $domainId = Domain::getIdByParameter('name', array($domain));
            $domain = $domainId[0];
        }
        
        $currentDomain = Domain::get($domain);
        
        $parentDomainId = Domain::getIdByParameter('domain_id', $currentDomain['parent_id']);
        
        if (count($parentDomainId) > 0) {
            $parent = Domain::get($parentDomainId[0]);
        } else {
            $parent = $currentDomain;
        }
        
        return $parent;
    }

    /**
     * Get list of objects
     *
     * @param string $searchStr
     * @return array
     */
    public static function getFormList($searchStr = "")
    {
        $db = Di::getDefault()->get('db_centreon');

        $sql = "SELECT root.domain_id as root_id, root.name as root_name, 
            child.name as child_name, child.domain_id as child_id 
            FROM cfg_domains root LEFT OUTER JOIN cfg_domains child ON child.parent_id = root.domain_id 
            WHERE root.parent_id IS NULL
            ORDER BY root_name, child_name";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $finalList = array();
        $previous = 0;
        foreach ($rows as $row) {
            if ($row['root_id'] != $previous) {
                $finalList[] = array(
                    'id' => $row['root_id'],
                    'text' => $row['root_name']
                );
            }
            if (!is_null($row['child_name'])) {
                $finalList[] = array(
                    'id' => $row['child_id'],
                    'text' => TreeUtils::formatChild($row['child_name'])
                );
            }
            $previous = $row['root_id'];
        }

        return $finalList;
    }
    
    /**
     * 
     * @param string $domain
     * @param boolean $withChildren
     * @return array
     */
    public static function getDomain($domain, $withChildren = false)
    {
        $domainList = array();
        $mainDomainId = Domain::getIdByParameter('name', array($domain));
        if (count($mainDomainId) > 0) {
            $domainList[] = Domain::get($mainDomainId[0]);
            if ($withChildren) {
                array_merge($domainList, Domain::getList('*', -1, 0, null, 'ASC', array('parent_id' => $mainDomainId[0]))); 
            }
        }
        return $domainList;
    }
    
    /**
     * 
     * @param type $domain
     * @param type $service
     * @param type $metricList
     * @return type
     */
    public static function normalizeMetrics($domain, $service, $metricList)
    {
        $normalizeMetricSet = array();
        $normalizeFunction = 'normalizeMetricsFor' . $domain;
        if (method_exists(__CLASS__, $normalizeFunction)) {
            $normalizeMetricSet = self::$normalizeFunction($domain, $service, $metricList);
        } else {
            self::genericNormalizeMetrics($domain, $service, $metricList);
        }
        return $normalizeMetricSet;
    }
    
    /**
     * 
     * @param type $service
     * @param type $metricList
     */
    public static function genericNormalizeMetrics($domain, $service, $metricList)
    {
        $normalizeMetricSet = array();
        
        $explodedOutput = explode("\n", $service['output']);
        
        $normalizeMetricSet['id'] = $service['service_id'];
        $normalizeMetricSet['name'] = $service['service_description'];
        $normalizeMetricSet['output'] = $explodedOutput[0];
        $normalizeMetricSet['status'] = strtolower(StatusUtils::numToString($service['state'], StatusUtils::TYPE_SERVICE));
        
        return $normalizeMetricSet;
    }
    
    /**
     * 
     * @param array $metricList
     * @return array
     */
    public static function normalizeMetricsForNetwork($domain, $service, $metricList)
    {
        $normalizeMetricSet = array();

        return $normalizeMetricSet;
    }
    
    /**
     * 
     * @param array $metricList
     * @return array
     */
    public static function normalizeMetricsForTraffic($domain, $service, $metricList)
    {
        $normalizeMetricSet = array();
        $endTime = time();
        $startTime = $endTime - 3600;

        if (isset($metricList['traffic_in'])) {
            $in = $metricList['traffic_in'];
            
            // unit
            $currentUnitExploded = explode('/', $in['unit_name']);
            
            // Get values
            $metricValuesForIn = MetricRepository::getMetricsValuesFromRrd(
                $in['metric_id'],
                $startTime,
                $endTime,
                $currentUnitExploded[0]
            );
            $normalizeMetricSet['in'] = $metricValuesForIn['datas'];
            
            // Max
            if (is_null($in['max'])) {
                $in['max'] = $in['current_value'];
            }
            $normalizeMetricSet['in_max'] = $in['max'];
            
            // Set Unit
            if (!empty($metricValuesForIn['unit'])) {
                $in['unit_name'] = $metricValuesForIn['unit'] . '/' . $currentUnitExploded[1];
            }
            $normalizeMetricSet['unit'] = $in['unit_name'];
        }

        if (isset($metricList['traffic_out'])) {
            $out = $metricList['traffic_out'];
            
            // unit
            $currentUnitExploded = explode('/', $out['unit_name']);
            
            // Get values
            $metricValuesForout = MetricRepository::getMetricsValuesFromRrd(
                $out['metric_id'],
                $startTime,
                $endTime,
                $currentUnitExploded[0]
            );
            $normalizeMetricSet['out'] = $metricValuesForout['datas'];
            
            // Max
            if (is_null($out['max'])) {
                $out['max'] = $out['current_value'];
            }
            $normalizeMetricSet['out_max'] = $out['max'];
            
            // Set Unit
            if (!empty($metricValuesForout['unit'])) {
                $out['unit_name'] = $metricValuesForout['unit'] . '/' . $currentUnitExploded[1];
            }
            $normalizeMetricSet['unit'] = $out['unit_name'];
        }
        
        $normalizeMetricSet['status'] = strtolower(UtilStatus::numToString($service['state'], UtilStatus::TYPE_SERVICE));

        return $normalizeMetricSet;
    }

    /**
     * 
     * @param array $metricList
     * @return array
     */
    public static function normalizeMetricsForMemory($domain, $service, $metricList)
    {
        $normalizeMetricSet = array();

        $metric = $metricList['used'];

        $normalizeMetricSet['current'] = $metric['current_value'];
        $normalizeMetricSet['max'] = $metric['max'];
        $normalizeMetricSet['unit'] = $metric['unit_name'];

        return $normalizeMetricSet;
    }

    /**
     * 
     * @param array $metricList
     * @return array
     */
    public static function normalizeMetricsForFileSystem($domain, $service, $metricList)
    {
        $normalizeMetricSet = array();

        $metric = $metricList['used'];

        $normalizeMetricSet['current'] = $metric['current_value'];
        $normalizeMetricSet['max'] = $metric['max'];
        $normalizeMetricSet['unit'] = $metric['unit_name'];

        return $normalizeMetricSet;
    }

    /**
     *
     * @param array $metricList
     * @return array
     */
    public static function normalizeMetricsForCpu($domain, $service, $metricList)
    {
        $normalizeMetricSet = array();

        foreach ($metricList as $metricName => $metricData) {
            if (preg_match('/^cpu(\d+)/', $metricName)) {
                $normalizeMetricSet[$metricName] = $metricData['current_value'];
            }

        }
        return $normalizeMetricSet;
    }

    /**
     *
     * @param array $metricList
     * @return array
     */
    public static function normalizeMetricsForIO($domain, $service, $metricList)
    {
        $normalizeMetricSet = array();
        
        $read = $metricList['read'];
        $write = $metricList['write'];
        
        $normalizeMetricSet['read'] = $read['current_value'];
        $normalizeMetricSet['write'] = $write['current_value'];
        $normalizeMetricSet['unit'] = $read['unit_name'];
        
        return $normalizeMetricSet;
    }
}

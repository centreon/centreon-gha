<?php
/**
 * Copyright 2005-2019 Centreon
 * Centreon is developed by : Julien Mathis and Romain Le Merlus under
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
 * As a special exception, the copyright holders of this program give Centreon
 * permission to link this program with independent modules to produce an executable,
 * regardless of the license terms of these independent modules, and to copy and
 * distribute the resulting executable under terms of Centreon choice, provided that
 * Centreon also meet, for each linked independent module, the terms  and conditions
 * of the license of that module. An independent module is a module which is not
 * derived from this program. If you modify this program, you may extend this
 * exception to your version of the program, but you are not obliged to do so. If you
 * do not wish to do so, delete this exception statement from your version.
 *
 * For more information : contact@centreon.com
 *
 * SVN : $URL$
 * SVN : $Id$
 *
 */

/**
 * Class for cycle downtime management
 *
 */
class CentreonDowntime
{
    protected $db;

    //$safe is the key to be bound
    protected $search = '';

    //$safeSearch is the value to bind in the prepared statement
    protected $safeSearch = '';

    protected $nbRows = null;
    protected $localCommands;
    protected $localCmdFile = '';
    protected $remoteCommands;
    protected $remoteCmdFile = '';
    protected $remoteCmdDir = '';
    protected $varlib;
    protected $periods = null;
    protected $downtimes = null;

    private const SERVICE_REGISTER_SERVICE_TEMPLATE = 0;

    /**
     * Construtor
     *
     * @param CentreonDB $pearDB The connection to database centreon
     * @param string $varlib Centreon dynamic dir
     */
    public function __construct($pearDB, $varlib = null)
    {
        $this->db = $pearDB;
        $this->localCommands = array();
        $this->remoteCommands = array();
        if (!is_null($varlib)) {
            $this->remoteCmdDir = $varlib . '/centcore';
        }
    }

    public function initPeriods()
    {
        if (!is_null($this->periods)) {
            return $this->periods;
        }

        $this->periods = array();

        $query = 'SELECT dt_id, dtp_start_time, dtp_end_time, '
            . 'dtp_day_of_week, dtp_month_cycle, dtp_day_of_month, '
            . 'dtp_fixed, dtp_duration '
            . 'FROM downtime_period ';

        $res = $this->db->query($query);
        while ($row = $res->fetch()) {
            $this->periods[$row['dt_id']][] = $row;
        }
    }

    /**
     * Set the string to filter the results
     *
     * The string search is set to filter
     * In SQL, the string is "%$search%"
     *
     * @param string $search The string for filter
     */
    public function setSearch(string $search = '')
    {
        $this->safeSearch = '';
        if ('' !== $search) {
            $this->safeSearch = htmlentities($search, ENT_QUOTES, "UTF-8");
            $this->search = "dt_name LIKE :search";
        }
    }

    /**
     * Get the number of rows to display, when a search filter is applied
     *
     * @return int The number of rows
     */
    public function getNbRows()
    {
        /* Get the number of rows if getList is call before*/
        if (!is_null($this->nbRows)) {
            return $this->nbRows;
        }
        /* Get the number of rows with a COUNT(*) */
        $query = "SELECT COUNT(*) FROM downtime";
        if ($this->search) {
            $query .= " WHERE " . $this->search;
        }
        try {
            $res = $this->db->prepare($query);
            if ($this->search) {
                $res->bindValue(':search', '%' . $this->safeSearch . '%', \PDO::PARAM_STR);
            }
            $res->execute();
        } catch (\PDOException $e) {
            return 0;
        }
        $row = $res->fetch();
        $res->closeCursor();
        return $row["COUNT(*)"];
    }

    /**
     * Get the list of downtime, with applied search filter
     *
     * <code>
     * $return_array =
     *   array(
     *      array(
     *          'dt_id' => int, // The downtime id
     *          'dt_name' => string, // The downtime name
     *          'dt_description' => string, // The downtime description
     *          'dt_activate' => int // 0 Downtime is deactivated, 1 Downtime is activated
     *      ),...
     *   )
     * </code>
     *
     * @param int $num The page number
     * @param int $limit The limit by page for pagination
     * @return array The list of downtime
     */
    public function getList($num, $limit, $type = null)
    {
        if ($type == "h") {
            $query = "SELECT SQL_CALC_FOUND_ROWS downtime.dt_id, dt_name, dt_description, dt_activate FROM downtime " .
                "WHERE (downtime.dt_id IN(SELECT dt_id FROM downtime_host_relation) " .
                "OR downtime.dt_id IN (SELECT dt_id FROM downtime_hostgroup_relation)) " .
                ($this->search == '' ? "" : " AND ") . $this->search .
                " ORDER BY dt_name LIMIT " . $num * $limit . ", " . $limit;
        } elseif ($type == "s") {
            $query = "SELECT SQL_CALC_FOUND_ROWS downtime.dt_id, dt_name, dt_description, dt_activate FROM downtime " .
                "WHERE (downtime.dt_id IN (SELECT dt_id FROM downtime_service_relation) " .
                "OR downtime.dt_id IN (SELECT dt_id FROM downtime_servicegroup_relation)) " .
                ($this->search == '' ? "" : " AND ") . $this->search .
                " ORDER BY dt_name LIMIT " . $num * $limit . ", " . $limit;
        } else {
            $query = "SELECT SQL_CALC_FOUND_ROWS downtime.dt_id, dt_name, dt_description, dt_activate FROM downtime " .
                ($this->search == '' ? "" : "WHERE " . $this->search ) .
                " ORDER BY dt_name LIMIT " . $num * $limit . ", " . $limit;
        }
        try {
            $res = $this->db->prepare($query);
            if (!empty($this->safeSearch)) {
                $res->bindValue(':search', '%' . $this->safeSearch . '%', \PDO::PARAM_STR);
            }
            $res->execute();
        } catch (\PDOException $e) {
            return array();
        }
        $list = array();
        while ($row = $res->fetch()) {
            $list[] = $row;
        }
        $res->closeCursor();
        $this->nbRows = $this->db->numberRows();
        return $list;
    }

    public function getPeriods($id)
    {
        $this->initPeriods();

        $periods = array();
        if (!isset($this->periods[$id])) {
            return $periods;
        }

        foreach ($this->periods[$id] as $period) {
            $days = $period['dtp_day_of_week'];
            /* Make a array if the cycle is all */
            if ($period['dtp_month_cycle'] == 'all') {
                $days = preg_split('/\,/', $days);
            }
            /* Convert HH:mm:ss to HH:mm */
            $start_time = substr($period['dtp_start_time'], 0, strrpos($period['dtp_start_time'], ':'));
            $end_time = substr($period['dtp_end_time'], 0, strrpos($period['dtp_end_time'], ':'));

            $periods[] = array(
                'start_time' => $start_time,
                'end_time' => $end_time,
                'day_of_week' => $days,
                'month_cycle' => $period['dtp_month_cycle'],
                'day_of_month' => preg_split('/\,/', $period['dtp_day_of_month']),
                'fixed' => $period['dtp_fixed'],
                'duration' => $period['dtp_duration']
            );
        }

        return $periods;
    }

    /**
     * Get informations for a downtime
     *
     * <code>
     * $return_array =
     * array(
     *      'name' => string, // The downtime name
     *      'description' => string, // The downtime description
     *      'activate' => int // 0 Downtime is deactivated, 1 Downtime is activated
     * )
     * </code
     *
     * @param int $id The downtime id
     * @return array The informations for a downtime
     */
    public function getInfos($id)
    {
        $query = "SELECT dt_name, dt_description, dt_activate FROM downtime WHERE dt_id = :id";
        try {
            $res = $this->db->prepare($query);
            $res->bindValue(':id', $id, PDO::PARAM_INT);
            $res->execute();
        } catch (\PDOException $e) {
            return array('name' => '', 'description' => '', 'activate' => '');
        }
        $row = $res->fetch();
        return array(
            'name' => $row['dt_name'],
            'description' => $row['dt_description'],
            'activate' => $row['dt_activate'],
        );
    }

    /**
     * Intends to return hosts, hostgroups, services, servicesgroups linked to the recurrent downtime
     *
     * @param integer $downtimeId
     * @return array<string, array<int, array{id: string, activated: '0'|'1'}>>
     */
    public function getRelations(int $downtimeId): array
    {
        $relations = [
            'hosts' => [],
            'hostgroups' => [],
            'services' => [],
            'servicegroups' => [],
        ];

        foreach (array_keys($relations) as $resourceType) {
            switch ($resourceType) {
                case 'hosts':
                    $query = <<<'SQL'
                        SELECT
                            dhr.host_host_id AS resource_id,
                            h.host_activate AS activated
                        FROM downtime_host_relation dhr
                        INNER JOIN host h
                            ON dhr.host_host_id = h.host_id
                        WHERE
                            dt_id = :downtimeId;
                        SQL;
                    break;
                case 'hostgroups':
                    $query = <<<'SQL'
                        SELECT
                            dhgr.hg_hg_id AS resource_id,
                            hg.hg_activate AS activated
                        FROM downtime_hostgroup_relation dhgr
                        INNER JOIN hostgroup hg
                            ON dhgr.hg_hg_id = hg.hg_id
                        WHERE
                            dt_id = :downtimeId;
                        SQL;
                    break;
                case 'services':
                    $query = <<<'SQL'
                        SELECT CONCAT(dsr.host_host_id, CONCAT('-', dsr.service_service_id)) AS resource_id,
                            s.service_activate AS activated
                        FROM downtime_service_relation dsr
                        INNER JOIN service s
                            ON dsr.service_service_id = s.service_id
                        WHERE
                            dt_id = :downtimeId;
                        SQL;
                    break;
                case 'servicegroups':
                    $query = <<<'SQL'
                        SELECT
                            dsgr.sg_sg_id AS resource_id,
                            sg.sg_activate AS activated
                        FROM downtime_servicegroup_relation dsgr
                        INNER JOIN servicegroup sg
                            ON dsgr.sg_sg_id = sg.sg_id
                        WHERE
                            dt_id = :downtimeId;
                    SQL;
                    break;
            }

            $statement = $this->db->prepare($query);
            $statement->bindValue(':downtimeId', $downtimeId, \PDO::PARAM_INT);
            $statement->setFetchMode(\PDO::FETCH_ASSOC);
            $statement->execute();

            foreach ($statement as $record) {
                $relations[$resourceType][] = [
                    'id' => $record['resource_id'],
                    'activated' => $record['activated']
                ];
            }

            $statement->closeCursor();
        }

        return $relations;
    }

    /**
     * Returns all downtimes configured for enabled hosts
     *
     * @return array
     */
    public function getForEnabledHosts(): array
    {
        $downtimes = [];

        $request = <<<'SQL'
            SELECT dt.dt_id,
                dt.dt_activate,
                dtp.dtp_start_time,
                dtp.dtp_end_time,
                dtp.dtp_day_of_week,
                dtp.dtp_month_cycle,
                dtp.dtp_day_of_month,
                dtp.dtp_fixed,
                dtp.dtp_duration,
                h.host_id,
                h.host_name,
                NULL as service_id,
                NULL as service_description
            FROM downtime_period dtp
            INNER JOIN downtime dt 
                ON dtp.dt_id = dt.dt_id
            INNER JOIN downtime_host_relation dtr 
                ON dtp.dt_id = dtr.dt_id
            INNER JOIN host h 
                ON dtr.host_host_id = h.host_id
            WHERE h.host_activate = '1'
        SQL;

        $statement = $this->db->query($request);
        while ($record = $statement->fetch(\PDO::FETCH_ASSOC)) {
            $downtimes[] = $record;
        }

        return $downtimes;
    }

    /**
     * Returns all downtimes configured for enabled services
     *
     * @return array
     */
    public function getForEnabledServices(): array
    {
        $downtimes = [];

        $request = <<<'SQL'
            SELECT dt.dt_id,
                dt.dt_activate,
                dtp.dtp_start_time,
                dtp.dtp_end_time,
                dtp.dtp_day_of_week,
                dtp.dtp_month_cycle,
                dtp.dtp_day_of_month,
                dtp.dtp_fixed,
                dtp.dtp_duration,
                h.host_id,
                h.host_name,
                s.service_id,
                s.service_description
            FROM downtime_period dtp
            INNER JOIN downtime dt 
                ON dtp.dt_id = dt.dt_id
            INNER JOIN downtime_service_relation dtr 
                ON dtp.dt_id = dtr.dt_id
            INNER JOIN service s 
                ON dtr.service_service_id = s.service_id
            INNER JOIN host_service_relation hsr 
                ON hsr.service_service_id = s.service_id
            INNER JOIN host h 
                ON hsr.host_host_id = h.host_id 
                AND h.host_id = dtr.host_host_id
            WHERE s.service_activate = '1'
        UNION
            SELECT dt.dt_id,
                dt.dt_activate,
                dtp.dtp_start_time,
                dtp.dtp_end_time,
                dtp.dtp_day_of_week,
                dtp.dtp_month_cycle,
                dtp.dtp_day_of_month,
                dtp.dtp_fixed,
                dtp.dtp_duration,
                s.service_description as obj_name,
                dtr.service_service_id as obj_id,
                h.host_name as host_name,
                h.host_id
            FROM downtime_period dtp
            INNER JOIN downtime dt 
                ON dtp.dt_id = dt.dt_id
            INNER JOIN downtime_service_relation dtr 
                ON dtp.dt_id = dtr.dt_id
            INNER JOIN host h 
                ON dtr.host_host_id = h.host_id
            INNER JOIN hostgroup_relation hgr 
                ON hgr.hostgroup_hg_id = h.host_id
            INNER JOIN host_service_relation hsr 
                ON hsr.hostgroup_hg_id = hgr.hostgroup_hg_id
            INNER JOIN service s 
                ON s.service_id = hsr.service_service_id 
                AND dtr.service_service_id = s.service_id
            WHERE h.host_activate = '1'
        SQL;

        $statement = $this->db->query($request);

        while ($record = $statement->fetch(\PDO::FETCH_ASSOC)) {
            $downtimes[] = $record;
        }

        return $downtimes;
    }

    /**
     * Returns all downtimes configured for enabled hostgroups
     *
     * @return array
     */
    public function getForEnabledHostgroups(): array
    {
        $downtimes = [];

        $request = <<<'SQL'
            SELECT dt.dt_id,
                dt.dt_activate,
                dtp.dtp_start_time,
                dtp.dtp_end_time,
                dtp.dtp_day_of_week,
                dtp.dtp_month_cycle,
                dtp.dtp_day_of_month,
                dtp.dtp_fixed,
                dtp.dtp_duration,
                h.host_id,
                h.host_name,
                NULL as service_id,
                NULL as service_description
            FROM downtime_period dtp
            INNER JOIN downtime dt 
                ON dtp.dt_id = dt.dt_id
            INNER JOIN downtime_hostgroup_relation dhr 
                ON dtp.dt_id = dhr.dt_id
            INNER JOIN hostgroup_relation hgr 
                ON dhr.hg_hg_id = hgr.hostgroup_hg_id
            INNER JOIN host h 
                ON hgr.host_host_id = h.host_id
            INNER JOIN hostgroup hg 
                ON hgr.hostgroup_hg_id = hg.hg_id
            WHERE hg.hg_activate = '1'
        SQL;

        $statement = $this->db->query($request);
        $statement->execute();

        while ($record = $statement->fetch(\PDO::FETCH_ASSOC)) {
            $downtimes[] = $record;
        }

        return $downtimes;
    }

    public function getForEnabledServicegroups()
    {
        $request = <<<'SQL'
            SELECT dt.dt_id,
                   dt.dt_activate,
                   dtp.dtp_start_time,
                   dtp.dtp_end_time,
                   dtp.dtp_day_of_week,
                   dtp.dtp_month_cycle,
                   dtp.dtp_day_of_month,
                   dtp.dtp_fixed,
                   dtp.dtp_duration,
                   h.host_id,
                   h.host_name,
                   s.service_id,
                   s.service_description,
                   s.service_register
            FROM downtime_period dtp
            INNER JOIN downtime dt 
                ON dtp.dt_id = dt.dt_id
            INNER JOIN downtime_servicegroup_relation dtr 
                ON dtp.dt_id = dtr.dt_id
            INNER JOIN servicegroup_relation sgr 
                ON dtr.sg_sg_id = sgr.servicegroup_sg_id
            INNER JOIN service s 
                ON sgr.service_service_id = s.service_id
            INNER JOIN host h 
                ON sgr.host_host_id = h.host_id
            INNER JOIN servicegroup sg 
                ON sgr.servicegroup_sg_id = sg.sg_id
            WHERE sg.sg_activate = '1'
            UNION DISTINCT
                SELECT dt.dt_id,
                       dt.dt_activate,
                       dtp.dtp_start_time,
                       dtp.dtp_end_time,
                       dtp.dtp_day_of_week,
                       dtp.dtp_month_cycle,
                       dtp.dtp_day_of_month,
                       dtp.dtp_fixed,
                       dtp.dtp_duration,
                       h.host_id,
                       h.host_name,
                       s.service_id,
                       s.service_description,
                       s.service_register
                FROM downtime_period dtp
                INNER JOIN downtime dt 
                    ON dtp.dt_id = dt.dt_id
                INNER JOIN downtime_servicegroup_relation dtr 
                    ON dtp.dt_id = dtr.dt_id
                INNER JOIN servicegroup_relation sgr 
                    ON dtr.sg_sg_id = sgr.servicegroup_sg_id
                INNER JOIN host_service_relation hsr 
                    ON sgr.hostgroup_hg_id = hsr.hostgroup_hg_id
                INNER JOIN hostgroup_relation hgr 
                    ON hsr.hostgroup_hg_id = hgr.hostgroup_hg_id
                INNER JOIN service s 
                    ON hsr.service_service_id = s.service_id
                INNER JOIN host h 
                    ON hgr.host_host_id = h.host_id
                WHERE sgr.hostgroup_hg_id IS NOT NULL;
        SQL;

        $statement = $this->db->query($request);

        $templateDowntimeInformation = [];
        $downtimes = [];

        while ($record = $statement->fetch(\PDO::FETCH_ASSOC)) {
            if ((int) $record['service_register'] === self::SERVICE_REGISTER_SERVICE_TEMPLATE) {
                $templateDowntimeInformation[(int) $record['service_id']] = [
                    'dt_id' => $record['dt_id'],
                    'dt_activate' => $record['dt_activate'],
                    'dtp_start_time' => $record['dtp_start_time'],
                    'dtp_end_time' => $record['dtp_end_time'],
                    'dtp_day_of_week' => $record['dtp_day_of_week'],
                    'dtp_month_cycle' => $record['dtp_month_cycle'],
                    'dtp_day_of_month' => $record['dtp_day_of_month'],
                    'dtp_fixed' => $record['dtp_fixed'],
                    'dtp_duration' => $record['dtp_duration'],
                ];
            } else {
                $downtimes[] = $record;
            }
        }

        if (! empty($templateDowntimeInformation)) {
            foreach ($this->findServicesByServiceTemplateIds(array_keys($templateDowntimeInformation)) as $service) {
                $downtimes[] = array_merge(
                    $templateDowntimeInformation[$service['service_template_model_stm_id']],
                    [
                        'host_id' => $service['host_id'],
                        'host_name' => $service['host_name'],
                        'service_id' => $service['service_id'],
                        'service_description' => $service['service_description']
                    ]
                ); 
            }
        }

        return $downtimes;
    }

    /**
     * @param array $serviceTemplateIds
     * @return array
     * @throws PDOException
     */
    private function findServicesByServiceTemplateIds(array $serviceTemplateIds): array
    {
        $idList = implode(', ', $serviceTemplateIds);
        $request = <<<SQL
            SELECT
                h.host_name,
                h.host_id,
                s.service_id,
                s.service_description,
                s.service_template_model_stm_id
            FROM host h
            LEFT JOIN host_service_relation hsr
                ON h.host_id = hsr.host_host_id
            INNER JOIN service s
                ON hsr.service_service_id = s.service_id
            WHERE
                s.service_template_model_stm_id IN ($idList)
        SQL;

        $statement = $this->db->query($request);

        $services = [];
        while ($record = $statement->fetch(\PDO::FETCH_ASSOC)) {
            $services[] = $record;
        }

        return $services;
    }

    /**
     * Get the list of all downtimes
     *
     * @return array All downtimes
     */
    public function getForEnabledResources()
    {
        if (! is_null($this->downtimes)) {
            return $this->downtimes;
        }

        $downtimes = array_merge(
            $this->getForEnabledHosts(),
            $this->getForEnabledServices(),
            $this->getForEnabledServicegroups(),
            $this->getForEnabledHostgroups()
        );

        /* Remove duplicate downtimes */
        $downtimes = array_intersect_key($downtimes, array_unique(array_map('serialize', $downtimes)));
        sort($downtimes);

        $this->downtimes = $downtimes;

        return $this->downtimes;
    }

    /**
     * The duplicate one or many downtime, with periods
     *
     * @param array $ids The list of downtime id to replicate
     * @param array $nb The list of number of duplicate by downtime id
     */
    public function duplicate($ids, $nb): void
    {
        if (false === is_array($ids)) {
            $ids = array($ids);
        } else {
            $ids = $this->normalizeArray($ids);
        }
        foreach ($ids as $id) {
            if (isset($nb[$id])) {
                $query = "SELECT dt_id, dt_name, dt_description, dt_activate FROM downtime WHERE dt_id = :id";
                try {
                    $statement = $this->db->prepare($query);
                    $statement->bindParam(':id', $id, \PDO::PARAM_INT);
                    $statement->execute();
                } catch (\PDOException $e) {
                    return;
                }
                $row = $statement->fetch(\PDO::FETCH_ASSOC);
                $index = $i = 1;
                while ($i <= $nb[$id]) {
                    if (!$this->downtimeExists($row['dt_name'] . '_' . $index)) {
                        $row['index'] = $index;
                        $this->duplicateDowntime($row);
                        $i++;
                    }
                    $index++;
                }
            }
        }
    }

    /**
     * Add a downtime
     *
     * @param string $name The downtime name
     * @param string $desc The downtime description
     * @param int $activate If the downtime is activated (0 Downtime is deactivated, 1 Downtime is activated)
     * @return int|false The id of downtime or false if in error
     */
    public function add($name, $desc, $activate): int|false
    {
        if ($desc == "") {
            $desc = $name;
        }
        $query = "INSERT INTO downtime (dt_name, dt_description, dt_activate) VALUES (:name, :desc, :activate)";
        try {
            $statement = $this->db->prepare($query);

            $statement->bindParam(':name', $name, \PDO::PARAM_STR);
            $statement->bindParam(':desc', $desc, \PDO::PARAM_STR);
            $statement->bindParam(':activate', $activate, \PDO::PARAM_STR);

            $statement->execute();
        } catch (\PDOException $e) {
            return false;
        }
        $query = "SELECT dt_id FROM downtime WHERE dt_name = :name";
        $error = false;
        try {
            $statement = $this->db->prepare($query);
            $statement->bindParam(':name', $name, \PDO::PARAM_STR);
            $statement->execute();
        } catch (\PDOException $e) {
            $error = true;
        }
        if ($error || $statement->rowCount() == 0) {
            return false;
        }
        $row = $statement->fetch(\PDO::FETCH_ASSOC);
        return $row['dt_id'];
    }

    /**
     * Modify a downtime
     *
     * @param $id The downtime id
     * @param string $name The downtime name
     * @param string $desc The downtime description
     * @param int $activate If the downtime is activated (0 Downtime is deactivated, 1 Downtime is activated)
     */
    public function modify($id, $name, $desc, $activate): void
    {

        if ($desc == "") {
            $desc = $name;
        }

        $updateQuery = <<<SQL
            UPDATE downtime SET
                    dt_name = :name,
                    dt_description = :desc,
                    dt_activate = :activate
                    WHERE dt_id = :id
            SQL;
        $statement = $this->db->prepare($updateQuery);
        $statement->bindValue(':name', $name, \PDO::PARAM_STR);
        $statement->bindValue(':desc', $desc, \PDO::PARAM_STR);
        $statement->bindValue(':activate', $activate, \PDO::PARAM_STR);
        $statement->bindValue(':id', $id, \PDO::PARAM_INT);
        $statement->execute();
    }

    /**
     * Add a period to a downtime
     *
     * <code>
     * $infos =
     *  array(
     *      'start_period' => string, // The start time of the period (HH:mm)
     *      'end_period' => string, // The end time of the period (HH:mm)
     *      'days' => array, // The days in week, it is a array with the day number in the week (1 to 7)
     *                       // if month_cycle is all, first or last
     *                       // The days of month if month_cycle is none
     *      'month_cycle' => string, // The cycle method (all: all in month, first: first in month, last: last in month
     *                               // , none: only the day of the month)
     *      'fixed' => int, // If the downtime is fixed (0: flexible, 1: fixed)
     *      'duration' => int, // If the downtime is fexible, the duration of the downtime
     *  )
     * </code>
     *
     * @param int $id Downtime id
     * @param array $infos The information for a downtime period
     */
    public function addPeriod(int $id, array $infos): void
    {
        if (trim($infos['duration']) !== '') {

            $infos['duration'] = match (trim($infos['scale'])) {
                'm' => $infos['duration'] * 60,
                'h' => $infos['duration'] * 60 * 60,
                'd' => $infos['duration'] * 60 * 60 * 24,
                default => (int) $infos['duration'],
            };
        } else {
            $infos['duration'] = null;
        }

        if (!isset($infos['days'])) {
            $infos['days'] = [];
        }

    $query = <<<'SQL'
        INSERT INTO downtime_period (
             dt_id, dtp_start_time, dtp_end_time, dtp_day_of_week, dtp_month_cycle, dtp_day_of_month, dtp_fixed, dtp_duration
        ) VALUES (
            :id, :start_time, :end_time, :days, :month_cycle, :day_of_month, :fixed, :duration
        )
        SQL;

        $statement = $this->db->prepare($query);
        $statement->bindValue(':id', $id, \PDO::PARAM_INT);
        $statement->bindValue(':start_time', $infos['start_period'], \PDO::PARAM_STR);
        $statement->bindValue(':end_time', $infos['end_period'], \PDO::PARAM_STR);
        $statement->bindValue(':fixed', $infos['fixed'], \PDO::PARAM_STR);
        $statement->bindValue(':duration', $infos['duration'], \PDO::PARAM_INT);

        switch ($infos['period_type']) {
            case 'weekly_basis':
                $statement->bindValue(':days', implode(',', $infos['days']), \PDO::PARAM_STR);
                $statement->bindValue(':month_cycle', 'all', \PDO::PARAM_STR);
                $statement->bindValue(':day_of_month', null, \PDO::PARAM_NULL);
                break;
            case 'monthly_basis':
                $statement->bindValue(':days', null, \PDO::PARAM_STR);
                $statement->bindValue(':month_cycle', 'none', \PDO::PARAM_STR);
                $statement->bindValue(':day_of_month', implode(',', $infos['days']), \PDO::PARAM_STR);
                break;
            case 'specific_date':
                $statement->bindValue(':days', $infos['days'], \PDO::PARAM_STR);
                $statement->bindValue(':month_cycle', $infos['month_cycle'], \PDO::PARAM_STR);
                $statement->bindValue(':day_of_month', null, \PDO::PARAM_NULL);
                break;
        }
        $statement->execute();
    }

    /**
     * Delete all periods for a downtime
     *
     * @param int $id The downtime id
     */
    public function deletePeriods($id)
    {
        $query = "DELETE FROM downtime_period WHERE dt_id = :id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id, \PDO::PARAM_INT);
        $stmt->execute();
    }

    /**
     * Add relations for downtime
     *
     * @param int $id The downtime id
     * @param array $obj_ids The list of object id
     * @param string $obj_type The object type (host, hostgrp, service, servicegrp)
     */
    public function addRelations($id, $obj_ids, $obj_type)
    {
        switch ($obj_type) {
            case 'host':
                $query = "INSERT INTO downtime_host_relation (dt_id, host_host_id) VALUES (" . $id . ", %obj_id%)";
                break;
            case 'hostgrp':
                $query = "INSERT INTO downtime_hostgroup_relation (dt_id, hg_hg_id) VALUES (" . $id . ", %obj_id%)";
                break;
            case 'svc':
                $query = "INSERT INTO downtime_service_relation (dt_id, host_host_id, service_service_id)
                    VALUES (" . $id . ", %obj_id%)";
                break;
            case 'svcgrp':
                $query = "INSERT INTO downtime_servicegroup_relation (dt_id, sg_sg_id) VALUES (" . $id . ", %obj_id%)";
                break;
        }
        foreach ($obj_ids as $obj_id) {
            if ($obj_type == 'svc') {
                $obj_id = str_replace('-', ', ', $obj_id);
            }
            $queryInsert = str_replace('%obj_id%', $obj_id, $query);
            $this->db->query($queryInsert);
        }
    }

    /**
     * Delete all relations for a downtime
     *
     * @param int $id The downtime id
     */
    public function deteleRelations($id)
    {
        $query = "DELETE FROM downtime_host_relation WHERE dt_id = " . $id;
        $this->db->query($query);
        $query = "DELETE FROM downtime_hostgroup_relation WHERE dt_id = " . $id;
        $this->db->query($query);
        $query = "DELETE FROM downtime_service_relation WHERE dt_id = " . $id;
        $this->db->query($query);
        $query = "DELETE FROM downtime_servicegroup_relation WHERE dt_id = " . $id;
        $this->db->query($query);
    }

    /**
     * Activate a downtime
     *
     * @param int $id The downtime id
     * @see CentreonDowntime::setActivate
     */
    public function enable($id)
    {
        $this->setActivate($id, '1');
    }

    /**
     * Activate downtimes
     *
     * @param array $id The list of downtimes id
     * @see CentreonDowntime::setActivate
     */
    public function multiEnable($ids)
    {
        $this->setActivate($ids, '1');
    }

    /**
     * Deactivate a downtime
     *
     * @param int $id The downtime id
     * @see CentreonDowntime::setActivate
     */
    public function disable($id)
    {
        $this->setActivate($id, '0');
    }

    /**
     * Deactivate downtimes
     *
     * @param array $id The list of downtimes id
     * @see CentreonDowntime::setActivate
     */
    public function multiDisable($ids)
    {
        $this->setActivate($ids, '0');
    }

    /**
     * Delete a downtime
     *
     * @param int $id The downtime id
     * @see CentreonDowntime::multiDelete
     */
    public function delete($id)
    {
        $this->multiDelete($id);
    }

    /**
     * Delete downtimes
     *
     * @param array $id The list of downtimes id
     */
    public function multiDelete($ids)
    {
        if (false === is_array($ids)) {
            $ids = array($ids);
        } else {
            $ids = $this->normalizeArray($ids);
        }
        if (0 !== count($ids)) {
            $query = "DELETE FROM downtime WHERE dt_id IN (" . join(', ', $ids) . ")";
            $this->db->query($query);
        }
    }

    /**
     * Activate or deactivate a downtime
     *
     * @param array $ids The list of downtimes id
     * @param int $status 0 Downtime is deactivated, 1 Downtime is activated
     */
    private function setActivate($ids, $status)
    {
        if (false === is_array($ids)) {
            $ids = array($ids);
        } else {
            $ids = $this->normalizeArray($ids);
        }
        if (0 !== count($ids)) {
            $query = "UPDATE downtime SET dt_activate = '" . $status . "' WHERE dt_id IN (" . join(', ', $ids) . ")";
            $this->db->query($query);
        }
    }

    /**
     * Normalize a array from post from $key => $value to list of $key
     *
     * @param array $arr The array
     * @return array
     */
    private function normalizeArray($arr)
    {
        $list = array();
        foreach ($arr as $key => $value) {
            $list[] = $key;
        }
        return $list;
    }

    /**
     *
     * @param integer $field
     * @return array
     */
    public static function getDefaultValuesParameters($field)
    {
        $parameters = array();
        $parameters['currentObject']['table'] = 'downtime';
        $parameters['currentObject']['id'] = 'dt_id';
        $parameters['currentObject']['name'] = 'dt_name';
        $parameters['currentObject']['comparator'] = 'dt_id';

        switch ($field) {
            case 'host_relation':
                $parameters['type'] = 'relation';
                $parameters['object'] = 'centreonHost';
                $parameters['externalObject']['table'] = 'host';
                $parameters['externalObject']['id'] = 'host_id';
                $parameters['externalObject']['name'] = 'host_name';
                $parameters['externalObject']['comparator'] = 'host_id';
                $parameters['relationObject']['table'] = 'downtime_host_relation';
                $parameters['relationObject']['field'] = 'host_host_id';
                $parameters['relationObject']['comparator'] = 'dt_id';
                break;
            case 'hostgroup_relation':
                $parameters['type'] = 'relation';
                $parameters['object'] = 'centreonHostgroups';
                $parameters['externalObject']['table'] = 'hostgroup';
                $parameters['externalObject']['id'] = 'hg_id';
                $parameters['externalObject']['name'] = 'hg_name';
                $parameters['externalObject']['comparator'] = 'hg_id';
                $parameters['relationObject']['table'] = 'downtime_hostgroup_relation';
                $parameters['relationObject']['field'] = 'hg_hg_id';
                $parameters['relationObject']['comparator'] = 'dt_id';
                break;
            case 'svc_relation':
                $parameters['type'] = 'relation';
                $parameters['object'] = 'centreonService';
                $parameters['externalObject']['table'] = 'service';
                $parameters['externalObject']['id'] = 'service_id';
                $parameters['externalObject']['name'] = 'service_description';
                $parameters['externalObject']['comparator'] = 'service_id';
                $parameters['relationObject']['table'] = 'downtime_service_relation';
                $parameters['relationObject']['field'] = 'service_service_id';
                $parameters['relationObject']['comparator'] = 'dt_id';
                break;
            case 'svcgroup_relation':
                $parameters['type'] = 'relation';
                $parameters['object'] = 'centreonServicegroups';
                $parameters['externalObject']['table'] = 'servicegroup';
                $parameters['externalObject']['id'] = 'sg_id';
                $parameters['externalObject']['name'] = 'sg_name';
                $parameters['externalObject']['comparator'] = 'sg_id';
                $parameters['relationObject']['table'] = 'downtime_servicegroup_relation';
                $parameters['relationObject']['field'] = 'sg_sg_id';
                $parameters['relationObject']['comparator'] = 'dt_id';
                break;
        }

        return $parameters;
    }

    /**
     * All in one function to duplicate downtime.
     *
     * @param array $params
     */
    private function duplicateDowntime(array $params): void
    {
        $isAlreadyInTransaction = $this->db->inTransaction();
        if (! $isAlreadyInTransaction) {
            $this->db->beginTransaction();
        }
        try {
            $params['dt_id_new'] = $this->createDowntime($params);
            $this->createDowntimePeriods($params);
            $this->createDowntimeHostsRelations($params);
            $this->createDowntimeHostGroupsRelations($params);
            $this->createDowntimeServicesRelations($params);
            $this->createDowntimeServiceGroupsRelations($params);
            if (! $isAlreadyInTransaction) {
                $this->db->commit();
            }
        } catch (\Exception $e) {
            if (! $isAlreadyInTransaction) {
                $this->db->rollBack();
            }
        }
    }

    /**
     * Check if the downtime exists by name.
     *
     * @param string $dtName
     * @return bool
     */
    private function downtimeExists($dtName): bool
    {
        $statement = $this->db->prepare('SELECT 1 FROM downtime WHERE dt_name = :dt_name LIMIT 1');
        $statement->bindValue(':dt_name', $dtName, \PDO::PARAM_STR);
        $statement->execute();
        return (bool) $statement->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Creating new downtime and returns id.
     *
     * @param array<string, string> $params
     * @return int
     */
    private function createDowntime(array $params): int
    {
        $rq = 'INSERT INTO downtime (dt_name, dt_description, dt_activate)
			   VALUES (:dt_name, :dt_description, :dt_activate)';
        $statement = $this->db->prepare($rq);
        $statement->bindValue(':dt_name', $params['dt_name'] . '_' . $params['index'], \PDO::PARAM_STR);
        $statement->bindValue(':dt_description', $params['dt_description'], \PDO::PARAM_STR);
        $statement->bindValue(':dt_activate', $params['dt_activate'], \PDO::PARAM_STR);
        $statement->execute();
        return $this->db->lastInsertId();
    }

    /**
     * Creating downtime periods for the new downtime.
     *
     * @param array<string, string> $params
     */
    private function createDowntimePeriods(array $params): void
    {
        $query = 'INSERT INTO downtime_period (dt_id, dtp_start_time, dtp_end_time,
            dtp_day_of_week, dtp_month_cycle, dtp_day_of_month, dtp_fixed, dtp_duration,
            dtp_activate)
            SELECT :dt_id_new, dtp_start_time, dtp_end_time, dtp_day_of_week, dtp_month_cycle,
            dtp_day_of_month, dtp_fixed, dtp_duration, dtp_activate
            FROM downtime_period WHERE dt_id = :dt_id';
        $statement = $this->db->prepare($query);
        $statement->bindValue(':dt_id_new', (int) $params['dt_id_new'], \PDO::PARAM_INT);
        $statement->bindValue(':dt_id', (int) $params['dt_id'], \PDO::PARAM_INT);
        $statement->execute();
    }

    /**
     * Creating hosts relations for the new downtime.
     *
     * @param array<string, string> $params
     */
    private function createDowntimeHostsRelations(array $params): void
    {
        $statement = $this->db->prepare(
            'INSERT INTO downtime_host_relation (dt_id, host_host_id)
            SELECT :dt_id_new, host_host_id FROM downtime_host_relation WHERE dt_id = :dt_id'
        );
        $statement->bindValue(':dt_id_new', (int) $params['dt_id_new'], \PDO::PARAM_INT);
        $statement->bindValue(':dt_id', (int) $params['dt_id'], \PDO::PARAM_INT);
        $statement->execute();
    }

    /**
     * Create host groups for the new downtime.
     *
     * @param array<string, string> $params
     */
    private function createDowntimeHostGroupsRelations(array $params): void
    {
        $statement = $this->db->prepare(
            'INSERT INTO downtime_hostgroup_relation (dt_id, hg_hg_id)
            SELECT :dt_id_new, hg_hg_id FROM downtime_hostgroup_relation WHERE dt_id = :dt_id'
        );
        $statement->bindValue(':dt_id_new', (int) $params['dt_id_new'], \PDO::PARAM_INT);
        $statement->bindValue(':dt_id', (int) $params['dt_id'], \PDO::PARAM_INT);
        $statement->execute();
    }

    /**
     * Creating services relations for the new downtime.
     *
     * @param array<string, string> $params
     */
    private function createDowntimeServicesRelations(array $params): void
    {
        $statement = $this->db->prepare(
            'INSERT INTO downtime_service_relation
            (dt_id, host_host_id, service_service_id)
            SELECT :dt_id_new, host_host_id, service_service_id
            FROM downtime_service_relation WHERE dt_id = :dt_id'
        );
        $statement->bindValue(':dt_id_new', (int) $params['dt_id_new'], \PDO::PARAM_INT);
        $statement->bindValue(':dt_id', (int) $params['dt_id'], \PDO::PARAM_INT);
        $statement->execute();
    }

    /**
     * Creating service groups relations for the new downtime.
     *
     * @param array<string, string> $params
     */
    private function createDowntimeServiceGroupsRelations(array $params): void
    {
        $statement = $this->db->prepare(
            'INSERT INTO downtime_servicegroup_relation (dt_id, sg_sg_id)
            SELECT :dt_id_new, sg_sg_id FROM downtime_servicegroup_relation WHERE dt_id = :dt_id'
        );
        $statement->bindValue(':dt_id_new', (int) $params['dt_id_new'], \PDO::PARAM_INT);
        $statement->bindValue(':dt_id', (int) $params['dt_id'], \PDO::PARAM_INT);
        $statement->execute();
    }
}

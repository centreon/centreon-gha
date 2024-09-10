<?php

/*
 * Copyright 2005-2021 Centreon
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
 */

if (!isset($oreon)) {
    exit();
}

/**
 * Get the list of services id for a pool
 *
 * @param int $poolId The pool id
 * @return array
 */
function getListServiceForPool($poolId)
{
    global $pearDB;

    if (!is_int($poolId)) {
        return [];
    }

    /*
    * Get pool informations
    */
    $stmt = $pearDB->prepareQuery('SELECT pool_host_id, pool_prefix FROM mod_dsm_pool WHERE pool_id = :pool_id');
    $pearDB->executePreparedQuery($stmt, [':pool_id' => [$poolId, PDO::PARAM_INT]], true);
    $row = $pearDB->fetch($stmt);
    if (is_null($row['pool_host_id']) || $row['pool_host_id'] == '') {
        $pearDB->closeQuery($stmt);
        return [];
    }

    $poolPrefix = $row['pool_prefix'];

    $stmt = $pearDB->prepareQuery(
        'SELECT service_id, service_description
        FROM service s, host_service_relation hsr
        WHERE hsr.host_host_id = :host_id
            AND service_id = service_service_id
            AND service_description LIKE :pool_prefix'
    );

    $pearDB->executePreparedQuery($stmt, [
        ':host_id' => [$row['pool_host_id'], PDO::PARAM_INT],
        ':pool_prefix' => [$poolPrefix . '%', PDO::PARAM_STR]
    ], true);

    $listServices = [];
    while ($row = $pearDB->fetch($stmt)) {
        if (preg_match('/^' . preg_quote($poolPrefix, '/') . '(\d{4})$/', $row['service_description'])) {
            $listServices[] = $row['service_id'];
        }
    }
    $pearDB->closeQuery($stmt);
    return $listServices;
}

/**
 * Return if a host is already use in DSM
 *
 * @param int $hostId The host id
 * @param string $poolPrefix The pool prefix
 * @param int|null $poolId The pool id (optional)
 * @return bool
 */
function hostPoolPrefixUsed($hostId, $poolPrefix, $poolId = null)
{
    global $pearDB;

    $query = "SELECT COUNT(pool_id) AS nb FROM mod_dsm_pool WHERE pool_host_id = :host_id AND pool_prefix = :pool_prefix";
    $params = [
        ':host_id' => [$hostId, PDO::PARAM_INT],
        ':pool_prefix' => [$poolPrefix, PDO::PARAM_STR]
    ];

    if (!is_null($poolId)) {
        $query .= " AND pool_id != :pool_id";
        $params[':pool_id'] = [$poolId, PDO::PARAM_INT];
    }

    $stmt = $pearDB->prepareQuery($query);
    $pearDB->executePreparedQuery($stmt, $params, true);

    $row = $pearDB->fetch($stmt);
    $pearDB->closeQuery($stmt);

    return ($row['nb'] > 0);
}

/**
 * Enable a slot pool system
 *
 * @param int|null $pool_id The pool ID to enable (optional)
 * @param array $pool_arr An array of pool IDs to enable
 */
function enablePoolInDB($pool_id = null, $pool_arr = array())
{
    global $pearDB;

    if (!$pool_id && !count($pool_arr)) {
        return;
    }

    if ($pool_id) {
        $pool_arr = array($pool_id => "1");
    }

    /*
     * Update services in Centreon configuration
     */
    foreach ($pool_arr as $id => $values) {
        if (!is_int($id)) {
            continue;
        }

        $stmt = $pearDB->prepareQuery("UPDATE mod_dsm_pool SET pool_activate = '1' WHERE pool_id = :pool_id");
        $pearDB->executePreparedQuery($stmt, [':pool_id' => [$id, PDO::PARAM_INT]], true);
        $pearDB->closeQuery($stmt);

        $listServices = getListServiceForPool($id);
        if (count($listServices) > 0) {
            $placeholders = implode(', ', array_fill(0, count($listServices), '?'));
            $query = "UPDATE service SET service_activate = '1' WHERE service_id IN ($placeholders)";

            // Prepare and execute the query with service IDs
            $stmt = $pearDB->prepareQuery($query);
            $pearDB->executePreparedQuery($stmt, array_map(fn($serviceId) => [$serviceId, PDO::PARAM_INT], $listServices), false);
            $pearDB->closeQuery($stmt);
        }
    }
}

/**
 *
 * Disable a slot pool system
 *
 * @param int|null $pool_id The pool ID to disable (optional)
 * @param array $pool_arr An array of pool IDs to disable
 */
function disablePoolInDB($pool_id = null, $pool_arr = array())
{
    global $pearDB;

    if (!$pool_id && !count($pool_arr)) {
        return;
    }

    if ($pool_id) {
        $pool_arr = array($pool_id => "1");
    }

    foreach ($pool_arr as $id => $values) {
        if (!is_int($id)) {
            continue;
        }

        $stmt = $pearDB->prepareQuery("UPDATE mod_dsm_pool SET pool_activate = '0' WHERE pool_id = :pool_id");
        $pearDB->executePreparedQuery($stmt, [':pool_id' => [$id, PDO::PARAM_INT]], true);
        $pearDB->closeQuery($stmt);

        /*
         * Update services in Centreon configuration
         */
        $listServices = getListServiceForPool($id);
        if (count($listServices) > 0) {
            $placeholders = implode(', ', array_fill(0, count($listServices), '?'));
            $query = "UPDATE service SET service_activate = '0' WHERE service_id IN ($placeholders)";

            $stmt = $pearDB->prepareQuery($query);
            $pearDB->executePreparedQuery($stmt, array_map(fn($serviceId) => [$serviceId, PDO::PARAM_INT], $listServices), false);
            $pearDB->closeQuery($stmt);
        }
    }
}

/**
 * Delete a slot pool system
 * @param array $pools An array of pool IDs to delete
 */
function deletePoolInDB($pools = array())
{
    global $pearDB;

    foreach ($pools as $key => $value) {
        /*
         * Delete services in Centreon configuration
         */
        $listServices = getListServiceForPool($key);
        if (count($listServices) > 0) {
            $placeholders = implode(', ', array_fill(0, count($listServices), '?'));
            $query = "DELETE FROM service WHERE service_id IN ($placeholders)";

            $stmt = $pearDB->prepareQuery($query);
            $pearDB->executePreparedQuery($stmt, array_map(fn($serviceId) => [$serviceId, PDO::PARAM_INT], $listServices), false);
            $pearDB->closeQuery($stmt);
        }

        $stmt = $pearDB->prepareQuery("DELETE FROM mod_dsm_pool WHERE pool_id = :pool_id");
        $pearDB->executePreparedQuery($stmt, [':pool_id' => [$key, PDO::PARAM_INT]], true);
        $pearDB->closeQuery($stmt);
    }
}

/**
 *
 * Update a slot pool in DB
 *
 * @param int|null $pool_id The pool ID to update
 * @return bool
 */
function updatePoolInDB($pool_id = null)
{
    global $form;

    if (!$pool_id) {
        return false;
    }

    $ret = $form->getSubmitValues();

    /*
     * Global function to use
     */
    return updatePool($pool_id);
}

/**
 * Insert a slot pool in DB
 *
 * @param array $ret The values for new pool
 * @return int $pool_id The pool id, return -1 if error
 */
function insertPoolInDB($ret = array())
{
    $pool_id = insertPool($ret);
    return ($pool_id);
}

/**
 *
 * Check Pool Existence
 * @param string $pool_name The pool name to check
 * @return int 0 if the pool does not exist, 1 if it does
 */
function testPoolExistence($pool_name)
{
    global $pearDB;

    $stmt = $pearDB->prepareQuery("SELECT * FROM `mod_dsm_pool` WHERE `pool_name` = :pool_name");
    $pearDB->executePreparedQuery($stmt, [':pool_name' => [$pool_name, PDO::PARAM_STR]], true);

    $exists = $stmt->rowCount() == 0 ? 0 : 1;
    $pearDB->closeQuery($stmt);

    return $exists;
}

/**
 * Duplicate Pool
 *
 * @param array $pool An array of pool IDs to duplicate
 * @param array $nbrDup An array of the number of duplications for each pool
 */
function multiplePoolInDB($pool = array(), $nbrDup = array())
{
    global $pearDB;

    foreach ($pool as $key => $value) {
        $stmt = $pearDB->prepareQuery("SELECT * FROM `mod_dsm_pool` WHERE `pool_id` = :pool_id LIMIT 1");
        $pearDB->executePreparedQuery($stmt, [':pool_id' => [$key, PDO::PARAM_INT]], true);

        $row = $pearDB->fetch($stmt);
        $pearDB->closeQuery($stmt);

        $row["pool_id"] = null;

        for ($i = 1; $i <= $nbrDup[$key]; $i++) {
            $val = null;

            foreach ($row as $key2 => $value2) {
                $key2 == "pool_name" ? ($pool_name = $value2 = $value2 . "_" . $i) : null;
                if ($key2 == 'pool_host_id') {
                    $value2 = null;
                } elseif ($key2 == 'pool_activate') {
                    $value2 = '0';
                }
                $val ? $val .= (
                    $value2 != null ? (", '" . $pearDB->escapeString($value2) . "'") : ", NULL"
                ) : $val .= (
                    $value2 != null ? ("'" . $pearDB->escapeString($value2) . "'") : "NULL"
                );
                if ($key2 != "pool_id") {
                    $fields[$key2] = $pearDB->escapeString($value2);
                }
                if (isset($pool_name)) {
                    $fields["pool_name"] = $pool_name . "_$i";
                }
            }

            if (isset($pool_name) && !testPoolExistence($pool_name)) {
                if ($val) {
                    $stmt = $pearDB->prepareQuery("INSERT INTO `mod_dsm_pool` VALUES ($val)");
                    $pearDB->executePreparedQuery($stmt, [], false);
                    $pearDB->closeQuery($stmt);
                }

                $stmt = $pearDB->prepareQuery("SELECT MAX(pool_id) FROM `mod_dsm_pool`");
                $pearDB->executePreparedQuery($stmt, [], false);
                $cmd_id = $pearDB->fetch($stmt);
                $pearDB->closeQuery($stmt);
            }
        }
    }
}

/**
 *
 * Generate Slot services for a pool
 *
 * @param string $prefix The prefix for the service
 * @param int $number The number of services to generate
 * @param int $host_id The host ID for the services
 * @param int $template The template ID for the services
 * @param int|null $cmd The command ID (optional)
 * @param string|null $args The command arguments (optional)
 * @param string $oldPrefix The old prefix to be replaced
 */
function generateServices($prefix, $number, $host_id, $template, $cmd, $args, $oldPrefix)
{
    global $pearDB;

    if (!isset($oldPrefix)) {
        $oldPrefix = "213343434334343434343";
    }

    $stmt = $pearDB->prepareQuery(
        "SELECT service_id, service_description
        FROM service s, host_service_relation hsr
        WHERE hsr.host_host_id = :host_id
        AND service_id = service_service_id
        AND service_description LIKE :oldPrefix
        ORDER BY service_description ASC"
    );
    $pearDB->executePreparedQuery($stmt, [
        ':host_id' => [$host_id, PDO::PARAM_INT],
        ':oldPrefix' => [$oldPrefix . '%', PDO::PARAM_STR]
    ], true);

    $currentNumber = $stmt->rowCount();

    if ($currentNumber == 0) {
        for ($i = 1; $i <= $number; $i++) {
            $suffix = "";
            for ($t = $i; $t < 1000; $t *= 10) {
                $suffix .= "0";
            }
            $suffix .= $i;
            $stmtInsert = $pearDB->prepareQuery(
                "INSERT INTO service (
                    service_description,
                    service_template_model_stm_id,
                    command_command_id,
                    command_command_id_arg,
                    service_activate,
                    service_register,
                    service_active_checks_enabled,
                    service_passive_checks_enabled,
                    service_parallelize_check,
                    service_obsess_over_service,
                    service_check_freshness,
                    service_event_handler_enabled,
                    service_process_perf_data,
                    service_retain_status_information,
                    service_notifications_enabled,
                    service_is_volatile
                ) VALUES (
                    :service_description, :template, :cmd, :args, '1', '1', '0', '1', '2', '2', '2', '2', '2', '2', '2', '2'
                )"
            );
            $pearDB->executePreparedQuery($stmtInsert, [
                ':service_description' => [$prefix . $suffix, PDO::PARAM_STR],
                ':template' => [$template, PDO::PARAM_INT],
                ':cmd' => [$cmd ?: null, $cmd ? PDO::PARAM_INT : PDO::PARAM_NULL],
                ':args' => [$args ?: null, $args ? PDO::PARAM_STR : PDO::PARAM_NULL]
            ], true);
            $pearDB->closeQuery($stmtInsert);

            $stmtMax = $pearDB->prepareQuery(
                "SELECT MAX(service_id) FROM service
                WHERE service_description = :service_description
                AND service_activate = '1'
                AND service_register = '1'"
            );
            $pearDB->executePreparedQuery($stmtMax, [':service_description' => [$prefix . $suffix, PDO::PARAM_STR]], true);
            $service = $pearDB->fetch($stmtMax);
            $service_id = $service["MAX(service_id)"];
            $pearDB->closeQuery($stmtMax);

            if ($service_id != 0) {
                $stmtInsertHostRelation = $pearDB->prepareQuery(
                    "INSERT INTO host_service_relation (service_service_id, host_host_id) 
                    VALUES (:service_id, :host_id)"
                );
                $pearDB->executePreparedQuery($stmtInsertHostRelation, [
                    ':service_id' => [$service_id, PDO::PARAM_INT],
                    ':host_id' => [$host_id, PDO::PARAM_INT]
                ], true);
                $pearDB->closeQuery($stmtInsertHostRelation);

                $stmtInsertExtended = $pearDB->prepareQuery(
                    "INSERT INTO extended_service_information (service_service_id) VALUES (:service_id)"
                );
                $pearDB->executePreparedQuery($stmtInsertExtended, [':service_id' => [$service_id, PDO::PARAM_INT]], true);
                $pearDB->closeQuery($stmtInsertExtended);
            }
        }
    } elseif ($currentNumber <= $number) {
        for ($i = 1; $data = $pearDB->fetch($stmt); $i++) {
            $suffix = str_pad($i, 4, '0', STR_PAD_LEFT);
            $stmtUpdate = $pearDB->prepareQuery(
                "UPDATE service SET 
                service_template_model_stm_id = :template,
                service_description = :service_description,
                command_command_id = :cmd,
                command_command_id_arg = :args 
                WHERE service_id = :service_id"
            );
            $pearDB->executePreparedQuery($stmtUpdate, [
                ':template' => [$template, PDO::PARAM_INT],
                ':service_description' => [$prefix . $suffix, PDO::PARAM_STR],
                ':cmd' => [$cmd ?: null, $cmd ? PDO::PARAM_INT : PDO::PARAM_NULL],
                ':args' => [$args ?: null, $args ? PDO::PARAM_STR : PDO::PARAM_NULL],
                ':service_id' => [$data["service_id"], PDO::PARAM_INT]
            ], true);
            $pearDB->closeQuery($stmtUpdate);

            $stmtDeleteHostRelation = $pearDB->prepareQuery(
                "DELETE FROM host_service_relation WHERE service_service_id = :service_id"
            );
            $pearDB->executePreparedQuery($stmtDeleteHostRelation, [':service_id' => [$data["service_id"], PDO::PARAM_INT]], true);
            $pearDB->closeQuery($stmtDeleteHostRelation);

            $stmtInsertHostRelation = $pearDB->prepareQuery(
                "INSERT INTO host_service_relation (service_service_id, host_host_id) 
                VALUES (:service_id, :host_id)"
            );
            $pearDB->executePreparedQuery($stmtInsertHostRelation, [
                ':service_id' => [$data["service_id"], PDO::PARAM_INT],
                ':host_id' => [$host_id, PDO::PARAM_INT]
            ], true);
            $pearDB->closeQuery($stmtInsertHostRelation);
        }
        while ($i <= $number) {
            $suffix = "";
            for ($t = $i; $t < 1000; $t *= 10) {
                $suffix .= "0";
            }
            $suffix .= $i;
            $stmtInsert = $pearDB->prepareQuery(
                "INSERT INTO service (
                    service_description,
                    service_template_model_stm_id,
                    command_command_id,
                    command_command_id_arg,
                    service_activate,
                    service_register,
                    service_active_checks_enabled,
                    service_passive_checks_enabled,
                    service_parallelize_check,
                    service_obsess_over_service,
                    service_check_freshness,
                    service_event_handler_enabled,
                    service_process_perf_data,
                    service_retain_status_information,
                    service_notifications_enabled,
                    service_is_volatile
                ) VALUES (
                    :service_description, :template, :cmd, :args, '1', '1', '0', '1', '2', '2', '2', '2', '2', '2', '2', '2'
                )"
            );
            $pearDB->executePreparedQuery($stmtInsert, [
                ':service_description' => [$prefix . $suffix, PDO::PARAM_STR],
                ':template' => [$template, PDO::PARAM_INT],
                ':cmd' => [$cmd ?: null, $cmd ? PDO::PARAM_INT : PDO::PARAM_NULL],
                ':args' => [$args ?: null, $args ? PDO::PARAM_STR : PDO::PARAM_NULL]
            ], true);
            $pearDB->closeQuery($stmtInsert);

            $stmtMax = $pearDB->prepareQuery(
                "SELECT MAX(service_id) FROM service
                WHERE service_description = :service_description
                AND service_activate = '1'
                AND service_register = '1'"
            );
            $pearDB->executePreparedQuery($stmtMax, [':service_description' => [$prefix . $suffix, PDO::PARAM_STR]], true);
            $service = $pearDB->fetch($stmtMax);
            $service_id = $service["MAX(service_id)"];
            $pearDB->closeQuery($stmtMax);

            if ($service_id != 0) {
                $stmtInsertHostRelation = $pearDB->prepareQuery(
                    "INSERT INTO host_service_relation (service_service_id, host_host_id)
                    VALUES (:service_id, :host_id)"
                );
                $pearDB->executePreparedQuery($stmtInsertHostRelation, [
                    ':service_id' => [$service_id, PDO::PARAM_INT],
                    ':host_id' => [$host_id, PDO::PARAM_INT]
                ], true);
                $pearDB->closeQuery($stmtInsertHostRelation);

                $stmtInsertExtended = $pearDB->prepareQuery(
                    "INSERT INTO extended_service_information (service_service_id) VALUES (:service_id)"
                );
                $pearDB->executePreparedQuery($stmtInsertExtended, [':service_id' => [$service_id, PDO::PARAM_INT]], true);
                $pearDB->closeQuery($stmtInsertExtended);
            }
            $i++;
        }
    } elseif ($currentNumber > $number) {
        for ($i = 1; $data = $pearDB->fetch($stmt); $i++) {
            if ($i > $number) {
                $stmtDeleteService = $pearDB->prepareQuery("DELETE FROM service WHERE service_id = :service_id");
                $pearDB->executePreparedQuery($stmtDeleteService, [':service_id' => [$data["service_id"], PDO::PARAM_INT]], true);
                $pearDB->closeQuery($stmtDeleteService);
            }
        }
    }

    $pearDB->closeQuery($stmt);
}

/**
 * Insert Pool
 *
 * @param array $ret The values for new pool
 * @return int The pool ID, or -1 if an error occurs
 */
function insertPool($ret = array())
{
    global $form, $pearDB;

    if (!count($ret)) {
        $ret = $form->getSubmitValues();
    }

    if (hostPoolPrefixUsed($ret['pool_host_id'], $ret['pool_prefix'])) {
        throw new Exception(_('Hosts is already use that pool prefix'));
    }

    $stmt = $pearDB->prepareQuery(
        "INSERT INTO `mod_dsm_pool` (
            `pool_id`,
            `pool_name`,
            `pool_host_id`,
            `pool_description`,
            `pool_number`,
            `pool_prefix`,
            `pool_cmd_id`,
            `pool_args`,
            `pool_activate`,
            `pool_service_template_id`
        ) VALUES (
            NULL, :pool_name, :pool_host_id, :pool_description, :pool_number,
            :pool_prefix, :pool_cmd_id, :pool_args, :pool_activate, :pool_service_template_id
        )"
    );


    /*
    * Generate all services
    */
    generateServices(
        $ret["pool_prefix"],
        $ret["pool_number"],
        $ret["pool_host_id"],
        $ret["pool_service_template_id"],
        $ret["pool_cmd_id"],
        $ret["pool_args"],
        "kjqsddlqkjdqslkjdqsldkj"
    );

    $pearDB->executePreparedQuery($stmt, [
        ':pool_name' => [isset($ret["pool_name"]) ? $ret["pool_name"] : null, isset($ret["pool_name"]) ? PDO::PARAM_STR : PDO::PARAM_NULL],
        ':pool_host_id' => [isset($ret["pool_host_id"]) ? $ret["pool_host_id"] : null, isset($ret["pool_host_id"]) ? PDO::PARAM_INT : PDO::PARAM_NULL],
        ':pool_description' => [isset($ret["pool_description"]) ? $ret["pool_description"] : null, isset($ret["pool_description"]) ? PDO::PARAM_STR : PDO::PARAM_NULL],
        ':pool_number' => [isset($ret["pool_number"]) ? $ret["pool_number"] : null, isset($ret["pool_number"]) ? PDO::PARAM_INT : PDO::PARAM_NULL],
        ':pool_prefix' => [isset($ret["pool_prefix"]) ? $ret["pool_prefix"] : null, isset($ret["pool_prefix"]) ? PDO::PARAM_STR : PDO::PARAM_NULL],
        ':pool_cmd_id' => [isset($ret["pool_cmd_id"]) ? $ret["pool_cmd_id"] : null, isset($ret["pool_cmd_id"]) ? PDO::PARAM_INT : PDO::PARAM_NULL],
        ':pool_args' => [isset($ret["pool_args"]) ? $ret["pool_args"] : null, isset($ret["pool_args"]) ? PDO::PARAM_STR : PDO::PARAM_NULL],
        ':pool_activate' => [isset($ret["pool_activate"]["pool_activate"]) ? $ret["pool_activate"]["pool_activate"] : null, isset($ret["pool_activate"]["pool_activate"]) ? PDO::PARAM_INT : PDO::PARAM_NULL],
        ':pool_service_template_id' => [isset($ret["pool_service_template_id"]) ? $ret["pool_service_template_id"] : null, isset($ret["pool_service_template_id"]) ? PDO::PARAM_INT : PDO::PARAM_NULL]
    ], true);
    $pearDB->closeQuery($stmt);

    $stmtMax = $pearDB->prepareQuery("SELECT MAX(pool_id) FROM mod_dsm_pool");
    $pearDB->executePreparedQuery($stmtMax, [], false);
    $pool_id = $pearDB->fetch($stmtMax);
    $pearDB->closeQuery($stmtMax);

    if ($ret["pool_activate"]["pool_activate"] == 1) {
        enablePoolInDB($pool_id["MAX(pool_id)"]);
    } else {
        disablePoolInDB($pool_id["MAX(pool_id)"]);
    }

    return ($pool_id["MAX(pool_id)"]);
}

/**
 * Update Pool
 *
 * @param int|null $pool_id The pool ID
 * @return bool
 */
function updatePool($pool_id = null)
{
    global $form, $pearDB;

    if (!$pool_id) {
        return false;
    }

    /*
     * Get Old Prefix
     */
    $stmt = $pearDB->prepareQuery("SELECT pool_prefix FROM mod_dsm_pool WHERE pool_id = :pool_id");
    $pearDB->executePreparedQuery($stmt, [':pool_id' => [$pool_id, PDO::PARAM_INT]], true);
    $data = $pearDB->fetch($stmt);
    $oldPrefix = $data["pool_prefix"];
    $pearDB->closeQuery($stmt);

    $ret = $form->getSubmitValues();

    /*
     * Validate if host is not already use
     */
    if (isset($ret['pool_host_id'], $ret['pool_prefix']) && hostPoolPrefixUsed($ret['pool_host_id'], $ret['pool_prefix'], $pool_id)) {
        throw new Exception(_('Hosts is already use that pool prefix'));
    }

    $stmt = $pearDB->prepareQuery(
        "UPDATE mod_dsm_pool SET
            pool_name = :pool_name,
            pool_description = :pool_description,
            pool_host_id = :pool_host_id,
            pool_number = :pool_number,
            pool_prefix = :pool_prefix,
            pool_cmd_id = :pool_cmd_id,
            pool_args = :pool_args,
            pool_activate = :pool_activate,
            pool_service_template_id = :pool_service_template_id
        WHERE pool_id = :pool_id"
    );

    $pearDB->executePreparedQuery($stmt, [
        ':pool_name' => [isset($ret["pool_name"]) ? $ret["pool_name"] : null, isset($ret["pool_name"]) ? PDO::PARAM_STR : PDO::PARAM_NULL],
        ':pool_description' => [isset($ret["pool_description"]) ? $ret["pool_description"] : null, isset($ret["pool_description"]) ? PDO::PARAM_STR : PDO::PARAM_NULL],
        ':pool_host_id' => [isset($ret["pool_host_id"]) ? $ret["pool_host_id"] : null, isset($ret["pool_host_id"]) ? PDO::PARAM_INT : PDO::PARAM_NULL],
        ':pool_number' => [isset($ret["pool_number"]) ? $ret["pool_number"] : null, isset($ret["pool_number"]) ? PDO::PARAM_INT : PDO::PARAM_NULL],
        ':pool_prefix' => [isset($ret["pool_prefix"]) ? $ret["pool_prefix"] : null, isset($ret["pool_prefix"]) ? PDO::PARAM_STR : PDO::PARAM_NULL],
        ':pool_cmd_id' => [isset($ret["pool_cmd_id"]) ? $ret["pool_cmd_id"] : null, isset($ret["pool_cmd_id"]) ? PDO::PARAM_INT : PDO::PARAM_NULL],
        ':pool_args' => [isset($ret["pool_args"]) ? $ret["pool_args"] : null, isset($ret["pool_args"]) ? PDO::PARAM_STR : PDO::PARAM_NULL],
        ':pool_activate' => [isset($ret["pool_activate"]["pool_activate"]) ? $ret["pool_activate"]["pool_activate"] : null, isset($ret["pool_activate"]["pool_activate"]) ? PDO::PARAM_INT : PDO::PARAM_NULL],
        ':pool_service_template_id' => [isset($ret["pool_service_template_id"]) ? $ret["pool_service_template_id"] : null, isset($ret["pool_service_template_id"]) ? PDO::PARAM_INT : PDO::PARAM_NULL],
        ':pool_id' => [$pool_id, PDO::PARAM_INT]
    ], true);
    $pearDB->closeQuery($stmt);

    generateServices(
        $ret["pool_prefix"],
        $ret["pool_number"],
        $ret["pool_host_id"],
        $ret["pool_service_template_id"],
        $ret["pool_cmd_id"],
        $ret["pool_args"],
        $oldPrefix
    );

    if (isset($ret["pool_activate"]["pool_activate"]) && $ret["pool_activate"]["pool_activate"] == 1) {
        enablePoolInDB($pool_id);
    } else {
        disablePoolInDB($pool_id);
    }

    return true;
}

/**
 *
 * Update Pool ContactGroups
 * @param array $ret
 */
function updatePoolContactGroup($pool_id = null, $ret = array())
{
    global $form, $pearDB;

    if (!$pool_id) {
        return;
    }

    $stmt = $pearDB->prepareQuery("DELETE FROM mod_dsm_cg_relation WHERE pool_id = :pool_id");
    $pearDB->executePreparedQuery($stmt, [':pool_id' => [$pool_id, PDO::PARAM_INT]], true);
    $pearDB->closeQuery($stmt);

    $ret = isset($ret["pool_cg"]) ? $ret["pool_cg"] : $form->getSubmitValue("pool_cg");

    foreach ($ret as $cg_id) {
        $stmt = $pearDB->prepareQuery(
            "INSERT INTO mod_dsm_cg_relation (pool_id, cg_cg_id) VALUES (:pool_id, :cg_cg_id)"
        );
        $pearDB->executePreparedQuery($stmt, [
            ':pool_id' => [$pool_id, PDO::PARAM_INT],
            ':cg_cg_id' => [$cg_id, PDO::PARAM_INT]
        ], true);
        $pearDB->closeQuery($stmt);
    }
}

/**
 * Update Pool Contacts
 * @param int|null $pool_id The pool ID to update
 * @param array $ret The contacts to update
 */
function updatePoolContact($pool_id = null, $ret = array())
{
    global $form, $pearDB;

    if (!$pool_id) {
        return;
    }

    $stmt = $pearDB->prepareQuery("DELETE FROM mod_dsm_cct_relation WHERE pool_id = :pool_id");
    $pearDB->executePreparedQuery($stmt, [':pool_id' => [$pool_id, PDO::PARAM_INT]], true);
    $pearDB->closeQuery($stmt);

    $ret = isset($ret["pool_cct"]) ? $ret["pool_cct"] : $form->getSubmitValue("pool_cct");

    foreach ($ret as $cct_id) {
        $stmt = $pearDB->prepareQuery(
            "INSERT INTO mod_dsm_cct_relation (pool_id, cct_cct_id) VALUES (:pool_id, :cct_cct_id)"
        );
        $pearDB->executePreparedQuery($stmt, [
            ':pool_id' => [$pool_id, PDO::PARAM_INT],
            ':cct_cct_id' => [$cct_id, PDO::PARAM_INT]
        ], true);
        $pearDB->closeQuery($stmt);
    }
}

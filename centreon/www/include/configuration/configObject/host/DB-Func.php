<?php

/*
 * Copyright 2005-2020 Centreon
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

if (!isset($centreon)) {
    exit();
}

require_once _CENTREON_PATH_ . 'www/class/centreonLDAP.class.php';
require_once _CENTREON_PATH_ . 'www/class/centreonContactgroup.class.php';
require_once _CENTREON_PATH_ . 'www/class/centreonACL.class.php';
require_once _CENTREON_PATH_ . 'www/include/common/vault-functions.php';

use App\Kernel;
use Centreon\Domain\Log\Logger;
use Core\Common\Application\Repository\ReadVaultRepositoryInterface;
use Core\Common\Application\Repository\WriteVaultRepositoryInterface;
use Core\Infrastructure\Common\Api\Router;
use Core\Common\Infrastructure\Repository\AbstractVaultRepository;
use Core\Host\Application\Converter\HostEventConverter;
use Core\Security\Vault\Domain\Model\VaultConfiguration;
use Core\Security\Vault\Application\Repository\ReadVaultConfigurationRepositoryInterface;
use Symfony\Component\HttpClient\CurlHttpClient;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Quickform rule that checks whether or not monitoring server can be set
 *
 * @global CentreonDB $pearDB
 * @global HTML_QuickFormCustom $form
 * @param int $instanceId
 * @return boolean
 */
function testPollerDep($instanceId)
{
    global $pearDB, $form;

    $hostId = $form->getSubmitValue('host_id');
    $hostParents = filter_var_array(
        $form->getSubmitValue('host_parents'),
        FILTER_VALIDATE_INT
    );

    if (!$hostId || is_null($hostParents)) {
        return true;
    }

    $request = "SELECT COUNT(*) as total "
        . "FROM host_hostparent_relation hhr, ns_host_relation nhr "
        . "WHERE hhr.host_parent_hp_id = nhr.host_host_id "
        . "AND hhr.host_host_id = :host_id "
        . "AND nhr.nagios_server_id != :server_id";

    $fieldsToBind = [];
    if (!in_array(false, $hostParents)) {
        for ($index = 0; $index < count($hostParents); $index++) {
            $fieldsToBind[':parent_' . $index] = $hostParents[$index];
        }
        $request .= " AND host_parent_hp_id IN (" .
            implode(',', array_keys($fieldsToBind)) . ")";
    }

    $prepare = $pearDB->prepare($request);
    $prepare->bindValue(':host_id', $hostId, \PDO::PARAM_INT);
    $prepare->bindValue(':server_id', $instanceId, \PDO::PARAM_INT);

    foreach ($fieldsToBind as $field => $hostParentId) {
        $prepare->bindValue($field, $hostParentId, \PDO::PARAM_INT);
    }

    if ($prepare->execute()) {
        $result = $prepare->fetch(\PDO::FETCH_ASSOC);
        return ((int) $result['total']) == 0;
    }

    return true;
}

/**
 * Quickform rule that checks whether or not reserved macro are used
 *
 * @global CentreonDB $pearDB
 * @return boolean
 */
function hostMacHandler()
{
    global $pearDB;

    if (!isset($_REQUEST['macroInput'])) {
        return true;
    }

    $fieldsToBind = [];
    for ($index = 0; $index < count($_POST['macroInput']); $index++) {
        $fieldsToBind[':macro_' . $index] =
            "'\$_HOST" . strtoupper($_POST['macroInput'][$index]) . "\$'";
    }

    $request =
        "SELECT count(*) as total FROM nagios_macro WHERE macro_name IN (" .
        implode(',', array_keys($fieldsToBind)) . ")";

    $prepare = $pearDB->prepare($request);
    foreach ($fieldsToBind as $field => $macroName) {
        $prepare->bindValue($field, $macroName, \PDO::PARAM_STR);
    }

    if ($prepare->execute()) {
        $result = $prepare->fetch(\PDO::FETCH_ASSOC);
        return ((int) $result['total']) == 0;
    }
    return true;
}

/**
 * Indicates if the host name has already been used
 *
 * @global CentreonDB $pearDB
 * @global HTML_QuickFormCustom $form
 * @global Centreon $centreon
 * @param string $name Name to check
 * @return boolean Return false if the host name has already been used
 */
function hasHostNameNeverUsed($name = null)
{
    global $pearDB, $form, $centreon;

    $id = null;
    if (isset($form)) {
        $id = (int) $form->getSubmitValue('host_id');
    }

    $prepare = $pearDB->prepare(
        "SELECT host_name, host_id FROM host "
        . "WHERE host_name = :host_name AND host_register = '1'"
    );
    $hostName = CentreonDB::escape($centreon->checkIllegalChar($name));

    $prepare->bindValue(':host_name', $hostName, \PDO::PARAM_STR);
    $prepare->execute();
    $result = $prepare->fetch(\PDO::FETCH_ASSOC);
    $totals = $prepare->rowCount();

    if ($totals >= 1 && ($result["host_id"] == $id)) {
        /**
         * In case of modification
         */
        return true;
    } elseif ($totals >= 1 && ($result["host_id"] != $id)) {
        return false;
    } else {
        return true;
    }
}

function testHostName($name = null)
{
    if (preg_match("/^_Module_/", $name)) {
        return false;
    }
    return true;
}

/**
 * Indicates if the host template has already been used
 *
 * @global CentreonDB $pearDB
 * @global HTML_QuickFormCustom $form
 * @param string $name Name to check
 * @return boolean Return false if the host template has already been used
 */
function hasHostTemplateNeverUsed($name = null)
{
    global $pearDB, $form;

    $id = null;
    if (isset($form)) {
        $id = (int) $form->getSubmitValue('host_id');
    }

    $prepare = $pearDB->prepare(
        "SELECT host_name, host_id FROM host "
        . "WHERE host_name = :host_name AND host_register = '0'"
    );
    $prepare->bindValue(':host_name', $name, \PDO::PARAM_STR);
    $prepare->execute();
    $total = $prepare->rowCount();
    $result = $prepare->fetch(\PDO::FETCH_ASSOC);

    if ($total >= 1 && $result["host_id"] == $id) {
        /**
         * In case of modification
         */
        return true;
    } elseif ($total >= 1 && $result["host_id"] != $id) {
        /**
         * In case of duplicate
         */
        return false;
    } else {
        return true;
    }
}

/**
 * Checks if the insertion can be made
 *
 * @return bool
 */
function hasNoInfiniteLoop($hostId, $templateId)
{
    global $pearDB;
    static $antiTplLoop = array();

    if ($hostId === $templateId) {
        return false;
    }

    if (!count($antiTplLoop)) {
        $query = "SELECT * FROM host_template_relation";
        $res = $pearDB->query($query);
        while ($row = $res->fetch()) {
            if (!isset($antiTplLoop[$row['host_tpl_id']])) {
                $antiTplLoop[$row['host_tpl_id']] = array();
            }
            $antiTplLoop[$row['host_tpl_id']][$row['host_host_id']] = $row['host_host_id'];
        }
    }

    if (isset($antiTplLoop[$hostId])) {
        foreach ($antiTplLoop[$hostId] as $hId) {
            if ($hId == $templateId) {
                return false;
            }
            if (false === hasNoInfiniteLoop($hId, $templateId)) {
                return false;
            }
        }
    }
    return true;
}

function enableHostInDB($host_id = null, $host_arr = array())
{
    global $pearDB, $centreon;

    if (!$host_id && !count($host_arr)) {
        return;
    }

    if ($host_id) {
        $host_arr = [$host_id => "1"];
    }
    $updateStatement = $pearDB->prepare("UPDATE host SET host_activate = '1' WHERE host_id = :hostId");
    $selectStatement = $pearDB->prepare("SELECT host_name FROM `host` WHERE host_id = :hostId");
    foreach (array_keys($host_arr) as $hostId) {
        $updateStatement->bindValue(':hostId', $hostId, \PDO::PARAM_INT);
        $updateStatement->execute();

        $selectStatement->bindValue(':hostId', $hostId, \PDO::PARAM_INT);
        $selectStatement->execute();
        $hostName = $selectStatement->fetchColumn();

        signalConfigurationChange('host', (int) $hostId);
        $centreon->CentreonLogAction->insertLog("host", $hostId, $hostName, "enable");
    }
}

function disableHostInDB($host_id = null, $host_arr = array())
{
    global $pearDB, $centreon;
    if (!$host_id && !count($host_arr)) {
        return;
    }

    if ($host_id) {
        $host_arr = [$host_id => "1"];
    }
    $updateStatement = $pearDB->prepare("UPDATE host SET host_activate = '0' WHERE host_id = :hostId");
    $selectStatement = $pearDB->prepare("SELECT host_name FROM `host` WHERE host_id = :hostId");
    foreach (array_keys($host_arr) as $hostId) {
        $updateStatement->bindValue(':hostId', $hostId, \PDO::PARAM_INT);
        $updateStatement->execute();

        $selectStatement->bindValue(':hostId', $hostId, \PDO::PARAM_INT);
        $selectStatement->execute();
        $hostName = $selectStatement->fetchColumn();

        signalConfigurationChange('host', (int) $hostId, [], false);
        $centreon->CentreonLogAction->insertLog("host", $hostId, $hostName, "disable");
    }
}

/**
 * @param int $hostId
 */
function removeRelationLastHostDependency(int $hostId): void
{
    global $pearDB;

    $query = 'SELECT service_service_id FROM host_service_relation WHERE host_host_id =  ' . $hostId;
    $res = $pearDB->query($query);

    $query = 'SELECT count(dependency_dep_id) AS nb_dependency , dependency_dep_id AS id
        FROM dependency_serviceParent_relation
        WHERE dependency_dep_id = (SELECT dependency_dep_id FROM dependency_serviceParent_relation
        WHERE service_service_id =  :service_service_id) GROUP BY dependency_dep_id';

    $countStatement = $pearDB->prepare($query);
    $deleteStatement = $pearDB->prepare("DELETE FROM dependency WHERE dep_id = :dep_id");
    while ($row = $res->fetch()) {
        $countStatement->bindValue(':service_service_id', (int) $row['service_service_id'], \PDO::PARAM_INT);
        $countStatement->execute();
        if (false !== ($result = $countStatement->fetch(\PDO::FETCH_ASSOC))) {;
            //is last service parent
            if ($result['nb_dependency'] == 1) {
                $deleteStatement->bindValue(':dep_id', (int) $result['id'], \PDO::PARAM_INT);
                $deleteStatement->execute();
            }
        }
    }

    $query = 'SELECT count(dependency_dep_id) AS nb_dependency , dependency_dep_id AS id
              FROM dependency_hostParent_relation
              WHERE dependency_dep_id = (SELECT dependency_dep_id FROM dependency_hostParent_relation
                                         WHERE host_host_id =  ' . $hostId . ') GROUP BY dependency_dep_id';
    $dbResult = $pearDB->query($query);

    if (false !== ($result = $dbResult->fetch())) {
        //is last parent
        if ($result['nb_dependency'] == 1) {
            $pearDB->query("DELETE FROM dependency WHERE dep_id = " . $result['id']);
        }
    };
}

function deleteHostInDB($hosts = array())
{
    global $pearDB, $centreon;

    $hostIds = array_keys($hosts);
    $kernel = Kernel::createForWeb();
    $readVaultConfigurationRepository = $kernel->getContainer()->get(
        ReadVaultConfigurationRepositoryInterface::class
    );
    $vaultConfiguration = $readVaultConfigurationRepository->find();
    if ($vaultConfiguration !== null) {
        /** @var WriteVaultRepositoryInterface $writeVaultRepository */
        $writeVaultRepository = $kernel->getContainer()->get(WriteVaultRepositoryInterface::class);
        deleteResourceSecretsInVault($writeVaultRepository, $hostIds, []);
    }
    foreach ($hostIds as $hostId) {
        $previousPollerIds = findPollersForConfigChangeFlagFromHostIds([$hostId]);

        removeRelationLastHostDependency((int) $hostId);
        $rq = "SELECT @nbr := (SELECT COUNT( * )
                            FROM host_service_relation
                            WHERE service_service_id = hsr.service_service_id
                            GROUP BY service_service_id)
                            AS nbr, hsr.service_service_id
                            FROM host_service_relation hsr, host
                            WHERE hsr.host_host_id = '" . (int) $hostId . "'
                            AND host.host_id = hsr.host_host_id
                            AND host.host_register = '1'";
        $dbResult = $pearDB->query($rq);

        $dbResult3 = $pearDB->query("SELECT host_name FROM `host` WHERE `host_id` = '" . (int) $hostId . "' LIMIT 1");
        $hostname = $dbResult3->fetch();

        while ($row = $dbResult->fetch()) {
            if ($row["nbr"] == 1) {
                $dbResult4 = $pearDB->query("SELECT service_description
                                            FROM `service`
                                            WHERE `service_id` = '" . $row["service_service_id"] . "' LIMIT 1");
                $svcname = $dbResult4->fetch();

                $dbResult2 = $pearDB->query("DELETE FROM service
                                              WHERE service_id = '" . $row["service_service_id"] . "'");
                $centreon->CentreonLogAction->insertLog(
                    "service",
                    $row["service_service_id"],
                    $hostname['host_name'] . "/" . $svcname["service_description"],
                    "d"
                );
            }
        }
        $centreon->user->access->updateACL(array("type" => 'HOST', 'id' => $hostId, "action" => "DELETE"));
        $dbResult = $pearDB->query("DELETE FROM host WHERE host_id = '" . (int) $hostId . "'");
        $dbResult = $pearDB->query("DELETE FROM host_template_relation WHERE host_host_id = '" . (int) $hostId . "'");
        $dbResult = $pearDB->query("DELETE FROM on_demand_macro_host WHERE host_host_id = '" . (int) $hostId . "'");
        $dbResult = $pearDB->query("DELETE FROM contact_host_relation WHERE host_host_id = '" . (int) $hostId . "'");

        signalConfigurationChange('host', (int) $hostId, $previousPollerIds);
        $centreon->CentreonLogAction->insertLog("host", $hostId, $hostname['host_name'], "d");
    }
}

/*
 *  This function is called for duplicating a host
 */

function multipleHostInDB($hosts = array(), $nbrDup = array())
{
    global $pearDB, $path, $centreon, $is_admin;

    $hostAcl = [];
    $kernel = Kernel::createForWeb();
    /** @var Logger $logger */
    $logger = $kernel->getContainer()->get(Logger::class);
    $readVaultConfigurationRepository = $kernel->getContainer()->get(
        ReadVaultConfigurationRepositoryInterface::class
    );
    $vaultConfiguration = $readVaultConfigurationRepository->find();
    foreach ($hosts as $key => $value) {
        $dbResult = $pearDB->query("SELECT * FROM host WHERE host_id = '" . (int)$key . "' LIMIT 1");
        $row = $dbResult->fetch();
        $row["host_id"] = null;
        for ($i = 1; $i <= $nbrDup[$key]; $i++) {
            $val = null;
            foreach ($row as $key2 => $value2) {
                $value2 = is_int($value2) ? (string) $value2 : $value2;
                $key2 == "host_name" ? ($hostName = $value2 = $value2 . "_" . $i) : null;
                $val
                    ? $val .= ($value2 != null ? (", '" . CentreonDB::escape($value2) . "'") : ", NULL")
                    : $val .= ($value2 != null ? ("'" . CentreonDB::escape($value2) . "'") : "NULL");
                if ($key2 != "host_id") {
                    $fields[$key2] = $value2;
                }
                if (isset($hostName)) {
                    $fields["host_name"] = $hostName;
                }
            }
            if (hasHostNameNeverUsed($hostName)) {
                $val ? $rq = "INSERT INTO host VALUES (" . $val . ")" : $rq = null;
                $dbResult = $pearDB->query($rq);
                $dbResult = $pearDB->query("SELECT MAX(host_id) FROM host");
                $maxId = $dbResult->fetch();
                if (isset($maxId["MAX(host_id)"])) {
                    $hostAcl[$maxId['MAX(host_id)']] = $key;

                    $dbResult = $pearDB->query("SELECT DISTINCT host_parent_hp_id
                                                FROM host_hostparent_relation
                                                WHERE host_host_id = '" . (int)$key . "'");
                    $fields["host_parents"] = "";
                    $statement = $pearDB->prepare(
                        "INSERT INTO host_hostparent_relation
                              VALUES (:host_parent_hp_id, :host_host_id)"
                    );
                    while ($host = $dbResult->fetch()) {
                        $statement->bindValue(':host_parent_hp_id', (int) $host["host_parent_hp_id"], \PDO::PARAM_INT);
                        $statement->bindValue(':host_host_id', (int) $maxId["MAX(host_id)"], \PDO::PARAM_INT);
                        $statement->execute();
                        $fields["host_parents"] .= $host["host_parent_hp_id"] . ",";
                    }
                    $fields["host_parents"] = trim($fields["host_parents"], ",");

                    $res = $pearDB->query("SELECT DISTINCT host_host_id
                                          FROM host_hostparent_relation
                                          WHERE host_parent_hp_id = '" . (int)$key . "'");
                    $fields["host_childs"] = "";
                    $statement = $pearDB->prepare(
                        "INSERT INTO host_hostparent_relation (host_parent_hp_id, host_host_id)
                         VALUES (:host_parent_hp_id, :host_host_id)"
                    );
                    while ($host = $res->fetch()) {
                        $statement->bindValue(':host_parent_hp_id', (int) $maxId["MAX(host_id)"], \PDO::PARAM_INT);
                        $statement->bindValue(':host_host_id', (int) $host['host_host_id'], \PDO::PARAM_INT);
                        $statement->execute();
                        $fields["host_childs"] .= $host['host_host_id'] . ",";
                    }
                    $fields['host_childs'] = trim($fields['host_childs'], ",");

                    // We need to duplicate the entire Service and not only create a new relation for it in the DB
                    // /Need Service functions
                    if (file_exists($path . "../service/DB-Func.php")) {
                        require_once($path . "../service/DB-Func.php");
                    } elseif (file_exists($path . "../service/DB-Func.php")) {
                        require_once($path . "../configObject/service/DB-Func.php");
                    }
                    $hostInf = $maxId["MAX(host_id)"];
                    $serviceArr = array();
                    $serviceNbr = array();
                    // Get all Services link to the Host
                    $dbResult = $pearDB->query("SELECT DISTINCT service_service_id
                                              FROM host_service_relation
                                              WHERE host_host_id = '" . (int)$key . "'");
                    $countStatement = $pearDB->prepare(
                        "SELECT COUNT(*)
                        FROM host_service_relation
                        WHERE service_service_id = :service_service_id"
                    );
                    $insertStatement = $pearDB->prepare(
                        "INSERT INTO host_service_relation
                        VALUES (NULL, NULL, :host_id, NULL, :service_service_id)"
                    );
                    while ($service = $dbResult->fetch()) {
                        // If the Service is link with several Host, we keep this property and don't duplicate it,
                        // just create a new relation with the new Host
                        $countStatement->bindValue(
                            ':service_service_id',
                            (int) $service["service_service_id"],
                            \PDO::PARAM_INT
                        );
                        $countStatement->execute();
                        $mulHostSv = $countStatement->fetch(\PDO::FETCH_ASSOC);
                        if ($mulHostSv["COUNT(*)"] > 1) {
                            $insertStatement->bindValue(':host_id', (int) $maxId["MAX(host_id)"], \PDO::PARAM_INT);
                            $insertStatement->bindValue(
                                ':service_service_id',
                                (int) $service["service_service_id"],
                                \PDO::PARAM_INT
                            );
                            $insertStatement->execute();
                        } else {
                            $serviceArr[$service["service_service_id"]] = $service["service_service_id"];
                            $serviceNbr[$service["service_service_id"]] = 1;
                        }
                    }
                    // Register Host -> Duplicate the Service list
                    if ($row["host_register"] == 1) {
                        multipleServiceInDB($serviceArr, $serviceNbr, $hostInf, 0);
                    } else {
                        // Host Template -> Link to the existing Service Template List
                        $dbResult = $pearDB->query("SELECT DISTINCT service_service_id
                                                    FROM host_service_relation
                                                    WHERE host_host_id = '" . (int)$key . "'");
                        $statement = $pearDB->prepare(
                            "INSERT INTO host_service_relation
                             VALUES (NULL, NULL, :host_id, NULL, :service_service_id)"
                        );
                        while ($svs = $dbResult->fetch()) {
                            $statement->bindValue(':host_id', (int) $maxId["MAX(host_id)"], \PDO::PARAM_INT);
                            $statement->bindValue(
                                ':service_service_id',
                                (int) $svs["service_service_id"],
                                \PDO::PARAM_INT
                            );
                            $statement->execute();
                        }
                    }

                    /*
                     * ContactGroup duplication
                     */
                    $dbResult = $pearDB->query("SELECT DISTINCT contactgroup_cg_id
                                                FROM contactgroup_host_relation
                                                WHERE host_host_id = '" . (int)$key . "'");
                    $fields["host_cgs"] = "";
                    $statement = $pearDB->prepare(
                        "INSERT INTO contactgroup_host_relation
                         VALUES (:host_id, :contactgroup_cg_id)"
                    );
                    while ($cg = $dbResult->fetch()) {
                        $statement->bindValue(':host_id', (int) $maxId["MAX(host_id)"], \PDO::PARAM_INT);
                        $statement->bindValue(':contactgroup_cg_id', (int) $cg["contactgroup_cg_id"], \PDO::PARAM_INT);
                        $statement->execute();
                        $fields["host_cgs"] .= $cg["contactgroup_cg_id"] . ",";
                    }
                    $fields["host_cgs"] = trim($fields["host_cgs"], ",");

                    /*
                     * Contact duplication
                     */
                    $dbResult = $pearDB->query("SELECT DISTINCT contact_id
                                                FROM contact_host_relation
                                                WHERE host_host_id = '" . (int)$key . "'");
                    $fields["host_cs"] = "";
                    $statement = $pearDB->prepare(
                        "INSERT INTO contact_host_relation
                         VALUES (:host_id, :contact_id)"
                    );
                    while ($c = $dbResult->fetch()) {
                        $statement->bindValue(':host_id', (int) $maxId["MAX(host_id)"], \PDO::PARAM_INT);
                        $statement->bindValue(':contact_id', (int) $c["contact_id"], \PDO::PARAM_INT);
                        $statement->execute();
                        $fields["host_cs"] .= $c["contact_id"] . ",";
                    }
                    $fields["host_cs"] = trim($fields["host_cs"], ",");

                    /*
                     * Hostgroup duplication
                     */
                    $dbResult = $pearDB->query("SELECT DISTINCT hostgroup_hg_id
                                                FROM hostgroup_relation
                                                WHERE host_host_id = '" . (int)$key . "'");
                    $statement = $pearDB->prepare(
                        "INSERT INTO hostgroup_relation
                         VALUES (NULL, :hostgroup_hg_id, :host_id)"
                    );
                    while ($hg = $dbResult->fetch()) {
                        $statement->bindValue(':hostgroup_hg_id', (int) $hg["hostgroup_hg_id"], \PDO::PARAM_INT);
                        $statement->bindValue(':host_id', (int) $maxId["MAX(host_id)"], \PDO::PARAM_INT);
                        $statement->execute();
                    }

                    /*
                     * Host Extended Informations
                     */
                    $dbResult = $pearDB->query("SELECT *
                                                FROM extended_host_information
                                                WHERE host_host_id = '" . (int)$key . "'");
                    while ($ehi = $dbResult->fetch()) {
                        $val = null;
                        $ehi["host_host_id"] = $maxId["MAX(host_id)"];
                        $ehi["ehi_id"] = null;
                        foreach ($ehi as $key2 => $value2) {
                            $value2 = is_int($value2) ? (string) $value2 : $value2;
                            $val
                                ? $val .= ($value2 != null ? (", '" . CentreonDB::escape($value2) . "'") : ", NULL")
                                : $val .= ($value2 != null ? ("'" . CentreonDB::escape($value2) . "'") : "NULL");
                            if ($key2 != "ehi_id") {
                                $fields[$key2] = $value2;
                            }
                        }
                        $val
                            ? $rq = "INSERT INTO extended_host_information VALUES (" . $val . ")"
                            : $rq = null;
                        $dbResult2 = $pearDB->query($rq);
                    }

                    /*
                     * Poller link ducplication
                     */
                    $dbResult = $pearDB->query("SELECT DISTINCT nagios_server_id
                                                FROM ns_host_relation
                                                WHERE host_host_id = '" . (int)$key . "'");
                    $fields["nagios_server_id"] = "";
                    $statement = $pearDB->prepare(
                        "INSERT INTO ns_host_relation
                         VALUES (:nagios_server_id, :host_id)"
                    );
                    while ($hg = $dbResult->fetch()) {
                        $statement->bindValue(':nagios_server_id', (int) $hg["nagios_server_id"], \PDO::PARAM_INT);
                        $statement->bindValue(':host_id', (int) $maxId["MAX(host_id)"], \PDO::PARAM_INT);
                        $statement->execute();
                        $fields["nagios_server_id"] .= $hg["nagios_server_id"] . ",";
                    }
                    $fields["nagios_server_id"] = trim($fields["nagios_server_id"], ",");

                    /*
                     *  multiple templates & on demand macros
                     */
                    $mTpRq1 = "SELECT *
                              FROM `host_template_relation`
                              WHERE `host_host_id` ='" . (int)$key . "'
                              ORDER BY `order`";
                    $dbResult3 = $pearDB->query($mTpRq1);
                    $multiTP_logStr = "";
                    $mTpRq2 = "INSERT INTO `host_template_relation` (`host_host_id`, `host_tpl_id`, `order`)
                               VALUES (:host_host_id, :host_tpl_id, :order)";
                    $statement = $pearDB->prepare($mTpRq2);
                    while ($hst = $dbResult3->fetch()) {
                        if ($hst['host_tpl_id'] != $maxId["MAX(host_id)"]) {
                            $statement->bindValue(':host_host_id', (int) $maxId["MAX(host_id)"], \PDO::PARAM_INT);
                            $statement->bindValue(':host_tpl_id', (int) $hst['host_tpl_id'], \PDO::PARAM_INT);
                            $statement->bindValue(':order', (int) $hst['order'], \PDO::PARAM_INT);
                            $statement->execute();
                            $multiTP_logStr .= $hst['host_tpl_id'] . ",";
                        }
                    }
                    $multiTP_logStr = trim($multiTP_logStr, ",");
                    $fields["templates"] = $multiTP_logStr;

                    /*
                     * on demand macros
                     */
                    $mTpRq1 = "SELECT * FROM `on_demand_macro_host` WHERE `host_host_id` ='" . (int)$key . "'";
                    $dbResult3 = $pearDB->query($mTpRq1);
                    $mTpRq2 = "INSERT INTO `on_demand_macro_host`
                                  (`host_host_id`, `host_macro_name`, `host_macro_value`,
                                   `is_password`)
                                   VALUES (:host_host_id, :host_macro_name, :host_macro_value,
                                           :is_password)";
                    $statement = $pearDB->prepare($mTpRq2);
                    $macroPasswords = [];
                    while ($hst = $dbResult3->fetch()) {
                        $macName = str_replace("\$", "", $hst["host_macro_name"]);
                        $macVal = $hst['host_macro_value'];
                        if (!isset($hst['is_password'])) {
                            $hst['is_password'] = '0';
                        }
                        $statement->bindValue(':host_host_id', (int) $maxId["MAX(host_id)"], \PDO::PARAM_INT);
                        $statement->bindValue(':host_macro_name', '$' . $macName . '$', \PDO::PARAM_STR);
                        $statement->bindValue(':host_macro_value', $macVal, \PDO::PARAM_STR);
                        $statement->bindValue(':is_password', (int) $hst["is_password"], \PDO::PARAM_INT);
                        $statement->execute();
                        $fields["_" . strtoupper($macName) . "_"] = $macVal;
                        if ($hst['is_password'] === 1) {
                            $maxIdStatement = $pearDB->query(
                                "SELECT MAX(host_macro_id) from on_demand_macro_host WHERE is_password = 1"
                            );
                            $resultMacro = $maxIdStatement->fetch();
                            $macroPasswords[$resultMacro['MAX(host_macro_id)']] = [
                                'macroName' => $macName,
                                'macroValue' => $macVal
                            ];
                        }
                    }

                    /*
                     * Host Categorie Duplication
                     */
                    $request = "INSERT INTO hostcategories_relation
                                SELECT hostcategories_hc_id, :max_host_id
                                FROM hostcategories_relation
                                WHERE host_host_id = :host_id";
                    $statement = $pearDB->prepare($request);
                    $statement->bindValue(':max_host_id', (int) $maxId["MAX(host_id)"], \PDO::PARAM_INT);
                    $statement->bindValue(':host_id', (int) $key, \PDO::PARAM_INT);
                    $statement->execute();

                    /**
                     * The value should be duplicated in vault if it's a password and is already in vault
                     * The pattern secret:: define that the value is store in vault.
                     */
                    if (
                        ! empty($row['host_snmp_community'])
                        && str_starts_with(VaultConfiguration::VAULT_PATH_PATTERN, $row['host_snmp_community'])
                        || ! empty($macroPasswords)
                    ) {
                        if ($vaultConfiguration !== null) {
                            /** @var ReadVaultRepositoryInterface $readVaultRepository */
                            $readVaultRepository = $kernel->getContainer()->get(
                                ReadVaultRepositoryInterface::class
                            );
                            /** @var WriteVaultRepositoryInterface $writeVaultRepository */
                            $writeVaultRepository = $kernel->getContainer()->get(
                                WriteVaultRepositoryInterface::class
                            );
                            $writeVaultRepository->setCustomPath(AbstractVaultRepository::HOST_VAULT_PATH);
                            try {
                                duplicateHostSecretsInVault(
                                    $readVaultRepository,
                                    $writeVaultRepository,
                                    $logger,
                                    $row['host_snmp_community'],
                                    $macroPasswords,
                                    $key,
                                    (int) $maxId["MAX(host_id)"]
                                );
                            } catch (\Throwable $ex) {
                                error_log((string) $ex);
                            }
                        }
                    }

                    signalConfigurationChange('host', (int) $maxId["MAX(host_id)"]);
                    $centreon->CentreonLogAction->insertLog("host", $maxId["MAX(host_id)"], $hostName, "a", $fields);
                }
            }
            // if all duplication names are already used, next value is never set
            if (isset($maxId['MAX(host_id)'])) {
                $centreon->user->access->updateACL([
                    'type' => 'HOST',
                    'id' => $maxId['MAX(host_id)'],
                    'action' => 'DUP',
                    'duplicate_host' => (int)$key,
                ]);
            }
        }
    }
    CentreonACL::duplicateHostAcl($hostAcl);
}

/**
 * @param int $host_id
 */
function resetHostHostParent(int $host_id): void
{
    global $pearDB;

    $stmt = $pearDB->prepare("DELETE FROM host_hostparent_relation WHERE host_host_id = :hostId");
    $stmt->bindValue(':hostId', $host_id, \PDO::PARAM_INT);
    $stmt->execute();
}

/**
 * @param int $host_id
 */
function resetHostHostChild(int $host_id): void
{
    global $pearDB;

    $stmt = $pearDB->prepare("DELETE FROM host_hostparent_relation WHERE host_parent_hp_id = :hostId");
    $stmt->bindValue(':hostId', $host_id, \PDO::PARAM_INT);
    $stmt->execute();
}

/**
 * @param int $host_id
 */
function resetHostContactGroup(int $host_id): void
{
    global $pearDB;

    $stmt = $pearDB->prepare("DELETE FROM contactgroup_host_relation WHERE host_host_id = :hostId");
    $stmt->bindValue(':hostId', $host_id, \PDO::PARAM_INT);
    $stmt->execute();
}

/**
 * @param int $host_id
 */
function resetHostContact(int $host_id): void
{
    global $pearDB;

    $stmt = $pearDB->prepare("DELETE FROM contact_host_relation WHERE host_host_id = :hostId");
    $stmt->bindValue(':hostId', $host_id, \PDO::PARAM_INT);
    $stmt->execute();
}

function updateHostInDB($hostId = null, $isMassiveChange = false, $configuration = null)
{
    global $form, $centreon, $isCloudPlatform;

    if (! $hostId) {
        return;
    }

    if (! isset($configuration)) {
        $ret = $form->getSubmitValues();
    } else {
        $ret = $configuration;
    }

    $previousPollerIds = findPollersForConfigChangeFlagFromHostIds([$hostId]);

    /*
     *  Global function to use
     */

    if ($isMassiveChange) {
        updateHost_MC($hostId);
    } else {
        updateHost($hostId, $isMassiveChange, $ret);
    }

    if ($isCloudPlatform) {
        resetHostHostParent($hostId);
        resetHostHostChild($hostId);
        resetHostContactGroup($hostId);
        resetHostContact($hostId);
    }

    if (! $isCloudPlatform) {
        /*
         *  Function for updating host parents
         *  1 - MC with deletion of existing parents
         *  2 - MC with addition of new parents
         *  3 - Normal update
         */
        if (isset($ret['mc_mod_hpar']['mc_mod_hpar']) && $ret['mc_mod_hpar']['mc_mod_hpar']) {
            updateHostHostParent($hostId);
        } elseif (isset($ret['mc_mod_hpar']['mc_mod_hpar']) && !$ret['mc_mod_hpar']['mc_mod_hpar']) {
            updateHostHostParent_MC($hostId);
        } else {
            updateHostHostParent($hostId);
        }

        /*
         * Function for updating host childs
         * 1 - MC with deletion of existing childs
         * 2 - MC with addition of new childs
         * 3 - Normal update
         */
        if (isset($ret['mc_mod_hch']['mc_mod_hch']) && $ret['mc_mod_hch']['mc_mod_hch']) {
            updateHostHostChild($hostId);
        } elseif (isset($ret['mc_mod_hch']['mc_mod_hch']) && !$ret['mc_mod_hch']['mc_mod_hch']) {
            updateHostHostChild_MC($hostId);
        } else {
            updateHostHostChild($hostId);
        }

        /*
         * Function for updating host cg
         * 1 - MC with deletion of existing cg
         * 2 - MC with addition of new cg
         * 3 - Normal update
         */
        if (isset($ret['mc_mod_hcg']['mc_mod_hcg']) && $ret['mc_mod_hcg']['mc_mod_hcg']) {
            updateHostContactGroup($hostId, $ret);
            updateHostContact($hostId, $ret);
        } elseif (isset($ret['mc_mod_hcg']['mc_mod_hcg']) && !$ret['mc_mod_hcg']['mc_mod_hcg']) {
            updateHostContactGroup_MC($hostId, $ret);
            updateHostContact_MC($hostId, $ret);
        } else {
            updateHostContactGroup($hostId, $ret);
            updateHostContact($hostId, $ret);
        }

        /*
         * Function for updating notification options
         * 1 - MC with deletion of existing options (Replacement)
         * 2 - MC with addition of new options (incremental)
         * 3 - Normal update
         */
        if (isset($ret['mc_mod_notifopts']['mc_mod_notifopts']) && $ret['mc_mod_notifopts']['mc_mod_notifopts']) {
            updateHostNotifs($hostId);
        } elseif (isset($ret['mc_mod_notifopts']['mc_mod_notifopts']) && !$ret['mc_mod_notifopts']['mc_mod_notifopts']) {
            updateHostNotifs_MC($hostId);
        } else {
            updateHostNotifs($hostId);
        }

        /*
         * Function for updating notification interval options
         * 1 - MC with deletion of existing options (Replacement)
         * 2 - MC with addition of new options (incremental)
         * 3 - Normal update
         */
        if (
            isset($ret['mc_mod_notifopt_notification_interval']['mc_mod_notifopt_notification_interval'])
            && $ret['mc_mod_notifopt_notification_interval']['mc_mod_notifopt_notification_interval']
        ) {
            updateHostNotifOptionInterval($hostId);
        } elseif (
            isset($ret['mc_mod_notifopt_notification_interval']['mc_mod_notifopt_notification_interval'])
            && !$ret['mc_mod_notifopt_notification_interval']['mc_mod_notifopt_notification_interval']
        ) {
            updateHostNotifOptionInterval_MC($hostId);
        } else {
            updateHostNotifOptionInterval($hostId);
        }

        /*
         * Function for updating first notification delay options
         * 1 - MC with deletion of existing options (Replacement)
         * 2 - MC with addition of new options (incremental)
         * 3 - Normal update, default behavior
         */
        if (
            isset($ret['mc_mod_notifopt_first_notification_delay']['mc_mod_notifopt_first_notification_delay'])
            && $ret['mc_mod_notifopt_first_notification_delay']['mc_mod_notifopt_first_notification_delay']
        ) {
            updateHostNotifOptionFirstNotificationDelay($hostId);
        } elseif (
            isset($ret['mc_mod_notifopt_first_notification_delay']['mc_mod_notifopt_first_notification_delay'])
            && !$ret['mc_mod_notifopt_first_notification_delay']['mc_mod_notifopt_first_notification_delay']
        ) {
            updateHostNotifOptionFirstNotificationDelay_MC($hostId);
        } else {
            updateHostNotifOptionFirstNotificationDelay($hostId);
        }


        // Function for updating first notification delay options
        updateHostNotifOptionRecoveryNotificationDelay($hostId);

        /*
         * Function for updating notification timeperiod options
         * 1 - MC with deletion of existing options (Replacement)
         * 2 - MC with addition of new options (incremental)
         * 3 - Normal update
         */
        if (
            isset($ret['mc_mod_notifopt_timeperiod']['mc_mod_notifopt_timeperiod'])
            && $ret['mc_mod_notifopt_timeperiod']['mc_mod_notifopt_timeperiod']
        ) {
            updateHostNotifOptionTimeperiod($hostId);
        } elseif (
            isset($ret['mc_mod_notifopt_timeperiod']['mc_mod_notifopt_timeperiod'])
            && !$ret['mc_mod_notifopt_timeperiod']['mc_mod_notifopt_timeperiod']
        ) {
            updateHostNotifOptionTimeperiod_MC($hostId);
        } else {
            updateHostNotifOptionTimeperiod($hostId);
        }
    }
    /*
     * Function for updating host hg
     * 1 - MC with deletion of existing hg
     * 2 - MC with addition of new hg
     * 3 - Normal update
     */
    if (isset($ret['mc_mod_hhg']['mc_mod_hhg']) && $ret['mc_mod_hhg']['mc_mod_hhg']) {
        updateHostHostGroup($hostId);
    } elseif (isset($ret['mc_mod_hhg']['mc_mod_hhg']) && !$ret['mc_mod_hhg']['mc_mod_hhg']) {
        updateHostHostGroup_MC($hostId);
    } else {
        updateHostHostGroup($hostId);
    }

    /*
     * Function for updating host hc
     * 1 - MC with deletion of existing hc
     * 2 - MC with addition of new hc
     * 3 - Normal update
     */
    if (isset($ret['mc_mod_hhc']['mc_mod_hhc']) && $ret['mc_mod_hhc']['mc_mod_hhc']) {
        updateHostHostCategory($hostId);
    } elseif (isset($ret['mc_mod_hhc']['mc_mod_hhc']) && !$ret['mc_mod_hhc']['mc_mod_hhc']) {
        updateHostHostCategory_MC($hostId);
    } else {
        updateHostHostCategory($hostId, $ret);
    }
    /*
     * Function for updating host template
     * 1 - MC with deletion of existing template
     * 2 - MC with addition of new template
     * 3 - Normal update
     */
    if (isset($ret['mc_mod_htpl']['mc_mod_htpl']) && $ret['mc_mod_htpl']['mc_mod_htpl']) {
        updateHostTemplateService($hostId);
    } elseif (isset($ret['mc_mod_htpl']['mc_mod_htpl']) && !$ret['mc_mod_htpl']['mc_mod_htpl']) {
        updateHostTemplateService_MC($hostId);
    } else {
        updateHostTemplateService($hostId);
    }

    if (isset($ret['dupSvTplAssoc']['dupSvTplAssoc']) && $ret['dupSvTplAssoc']['dupSvTplAssoc']) {
        if (isset($ret['host_template_model_htm_id'])) {
            createHostTemplateService($hostId, $ret['host_template_model_htm_id']);
        } elseif ($centreon->user->get_version()) {
            createHostTemplateService($hostId);
        }
    }

    /*
     * Host extended information
     */
    if ($isMassiveChange) {
        updateHostExtInfos_MC($hostId);
    } else {
        updateHostExtInfos($hostId, $ret);
    }

    # Function for updating host hg
    # 1 - MC with deletion of existing hg
    # 2 - MC with addition of new hg
    # 3 - Normal update
    updateNagiosServerRelation($hostId);

    signalConfigurationChange('host', $hostId, $previousPollerIds);

    return ($hostId);
}

/**
 * @param array<string,<int,string|int|null>> $bindParams
 * @param bool $isTemplate
 * @return array<string,<int,string|int|null>>
 */
function resetHostTypeSpecificParams(array $bindParams, int $isTemplate): array
{
    if ($isTemplate) {
        $hostSpecificInputs = ['host_address', 'host_activate', 'nagios_server_id', 'geo_coords'];
        foreach ($hostSpecificInputs as $inputName) {
            if (in_array(":$inputName", $bindParams)) {
                $bindParams[":$inputName"] = [\PDO::PARAM_NULL => null];
            }
        }
    } else {
        if (in_array(":command_command_id", $bindParams)) {
            $bindParams[":command_command_id"] = [\PDO::PARAM_NULL => null];
        }
    }

    return $bindParams;
}

/**
 * @param array<string,<int,string|int|null>> $bindParams
 * @return array<string,<int,string|int|null>>
 */
function resetUnwantedParameters(array $bindParams): array
{
    $paramsToReset = [
        'timeperiod_tp_id2',
        'command_command_id2',
        'host_freshness_threshold',
        'host_low_flap_threshold',
        'host_high_flap_threshold',
        'host_notification_interval',
        'host_first_notification_delay',
        'host_recovery_notification_delay',
        'host_acknowledgement_timeout',
        'command_command_id_arg1',
        'command_command_id_arg2',
        'host_comment',
        'host_checks_enabled',
        'host_obsess_over_host',
        'host_check_freshness',
        'host_event_handler_enabled',
        'host_flap_detection_enabled',
        'host_retain_status_information',
        'host_retain_nonstatus_information',
        'host_notifications_enabled',
        'host_notification_options',
        'contact_additive_inheritance',
        'cg_additive_inheritance',
        'host_stalking_options'
    ];

    foreach ($paramsToReset as $paramName) {
        $bindParams[':' . $paramName] = [
            \PDO::PARAM_NULL => null
        ];
    }

    $paramsToEnumDefault = [
        'host_active_checks_enabled',
        'host_passive_checks_enabled',
        'host_notifications_enabled',
        'host_obsess_over_host',
        'host_check_freshness',
        'host_flap_detection_enabled',
        'host_retain_status_information',
        'host_retain_nonstatus_information',
        'host_event_handler_enabled',
    ];

    foreach ($paramsToEnumDefault as $paramName) {
        $bindParams[':' . $paramName] = [
            \PDO::PARAM_STR => '2'
        ];
    }

    return $bindParams;
}

/*
 * Get list of host templates recursively
 */

function getHostListInUse($hst_list, $hst)
{
    global $pearDB;

    $str = $hst_list;
    $statement = $pearDB->prepare(
        "SELECT `host_tpl_id` FROM `host_template_relation` WHERE host_host_id = :host_host_id"
    );
    $statement->bindValue(':host_host_id', (int) $hst, \PDO::PARAM_INT);
    $statement->execute();
    while (($result = $statement->fetch(\PDO::FETCH_ASSOC)) !== false) {
        $str .= "," . $result['host_tpl_id'];
        $str = getHostListInUse($str, $result['host_tpl_id']);
    }
    $statement->closeCursor();
    return $str;
}

/*
 *  Checks if the service that is gonna be deleted is actually
 *  associated to another host template
 *  if yes, we do not delete the service
 *  Function returns true if it doesn't have to be deleted, otherwise it returns false
 */

function serviceIsInUse($svc_id, $host_list)
{
    global $pearDB;

    $hst_list = "";
    $flag_first = 1;
    foreach ($host_list as $val) {
        if (isset($val)) {
            if (!$flag_first) {
                $hst_list .= "," . $val;
            } else {
                $hst_list .= $val;
                $flag_first = 0;
            }
            $hst_list = getHostListInUse($hst_list, $val);
        }
    }
    if ($hst_list == "") {
        $hst_list = "NULL";
    }
    $hstListExploded = explode(',', $hst_list);
    $queryBindValues = [];
    foreach ($hstListExploded as $index => $hostId) {
        $queryBindValues[':host_' . $index] = (int) $hostId;
    }
    $bindIds = implode(', ', array_keys($queryBindValues));
    $rq = "SELECT service_id " .
        "FROM service svc, host_service_relation hsr " .
        "WHERE hsr.service_service_id = svc.service_template_model_stm_id " .
        "AND hsr.service_service_id = :service_service_id " .
        "AND hsr.host_host_id IN ($bindIds)";
    $statement = $pearDB->prepare($rq);
    $statement->bindValue(':service_service_id', (int) $svc_id, \PDO::PARAM_INT);
    foreach ($queryBindValues as $bindKey => $hostId) {
        $statement->bindValue($bindKey, $hostId, \PDO::PARAM_INT);
    }
    $statement->execute();
    if ($statement->rowCount() >= 1) {
        return true;
    }
    return false;
}

/*
 * 	this function cleans all the services that were linked to the removed host template
 */

function deleteHostServiceMultiTemplate($hID, $scndHID, $host_list, $antiLoop = null)
{
    global $pearDB;

    if (isset($antiLoop[$scndHID]) && $antiLoop[$scndHID]) {
        return 0;
    }
    $dbResult = $pearDB->query("SELECT service_service_id " .
        "FROM `service` svc, `host_service_relation` hsr " .
        "WHERE svc.service_id = hsr.service_service_id " .
        "AND svc.service_register = '0' " .
        "AND hsr.host_host_id = '" . $scndHID . "'");
    $rq2 = "DELETE hsr, svc FROM `host_service_relation` hsr, `service` svc " .
        "WHERE hsr.service_service_id = svc.service_id " .
        "AND svc.service_template_model_stm_id = :service_template_model_stm_id " .
        "AND svc.service_register = '1' " .
        "AND hsr.host_host_id = :host_host_id";
    $statement = $pearDB->prepare($rq2);
    while ($svcID = $dbResult->fetch()) {
        if (!serviceIsInUse($svcID['service_service_id'], $host_list)) {
            $statement->bindValue(
                ':service_template_model_stm_id',
                (int) $svcID['service_service_id'],
                \PDO::PARAM_INT
            );
            $statement->bindValue(
                ':host_host_id',
                (int) $hID,
                \PDO::PARAM_INT
            );
            $statement->execute();
        }
    }
    $dbResult->closeCursor();

    $rq = "SELECT host_tpl_id " .
        "FROM host_template_relation " .
        "WHERE host_host_id = '" . $scndHID . "' " .
        "ORDER BY `order`";

    $dbResult = $pearDB->query($rq);
    $selectStatement = $pearDB->prepare(
        "SELECT service_service_id " .
        "FROM `service` svc, `host_service_relation` hsr " .
        "WHERE svc.service_id = hsr.service_service_id " .
        "AND svc.service_register = '0' " .
        "AND hsr.host_host_id = :host_host_id"
    );
    $rq2 = "DELETE hsr, svc FROM `host_service_relation` hsr, `service` svc " .
        "WHERE hsr.service_service_id = svc.service_id " .
        "AND svc.service_template_model_stm_id = :service_template_model_stm_id " .
        "AND svc.service_register = '1' " .
        "AND hsr.host_host_id = :host_host_id";
    $deleteStatement = $pearDB->prepare($rq2);
    while ($result = $dbResult->fetch()) {
        $selectStatement->bindValue(':host_host_id', (int) $result["host_tpl_id"], \PDO::PARAM_INT);
        $selectStatement->execute();
        while (($svcID = $selectStatement->fetch()) !== false) {
            $deleteStatement->bindValue(
                ':service_template_model_stm_id',
                (int) $svcID[ 'service_service_id' ],
                \PDO::PARAM_INT
            );
            $deleteStatement->bindValue(
                ':host_host_id',
                (int) $hID,
                \PDO::PARAM_INT
            );
            $deleteStatement->execute();
        }
        $antiLoop[$scndHID] = 1;
        deleteHostServiceMultiTemplate($hID, $result["host_tpl_id"], $host_list, $antiLoop);
    }
    $dbResult->closeCursor();
}

function updateHost($hostId = null, $isMassiveChange = false, $configuration = null)
{
    global $form, $pearDB, $centreon, $isCloudPlatform;

    $hostObj = new CentreonHost($pearDB);

    if (! $hostId) {
        return;
    }

    $host = new CentreonHost($pearDB);

    $ret = [];

    if (! isset($configuration)) {
        $ret = $form->getSubmitValues();
    } else {
        $ret = $configuration;
    }

    $kernel = Kernel::createForWeb();
    /** @var Logger $logger */
    $logger = $kernel->getContainer()->get(Logger::class);
    $readVaultConfigurationRepository = $kernel->getContainer()->get(
        ReadVaultConfigurationRepositoryInterface::class
    );
    $vaultConfiguration = $readVaultConfigurationRepository->find();

    //Retrieve UUID for vault path before updating values in database.
    $vaultPath = null;
    if ($vaultConfiguration !== null ){
        $vaultPath = retrieveHostVaultPathFromDatabase($pearDB, $hostId);
    }

    if (! $isCloudPlatform) {
        if (! isset($ret['contact_additive_inheritance'])) {
            $ret['contact_additive_inheritance'] = '0';
        }
        if (! isset($ret['cg_additive_inheritance'])) {
            $ret['cg_additive_inheritance'] = '0';
        }
    }

    isset($ret["nagios_server_id"])
        ? $server_id = $ret["nagios_server_id"]
        : $server_id = $form->getSubmitValue("nagios_server_id");

    if (! isset($server_id) || $server_id == "" || $server_id == 0) {
        $server_id = null;
    }

    if (! $isCloudPlatform) {
        if (isset($ret["command_command_id_arg1"]) && $ret["command_command_id_arg1"] != null) {
            $ret["command_command_id_arg1"] = str_replace("\n", "#BR#", $ret["command_command_id_arg1"]);
            $ret["command_command_id_arg1"] = str_replace("\t", "#T#", $ret["command_command_id_arg1"]);
            $ret["command_command_id_arg1"] = str_replace("\r", "#R#", $ret["command_command_id_arg1"]);
        }
        if (isset($ret["command_command_id_arg2"]) && $ret["command_command_id_arg2"] != null) {
            $ret["command_command_id_arg2"] = str_replace("\n", "#BR#", $ret["command_command_id_arg2"]);
            $ret["command_command_id_arg2"] = str_replace("\t", "#T#", $ret["command_command_id_arg2"]);
            $ret["command_command_id_arg2"] = str_replace("\r", "#R#", $ret["command_command_id_arg2"]);
        }
    }

    $ret["host_name"] = $host->checkIllegalChar($ret["host_name"], $server_id);
    if ($ret['host_snmp_community'] === PASSWORD_REPLACEMENT_VALUE) {
        unset($ret['host_snmp_community']);
    }
    $bindParams = sanitizeFormHostParameters($ret);

    if ($isCloudPlatform) {
        $bindParams = resetUnwantedParameters($bindParams);
        $bindParams = resetHostTypeSpecificParams(
            $bindParams,
            isset($ret["host_register"]) && $ret['host_register'] === '0' ? true : false
        );
    }

    $rq = "UPDATE host SET ";
    foreach (array_keys($bindParams) as $token) {
        $rq .= ltrim($token, ':') . " = " . $token . ", ";
    }
    $rq = rtrim($rq, ', ');
    $rq .= " WHERE host_id = :hostId";
    $stmt = $pearDB->prepare($rq);
    foreach ($bindParams as $token => $bindValues) {
        foreach ($bindValues as $paramType => $value) {
            $stmt->bindValue($token, $value, $paramType);
        }
    }
    $stmt->bindValue(':hostId', $hostId, \PDO::PARAM_INT);
    $stmt->execute();

    /*
     *  Update multiple templates
     */
    if (isset($_REQUEST['tpSelect'])) {
        /* Cleanup host service link to host template to be removed */
        $newTp = array();
        foreach ($_POST['tpSelect'] as $tmpl) {
            $newTp[$tmpl] = $tmpl;
        }

        $dbResult = $pearDB->query("SELECT `host_tpl_id`
                                    FROM `host_template_relation`
                                    WHERE `host_host_id` = '" . $hostId . "'");
        while ($hst = $dbResult->fetch()) {
            if (!isset($newTp[$hst['host_tpl_id']])) {
                deleteHostServiceMultiTemplate($hostId, $hst['host_tpl_id'], $newTp);
            }
        }

        /* Set template */
        $hostObj->setTemplates($hostId, $_REQUEST['tpSelect']);
    } elseif (isset($ret["use"]) && $ret["use"]) {
        $already_stored = array();
        $tplTab = preg_split("/\,/", $ret["use"]);
        $j = 0;
        $DBRES = $pearDB->query("DELETE FROM `host_template_relation` WHERE `host_host_id` = '" . $hostId . "'");
        foreach ($tplTab as $val) {
            $tplId = getMyHostID($val);
            if (!isset($already_stored[$tplId]) && $tplId) {
                $rq = "INSERT INTO host_template_relation (`host_host_id`, `host_tpl_id`, `order`)
                        VALUES (" . $hostId . ", " . $tplId . ", " . $j . ")";
                $dbResult = $pearDB->query($rq);
                $j++;
                $already_stored[$tplId] = 1;
            }
        }
    } else {
        /* Cleanup host service link to host template to be removed */
        $newTp = array();

        $dbResult = $pearDB->query("SELECT `host_tpl_id`
                                    FROM `host_template_relation`
                                    WHERE `host_host_id` = '" . $hostId . "'");
        while ($hst = $dbResult->fetch()) {
            if (!isset($newTp[$hst['host_tpl_id']])) {
                deleteHostServiceMultiTemplate($hostId, $hst['host_tpl_id'], $newTp);
            }
        }

        /* Set template */
        $hostObj->setTemplates($hostId, array());
    }

    /*
     *  Update demand macros
     */
    if (
        isset($_REQUEST['macroInput']) &&
        isset($_REQUEST['macroValue'])
    ) {
        $macroDescription = array();
        foreach ($_REQUEST as $nam => $ele) {
            if (preg_match_all("/^macroDescription_(\w+)$/", $nam, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $macroDescription[$match[1]] = $ele;
                }
            }
        }
        $hostObj->insertMacro(
            $hostId,
            $_REQUEST['macroInput'],
            $_REQUEST['macroValue'],
            $_REQUEST['macroPassword'] ?? [],
            $macroDescription,
            false,
            $ret["command_command_id"] ?? false
        );
    } else {
        $pearDB->query("DELETE FROM on_demand_macro_host WHERE host_host_id = '" . CentreonDB::escape($hostId) . "'");
    }

    if (isset($ret['criticality_id'])) {
        setHostCriticality($hostId, $ret['criticality_id']);
    }

    //If there is a vault configuration write into vault
    if ($vaultConfiguration !== null) {
        /** @var ReadVaultRepositoryInterface $readVaultRepository */
        $readVaultRepository = $kernel->getContainer()->get(ReadVaultRepositoryInterface::class);

        /** @var WriteVaultRepositoryInterface $writeVaultRepository */
        $writeVaultRepository = $kernel->getContainer()->get(WriteVaultRepositoryInterface::class);
        $writeVaultRepository->setCustomPath(AbstractVaultRepository::HOST_VAULT_PATH);
        try {
            updateHostSecretsInVault(
                $readVaultRepository,
                $writeVaultRepository,
                $logger,
                $vaultPath,
                (int) $hostId,
                $hostObj->getFormattedMacros(),
                $bindParams[':host_snmp_community'][\PDO::PARAM_STR] ?? null
            );
        } catch (\Throwable $ex) {
            error_log((string) $ex);
        }
    }

    /*
     *  Logs
     */
    /* Prepare value for changelog */
    $fields = CentreonLogAction::prepareChanges($ret);
    $centreon->CentreonLogAction->insertLog("host", $hostId, CentreonDB::escape($ret["host_name"]), "c", $fields);
    $centreon->user->access->updateACL(array("type" => 'HOST', 'id' => $hostId, "action" => "UPDATE"));
}

function updateHost_MC($hostId = null)
{
    global $form, $pearDB, $centreon, $isCloudPlatform;

    $hostObj = new CentreonHost($pearDB);

    if (! $hostId) {
        return;
    }

    $kernel = Kernel::createForWeb();
    /** @var Logger $logger */
    $logger = $kernel->getContainer()->get(Logger::class);
    $readVaultConfigurationRepository = $kernel->getContainer()->get(
        ReadVaultConfigurationRepositoryInterface::class
    );
    $vaultConfiguration = $readVaultConfigurationRepository->find();

    //Retrieve UUID for vault path before updating values in database.
    $vaultPath = null;
    if ($vaultConfiguration !== null ){
        $vaultPath = retrieveHostVaultPathFromDatabase($pearDB, $hostId);
    }

    $submittedValues = $form->getSubmitValues();

    if (! $isCloudPlatform) {
        if (isset($submittedValues["command_command_id_arg1"]) && $submittedValues["command_command_id_arg1"] != null) {
            $submittedValues["command_command_id_arg1"] = str_replace("\n", "#BR#", $submittedValues["command_command_id_arg1"]);
            $submittedValues["command_command_id_arg1"] = str_replace("\t", "#T#", $submittedValues["command_command_id_arg1"]);
            $submittedValues["command_command_id_arg1"] = str_replace("\r", "#R#", $submittedValues["command_command_id_arg1"]);
        }
        if (isset($submittedValues["command_command_id_arg2"]) && $submittedValues["command_command_id_arg2"] != null) {
            $submittedValues["command_command_id_arg2"] = str_replace("\n", "#BR#", $submittedValues["command_command_id_arg2"]);
            $submittedValues["command_command_id_arg2"] = str_replace("\t", "#T#", $submittedValues["command_command_id_arg2"]);
            $submittedValues["command_command_id_arg2"] = str_replace("\r", "#R#", $submittedValues["command_command_id_arg2"]);
        }
    }

    // For Centreon 2, we no longer need "host_template_model_htm_id" in Nagios 3
    // but we try to keep it compatible with Nagios 2 which needs "host_template_model_htm_id"
    if (isset($_POST['nbOfSelect'])) {
        $dbResult = $pearDB->query("SELECT host_id FROM `host` WHERE host_register='0' LIMIT 1");
        $result = $dbResult->fetch();
        $submittedValues["host_template_model_htm_id"] = $result["host_id"];
        $dbResult->closeCursor();
    }

    // Remove all parameters that have an empty value in order to keep the host properties that have not been modified
    foreach ($submittedValues as $name => $value) {
        if (is_string($value) && empty($value)) {
            unset($submittedValues[$name]);
        }
    }

    $bindParams = sanitizeFormHostParameters($submittedValues);

    if ($isCloudPlatform) {
        $bindParams = resetUnwantedParameters($bindParams);
        $bindParams = resetHostTypeSpecificParams(
            $bindParams,
            isset($ret['host_register']) && $ret['host_register'] === '0' ? true : false
        );
    }

    $request = "UPDATE host SET ";
    foreach (array_keys($bindParams) as $token) {
        $request .= ltrim($token, ':') . " = " . $token . ", ";
    }
    $request = rtrim($request, ', ');
    $request .= " WHERE host_id = :hostId";
    $statement = $pearDB->prepare($request);
    foreach ($bindParams as $token => $bindValues) {
        foreach ($bindValues as $paramType => $value) {
            $statement->bindValue($token, $value, $paramType);
        }
    }
    $statement->bindValue(':hostId', $hostId, \PDO::PARAM_INT);
    $statement->execute();

    /*
     *  update multiple templates
     */
    if (isset($_REQUEST['tpSelect'])) {
        $oldTp = array();
        if (isset($_POST['mc_mod_tplp']['mc_mod_tplp']) && $_POST['mc_mod_tplp']['mc_mod_tplp'] == 0) {
            $dbResult = $pearDB->query("SELECT `host_tpl_id`
                                        FROM `host_template_relation`
                                        WHERE `host_host_id`='" . $hostId . "'");
            while ($hst = $dbResult->fetch()) {
                $oldTp[$hst["host_tpl_id"]] = $hst["host_tpl_id"];
            }
        }
        $hostObj->setTemplates($hostId, $_REQUEST['tpSelect'], $oldTp);
    }

    /*
     *  Update on demand macros
     */
    $macroDescription = array();
    foreach ($_REQUEST as $nam => $ele) {
        if (preg_match_all("/^macroDescription_(\w+)$/", $nam, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $macroDescription[$match[1]] = $ele;
            }
        }
    }

    if (isset($_REQUEST['macroInput']) && isset($_REQUEST['macroValue'])) {
        $hostObj->insertMacro(
            $hostId,
            $_REQUEST['macroInput'],
            $_REQUEST['macroValue'],
            $_REQUEST['macroPassword'] ?? [],
            $macroDescription,
            true
        );
    }

    if (isset($submittedValues['criticality_id']) && $submittedValues['criticality_id']) {
        setHostCriticality($hostId, $submittedValues['criticality_id']);
    }

    // If there is a vault configuration write into vault.
    if ($vaultConfiguration !== null) {
        try {
            /** @var ReadVaultRepositoryInterface $readVaultRepository */
            $readVaultRepository = $kernel->getContainer()->get(ReadVaultRepositoryInterface::class);

            /** @var WriteVaultRepositoryInterface $writeVaultRepository */
            $writeVaultRepository = $kernel->getContainer()->get(WriteVaultRepositoryInterface::class);
            $writeVaultRepository->setCustomPath(AbstractVaultRepository::HOST_VAULT_PATH);

            $updatedPasswordMacros = array_filter($hostObj->getFormattedMacros(), function ($macro) {
                return $macro['macroPassword'] === '1'
                    && ! str_starts_with($macro['macroValue'], VaultConfiguration::VAULT_PATH_PATTERN);
            });
            updateHostSecretsInVaultFromMC(
                $readVaultRepository,
                $writeVaultRepository,
                $logger,
                $vaultPath,
                $hostId,
                $updatedPasswordMacros,
                $submittedValues['host_snmp_community'] ?? null
            );
        } catch (\Throwable $ex) {
            error_log((string) $ex);
        }
    }

    $dbResultX = $pearDB->query("SELECT host_name FROM `host` WHERE host_id='" . $hostId . "' LIMIT 1");
    $row = $dbResultX->fetch();

    /* Prepare value for changelog */
    $fields = CentreonLogAction::prepareChanges($submittedValues);
    $centreon->CentreonLogAction->insertLog("host", $hostId, $row["host_name"], "mc", $fields);
}

function updateHostHostParent($host_id = null, $ret = array())
{
    global $form, $pearDB;

    if (!$host_id) {
        return;
    }

    $rq = "DELETE FROM host_hostparent_relation ";
    $rq .= "WHERE host_host_id = '" . $host_id . "'";
    $dbResult = $pearDB->query($rq);

    if (isset($ret["host_parents"])) {
        $ret = $ret["host_parents"];
    } else {
        $ret = CentreonUtils::mergeWithInitialValues($form, 'host_parents');
    }

    for ($i = 0; $i < count($ret); $i++) {
        if (isset($ret[$i]) && $ret[$i] != $host_id && $ret[$i] != "") {
            $rq = "INSERT INTO host_hostparent_relation ";
            $rq .= "(host_parent_hp_id, host_host_id) ";
            $rq .= "VALUES ";
            $rq .= "('" . $ret[$i] . "', '" . $host_id . "')";
            $dbResult = $pearDB->query($rq);
        }
    }
}

/*
 * For massive change. We just add the new list if the elem doesn't exist yet
 */

function updateHostHostParent_MC($host_id = null, $ret = array())
{
    global $form, $pearDB;

    if (!$host_id) {
        return;
    }

    $rq = "SELECT * FROM host_hostparent_relation ";
    $rq .= "WHERE host_host_id = '" . $host_id . "'";
    $dbResult = $pearDB->query($rq);
    $hpars = array();
    while ($arr = $dbResult->fetch()) {
        $hpars[$arr["host_parent_hp_id"]] = $arr["host_parent_hp_id"];
    }

    $ret = $form->getSubmitValue("host_parents");
    if (is_array($ret)) {
        for ($i = 0; $i < count($ret); $i++) {
            if (!isset($hpars[$ret[$i]]) && isset($ret[$i])) {
                if (isset($ret[$i]) && $ret[$i] != $host_id && $ret[$i] != "") {
                    $rq = "INSERT INTO host_hostparent_relation ";
                    $rq .= "(host_parent_hp_id, host_host_id) ";
                    $rq .= "VALUES ";
                    $rq .= "('" . $ret[$i] . "', '" . $host_id . "')";
                    $dbResult = $pearDB->query($rq);
                }
            }
        }
    }
}

function updateHostHostChild($host_id = null)
{
    global $form, $pearDB;

    if (!$host_id) {
        return;
    }

    $rq = "DELETE FROM host_hostparent_relation ";
    $rq .= "WHERE host_parent_hp_id = '" . $host_id . "'";
    $dbResult = $pearDB->query($rq);

    $ret = array();
    $ret = CentreonUtils::mergeWithInitialValues($form, 'host_childs');
    for ($i = 0; $i < count($ret); $i++) {
        if (isset($ret[$i]) && $ret[$i] != $host_id && $ret[$i] != "") {
            $rq = "INSERT INTO host_hostparent_relation ";
            $rq .= "(host_parent_hp_id, host_host_id) ";
            $rq .= "VALUES ";
            $rq .= "('" . $host_id . "', '" . $ret[$i] . "')";
            $dbResult = $pearDB->query($rq);
        }
    }
}

/**
 * For massive change. We just add the new list if the elem doesn't exist yet
 */
function updateHostHostChild_MC($host_id = null)
{
    global $form, $pearDB;

    if (!$host_id) {
        return;
    }

    $rq = "SELECT * FROM host_hostparent_relation ";
    $rq .= "WHERE host_parent_hp_id = '" . $host_id . "'";
    $dbResult = $pearDB->query($rq);
    $hchs = array();
    while ($arr = $dbResult->fetch()) {
        $hchs[$arr["host_host_id"]] = $arr["host_host_id"];
    }

    $ret = $form->getSubmitValue("host_childs");
    if (is_array($ret)) {
        for ($i = 0; $i < count($ret); $i++) {
            if (!isset($hchs[$ret[$i]]) && isset($ret[$i])) {
                if (isset($ret[$i]) && $ret[$i] != $host_id && $ret[$i] != "") {
                    $rq = "INSERT INTO host_hostparent_relation ";
                    $rq .= "(host_parent_hp_id, host_host_id) ";
                    $rq .= "VALUES ";
                    $rq .= "('" . $host_id . "', '" . $ret[$i] . "')";
                    $dbResult = $pearDB->query($rq);
                }
            }
        }
    }
}

/**
 *
 */
function updateHostExtInfos($host_id = null, $ret = array())
{
    global $form, $pearDB;

    if (!$host_id) {
        return;
    }

    if (!count($ret)) {
        $ret = $form->getSubmitValues();
    }

    /*
     * Check if image selected isn't a directory
     */
    if (isset($ret["ehi_icon_image"]) && strrchr("REP_", $ret["ehi_icon_image"])) {
        $ret["ehi_icon_image"] = null;
    }
    if (isset($ret["ehi_statusmap_image"]) && strrchr("REP_", $ret["ehi_statusmap_image"])) {
        $ret["ehi_statusmap_image"] = null;
    }
    /*
     *
     */
    $rq = "UPDATE extended_host_information ";
    $rq .= "SET ehi_notes = ";
    isset($ret["ehi_notes"]) && $ret["ehi_notes"] != null
        ? $rq .= "'" . CentreonDB::escape($ret["ehi_notes"]) . "', "
        : $rq .= "NULL, ";
    $rq .= "ehi_notes_url = ";
    isset($ret["ehi_notes_url"]) && $ret["ehi_notes_url"] != null
        ? $rq .= "'" . CentreonDB::escape($ret["ehi_notes_url"]) . "', "
        : $rq .= "NULL, ";
    $rq .= "ehi_action_url = ";
    isset($ret["ehi_action_url"]) && $ret["ehi_action_url"] != null
        ? $rq .= "'" . CentreonDB::escape($ret["ehi_action_url"]) . "', "
        : $rq .= "NULL, ";
    $rq .= "ehi_icon_image = ";
    isset($ret["ehi_icon_image"]) && $ret["ehi_icon_image"] != null
        ? $rq .= "'" . CentreonDB::escape($ret["ehi_icon_image"]) . "', "
        : $rq .= "NULL, ";
    $rq .= "ehi_icon_image_alt = ";
    isset($ret["ehi_icon_image_alt"]) && $ret["ehi_icon_image_alt"] != null
        ? $rq .= "'" . CentreonDB::escape($ret["ehi_icon_image_alt"]) . "', "
        : $rq .= "NULL, ";
    $rq .= "ehi_statusmap_image = ";
    isset($ret["ehi_statusmap_image"]) && $ret["ehi_statusmap_image"] != null
        ? $rq .= "'" . CentreonDB::escape($ret["ehi_statusmap_image"]) . "', "
        : $rq .= "NULL, ";
    $rq .= "ehi_2d_coords = ";
    isset($ret["ehi_2d_coords"]) && $ret["ehi_2d_coords"] != null
        ? $rq .= "'" . CentreonDB::escape($ret["ehi_2d_coords"]) . "', "
        : $rq .= "NULL, ";
    $rq .= "ehi_3d_coords = ";
    isset($ret["ehi_3d_coords"]) && $ret["ehi_3d_coords"] != null
        ? $rq .= "'" . CentreonDB::escape($ret["ehi_3d_coords"]) . "' "
        : $rq .= "NULL ";
    $rq .= "WHERE host_host_id = '" . $host_id . "'";
    $dbResult = $pearDB->query($rq);
}

/**
 *
 */
function updateHostExtInfos_MC($host_id = null)
{
    global $form, $pearDB;

    if (!$host_id) {
        return;
    }

    $ret = $form->getSubmitValues();
    $rq = "UPDATE extended_host_information SET ";
    if (isset($ret["ehi_notes"]) && $ret["ehi_notes"] != null) {
        $rq .= "ehi_notes = '" . CentreonDB::escape($ret["ehi_notes"]) . "', ";
    }
    if (isset($ret["ehi_notes_url"]) && $ret["ehi_notes_url"] != null) {
        $rq .= "ehi_notes_url = '" . CentreonDB::escape($ret["ehi_notes_url"]) . "', ";
    }
    if (isset($ret["ehi_action_url"]) && $ret["ehi_action_url"] != null) {
        $rq .= "ehi_action_url = '" . CentreonDB::escape($ret["ehi_action_url"]) . "', ";
    }
    if (isset($ret["ehi_icon_image"]) && $ret["ehi_icon_image"] != null) {
        $rq .= "ehi_icon_image = '" . CentreonDB::escape($ret["ehi_icon_image"]) . "', ";
    }
    if (isset($ret["ehi_icon_image_alt"]) && $ret["ehi_icon_image_alt"] != null) {
        $rq .= "ehi_icon_image_alt = '" . CentreonDB::escape($ret["ehi_icon_image_alt"]) . "', ";
    }
    if (isset($ret["ehi_statusmap_image"]) && $ret["ehi_statusmap_image"] != null) {
        $rq .= "ehi_statusmap_image = '" . CentreonDB::escape($ret["ehi_statusmap_image"]) . "', ";
    }
    if (isset($ret["ehi_2d_coords"]) && $ret["ehi_2d_coords"] != null) {
        $rq .= "ehi_2d_coords = '" . CentreonDB::escape($ret["ehi_2d_coords"]) . "', ";
    }
    if (isset($ret["ehi_3d_coords"]) && $ret["ehi_3d_coords"] != null) {
        $rq .= "ehi_3d_coords = '" . CentreonDB::escape($ret["ehi_3d_coords"]) . "', ";
    }
    if (strcmp("UPDATE extended_host_information SET ", $rq)) {
        // Delete last ',' in request
        $rq[strlen($rq) - 2] = " ";
        $rq .= "WHERE host_host_id = '" . $host_id . "'";
        $dbResult = $pearDB->query($rq);
    }
}

/**
 *
 */
function updateHostContactGroup($host_id, $ret = array())
{
    global $form, $pearDB;

    if (!$host_id) {
        return;
    }

    $rq = "DELETE FROM contactgroup_host_relation ";
    $rq .= "WHERE host_host_id = '" . $host_id . "'";
    $dbResult = $pearDB->query($rq);

    $ret = isset($ret["host_cgs"]) ? $ret["host_cgs"] : CentreonUtils::mergeWithInitialValues($form, 'host_cgs');
    $cg = new CentreonContactgroup($pearDB);
    for ($i = 0; $i < count($ret); $i++) {
        if (!is_numeric($ret[$i])) {
            $res = $cg->insertLdapGroup($ret[$i]);
            if ($res != 0) {
                $ret[$i] = $res;
            } else {
                continue;
            }
        }
        if (isset($ret[$i]) && $ret[$i] && $ret[$i] != "") {
            $rq = "INSERT INTO contactgroup_host_relation ";
            $rq .= "(host_host_id, contactgroup_cg_id) ";
            $rq .= "VALUES ";
            $rq .= "('" . $host_id . "', '" . $ret[$i] . "')";
            $dbResult = $pearDB->query($rq);
        }
    }
}

/*
 *  Only for Nagios 3
 */

function updateHostContact($host_id, $ret = array())
{
    global $form, $pearDB;

    if (!$host_id) {
        return;
    }
    $rq = "DELETE FROM contact_host_relation ";
    $rq .= "WHERE host_host_id = '" . $host_id . "'";
    $dbResult = $pearDB->query($rq);

    $ret = isset($ret["host_cs"]) ? $ret["host_cs"] : CentreonUtils::mergeWithInitialValues($form, 'host_cs');
    for ($i = 0; $i < count($ret); $i++) {
        $rq = "INSERT INTO contact_host_relation ";
        $rq .= "(host_host_id, contact_id) ";
        $rq .= "VALUES ";
        $rq .= "('" . $host_id . "', '" . $ret[$i] . "')";
        $dbResult = $pearDB->query($rq);
    }
}

/**
 * For massive change. We just add the new list if the elem doesn't exist yet
 */
function updateHostContactGroup_MC($host_id, $ret = array())
{
    global $form, $pearDB;

    if (!$host_id) {
        return;
    }

    $rq = "SELECT * FROM contactgroup_host_relation ";
    $rq .= "WHERE host_host_id = '" . $host_id . "'";
    $dbResult = $pearDB->query($rq);
    $cgs = array();
    while ($arr = $dbResult->fetch()) {
        $cgs[$arr["contactgroup_cg_id"]] = $arr["contactgroup_cg_id"];
    }
    $ret = $form->getSubmitValue("host_cgs");
    if (is_array($ret)) {
        $cg = new CentreonContactgroup($pearDB);
        for ($i = 0; $i < count($ret); $i++) {
            if (!isset($cgs[$ret[$i]])) {
                if (!is_numeric($ret[$i])) {
                    $res = $cg->insertLdapGroup($ret[$i]);
                    if ($res != 0) {
                        $ret[$i] = $res;
                    } else {
                        continue;
                    }
                }
                if (isset($ret[$i]) && $ret[$i] && $ret[$i] != "") {
                    $rq = "INSERT INTO contactgroup_host_relation ";
                    $rq .= "(host_host_id, contactgroup_cg_id) ";
                    $rq .= "VALUES ";
                    $rq .= "('" . $host_id . "', '" . $ret[$i] . "')";
                    $dbResult = $pearDB->query($rq);
                }
            }
        }
    }
}

/**
 * For massive change. We just add the new list if the elem doesn't exist yet
 */
function updateHostContact_MC($host_id, $ret = array())
{
    global $form, $pearDB;

    if (!$host_id) {
        return;
    }

    $rq = "SELECT * FROM contact_host_relation ";
    $rq .= "WHERE host_host_id = '" . $host_id . "'";
    $dbResult = $pearDB->query($rq);
    $cs = array();
    while ($arr = $dbResult->fetch()) {
        $cs[$arr["contact_id"]] = $arr["contact_id"];
    }
    $ret = $form->getSubmitValue("host_cs");
    if (is_array($ret)) {
        for ($i = 0; $i < count($ret); $i++) {
            if (!isset($cs[$ret[$i]])) {
                $rq = "INSERT INTO contact_host_relation ";
                $rq .= "(host_host_id, contact_id) ";
                $rq .= "VALUES ";
                $rq .= "('" . $host_id . "', '" . $ret[$i] . "')";
                $dbResult = $pearDB->query($rq);
            }
        }
    }
}

/**
 *
 */
function updateHostNotifs($host_id = null, $ret = array())
{
    global $form, $pearDB;

    if (!$host_id) {
        return;
    }

    if (isset($ret["host_notifOpts"])) {
        $ret = $ret["host_notifOpts"];
    } else {
        $ret = $form->getSubmitValue("host_notifOpts");
    }

    $rq = "UPDATE host SET ";
    $rq .= "host_notification_options  = ";
    isset($ret) && $ret != null ? $rq .= "'" . implode(",", array_keys($ret)) . "' " : $rq .= "NULL ";
    $rq .= "WHERE host_id = '" . $host_id . "'";
    $dbResult = $pearDB->query($rq);
}

// For massive change. incremental mode
function updateHostNotifs_MC($host_id = null)
{
    if (!$host_id) {
        return;
    }

    global $form;
    global $pearDB;

    $rq = "SELECT host_notification_options FROM host ";
    $rq .= "WHERE host_id = '" . $host_id . "' LIMIT 1";
    $dbResult = $pearDB->query($rq);
    $host = array_map("myDecode", $dbResult->fetch());

    $ret = $form->getSubmitValue("host_notifOpts");
    if (!isset($ret) || !$ret) {
        return;
    }

    $temp = (isset($host["host_notification_options"]))
        ? $host["host_notification_options"] . "," . implode(",", array_keys($ret))
        : implode(",", array_keys($ret));

    $rq = "UPDATE host SET ";
    $rq .= "host_notification_options = '" . trim($temp, ',') . "' ";
    $rq .= "WHERE host_id = '" . $host_id . "'";
    $pearDB->query($rq);
}

function updateHostNotifOptionInterval($host_id = null, $ret = array())
{
    if (!$host_id) {
        return;
    }
    global $form;
    global $pearDB;

    if (isset($ret["host_notification_interval"])) {
        $ret = $ret["host_notification_interval"];
    } else {
        $ret = $form->getSubmitValue("host_notification_interval");
    }

    $rq = "UPDATE host SET ";
    $rq .= "host_notification_interval = ";
    isset($ret) && $ret != null ? $rq .= "'" . $ret . "' " : $rq .= "NULL ";
    $rq .= "WHERE host_id = '" . $host_id . "'";
    $dbResult = $pearDB->query($rq);
}

/**
 * For massive change. incremental mode
 */
function updateHostNotifOptionInterval_MC($host_id = null)
{
    if (!$host_id) {
        return;
    }
    global $form;
    global $pearDB;

    $ret = $form->getSubmitValue("host_notification_interval");

    if (isset($ret) && $ret != null) {
        $rq = "UPDATE host SET ";
        $rq .= "host_notification_interval = '" . $ret . "' ";
        $rq .= "WHERE host_id = '" . $host_id . "'";
        $dbResult = $pearDB->query($rq);
    }
}

function updateHostNotifOptionTimeperiod($host_id = null, $ret = array())
{
    if (!$host_id) {
        return;
    }
    global $form;
    global $pearDB;

    if (isset($ret["timeperiod_tp_id2"])) {
        $ret = $ret["timeperiod_tp_id2"];
    } else {
        $ret = $form->getSubmitValue("timeperiod_tp_id2");
    }

    $rq = "UPDATE host SET ";
    $rq .= "timeperiod_tp_id2 = ";
    isset($ret) && $ret != null ? $rq .= "'" . $ret . "' " : $rq .= "NULL ";
    $rq .= "WHERE host_id = '" . $host_id . "'";
    $dbResult = $pearDB->query($rq);
}

/**
 * For massive change. incremental mode
 */
function updateHostNotifOptionTimeperiod_MC($host_id = null)
{
    if (!$host_id) {
        return;
    }
    global $form;
    global $pearDB;

    $ret = $form->getSubmitValue("timeperiod_tp_id2");

    if (isset($ret) && $ret != null) {
        $rq = "UPDATE host SET ";
        $rq .= "timeperiod_tp_id2 = '" . $ret . "' ";
        $rq .= "WHERE host_id = '" . $host_id . "'";
        $dbResult = $pearDB->query($rq);
    }
}

function updateHostNotifOptionFirstNotificationDelay($host_id = null, $ret = array())
{
    if (!$host_id) {
        return;
    }
    global $form;
    global $pearDB;

    if (isset($ret["host_first_notification_delay"])) {
        $ret = $ret["host_first_notification_delay"];
    } else {
        $ret = $form->getSubmitValue("host_first_notification_delay");
    }


    $rq = "UPDATE host SET ";
    $rq .= "host_first_notification_delay = ";
    isset($ret) && $ret != null ? $rq .= "'" . $ret . "' " : $rq .= "NULL ";
    $rq .= "WHERE host_id = '" . $host_id . "'";
    $dbResult = $pearDB->query($rq);
}

/**
 * For massive change. incremental mode
 */
function updateHostNotifOptionFirstNotificationDelay_MC($host_id = null)
{
    if (!$host_id) {
        return;
    }

    global $form;
    global $pearDB;

    $ret = $form->getSubmitValue("host_first_notification_delay");

    if (isset($ret) && $ret != null) {
        $rq = "UPDATE host SET ";
        $rq .= "host_first_notification_delay = '" . $ret . "' ";
        $rq .= "WHERE host_id = '" . $host_id . "'";
        $dbResult = $pearDB->query($rq);
    }
}


function updateHostNotifOptionRecoveryNotificationDelay($host_id = null, $ret = array())
{
    if (!$host_id) {
        return;
    }
    global $form;
    global $pearDB;

    if (isset($ret["host_recovery_notification_delay"])) {
        $ret = $ret["host_recovery_notification_delay"];
    } else {
        $ret = $form->getSubmitValue("host_recovery_notification_delay");
    }

    if ($ret == '') {
        return;
    }
    $rq = "UPDATE host SET ";
    $rq .= "host_recovery_notification_delay = ";
    isset($ret) && $ret != null ? $rq .= "'" . $ret . "' " : $rq .= "NULL ";
    $rq .= "WHERE host_id = '" . $host_id . "'";
    $pearDB->query($rq);
}




function updateHostHostGroup($host_id, $ret = array())
{
    global $form, $pearDB;

    if (!$host_id) {
        return;
    }

    /* Special Case, delete relation between host/service, when service is linked
     * to hostgroup in escalation, dependencies.
     * Get initial Hostgroup list to make a diff after deletion
     */
    $rq = "SELECT hostgroup_hg_id FROM hostgroup_relation ";
    $rq .= "WHERE host_host_id = '" . $host_id . "'";
    $dbResult = $pearDB->query($rq);
    $hgsOLD = array();
    while ($hg = $dbResult->fetch()) {
        $hgsOLD[$hg["hostgroup_hg_id"]] = $hg["hostgroup_hg_id"];
    }

    // Get service lists linked to hostgroup
    $hgSVS = array();
    foreach ($hgsOLD as $hg) {
        $rq = "SELECT service_service_id FROM host_service_relation ";
        $rq .= "WHERE hostgroup_hg_id = '" . $hg . "' AND host_host_id IS NULL";
        $dbResult = $pearDB->query($rq);
        while ($sv = $dbResult->fetch()) {
            $hgSVS[$hg][$sv["service_service_id"]] = $sv["service_service_id"];
        }
    }

    $rq = "DELETE FROM hostgroup_relation ";
    $rq .= "WHERE host_host_id = '" . $host_id . "'";
    $dbResult = $pearDB->query($rq);
    isset($ret["host_hgs"]) ? $ret = $ret["host_hgs"] : $ret = $form->getSubmitValue("host_hgs");
    $hgsNEW = array();

    if ($ret) {
        for ($i = 0; $i < count($ret); $i++) {
            $rq = "INSERT INTO hostgroup_relation ";
            $rq .= "(hostgroup_hg_id, host_host_id) ";
            $rq .= "VALUES ";
            $rq .= "('" . $ret[$i] . "', '" . $host_id . "')";
            $dbResult = $pearDB->query($rq);
            $hgsNEW[$ret[$i]] = $ret[$i];
        }
    }

    // Special Case, delete relation between host/service,
    // when service is linked to hostgroup in escalation, dependencies
    if ($hgSVS !== []) {
        foreach ($hgsOLD as $hg) {
            if (!isset($hgsNEW[$hg])) {
                if (isset($hgSVS[$hg])) {
                    foreach ($hgSVS[$hg] as $sv) {
                        // Delete in escalation
                        $rq = "DELETE FROM escalation_service_relation ";
                        $rq .= "WHERE host_host_id = '" . $host_id . "' AND service_service_id = '" . $sv . "'";
                        $dbResult = $pearDB->query($rq);
                        // Delete in dependencies
                        $rq = "DELETE FROM dependency_serviceChild_relation ";
                        $rq .= "WHERE host_host_id = '" . $host_id . "' AND service_service_id = '" . $sv . "'";
                        $dbResult = $pearDB->query($rq);
                        $rq = "DELETE FROM dependency_serviceParent_relation ";
                        $rq .= "WHERE host_host_id = '" . $host_id . "' AND service_service_id = '" . $sv . "'";
                        $dbResult = $pearDB->query($rq);
                    }
                }
            }
        }
    }
    #
}

/**
 * For massive change. We just add the new list if the elem doesn't exist yet
 */
function updateHostHostGroup_MC($host_id, $ret = array())
{
    global $form, $pearDB;

    if (!$host_id) {
        return;
    }

    $rq = "SELECT * FROM hostgroup_relation ";
    $rq .= "WHERE host_host_id = '" . $host_id . "'";
    $dbResult = $pearDB->query($rq);
    $hgs = array();
    while ($arr = $dbResult->fetch()) {
        $hgs[$arr["hostgroup_hg_id"]] = $arr["hostgroup_hg_id"];
    }

    $ret = $form->getSubmitValue("host_hgs");
    if (is_array($ret)) {
        for ($i = 0; $i < count($ret); $i++) {
            if (!isset($hgs[$ret[$i]])) {
                $rq = "INSERT INTO hostgroup_relation ";
                $rq .= "(hostgroup_hg_id, host_host_id) ";
                $rq .= "VALUES ";
                $rq .= "('" . $ret[$i] . "', '" . $host_id . "')";
                $dbResult = $pearDB->query($rq);
            }
        }
    }
}

function updateHostHostCategory($host_id, $ret = array())
{
    global $form, $pearDB;

    if (!$host_id) {
        return;
    }

    $rq = "DELETE FROM hostcategories_relation ";
    $rq .= "WHERE host_host_id = '" . $host_id . "' ";
    $rq .= "AND NOT EXISTS(
                            SELECT hc_id
                            FROM hostcategories hc
                            WHERE hc.hc_id = hostcategories_relation.hostcategories_hc_id
                            AND hc.level IS NOT NULL) ";
    $pearDB->query($rq);

    $ret = isset($ret["host_hcs"]) ? $ret["host_hcs"] : $ret = $form->getSubmitValue("host_hcs");
    $hcsNEW = array();

    if (!$ret) {
        return;
    }

    for ($i = 0; $i < count($ret); $i++) {
        $rq = "INSERT INTO hostcategories_relation ";
        $rq .= "(hostcategories_hc_id, host_host_id) ";
        $rq .= "VALUES ";
        $rq .= "('" . $ret[$i] . "', '" . $host_id . "')";
        $pearDB->query($rq);
        $hcsNEW[$ret[$i]] = $ret[$i];
    }
}

/**
 * For massive change. We just add the new list if the elem doesn't exist yet
 */
function updateHostHostCategory_MC($host_id, $ret = array())
{
    global $form, $pearDB;

    if (!$host_id) {
        return;
    }

    $rq = "SELECT * FROM hostcategories_relation ";
    $rq .= "WHERE host_host_id = '" . $host_id . "'";
    $dbResult = $pearDB->query($rq);
    $hcs = array();
    while ($arr = $dbResult->fetch()) {
        $hcs[$arr["hostcategories_hc_id"]] = $arr["hostcategories_hc_id"];
    }
    $ret = $form->getSubmitValue("host_hcs");
    if (is_array($ret)) {
        for ($i = 0; $i < count($ret); $i++) {
            if (!isset($hcs[$ret[$i]])) {
                $rq = "INSERT INTO hostcategories_relation ";
                $rq .= "(hostcategories_hc_id, host_host_id) ";
                $rq .= "VALUES ";
                $rq .= "('" . $ret[$i] . "', '" . $host_id . "')";
                $dbResult = $pearDB->query($rq);
            }
        }
    }
}

function generateHostServiceMultiTemplate($hID, $hID2 = null, $antiLoop = null)
{
    global $pearDB, $path, $centreon;

    if (isset($antiLoop[$hID2]) && $antiLoop[$hID2]) {
        return 0;
    }

    require_once $path . "../service/DB-Func.php";

    $dbResult = $pearDB->query("SELECT host_tpl_id
                                FROM `host_template_relation`
                                WHERE host_host_id = " . $hID2 . "
                                ORDER BY `order`");
    $rq2 = "SELECT service_service_id, service_register
                FROM `host_service_relation`, service
                WHERE service_service_id = service_id
                AND host_host_id = :host_host_id";
    $hostServiceStatement = $pearDB->prepare($rq2);
    $statement = $pearDB->prepare(
        "SELECT DISTINCT servicegroup_sg_id
              FROM servicegroup_relation
              WHERE service_service_id = :service_service_id"
    );
    while ($hTpl = $dbResult->fetch()) {
        $hostServiceStatement->bindValue(':host_host_id', (int) $hTpl['host_tpl_id'], \PDO::PARAM_INT);
        $hostServiceStatement->execute();
        while (($hTpl2 = $hostServiceStatement->fetch()) !== false) {
            $alias = getMyServiceAlias($hTpl2["service_service_id"]);

            $service_sgs = array();
            $statement->bindValue(':service_service_id', (int) $hTpl2["service_service_id"], \PDO::PARAM_INT);
            $statement->execute();
            for ($i = 0; $sg = $statement->fetch(\PDO::FETCH_ASSOC); $i++) {
                $service_sgs[$i] = $sg["servicegroup_sg_id"];
            }
            $statement->closeCursor();

            if (testServiceExistence($alias, array(0 => $hID))) {
                $service = array(
                    "service_template_model_stm_id" => $hTpl2["service_service_id"],
                    "service_description" => $alias,
                    "service_register" => ($hTpl2["service_register"] + 1),
                    "service_activate" => array("service_activate" => 1),
                    "service_hPars" => array("0" => $hID),
                    "service_sgs" => $service_sgs
                );
                insertServiceInDB($service, array());
            }
        }
        $antiLoop[$hID2] = 1;
        generateHostServiceMultiTemplate($hID, $hTpl['host_tpl_id'], $antiLoop);
    }
}

function createHostTemplateService($hostId = null, $htm_id = null)
{
    global $pearDB, $path, $centreon, $form, $isCloudPlatform;

    if (! $hostId) {
        return;
    }

    /*
     * If we select a host template model,
     * we create the services linked to this host template model
     */
    $submittedValues = $form->getSubmitValues();
    if (
        ! empty($submittedValues['dupSvTplAssoc']['dupSvTplAssoc'])
        || $isCloudPlatform === true
    ) {
        generateHostServiceMultiTemplate($hostId, $hostId);
    }
}

function updateHostTemplateService($hostId = null)
{
    global $form, $pearDB, $centreon;

    if (! $hostId) {
        return;
    }

    $statement = $pearDB->query("SELECT host_register FROM host WHERE host_id = '" . $hostId . "'");
    $result = $statement->fetch();
    $ret = [];
    if ($result["host_register"] == 0) {
        $request = "DELETE FROM host_service_relation ";
        $request .= "WHERE host_host_id = '" . $hostId . "'";
        $pearDB->query($request);
        $ret = $form->getSubmitValue("host_svTpls");
        if ($ret) {
            for ($i = 0; $i < count($ret); $i++) {
                if (isset($ret[$i]) && $ret[$i] != "") {
                    $request = "INSERT INTO host_service_relation ";
                    $request .= "(hostgroup_hg_id, host_host_id, servicegroup_sg_id, service_service_id) ";
                    $request .= "VALUES ";
                    $request .= "(NULL, '" . $hostId . "', NULL, '" . $ret[$i] . "')";
                    $pearDB->query($request);
                }
            }
        }
    } elseif ($centreon->user->get_version() >= 3) {
        if (isset($ret["dupSvTplAssoc"]["dupSvTplAssoc"]) && $ret["dupSvTplAssoc"]["dupSvTplAssoc"]) {
            generateHostServiceMultiTemplate($hostId, $hostId);
        }
    }
}

function updateHostTemplateService_MC($host_id = null)
{
    global $form, $pearDB, $centreon, $path;

    if (!$host_id) {
        return;
    }
    $dbResult = $pearDB->query("SELECT host_register FROM host WHERE host_id = '" . (int)$host_id . "'");
    $row = $dbResult->fetch();
    if ($row["host_register"] == 0) {
        $dbResult2 = $pearDB->query("SELECT *
                                      FROM host_service_relation
                                      WHERE host_host_id = '" . (int)$host_id . "'");
        $svtpls = array();
        while ($arr = $dbResult2->fetch()) {
            $svtpls [$arr["service_service_id"]] = $arr["service_service_id"];
        }

        $ret = $form->getSubmitValue("host_svTpls");
        if (!empty($ret)) {
            for ($i = 0; $i < count($ret); $i++) {
                if (!isset($svtpls[$ret[$i]])) {
                    $rq = "INSERT INTO host_service_relation ";
                    $rq .= "(hostgroup_hg_id, host_host_id, servicegroup_sg_id, service_service_id) ";
                    $rq .= "VALUES ";
                    $rq .= "(NULL, '" . (int)$host_id . "', NULL, '" . $ret[$i] . "')";
                    $dbResult2 = $pearDB->query($rq);
                }
            }
        }
    } elseif ($centreon->user->get_version() >= 3) {
        if (isset($ret["dupSvTplAssoc"]["dupSvTplAssoc"]) && $ret["dupSvTplAssoc"]["dupSvTplAssoc"]) {
            generateHostServiceMultiTemplate($host_id, $host_id);
        }
    }
}

function updateHostTemplateUsed($useTpls = array())
{
    global $pearDB;

    if (!count($useTpls)) {
        return;
    }

    require_once "./include/common/common-Func.php";

    foreach ($useTpls as $key => $value) {
        $pearDB->query(
            "UPDATE host
            SET host_template_model_htm_id = '" . getMyHostID($value) . "'
            WHERE host_id = '" . $key . "'"
        );
    }
}

/**
 *
 */
function updateNagiosServerRelation($hostId, $ret = [])
{
    global $form, $pearDB;

    if (! $hostId) {
        return;
    }

    isset($ret["nagios_server_id"])
        ? $ret = $ret["nagios_server_id"]
        : $ret = $form->getSubmitValue("nagios_server_id");

    if (isset($ret) && $ret != "" && $ret != 0) {
        $pearDB->query("DELETE FROM `ns_host_relation` WHERE `host_host_id` = '" . (int) $hostId . "'");

        $request = "INSERT INTO `ns_host_relation` ";
        $request .= "(`host_host_id`, `nagios_server_id`) ";
        $request .= "VALUES ";
        $request .= "('" . (int) $hostId . "', '" . $ret . "')";

        $pearDB->query($request);
    }
}

/**
 * Inserts criticality relations
 *
 * @param int $hostId
 * @param int $criticalityId
 * @return void
 */
function setHostCriticality($hostId, $criticalityId)
{
    global $pearDB;

    $statement = $pearDB->prepare("DELETE FROM hostcategories_relation
                WHERE host_host_id = :host_host_id
                AND NOT EXISTS(
                    SELECT hc_id
                    FROM hostcategories hc
                    WHERE hc.hc_id = hostcategories_relation.hostcategories_hc_id
                    AND hc.level IS NULL)");
    $statement->bindValue(':host_host_id', (int) $hostId, \PDO::PARAM_INT);
    $statement->execute();
    if ($criticalityId) {
        $statement = $pearDB->prepare(
            "INSERT INTO hostcategories_relation (hostcategories_hc_id, host_host_id)
            VALUES (:hostcategories_hc_id, :host_host_id)"
        );
        $statement->bindValue(':hostcategories_hc_id', (int) $criticalityId, \PDO::PARAM_INT);
        $statement->bindValue(':host_host_id', (int) $hostId, \PDO::PARAM_INT);
        $statement->execute();
    }
}

/**
 * Rule for test if a ldap contactgroup name already exists
 *
 * @param array $listCgs The list of contactgroups to validate
 * @return boolean
 */
function testCg($list)
{
    return CentreonContactgroup::verifiedExists($list);
}

/**
 * Apply template in order to deploy services
 *
 * @param array $hosts
 * @return void
 */
function applytpl($hosts)
{
    global $pearDB;

    $hostObj = new CentreonHost($pearDB);

    foreach ($hosts as $key => $value) {
        $hostObj->deployServices($key);
    }
}

/**
 * Sanitize all the host parameters from the host form and return a ready to bind array.
 *
 * @param array $ret
 * @return array
 */
function sanitizeFormHostParameters(array $ret): array
{
    $bindParams = [];
    foreach ($ret as $inputName => $inputValue) {
        switch ($inputName) {
            case 'host_template_model_htm_id':
            case 'command_command_id':
            case 'timeperiod_tp_id':
            case 'timeperiod_tp_id2':
            case 'command_command_id2':
            case 'host_max_check_attempts':
            case 'host_check_interval':
            case 'host_retry_check_interval':
            case 'host_freshness_threshold':
            case 'host_low_flap_threshold':
            case 'host_high_flap_threshold':
            case 'host_notification_interval':
            case 'host_first_notification_delay':
            case 'host_recovery_notification_delay':
            case 'host_location':
            case 'host_acknowledgement_timeout':
                $bindParams[':' . $inputName] = [
                    \PDO::PARAM_INT => (filter_var($inputValue, FILTER_VALIDATE_INT) === false)
                        ? null
                        : (int) $inputValue
                ];
                break;
            case 'host_name':
                if (!empty($inputValue)) {
                    $inputValue = \HtmlAnalyzer::sanitizeAndRemoveTags($inputValue);
                    $bindParams[':' . $inputName] = [
                        \PDO::PARAM_STR => ($inputValue === '' || $inputValue === false)
                            ? null
                            : $inputValue
                    ];
                }
                break;
            case 'host_address':
            case 'command_command_id_arg1':
            case 'command_command_id_arg2':
            case 'host_alias':
            case 'host_snmp_community':
            case 'host_snmp_version':
            case 'host_comment':
            case 'geo_coords':
                $inputValue = \HtmlAnalyzer::sanitizeAndRemoveTags($inputValue);
                $bindParams[':' . $inputName] = [
                    \PDO::PARAM_STR => ($inputValue === '' || $inputValue === false)
                        ? null
                        : $inputValue
                ];
                break;
            case 'host_active_checks_enabled':
            case 'host_passive_checks_enabled':
            case 'host_checks_enabled':
            case 'host_obsess_over_host':
            case 'host_check_freshness':
            case 'host_event_handler_enabled':
            case 'host_flap_detection_enabled':
            case 'host_retain_status_information':
            case 'host_retain_nonstatus_information':
            case 'host_notifications_enabled':
                $bindParams[':' . $inputName] = [
                    \PDO::PARAM_STR => in_array($inputValue[$inputName], ['0', '1', '2'])
                        ? $inputValue[$inputName]
                        : '2'
                ];
                break;
            case 'host_notifOpts':
                if (!empty($inputValue)) {
                    $inputValue = \HtmlAnalyzer::sanitizeAndRemoveTags(
                        implode(",", array_keys($inputValue))
                    );
                    $bindParams[':host_notification_options'] = [
                        \PDO::PARAM_STR => ($inputValue === '' || $inputValue === false)
                            ? null
                            : $inputValue
                    ];
                }
                break;
            case 'contact_additive_inheritance':
            case 'cg_additive_inheritance':
                $bindParams[':' . $inputName] = [\PDO::PARAM_INT => $inputValue];
                break;
            case 'mc_contact_additive_inheritance':
            case 'mc_cg_additive_inheritance':
                if (in_array($inputValue[$inputName], ['0', '1'])) {
                    $bindParams[':' . str_replace('mc_', '', $inputName)] = [
                        \PDO::PARAM_INT => $inputValue[$inputName]
                    ];
                }
                break;
            case 'host_stalOpts':
                if (!empty($inputValue)) {
                    $inputValue = \HtmlAnalyzer::sanitizeAndRemoveTags(
                        implode(",", array_keys($inputValue))
                    );
                    $bindParams[':host_stalking_options'] = [
                        \PDO::PARAM_STR => ($inputValue === '' || $inputValue === false) ? null : $inputValue
                    ];
                }
                break;
            case 'host_register':
                $bindParams[':' . $inputName] = [
                    \PDO::PARAM_STR => in_array($inputValue, ['0', '1', '2', '3']) ? $inputValue : null
                ];
                break;
            case 'host_activate':
                $bindParams[':' . $inputName] = [
                    \PDO::PARAM_STR => in_array($inputValue[$inputName], ['0', '1', '2'])
                        ? $inputValue[$inputName]
                        : '1'
                ];
                break;
        }
    }
    return $bindParams;
}

/**
 * Create a new host from formData.
 *
 * @param array<mixed> $ret
 *
 * @return int|null
 */
function insertHostInAPI(array $ret = []): int|null
{
    global $centreon, $form, $isCloudPlatform, $basePath;

    /** @var array<string,int|string|null> $formData */
    $formData = $ret === [] ? $form->getSubmitValues() : $ret;

    try {
        $hostId = insertHostByApi($formData, $isCloudPlatform, $basePath);

        if ((int) $formData['host_register'] === 0) {
            updateHostTemplateService($hostId);
        }

        if (! $isCloudPlatform) {
            if ((int) $formData['host_register'] !== 0) {
                updateHostHostParent($hostId, $formData);
                updateHostHostChild($hostId);
            }
            updateHostContactGroup($hostId, $formData);
            updateHostContact($hostId, $formData);
        }

        if (
            ! empty($formData['dupSvTplAssoc']['dupSvTplAssoc'])
            || $isCloudPlatform === true
        ) {
            createHostTemplateService($hostId);
        }

        // Update conf change flag for poller
        signalConfigurationChange('host', $hostId);
        // Update host ACLs
        $centreon->user->access->updateACL([
            'type' => 'HOST',
            'id' => $hostId,
            'action' => 'ADD',
            'access_grp_id' => (isset($formData['acl_groups']) ? $formData['acl_groups'] : null),
        ]);
        // Insert change logs
        $fields = CentreonLogAction::prepareChanges($formData);
        $centreon->CentreonLogAction->insertLog(
            "host",
            $hostId,
            CentreonDB::escape($formData["host_name"]),
            "a",
            $fields
        );

        return ($hostId);
    } catch (\Throwable $th) {
        echo "<div class='msg' align='center'>" . _($th->getMessage()) . "</div>";

        return (null);
    }
}

/**
 * Make the API request to create a new host and return the new ID.
 *
 * @param array $formData
 * @param bool $isCloudPlatform
 * @param string $basePath
 *
 * @throws \LogicException
 * @throws \Exception
 *
 * @return int
 */
function insertHostByApi(array $formData, bool $isCloudPlatform, string $basePath): int
{
    $kernel = Kernel::createForWeb();
    /** @var Router $router */
    $router = $kernel->getContainer()->get(Router::class)
        ?? throw new LogicException('Router not found in container');
    $client = new CurlHttpClient();

    if ((int) $formData['host_register'] === 0) {
        // is template
        $payload = getPayloadForHostTemplate($isCloudPlatform, $formData);

        $url = $router->generate(
            'AddHostTemplate',
            $basePath ? ['base_uri' => $basePath] : [],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );
    } else {
        // is regular host
        $payload = getPayloadForHost($isCloudPlatform, $formData);

        $url = $router->generate(
            'AddHost',
            $basePath ? ['base_uri' => $basePath] : [],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );
    }

    $headers = [
        'Content-Type' => 'application/json',
        'Cookie' => 'PHPSESSID=' . $_COOKIE['PHPSESSID'],
    ];
    $response = $client->request(
        'POST',
        $url,
        [
            'headers' => $headers,
            'body' => json_encode($payload),
        ],
    );

    if ($response->getStatusCode() !== 201) {
        $content = json_decode($response->getContent(false));

        throw new \Exception($content->message ?? 'Unexpected return status');
    }

    $data = $response->toArray();

    /** @var array{id:int} $data */
    return $data['id'];
}

/**
 * @param bool $isCloudPlatform
 * @param array<mixed> $formData
 * @return array<string,mixed>
 */
function getPayloadForHostTemplate(bool $isCloudPlatform, array $formData): array
{
    if ($isCloudPlatform === true) {
        return [
            'name' => $formData['host_name'],
            'alias' => $formData['host_alias'] ?: null,
            'snmp_version' => $formData['host_snmp_version'] ?: null,
            'snmp_community' => $formData['host_snmp_community'] ?: null,
            'note_url' => $formData['ehi_notes_url'] ?: null,
            'note' => $formData['ehi_notes'] ?: null,
            'action_url' => $formData['ehi_action_url'] ?: null,
            'icon_id' => '' !== $formData['ehi_icon_image']
                ? (int) $formData['ehi_icon_image']
                : null,
            'timezone_id' => '' !== $formData['host_location']
                ? (int) $formData['host_location']
                : null,
            'severity_id' => '' !== $formData['criticality_id']
                ? (int) $formData['criticality_id']
                : null,
            'check_timeperiod_id' => '' !== $formData['timeperiod_tp_id']
                ? (int) $formData['timeperiod_tp_id']
                : null,
            'max_check_attempts' => '' !== $formData['host_max_check_attempts']
                ? (int) $formData['host_max_check_attempts']
                : null,
            'normal_check_interval' => '' !== $formData['host_check_interval']
                ? (int) $formData['host_check_interval']
                : null,
            'retry_check_interval' => '' !== $formData['host_retry_check_interval']
                ? (int) $formData['host_retry_check_interval']
                : null,
            'templates' => array_map(static fn(string $id): int => (int) $id, $formData['tpSelect'] ?? []),
            'categories' => array_map(static fn(string $id): int => (int) $id, $formData['host_hcs'] ?? []),
            'macros' => array_map(
                static function (int $key, string $name, string $value) use ($formData): array {
                    return [
                        'name' => $name,
                        'value' => $value,
                        'is_password' => (bool) ($formData['macroPassword'][$key] ?? false),
                        'description' => $formData["macroDescription_{$key}"],
                    ];
                },
                array_keys($formData['macroInput'] ?? []),
                $formData['macroInput'] ?? [],
                $formData['macroValue'] ?? []
            ),
        ];
    } else {
        return [
            'name' => $formData['host_name'],
            'alias' => $formData['host_alias'] ?: null,
            'snmp_version' => $formData['host_snmp_version'] ?: null,
            'snmp_community' => $formData['host_snmp_community'] ?: null,
            'note_url' => $formData['ehi_notes_url'] ?: null,
            'note' => $formData['ehi_notes'] ?: null,
            'action_url' => $formData['ehi_action_url'] ?: null,
            'icon_id' => '' !== $formData['ehi_icon_image']
                ? (int) $formData['ehi_icon_image']
                : null,
            'icon_alternative' => $formData['ehi_icon_image_alt'] ?: null,
            'comment' => $formData['host_comment'] ?: null,
            'timezone_id' => '' !== $formData['host_location']
                ? (int) $formData['host_location']
                : null,
            'severity_id' => '' !== $formData['criticality_id']
                ? (int) $formData['criticality_id']
                : null,
            'check_command_id' => '' !== $formData['command_command_id']
                ? (int) $formData['command_command_id']
                : null,
            'check_command_args' => array_values(array_filter(
                explode('!', $formData['command_command_id_arg1']),
                static fn(string $elem): bool => $elem !== ""
            )),
            'check_timeperiod_id' => '' !== $formData['timeperiod_tp_id']
                ? (int) $formData['timeperiod_tp_id']
                : null,
            'max_check_attempts' => '' !== $formData['host_max_check_attempts']
                ? (int) $formData['host_max_check_attempts']
                : null,
            'normal_check_interval' => '' !== $formData['host_check_interval']
                ? (int) $formData['host_check_interval']
                : null,
            'retry_check_interval' => '' !== $formData['host_retry_check_interval']
                ? (int) $formData['host_retry_check_interval']
                : null,
            'active_check_enabled' => (int) $formData['host_active_checks_enabled']['host_active_checks_enabled'],
            'passive_check_enabled' => (int) $formData['host_passive_checks_enabled']['host_passive_checks_enabled'],
            'low_flap_threshold' => '' !== $formData['host_low_flap_threshold']
                ? (int) $formData['host_low_flap_threshold']
                : null,
            'high_flap_threshold' => '' !== $formData['host_high_flap_threshold']
                ? (int) $formData['host_high_flap_threshold']
                : null,
            'freshness_checked' => (int) $formData['host_check_freshness']['host_check_freshness'],
            'freshness_threshold' => '' !== $formData['host_freshness_threshold']
                ? (int) $formData['host_freshness_threshold']
                : null,
            'acknowledgement_timeout' => '' !== $formData['host_acknowledgement_timeout']
                ? (int) $formData['host_acknowledgement_timeout']
                : null,
            'flap_detection_enabled' => (int) $formData['host_flap_detection_enabled']['host_flap_detection_enabled'],
            'event_handler_enabled' => (int) $formData['host_event_handler_enabled']['host_event_handler_enabled'],
            'event_handler_command_id' => '' !== $formData['command_command_id2']
                ? (int) $formData['command_command_id2']
                : null,
            'event_handler_command_args' => array_values(array_filter(
                explode('!', $formData['command_command_id_arg2']),
                static fn(string $elem): bool => $elem !== ""
            )),
            'notification_enabled' => (int) $formData['host_notifications_enabled']['host_notifications_enabled'],
            'notification_interval' => '' !== $formData['host_notification_interval'] ?
                 (int) $formData['host_notification_interval']
                 : null,
            'notification_timeperiod_id' => '' !== $formData['timeperiod_tp_id2']
                ? (int) $formData['timeperiod_tp_id2']
                : null,
            'notification_options' => HostEventConverter::toBitFlag(HostEventConverter::fromString(
                implode(',', array_keys($formData['host_notifOpts'] ?? []))
            )),
            'first_notification_delay' => '' !== $formData['host_first_notification_delay']
                ? (int) $formData['host_first_notification_delay']
                : null,
            'recovery_notification_delay' => '' !== $formData['host_recovery_notification_delay']
                ? (int) $formData['host_recovery_notification_delay']
                : null,
            'add_inherited_contact_group' => (bool) ($formData['cg_additive_inheritance'] ?? false),
            'add_inherited_contact' => (bool) ($formData['contact_additive_inheritance'] ?? false),
            'templates' => array_map(static fn(string $id): int => (int) $id, $formData['tpSelect'] ?? []),
            'categories' => array_map(static fn(string $id): int => (int) $id, $formData['host_hcs'] ?? []),
            'macros' => array_map(
                static function (int $key, string $name, string $value) use ($formData): array {
                    return [
                        'name' => $name,
                        'value' => $value,
                        'is_password' => (bool) ($formData['macroPassword'][$key] ?? false),
                        'description' => $formData["macroDescription_{$key}"],
                    ];
                },
                array_keys($formData['macroInput'] ?? []),
                $formData['macroInput'] ?? [],
                $formData['macroValue'] ?? []
            ),
        ];
    }
}

/**
 * @param bool $isCloudPlatform
 * @param array $formData
 * @return array<string,mixed>
 */
function getPayloadForHost(bool $isCloudPlatform, array $formData): array
{
    if ($isCloudPlatform === true) {
        return [
            'name' => $formData['host_name'],
            'address' => $formData['host_address'],
            'monitoring_server_id' => (int) $formData['nagios_server_id'] ?: null,
            'alias' => $formData['host_alias'] ?: null,
            'snmp_version' => $formData['host_snmp_version'] ?: null,
            'snmp_community' => $formData['host_snmp_community'] ?: null,
            'note_url' => $formData['ehi_notes_url'] ?: null,
            'note' => $formData['ehi_notes'] ?: null,
            'action_url' => $formData['ehi_action_url'] ?: null,
            'icon_id' => '' !== $formData['ehi_icon_image']
                ? (int) $formData['ehi_icon_image']
                : null,
            'geo_coords' => $formData['geo_coords'] ?: null,
            'timezone_id' => '' !== $formData['host_location']
                ? (int) $formData['host_location']
                : null,
            'severity_id' => '' !== $formData['criticality_id']
                ? (int) $formData['criticality_id']
                : null,
            'check_timeperiod_id' => '' !== $formData['timeperiod_tp_id']
                ? (int) $formData['timeperiod_tp_id']
                : null,
            'max_check_attempts' => '' !== $formData['host_max_check_attempts']
                ? (int) $formData['host_max_check_attempts']
                : null,
            'normal_check_interval' => '' !== $formData['host_check_interval']
                ? (int) $formData['host_check_interval']
                : null,
            'retry_check_interval' => '' !== $formData['host_retry_check_interval']
                ? (int) $formData['host_retry_check_interval']
                : null,
            'is_activated' => (bool) ($formData['host_activate']['host_activate'] ?: false),
            'templates' => array_map(static fn(string $id): int => (int) $id, $formData['tpSelect'] ?? []),
            'categories' => array_map(static fn(string $id): int => (int) $id, $formData['host_hcs'] ?? []),
            'groups' => array_map(static fn(string $id): int => (int) $id, $formData['host_hgs'] ?? []),
            'macros' => array_map(
                static function (int|string $key, string $name, string $value) use ($formData): array {
                    return [
                        'name' => $name,
                        'value' => $value,
                        'is_password' => (bool) ($formData['macroPassword'][$key] ?? false),
                        'description' => $formData["macroDescription_{$key}"],
                    ];
                },
                array_keys($formData['macroInput'] ?? []),
                $formData['macroInput'] ?? [],
                $formData['macroValue'] ?? []
            ),
        ];
    } else {
        return [
            'name' => $formData['host_name'],
            'address' => $formData['host_address'],
            'monitoring_server_id' => (int) $formData['nagios_server_id'] ?: null,
            'alias' => $formData['host_alias'] ?: null,
            'snmp_version' => $formData['host_snmp_version'] ?: null,
            'snmp_community' => $formData['host_snmp_community'] ?: null,
            'note_url' => $formData['ehi_notes_url'] ?: null,
            'note' => $formData['ehi_notes'] ?: null,
            'action_url' => $formData['ehi_action_url'] ?: null,
            'icon_id' => '' !== $formData['ehi_icon_image']
                ? (int) $formData['ehi_icon_image']
                : null,
            'icon_alternative' => $formData['ehi_icon_image_alt'] ?: null,
            'comment' => $formData['host_comment'] ?: null,
            'geo_coords' => $formData['geo_coords'] ?: null,
            'timezone_id' => '' !== $formData['host_location']
                ? (int) $formData['host_location']
                : null,
            'severity_id' => '' !== $formData['criticality_id']
                ? (int) $formData['criticality_id']
                : null,
            'check_command_id' => '' !== $formData['command_command_id']
                ? (int) $formData['command_command_id']
                : null,
            'check_command_args' => array_values(array_filter(
                explode('!', $formData['command_command_id_arg1']),
                static fn(string $elem): bool => $elem !== ""
            )),
            'check_timeperiod_id' => '' !== $formData['timeperiod_tp_id']
                ? (int) $formData['timeperiod_tp_id']
                : null,
            'max_check_attempts' => '' !== $formData['host_max_check_attempts']
                ? (int) $formData['host_max_check_attempts']
                : null,
            'normal_check_interval' => '' !== $formData['host_check_interval']
                ? (int) $formData['host_check_interval']
                : null,
            'retry_check_interval' => '' !== $formData['host_retry_check_interval']
                ? (int) $formData['host_retry_check_interval']
                : null,
            'active_check_enabled' => (int) $formData['host_active_checks_enabled']['host_active_checks_enabled'],
            'passive_check_enabled' => (int) $formData['host_passive_checks_enabled']['host_passive_checks_enabled'],
            'low_flap_threshold' => '' !== $formData['host_low_flap_threshold']
                ? (int) $formData['host_low_flap_threshold']
                : null,
            'high_flap_threshold' => '' !== $formData['host_high_flap_threshold']
                ? (int) $formData['host_high_flap_threshold']
                : null,
            'freshness_checked' => (int) $formData['host_check_freshness']['host_check_freshness'],
            'freshness_threshold' => '' !== $formData['host_freshness_threshold']
                ? (int) $formData['host_freshness_threshold']
                : null,
            'acknowledgement_timeout' => '' !== $formData['host_acknowledgement_timeout']
                ? (int) $formData['host_acknowledgement_timeout']
                : null,
            'flap_detection_enabled' => (int) $formData['host_flap_detection_enabled']['host_flap_detection_enabled'],
            'event_handler_enabled' => (int) $formData['host_event_handler_enabled']['host_event_handler_enabled'],
            'event_handler_command_id' => '' !== $formData['command_command_id2']
                ? (int) $formData['command_command_id2']
                : null,
            'event_handler_command_args' => array_values(array_filter(
                explode('!', $formData['command_command_id_arg2']),
                static fn(string $elem): bool => $elem !== ""
            )),
            'notification_enabled' => (int) $formData['host_notifications_enabled']['host_notifications_enabled'],
            'notification_interval' => '' !== $formData['host_notification_interval']
                ? (int) $formData['host_notification_interval']
                : null,
            'notification_timeperiod_id' => '' !== $formData['timeperiod_tp_id2']
                ? (int) $formData['timeperiod_tp_id2']
                : null,
            'notification_options' => HostEventConverter::toBitFlag(HostEventConverter::fromString(
                implode(',', array_keys($formData['host_notifOpts'] ?? []))
            )),
            'first_notification_delay' => '' !== $formData['host_first_notification_delay']
                ? (int) $formData['host_first_notification_delay']
                : null,
            'recovery_notification_delay' => '' !== $formData['host_recovery_notification_delay']
                ? (int) $formData['host_recovery_notification_delay']
                : null,
            'add_inherited_contact_group' => (bool) ($formData['cg_additive_inheritance'] ?? false),
            'add_inherited_contact' => (bool) ($formData['contact_additive_inheritance'] ?? false),
            'is_activated' => (bool) ($formData['host_activate']['host_activate'] ?: false),
            'templates' => array_map(static fn(string $id): int => (int) $id, $formData['tpSelect'] ?? []),
            'categories' => array_map(static fn(string $id): int => (int) $id, $formData['host_hcs'] ?? []),
            'groups' => array_map(static fn(string $id): int => (int) $id, $formData['host_hgs'] ?? []),
            'macros' => array_map(
                static function (int|string $key, string $name, string $value) use ($formData): array {
                    return [
                        'name' => $name,
                        'value' => $value,
                        'is_password' => (bool) ($formData['macroPassword'][$key] ?? false),
                        'description' => $formData["macroDescription_{$key}"],
                    ];
                },
                array_keys($formData['macroInput'] ?? []),
                $formData['macroInput'] ?? [],
                $formData['macroValue'] ?? []
            ),
        ];
    }
}

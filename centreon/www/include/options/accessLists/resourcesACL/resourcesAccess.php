<?php

/*
 * Copyright 2005 - 2024 Centreon
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

$aclId = filter_var(
    $_GET['acl_res_id'] ?? $_POST['acl_res_id'] ?? null,
    FILTER_VALIDATE_INT
) ?: null;

$select = filter_var_array(
    $_GET["select"] ?? $_POST["select"] ?? [],
    FILTER_VALIDATE_INT
);

$dupNbr = filter_var_array(
    $_GET["dupNbr"] ?? $_POST["dupNbr"] ?? [],
    FILTER_VALIDATE_INT
);

/* PHP functions */
require_once __DIR__ . '/DB-Func.php';
require_once "./include/common/common-Func.php";

if (isset($_POST["o1"]) && isset($_POST["o2"])) {
    if ($_POST["o1"] != "") {
        $o = $_POST["o1"];
    }
    if ($_POST["o2"] != "") {
        $o = $_POST["o2"];
    }
}

switch ($o) {
    case "a":
        require_once(__DIR__ . '/formResourcesAccess.php');
        break; #Add a LCA
    case "w":
        require_once(__DIR__ . '/formResourcesAccess.php');
        break; #Watch a LCA
    case "c":
        require_once(__DIR__ . '/formResourcesAccess.php');
        break; #Modify a LCA
    case "s":
        purgeOutdatedCSRFTokens();
        if (isCSRFTokenValid()) {
            purgeCSRFToken();
            enableLCAInDB($aclId);
        } else {
            unvalidFormMessage();
        }
        require_once(__DIR__ . '/listsResourcesAccess.php');
        break; #Activate a LCA
    case "ms":
        purgeOutdatedCSRFTokens();
        if (isCSRFTokenValid()) {
            purgeCSRFToken();
            enableLCAInDB(null, $select);
        } else {
            unvalidFormMessage();
        }
        require_once(__DIR__ . '/listsResourcesAccess.php');
        break; #Activate n LCA
    case "u":
        purgeOutdatedCSRFTokens();
        if (isCSRFTokenValid()) {
            purgeCSRFToken();
            disableLCAInDB($aclId);
        } else {
            unvalidFormMessage();
        }
        require_once(__DIR__ . '/listsResourcesAccess.php');
        break; #Desactivate a LCA
    case "mu":
        purgeOutdatedCSRFTokens();
        if (isCSRFTokenValid()) {
            purgeCSRFToken();
            disableLCAInDB(null, $select);
        } else {
            unvalidFormMessage();
        }
        require_once(__DIR__ . '/listsResourcesAccess.php');
        break; #Desactivate n LCA
    case "m":
        purgeOutdatedCSRFTokens();
        if (isCSRFTokenValid()) {
            purgeCSRFToken();
            multipleLCAInDB($select, $dupNbr);
        } else {
            unvalidFormMessage();
        }
        require_once(__DIR__ . '/listsResourcesAccess.php');
        break; #Duplicate n LCAs
    case "d":
        purgeOutdatedCSRFTokens();
        if (isCSRFTokenValid()) {
            purgeCSRFToken();
            deleteLCAInDB($select);
        } else {
            unvalidFormMessage();
        }
        require_once(__DIR__ . '/listsResourcesAccess.php');
        break; #Delete n LCAs
    default:
        require_once(__DIR__ . '/listsResourcesAccess.php');
        break;
}

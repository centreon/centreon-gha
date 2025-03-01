<?php
/*
 * Copyright 2005-2015 Centreon
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
require_once './class/centreonDuration.class.php';
require_once './class/centreonBroker.class.php';
include_once("./include/monitoring/common-Func.php");

/*
 * Path to the option dir
 */
$path = "./include/Administration/performance/";

/*
 * Set URL for search
 */
$url = "viewData.php";

/*
 * PHP functions
 */
require_once("./include/Administration/parameters/DB-Func.php");
require_once("./include/common/common-Func.php");
require_once("./class/centreonDB.class.php");

include("./include/common/autoNumLimit.php");


const REBUILD_RRD = "rg";
const STOP_REBUILD_RRD = "nrg";
const DELETE_GRAPH = "ed";
const HIDE_GRAPH = "hg";
const SHOW_GRAPH = "nhg";
const LOCK_SERVICE = "lk";
const UNLOCK_SERVICE = "nlk";

/*
 * Prepare search engine
 */
$inputGet = ['Search' => \HtmlAnalyzer::sanitizeAndRemoveTags($_GET['Search'] ?? ''), 'searchH' => \HtmlAnalyzer::sanitizeAndRemoveTags($_GET['searchH'] ?? ''), 'num' => filter_input(INPUT_GET, 'num', FILTER_SANITIZE_NUMBER_INT), 'limit' => filter_input(INPUT_GET, 'limit', FILTER_SANITIZE_NUMBER_INT), 'searchS' => \HtmlAnalyzer::sanitizeAndRemoveTags($_GET['searchS'] ?? ''), 'searchP' => \HtmlAnalyzer::sanitizeAndRemoveTags($_GET['searchP'] ?? ''), 'o' => \HtmlAnalyzer::sanitizeAndRemoveTags($_GET['o'] ?? ''), 'o1' => \HtmlAnalyzer::sanitizeAndRemoveTags($_GET['o1'] ?? ''), 'o2' => \HtmlAnalyzer::sanitizeAndRemoveTags($_GET['o2'] ?? ''), 'select' => \HtmlAnalyzer::sanitizeAndRemoveTags($_GET['select'] ?? ''), 'id' => \HtmlAnalyzer::sanitizeAndRemoveTags($_GET['id'] ?? '')];

$sanitizedPostSelect = [];
if (isset($_POST['select']) && is_array($_POST['select'])) {
    foreach ($_POST['select'] as $key => $value) {
        $sanitizedPostSelect[$key] = \HtmlAnalyzer::sanitizeAndRemoveTags($value);
    }
}

$inputPost = ['Search' => \HtmlAnalyzer::sanitizeAndRemoveTags($_POST['Search'] ?? ''), 'searchH' => \HtmlAnalyzer::sanitizeAndRemoveTags($_POST['searchH'] ?? ''), 'num' => filter_input(INPUT_POST, 'num', FILTER_SANITIZE_NUMBER_INT), 'limit' => filter_input(INPUT_POST, 'limit', FILTER_SANITIZE_NUMBER_INT), 'searchS' => \HtmlAnalyzer::sanitizeAndRemoveTags($_POST['searchS'] ?? ''), 'searchP' => \HtmlAnalyzer::sanitizeAndRemoveTags($_POST['searchP'] ?? ''), 'o' => \HtmlAnalyzer::sanitizeAndRemoveTags($_POST['o'] ?? ''), 'o1' => \HtmlAnalyzer::sanitizeAndRemoveTags($_POST['o1'] ?? ''), 'o2' => \HtmlAnalyzer::sanitizeAndRemoveTags($_POST['o2'] ?? ''), 'select' => $sanitizedPostSelect, 'id' => \HtmlAnalyzer::sanitizeAndRemoveTags($_POST['id'] ?? '')];

$inputs = [];
foreach ($inputGet as $argumentName => $argumentValue) {
    if (
        !empty($inputPost[$argumentName]) && (
            (is_array($inputPost[$argumentName]) && $inputPost[$argumentName]) ||
            (!is_array($inputPost[$argumentName]) && trim($inputPost[$argumentName]) != '')
        )
    ) {
        $inputs[$argumentName] = $inputPost[$argumentName];
    } else {
        $inputs[$argumentName] = $inputGet[$argumentName];
    }
}

$searchS = null;
$searchH = null;
$searchP = null;

if (isset($inputs['Search']) && $inputs['Search'] !== "" ) {
    $centreon->historySearch[$url] = [];
    $searchH = $inputs["searchH"];
    $centreon->historySearch[$url]["searchH"] = $searchH;
    $searchS = $inputs["searchS"];
    $centreon->historySearch[$url]["searchS"] = $searchS;
    $searchP = $inputs["searchP"];
    $centreon->historySearch[$url]["searchP"] = $searchP;
} else {
    if (isset($centreon->historySearch[$url]['searchH'])) {
        $searchH = $centreon->historySearch[$url]['searchH'];
    }
    if (isset($centreon->historySearch[$url]['searchS'])) {
        $searchS = $centreon->historySearch[$url]['searchS'];
    }
    if (isset($centreon->historySearch[$url]['searchP'])) {
        $searchP = $centreon->historySearch[$url]['searchP'];
    }
}

/* Get broker type */
$brk = new CentreonBroker($pearDB);

if ((isset($inputs["o1"]) && $inputs["o1"]) || (isset($inputs["o2"]) && $inputs["o2"])) {
    //filter integer keys
    $selected = array_filter(
        $inputs["select"],
        function ($k) {
            if (is_int($k)) {
                return $k;
            }
        },
        ARRAY_FILTER_USE_KEY
    );
    if ($inputs["o"] == REBUILD_RRD && $selected !== []) {
        foreach (array_keys($selected) as $id) {
            $DBRESULT = $pearDBO->query("UPDATE index_data SET `must_be_rebuild` = '1' WHERE id = " . $id);
        }
        $brk->reload();
    } elseif ($inputs["o"] == STOP_REBUILD_RRD && $selected !== []) {
        foreach (array_keys($selected) as $id) {
            $query = "UPDATE index_data SET `must_be_rebuild` = '0' WHERE `must_be_rebuild` = '1' AND id = " . $id;
            $pearDBO->query($query);
        }
    } elseif ($inputs["o"] == DELETE_GRAPH && $selected !== []) {
        $listMetricsToDelete = [];
        foreach (array_keys($selected) as $id) {
            $DBRESULT = $pearDBO->query("SELECT metric_id FROM metrics WHERE  `index_id` = " . $id);
            while ($metrics = $DBRESULT->fetchRow()) {
                $listMetricsToDelete[] = $metrics['metric_id'];
            }
        }
        $listMetricsToDelete = array_unique($listMetricsToDelete);
        if ($listMetricsToDelete !== []) {
            $query = "UPDATE metrics SET to_delete = 1 WHERE metric_id IN (" .
                implode(', ', $listMetricsToDelete) . ")";
            $pearDBO->query($query);
            $query = "UPDATE index_data SET to_delete = 1 WHERE id IN (" . implode(', ', array_keys($selected)) . ")";
            $pearDBO->query($query);
            $query = "DELETE FROM ods_view_details WHERE metric_id IN (" . implode(', ', $listMetricsToDelete) . ")";
            $pearDB->query($query);
            $brk->reload();
        }
    } elseif ($inputs["o"] == HIDE_GRAPH && $selected !== []) {
        foreach (array_keys($selected) as $id) {
            $DBRESULT = $pearDBO->query("UPDATE index_data SET `hidden` = '1' WHERE id = " . $id);
        }
    } elseif ($inputs["o"] == SHOW_GRAPH && $selected !== []) {
        foreach (array_keys($selected) as $id) {
            $DBRESULT = $pearDBO->query("UPDATE index_data SET `hidden` = '0' WHERE id = " . $id);
        }
    } elseif ($inputs["o"] == LOCK_SERVICE && $selected !== []) {
        foreach (array_keys($selected) as $id) {
            $DBRESULT = $pearDBO->query("UPDATE index_data SET `locked` = '1' WHERE id = " . $id);
        }
    } elseif ($inputs["o"] == UNLOCK_SERVICE && $selected !== []) {
        foreach (array_keys($selected) as $id) {
            $DBRESULT = $pearDBO->query("UPDATE index_data SET `locked` = '0' WHERE id = " . $id);
        }
    }
}

if (isset($inputs["o"]) && $inputs["o"] == "d" && isset($inputs["id"])) {
    $query = "UPDATE index_data SET `trashed` = '1' WHERE id = '" .
        htmlentities($inputs["id"], ENT_QUOTES, 'UTF-8') . "'";
    $pearDBO->query($query);
}

if (isset($inputs["o"]) && $inputs["o"] == "rb" && isset($inputs["id"])) {
    $query = "UPDATE index_data SET `must_be_rebuild` = '1' WHERE id = '" .
        htmlentities($inputs["id"], ENT_QUOTES, 'UTF-8') . "'";
    $pearDBO->query($query);
}

$search_string = "";
$extTables = "";
$queryParams = [];
if ($searchH != "" || $searchS != "" || $searchP != "") {
    if ($searchH != "") {
        $search_string .= " AND i.host_name LIKE :searchH ";
        $queryParams[':searchH'] = '%' . htmlentities($searchH, ENT_QUOTES, 'UTF-8') . '%';
    }
    if ($searchS != "") {
        $search_string .= " AND s.display_name LIKE :searchS ";
        $queryParams[':searchS'] = '%' . htmlentities($searchS, ENT_QUOTES, 'UTF-8') . '%';
    }
    if ($searchP != "") {
        /* Centron Broker */
        $extTables = "JOIN hosts h ON h.host_id = i.host_id";
        $search_string .= " AND i.host_id = h.host_id AND h.instance_id = :searchP ";
        $queryParams[':searchP'] = $searchP;
    }
}

$tab_class = ["0" => "list_one", "1" => "list_two"];
$storage_type = [0 => "RRDTool", 2 => "RRDTool & MySQL"];
$yesOrNo = [0 => "No", 1 => "Yes", 2 => "Rebuilding"];

$data = [];
$query = <<<SQL
    SELECT SQL_CALC_FOUND_ROWS DISTINCT i.* , s.display_name
    FROM index_data i
    JOIN services s
        ON i.service_id = s.service_id
    JOIN metrics m
        ON i.id = m.index_id
    {$extTables}
    WHERE i.id = m.index_id
    {$search_string}
    ORDER BY host_name, display_name
    LIMIT :offset, :limit
    SQL;

$stmt = $pearDBO->prepare($query);

$stmt->bindValue(':offset', $num * $limit, \PDO::PARAM_INT);
$stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);

// loop and inject search  values into queries
foreach ($queryParams as $param => $value) {
    $stmt->bindValue($param, $value, \PDO::PARAM_STR);
}
$stmt->execute();
$stmt2 = $pearDBO->query("SELECT FOUND_ROWS()");
$rows = $stmt2->fetchColumn();
$query = "SELECT * FROM metrics WHERE index_id = :indexId ORDER BY metric_name";
$stmt2 = $pearDBO->prepare($query);
for ($i = 0; $indexData = $stmt->fetch(\PDO::FETCH_ASSOC); $i++) {
    $stmt2->bindValue(':indexId', $indexData["id"], \PDO::PARAM_INT);
    $stmt2->execute();

    $metric = "";
    for ($im = 0; $metrics = $stmt2->fetch(\PDO::FETCH_ASSOC); $im++) {
        if ($im) {
            $metric .= " - ";
        }
        $metric .= $metrics["metric_name"];
        if (isset($metrics["unit_name"]) && $metrics["unit_name"]) {
            $metric .= "(" . $metrics["unit_name"] . ")";
        }
    }
    $indexData["metrics_name"] = $metric;
    $indexData["service_description"] = "<a href='./main.php?p=50119&o=msvc&index_id=" . $indexData["id"] . "'>" .
        $indexData["display_name"] . "</a>";

    $indexData["storage_type"] = $storage_type[$indexData["storage_type"]];
    $indexData["must_be_rebuild"] = $yesOrNo[$indexData["must_be_rebuild"]];
    $indexData["to_delete"] = $yesOrNo[$indexData["to_delete"]];
    $indexData["trashed"] = $yesOrNo[$indexData["trashed"]];
    $indexData["hidden"] = $yesOrNo[$indexData["hidden"]];

    $indexData["locked"] = isset($indexData["locked"]) ? $yesOrNo[$indexData["locked"]] : $yesOrNo[0];

    $indexData["class"] = $tab_class[$i % 2];
    $data[$i] = $indexData;
}

//select2 Poller
$poller = $searchP ?? '';
$pollerRoute = './api/internal.php?object=centreon_configuration_poller&action=list';
$attrPoller = ['datasourceOrigin' => 'ajax', 'availableDatasetRoute' => $pollerRoute, 'multiple' => false, 'defaultDataset' => $poller, 'linkedObject' => 'centreonInstance'];

include("./include/common/checkPagination.php");

// Smarty template initialization
$tpl = SmartyBC::createSmartyTemplate($path);

$form = new HTML_QuickFormCustom('form', 'POST', "?p=" . $p);

$form->addElement('select2', 'searchP', "", [], $attrPoller);

$attrBtnSuccess = ["class" => "btc bt_success", "onClick" => "window.history.replaceState('', '', '?p=" . $p . "');"];
$form->addElement('submit', 'Search', _("Search"), $attrBtnSuccess);


?>
    <script type="text/javascript">
        function setO(_i) {
            document.forms['form'].elements['o'].value = _i;
        }
    </script>
<?php
$attrs1 = ['onchange' => "javascript: " .
    "if (this.form.elements['o1'].selectedIndex == 1) {" .
    " 	setO(this.form.elements['o1'].value); submit();} " .
    "else if (this.form.elements['o1'].selectedIndex == 2) {" .
    " 	setO(this.form.elements['o1'].value); submit();} " .
    "else if (this.form.elements['o1'].selectedIndex == 3 && confirm('" .
    _('Do you confirm the deletion ?') . "')) {" .
    " 	setO(this.form.elements['o1'].value); submit();} " .
    "else if (this.form.elements['o1'].selectedIndex == 4) {" .
    " 	setO(this.form.elements['o1'].value); submit();} " .
    "else if (this.form.elements['o1'].selectedIndex == 5) {" .
    " 	setO(this.form.elements['o1'].value); submit();} " .
    "else if (this.form.elements['o1'].selectedIndex == 6) {" .
    " 	setO(this.form.elements['o1'].value); submit();} " .
    "else if (this.form.elements['o1'].selectedIndex == 7) {" .
    " 	setO(this.form.elements['o1'].value); submit();} " .
    ""];
$form->addElement('select', 'o1', null, [null => _("More actions..."), "rg" => _("Rebuild RRD Database"), "nrg" => _("Stop rebuilding RRD Databases"), "ed" => _("Delete graphs"), "hg" => _("Hide graphs of selected Services"), "nhg" => _("Stop hiding graphs of selected Services"), "lk" => _("Lock Services"), "nlk" => _("Unlock Services")], $attrs1);
$form->setDefaults(['o1' => null]);

$attrs2 = ['onchange' => "javascript: " .
    "if (this.form.elements['o2'].selectedIndex == 1) {" .
    " 	setO(this.form.elements['o2'].value); submit();} " .
    "else if (this.form.elements['o2'].selectedIndex == 2) {" .
    " 	setO(this.form.elements['o2'].value); submit();} " .
    "else if (this.form.elements['o2'].selectedIndex == 3 && confirm('" .
    _('Do you confirm the deletion ?') . "')) {" .
    " 	setO(this.form.elements['o2'].value); submit();} " .
    "else if (this.form.elements['o2'].selectedIndex == 4) {" .
    " 	setO(this.form.elements['o2'].value); submit();} " .
    "else if (this.form.elements['o2'].selectedIndex == 5) {" .
    " 	setO(this.form.elements['o2'].value); submit();} " .
    "else if (this.form.elements['o2'].selectedIndex == 6) {" .
    " 	setO(this.form.elements['o2'].value); submit();} " .
    "else if (this.form.elements['o2'].selectedIndex == 7) {" .
    " 	setO(this.form.elements['o2'].value); submit();} " .
    ""];
$form->addElement('select', 'o2', null, [null => _("More actions..."), "rg" => _("Rebuild RRD Database"), "nrg" => _("Stop rebuilding RRD Databases"), "ed" => _("Delete graphs"), "hg" => _("Hide graphs of selected Services"), "nhg" => _("Stop hiding graphs of selected Services"), "lk" => _("Lock Services"), "nlk" => _("Unlock Services")], $attrs2);
$form->setDefaults(['o2' => null]);

$o1 = $form->getElement('o1');
$o1->setValue(null);
$o1->setSelected(null);

$o2 = $form->getElement('o2');
$o2->setValue(null);
$o2->setSelected(null);

$tpl->assign('limit', $limit);

$tpl->assign("p", $p);
$tpl->assign('o', $o);
$tpl->assign("num", $num);
$tpl->assign("limit", $limit);
$tpl->assign("data", $data);
if (isset($instances)) {
    $tpl->assign("instances", $instances);
}
$tpl->assign("Host", _("Host"));
$tpl->assign("Service", _("Service"));
$tpl->assign("Metrics", _("Metrics"));
$tpl->assign("RebuildWaiting", _("Rebuild Waiting"));
$tpl->assign("Delete", _("Delete"));
$tpl->assign("Hidden", _("Hidden"));
$tpl->assign("Locked", _("Locked"));
$tpl->assign("StorageType", _("Storage Type"));
$tpl->assign("Actions", _("Actions"));

$tpl->assign('Services', _("Services"));
$tpl->assign('Hosts', _("Hosts"));
$tpl->assign('Pollers', _("Pollers"));
$tpl->assign('Search', _("Search"));

if (isset($searchH)) {
    $tpl->assign('searchH', $searchH);
}
if (isset($searchS)) {
    $tpl->assign('searchS', $searchS);
}

$renderer = new HTML_QuickForm_Renderer_ArraySmarty($tpl);
$form->accept($renderer);
$tpl->assign('form', $renderer->toArray());
$tpl->display("viewData.ihtml");

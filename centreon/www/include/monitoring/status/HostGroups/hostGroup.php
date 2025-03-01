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

include("./include/common/autoNumLimit.php");

$sort_types = !isset($_GET["sort_types"]) ? 0 : $_GET["sort_types"];
$order = !isset($_GET["order"]) ? 'ASC' : $_GET["order"];
$num = !isset($_GET["num"]) ? 0 : $_GET["num"];
$sort_type = !isset($_GET["sort_type"]) ? "hostGroup_name" : $_GET["sort_type"];

$tab_class = ["0" => "list_one", "1" => "list_two"];
$rows = 10;

include_once("./include/monitoring/status/Common/default_poller.php");
include_once($path_hg."hostGroupJS.php");

// Smarty template initialization
$tpl = SmartyBC::createSmartyTemplate($path_hg, "/templates/");

$tpl->assign("p", $p);
$tpl->assign('o', $o);
$tpl->assign("sort_types", $sort_types);
$tpl->assign("num", $num);
$tpl->assign("limit", $limit);
$tpl->assign("mon_host", _("Hosts"));
$tpl->assign("mon_status", _("Status"));
$tpl->assign("mon_ip", _("IP"));
$tpl->assign("mon_last_check", _("Last Check"));
$tpl->assign("mon_duration", _("Duration"));
$tpl->assign("mon_status_information", _("Status information"));
$tpl->assign('poller_listing', $centreon->user->access->checkAction('poller_listing'));

$form = new HTML_QuickFormCustom('select_form', 'GET', "?p=".$p);

$tpl->assign("order", strtolower($order));
$tab_order = ["sort_asc" => "sort_desc", "sort_desc" => "sort_asc"];
$tpl->assign("tab_order", $tab_order);

$tpl->assign('limit', $limit);
if (isset($_GET['searchHG'])) {
    $tpl->assign('searchHG', $_GET['searchHG']);
} else {
    $tpl->assign('searchHG', '');
}

$renderer = new HTML_QuickForm_Renderer_ArraySmarty($tpl);
$form->accept($renderer);

$tpl->assign('form', $renderer->toArray());
$tpl->display("hostGroup.ihtml");

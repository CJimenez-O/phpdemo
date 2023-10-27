<?php
// error_reporting(E_ALL);
// ini_set('display_errors', '1');
require_once('/DRBD/www/pareps/auth/auth.php');
include_once('phpMysql/dbConfig.inc.php');
/***
 * [1] 2016-07-31 TMcPherson - add Zero Pick filter
 * [2] 2016-11-02 TMcPherson - added EX% types of delv codes
 * [3] 2017-05-04 tmcpherson - update sql
 ***/

?>
<html>
<link rel="stylesheet" type="text/css" href="/assets/vendor/dhtmlxWindows/dhtmlxwindows.css">
<link rel="stylesheet" type="text/css" href="/assets/vendor/dhtmlxWindows/skins/dhtmlxwindows_dhx_skyblue.css">

<link rel="stylesheet" href="/assets/vendor/bootstrap-3.3.7/css/bootstrap.min.css"/>
<script src="/assets/vendor/jquery/jquery.min.js"></script>
<script src="/assets/vendor/bootstrap-3.3.7/js/bootstrap.min.js"></script>

<script src="/assets/vendor/dhtmlxWindows/dhtmlxcommon.js"></script>
<script src="/assets/vendor/dhtmlxWindows/dhtmlxwindows.js"></script>
<script src="/assets/vendor/dhtmlxWindows/dhtmlxcontainer.js"></script>
<script src='/assets/vendor/ajaxTrans/ajaxTrans.js'></script>
<body style='margin:0px;' onload='doLoad()'>
<script type="text/JavaScript">
    <!--
    var sURL = unescape(window.location.pathname);

    function doLoad() {
        var d = new Date();
        var curr_hour = d.getHours();
        if (curr_hour > 2 && curr_hour < 6) {
            // The timeout value should be the same as in the "refresh" meta-tag
            setTimeout("refresh()", 6000 * 1000);
        } else {
            setTimeout("refresh()", 60 * 1000);
        }
    }

    function refresh() {
        window.location.reload(true);

    }

    function updateURLParameter(url, param, paramVal) {
        var TheAnchor = null;
        var newAdditionalURL = "";
        var tempArray = url.split("?");
        var baseURL = tempArray[0];
        var additionalURL = tempArray[1];
        var temp = "";

        if (additionalURL) {
            var tmpAnchor = additionalURL.split("#");
            var TheParams = tmpAnchor[0];
            TheAnchor = tmpAnchor[1];
            if (TheAnchor) {
                additionalURL = TheParams;
            }

            tempArray = additionalURL.split("&");

            for (var i = 0; i < tempArray.length; i++) {
                if (tempArray[i].split('=')[0] != param) {
                    newAdditionalURL += temp + tempArray[i];
                    temp = "&";
                }
            }
        } else {
            var tmpAnchor = baseURL.split("#");
            var TheParams = tmpAnchor[0];
            TheAnchor = tmpAnchor[1];

            if (TheParams) {
                baseURL = TheParams;
            }
        }

        if (TheAnchor) {
            paramVal += "#" + TheAnchor;
        }

        var rows_txt = temp + "" + param + "=" + paramVal;
        return baseURL + "?" + newAdditionalURL + rows_txt;
    }

    function getboard(usr, curbrd, curzne, brnch) {
        var chbrd = document.getElementById('selbrd');
        var bd = chbrd.options[chbrd.selectedIndex].value;
        //    for (var i=0;i < chbrd.length;i++) {
        //console.log(bd);
        // 	bd = chbrd[i];
        // }
        //return;
        if (bd == curbrd) {
            brd = curbrd;
        } else {
            brd = bd;
        }
        //if (brd == 0) {brd = curbrd;}
        zne = '';
        if (document.getElementById('zonesel')) {
            var chzon = document.getElementById('zonesel');
            idx = chzon.selectedIndex;
            zn = chzon.options[idx].text;
            if (zn == curzne) {
                zne = curzne;
            } else if (idx == 0) {
                zne = '';
            } else {
                zne = zn;
            }
        }
        //if (brd == 0 && zne == 0) {
        //alert('Must choose tracker or zone');
        //} else {
        var argString = 'usr=' + usr;
        argString += '&brd=' + brd;
        argString += '&zne=' + zne;
        argString += '&brnch='+brnch;
        var ajax = new ajaxObj();
        ajax.setArgString(argString);
        ajax.setUrl('putBoard.php');
        ajax.setMethod('GET');
        ajax.setCallback(function (x) {
                //alert (x);
                checked = x;
                //alert('Changing to tracker ' +brd);
                var filter = "";
                var o = document.getElementById('dlc_filter');
                if (o) {
                    filter = o.value;
                }

                window.location.href = updateURLParameter(window.location.href, 'dlc_filter', filter);

            }
        );
        ajax.send();
        //}
    }

    //-->
</script>
<?php

/*error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once("../auth/auth.php");*/

if (!empty($_REQUEST['branch']) && ctype_digit($_REQUEST['branch'])) {
    $branch = $_REQUEST['branch'];
} else {
    $branch = 1;
}

if (!empty($_REQUEST['dlc_filter'])) {
    $dlc_filter = trim($_REQUEST['dlc_filter']);
} else {
    $dlc_filter = '';
}

$userId = $_SESSION['userId'];
/*2018-06-14 tmcpherson pull data from read-only as400*/
/*$conn = odbc_connect("AS400",'ftpusr','d9v1nc1')or die(odbc_errormsg());*/
Database::setConfig('conn', 'AS400-ro');
$conn = Database::get('conn');

if ($conn) {

    //    Include Common Function File
    require_once('function.common.php');

    //[1]+ TMcPherson
    $skip_count = 0;
    $sfbranch = "skipfilter" . $branch;
    if (isset($_SESSION[$sfbranch])) {
        $skipfilter = $_SESSION[$sfbranch];
    } else {
        $skipfilter = "n";
        $_SESSION[$sfbranch] = $skipfilter;
    }

    if (isset($_GET['sk']) and ($_GET['sk'] == 'y' or $_GET['sk'] == 'n')) {
        $skipfilter = $_GET['sk'];
        $_SESSION[$sfbranch] = $skipfilter;
    }
    //[1]- TMcPherson

    //CHAR(CURRENT_TIMESTAMP)
    require_once('helper.branch_list.php');
    date_default_timezone_set('America/New_York');
    $time_offset = get_branch_lists('time_offset');
    foreach ($time_offset as $k => $v) {
        if (in_array($branch, $v)) {
            $offset = $k;
            break;
        }
    }
    //NOTE: must be -2 hours and -3000 for dst and dst1 during DST
    //AZ is only 2 hours behind php time but 3 hours behind orders entered in NY
    //during non-DST, set to -2 hours -2000 for dst and dst1 as time dif same

    $dst = "{$offset} hours";
    $dst1 = (int)"{$offset}0000";

    //NOTE: time doesn't change in AZ
    //end of work around - change above date selections if DST should change
    $date = date('Ymd');
    $prev = date('Ymd', strtotime("-8 day"));
    $date2 = date('m/d/Y');
    $datd = (double)$date2;
    $time = date('Gis');
    $timd = (double)$time;
    $time2 = date('G:i:s', strtotime($dst));
    $time3 = date('Gis', strtotime($dst));
    $dat = (int)$date;

    if (!empty($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }

    //get zone from ip
    //No zone select except for listed IPs in zonetrak table
    $zon = '';
    $query2 = "select zone from zonetrak where bran = {$branch} and ipadd = '" . $ip . "' ";
    $contents = $conn->run($query2)->fetch(PDO::FETCH_ASSOC);
    if (!empty($contents)) {
        $zon = $contents['ZONE'];
    }
    //get selected tracker Hot, Inet, Stock
    $Board = '';
    $zone = '';
    // $query3 = "select SLCT, trim(LOC) zone from SELUSER where userid = " . $userId . " ";
    $test = '';
    /*2018-06-14 tmcpherson pull data from read-only as400*/
    if(isset($_SESSION['ptrack_board_all']) && isset($_SESSION['ptrack_board_all'][$branch])){
        //print_r($_SESSION);exit;
        if (isset($_SESSION['ptrack_board_all'][$branch]['board']) && $_SESSION['ptrack_board_all'][$branch]['board'] != '') {
            $Board = $_SESSION['ptrack_board_all'][$branch]['board'];
        }

        if (isset($_SESSION['ptrack_board_all'][$branch]['zone']) && $_SESSION['ptrack_board_all'][$branch]['zone'] != '') {
            $zone = $_SESSION['ptrack_board_all'][$branch]['zone'];
        }
    }
    $disp = "";
    switch ($Board) {
        case "1":
            $disp = "HOT";
            break;
        case "2":
            $disp = "CPU";
            break;
        case "3":
            //[2] trm 2016-11-02 $disp = "H/C/D/Q/DS";
            $disp = "H/C/D/Q/DS/EX";
            break;
        case "4":
            $disp = "INTERNET";
            break;
        case "5":
            $disp = "SHUTTLE";
            break;
        case "6":
            $disp = "OVERNIGHT";
            break;
        case "7":
            $disp = "STOCK";
            break;
        case "8":
            $disp = "SUMMARY";
            break;
        case "9":
            $disp = "BY ZONE";
            break;
        case "10":
            $disp = "BY ZONE TRANS";
            break;
        case "11":
            $disp = "AMAZON DROP";
            break;
        case "12":
            $disp = "TRANSFER";
            break;
        default:
            $disp = "ALL";
            break;
    }

    if ($disp == "ALL") {
        $checked = 'selected="selected"';
    } else {
        $checked = '';
    }
    $bstring = "Type: <select id=selbrd name=selbrd><option value=\"0\" " . $checked . ">ALL</option>";
    if ($disp == "HOT") {
        $checked = 'selected="selected"';
    } else {
        $checked = '';
    }
    //2016-06-27 TMcPherson add NIV
    //$bstring .= "<option value=\"hot\" ".$checked.">HOT-QIK</option>";
    $bstring .= "<option value=\"1\" " . $checked . ">HOT (Our Truck)</option>";
    //end add NIV
    if ($disp == "CPU") {
        $checked = 'selected="selected"';
    } else {
        $checked = '';
    }
    $bstring .= "<option value=\"2\" " . $checked . ">CPU</option>";
    /*[2]+ trm 2016-11-02
    if ($disp == "H/C/D/Q/DS") {$checked = 'selected="selected"';} else {$checked = '';}
    $bstring .= "<option value=\"h/c/d/q/ds\" ".$checked.">H/C/D/Q/DS</option>";
    */
    //if ($disp == "H/C/D/Q/DS/EX") {$checked = 'selected="selected"';} else {$checked = '';}
    //$bstring .= "<option value=\"h/c/d/q/ds/ex\" ".$checked.">H/C/D/Q/DS/EX</option>";
    //[2]-
    if ($disp == "INTERNET") {
        $checked = 'selected="selected"';
    } else {
        $checked = '';
    }
    $bstring .= "<option value=\"4\" " . $checked . ">INTERNET</option>";
    if ($disp == "SHUTTLE") {$checked = 'selected="selected"';} else {$checked = '';}
    $bstring .= "<option value=\"5\" ".$checked.">SHUTTLE</option>";
    if ($disp == "OVERNIGHT") {$checked = 'selected="selected"';} else {$checked = '';}
    $bstring .= "<option value=\"6\" ".$checked.">OVERNIGHT</option>";
    //if ($disp == "STOCK") {$checked = 'selected="selected"';} else {$checked = '';}
    //$bstring .= "<option value=\"stk\" ".$checked.">STOCK</option>";
    //if ($disp == "SUMMARY") {$checked = 'selected="selected"';} else {$checked = '';}
    //$bstring .= "<option value=\"sum\" ".$checked.">SUMMARY</option>";
    //if ($disp == "BY ZONE") {$checked = 'selected="selected"';} else {$checked = '';}
    //$bstring .= "<option value=\"zon\" ".$checked.">BY ZONE</option>";
    //if ($disp == "BY ZONE TRANS") {$checked = 'selected="selected"';} else {$checked = '';}
    //$bstring .= "<option value=\"zon\" ".$checked.">BY ZONE TRANS</option>";
    //if ($disp == "AMAZON DROP") {$checked = 'selected="selected"';} else {$checked = '';}
    //$bstring .= "<option value=\"drop\" ".$checked.">AMAZON DROP</option>";
    if ($disp == "TRANSFER") {
        $checked = 'selected="selected"';
    } else {
        $checked = '';
    }
    $bstring .= "<option value=\"12\" " . $checked . ">TRANSFERS</option>";
    $bstring .= "</select>";
    // Show Delivery code filter
    $sql = " WITH ship_methods AS (
                SELECT DISTINCT
                    ship_meth,
                    CASE
                        WHEN UPPER(TRIM(descr)) LIKE '% NIGHT ROUTE' THEN 'OVERNIGHT'
                        ELSE 'SHUTTLE'
                    END route_type
                FROM dl_rte
                WHERE
                    branch = {$branch}
            )
            SELECT ship_meth
            FROM ship_methods
            WHERE
                UPPER(route_type) = UPPER('{$disp}')";

    $contents = $conn->run($sql)->fetchAll(PDO::FETCH_ASSOC);
    $dlc_select = "";
    $valid_dlc_filter = false;

    if (!empty($contents)) {
        foreach ($contents as $cnt) {
            $ship_meth = $cnt['SHIP_METH'];

            $checked = "";
            if ($dlc_filter == $ship_meth) {
                $valid_dlc_filter = true;
                $checked = 'selected="selected"';
            }

            $dlc_select .= "    <OPTION value='{$ship_meth}' {$checked}>{$ship_meth}</OPTION>\n";
        }
    }
    // Filter selected not valid for this display type, switch back to all and do not filter the query
    if (!$valid_dlc_filter) {
        $dlc_filter = "";
    }
    if ($dlc_select != "") {
        $checked = ($dlc_filter == '' ? 'selected="selected"' : '');
        $dlc_select = "DLC: <SELECT id='dlc_filter'>
                                <OPTION value='' {$checked}>ALL</OPTION>
                                {$dlc_select}
                            </SELECT>";
    }

    //allow zone selection if not ip selected
    $zstring = '';
    if ($zon == '') {
        $zon = $zone;
        //$len = strlen($zon);
        $query1 = "select trim(SQLOCT) zones from WMOPCKD where sqobr = {$branch} group by sqloct order by sqloct ";
        $contents = $conn->run($query1)->fetchAll(PDO::FETCH_ASSOC);
        //if ($zone == '') {$zon = 'ALL';}
        if ($zone == '') {
            $zcheck = 'selected="selected"';
        } else {
            $zcheck = '';
        }
        $zstring = "Zone: <select id=zonesel name=zonesel><option value=\"all\" " . $zcheck . ">ALL</option>";
        $count = 0;
        if (!empty($contents)) {
            foreach ($contents as $cnt) {
                $count++;
                $zones = $cnt['ZONES'];
                //if (strlen($zones) == 1) {$zones = $zones . ' ';}
                if ($zone == $zones) {
                    $zcheck = 'selected="selected"';
                } else {
                    $zcheck = '';
                }
                $zstring .= "<option value='" . $zones . "' " . $zcheck . ">" . $zones . "</option>";
            }
        }
        $zstring .= "</select>";
    } else {
//$zone = '';
        $zstring = $zon;
    }


    $checked = $Board;
    //continue here with selection
    //$time2 = display_time($checked, $tdat);
    /* check DST job run status */
    $stringTable = '';
    $marginStatus = 0;
    /*if(in_array( $branch, array("62", "64", "65"))){*/
    $checkDSTJobStatus = checkDSTJobRunStatus();
    if (!empty($checkDSTJobStatus)) {
        if (isset($checkDSTJobStatus['string'])) {
            $stringTable = $checkDSTJobStatus['string'];
        }
        if (isset($checkDSTJobStatus['marginStatus'])) {
            $marginStatus = $checkDSTJobStatus['marginStatus'];
        }
    }
    /*}*/


    /* [1] TMcPherson
    $string = "<table width=100% valign='top' bgcolor='C0C0C0' border=1><tr><td></td><td width=5%>Order#</td><td width=32%>PICKS -<b>Bran {$branch}</b> $bstring $dlc_select $zstring <input type=button onclick=getboard('".$userId."','".$Board."','".$zone."'); value='GO' style=\"width:35px\" ></td><td align=center width=10%><b>$time2</b></td><td></td><td width=12%>PartNo</td><td width=6%>Picked</td><td width=6%>InStock</td><td>Rls At</td><td>Zone</td><td width=10%>Opened For</td></tr></table>";
    */

    //[1]+ TMcPherson
    $stringTable .= "<table width=100% border='0' cellspacing='0' cellpadding='0' " . ($marginStatus ? "style='margin-top:35px;'" : "") . ">";
    //$string = "<table width=100% valign='top' bgcolor='C0C0C0' border=1>";
    $stringHeader = "<thead>";
    $stringHeader .= "<tr bgcolor='#C0C0C0'>";

    if ($skipfilter == 'y') {
        $stringHeader .= "<th width=1% bgcolor='yellow'><b>S</b></th>";
    } else {
        $stringHeader .= "<th width=1%></th>";
    }
    $stringHeader .= "<th width=5%>Order#</th>";
    //$string.= "<td width=32%>PICKS -<b>Bran {$branch}</b>";
    //$stringHeader.= "<th>PICKS -<b>Bran {$branch}</b></th>";
    $stringHeader .= "<th>PICKS -<b>Bran ";
    $stringHeader .= '<button type="button" class="btn btn-info btn-sm" data-toggle="modal" data-target="#myModal">' . $branch . '</button>';
    $stringHeader .= "</th>";
    $stringHeader .= "<th>" . $bstring . " " . $dlc_select . " " . $zstring;
    $stringHeader .= "<input type=button onclick=\"getboard('" . $userId . "','" . $Board . "','" . $zone . "','".$branch."');\" value='GO'>";

    if ($skipfilter == 'y') {
        $stringHeader .= "&nbsp;<strong><a href='indexALL-NABP.php?branch=" . $branch . "&sk=n'>[Disable Skip Filter]</a></strong>";
    } else {
        $stringHeader .= "&nbsp;<strong><a href='indexALL-NABP.php?branch=" . $branch . "&sk=y'>[Only View Skips]</a></strong>";
    }

    $stringHeader .= "</td>";
    //$string.= "<td align=center width=10%><b>$time2</b></td><td width=10%>PartNo</td><td width=6%>Picked</td><td width=6%>InStock</td>";
    $stringHeader .= "<th><b>$time2</b></th><th width=10%>PartNo</th><th width=6%>Picked</th><th width=6%>InStock</th>";
    //$string.= "<td width=8%>Rls At</td><td width=8%>Zone</td><td width=9%>Opened For</td></tr></table>";
    $stringHeader .= "<th>Rls At</th><th>Zone</th><th>Opened For</th></tr>";
    //[1]- TMcPherson
    $stringHeader .= "</thead><tbody>";


    print $stringTable;
    print $stringHeader;
}
$debugQuery = '';
$dlc_where = "";

$overnightDeliveryCodes = "'R01', 'R02', 'R03', 'R04', 'R05', 'R06', 'R07', 'R08', 'R09', 'R10', 'R11', 'R12', 'R13', 'R14'";
//Make selections per tracker query:  hot,int,stk
if ($disp == 'HOT') {
    //default to this board
    if (strlen($zon) > 0) {
        $query = "SELECT DISTINCT  h.ONO, h.SACODE, c.OLCNAME, t.SQITEM, t.SQPQTY, t.SQOQTY, h.PRTTIME+" . $dst1 . " prttime, t.SQFBIN, s.SQBOH, t.SQSTAT, h.CUSTACCT, SUBSTR(t.SQLOCT,1,2),0 cnt, " . $dat . "-h.prtdate dys, h.prtdate, h.DLVRCD, x.AXPBRN, ifNull(u.sacod,'') TRAN from WMOPCKH h JOIN WMOPCKD t ON (h.ono = t.sqono and h.prtdate = t.sqdate) JOIN ORDERSTAT c ON (h.ONO = c.OLORD# and h.OWHSE = c.OLBRAN and h.custacct +paicust.cobrac_offset() = c.olcusno  AND c.OLODAT >= CURDATE() - 10 DAYS) JOIN WMOPCKDAX s ON (h.ONO = s.SQONO and h.OBR = s.SQOBR and t.SQITEM = s.SQITEM) join ARCUST r on (h.custacct = r.cmacc) join ARCAUX x on (r.cmacc=x.axacc) left join TRANUSER U on h.sacode = u.sacod where c.OLORD# not in (SELECT OLORD# FROM ORDERSTAT WHERE OLORD# = h.ONO and OLBRAN = h.OWHSE AND OLODAT >= CURDATE() - 10 DAYS AND oltran='#PP') AND (((prtdate >= $prev or (prtdate = $date and h.PRTTIME - 20000 <= $time)) and OWHSE = {$branch} and h.HOTSHOT = 'Y' AND h.DLVRCD NOT IN ('DTB') and r.CMCAT <> 'II' and trim(t.SQLOCT) = '" . $zon . "' )  or (OWHSE = {$branch} and h.sacode = u.sacod and oltran = 'INV' and PRTDATE = $date)) {$dlc_where} AND h.DLVRCD NOT IN ({$overnightDeliveryCodes})  order by prtdate, PRTTIME";
        /** tmcpherson removed per Marc T. 2019-07-24 [and (select count(*) from WMOPCKD dd where dd.sqono = h.ono) < 15] */
    } else {
        //2016-06-27 TMcPherson add NIV
        //$query = "SELECT DISTINCT  h.ONO, h.SACODE, c.OLCNAME, t.SQITEM, t.SQPQTY, t.SQOQTY, h.PRTTIME+" . $dst1 . " prttime, t.SQFBIN, s.SQBOH, t.SQSTAT, h.CUSTACCT, SUBSTR(t.SQLOCT,1,2), 0 cnt, " . $dat . "-h.prtdate dys, h.prtdate, h.DLVRCD, x.AXPBRN, ifNull(u.sacod,'') TRAN from WMOPCKH h JOIN WMOPCKD t ON (h.ono = t.sqono and h.prtdate = t.sqdate) JOIN ORDERSTAT c ON (h.ONO = c.OLORD# and h.OWHSE = c.OLBRAN and h.custacct +paicust.cobrac_offset() = c.olcusno  AND c.OLODAT >= CURDATE() - 10 DAYS) JOIN WMOPCKDAX s ON (h.ONO = s.SQONO and h.OBR = s.SQOBR and t.SQITEM = s.SQITEM) join ARCUST r on (h.custacct = r.cmacc) join ARCAUX x on (r.cmacc=x.axacc) left join TRANUSER U on h.sacode = u.sacod where (((prtdate >= $prev or (prtdate = $date and h.PRTTIME - 20000 <= $time)) and ( h.DLVRCD = 'QIK'  or h.DLVRCD = 'XCC' or h.DLVRCD = 'NXT' or h.DLVRCD = 'FDX' or h.HOTSHOT = 'Y') and OWHSE = {$branch} and r.CMCAT <> 'II' and (select count(*) from WMOPCKD dd where dd.sqono = h.ono) < 15) or (OWHSE = {$branch} and h.sacode = u.sacod and oltran = 'INV' and PRTDATE = $date)) {$dlc_where}  order by prtdate, PRTTIME";
        $query = "SELECT DISTINCT  h.ONO, h.SACODE, c.OLCNAME, t.SQITEM, t.SQPQTY, t.SQOQTY, h.PRTTIME+" . $dst1 . " prttime, t.SQFBIN, s.SQBOH, t.SQSTAT, h.CUSTACCT, SUBSTR(t.SQLOCT,1,2), 0 cnt, " . $dat . "-h.prtdate dys, h.prtdate, h.DLVRCD, x.AXPBRN, ifNull(u.sacod,'') TRAN from WMOPCKH h JOIN WMOPCKD t ON (h.ono = t.sqono and h.prtdate = t.sqdate) JOIN ORDERSTAT c ON (h.ONO = c.OLORD# and h.OWHSE = c.OLBRAN and h.custacct +paicust.cobrac_offset() = c.olcusno  AND c.OLODAT >= CURDATE() - 10 DAYS) JOIN WMOPCKDAX s ON (h.ONO = s.SQONO and h.OBR = s.SQOBR and t.SQITEM = s.SQITEM) join ARCUST r on (h.custacct = r.cmacc) join ARCAUX x on (r.cmacc=x.axacc) left join TRANUSER U on h.sacode = u.sacod where c.OLORD# not in (SELECT OLORD# FROM ORDERSTAT WHERE OLORD# = h.ONO and OLBRAN = h.OWHSE AND OLODAT >= CURDATE() - 10 DAYS AND oltran='#PP') AND (((prtdate >= $prev or (prtdate = $date and h.PRTTIME - 20000 <= $time)) and h.HOTSHOT = 'Y' AND h.DLVRCD NOT IN ('DTB') and OWHSE = {$branch} and r.CMCAT <> 'II' ) or (OWHSE = {$branch} and h.sacode = u.sacod and oltran = 'INV' and PRTDATE = $date)) {$dlc_where} AND h.DLVRCD NOT IN ({$overnightDeliveryCodes}) order by prtdate, PRTTIME";
        //end add NIV
        /** tmcpherson 2019-07-24 remove per Marc T [and (select count(*) from WMOPCKD dd where dd.sqono = h.ono) < 15] */

        //	$query = "SELECT DISTINCT  h.ONO, h.SACODE, c.OLCNAME, t.SQITEM, t.SQPQTY, t.SQOQTY, h.PRTTIME+".$dst1." prttime, t.SQFBIN, s.SQIOH, t.SQSTAT, h.CUSTACCT, SUBSTR(t.SQLOCT,1,2),(select count(sqitem) from WMOPCKD d where h.ono = d.sqono and h.prtdate = d.sqdate group by sqitem) cnt from WMOPCKH h JOIN WMOPCKD t ON (h.ono = t.sqono and h.prtdate = t.sqdate) JOIN ORDERSTAT c ON (h.ONO = c.OLORD# and h.OWHSE = c.OLBRAN and h.custacct +paicust.cobrac_offset() = c.olcusno  AND c.OLODAT >= CURDATE() - 10 DAYS) JOIN WMOPCKDAX s ON (h.ONO = s.SQONO and h.OBR = s.SQOBR and t.SQITEM = s.SQITEM) join ARCUST r on (h.custacct = r.cmacc) where prtdate = $date and h.PRTTIME - 20000 <= $time and OWHSE = {$branch} and h.HOTSHOT = 'Y' order by PRTTIME";
    }
} elseif ($disp == 'CPU') {
    //default to this board
    if (strlen($zon) > 0) {
        $query = "SELECT DISTINCT  h.ONO, h.SACODE, c.OLCNAME, t.SQITEM, t.SQPQTY, t.SQOQTY, h.PRTTIME+" . $dst1 . " prttime, t.SQFBIN, s.SQBOH, t.SQSTAT, h.CUSTACCT, SUBSTR(t.SQLOCT,1,2),0 cnt, " . $dat . "-h.prtdate dys, h.prtdate, h.DLVRCD, x.AXPBRN, ifNull(u.sacod,'') TRAN from WMOPCKH h JOIN WMOPCKD t ON (h.ono = t.sqono and h.prtdate = t.sqdate) JOIN ORDERSTAT c ON (h.ONO = c.OLORD# and h.OWHSE = c.OLBRAN and h.custacct +paicust.cobrac_offset() = c.olcusno  AND c.OLODAT >= CURDATE() - 10 DAYS) JOIN WMOPCKDAX s ON (h.ONO = s.SQONO and h.OBR = s.SQOBR and t.SQITEM = s.SQITEM) join ARCUST r on (h.custacct = r.cmacc) join ARCAUX x on (r.cmacc=x.axacc) left join TRANUSER U on h.sacode = u.sacod where c.OLORD# not in (SELECT OLORD# FROM ORDERSTAT WHERE OLORD# = h.ONO and OLBRAN = h.OWHSE AND OLODAT >= CURDATE() - 10 DAYS AND oltran='#PP') AND (((prtdate >= $prev or (prtdate = $date and h.PRTTIME - 20000 <= $time)) and OWHSE = {$branch} and (h.DLVRCD = 'CPU') and r.CMCAT <> 'II' and trim(t.SQLOCT) = '" . $zon . "' )  or (OWHSE = {$branch} and h.sacode = u.sacod and oltran = 'INV' and PRTDATE = $date)) {$dlc_where}  order by prtdate, PRTTIME";
    } else {
        $query = "SELECT DISTINCT  h.ONO, h.SACODE, c.OLCNAME, t.SQITEM, t.SQPQTY, t.SQOQTY, h.PRTTIME+" . $dst1 . " prttime, t.SQFBIN, s.SQBOH, t.SQSTAT, h.CUSTACCT, SUBSTR(t.SQLOCT,1,2), 0 cnt, " . $dat . "-h.prtdate dys, h.prtdate, h.DLVRCD, x.AXPBRN, ifNull(u.sacod,'') TRAN from WMOPCKH h JOIN WMOPCKD t ON (h.ono = t.sqono and h.prtdate = t.sqdate) JOIN ORDERSTAT c ON (h.ONO = c.OLORD# and h.OWHSE = c.OLBRAN and h.custacct +paicust.cobrac_offset() = c.olcusno  AND c.OLODAT >= CURDATE() - 10 DAYS) JOIN WMOPCKDAX s ON (h.ONO = s.SQONO and h.OBR = s.SQOBR and t.SQITEM = s.SQITEM) join ARCUST r on (h.custacct = r.cmacc) join ARCAUX x on (r.cmacc=x.axacc) left join TRANUSER U on h.sacode = u.sacod where c.OLORD# not in (SELECT OLORD# FROM ORDERSTAT WHERE OLORD# = h.ONO and OLBRAN = h.OWHSE AND OLODAT >= CURDATE() - 10 DAYS AND oltran='#PP') AND (((prtdate >= $prev or (prtdate = $date and h.PRTTIME - 20000 <= $time)) and (h.DLVRCD = 'CPU') and OWHSE = {$branch} and r.CMCAT <> 'II' ) or (OWHSE = {$branch} and h.sacode = u.sacod and oltran = 'INV' and PRTDATE = $date)) {$dlc_where}  order by prtdate, PRTTIME";

        //	$query = "SELECT DISTINCT  h.ONO, h.SACODE, c.OLCNAME, t.SQITEM, t.SQPQTY, t.SQOQTY, h.PRTTIME+".$dst1." prttime, t.SQFBIN, s.SQIOH, t.SQSTAT, h.CUSTACCT, SUBSTR(t.SQLOCT,1,2),(select count(sqitem) from WMOPCKD d where h.ono = d.sqono and h.prtdate = d.sqdate group by sqitem) cnt from WMOPCKH h JOIN WMOPCKD t ON (h.ono = t.sqono and h.prtdate = t.sqdate) JOIN ORDERSTAT c ON (h.ONO = c.OLORD# and h.OWHSE = c.OLBRAN and h.custacct +paicust.cobrac_offset() = c.olcusno  AND c.OLODAT >= CURDATE() - 10 DAYS) JOIN WMOPCKDAX s ON (h.ONO = s.SQONO and h.OBR = s.SQOBR and t.SQITEM = s.SQITEM) join ARCUST r on (h.custacct = r.cmacc) where prtdate = $date and h.PRTTIME - 20000 <= $time and OWHSE = {$branch} and h.HOTSHOT = 'Y' order by PRTTIME";
    }
//[2] trm 2016-11-02 } elseif ($disp == 'SHUTTLE' || $disp == 'OVERNIGHT' || $disp == 'H/C/D/Q/DS') {
} elseif ($disp == 'SHUTTLE' || $disp == 'OVERNIGHT' || $disp == 'H/C/D/Q/DS/EX') {
    $numeric = "SUBSTR(h.dlvrcd, 1, 1) BETWEEN '0' AND '9'
	AND SUBSTR(h.dlvrcd, 2, 1) BETWEEN '0' AND '9'
	AND SUBSTR(h.dlvrcd, 3, 1) BETWEEN '0' AND '9'";

    // [3] TMcPherson '125' to '124'
    $shuttle = "
    (
        h.dlvrcd BETWEEN '124' AND '144' or
        h.dlvrcd BETWEEN '521' AND '532' or
        h.dlvrcd BETWEEN '620' AND '657'
    )";

    $shuttleExtra = " (
                            SUBSTR(h.dlvrcd, 1, 1) IN ('T', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I')
                            AND h.dlvrcd NOT IN ('HOT')
                        )";

    $overnight = "(
        h.dlvrcd IN ({$overnightDeliveryCodes})
    )";

    if ($dlc_filter != '') {
        $dlc_where = " AND h.DLVRCD = '{$dlc_filter}' ";
    }

    if ($disp == 'SHUTTLE') {
        $dlvrcd = "({$numeric} AND {$shuttle}) OR {$shuttleExtra} OR h.DLVRCD IN ('DTB')";
        /*[2]+ trm 2016-11-02
        } elseif($disp == 'H/C/D/Q/DS') {
            $dlvrcd = "(({$numeric} AND {$shuttle}) OR h.dlvrcd IN ('HOT', 'CPU', 'DTB', 'QIK'))";
        */
    } elseif ($disp == 'H/C/D/Q/DS/EX') {
        // [3] TMcPherson updated sql
        //$dlvrcd = "(({$numeric} AND {$shuttle}) OR h.dlvrcd IN ('HOT', 'CPU', 'DTB', 'QIK') OR ( h.dlvrcd LIKE 'EX%'))";
        $dlvrcd = "(({$numeric} AND {$shuttle}) OR h.dlvrcd IN ('HOT', 'CPU', 'DTB', 'QIK') OR h.dlvrcd LIKE 'EX%' )";
        //[2]-
    } elseif ($disp == 'OVERNIGHT') {
        $dlvrcd = " {$overnight}";
    } else {
        $dlvrcd = "{$numeric} AND ({$shuttle} OR {$overnight})";
    }
    if (strlen($zon) > 0) {
        //2016-06-10 tmcpherson: $query = "SELECT DISTINCT  h.ONO, h.SACODE, c.OLCNAME, t.SQITEM, t.SQPQTY, t.SQOQTY, h.PRTTIME+".$dst1." prttime, t.SQFBIN, s.SQBOH, t.SQSTAT, h.CUSTACCT, SUBSTR(t.SQLOCT,1,2),0 cnt, ".$dat."-h.prtdate dys, h.prtdate, h.DLVRCD, x.AXPBRN, ifNull(u.sacod,'') TRAN from WMOPCKH h JOIN WMOPCKD t ON (h.ono = t.sqono and h.prtdate = t.sqdate) JOIN ORDERSTAT c ON (h.ONO = c.OLORD# and h.OWHSE = c.OLBRAN and h.custacct +paicust.cobrac_offset() = c.olcusno  AND c.OLODAT >= CURDATE() - 10 DAYS) JOIN WMOPCKDAX s ON (h.ONO = s.SQONO and h.OBR = s.SQOBR and t.SQITEM = s.SQITEM) join ARCUST r on (h.custacct = r.cmacc) join ARCAUX x on (r.cmacc=x.axacc) left join TRANUSER U on h.sacode = u.sacod where ((((prtdate >= $prev or (prtdate = $date and h.PRTTIME - 20000 <= $time)) and OWHSE = {$branch} and ({$dlvrcd}) and r.CMCAT <> 'II' and trim(t.SQLOCT) = '".$zon."' and (select count(*) from WMOPCKD dd where dd.sqono = h.ono) < 15)  or (OWHSE = {$branch} and h.sacode = u.sacod and oltran = 'INV' and PRTDATE = $date)) {$dlc_where} order by prtdate, PRTTIME";
        $query = "SELECT DISTINCT  h.ONO, h.SACODE, c.OLCNAME, t.SQITEM, t.SQPQTY, t.SQOQTY, h.PRTTIME+" . $dst1 . " prttime, t.SQFBIN, s.SQBOH, t.SQSTAT, h.CUSTACCT, SUBSTR(t.SQLOCT,1,2),0 cnt, " . $dat . "-h.prtdate dys, h.prtdate, h.DLVRCD, x.AXPBRN, ifNull(u.sacod,'') TRAN from WMOPCKH h JOIN WMOPCKD t ON (h.ono = t.sqono and h.prtdate = t.sqdate) JOIN ORDERSTAT c ON (h.ONO = c.OLORD# and h.OWHSE = c.OLBRAN and h.custacct +paicust.cobrac_offset() = c.olcusno  AND c.OLODAT >= CURDATE() - 10 DAYS) JOIN WMOPCKDAX s ON (h.ONO = s.SQONO and h.OBR = s.SQOBR and t.SQITEM = s.SQITEM) join ARCUST r on (h.custacct = r.cmacc) join ARCAUX x on (r.cmacc=x.axacc) left join TRANUSER U on h.sacode = u.sacod where c.OLORD# not in (SELECT OLORD# FROM ORDERSTAT WHERE OLORD# = h.ONO and OLBRAN = h.OWHSE AND OLODAT >= CURDATE() - 10 DAYS AND oltran='#PP') AND ((((prtdate >= $prev or (prtdate = $date and h.PRTTIME - 20000 <= $time)) and OWHSE = {$branch} and ({$dlvrcd}) and r.CMCAT <> 'II' and trim(t.SQLOCT) = '" . $zon . "' and (select count(*) from WMOPCKD dd where dd.sqono = h.ono) < 15)  or (OWHSE = {$branch} and h.sacode = u.sacod and oltran = 'INV' and PRTDATE = $date)) {$dlc_where} ) order by prtdate, PRTTIME";
    } else {
        //2016-06-10 tmcpherson: $query = "SELECT DISTINCT  h.ONO, h.SACODE, c.OLCNAME, t.SQITEM, t.SQPQTY, t.SQOQTY, h.PRTTIME+".$dst1." prttime, t.SQFBIN, s.SQBOH, t.SQSTAT, h.CUSTACCT, SUBSTR(t.SQLOCT,1,2), 0 cnt, ".$dat."-h.prtdate dys, h.prtdate, h.DLVRCD, x.AXPBRN, ifNull(u.sacod,'') TRAN from WMOPCKH h JOIN WMOPCKD t ON (h.ono = t.sqono and h.prtdate = t.sqdate) JOIN ORDERSTAT c ON (h.ONO = c.OLORD# and h.OWHSE = c.OLBRAN and h.custacct +paicust.cobrac_offset() = c.olcusno  AND c.OLODAT >= CURDATE() - 10 DAYS) JOIN WMOPCKDAX s ON (h.ONO = s.SQONO and h.OBR = s.SQOBR and t.SQITEM = s.SQITEM) join ARCUST r on (h.custacct = r.cmacc) join ARCAUX x on (r.cmacc=x.axacc) left join TRANUSER U on h.sacode = u.sacod where (((prtdate >= $prev or (prtdate = $date and h.PRTTIME - 20000 <= $time)) and (({$dlvrcd})) and OWHSE = {$branch} and r.CMCAT <> 'II' and (select count(*) from WMOPCKD dd where dd.sqono = h.ono) < 15) or (OWHSE = {$branch} and h.sacode = u.sacod and oltran = 'INV' and PRTDATE = $date)) {$dlc_where}  order by prtdate, PRTTIME";
        $query = "SELECT DISTINCT  h.ONO, h.SACODE, c.OLCNAME, t.SQITEM, t.SQPQTY, t.SQOQTY, h.PRTTIME+" . $dst1 . " prttime, t.SQFBIN, s.SQBOH, t.SQSTAT, h.CUSTACCT, SUBSTR(t.SQLOCT,1,2), 0 cnt, " . $dat . "-h.prtdate dys, h.prtdate, h.DLVRCD, x.AXPBRN, ifNull(u.sacod,'') TRAN from WMOPCKH h JOIN WMOPCKD t ON (h.ono = t.sqono and h.prtdate = t.sqdate) JOIN ORDERSTAT c ON (h.ONO = c.OLORD# and h.OWHSE = c.OLBRAN and h.custacct +paicust.cobrac_offset() = c.olcusno  AND c.OLODAT >= CURDATE() - 10 DAYS) JOIN WMOPCKDAX s ON (h.ONO = s.SQONO and h.OBR = s.SQOBR and t.SQITEM = s.SQITEM) join ARCUST r on (h.custacct = r.cmacc) join ARCAUX x on (r.cmacc=x.axacc) left join TRANUSER U on h.sacode = u.sacod where c.OLORD# not in (SELECT OLORD# FROM ORDERSTAT WHERE OLORD# = h.ONO and OLBRAN = h.OWHSE AND OLODAT >= CURDATE() - 10 DAYS AND oltran='#PP') AND (((prtdate >= $prev or (prtdate = $date and h.PRTTIME - 20000 <= $time)) and (({$dlvrcd})) and OWHSE = {$branch} and r.CMCAT <> 'II' and (select count(*) from WMOPCKD dd where dd.sqono = h.ono) < 15) or (OWHSE = {$branch} and h.sacode = u.sacod and oltran = 'INV' and PRTDATE = $date)) {$dlc_where}   order by prtdate, PRTTIME";
    }
} elseif ($disp == 'STOCK') {
    if (strlen($zon) > 0) {
        $query = "SELECT DISTINCT  h.ONO, h.SACODE, c.OLCNAME, t.SQITEM, t.SQPQTY, t.SQOQTY, h.PRTTIME+" . $dst1 . " prttime, t.SQFBIN, s.SQBOH, t.SQSTAT, h.CUSTACCT, SUBSTR(t.SQLOCT,1,2),0 cnt, " . $dat . "-h.prtdate dys, h.prtdate, h.dlvrcd, AXPBRN, '' TRAN from WMOPCKH h JOIN WMOPCKD t ON (h.ono = t.sqono and h.prtdate = t.sqdate) JOIN ORDERSTAT c ON (h.ONO = c.OLORD# and h.OWHSE = c.OLBRAN and h.custacct +paicust.cobrac_offset() = c.olcusno  AND c.OLODAT >= CURDATE() - 10 DAYS) JOIN WMOPCKDAX s ON (h.ONO = s.SQONO and h.OBR = s.SQOBR and t.SQITEM = s.SQITEM) join ARCUST r on (h.custacct = r.cmacc) join ARCAUX x on (h.custacct = x.axacc) where c.OLORD# not in (SELECT OLORD# FROM ORDERSTAT WHERE OLORD# = h.ONO and OLBRAN = h.OWHSE AND OLODAT >= CURDATE() - 10 DAYS AND oltran='#PP') AND ((prtdate >= $prev or (prtdate = $date and h.PRTTIME - 20000 <= $time)) and r.CMCAT <> 'II' and (h.CUSTACCT < 99000 or h.CUSTACCT > 99999) and OWHSE = {$branch} and ((h.dlvrcd <> 'QIK' and h.HOTSHOT = '') or (select count(*) from wmopckh hh join wmopckd dd on hh.obr = dd.sqobr and hh.ono = dd.sqono where hh.ono = h.ono) >= 15) and h.dlvrcd <> 'XXX' and trim(t.SQLOCT) = '" . $zon . "') {$dlc_where} order by prtdate, PRTTIME";
//OLD: where (prtdate >= $prev or (prtdate = $date and h.PRTTIME - 20000 <= $time)) and h.HOTSHOT = '' and r.CMCAT <> 'II' and h.CUSTACCT < 99000 and OWHSE = {$branch} and (h.dlvrd <> 'QIK' or (select count(*) from wmopckh hh join wmopckd dd on hh.obr = dd.sqobr and hh.ono = dd.sqono where hh.ono = h.ono and hh.dlvrcd = 'QIK') >= 15)
    } else {
        $query = "SELECT DISTINCT  h.ONO, h.SACODE, c.OLCNAME, t.SQITEM, t.SQPQTY, t.SQOQTY, h.PRTTIME+" . $dst1 . " prttime, t.SQFBIN, s.SQBOH, t.SQSTAT, h.CUSTACCT, SUBSTR(t.SQLOCT,1,2),0 cnt, " . $dat . "-h.prtdate dys, h.prtdate, h.dlvrcd, AXPBRN, '' TRAN from WMOPCKH h JOIN WMOPCKD t ON (h.ono = t.sqono and h.prtdate = t.sqdate) JOIN ORDERSTAT c ON (h.ONO = c.OLORD# and h.OWHSE = c.OLBRAN and h.custacct +paicust.cobrac_offset() = c.olcusno  AND c.OLODAT >= CURDATE() - 10 DAYS) JOIN WMOPCKDAX s ON (h.ONO = s.SQONO and h.OBR = s.SQOBR and t.SQITEM = s.SQITEM) join ARCUST r on (h.custacct = r.cmacc) join ARCAUX x on (h.custacct = x.axacc) where c.OLORD# not in (SELECT OLORD# FROM ORDERSTAT WHERE OLORD# = h.ONO and OLBRAN = h.OWHSE AND OLODAT >= CURDATE() - 10 DAYS AND oltran='#PP') AND ((prtdate >= $prev or (prtdate = $date and h.PRTTIME - 20000 <= $time)) and r.CMCAT <> 'II' and (h.CUSTACCT < 99000 or h.CUSTACCT > 99999) and OWHSE = {$branch} and ((h.dlvrcd <> 'QIK' and h.HOTSHOT = '') or (select count(*) from wmopckh hh join wmopckd dd on hh.obr = dd.sqobr and hh.ono = dd.sqono where hh.ono = h.ono) >= 15) and h.dlvrcd <> 'XXX') {$dlc_where} order by prtdate, PRTTIME";
    }
} elseif ($disp == 'INTERNET') {
    if (strlen($zon) > 0) {
        $query = "SELECT DISTINCT  h.ONO, h.SACODE, c.OLCNAME, t.SQITEM, t.SQPQTY, t.SQOQTY, h.PRTTIME+{$dst1} prttime, 
                  t.SQFBIN, s.SQBOH, t.SQSTAT, h.CUSTACCT, SUBSTR(t.SQLOCT,1,2), {$dat} -prtdate dys, prtdate, '' TRAN, 
                  h.DLVRCD, AXPBRN  
                from WMOPCKH h 
                JOIN WMOPCKD t ON 
                  (h.ono = t.sqono and h.prtdate = t.sqdate) 
                JOIN ORDERSTAT c ON (h.ONO = c.OLORD# and 
                     h.OWHSE = c.OLBRAN and 
                     h.custacct +paicust.cobrac_offset() = c.olcusno  AND 
                     c.OLODAT >= CURDATE() - 10 DAYS) 
                JOIN WMOPCKDAX s ON 
                     (h.ONO = s.SQONO and h.OBR = s.SQOBR and t.SQITEM = s.SQITEM) 
                JOIN ARCUST r on 
                     (h.custacct = r.cmacc) 
                JOIN ARCAUX x on 
                     (h.custacct = x.axacc) 
                WHERE c.OLORD# not in (SELECT OLORD# FROM ORDERSTAT WHERE OLORD# = h.ONO and OLBRAN = h.OWHSE AND OLODAT >= CURDATE() - 10 DAYS AND oltran='#PP') 
                    AND ((prtdate >= $prev or (prtdate = $date and h.PRTTIME - 20000 <= $time)) 
                    AND (r.CMCAT = 'II' 
                    OR h.DLVRCD in (select distinct sgf.ship_meth
                FROM SHIP_GRP SGF
                LEFT JOIN SHIP_GRP SGT ON
                    SGF.GEN_SVC = SGT.GEN_SVC
                LEFT JOIN SHIP_CODE SCD ON
                    SGF.SHIP_METH = SCD.SHIP_METH
                where SCD.carrier is NOT NULL
                  AND sgf.ship_meth NOT LIKE '?%'
                order by sgf.ship_meth asc) )
                    AND OWHSE = {$branch} 
                    AND trim(t.SQLOCT) = '{$zon}') 
                    {$dlc_where} 
                ORDER BY prtdate, PRTTIME";
    } else {
        $query = " SELECT DISTINCT  h.ONO, h.SACODE, c.OLCNAME, t.SQITEM, t.SQPQTY, t.SQOQTY, h.PRTTIME+{$dst1} prttime, 
                       t.SQFBIN, s.SQBOH, t.SQSTAT, h.CUSTACCT, SUBSTR(t.SQLOCT,1,2), {$dat}-prtdate dys, prtdate, '' TRAN, 
                       h.DLVRCD, AXPBRN  
                    FROM WMOPCKH h 
                    JOIN WMOPCKD t ON 
                       (h.ono = t.sqono and h.prtdate = t.sqdate) 
                    JOIN ORDERSTAT c ON 
                       (h.ONO = c.OLORD# 
                       and h.OWHSE = c.OLBRAN 
                       and h.custacct +paicust.cobrac_offset() = c.olcusno  
                       AND c.OLODAT >= CURDATE() - 10 DAYS) 
                    JOIN WMOPCKDAX s ON 
                       (h.ONO = s.SQONO and h.OBR = s.SQOBR and t.SQITEM = s.SQITEM) 
                    JOIN ARCUST r on 
                       (h.custacct = r.cmacc) 
                    JOIN ARCAUX x on 
                       (h.custacct = x.axacc) 
                    WHERE c.OLORD# not in (SELECT OLORD# FROM ORDERSTAT WHERE OLORD# = h.ONO and OLBRAN = h.OWHSE AND OLODAT >= CURDATE() - 10 DAYS AND oltran='#PP') 
                        AND ((prtdate >= $prev or (prtdate = $date and h.PRTTIME - 20000 <= $time)) 
                        AND (r.CMCAT = 'II' 
                        OR h.DLVRCD in (select distinct sgf.ship_meth
                    FROM SHIP_GRP SGF
                    LEFT JOIN SHIP_GRP SGT ON
                        SGF.GEN_SVC = SGT.GEN_SVC
                    LEFT JOIN SHIP_CODE SCD ON
                        SGF.SHIP_METH = SCD.SHIP_METH
                    where SCD.carrier is NOT NULL
                      AND sgf.ship_meth NOT LIKE '?%'
                    order by sgf.ship_meth asc))
                        AND OWHSE = {$branch}) {$dlc_where} 
                    ORDER BY prtdate, PRTTIME";
    }
} elseif ($disp == 'SUMMARY') {
    $query = "SELECT DISTINCT
                h.ono,
                MAX(h.sacode),
                MAX(r.cmname),
                '',
                SUM(t.sqpqty),
                SUM(t.sqoqty),
                MAX(h.prttime) + {$dst1} prttime,
                '',
                '',
                '',
                MAX(h.custacct),
                trim(t.sqloct),
                MAX({$dat} - h.prtdate) dys,
                h.prtdate,
                '' tran,
                MAX(h.dlvrcd) dlvrcd,
                MAX(axpbrn) axpbrn
            FROM wmopckh h
            JOIN wmopckd t ON
                h.ono = t.sqono AND
                h.prtdate = t.sqdate
            JOIN arcust r ON
                h.custacct = r.cmacc
            JOIN arcaux x ON
                h.custacct = x.axacc
            WHERE
                (
                    prtdate >= {$prev} OR
                    (
                        prtdate = {$date} AND
                        h.prttime - 20000 <= {$time}
                    )
                ) AND
                obr = {$branch} AND
                owhse = {$branch}
                {$dlc_where}
            GROUP BY
                h.prtdate,
                h.ono,
                TRIM(t.sqloct)
            ORDER BY
                h.prtdate,
                prttime";
} elseif ($disp == 'BY ZONE') {
    $query = "SELECT DISTINCT  '', '', 'All Parts per Zone', '', sum(t.SQPQTY), sum(t.SQOQTY),'','',0,'',0,Trim(t.SQLOCT), 0 dys, '' TRAN, '' AXPBRN from WMOPCKH h JOIN WMOPCKD t ON (h.ono = t.sqono and h.prtdate = t.sqdate)  join ARCUST r on (h.custacct = r.cmacc) where (prtdate = $date and h.PRTTIME - 20000 <= $time and obr = {$branch} and OWHSE = {$branch}) {$dlc_where} group by Trim(t.SQLOCT) order by trim(t.SQLOCT)";
} elseif ($disp == 'BY ZONE TRANS') {
    if (strlen($zon) > 0) {
        $query = "SELECT DISTINCT  '', '', 'Transfer Parts per Zone by ', h.picker, sum(t.SQPQTY), sum(t.SQOQTY),'','','','',0,max(t.SQLOCT), 0 dys, '' TRAN, '' AXPBRN from WMOPCKH h JOIN WMOPCKD t ON (h.ono = t.sqono and h.prtdate = t.sqdate)  join ARCUST r on (h.custacct = r.cmacc) where (prtdate = $date and h.PRTTIME - 20000 <= $time and obr = {$branch} and OWHSE = {$branch} and  h.custacct > 99000 and  h.custacct <= 99999 and  trim(t.SQLOCT) = '" . $zon . "') {$dlc_where} group by h.picker";
    } else {
        $query = "SELECT DISTINCT  '', '', 'Transfer Parts per Zone', '', sum(t.SQPQTY), sum(t.SQOQTY),'','','','',0,Trim(t.SQLOCT), 0 dys, '' TRAN, '' AXPBRN from WMOPCKH h JOIN WMOPCKD t ON (h.ono = t.sqono and h.prtdate = t.sqdate)  join ARCUST r on (h.custacct = r.cmacc) where (prtdate = $date and h.PRTTIME - 20000 <= $time and obr = {$branch} and OWHSE = {$branch} and h.custacct > 99000 and  h.custacct <= 99999) {$dlc_where} group by Trim(t.SQLOCT) order by trim(t.SQLOCT)";
    }
} elseif ($disp == 'TRANSFER') {
    if (strlen($zon) > 0) {
        $query = "SELECT DISTINCT  h.ONO, h.SACODE, c.OLCNAME, t.SQITEM, t.SQPQTY, t.SQOQTY, h.PRTTIME+" . $dst1 . " prttime, t.SQFBIN, s.SQBOH, t.SQSTAT, h.CUSTACCT, SUBSTR(t.SQLOCT,1,2),0 cnt, " . $dat . "-h.prtdate dys, h.prtdate, h.DLVRCD, x.AXPBRN, ifNull(u.sacod,'') TRAN from WMOPCKH h JOIN WMOPCKD t ON (h.ono = t.sqono and h.prtdate = t.sqdate) JOIN ORDERSTAT c ON (h.ONO = c.OLORD# and h.OWHSE = c.OLBRAN and h.custacct +paicust.cobrac_offset() = c.olcusno  AND c.OLODAT >= CURDATE() - 10 DAYS) JOIN WMOPCKDAX s ON (h.ONO = s.SQONO and h.OBR = s.SQOBR and t.SQITEM = s.SQITEM) join ARCUST r on (h.custacct = r.cmacc) join ARCAUX x on (r.cmacc=x.axacc) left join TRANUSER U on h.sacode = u.sacod where axddlc in (select distinct ship_meth from dl_rte r where r.branch = {$branch} AND substr(ship_meth,1,1) = 'A') AND c.OLORD# not in (SELECT OLORD# FROM ORDERSTAT WHERE OLORD# = h.ONO and OLBRAN = h.OWHSE AND OLODAT >= CURDATE() - 10 DAYS AND oltran='#PP') AND (((prtdate >= $prev or (prtdate = $date and h.PRTTIME - 20000 <= $time)) and OWHSE = {$branch} and (h.DLVRCD = 'CPU') and r.CMCAT <> 'II' and trim(t.SQLOCT) = '" . $zon . "' )  or (OWHSE = {$branch} and h.sacode = u.sacod and oltran = 'INV' and PRTDATE = $date)) {$dlc_where}  order by prtdate, PRTTIME";
    } else {
        $query = "SELECT DISTINCT  h.ONO, h.SACODE, c.OLCNAME, t.SQITEM, t.SQPQTY, t.SQOQTY, h.PRTTIME+" . $dst1 . " prttime, t.SQFBIN, s.SQBOH, t.SQSTAT, h.CUSTACCT, SUBSTR(t.SQLOCT,1,2), 0 cnt, " . $dat . "-h.prtdate dys, h.prtdate, h.DLVRCD, x.AXPBRN, ifNull(u.sacod,'') TRAN from WMOPCKH h JOIN WMOPCKD t ON (h.ono = t.sqono and h.prtdate = t.sqdate) JOIN ORDERSTAT c ON (h.ONO = c.OLORD# and h.OWHSE = c.OLBRAN and h.custacct +paicust.cobrac_offset() = c.olcusno  AND c.OLODAT >= CURDATE() - 10 DAYS) JOIN WMOPCKDAX s ON (h.ONO = s.SQONO and h.OBR = s.SQOBR and t.SQITEM = s.SQITEM) join ARCUST r on (h.custacct = r.cmacc) join ARCAUX x on (r.cmacc=x.axacc) left join TRANUSER U on h.sacode = u.sacod where axddlc in (select distinct ship_meth from dl_rte r where r.branch = {$branch} AND substr(ship_meth,1,1) = 'A') AND c.OLORD# not in (SELECT OLORD# FROM ORDERSTAT WHERE OLORD# = h.ONO and OLBRAN = h.OWHSE AND OLODAT >= CURDATE() - 10 DAYS AND oltran='#PP') AND (((prtdate >= $prev or (prtdate = $date and h.PRTTIME - 20000 <= $time)) and (h.DLVRCD = 'CPU') and OWHSE = {$branch} and r.CMCAT <> 'II' ) or (OWHSE = {$branch} and h.sacode = u.sacod and oltran = 'INV' and PRTDATE = $date)) {$dlc_where}  order by prtdate, PRTTIME";
    }
} elseif ($disp == 'ALL') {
    if (strlen($zon) > 0) {
        $query = "SELECT DISTINCT  h.ONO, h.SACODE, c.OLCNAME, t.SQITEM, t.SQPQTY, t.SQOQTY, h.PRTTIME+" . $dst1 . " prttime, t.SQFBIN, s.SQBOH, t.SQSTAT, h.CUSTACCT, SUBSTR(t.SQLOCT,1,2), " . $dat . "-prtdate dys, prtdate, '' TRAN, h.DLVRCD, AXPBRN from WMOPCKH h JOIN WMOPCKD t ON (h.ono = t.sqono and h.prtdate = t.sqdate) JOIN ORDERSTAT c ON (h.ONO = c.OLORD# and h.OWHSE = c.OLBRAN and h.custacct +paicust.cobrac_offset() = c.olcusno  AND c.OLODAT >= CURDATE() - 10 DAYS) JOIN WMOPCKDAX s ON (h.ONO = s.SQONO and h.OBR = s.SQOBR and t.SQITEM = s.SQITEM) join ARCAUX x on (h.custacct = x.axacc) where c.OLORD# not in (SELECT OLORD# FROM ORDERSTAT WHERE OLORD# = h.ONO and OLBRAN = h.OWHSE AND OLODAT >= CURDATE() - 10 DAYS AND oltran='#PP') AND ((prtdate >= $prev or (prtdate = $date and h.PRTTIME - 20000 <= $time)) and OWHSE = {$branch} and trim(t.SQLOCT) = '" . $zon . "') {$dlc_where} order by prtdate, PRTTIME";
    } else {
        $query = "SELECT DISTINCT  h.ONO, h.SACODE, c.OLCNAME, t.SQITEM, t.SQPQTY, t.SQOQTY, h.PRTTIME+" . $dst1 . " prttime, t.SQFBIN, s.SQBOH, t.SQSTAT, h.CUSTACCT, SUBSTR(t.SQLOCT,1,2), " . $dat . "-prtdate dys, prtdate, '' TRAN, h.DLVRCD, AXPBRN from WMOPCKH h JOIN WMOPCKD t ON (h.ono = t.sqono and h.prtdate = t.sqdate) JOIN ORDERSTAT c ON (h.ONO = c.OLORD# and h.OWHSE = c.OLBRAN and h.custacct +paicust.cobrac_offset() = c.olcusno  AND c.OLODAT >= CURDATE() - 10 DAYS) JOIN WMOPCKDAX s ON (h.ONO = s.SQONO and h.OBR = s.SQOBR and t.SQITEM = s.SQITEM) join ARCAUX x on (h.custacct = x.axacc) where c.OLORD# not in (SELECT OLORD# FROM ORDERSTAT WHERE OLORD# = h.ONO and OLBRAN = h.OWHSE AND OLODAT >= CURDATE() - 10 DAYS AND oltran='#PP') AND ((prtdate >= $prev or (prtdate = $date and h.PRTTIME - 20000 <= $time)) and OWHSE = {$branch}) {$dlc_where} order by prtdate, PRTTIME";
    }
}

$debugQuery = $query;

$contents = $conn->run($query)->fetchAll(PDO::FETCH_BOTH);
// $contents = odbc_exec($conn,$query) or die("<p>".odbc_errormsg());

$time_left = 0;
$time_start = 0;

//echo "<table width=100% cellspacing=0 border=0 class=\"table\">";
$i = 0;
$ptrackrowcount = 0;
if (!empty($contents)) {
    //begin loop here
    foreach ($contents as $cnt) {
        if (($disp != 'BY ZONE')) {
            $testcontents[$cnt['DLVRCD']][$i]['ono'] = $cnt['ONO'];
            $testcontents[$cnt['DLVRCD']][$i]['DLVRCD'] = $cnt['DLVRCD'];
            $testcontents[$cnt['DLVRCD']][$i]['CUSTACCT'] = $cnt['CUSTACCT'];
            $testcontents[$cnt['DLVRCD']][$i]['OLCNAME'] = $cnt['OLCNAME'];
            $testcontents[$cnt['DLVRCD']][$i]['prtdate'] = $cnt['PRTDATE'];
            $testcontents[$cnt['DLVRCD']][$i]['PRTTIME'] = $cnt['PRTTIME'];
        } else {
            $testcontents = '';
        }

        //if ($disp == 'INTERNET' || $disp == 'STOCK' && odbc_result($contents,13) >= 15 || $disp == 'HOT' && odbc_result($contents,13) < 15 || $disp == 'SUMMARY' || $disp == 'BY ZONE' || $disp == 'ALL') {
        $i++;

        /* [1]+ TMcPherson
        echo "<tr>";

        //test status value for correct graphic
        $test = substr(odbc_result($contents,10), 0, 1);
        if ($test == 'P') {
        echo "<td width=2%><IMG SRC=\"img/chkmark4.gif\" ALT=\"$test\" WIDTH=15 HEIGHT=15></td>";
        } elseif ($test == 'S') {
        echo "<td width=2%><IMG SRC=\"img/question-icon30.gif\" ALT=\"$test\" WIDTH=16 HEIGHT=16></td>";
        } else {
        echo "<td width=2% align=center><IMG SRC=\"img/RED_DOT.gif\" ALT=\"$test\" WIDTH=9 HEIGHT=9></td>";
        }
        //[1]- TMcPherson */

        //[1]+ TMcPherson
        //test status value for correct graphic
        $test = substr($cnt[9], 0, 1);


        switch ($test) {
            case "P":
                $status_image = "img/chkmark4.gif";
                $status_alt = $test;
                $status_image_size = "15";
                break;
            case "S":
                $status_image = "img/question-icon30.gif";
                $status_alt = $test;
                $status_image_size = "16";
                $skip_count++;
                break;
            default:
                $status_image = "img/RED_DOT.gif";
                $status_alt = $test;
                $status_image_size = "9";
                break;
        }


        if ($skipfilter == 'y' and $status_image != "img/question-icon30.gif") continue;

        echo "<tr>";
        echo "<td width=2%><IMG SRC='" . $status_image . "' ALT='" . $status_alt . "' WIDTH='" . $status_image_size . "' HEIGHT='" . $status_image_size . "' ></td>";
        //[1]- TMcPherson


        //echo "<td color='Green' width=2%\><b>".$test ."</b></td>";

        //make color blue or red
        $dlvcd = '';
        if ($disp != 'SUMMARY' && $disp != 'BY ZONE' && $disp != 'BY ZONE TRANS') {
            $sacod = $cnt['SACODE'];
            $dlvcd = $cnt['DLVRCD'];
        }
        $pbrn = '';
        if ($disp == 'HOT') {
            $pbrn = $cnt['AXPBRN'];
        }
        $timstar = $cnt[6];
        $tran = $cnt['TRAN'];
        $days = 0;
        if ($disp != 'BY ZONE') {
            $days = $cnt['DYS'];
            //$timstar = $timstar + 240000;
        }
        if ($timstar != '') {
            $secs_left = sub_secs($time3, $timstar);
            if ($days > 0) {
                //fucia
                $color = '#FF00FF';
            } elseif ($secs_left >= 660) {
                //red
                if ($dlvcd == 'QIK' || $pbrn == 32) {
                    $color = "FFD700";
                } else if ($dlvcd == 'CPU') {
                    $color = "#DA458F";
                } else {
                    $color = "FF3333";
                }
            } else {
                //blue 6699CC
                if ($dlvcd == 'QIK' || $pbrn == 32) {
                    $color = 'lawngreen';
                } else {
                    $color = "lightsteelblue";
                }
            }
        } else {
            $color = "lightgreen";
        }
        //check if transfer
        if ($tran != '') {
            $color = "yellow";
        }
        //special color override for stock orders STK
        /*
        if ($disp == 'STOCK' and $dlvcd == 'STK' and $sacod != '><' and $sacod != '/\\' and $sacod != ')(') {
            $color = "FFD700";
        }
        */
        $Zone = $cnt[7];
        $Zn = substr($cnt[7], 0, 1);
        $Zon = $cnt[11];
        switch (trim($Zn)) {
            case 'A':
                //Green
                $Pick = "<FONT COLOR=\"#006400\">" . $Zon . " " . $Zone . "</FONT>";
                break;
            case 'B':
                //Yellow
                $Pick = "<FONT COLOR=\"#4B0082\">" . $Zon . " " . $Zone . "</FONT>";
                break;
            case 'E':
                //Pink
                $Pick = "<FONT COLOR=\"#8B0000\">" . $Zon . " " . $Zone . "</FONT>";
                break;
            case 'M':
                //Lavender
                $Pick = "<FONT COLOR=\"#8B008B\">" . $Zon . " " . $Zone . "</FONT>";
                break;
            default:
                //Blue
                $Pick = "<FONT COLOR=\"#000000\">" . $Zon . " " . $Zone . "</FONT>";
        }

        $acc = $cnt[10];
        if ($acc == '0' || substr($acc, 0, 2) == '99') {
            $cust = $cnt[2];
        } else {
            $cust = $acc . " : " . $cnt[2] . "  -PB(" . $cnt['AXPBRN'] . ")";
        }
        if ($timstar != '') {
            $time_start = format_time($timstar);
            //$time_left = format_secs($secs_left);
            $time_left = format_time(secs_to_time($secs_left));
        } else {
            $time_start = '';
            $time_left = '';
        }
        if ($disp != 'BY ZONE' && $days > 0) {
            //if ($days == 1) {
            //$time_left = $days . " day ago";
            //} else {
            //$time_left = $days . " days ago";
            //}
            $time_left = "over 24 hours";
        }

        echo "<td bgcolor='$color' height=1><B>" . $cnt[0] . "</B></td>";
        echo "<td bgcolor='$color' height=1><B>" . $cnt[1] . (!empty($dlvcd) ? "&nbsp;&nbsp;{$dlvcd}" : "") . "</B></td>";
        //	echo "<td width=36% bgcolor='$color' height=1><B>" .odbc_result($contents,3). "</B></td>";
        echo "<td colspan='2' bgcolor='$color' height=1><B>" . $cust . "</B></td>";
        if ($disp != 'BY ZONE') {
            echo "<td bgcolor='$color' height=1><B>" . $cnt[3] . "</B></td>";
            echo "<td bgcolor='$color' height=1><B>" . $cnt[4] . " of " . $cnt[5] . "</B></td>";
        } else {
            echo "<td bgcolor='$color' height=1><B>" . $cnt[3] . "</B></td>";
            echo "<td bgcolor='$color' height=1><B>" . $cnt[4] . " of " . $cnt[5] . "</B></td>";
        }
        echo "<td bgcolor='$color' height=1><B>" . number_format($cnt[8], 0, '', ',') . "</B></td>";
        echo "<td bgcolor='$color' height=1><B>" . $time_start . "</B></td>";
        echo "<td height=1><B>" . $Pick . "</B></td>";
        //echo "<td width=9% bgcolor='$color' height=1><B>" .odbc_result($contents,8). "</B></td>";
        echo "<td bgcolor='$color' height=1><B>" . $time_left . "</B></td>";
        echo "</tr>";
        //}
        $ptrackrowcount++;
    }
}

/* [1]+ TMcPherson
if ($i == 0) {
    echo "<td> </td><td> </td><td></td><td></td><td bgcolor='lightsteelblue'><B>Nothing Slow at the moment<B></td>";
}
    [1]- TMcPherson */


//[1]+ TMcPherson
if ($i == 0) {
    echo "<td colspan='11' bgcolor='lightsteelblue'><B>Nothing: Slow at the moment</B></td>";
} elseif ($i > 0 and $skip_count == 0 and $skipfilter == 'y') {
    echo "<td colspan='11' bgcolor='lightsteelblue'><B>No skips at the moment</B></td>";
}
//[1]- TMcPherson

echo "</tbody>";
echo "</table>";

//[4] tmcpherson show debug info
if (!empty($_SESSION['userId']) && (in_array($_SESSION['userId'], [2408, 4688, 3820, 7806, 9461, 9827]))) {
    echo "<hr>";
    echo "<table>";
    echo "<tr><td>";
    echo "SQL:<pre><textarea cols='80' rows='20'>";
    echo $query;
    echo "</textarea></pre>";
    echo "</td><td>";
    echo "Results:<pre><textarea cols='80' rows='20'>";
    @print_r($testcontents);
    echo "</textarea></pre>";
    echo "</td>";
    echo "<td><table>";
    echo "<tr><td><pre>";
    print_r($_SESSION['ptrack_board_all'] ?? []);
    echo "</pre></td></tr></table>";
    echo "</td></tr>";
    echo "</table>";
} //end debug info


//odbc_free_query($query); // odbc_free_query() doesn't exist
//odbc_free_result($contents); // not really necessary


?>
<!-- Modal -->
<div class="modal fade" id="myModal" role="dialog">
    <div class="modal-dialog">
        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Pick A Branch</h4>
            </div>
            <div class="modal-body">
                <?php print getBrans(); ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>

    </div>
    <?php
    function getBrans()
    {
        $branch_lists = get_branch_lists();
        sort($branch_lists->wms_location);
        $string = "<table class=\"table\"><tr>";
        $colcount = 0;
        $brachAry = [700, 701];
        for ($i = 0; $i < count($brachAry); $i++) {
            $string .= "<td align='center'><a href='indexALL-NABP.php?branch=" . $brachAry[$i] . "' class=\"btn btn-info\" role=\"button\">" . $brachAry[$i] . "</a></td>";
            $colcount++;
            if ($colcount == 6) {
                $string .= "</tr><tr>";
                $colcount = 0;
            }
        }
        $string .= "</tr></table>";


        return ($string);
    }

    function format_time($gettime)
    {
        //test length and return formatted time
        $length = strlen((string)$gettime);
        $time = '';
        switch ($length) {
            case 6:
                $time = substr($gettime, 0, 2) . ":" . substr($gettime, 2, 2) . ":" . substr($gettime, 4, 2);
                break;
            case 5:
                $time = substr($gettime, 0, 1) . ":" . substr($gettime, 1, 2) . ":" . substr($gettime, 3, 2);
                break;
            case 4:
                $time = substr($gettime, 0, 2) . ":" . substr($gettime, 2, 2);
                break;
            case 3:
                $time = substr($gettime, 0, 1) . ":" . substr($gettime, 1, 2);
                break;
            case 2:
                $time = ":" . substr($gettime, 0, 2);
                break;
            case 1:
                $time = ":0" . substr($gettime, 0, 1);
                break;
        }
        return $time;
    }

    function format_secs($gettime)
    {
        //converts secs to time format
        $hour = (int)($gettime / 3600);
        $min = (int)(($gettime - ($hour * 3600)) / 60);
        $sec = ($gettime - ($min * 60));
        $time = format_time($hour * 10000 + $min * 100 + $sec);
        return $time;
    }

    function sub_secs($current, $order)
    {
        //subtracts two time values to return seconds difference
        // first convert everthing to secs
        $curnew = 0;
        $curtime = (double)($current);
        $ordtime = (double)($order);
        $curnew = (int)($curtime / 10000) * 3600 + (int)(($curtime % 10000) / 100) * 60 + $curtime % 100;
        $ordnew = (int)($ordtime / 10000) * 3600 + (int)(($ordtime % 10000) / 100) * 60 + $ordtime % 100;
        //$time_diff = $curnew - $ordnew;
        $time_diff = $curnew - $ordnew;
        return $time_diff;
    }

    function secs_to_time($seconds)
    {
        //translate secs into time: hhmmss
        $hours = (int)($seconds / 3600);
        $mins = (int)(($seconds - ($hours * 3600)) / 60);
        $secs = $seconds - ($hours * 3600) - ($mins * 60);
        $timeback = $hours * 10000 + $mins * 100 + $secs;
        return $timeback;
    }

    ?>
</div>
<?php 
/*
<!-- Temp code to Print Query on QA server for Debugging -->
<div style="margin: 50px; border: 2px solid #ccc; padding: 10px; border-radius: 10px;">
    <code><pre><?php echo $debugQuery; ?></pre></code>
</div>
*/ 
?>
</body>
</html>


<?php
// error_reporting(E_ALL);
// ini_set('display_errors', '1');
require_once('/DRBD/www/pareps/auth/auth.php');
include_once('phpMysql/dbConfig.inc.php');

/***
[1] 2016-07-31 TMcPherson - add Zero Pick filter
[2] 2016-11-02 TMcPherson - added EX% types of delv codes
[3] 2017-05-04 tmcpherson - update sql
 ***/
?>
<html>
<link rel="stylesheet" type="text/css" href="/assets/vendor/dhtmlxWindows/dhtmlxwindows.css">
<link rel="stylesheet" type="text/css" href="/assets/vendor/dhtmlxWindows/skins/dhtmlxwindows_dhx_skyblue.css">
<script src="/assets/vendor/dhtmlxWindows/dhtmlxcommon.js"></script>
<script src="/assets/vendor/dhtmlxWindows/dhtmlxwindows.js"></script>
<script src="/assets/vendor/dhtmlxWindows/dhtmlxcontainer.js"></script>
<script src='/assets/vendor/ajaxTrans/ajaxTrans.js'></script>
<script src="/assets/vendor/jquery-3.4.1/jquery.min.js"></script>
<script src="js/jquery.multi-select.js"></script>
<style type="text/css">
    .multi-select-container {
        display: inline-block;
        position: relative;
    }
    .multi-select-menu {
        position: absolute;
        left: 0;
        top: 0.8em;
        z-index: 1;
        float: left;
        min-width: 100%;
        background: #fff;
        margin: 1em 0;
        border: 1px solid #aaa;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
        display: none;
    }

    .multi-select-menuitem {
        display: block;
        font-size: 0.875em;
        padding: 0.6em 1em 0.6em 30px;
        white-space: nowrap;
    }

    .multi-select-menuitem--titled:before {
        display: block;
        font-weight: bold;
        content: attr(data-group-title);
        margin: 0 0 0.25em -20px;
    }

    .multi-select-menuitem--titledsr:before {
        display: block;
        font-weight: bold;
        content: attr(data-group-title);
        border: 0;
        clip: rect(0 0 0 0);
        height: 1px;
        margin: -1px;
        overflow: hidden;
        padding: 0;
        position: absolute;
        width: 1px;
    }

    .multi-select-menuitem + .multi-select-menuitem {
        padding-top: 0;
    }

    .multi-select-presets {
        border-bottom: 1px solid #ddd;
    }

    .multi-select-menuitem input {
        position: absolute;
        margin-top: 0.25em;
        margin-left: -20px;
    }

    .multi-select-button {
        display: inline-block;
        font-size: 0.875em;
        padding: 0em 0.6em;
        /*max-width: 3em;*/
        max-width:50px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        vertical-align: -0.3em;
        background-color: #fff;
        border: 1px solid #aaa;
        /*border-radius: 4px;*/
        /*box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);*/
        cursor: default;
        margin-right: 5px;
    }

    .multi-select-button:after {
        content: "";
        display: inline-block;
        width: 0;
        height: 0;
        border-style: solid;
        border-width: 0.4em 0.4em 0 0.4em;
        border-color: #999 transparent transparent transparent;
        margin-left: 0.4em;
        vertical-align: 0.1em;
    }

    .multi-select-container--open .multi-select-menu {
        display: block;
    }

    .multi-select-container--open .multi-select-button:after {
        border-width: 0 0.4em 0.4em 0.4em;
        border-color: transparent transparent #999 transparent;
    }

    .multi-select-container--positioned .multi-select-menu {
        /* Avoid border/padding on menu messing with JavaScript width calculation */
        box-sizing: border-box;
    }

    .multi-select-container--positioned .multi-select-menu label {
        /* Allow labels to line wrap when menu is artificially narrowed */
        white-space: normal;
    }

</style>
<body style='margin:0px;' onload='doLoad()'>
<script type="text/JavaScript">
    var sURL = unescape(window.location.pathname);
    function doLoad()
    {
        var d = new Date();
        var curr_hour = d.getHours();
        //var curr_min = d.getMinutes();
        if (curr_hour > 2 && curr_hour < 6) {
            // The timeout value should be the same as in the "refresh" meta-tag
            setTimeout( "refresh()", 6000*1000);
        } else {
            setTimeout( "refresh()", 60*1000);
        }
    }
    function refresh()
    {
        //  This version of the refresh function will cause a new
        //  entry in the visitor's history.  It is provided for
        //  those browsers that only support JavaScript 1.0.
        //
        //window.location.href = sURL;
        //window.location.replace( sURL );
        window.location.reload(true);

    }
    function updateURLParameter(url, param, paramVal)
    {
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
                if(tempArray[i].split('=')[0] != param)
                {
                    newAdditionalURL += temp + tempArray[i];
                    temp = "&";
                }
            }
        } else {
            var tmpAnchor = baseURL.split("#");
            var TheParams = tmpAnchor[0];
            TheAnchor  = tmpAnchor[1];

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
    function getboard(usr,curbrd,curzne,branch)
    {
        var chbrd = document.getElementsByName('selbrd');
        for (var i=0;i < chbrd.length;i++) {
            bd = chbrd[i].selectedIndex;
        }
        if (bd == curbrd) {
            brd = curbrd;
        } else if(bd == 0) {
            brd = 0;
        } else {
            brd = bd;
        }
        //if (brd == 0) {brd = curbrd;}
        zne = [];
        if(document.getElementById('zonesel')) {
            var chzon = document.getElementById('zonesel');
            if(chzon.selectedOptions.length > 0){
                Array.from(chzon.selectedOptions).forEach(function (element) {
                    zne.push(element.value);
                });
            }
            // idx = chzon.selectedIndex;
            // zn = chzon.options[idx].text;
            // if (zn == curzne) {
            //     zne.push(curzne);
            // } else if(idx == 0) {
            //     zne = '';
            // } else {
            //     zne.push(zn);
            // }
        }
        //if (brd == 0 && zne == 0) {
        //alert('Must choose tracker or zone');
        //} else {
        var argString='usr='+usr;
        argString+='&brd='+brd;
        argString+='&zne='+zne.join(',');
        argString +='&bran='+ branch;

        var ajax = new ajaxObj();
        ajax.setArgString(argString);
        ajax.setUrl('putBoard-CA.php');
        ajax.setMethod('GET');
        ajax.setCallback(function(x){
                //alert (x);
                checked = x;
                //alert('Changing to tracker ' +brd);
                var filter = "";
                var o = document.getElementById('dlc_filter');
                if(o) {
                    filter = o.value;
                }

                window.location.href = updateURLParameter(window.location.href, 'dlc_filter', filter);

            }
        );
        ajax.send();
        //}
    }

    $(document).ready(function(){
        $('#zonesel').multiSelect({
            'noneText' : '*All*',
            'allText' : 'ALL',
            presets: [{
              name: 'ALL',
              all: true // select all
            }]

        });
    })
</script>
<?php

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

/*2018-06-20 tmcpherson A is prod*/
Database::setConfig('conn', DBDNS::AS400_RO);
$conn = Database::get('conn');
/*2018-06-20 tmcpherson B is dev box
 * $conn = odbc_connect("AS400-B",'ftpusr','d9v1nc1')
    	or die(odbc_errormsg());*/
if ($conn) {

    // Include Common Function File
    require_once('function.common.php');

    //[1]+ TMcPherson
    $skip_count=0;
    $sfbranch = "skipfilter".$branch;
    if(isset($_SESSION[$sfbranch])){
        $skipfilter = $_SESSION[$sfbranch];
    }else{
        $skipfilter = "n";
        $_SESSION[$sfbranch] = $skipfilter;
    }

    if(isset($_GET['sk']) AND ($_GET['sk'] == 'y' or $_GET['sk'] == 'n')){
        $skipfilter = $_GET['sk'];
        $_SESSION[$sfbranch] = $skipfilter;
    }
    //[1]- TMcPherson
    $offset = 0;
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

    //above DST offset is not working correctly, here is work around:
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
    $time2 = date('G:i:s',strtotime($dst));
    $time3 = date('Gis',strtotime($dst));
    $dat = (int)$date;

    if (!empty($_SERVER['REMOTE_ADDR'])) {
        $ip=$_SERVER['REMOTE_ADDR'];
    }

    //get zone from ip
    //No zone select except for listed IPs in zonetrak table
    $zon = '';
    $query2 = "select zone from zonetrak where bran = {$branch} and ipadd = '".$ip."' ";
    $contents = $conn->run($query2)->fetch(PDO::FETCH_ASSOC);
    if(!empty($contents)){
        $zon = trim($contents['ZONE']);
    }

    //get selected tracker Hot, Inet, Stock
    $zone = $Board = "";
    /*2018-06-14 tmcpherson pull data from read-only as400*/

    if(isset($_SESSION['ptrack_board_ca']) && isset($_SESSION['ptrack_board_ca'][$branch])){
        //print_r($_SESSION);exit;
        if (isset($_SESSION['ptrack_board_ca'][$branch]['board']) && $_SESSION['ptrack_board_ca'][$branch]['board'] != '') {
            $Board = $_SESSION['ptrack_board_ca'][$branch]['board'];
        }

        if (isset($_SESSION['ptrack_board_ca'][$branch]['zone']) && $_SESSION['ptrack_board_ca'][$branch]['zone'] != '') {
            $zone = $_SESSION['ptrack_board_ca'][$branch]['zone'];
            $zone = explode(",", $zone);
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
            $disp = "OnDemand62";
            break;
        case "5":
            $disp = "INTERNET";
            break;
        case "6":
            $disp = "SHUTTLE";
            break;
        case "7":
            $disp = "OVERNIGHT";
            break;
        case "8":
            $disp = "STOCK";
            break;
        case "9":
            $disp = "SUMMARY";
            break;
        case "10":
            $disp = "BY ZONE";
            break;
        case "11":
            $disp = "BY ZONE TRANS";
            break;
        case "12":
            $disp = "AMAZON DROP";
            break;
        default:
            $disp = "ALL";
            break;
    }

    if ($disp == "ALL") {$checked = 'selected="selected"';} else {$checked = '';}
    $bstring = "Type: <select name=selbrd><option value=\"0\" ".$checked.">ALL</option>";
    if ($disp == "HOT") {$checked = 'selected="selected"';} else {$checked = '';}
//2016-06-27 TMcPherson add NIV
//$bstring .= "<option value=\"hot\" ".$checked.">HOT-QIK</option>";
    $bstring .= "<option value=\"hot\" ".$checked.">HOT-QIK-NIV</option>";
//end add NIV
    if ($disp == "CPU") {$checked = 'selected="selected"';} else {$checked = '';}
    $bstring .= "<option value=\"cpu\" ".$checked.">CPU</option>";
    /*[2]+ trm 2016-11-02
    if ($disp == "H/C/D/Q/DS") {$checked = 'selected="selected"';} else {$checked = '';}
    $bstring .= "<option value=\"h/c/d/q/ds\" ".$checked.">H/C/D/Q/DS</option>";
    */
    if ($disp == "H/C/D/Q/DS/EX") {$checked = 'selected="selected"';} else {$checked = '';}
    $bstring .= "<option value=\"h/c/d/q/ds/ex\" ".$checked.">H/C/D/Q/DS/EX</option>";
//[2]-
    // 2019-09-23 tmcpherson if($branch == 62){
    if ($disp == "OnDemand62") {$checked = 'selected="selected"';} else {$checked = '';}
    $bstring .= "<option value=\"OnDemand62\" ".$checked.">OnDemand62</option>";
    // 2019-09-23 tmcpherson }
    if ($disp == "INTERNET") {$checked = 'selected="selected"';} else {$checked = '';}
    $bstring .= "<option value=\"int\" ".$checked.">INTERNET</option>";
    if ($disp == "SHUTTLE") {$checked = 'selected="selected"';} else {$checked = '';}
    $bstring .= "<option value=\"job\" ".$checked.">SHUTTLE</option>";
    if ($disp == "OVERNIGHT") {$checked = 'selected="selected"';} else {$checked = '';}
    $bstring .= "<option value=\"job\" ".$checked.">OVERNIGHT</option>";
    if ($disp == "STOCK") {$checked = 'selected="selected"';} else {$checked = '';}
    $bstring .= "<option value=\"stk\" ".$checked.">STOCK</option>";
    if ($disp == "SUMMARY") {$checked = 'selected="selected"';} else {$checked = '';}
    $bstring .= "<option value=\"sum\" ".$checked.">SUMMARY</option>";
    if ($disp == "BY ZONE") {$checked = 'selected="selected"';} else {$checked = '';}
    $bstring .= "<option value=\"zon\" ".$checked.">BY ZONE</option>";
    if ($disp == "BY ZONE TRANS") {$checked = 'selected="selected"';} else {$checked = '';}
    $bstring .= "<option value=\"zon\" ".$checked.">BY ZONE TRANS</option>";
    if ($disp == "AMAZON DROP") {$checked = 'selected="selected"';} else {$checked = '';}
    $bstring .= "<option value=\"drop\" ".$checked.">AMAZON DROP</option></select>";

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
    // $contents = odbc_exec($conn, $sql) or die("<p>" . odbc_errormsg($conn));
    $dlc_select = "";
    $valid_dlc_filter = false;
    foreach ($contents as $c) {
        $ship_meth = $c['SHIP_METH'];
        $checked = "";
        if ($dlc_filter == $ship_meth) {
            $valid_dlc_filter = true;
            $checked = 'selected="selected"';
        }

        $dlc_select .= "    <OPTION value='{$ship_meth}' {$checked}>{$ship_meth}</OPTION>\n";
    }
    // Filter selected not valid for this display type, switch back to all and do not filter the query
    if (!$valid_dlc_filter) {
        $dlc_filter = "";
    }
    if ($dlc_select != "") {
        $checked = ($dlc_filter == '' ? 'selected="selected"' : '');
        $dlc_select = " DLC: <SELECT id='dlc_filter'>
                                <OPTION value='' {$checked}>ALL</OPTION>
                                {$dlc_select}
                            </SELECT>";
    }

    //allow zone selection if not ip selected
    $zstring = '';
    if ($zon == '') {
        $zon = $zone;
        //$len = strlen($zon);
        $query1 = "select trim(SQLOCT) zones from WMOPCKD where sqobr = {$branch} and length(trim(sqloct)) <= 3 group by sqloct order by sqloct "; // Update length(trim(sqloct)) < 3 with <= 3 due to cp1 zone
        $contents = $conn->run($query1)->fetchAll(PDO::FETCH_ASSOC);
        // $contents = odbc_exec($conn,$query) or die("<p>".odbc_errormsg());
        //if ($zone == '') {$zon = 'ALL';}
        if ($zone == '') {$zcheck = 'selected="selected"';} else {$zcheck = '';}
        $zstring = "Zone: <select id=zonesel name=zonesel multiple >"; //<option value=\"all\" ".$zcheck.">ALL</option>
        $count = 0;
        if(!empty($contents)){
            // while(odbc_fetch_row($contents)) {
            foreach ($contents as $d){
                $count++;
                $zones = $d['ZONES'];
                if(is_array($zone)){
                  if (in_array($zones, $zone)) {
                    $zcheck = 'selected="selected"';
                  } else {
                    $zcheck = '';
                  }
                }else{
                  if ($zone == $zones) {
                    $zcheck = 'selected="selected"';
                  } else {
                    $zcheck = '';
                  }
                }

                $zstring .= "<option value='".$zones."' ".$zcheck.">".$zones."</option>";
            }
        }
        $zstring .= "</select>";
    }
    else {
        //$zone = '';
        $zstring = $zon;
    }


    $checked = $Board;
    //continue here with selection
    //$time2 = display_time($checked, $tdat);

    /* [1] TMcPherson
    $string = "<table width=100% valign='top' bgcolor='C0C0C0' border=1><tr><td></td><td width=5%>Order#</td><td width=32%>PICKS -<b>Bran {$branch}</b> $bstring $dlc_select $zstring <input type=button onclick=getboard('".$userId."','".$Board."','".$zone."'); value='GO' style=\"width:35px\" ></td><td align=center width=10%><b>$time2</b></td><td></td><td width=12%>PartNo</td><td width=6%>Picked</td><td width=6%>InStock</td><td>Rls At</td><td>Zone</td><td width=10%>Opened For</td></tr></table>";
    */
    /* check DST job run status */
    $string = '';
    $marginStatus = 0;

    $checkDSTJobStatus = checkDSTJobRunStatus();
    if(!empty($checkDSTJobStatus)){
        if(isset($checkDSTJobStatus['string'])){
            $string = $checkDSTJobStatus['string'];
        }
        if(isset($checkDSTJobStatus['marginStatus'])){
            $marginStatus = $checkDSTJobStatus['marginStatus'];
        }
    }

    //[1]+ TMcPherson
    $string .= "<table width=100% cellpadding='1' cellspacing='0' valign='top' border=0 ".( $marginStatus ? "style='margin-top:35px;'" : "").">";
    $string.= "<tr bgcolor='C0C0C0' >";

    if($skipfilter == 'y'){
        $string.= "<td width=1% bgcolor='yellow'><b>S</b></td>";
    }else{
        $string.= "<td width=1%></td>";
    }
    $string.= "<td width=5%>Order#</td>";
    /*$string.= "<td width=32%>PICKS -<b>Bran {$branch}</b>";*/
    $string.= "<td align='center'>PICKS<br><b>{$branch}</b></td>";
    $string.= "<td>";
    $string.= $bstring." ".$dlc_select." ".$zstring;
    $string.= "<input type=button onclick=getboard('".$userId."','".$Board."','".(is_array($zone) ? implode(',', $zone) :$zone)."','".$branch."'); value='GO' style=\"width:35px\" >";

    if($skipfilter == 'y'){
        $string.= "&nbsp;<strong><a href='indexALL-CA.php?branch=".$branch."&sk=n'>[Disable Skip Filter]</a></strong>";
    }else{
        $string.= "&nbsp;<strong><a href='indexALL-CA.php?branch=".$branch."&sk=y'>[Only View Skips]</a></strong>";
    }
    $string.= "</td>";
    $string.= "<td align=center width=10%><b>$time2</b></td><td width=10%>PartNo</td><td width=6%>Picked</td><td width=6%>InStock</td>";
    //$string.= "<td width=8%>Rls At</td><td width=8%>Zone</td><td width=9%>Opened For</td></tr></table>";
    $string.= "<td width=8%>Rls At</td><td width=8%>Zone</td><td width=9%>Opened For</td></tr>";
    //[1]- TMcPherson

    echo $string;
}

$dlc_where = "";
//Make selections per tracker query:  hot,int,stk
if ($disp == 'HOT') {
    //default to this board
    if ((is_array($zon) && count($zon) > 0) || (is_string($zon) && strlen($zon) > 0)) {
        /*2019-01-14 tmcpherson per Marc T remove the total line count filter
        *$query = "SELECT DISTINCT  h.ONO, h.SACODE, c.OLCNAME, t.SQITEM, t.SQPQTY, t.SQOQTY, h.PRTTIME+" . $dst1 . " prttime, t.SQFBIN, s.SQBOH, t.SQSTAT, h.CUSTACCT, SUBSTR(t.SQLOCT,1,2),0 cnt, " . $dat . "-h.prtdate dys, h.prtdate, h.DLVRCD, x.AXPBRN, ifNull(u.sacod,'') TRAN from WMOPCKH h JOIN WMOPCKD t ON (h.ono = t.sqono and h.prtdate = t.sqdate) JOIN ORDERSTAT c ON (h.ONO = c.OLORD# and h.OWHSE = c.OLBRAN and h.custacct +paicust.cobrac_offset() = c.olcusno  AND c.OLODAT >= CURDATE() - 10 DAYS) JOIN WMOPCKDAX s ON (h.ONO = s.SQONO and h.OBR = s.SQOBR and t.SQITEM = s.SQITEM) join ARCUST r on (h.custacct = r.cmacc) join ARCAUX x on (r.cmacc=x.axacc) left join TRANUSER U on h.sacode = u.sacod where (((prtdate >= $prev or (prtdate = $date and h.PRTTIME - 20000 <= $time)) and OWHSE = {$branch} and (h.DLVRCD = 'QIK' or h.DLVRCD = 'XCC' or h.DLVRCD = 'NXT' or h.DLVRCD = 'FDX' or h.HOTSHOT = 'Y') and r.CMCAT <> 'II' and trim(t.SQLOCT) = '" . $zon . "' and (select count(*) from WMOPCKD dd where dd.sqono = h.ono) < 15)  or (OWHSE = {$branch} and h.sacode = u.sacod and oltran = 'INV' and PRTDATE = $date)) {$dlc_where}  order by prtdate, PRTTIME";*/

        /* 2020-04-13 tmcpherson
        $query = "SELECT DISTINCT  h.ONO, h.SACODE, c.OLCNAME, t.SQITEM, t.SQPQTY, t.SQOQTY, h.PRTTIME+" . $dst1 . " prttime, t.SQFBIN, s.SQBOH, t.SQSTAT, h.CUSTACCT, SUBSTR(t.SQLOCT,1,2),0 cnt, " . $dat . "-h.prtdate dys, h.prtdate, h.DLVRCD, x.AXPBRN, ifNull(u.sacod,'') TRAN from WMOPCKH h JOIN WMOPCKD t ON (h.ono = t.sqono and h.prtdate = t.sqdate) JOIN ORDERSTAT c ON (h.ONO = c.OLORD# and h.OWHSE = c.OLBRAN and h.custacct +paicust.cobrac_offset() = c.olcusno  AND c.OLODAT >= CURDATE() - 10 DAYS) JOIN WMOPCKDAX s ON (h.ONO = s.SQONO and h.OBR = s.SQOBR and t.SQITEM = s.SQITEM) join ARCUST r on (h.custacct = r.cmacc) join ARCAUX x on (r.cmacc=x.axacc) left join TRANUSER U on h.sacode = u.sacod where (((prtdate >= $prev or (prtdate = $date and h.PRTTIME - 20000 <= $time)) and OWHSE = {$branch} and (h.DLVRCD = 'QIK' or h.DLVRCD = 'XCC' or h.DLVRCD = 'NXT' or h.DLVRCD = 'FDX' or h.HOTSHOT = 'Y') and r.CMCAT <> 'II' and trim(t.SQLOCT) = '" . $zon . "')  or (OWHSE = {$branch} and h.sacode = u.sacod and oltran = 'INV' and PRTDATE = $date)) {$dlc_where}  order by prtdate, PRTTIME";*/
        $zonCond = ' AND trim(t.SQLOCT) ';
        if(is_array($zon)){
            $zonCond .= " IN ('".implode("','", $zon)."') ";
        }else{
            $zonCond .= " = '".$zon."'";
        }
        $query = " SELECT DISTINCT  h.ONO, h.SACODE, c.OLCNAME, t.SQITEM, t.SQPQTY, t.SQOQTY, h.PRTTIME+" . $dst1 . " prttime, t.SQFBIN, s.SQBOH, t.SQSTAT, h.CUSTACCT, SUBSTR(t.SQLOCT,1,2),0 cnt, " . $dat . "-h.prtdate dys, h.prtdate, h.DLVRCD, x.AXPBRN, ifNull(u.sacod,'') TRAN 
                    FROM WMOPCKH h 
                        JOIN WMOPCKD t ON (h.ono = t.sqono and h.prtdate = t.sqdate) 
                        JOIN ORDERSTAT c ON (h.ONO = c.OLORD# and h.OWHSE = c.OLBRAN and h.custacct +paicust.cobrac_offset() = c.olcusno  AND c.OLODAT >= CURDATE() - 10 DAYS) 
                        JOIN WMOPCKDAX s ON (h.ONO = s.SQONO and h.OBR = s.SQOBR and t.SQITEM = s.SQITEM) 
                        join ARCUST r on (h.custacct = r.cmacc) 
                        join ARCAUX x on (r.cmacc=x.axacc) 
                        left join TRANUSER U on h.sacode = u.sacod 
                    where (
                          ((prtdate >= $prev or (prtdate = $date and h.PRTTIME - 20000 <= $time)) and        
                          OWHSE = {$branch} and
                          (h.DLVRCD = 'NIV' or 
                           h.DLVRCD = 'QIK' or 
                           h.DLVRCD = 'XCC' or 
                           h.DLVRCD = 'NXT' or 
                           h.DLVRCD = 'FDX'or
                           h.DLVRCD IN ('H01','H02','H03','H04','H05','H06','H07','H08','H09','H10','H11','H12','H13','H14','H15','H16','H17','H18') or 
                           h.HOTSHOT = 'Y') and
                          r.CMCAT <> 'II' {$zonCond})           
                          or 
                          (OWHSE = {$branch} and h.sacode = u.sacod and oltran = 'INV' and PRTDATE = $date)) 
                          {$dlc_where}  
                    order by prtdate, PRTTIME";
    } else {
        /*2019-01-14 tmcpherson per Marc T remove the total line count filter
         * $query = "SELECT DISTINCT  h.ONO, h.SACODE, c.OLCNAME, t.SQITEM, t.SQPQTY, t.SQOQTY, h.PRTTIME+" . $dst1 . " prttime, t.SQFBIN, s.SQBOH, t.SQSTAT, h.CUSTACCT, SUBSTR(t.SQLOCT,1,2), 0 cnt, " . $dat . "-h.prtdate dys, h.prtdate, h.DLVRCD, x.AXPBRN, ifNull(u.sacod,'') TRAN from WMOPCKH h JOIN WMOPCKD t ON (h.ono = t.sqono and h.prtdate = t.sqdate) JOIN ORDERSTAT c ON (h.ONO = c.OLORD# and h.OWHSE = c.OLBRAN and h.custacct +paicust.cobrac_offset() = c.olcusno  AND c.OLODAT >= CURDATE() - 10 DAYS) JOIN WMOPCKDAX s ON (h.ONO = s.SQONO and h.OBR = s.SQOBR and t.SQITEM = s.SQITEM) join ARCUST r on (h.custacct = r.cmacc) join ARCAUX x on (r.cmacc=x.axacc) left join TRANUSER U on h.sacode = u.sacod where (((prtdate >= $prev or (prtdate = $date and h.PRTTIME - 20000 <= $time)) and (h.DLVRCD = 'NIV'  or h.DLVRCD = 'QIK'  or h.DLVRCD = 'XCC' or h.DLVRCD = 'NXT' or h.DLVRCD = 'FDX' or h.HOTSHOT = 'Y') and OWHSE = {$branch} and r.CMCAT <> 'II' and (select count(*) from WMOPCKD dd where dd.sqono = h.ono) < 15) or (OWHSE = {$branch} and h.sacode = u.sacod and oltran = 'INV' and PRTDATE = $date)) {$dlc_where}  order by prtdate, PRTTIME";*/
        $query = "SELECT DISTINCT  h.ONO, h.SACODE, c.OLCNAME, t.SQITEM, t.SQPQTY, t.SQOQTY, h.PRTTIME+" . $dst1 . " prttime, t.SQFBIN, s.SQBOH, t.SQSTAT, h.CUSTACCT, SUBSTR(t.SQLOCT,1,2), 0 cnt, " . $dat . "-h.prtdate dys, h.prtdate, h.DLVRCD, x.AXPBRN, ifNull(u.sacod,'') TRAN from WMOPCKH h JOIN WMOPCKD t ON (h.ono = t.sqono and h.prtdate = t.sqdate) JOIN ORDERSTAT c ON (h.ONO = c.OLORD# and h.OWHSE = c.OLBRAN and h.custacct +paicust.cobrac_offset() = c.olcusno  AND c.OLODAT >= CURDATE() - 10 DAYS) JOIN WMOPCKDAX s ON (h.ONO = s.SQONO and h.OBR = s.SQOBR and t.SQITEM = s.SQITEM) join ARCUST r on (h.custacct = r.cmacc) join ARCAUX x on (r.cmacc=x.axacc) left join TRANUSER U on h.sacode = u.sacod where (((prtdate >= $prev or (prtdate = $date and h.PRTTIME - 20000 <= $time)) and (h.DLVRCD = 'NIV'  or h.DLVRCD = 'QIK'  or h.DLVRCD = 'XCC' or h.DLVRCD = 'NXT' or h.DLVRCD = 'FDX' or h.DLVRCD IN ('H01','H02','H03','H04','H05','H06','H07','H08','H09','H10','H11','H12','H13','H14','H15','H16','H17','H18') or h.HOTSHOT = 'Y') and OWHSE = {$branch} and r.CMCAT <> 'II' and (select count(*) from WMOPCKD dd where dd.sqono = h.ono) < 15) or (OWHSE = {$branch} and h.sacode = u.sacod and oltran = 'INV' and PRTDATE = $date)) {$dlc_where}  order by prtdate, PRTTIME";
    }
} elseif ($disp == 'CPU') {
    //default to this board
    if ((is_array($zon) && count($zon) > 0) || (is_string($zon) && strlen($zon) > 0)) {

        $zonCond = ' AND trim(t.SQLOCT) ';
        if (is_array($zon)) {
          $zonCond .= " IN ('" . implode("','", $zon) . "') ";
        } else {
          $zonCond .= " = '" . $zon . "'";
        }
        $query = "SELECT DISTINCT  h.ONO, h.SACODE, c.OLCNAME, t.SQITEM, t.SQPQTY, t.SQOQTY, h.PRTTIME+" . $dst1 . " prttime, t.SQFBIN, s.SQBOH, t.SQSTAT, h.CUSTACCT, SUBSTR(t.SQLOCT,1,2),0 cnt, " . $dat . "-h.prtdate dys, h.prtdate, h.DLVRCD, x.AXPBRN, ifNull(u.sacod,'') TRAN from WMOPCKH h JOIN WMOPCKD t ON (h.ono = t.sqono and h.prtdate = t.sqdate) JOIN ORDERSTAT c ON (h.ONO = c.OLORD# and h.OWHSE = c.OLBRAN and h.custacct +paicust.cobrac_offset() = c.olcusno  AND c.OLODAT >= CURDATE() - 10 DAYS) JOIN WMOPCKDAX s ON (h.ONO = s.SQONO and h.OBR = s.SQOBR and t.SQITEM = s.SQITEM) join ARCUST r on (h.custacct = r.cmacc) join ARCAUX x on (r.cmacc=x.axacc) left join TRANUSER U on h.sacode = u.sacod where (((prtdate >= $prev or (prtdate = $date and h.PRTTIME - 20000 <= $time)) and OWHSE = {$branch} and (h.DLVRCD = 'CPU') and r.CMCAT <> 'II' {$zonCond})  or (OWHSE = {$branch} and h.sacode = u.sacod and oltran = 'INV' and PRTDATE = $date)) {$dlc_where}  order by prtdate, PRTTIME";
    } else {
        $query = "SELECT DISTINCT  h.ONO, h.SACODE, c.OLCNAME, t.SQITEM, t.SQPQTY, t.SQOQTY, h.PRTTIME+" . $dst1 . " prttime, t.SQFBIN, s.SQBOH, t.SQSTAT, h.CUSTACCT, SUBSTR(t.SQLOCT,1,2), 0 cnt, " . $dat . "-h.prtdate dys, h.prtdate, h.DLVRCD, x.AXPBRN, ifNull(u.sacod,'') TRAN from WMOPCKH h JOIN WMOPCKD t ON (h.ono = t.sqono and h.prtdate = t.sqdate) JOIN ORDERSTAT c ON (h.ONO = c.OLORD# and h.OWHSE = c.OLBRAN and h.custacct +paicust.cobrac_offset() = c.olcusno  AND c.OLODAT >= CURDATE() - 10 DAYS) JOIN WMOPCKDAX s ON (h.ONO = s.SQONO and h.OBR = s.SQOBR and t.SQITEM = s.SQITEM) join ARCUST r on (h.custacct = r.cmacc) join ARCAUX x on (r.cmacc=x.axacc) left join TRANUSER U on h.sacode = u.sacod where (((prtdate >= $prev or (prtdate = $date and h.PRTTIME - 20000 <= $time)) and (h.DLVRCD = 'CPU') and OWHSE = {$branch} and r.CMCAT <> 'II' and (select count(*) from WMOPCKD dd where dd.sqono = h.ono) < 15) or (OWHSE = {$branch} and h.sacode = u.sacod and oltran = 'INV' and PRTDATE = $date)) {$dlc_where}  order by prtdate, PRTTIME";
    }
//[2] trm 2016-11-02 } elseif ($disp == 'SHUTTLE' || $disp == 'OVERNIGHT' || $disp == 'H/C/D/Q/DS') {
} elseif ($disp == 'SHUTTLE' || $disp == 'OVERNIGHT' || $disp == 'H/C/D/Q/DS/EX' || $disp == 'OnDemand62') {
    $numeric = " SUBSTR(h.dlvrcd, 1, 1) BETWEEN '0' AND '9'
                AND SUBSTR(h.dlvrcd, 2, 1) BETWEEN '0' AND '9'
                AND SUBSTR(h.dlvrcd, 3, 1) BETWEEN '0' AND '9'";

    // [3] TMcPherson '125' to '124'
    $shuttle = "(
                    h.dlvrcd BETWEEN '124' AND '144' or
                    h.dlvrcd BETWEEN '521' AND '532' or
                    h.dlvrcd BETWEEN '620' AND '657'
                )";

    $overnight = "(
                    h.dlvrcd BETWEEN '101' AND '115' or
                    h.dlvrcd BETWEEN '511' AND '518' or
                    h.dlvrcd IN ('606', '611')
                )";

    if ($dlc_filter != '') {
        $dlc_where = " AND h.DLVRCD = '{$dlc_filter}' ";
    }

    if ($disp == 'SHUTTLE') {
        // Updated following condition as per PMX-1023 request on release 27thJan2022
        // $dlvrcd = "{$numeric} AND {$shuttle}";
        if($branch != 431){
            $dlvrcd = "{$numeric} AND {$shuttle}";
        }
        else{
            $dlvrcd = " h.DLVRCD in ('R01', 'R02', 'R03', 'R04', 'R05', 'R06','R07', 'R08', 'R09')";
        }
        /*[2]+ trm 2016-11-02
        } elseif($disp == 'H/C/D/Q/DS') {
            $dlvrcd = "(({$numeric} AND {$shuttle}) OR h.dlvrcd IN ('HOT', 'CPU', 'DTB', 'QIK'))";
        */
    } elseif($disp == 'H/C/D/Q/DS/EX') {
        // [3] TMcPherson updated sql
        //$dlvrcd = "(({$numeric} AND {$shuttle}) OR h.dlvrcd IN ('HOT', 'CPU', 'DTB', 'QIK') OR ( h.dlvrcd LIKE 'EX%'))";
        // tmcpherson 2020-01-28 show STK in 44
        if($branch == 44) {
            $b44stk = ", 'STK', 'H01','H02','H03','H04','H05','H06','H07','H08','H09','H10','H11','H12','H13','H14','H15','H16','H17','H18'";
        }else{
            $b44stk = '';
        }
        $dlvrcd = "(({$numeric} AND {$shuttle}) OR h.dlvrcd IN ('HOT', 'CPU', 'DTB', 'QIK' ".$b44stk." ) or h.HOTSHOT = 'Y' OR h.dlvrcd LIKE 'EX%' )";
        //[2]-
    } elseif($disp == 'OnDemand62') {
        // [3] TMcPherson updated sql
        //$dlvrcd = "(({$numeric} AND {$shuttle}) OR h.dlvrcd IN ('HOT', 'CPU', 'DTB', 'QIK') OR ( h.dlvrcd LIKE 'EX%'))";
        $dlvrcd = "(({$numeric} AND {$shuttle}) OR h.dlvrcd IN ('HOT', 'CPU', 'DTB', 'QIK', 'CLN', 'GRT', 'LOM', 'MUS', 'NHI', 'RIE', 'SBE', 'SBN', 'SBS', 'SBW') OR h.dlvrcd LIKE 'EX%' OR h.HOTSHOT = 'Y' )";
        //$dlvrcd = "h.dlvrcd IN ('CLN', 'GRT', 'LOM', 'MUS', 'NHI', 'RIE', 'SBE', 'SBN', 'SBS', 'SBW')";
        //[2]-
    } elseif($disp == 'OVERNIGHT') {
        $dlvrcd = "{$numeric} AND {$overnight}";
    } else {
        $dlvrcd = "{$numeric} AND ({$shuttle} OR {$overnight})";
    }
    if ((is_array($zon) && count($zon) > 0) || (is_string($zon) && strlen($zon) > 0)) {
        $zonCond = ' AND trim(t.SQLOCT) ';
        if (is_array($zon)) {
            $zonCond .= " IN ('" . implode("','", $zon) . "') ";
        } else {
            $zonCond .= " = '" . $zon . "'";
        }
        $query = "SELECT DISTINCT  h.ONO, h.SACODE, c.OLCNAME, t.SQITEM, t.SQPQTY, t.SQOQTY, h.PRTTIME+".$dst1." prttime, t.SQFBIN, s.SQBOH, t.SQSTAT, h.CUSTACCT, SUBSTR(t.SQLOCT,1,2),0 cnt, ".$dat."-h.prtdate dys, h.prtdate, h.DLVRCD, x.AXPBRN, ifNull(u.sacod,'') TRAN from WMOPCKH h JOIN WMOPCKD t ON (h.ono = t.sqono and h.prtdate = t.sqdate) JOIN ORDERSTAT c ON (h.ONO = c.OLORD# and h.OWHSE = c.OLBRAN and h.custacct +paicust.cobrac_offset() = c.olcusno  AND c.OLODAT >= CURDATE() - 10 DAYS) JOIN WMOPCKDAX s ON (h.ONO = s.SQONO and h.OBR = s.SQOBR and t.SQITEM = s.SQITEM) join ARCUST r on (h.custacct = r.cmacc) join ARCAUX x on (r.cmacc=x.axacc) left join TRANUSER U on h.sacode = u.sacod where ((((prtdate >= $prev or (prtdate = $date and h.PRTTIME - 20000 <= $time)) and OWHSE = {$branch} and ({$dlvrcd}) and r.CMCAT <> 'II' {$zonCond} )  or (OWHSE = {$branch} and h.sacode = u.sacod and oltran = 'INV' and PRTDATE = $date)) {$dlc_where} ) order by prtdate, PRTTIME";
    } else {
        $query = "SELECT DISTINCT  h.ONO, h.SACODE, c.OLCNAME, t.SQITEM, t.SQPQTY, t.SQOQTY, h.PRTTIME+".$dst1." prttime, t.SQFBIN, s.SQBOH, t.SQSTAT, h.CUSTACCT, SUBSTR(t.SQLOCT,1,2) SQLOCT, 0 cnt, ".$dat."-h.prtdate dys, h.prtdate, h.DLVRCD, x.AXPBRN, ifNull(u.sacod,'') TRAN from WMOPCKH h JOIN WMOPCKD t ON (h.ono = t.sqono and h.prtdate = t.sqdate) JOIN ORDERSTAT c ON (h.ONO = c.OLORD# and h.OWHSE = c.OLBRAN and h.custacct +paicust.cobrac_offset() = c.olcusno  AND c.OLODAT >= CURDATE() - 10 DAYS) JOIN WMOPCKDAX s ON (h.ONO = s.SQONO and h.OBR = s.SQOBR and t.SQITEM = s.SQITEM) join ARCUST r on (h.custacct = r.cmacc) join ARCAUX x on (r.cmacc=x.axacc) left join TRANUSER U on h.sacode = u.sacod where (((prtdate >= $prev or (prtdate = $date and h.PRTTIME - 20000 <= $time)) and (({$dlvrcd})) and OWHSE = {$branch} and r.CMCAT <> 'II' ) or (OWHSE = {$branch} and h.sacode = u.sacod and oltran = 'INV' and PRTDATE = $date)) {$dlc_where}   order by prtdate, PRTTIME";
    }
} elseif ($disp == 'STOCK') {
    if ((is_array($zon) && count($zon) > 0) || (is_string($zon) && strlen($zon) > 0)) {
        $zonCond = ' AND trim(t.SQLOCT) ';
        if (is_array($zon)) {
            $zonCond .= " IN ('" . implode("','", $zon) . "') ";
        } else {
            $zonCond .= " = '" . $zon . "'";
        }
        $query = "SELECT DISTINCT  h.ONO, h.SACODE, c.OLCNAME, t.SQITEM, t.SQPQTY, t.SQOQTY, h.PRTTIME+".$dst1." prttime, t.SQFBIN, s.SQBOH, t.SQSTAT, h.CUSTACCT, SUBSTR(t.SQLOCT,1,2) SQLOCT,0 cnt, ".$dat."-h.prtdate dys, h.prtdate, h.dlvrcd, AXPBRN, '' TRAN from WMOPCKH h JOIN WMOPCKD t ON (h.ono = t.sqono and h.prtdate = t.sqdate) JOIN ORDERSTAT c ON (h.ONO = c.OLORD# and h.OWHSE = c.OLBRAN and h.custacct +paicust.cobrac_offset() = c.olcusno  AND c.OLODAT >= CURDATE() - 10 DAYS) JOIN WMOPCKDAX s ON (h.ONO = s.SQONO and h.OBR = s.SQOBR and t.SQITEM = s.SQITEM) join ARCUST r on (h.custacct = r.cmacc) join ARCAUX x on (h.custacct = x.axacc) where ((prtdate >= $prev or (prtdate = $date and h.PRTTIME - 20000 <= $time)) and r.CMCAT <> 'II' and (h.CUSTACCT < 99000 or h.CUSTACCT > 99999) and OWHSE = {$branch} and ((h.dlvrcd <> 'QIK' and h.HOTSHOT = '') or (select count(*) from wmopckh hh join wmopckd dd on hh.obr = dd.sqobr and hh.ono = dd.sqono where hh.ono = h.ono) >= 15) and h.dlvrcd <> 'XXX' {$zonCond} ) {$dlc_where} order by prtdate, PRTTIME";
    } else {
        $query = "SELECT DISTINCT  h.ONO, h.SACODE, c.OLCNAME, t.SQITEM, t.SQPQTY, t.SQOQTY, h.PRTTIME+".$dst1." prttime, t.SQFBIN, s.SQBOH, t.SQSTAT, h.CUSTACCT, SUBSTR(t.SQLOCT,1,2) SQLOCT,0 cnt, ".$dat."-h.prtdate dys, h.prtdate, h.dlvrcd, AXPBRN, '' TRAN from WMOPCKH h JOIN WMOPCKD t ON (h.ono = t.sqono and h.prtdate = t.sqdate) JOIN ORDERSTAT c ON (h.ONO = c.OLORD# and h.OWHSE = c.OLBRAN and h.custacct +paicust.cobrac_offset() = c.olcusno  AND c.OLODAT >= CURDATE() - 10 DAYS) JOIN WMOPCKDAX s ON (h.ONO = s.SQONO and h.OBR = s.SQOBR and t.SQITEM = s.SQITEM) join ARCUST r on (h.custacct = r.cmacc) join ARCAUX x on (h.custacct = x.axacc) where ((prtdate >= $prev or (prtdate = $date and h.PRTTIME - 20000 <= $time)) and r.CMCAT <> 'II' and (h.CUSTACCT < 99000 or h.CUSTACCT > 99999) and OWHSE = {$branch} and ((h.dlvrcd <> 'QIK' and h.HOTSHOT = '') or (select count(*) from wmopckh hh join wmopckd dd on hh.obr = dd.sqobr and hh.ono = dd.sqono where hh.ono = h.ono) >= 15) and h.dlvrcd <> 'XXX') {$dlc_where} order by prtdate, PRTTIME";
    }
} elseif ($disp == 'INTERNET')  {
    if ((is_array($zon) && count($zon) > 0) || (is_string($zon) && strlen($zon) > 0)) {
        $zonCond = ' AND trim(t.SQLOCT) ';
        if (is_array($zon)) {
            $zonCond .= " IN ('" . implode("','", $zon) . "') ";
         } else {
            $zonCond .= " = '" . $zon . "'";
        }
        $query = "SELECT DISTINCT  h.ONO, h.SACODE, c.OLCNAME, t.SQITEM, t.SQPQTY, t.SQOQTY, h.PRTTIME+".$dst1." prttime, t.SQFBIN, s.SQBOH, t.SQSTAT, h.CUSTACCT, SUBSTR(t.SQLOCT,1,2) SQLOCT, ".$dat."-prtdate dys, prtdate, '' TRAN, h.DLVRCD, AXPBRN  from WMOPCKH h JOIN WMOPCKD t ON (h.ono = t.sqono and h.prtdate = t.sqdate) JOIN ORDERSTAT c ON (h.ONO = c.OLORD# and h.OWHSE = c.OLBRAN and h.custacct +paicust.cobrac_offset() = c.olcusno  AND c.OLODAT >= CURDATE() - 10 DAYS) JOIN WMOPCKDAX s ON (h.ONO = s.SQONO and h.OBR = s.SQOBR and t.SQITEM = s.SQITEM) join ARCUST r on (h.custacct = r.cmacc) join ARCAUX x on (h.custacct = x.axacc) where ((prtdate >= $prev or (prtdate = $date and h.PRTTIME - 20000 <= $time))  and (r.CMCAT = 'II' OR h.DLVRCD IN ('XPO', 'J3S', 'I3S', 'F1S', 'F3S', 'D1S', 'D5S')) and OWHSE = {$branch} {$zonCond}) {$dlc_where} order by prtdate, PRTTIME";
    } else {
        $query = "SELECT DISTINCT  h.ONO, h.SACODE, c.OLCNAME, t.SQITEM, t.SQPQTY, t.SQOQTY, h.PRTTIME+".$dst1." prttime, t.SQFBIN, s.SQBOH, t.SQSTAT, h.CUSTACCT, SUBSTR(t.SQLOCT,1,2) SQLOCT, ".$dat."-prtdate dys, prtdate, '' TRAN, h.DLVRCD, AXPBRN  
                    FROM WMOPCKH h 
                      JOIN WMOPCKD t ON 
                        (h.ono = t.sqono and h.prtdate = t.sqdate) 
                      JOIN ORDERSTAT c ON 
                        (h.ONO = c.OLORD# and h.OWHSE = c.OLBRAN and h.custacct +paicust.cobrac_offset() = c.olcusno  AND c.OLODAT >= CURDATE() - 10 DAYS) 
                      JOIN WMOPCKDAX s ON 
                        (h.ONO = s.SQONO and h.OBR = s.SQOBR and t.SQITEM = s.SQITEM) 
                      JOIN ARCUST r on 
                        (h.custacct = r.cmacc) 
                      JOIN ARCAUX x on 
                        (h.custacct = x.axacc) 
                    WHERE ((prtdate >= $prev or (prtdate = $date and h.PRTTIME - 20000 <= $time)) 
                         and (r.CMCAT = 'II' OR h.DLVRCD IN ('XPO', 'J3S', 'I3S', 'F1S', 'F3S', 'D1S', 'D5S')) and OWHSE = {$branch}) {$dlc_where} order by prtdate, PRTTIME";
    }
} elseif ($disp == 'SUMMARY')  {
    $query = " SELECT DISTINCT
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
} elseif ($disp == 'BY ZONE')  {
    $query = "SELECT DISTINCT  '', '', 'All Parts per Zone', '', sum(t.SQPQTY), sum(t.SQOQTY),'','',0,'',0,Trim(t.SQLOCT), 0 dys, '' TRAN, '' AXPBRN from WMOPCKH h JOIN WMOPCKD t ON (h.ono = t.sqono and h.prtdate = t.sqdate)  join ARCUST r on (h.custacct = r.cmacc) where (prtdate = $date and h.PRTTIME - 20000 <= $time and obr = {$branch} and OWHSE = {$branch}) {$dlc_where} group by Trim(t.SQLOCT) order by trim(t.SQLOCT)";
} elseif ($disp == 'BY ZONE TRANS')  {
    if ((is_array($zon) && count($zon) > 0) || (is_string($zon) && strlen($zon) > 0)) {
        $zonCond = ' AND trim(t.SQLOCT) ';
        if (is_array($zon)) {
            $zonCond .= " IN ('" . implode("','", $zon) . "') ";
        } else {
            $zonCond .= " = '" . $zon . "'";
        }
        $query = "SELECT DISTINCT  '', '', 'Transfer Parts per Zone by ', h.picker, sum(t.SQPQTY), sum(t.SQOQTY),'','','','',0,max(t.SQLOCT), 0 dys, '' TRAN, '' AXPBRN from WMOPCKH h JOIN WMOPCKD t ON (h.ono = t.sqono and h.prtdate = t.sqdate)  join ARCUST r on (h.custacct = r.cmacc) where (prtdate = $date and h.PRTTIME - 20000 <= $time and obr = {$branch} and OWHSE = {$branch} and  h.custacct > 99000 and  h.custacct <= 99999 {$zonCond}) {$dlc_where} group by h.picker";
    } else {
        $query = "SELECT DISTINCT  '', '', 'Transfer Parts per Zone', '', sum(t.SQPQTY), sum(t.SQOQTY),'','','','',0,Trim(t.SQLOCT), 0 dys, '' TRAN, '' AXPBRN from WMOPCKH h JOIN WMOPCKD t ON (h.ono = t.sqono and h.prtdate = t.sqdate)  join ARCUST r on (h.custacct = r.cmacc) where (prtdate = $date and h.PRTTIME - 20000 <= $time and obr = {$branch} and OWHSE = {$branch} and h.custacct > 99000 and  h.custacct <= 99999) {$dlc_where} group by Trim(t.SQLOCT) order by trim(t.SQLOCT)";
    }
} elseif ($disp == 'AMAZON DROP')  {
    if ((is_array($zon) && count($zon) > 0) || (is_string($zon) && strlen($zon) > 0)) {
        $zonCond = ' AND trim(t.SQLOCT) ';
        if (is_array($zon)) {
            $zonCond .= " IN ('" . implode("','", $zon) . "') ";
        } else {
            $zonCond .= " = '" . $zon . "'";
        }
        $query = "SELECT DISTINCT  h.ONO, h.SACODE, c.OLCNAME, t.SQITEM, t.SQPQTY, t.SQOQTY, h.PRTTIME+".$dst1." prttime, t.SQFBIN, s.SQBOH, t.SQSTAT, h.CUSTACCT, SUBSTR(t.SQLOCT,1,2) SQLOCT, ".$dat."-prtdate dys, prtdate, '' TRAN, h.DLVRCD, AXPBRN  from WMOPCKH h JOIN WMOPCKD t ON (h.ono = t.sqono and h.prtdate = t.sqdate) JOIN ORDERSTAT c ON (h.ONO = c.OLORD# and h.OWHSE = c.OLBRAN and h.custacct +paicust.cobrac_offset() = c.olcusno  AND c.OLODAT >= CURDATE() - 10 DAYS) JOIN WMOPCKDAX s ON (h.ONO = s.SQONO and h.OBR = s.SQOBR and t.SQITEM = s.SQITEM) join ARCUST r on (h.custacct = r.cmacc) join ARCAUX x on (h.custacct = x.axacc) where ((prtdate >= $prev )  and h.CUSTACCT = 64500 and OWHSE = {$branch} {$zonCond} ) {$dlc_where} order by prtdate, PRTTIME";
    } else {
        $query = "SELECT DISTINCT  h.ONO, h.SACODE, c.OLCNAME, t.SQITEM, t.SQPQTY, t.SQOQTY, h.PRTTIME+".$dst1." prttime, t.SQFBIN, s.SQBOH, t.SQSTAT, h.CUSTACCT, SUBSTR(t.SQLOCT,1,2) SQLOCT, ".$dat."-prtdate dys, prtdate, '' TRAN, h.DLVRCD, AXPBRN  from WMOPCKH h JOIN WMOPCKD t ON (h.ono = t.sqono and h.prtdate = t.sqdate) JOIN ORDERSTAT c ON (h.ONO = c.OLORD# and h.OWHSE = c.OLBRAN and h.custacct +paicust.cobrac_offset() = c.olcusno  AND c.OLODAT >= CURDATE() - 10 DAYS) JOIN WMOPCKDAX s ON (h.ONO = s.SQONO and h.OBR = s.SQOBR and t.SQITEM = s.SQITEM) join ARCUST r on (h.custacct = r.cmacc) join ARCAUX x on (h.custacct = x.axacc) where ((prtdate >= $prev ) and h.CUSTACCT = 64500 and OWHSE = {$branch}) {$dlc_where} order by prtdate, PRTTIME";
    }
} elseif ($disp == 'ALL')  {
    if ((is_array($zon) && count($zon) > 0) || (is_string($zon) && strlen($zon) > 0)) {
        $zonCond = ' AND trim(t.SQLOCT) ';
        if (is_array($zon)) {
            $zonCond .= " IN ('" . implode("','", $zon) . "') ";
        } else {
            $zonCond .= " = '" . $zon . "'";
        }
        $query = "SELECT DISTINCT  h.ONO, h.SACODE, c.OLCNAME, t.SQITEM, t.SQPQTY, t.SQOQTY, h.PRTTIME+".$dst1." prttime, t.SQFBIN, s.SQBOH, t.SQSTAT, h.CUSTACCT, SUBSTR(t.SQLOCT,1,2) SQLOCT, ".$dat."-prtdate dys, prtdate, '' TRAN, h.DLVRCD, AXPBRN from WMOPCKH h JOIN WMOPCKD t ON (h.ono = t.sqono and h.prtdate = t.sqdate) JOIN ORDERSTAT c ON (h.ONO = c.OLORD# and h.OWHSE = c.OLBRAN and h.custacct +paicust.cobrac_offset() = c.olcusno  AND c.OLODAT >= CURDATE() - 10 DAYS) JOIN WMOPCKDAX s ON (h.ONO = s.SQONO and h.OBR = s.SQOBR and t.SQITEM = s.SQITEM) join ARCAUX x on (h.custacct = x.axacc) where ((prtdate >= $prev or (prtdate = $date and h.PRTTIME - 20000 <= $time)) and OWHSE = {$branch} {$zonCond} ) {$dlc_where} order by prtdate, PRTTIME";
    } else {
        $query = "SELECT DISTINCT  h.ONO, h.SACODE, c.OLCNAME, t.SQITEM, t.SQPQTY, t.SQOQTY, h.PRTTIME+".$dst1." prttime, t.SQFBIN, s.SQBOH, t.SQSTAT, h.CUSTACCT, SUBSTR(t.SQLOCT,1,2) SQLOCT, ".$dat."-prtdate dys, prtdate, '' TRAN, h.DLVRCD, AXPBRN from WMOPCKH h JOIN WMOPCKD t ON (h.ono = t.sqono and h.prtdate = t.sqdate) JOIN ORDERSTAT c ON (h.ONO = c.OLORD# and h.OWHSE = c.OLBRAN and h.custacct +paicust.cobrac_offset() = c.olcusno  AND c.OLODAT >= CURDATE() - 10 DAYS) JOIN WMOPCKDAX s ON (h.ONO = s.SQONO and h.OBR = s.SQOBR and t.SQITEM = s.SQITEM) join ARCAUX x on (h.custacct = x.axacc) where ((prtdate >= $prev or (prtdate = $date and h.PRTTIME - 20000 <= $time)) and OWHSE = {$branch}) {$dlc_where} order by prtdate, PRTTIME";
    }
}
if(isset($_REQUEST['debug']) && $_REQUEST['debug']==1){
    echo $query;exit;
}

$contents = $conn->run($query)->fetchAll(PDO::FETCH_BOTH);
// $contents = odbc_exec($conn,$query) or die("<p>".odbc_errormsg());
$testcontents = [];
$i = 0;

if(!empty($contents)) {
    $time_left = 0;
    $time_start = 0;

    /*2018-06-20 tmcpherson fix mis aligned columns
     * echo "<table width=100% cellspacing=0 border=0>";*/

    //begin loop here
    foreach ($contents as $cnt){
        if(($disp != 'SUMMARY' && $disp != 'BY ZONE')){
            $testcontents[$cnt['DLVRCD']][$i]['ONO'] = $cnt['ONO'];
            $testcontents[$cnt['DLVRCD']][$i]['DLVRCD'] = $cnt['DLVRCD'];
            $testcontents[$cnt['DLVRCD']][$i]['CUSTACCT'] = $cnt['CUSTACCT'];
            $testcontents[$cnt['DLVRCD']][$i]['OLCNAME'] = $cnt['OLCNAME'];
            $testcontents[$cnt['DLVRCD']][$i]['prtdate'] = $cnt['PRTDATE'];
            $testcontents[$cnt['DLVRCD']][$i]['PRTTIME'] = $cnt['PRTTIME'];
        }else{
            $testcontents='';
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


        if($skipfilter == 'y' AND $status_image != "img/question-icon30.gif") continue;

        echo "<tr>";
        echo "<td width=2%><IMG SRC='".$status_image."' ALT='".$status_alt."' WIDTH='".$status_image_size."' HEIGHT='".$status_image_size."' ></td>";
        //[1]- TMcPherson



        //echo "<td color='Green' width=2%\><b>".$test ."</b></td>";

        //make color blue or red
        $dlvcd = '';
        if ($disp != 'SUMMARY' && $disp != 'BY ZONE'  && $disp != 'BY ZONE TRANS') {$sacod = $cnt['SACODE'];$dlvcd = $cnt['DLVRCD'];}
        $pbrn = '';
        if ($disp == 'HOT') {$pbrn = $cnt['AXPBRN'];}
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
        $Zn = substr($cnt[7],0,1);
        $Zon = $cnt[11];
        switch (trim($Zn)) {
            case 'A':
                //Green
                $Pick =  "<FONT COLOR=\"#006400\">".$Zon." ".$Zone."</FONT>";
                break;
            case 'B':
                //Yellow
                $Pick =  "<FONT COLOR=\"#4B0082\">".$Zon." ".$Zone."</FONT>";
                break;
            case 'E':
                //Pink
                $Pick =  "<FONT COLOR=\"#8B0000\">".$Zon." ".$Zone."</FONT>";
                break;
            case 'M':
                //Lavender
                $Pick =  "<FONT COLOR=\"#8B008B\">".$Zon." ".$Zone."</FONT>";
                break;
            default:
                //Blue
                $Pick =  "<FONT COLOR=\"#000000\">".$Zon." ".$Zone."</FONT>";
        }

        $acc = $cnt[10];
        if ($acc == '0' || substr($acc,0,2) == '99') {
            $cust = $cnt[2];
        } else {
            $cust = $acc." : ".$cnt[2] . "  -PB(".$cnt['AXPBRN'].")";
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

        echo "<td width=5% bgcolor='$color' height=1><B>" .$cnt[0]. "</B></td>";
        echo "<td width=4% bgcolor='$color' height=1><B>" .$cnt[1]. (!empty($dlvcd) ? "&nbsp;&nbsp;{$dlvcd}" : "") . "</B></td>";
        //	echo "<td width=36% bgcolor='$color' height=1><B>" .odbc_result($contents,3). "</B></td>";
        /*2018-06-20 tmcpherson
         * echo "<td width=38% bgcolor='$color' height=1><B>" . $cust . "</B></td>";*/
        echo "<td colspan='2' bgcolor='$color' height=1><B>" . $cust . "</B></td>";
        if ($disp != 'BY ZONE') {
            echo "<td width=8% bgcolor='$color' height=1><B>" .$cnt[3]. "</B></td>";
            echo "<td width=8% bgcolor='$color' height=1><B>" .$cnt[4] . " of ".$cnt[5]."</B></td>";
        } else {
            echo "<td width=10% bgcolor='$color' height=1><B>" .$cnt[3]. "</B></td>";
            echo "<td width=6% bgcolor='$color' height=1><B>" .$cnt[4] . " of ".$cnt[5]."</B></td>";
        }
        echo "<td width=6% bgcolor='$color' height=1><B>";
        if ($disp != 'SUMMARY'){
            echo number_format($cnt[8], 0, '', ',');
        }
        echo "</B></td>";
        echo "<td width=8% bgcolor='$color' height=1><B>" .$time_start. "</B></td>";
        echo "<td width=8%  height=1><B>" .$Pick. "</B></td>";
        //echo "<td width=9% bgcolor='$color' height=1><B>" .odbc_result($contents,8). "</B></td>";
        echo "<td width=9% bgcolor='$color' height=1><B>" .$time_left. "</B></td>";
        echo "</tr>";
        //}
    }
}
  //[1]+ TMcPherson
  if ($i == 0) {
    echo "<td> </td><td> </td><td></td><td></td><td bgcolor='lightsteelblue'><B>Nothing: Slow at the moment</B></td>";
  } elseif ($i > 0 and $skip_count == 0 and $skipfilter == 'y') {
    echo "<td> </td><td> </td><td></td><td></td><td bgcolor='lightsteelblue'><B>No skips at the moment</B></td>";
  }
  //[1]- TMcPherson

  echo "</table>";


  //[4] tmcpherson show debug info
  if (!empty($_SESSION['userId']) && (in_array($_SESSION['userId'], [2408, 4688, 3820, 7806, 9461, 9907, 9827]))) {
    echo "<hr>";
    echo "<table>";
    echo "<tr><td>";
    echo "SQL:<pre><textarea cols='80' rows='20'>";
    echo $query;
    echo "</textarea></pre>";
    echo "</td><td>";
    echo "Results:<pre><textarea cols='80' rows='20'>";
    print_r($testcontents);
    echo "</textarea></pre>";
    echo "</td><td>";
    echo "<table>";
    echo "<tr><td>dlvrcd</td><td>ono</td></tr>";
    ksort($testcontents);
    foreach ($testcontents as $key => $tc) {
      echo "<tr><td>{$key}</td><td>" . count($tc) . "</td></tr>";
    }
    echo "</table>";
    echo "<pre>";
    $session = $_SESSION['ptrack_board_ca'] ?? [];
    print_r($session);
    echo "</pre>";
    echo "</td></tr>";
    echo "</table>";
  } //end debug info

function format_time($gettime) {
//test length and return formatted time
    $length = strlen((string)$gettime);
    $time = '';
    switch ($length) {
        case 6:
            $time = substr($gettime,0,2) . ":" . substr($gettime,2,2) . ":" . substr($gettime,4,2);
            break;
        case 5:
            $time = substr($gettime,0,1) . ":" . substr($gettime,1,2) . ":" . substr($gettime,3,2);
            break;
        case 4:
            $time = substr($gettime,0,2) . ":" . substr($gettime,2,2);
            break;
        case 3:
            $time = substr($gettime,0,1) . ":" . substr($gettime,1,2);
            break;
        case 2:
            $time = ":" . substr($gettime,0,2);
            break;
        case 1:
            $time = ":0" . substr($gettime,0,1);
            break;
    }
    return $time;
}

function format_secs($gettime) {
//converts secs to time format
    $hour = (int)($gettime / 3600);
    $min = (int)(($gettime - ($hour * 3600)) / 60);
    $sec = ($gettime - ($min * 60));
    $time = format_time($hour * 10000 + $min * 100 + $sec);
    return $time;
}

function sub_secs($current, $order) {
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

function secs_to_time($seconds) {
//translate secs into time: hhmmss
    $hours = (int)($seconds / 3600);
    $mins = (int)(($seconds - ($hours * 3600)) / 60);
    $secs = $seconds - ($hours * 3600) - ($mins * 60);
    $timeback = $hours * 10000 + $mins * 100 + $secs;
    return $timeback;
}

?>
</body>
</html>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=gb2312" />
<title>Tradeweb SLB VIP Lookup Tools</title>
<link rel="stylesheet" href="../../css/table.css">
</head>
<body>
<table><tr><td id="main">
<h1>Tradeweb SLB VIP Lookup Tools</h1>
<table  cellspacing="0" border="0">

<!--  Content 1: Search   -->
<tr>
<td>
  <form action="SLB_VIP.php" method="post" name="SLB_VIP">
  <table cellspacing="0" border="0">
  <tr>
    <!--<td>Business Unit</td>
    <td><select name="slb"/>
      <option value = "" selected = "selected"> Any </option>
      <option value = "USSV-PRIFI-SLB-01"> IFI </option>
      <option value = "EUSG-PRIFI-SLB-01"> EU IFI </option>
      <option value = "USSV-PRIDB-SLB-01"> IDB </option>
      <option value = "USSV-WEB-SLB-01"> Retail </option>
      <option value = "USCH-WEB-SLB-01"> Retail </option>
      <option value = "USSV-DEMO-SLB-01"> IFI/IDB Demo </option>
      <option value = "USSV-DMWEB-SLB-01"> Retail Demo </option>
      <option value = "USPW-LTWEB-SLB-01"> Retail Load Test </option>
    </select></td> -->
    <td></td>
    <td></td>
  </tr>
  <tr>
    <td>Virtual IP</td>
    <td><input type="text" name="vip" maxlength="15"/></td>
    <td>Virtual Name</td>
    <td><input type="text" name="vname" maxlength="30" /></td>
  </tr>
  <tr>
    <td>Real IP</td>
    <td><input type="text" name="rip" maxlength="15" /></td>
    <td>Real Name</td>
    <td><input type="text" name="rname" maxlength="30" /></td>
  </tr>
  <tr>
    <td>Service</td>
    <td><input type="text" name="service" maxlength="30" /></td>
    <td>(Exact search</td>
    <td>if any input)</td>
  </tr>
  <tr>
    <td><input name="submitbtn" value="Search" type="submit"/></td>
    <td></td>
    <td></td>
  </tr>
  </table>
  </form>
</td>
</tr>


<?php
  $slb = trim($_REQUEST["slb"]);
  $vip =trim($_REQUEST["vip"]);
  $vname = trim($_REQUEST["vname"]);
  $rip = trim($_REQUEST["rip"]);
  $rname =trim($_REQUEST["rname"]);
  $service = trim($_REQUEST["service"]);
  $orderBy = trim($_GET["orderBy"]);
        $decend = trim($_GET["decend"]);

  //"select" and "From" part

  #$slbarray=array("USSV-PRIFI-SLB-01","USCH-PRIFI-SLB-01","USJC-PRIFI-SLB-01","EUSL-PRIFI-SLB-01","EUSG-PRIFI-SLB-01","ASTK-PRIFI-SLB-01","ASSA-PRIFI-SLB-01","USSV-PRIDB-SLB-01","EUSG-PRIDB-SLB-01","USSV-WEB-SLB-01","USCH-WEB-SLB-01","EUSG-WEB-SLB-01","USSV-DEMO-SLB-01","EUSG-DEMO-SLB-01","USSV-DMWEB-SLB-01","USPW-LTWEB-SLB-01");

  $GR_temp_string="CREATE TEMPORARY TABLE GR_temp AS (SELECT G.SLB, G.gindex, GR.rindex FROM SLB_Group G, SLB_Group_RIP GR WHERE G.SLB=GR.SLB and G.gindex=GR.gindex);";
  $R_temp_string="CREATE TEMPORARY TABLE R_temp AS (SELECT GR.SLB, GR.gindex, R.rip, R.rname FROM GR_temp GR, SLB_RIP R WHERE GR.SLB=R.SLB and GR.rindex=R.rindex);";
  $VG_temp_string="CREATE TEMPORARY TABLE VG_temp AS (SELECT R.SLB, VG.vindex, R.rip, R.rname, VG.service FROM R_temp R, SLB_VIP_Group VG WHERE R.SLB=VG.SLB and R.gindex=VG.gindex);";
  $V_temp_string_begin="CREATE TEMPORARY TABLE V_temp AS (SELECT DISTINCT V.SLB, V.vip, V.vname, VG.service, VG.rip, VG.rname FROM VG_temp VG, SLB_VIP V WHERE VG.SLB=V.SLB AND VG.vindex=V.vindex";
  $V_temp_string_end=");";

  $sql="SELECT A.*, B.InternetNAT, B.DealerNAT FROM V_temp A left outer join (SELECT DlrN.SourceIP, DlrN.NATedIP as DealerNAT, InetN.NATedIP as InternetNAT from DealerRouterNATs DlrN, InternetNATs InetN WHERE DlrN.SourceIP=InetN.SourceIP) B ON A.vip = B.SourceIP;";
  
  //"where" part
  $slb_string = "";
  $vip_string = "";
  $vname_string = "";
  $rip_string = "";
  $rname_string = "";
  $service_string = ""; 
        $sort_string = "";

  #if($slb!="") {
  # $slb_string=" AND V.SLB = '".$slb."'";
  #}
  if($vip!="") {
    $vip_string=" AND V.vip like '%".$vip."%'";
    $vip_string=" AND V.vip like '%".$vip."%'";
  }
  if($vname!="") {
    $vname_string=" AND V.vname like '%".$vname."%'";
  }
  if($rip!="") {
    $rip_string=" AND VG.rip like '%".$rip."%'";
  }
  if($rname!="") {
    $rname_string=" AND VG.rname like '%".$rname."%'";
  }
  if($service!="") {
    $service_string = " AND VG.service = '".$service."'";
  }
        if($orderBy!="") $sort_string = " ORDER BY `".$orderBy."`";
        if($decend== "true") $sort_string = $sort_string." DESC";

  //$sql=$select_string.$vip_string.$vname_string.$rip_string.$rname_string.$service_string.$sort_string.$selectNAT_string;
  $V_temp_string=$V_temp_string_begin.$vip_string.$vname_string.$rip_string.$rname_string.$service_string.$sort_string.$V_temp_string_end;
        //print $sql;
  $url = "SLB_VIP.php?vip=".urlencode($vip)."&vname=".urlencode($vname)."&rip=".urlencode($rip)."&rname=".urlencode($rname)."&service=".urlencode($service);
?>


<!-- Content 2: Table -->
<tr><td>
  <table id="chart"> 
  <?php
  
   if(isset($_POST['submitbtn'])){
      echo "<tr>";    
          //print table head
                        $a = array(     array("SLB","SLB"),
                                        array("vip","vip"),
                                        array("vname","vname"),
                                        array("service","service"),
                            array("rip","rip"),
                                        array("rname","rname"),                                        
                                        array("InternetNAT","InternetNAT"),                                        
                                        array("DealerNAT","DealerNAT")                                        
          );
                        foreach ($a as $column){
                                echo "<th><a href = \"".$url."&orderBy=".$column[0];
                                if($orderBy==$column[0] && $decend=="")
                                        echo "&decend=true\">".$column[1]."&#8593";
                                elseif($orderBy == $column[0])
                                        echo "\">".$column[1]."&#8595";
                                else
                                        echo "\">".$column[1];
                                echo "</a></th>";
                        }
      echo "<th> DNS </th>";
                        echo "</tr>";

                        //print table content
  //if(empty($_POST)){
  //  $vip="172.16.3.195";
  //}


      ini_set('display_errors', 'On');
      $fh = fopen("/var/web_data/etc/PHPMySQL_global.ini",'r');
      $phpHost = rtrim(fgets($fh),"\n");
      $phpUser = rtrim(fgets($fh),"\n");
      $phpPass = rtrim(fgets($fh),"\n");
      fclose($fh);
      $conn = mysqli_connect($phpHost, $phpUser, $phpPass, "NMG");
      if(!$conn)die("Unable to establish connection to NMG" . mysqli_connect_error());
              
    //  echo $sql;
        
        mysqli_query($conn,$GR_temp_string) or die(mysqli_error());
        mysqli_query($conn,$R_temp_string) or die(mysqli_error());
        mysqli_query($conn,$VG_temp_string) or die(mysqli_error());
        mysqli_query($conn,$V_temp_string) or die(mysqli_error());
        $data = mysqli_query($conn, $sql) or die(mysqli_error());
        $slbarray["USSV-PRIFI-SLB-01"] = "IFI";
        $slbarray["USCH-PRIFI-SLB-01"] = "IFI";
        $slbarray["EUSL-PRIFI-SLB-01"] = "IFI";
        $slbarray["EUSG-PRIFI-SLB-01"] = "IFI";
        $slbarray["ASTK-PRIFI-SLB-01"] = "IFI";
        $slbarray["ASSA-PRIFI-SLB-01"] = "IFI";
        $slbarray["USSV-PRIDB-SLB-01"] = "IDB";
        $slbarray["EUSG-PRIDB-SLB-01"] = "IDB";
        $slbarray["USSV-WEB-SLB-01"] = "Retail";
        $slbarray["USCH-WEB-SLB-01"] = "Retail";
        $slbarray["EUSG-WEB-SLB-01"] = "Retail";
        $slbarray["USSV-DEMO-SLB-01"] = "IFI/IDB Demo";
        $slbarray["EUSG-DEMO-SLB-01"] = "IFI/IDB Demo";
        $slbarray["USSV-DMWEB-SLB-01"] = "Retail Demo";
        $slbarray["USPW-LTWEB-SLB-01"] = "Retail Load Test";
        while ($res = mysqli_fetch_array($data, MYSQLI_ASSOC))
        {
        $slbref = "";
        if (array_key_exists($res['SLB'], $slbarray))
        {
          $slbref = $slbarray[$res['SLB']];
        }
        else
        {
          $slbref = "N/A";
        }
        echo "<tr><td id=\"name\">".$slbref."</td><td>".$res['vip']."</td><td>".$res['vname']."</td><td>".$res['service']."</td><td>".$res['rip']."</td><td>".$res['rname']."</td><td>".$res['InternetNAT']."</td><td>".$res['DealerNAT']."</td><td>";
        $dnsarray = mysqli_query($conn, "select dns from ARPTable where ip = '".$res['rip']."'");
        $dnsres = mysqli_fetch_array($dnsarray,MYSQLI_NUM);
        echo $dnsres[0];
        echo "</td></tr>";
        }
      mysqli_close($conn);
      }
    ?>
  </table>
</td></tr>

</table>
</td>
</tr>
</table>
</td>
</tr>
</table>

</body>
</html>


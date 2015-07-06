<?php

  /* Globals --------------------------------------------------------------------------------------------------------------------------------------------------------------- */
  
  define("ReviewsPerPage", 10);
  
  
  /* Functions ------------------------------------------------------------------------------------------------------------------------------------------------------------- */
  
  
  function DoHTMLHeader($Title = false) {
  /*/////////////////////////////////////////////////////////////
       Author: Plottery Corp.
      Created: v1.0.0 - 2010-12-09
    Revisions: None
      Purpose: Displays the HTML header block
      Returns: Nothing
  *//////////////////////////////////////////////////////////////
  
  global $LanguageID;
  
  if ($Title === false) {
    
    list ($QR, $DR, $T) = QuerySingle("SELECT COUNT(StringID) AS TStr FROM 0100_Strings WHERE StringID BETWEEN 1020 AND 1029;");
    $ID = ($QR > 0)?mt_rand(0, ($DR['TStr']-1)):0;
    $Title = GS(1020+$ID);
    
  }
  
  $Strings = GSA('1011,1012');
  
?><!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
 <HEAD>

   <META HTTP-EQUIV="Content-Type" CONTENT="text/html;charset=UTF-8" />
   <META HTTP-EQUIV="X-UA-Compatible" CONTENT="IE=9" />
   
   <META NAME="Keywords"    CONTENT="<?php echo $Strings[1012]; ?>">
   <META NAME="Description" CONTENT="<?php echo $Strings[1011]; ?>">
   <META NAME="thumbnail"   CONTENT="/IF/Thumbnail.png" />
   
   <TITLE>dealplotter &bull; <?php echo $Title; ?></TITLE>

   <LINK REL="StyleSheet" HREF="/Scripts/Main.css" TYPE="text/css" MEDIA="screen">
   <LINK REL="image_src"  HREF="/IF/Thumbnail.png" />
   
   <!--[if lt IE 9]>
     <LINK REL="StyleSheet" HREF="/Scripts/IE.css" TYPE="text/css" MEDIA="screen">
   <![endif]-->
   
<?php
  }
  
  
  function GetMainContent() {
  /*/////////////////////////////////////////////////////////////
       Author: Plottery Corp.
      Created: v1.0.0 - 2010-12-09
    Revisions: None
      Purpose: Determines the content to load
      Returns: Nothing
  *//////////////////////////////////////////////////////////////     
  

    global $LanguageID;
    global $LanguageCode;
    global $SessionID;

    if ($SessionID > 0) {
    
      //Session found
      DisplayMapPage();
    
    } else {
      //No valid cookie data
      if ($LanguageID == 0) {
        $LanguageID = 1;
        $LanguageCode = 'en';
      }
      
      DisplayLoginPage();
      
    }
    
  }
  
  function GetSingleContent() {
  
    global $LanguageID;
    global $Response;
    
    if (isset($_POST['DID'])) {
      if (is_numeric($_POST['DID'])) {
    
        list ($QR, $DR, $T) =
          QuerySingle(
            'SELECT D.DealID, COALESCE(LSDa.StringText, LSDb.StringText) AS Descr, COALESCE(STy.Icon, SC.Icon, "Blank") AS Icon,
              LD.LocationLatitude AS Lat, LD.LocationLongitude AS Lng, C.CountryCurrency AS CurS
               FROM 4000_Deals D
              INNER JOIN 0200_Language_Strings LSDa ON D.StringID = LSDa.StringID AND LSDa.LanguageID = '.$LanguageID.'
              INNER JOIN 0200_Language_Strings LSDb ON D.StringID = LSDb.StringID AND LSDb.LanguageID = 1
              INNER JOIN 2000_Stores ST ON D.StoreID = St.StoreID
              INNER JOIN 2200_Store_Locations SL ON SL.StoreID = ST.StoreID
              INNER JOIN 3000_Locations LD ON LD.LocationID = SL.LocationID
              INNER JOIN 3100_Countries C ON C.CountryID = LD.CountryID
               LEFT JOIN 2110_Store_Types STy ON ST.TypeID = STy.TypeID
               LEFT JOIN 2100_Store_Categories SC ON STy.CategoryID = SC.CategoryID
              WHERE D.DealID = '.(int)$_POST['DID'].'
              LIMIT 0,1;');
              
        if ($QR > 0) return DisplayMapPage(array($DR['DealID'], $DR['Descr'], $DR['Lat'], $DR['Lng'], $DR['CurS'], $DR['Icon']));
        
      }
    }
    
    //RedirectTo();
    $Response->J = 'PopErr();';
    $Response->Send();
      
  }
  
  function GetSingleDeal() {
  
    global $LanguageID;
    global $LanguageCode;

    if ($LanguageID == 0) {
      $LanguageID = 1;
      $LanguageCode = 'en';
      
      $Expiry = time() + (60 * 60);
      setcookie('LID', $LanguageID, $Expiry);
      
    }
  
    list ($QR, $DR, $T) = 
      QuerySingle(
        "SELECT D.DealID, COALESCE(LSDa.StringText, LSDb.StringText) AS Title
           FROM 4000_Deals D
          INNER JOIN 0200_Language_Strings LSDa ON D.StringID = LSDa.StringID AND LSDa.LanguageID = ".$LanguageID."
          INNER JOIN 0200_Language_Strings LSDb ON D.StringID = LSDb.StringID AND LSDb.LanguageID = 1
          WHERE D.DealID = ".(int)$_SERVER['QUERY_STRING'].";");
          
    if ($QR > 0) return DisplayMainScreen('InitD('.$DR['DealID'].');', $DR['Title']);
    
    RedirectTo();
  
  }

  function DisplayMainScreen($JS = false, $Title = false) {
  /*/////////////////////////////////////////////////////////////
       Author: Plottery Corp.
      Created: v1.0.0 - 2010-12-09
    Revisions: None
      Purpose: Displays the main interface shell
      Returns: Nothing
  *//////////////////////////////////////////////////////////////    
  
    global $LanguageID;
    global $LanguageCode;
    
    if ($Title === false) {
      DoHTMLHeader();
    } else {
      DoHTMLHeader($Title);
    }
    
    $Strings = GSA('1000,1001,1010,1203');
?>
   
 <SCRIPT TYPE="text/javascript" SRC="http://maps.google.com/maps/api/js?v=3.4&sensor=false&language=<?php echo $LanguageCode; ?>"></SCRIPT>
 <SCRIPT TYPE="text/javascript" SRC="/Scripts/Main.js"></SCRIPT>

</HEAD>
 
 <BODY CLASS="bkg" onLoad="SetBackMap(); DoRndLoc();">

 <DIV CLASS="full">
   <DIV ID="backmap" CLASS="abs full"></DIV>
   <DIV CLASS="logo"></DIV>
   <DIV ID="CpR" CLASS="cpr">&copy; 2010-<?php echo date('Y'); ?>, Plottery Corp.</DIV>
</DIV>

</BODY>
</HTML>

<?php  
    
  }  
   
  
  function OldDisplayMainScreen($JS = false, $Title = false) {
  /*/////////////////////////////////////////////////////////////
       Author: Plottery Corp.
      Created: v1.0.0 - 2010-12-09
    Revisions: None
      Purpose: Displays the main interface shell
      Returns: Nothing
  *//////////////////////////////////////////////////////////////    
  
    global $LanguageID;
    global $LanguageCode;
    
    if ($Title === false) {
      DoHTMLHeader();
    } else {
      DoHTMLHeader($Title);
    }
    
    $Strings = GSA('1000,1001,1010,1203');
    $InitJS = (($JS === false)?'Init();':$JS);
    
?>
   
 <SCRIPT TYPE="text/javascript" SRC="http://maps.google.com/maps/api/js?v=3.4&sensor=false&language=<?php echo $LanguageCode; ?>"></SCRIPT>
 <SCRIPT TYPE="text/javascript" SRC="http://www.google.com/recaptcha/api/js/recaptcha_ajax.js"></SCRIPT>
 <SCRIPT TYPE="text/javascript">
   loadstr = '<DIV CLASS="fullx" ID="PUC"><DIV CLASS="load ctr"><OBJECT TYPE="image/svg+xml" WIDTH=29 HEIGHT=30 CLASS="valgm" DATA="/IF/Icon-Gear.svg"><IMG SRC="/IF/Icon-Gear.png" CLASS="valgm" ALT="" /></OBJECT>&nbsp;<?php echo Pacify($Strings[1001]); ?>...</DIV></DIV>';
   closestr = '<DIV CLASS="cls z3" onClick="ClWin();"><?php echo Pacify($Strings[1000]); ?></DIV>';
   errormsg = '<?php echo Pacify($Strings[1203]); ?>';
</SCRIPT>
 <SCRIPT TYPE="text/javascript" SRC="/Scripts/Main.js"></SCRIPT>
 <SCRIPT TYPE="text/javascript">

  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', 'UA-22124771-1']);
  _gaq.push(['_trackPageview']);

  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();

</SCRIPT>
<SCRIPT type="text/javascript">
  (function() {
    var po = document.createElement('script'); po.type = 'text/javascript'; po.async = true;
    po.src = 'https://apis.google.com/js/plusone.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(po, s);
  })();
</SCRIPT>

</HEAD>
 
 <BODY CLASS="bkg" onLoad="<?php echo $InitJS; ?>">

 <DIV CLASS="full">
   <DIV ID="backmap" CLASS="abs full"></DIV>
   <DIV CLASS="logo"></DIV>
   <DIV ID="PopUp" CLASS="hid"></DIV>
   <DIV ID="MainContent"></DIV>
   <DIV ID="CpR" CLASS="cpr">&copy; 2010-<?php echo date('Y'); ?>, Plottery Corp. &bull; <SPAN CLASS="fklnk" onClick="ShoTrm();"><?php echo $Strings[1010]; ?></SPAN></DIV>
   <DIV ID="Hlp" CLASS="abs sz13 padaxs b fdl rbrds bky z3 hid shwl fra"></DIV>
   <DIV ID="FBC" CLASS="hid dno"></DIV>
</DIV>

<SCRIPT TYPE="text/javascript">
  window.fbAsyncInit = function() { FBLoad = true; };
  (function() {
    var e = document.createElement('script'); e.async = true;
    e.src = document.location.protocol + '//connect.facebook.net/en_US/all.js';
    document.getElementById('FBC').appendChild(e);
  }());
</SCRIPT>

</BODY>
</HTML>

<?php  
    
  }
  
  
  function DoToolboxLeft() {
  
    global $UserID;
    global $UserFlags;
    
    $Strings = GSA('1100,1101,1102,1103,1104,1105,1115,1116');
  
    $Response = '<DIV CLASS="tl z3">';
    if ($UserID > 0) {
      $Response .= '<DIV CLASS="tbutt tiad" onClick="EdtAct();" onMouseOver="DoHlp(this,1139,1);" onMouseOut="KlHlp();">'.$Strings[1116].'</DIV>';
      $Response .= '<DIV CLASS="tbutt tiho" onClick="ShoLoc();" onMouseOver="DoHlp(this,1130,1);" onMouseOut="KlHlp();">'.$Strings[1100].'</DIV>';
      $Response .= '<DIV CLASS="tbutt tise" onClick="EdtFlt();" onMouseOver="DoHlp(this,1131,1);" onMouseOut="KlHlp();">'.$Strings[1101].'</DIV>';
      $Response .= '<DIV CLASS="tbutt tino" onClick="EdtNot();" onMouseOver="DoHlp(this,1138,1);" onMouseOut="KlHlp();">'.$Strings[1115].'</DIV>';
      if ($UserFlags >= 32) $Response .= '<DIV CLASS="tbutt tiad" onClick="Admin();">'.$Strings[1103].'</DIV>';
    } else {
      $Response .= '<DIV CLASS="tbutt tiho" onClick="SignOut();" onMouseOver="DoHlp(this,1134,1);" onMouseOut="KlHlp();">'.$Strings[1104].'</DIV>';
      $Response .= '<DIV CLASS="tbutt tise" onClick="EdtFlt();" onMouseOver="DoHlp(this,1131,1);" onMouseOut="KlHlp();">'.$Strings[1101].'</DIV>';
      $Response .= '<DIV CLASS="dtbutt tidno" onMouseOver="DoHlp(this,1334,1);" onMouseOut="KlHlp();">'.$Strings[1115].'</DIV>';
      $Response .= '<DIV CLASS="tbutt tire ltbl" onClick="NewAcct();" onMouseOver="DoHlp(this,1135,1);" onMouseOut="KlHlp();">'.$Strings[1105].'</DIV>';
    }
    $Response .= '</DIV>';
    
    return $Response;
    
  }
  
  function DoToolboxRight($SingleMode = false) {
  
    global $UserID;
    
    $Strings = GSA('1106,1107,1108,1109,1113,1114');
  
    $Response = '<DIV CLASS="tr z3">';
    $Response .= '<DIV CLASS="gpb" ID="GPB"><g:plusone size="medium" annotation="none" href="http://www.dealplotter.com"></g:plusone></DIV>';
    $Response .= '<DIV CLASS="tbutts tsfb" onClick="DoFB();" onMouseOver="DoHlp(this,1136,1);" onMouseOut="KlHlp();"></DIV>';
    $Response .= '<DIV CLASS="tbutts tstw" onClick="PWin(\'http://twitter.com/home?status='.Pacify($Strings[1114]).' http://www.dealplotter.com\');" onMouseOver="DoHlp(this,1137,1);" onMouseOut="KlHlp();"></DIV>';
    //$Response .= '<DIV CLASS="tbutt tihe" onClick="">'.$Strings[1106].'</DIV>';
    if ((stripos($_SERVER['HTTP_USER_AGENT'], 'msie') !== false) && (stripos($_SERVER['HTTP_USER_AGENT'], 'opera') === false) && (stripos($_SERVER['HTTP_USER_AGENT'], 'gecko') === false) && (stripos($_SERVER['HTTP_USER_AGENT'], 'opera') === false) && (stripos($_SERVER['HTTP_USER_AGENT'], 'MSIE 9') === false)) $Response .= '<DIV CLASS="tbutt tiie" onClick="IEWarn();">'.$Strings[1113].'</DIV>';
    $Response .= '<DIV CLASS="tbutt tico" onClick="ShoCtc();" onMouseOver="DoHlp(this,1132,1);" onMouseOut="KlHlp();">'.$Strings[1107].'</DIV>';
    $Response .= '<DIV CLASS="tbutt tila" onClick="ChgLng();" onMouseOver="DoHlp(this,1133,1);" onMouseOut="KlHlp();">'.$Strings[1108].'</DIV>';
    if (($UserID > 0) && (!$SingleMode)) $Response .= '<DIV CLASS="tbutt tiex" onClick="SignOut();" onMouseOver="DoHlp(this,1134,1);" onMouseOut="KlHlp();">'.$Strings[1109].'</DIV>';
    $Response .= '</DIV>';
    
    return $Response;
  
  }
  
  function DoSingleMsgLeft($Lat) {
  
    $Strings = GSA('1356,1357,1368');

    return '<DIV CLASS="abs smsg box padaxs"><DIV CLASS="cbox cboxl"><B>'.$Strings[1357].'</B><BR />'.(($Lat == -1)?$Strings[1368].'<BR />':'').'<DIV CLASS="nbutt mgrts" onClick="self.location=\'/\';">'.$Strings[1356].'</DIV></DIV></DIV>';
  
  }
  
  function DoPanelLeft() {
  
    $Strings = GSA('1001,1141,1142,1143,1300,1301,1311,1318,1358,1359');
  
    return '<DIV NAME="Box" ID="PanelLeft"  CLASS="abs box shadow fdl panl">
               <DIV CLASS="inbox">
                 <DIV ID="PanL1" CLASS="abs shwr fdz panlt lt1 algc" onClick="SetTab(1);" onMouseOver="DoHlp(this,1346,0,1,1353);" onMouseOut="KlHlp();"><DIV CLASS="vlbl rtxt vlh1 lts" ID="PanLT1">'.$Strings[1141].'</DIV><DIV CLASS="spc"><DIV ID="DTCount">0</DIV><DIV><SPAN>'.$Strings[1300].'</SPAN></DIV><DIV ID="DMCount">0</DIV><DIV><SPAN>'.$Strings[1311].'</SPAN></DIV><DIV ID="DNCount">0</DIV><DIV><SPAN>'.$Strings[1301].'</SPAN></DIV></DIV></DIV>
                 <DIV ID="PanL3" CLASS="abs shwr fdm panlt lt3 algc" onClick="SetTab(3);" onMouseOver="DoHlp(this,1364,0,3,1353);" onMouseOut="KlHlp();"><DIV CLASS="vlbl rtxt vlh1 ltu" ID="PanLT3">'.$Strings[1142].'</DIV><DIV CLASS="spc"><DIV ID="WTCount">0</DIV><DIV><SPAN>'.$Strings[1300].'</SPAN></DIV><DIV ID="WMCount">0</DIV><DIV><SPAN>'.$Strings[1311].'</SPAN></DIV><DIV ID="WNCount">0</DIV><DIV><SPAN>'.$Strings[1301].'</SPAN></DIV></DIV></DIV>
                 <DIV ID="PanL2" CLASS="abs shwr fdm panlt lt2 algc" onClick="SetTab(2);" onMouseOver="DoHlp(this,1347,0,2,1353);" onMouseOut="KlHlp();"><DIV CLASS="vlbl rtxt vlh2 ltu" ID="PanLT2">'.$Strings[1143].'</DIV><DIV CLASS="spc"><DIV ID="FTCount">0</DIV><DIV><SPAN>'.$Strings[1300].'</SPAN></DIV><DIV ID="FECount">0</DIV><DIV><SPAN>'.$Strings[1318].'</SPAN></DIV></DIV></DIV>
                 <DIV CLASS="algr"><DIV CLASS="fll b sz18" ID="PanLTi">'.$Strings[1359].'</DIV><INPUT ID="Search" CLASS="schbox ltgr" TYPE="text" VALUE="'.$Strings[1358].'" onfocus="ChkSch();" onkeydown="STmr();"><HR></DIV>
                 <DIV ID="PanLM"><DIV CLASS="fullsp" ID="PUC"><DIV CLASS="load ctr"><OBJECT TYPE="image/svg+xml" WIDTH=29 HEIGHT=30 CLASS="valgm" DATA="/IF/Icon-Gear.svg"><IMG SRC="/IF/Icon-Gear.png" CLASS="valgm" ALT="" /></OBJECT>&nbsp;'.$Strings[1001].'...</DIV></DIV></DIV>
              </DIV>
           </DIV>';

  }
  
  function DoPanelRight() {
  
    return '<DIV NAME="Box" ID="PanelRight" CLASS="abs box shadow fdl panr hid">
              <DIV CLASS="inbox">
                <DIV ID="PanRT"></DIV>
                <DIV ID="PanRC" CLASS="abs fullsp flwa padrs"></DIV>
             </DIV>
           </DIV>';
  
  }
  
  function DisplayLoginPage() {
  /*/////////////////////////////////////////////////////////////
       Author: Plottery Corp.
      Created: v1.0.0 - 2010-12-09
    Revisions: None
      Purpose: Displays the main interface shell
      Returns: Nothing
  *////////////////////////////////////////////////////////////// 
  
    global $Response;
    
    $Response->S = true;
    
    $Strings = GSA('1110,1111,1200,1201,1207,1208,1209,1210,1211,1212,1213,1214', 0, false, true);
    
    $Response->R = DoToolboxRight().'
 <DIV NAME="Box" CLASS="splashleft ctr box shadow algc fdl">
   <DIV CLASS="inbox">
     <DIV CLASS="ttlw">'.$Strings[1200].'<HR></DIV>
     <DIV ID="LocationContent">
       <FORM onSubmit="ChkAddr(\'SubLoc\'); return false;" accept-charset="UTF-8">
         <DIV ID="LocMsg" CLASS="wrkmsg"><DIV CLASS="fll gear"><OBJECT TYPE="image/svg+xml" WIDTH=29 HEIGHT=30 CLASS="valgm" DATA="/IF/Icon-Gear.svg"><IMG SRC="/IF/Icon-Gear.png" CLASS="valgm" ALT="" /></OBJECT></DIV>'.$Strings[1201].'</DIV>
         <INPUT TYPE="Hidden" NAME="Coords" ID="Coords">
         <INPUT CLASS="wide algc" TYPE="text" NAME="InLoc" ID="InLoc" onKeyDown="RstLoc();"><BR /><EM>'.$Strings[1207].'</EM><BR />
         <INPUT CLASS="butt mrgts" TYPE="submit" ID="SubLoc" VALUE="'.$Strings[1110].'">
      </FORM>
    </DIV>
  </DIV>
</DIV>
 <DIV NAME="Box" CLASS="splashright ctr box shadow algc fdl">
   <DIV CLASS="inbox">
     <DIV CLASS="ttlw">'.$Strings[1208].'<HR></DIV>
     <DIV ID="LoginContent">
       <DIV ID="LoginMsg"></DIV>
       <DIV CLASS="valgm">
         <FORM NAME="DPLogin" onSubmit="CkLog(\'SubLog\'); return false;" accept-charset="UTF-8">
           <EM>'.$Strings[1209].'</EM><BR /><INPUT CLASS="boxmed algc" TYPE="text" NAME="DPUsername" ID="DPUsername" onKeyDown="ClDIV(\'LoginMsg\');" VALUE=""><BR />
           <EM>'.$Strings[1210].'</EM><BR /><INPUT CLASS="boxmed algc" TYPE="password" NAME="DPPassword" ID="DPPassword" onKeyDown="ClDIV(\'LoginMsg\');" VALUE=""><BR />
           <EM><INPUT TYPE="Checkbox" NAME="DPSave" VALUE="1">&nbsp;'.$Strings[1211].'</EM><BR />
           <INPUT CLASS="butt mrgts" TYPE="submit" ID="SubLog" VALUE="'.$Strings[1111].'">
        </FORM>
      </DIV>
    </DIV>
     <DIV CLASS="mvb abs algc">'.$Strings[1212].'&nbsp;<DIV CLASS="nbutt" onClick="NewAcct();">'.$Strings[1213].'</DIV><BR />'.$Strings[1214].'</DIV>
  </DIV>
</DIV>';

    $Strings = GSA('1110,1132,1133,1136,1137,1202,1204,1205,1206,1215,1216,1217,1420,1421,1422,1423,1424,1425', 0, false, true);
    $Response->C = $Strings;
    
    $Response->J = "DoGP(); GetLocation();";

    $Response->Send();

  }

  function DisplayMapPage($DID = null) {
  /*/////////////////////////////////////////////////////////////
       Author: Plottery Corp.
      Created: v1.0.0 - 2010-12-09
    Revisions: None
      Purpose: 
      Returns: Nothing
  *//////////////////////////////////////////////////////////////
  
    global $SessionID;
    global $LanguageID;
    global $Response;
    global $UserID;
   
    if (($SessionID == 0) && is_null($DID)) $Response->Send();

    $Response->S = true;

    if (!is_null($DID)) {
    
      $Response->R = DoToolboxRight(true).DoSingleMsgLeft($DID[2]).DoPanelRight();
      $Response->J = "DoGP(); SngDl(".$DID[0].",'".Pacify($DID[1])."',".$DID[2].",".$DID[3].",'".$DID[4]."','".Pacify($DID[5])."');";
      
      $Strings = GSA('1132,1133,1136,1137,1144,1305,1351', 0, false, true);
      $Response->C = $Strings;
      
    
    } else {

      $Response->R = DoToolboxLeft().DoToolboxRight().DoPanelLeft().DoPanelRight();
      $Response->J = "DoGP(); GetData(); setTimeout('TogPa()', 1000);";
      
      $Strings = GSA('1130,1131,1132,1133,1134,1135,1136,1137,1138,1139,1144,1003,1302,1303,1304,1305,1306,1307,1308,1319,1320,1321,1322,1323,1324,1325,1326,1327,1328,1329,1333,1334,1335,1336,1337,1338,1340,1341,1342,1343,1344,1345,1346,1347,1348,1349,1351,1352,1353,1354,1355,1358,1359,1360,1361,1362,1363,1364,1366,1421,1422,1423,1768', 0, false, true);
      $Response->C = $Strings;

    }
    
    $Response->Send();

  }
  
  function IEWarn() {
  
    global $Response;
    
    $Strings = GSA('1190,1191,1192,1193,1194,1195,1196,1197');
    
    $Response->S = true;
    $Response->R = '<DIV CLASS="ttlw">'.$Strings[1190].'<HR></DIV>
      <DIV CLASS="abs fulls flwa">
        <DIV><B>'.$Strings[1191].'</B></DIV>
        <DIV CLASS="padts">'.$Strings[1192].'</DIV>
        <DIV CLASS="padtxs">
          <UL>
            <LI><A CLASS="fklnk" HREF="http://www.opera.com">'.$Strings[1193].'</A></LI>
            <LI><A CLASS="fklnk" HREF="http://www.firefox.com">'.$Strings[1194].'</A></LI>
            <LI><A CLASS="fklnk" HREF="http://www.google.com/chrome">'.$Strings[1195].'</A></LI>
            <LI><A CLASS="fklnk" HREF="http://www.apple.com/safari">'.$Strings[1196].'</A></LI>
         </UL>
       </DIV>
        <DIV>'.$Strings[1197].'</DIV>
     </DIV>';
     
    $Response->Send();
  
  }
  
  function DisplayNewAccountPage() {
  
    global $Response;
    
    $Strings = GSA('1112,1400,1401,1402,1403,1404,1405,1406,1407,1408,1409,1410,1411', 0, false, true);
    
    $Response->S = true;
    
    $Terms = file_get_contents('./Scripts/Terms.txt', true);

    $Response->R = '  
    <DIV CLASS="ttlw">'.$Strings[1400].'<HR></DIV>
    <DIV CLASS="abs fulls fktbl flwa">
      <DIV CLASS="sctt">'.$Strings[1401].'</DIV>
      <DIV CLASS="hid" ID="NewLoginMsg"></DIV>
      <DIV><DIV CLASS="label">'.$Strings[1402].'</DIV><INPUT ID="UName" TYPE="text" CLASS="w150" MAXLENGTH=50 NAME="NewUsername"></DIV>
      <DIV><DIV CLASS="label">'.$Strings[1403].'</DIV><INPUT ID="Pass1" TYPE="password" CLASS="w150" MAXLENGTH=50 NAME="NewPasswordOne"></DIV>
      <DIV><DIV CLASS="label">'.$Strings[1404].'</DIV><INPUT ID="Pass2" TYPE="password" CLASS="w150" MAXLENGTH=50 NAME="NewPasswordTwo"></DIV>
      <DIV CLASS="sct">'.$Strings[1405].'</DIV>
      <DIV CLASS="hid" ID="NewOptsMsg"></DIV>
      <DIV><DIV CLASS="label">'.$Strings[1406].'</DIV><INPUT ID="FName" TYPE="text" CLASS="w300" MAXLENGTH=50 NAME="NewFullName"></DIV>
      <DIV><DIV CLASS="label">'.$Strings[1407].'</DIV><INPUT ID="EMail" TYPE="text" CLASS="w300" MAXLENGTH=50 NAME="NewEmail"></DIV>
      <DIV CLASS="lgl">'.$Strings[1408].'</DIV>
      <DIV CLASS="sct">'.$Strings[1409].'</DIV>
      <DIV CLASS="hid" ID="NewAgrMsg"></DIV>
      <DIV><TEXTAREA CLASS="lgltxt" READONLY>'.$Terms.'</TEXTAREA></DIV>
      <DIV CLASS="mrgl"><INPUT TYPE="checkbox" ID="Terms" VALUE="OK">&nbsp;'.$Strings[1410].'</DIV>
      <DIV CLASS="sct">'.$Strings[1411].'</DIV>
      <DIV CLASS="hid" ID="CaptchaMsg"></DIV>
      <DIV ID="RecaptchaContent"></DIV>
      <DIV CLASS="algc"><INPUT CLASS="butt mrgts" ID="NewAcctBut" TYPE="Button" VALUE="'.$Strings[1112].'" onClick="return ChkNewAcct(this);"></DIV>
   </DIV>';
   
    $Response->J = 'Recaptcha.create("6Lfwmr8SAAAAAFEmFAEUdEVGteNqW5IJDd0yKCTb", "RecaptchaContent", {theme: "clean"} ); Foc("UName");';
      
    $Response->Send();

  }
  
  function DisplayNewMessagePage() {
  
    global $Response;
    
    $Strings = GSA('1107,1600,1601,1602,1603,1604,1605,1606,1607,1608,1609,1610,1611,1612,1613');
    
    $Response->S = true;

    $Response->R = '  
    <DIV CLASS="ttlw">'.$Strings[1107].'<HR></DIV>
    <DIV CLASS="abs mvb algc padbs"><HR><INPUT CLASS="butt" ID="NewMsgBut" TYPE="Button" VALUE="'.$Strings[1604].'" onClick="return ChkNewMsg(this);"></DIV>
    <DIV CLASS="abs fullb fktbl flwa">
      <DIV>
        <DIV CLASS="label">'.$Strings[1600].'</DIV>
        <SELECT ID="MsgF" CLASS="w300 mrgtxs">
          <OPTION VALUE="1605">'.$Strings[1605].'</OPTION>
          <OPTION VALUE="1606">'.$Strings[1606].'</OPTION>
          <OPTION VALUE="1607">'.$Strings[1607].'</OPTION>
          <OPTION VALUE="1608">'.$Strings[1608].'</OPTION>
       </SELECT>
     </DIV>
      <DIV>
        <DIV CLASS="label">'.$Strings[1601].'</DIV>
        <SELECT ID="MsgS" CLASS="w300 mrgtxs">
          <OPTION VALUE="1609">'.$Strings[1609].'</OPTION>
          <OPTION VALUE="1610">'.$Strings[1610].'</OPTION>
          <OPTION VALUE="1611">'.$Strings[1611].'</OPTION>
          <OPTION VALUE="1608">'.$Strings[1608].'</OPTION>
       </SELECT>
     </DIV>
      <DIV><DIV CLASS="label">'.$Strings[1602].'</DIV><INPUT ID="MsgE" TYPE="text" CLASS="w300 mrgtxs"></DIV>
      <DIV CLASS="mrgll"><DIV CLASS="errmsg nomrgb mrgts dno" ID="CtcMsg">'.$Strings[1613].'</DIV></DIV>
      <DIV><DIV CLASS="label">'.$Strings[1603].'</DIV><TEXTAREA ID="MsgM" CLASS="w300 h200"></TEXTAREA></DIV>
      <DIV><DIV CLASS="label">&nbsp;</DIV><DIV CLASS="sz12 w300">'.$Strings[1612].'</DIV></DIV>
   </DIV>';
   
    $Response->J = 'Foc("MsgM");';
      
    $Response->Send();
    
  }
  
  function DisplayNewReviewPage() {
  
    global $UserID;
    global $Response;
    
    $Response->J = 'ClWin(); PopErr();';
    
    if (isset($_POST['DID'])) {
      if (is_numeric($_POST['DID'])) {

        list ($QR, $DR, $T) =
          QuerySingle(
            "SELECT D.DealID, S.StoreName, COALESCE(UR.ReviewID, 0) AS RID
               FROM 4000_Deals D
              INNER JOIN 4100_Deal_Sources DS ON DS.DealSourceID = D.DealSourceID
              INNER JOIN 2000_Stores S ON D.StoreID = S.StoreID
               LEFT JOIN (SELECT ReviewID, StoreID, UserID FROM 1300_User_Reviews) UR ON UR.UserID = ".$UserID." AND UR.StoreID = S.StoreID
              WHERE D.DealID = ".(int)$_POST['DID'].";");
              
        if ($QR < 0) {
          SysLogIt('Error searching for deal information.', StatusError, ActionSelect);
        } elseif ($QR > 0) {
        
          if ($DR['RID'] != 0) {
          
            $Response->J = 'PopErr(\''.Pacify(Pacify(str_replace('%a', $DR['StoreName'], GS(1458))), true).'\')';
          
          } else {
        
            $Strings = GSA('1450,1451,1452,1453,1454,1455,1457');
            
            $Response->S = true;
            $Response->J = '';
            
            $Response->R = '
            <DIV CLASS="ttlw"><DIV>'.$Strings[1450].'</DIV><DIV CLASS="sz13">'.$Strings[1457].' '.$DR['StoreName'].'<HR></DIV></DIV>
            <DIV CLASS="abs mvb algc padbs"><HR><INPUT CLASS="butt" ID="NewRvwBut" TYPE="Button" VALUE="'.$Strings[1451].'" onClick="return ChkNewRvw(this,'.$DR['DealID'].');"></DIV>
            <DIV CLASS="abs fullb flwa sz13">
              <DIV CLASS="errmsg nomrgb mrgtxs dno" ID="RvwMsg">'.$Strings[1452].'</DIV>
              <DIV CLASS="padbxs padtxs">'.$Strings[1453].'</DIV>
              <TABLE CELLPADDING=0 CELLSPACING=0 BORDER=0 CLASS="cellc" ALIGN="Center"><TR>';
              
            for ($x=1;$x<=10;$x++) { $Response->R .= '<TD>'.$x.'</TD>'; }
              $Response->R .= '</TR><TR>';
            for ($x=1;$x<=10;$x++) { $Response->R .= '<TD><INPUT Type="Radio" NAME="Score" VALUE="'.$x.'" ID="S'.$x.'"></TD>'; }
            
            $Response->R .= '</TR></TABLE><DIV CLASS="padts padbxs">'.$Strings[1454].'</DIV><TEXTAREA ID="Rvw" CLASS="w100p h75"></TEXTAREA><DIV CLASS="algc padts padls padrs">'.$Strings[1455].'</DIV></DIV>';
            
          }
          
        }
        
      }
    }
     
    $Response->Send();
  
  }
  
  function DisplayNotifications() {
  
    global $UserID;
    global $UserFlags;
    global $Response;
    
    $Response->J = 'ClWin(); PopErr();';
    
    list ($QR, $DR, $T) =
      QuerySingle(
        "SELECT UN.Settings, UNIX_TIMESTAMP(UN.SentDate) AS SentDate, U.UserEmail, U.UserEmailVerified AS UEV
           FROM 1000_Users U
           LEFT JOIN 1400_User_Notifications UN ON UN.UserID = U.UserID
          WHERE U.UserID = ".$UserID.";");
          
    if ($QR < 1) {
    
      SysLogIt('Error searching for user notification information.', StatusError, ActionSelect);
      
    } else {

      $Strings = GSA('1700,1701,1702,1703,1705,1706,1707,1708,1709,1710,1711,1712,1713,1714,1715,1716,1717,1718,1719,1720,1721,1722,1723');
      
      $ErrMsg = '';
      $NotDis = false;
      $NotYes = false;
      $NotVal = 2 | 16;
      $NotLst = $Strings[1715];
      
      if (is_null($DR['UserEmail'])) {
      
        $NotDis = true;
        $ErrMsg = $Strings[1716].' <SPAN CLASS="fklnk" onClick="EdtAct();">'.$Strings[1717].'</SPAN>';
        
      } elseif ($DR['UEV'] != 1) {
      
        $NotDis = true;
        $ErrMsg = $Strings[1718].' <SPAN CLASS="fklnk" onClick="EdtAct();">'.$Strings[1717].'</SPAN>';
      
      } elseif (!is_null($DR['Settings'])) {
      
        if ($DR['Settings'] > 0) {
          $NotYes = true;
          $NotVal = $DR['Settings'];
        }
        
        if (($DR['SentDate'] > 0) && (date('Y', $DR['SentDate']) != 1969)) $NotLst = date('Y-m-d H:i:s', $DR['SentDate']);

      }
      
      $Response->S = true;
      $Response->J = '';
      
      $Response->R = '
      <DIV CLASS="ttlw"><DIV>'.$Strings[1700].'</DIV><HR></DIV>
      <DIV CLASS="abs mvb algc padbs"><HR><INPUT CLASS="butt" ID="NotBut" TYPE="Button" VALUE="'.$Strings[1712].'" onClick="return ChkNot(this);"></DIV>
      <DIV CLASS="abs fullb flwa sz13">
        <DIV CLASS="padl padr sz14">
          <DIV CLASS="errmsg mrgbs mrgtxs '.(($NotDis)?'din':'dno').'" ID="NotMsg">'.$ErrMsg.'</DIV>
          <DIV CLASS="sz15 b">'.$Strings[1720].'</DIV>
          <DIV CLASS="">'.$Strings[1721].'</DIV>
          <DIV CLASS="padts"><INPUT TYPE="Radio" ID="RDN" NAME="NotRmd" '.( ((($UserFlags & UserReminders) != UserReminders) || $NotDis)?'CHECKED':'' ).'>'.$Strings[1722].'</DIV>
          <DIV CLASS="padtxs '.(($NotDis)?'fdh':'').'"><INPUT TYPE="Radio" ID="RDY" NAME="NotRmd" '.( ((($UserFlags & UserReminders) == UserReminders) && !$NotDis)?'CHECKED':'' ).' '.(($NotDis)?'DISABLED':'').'>'.$Strings[1723].'</DIV>
          <DIV CLASS="padt sz15 b">'.$Strings[1719].'</DIV>
          <DIV CLASS="">'.$Strings[1713].'</DIV>
          <DIV CLASS="padts"><INPUT TYPE="Radio" ID="NTN" NAME="NotTog" '.((!$NotYes || $NotDis)?'CHECKED':'').' onClick="ClRepI(\'TogPnl\', \'fdn\', \'fdh\');">'.$Strings[1701].'</DIV>
          <DIV CLASS="padtxs '.(($NotDis)?'fdh':'').'"><INPUT TYPE="Radio" ID="NTY" NAME="NotTog" '.(($NotYes && !$NotDis)?'CHECKED':'').' '.(($NotDis)?'DISABLED':'').' onClick="ClRepI(\'TogPnl\', \'fdh\', \'fdn\');">'.$Strings[1702].'
            <DIV ID="TogPnl" CLASS="padl padts '.(($NotYes)?'fdn':'fdh').'">
              <DIV CLASS="fll fra w150 h125">
                <DIV CLASS="padts padbs">
                  <DIV CLASS="algc b sz12">'.$Strings[1708].'<HR></DIV>
                  <DIV CLASS="padtxs"><INPUT TYPE="Radio" ID="NT1" NAME="NotTim" '.((($NotVal & 1) == 1)?'CHECKED':'').'>'.$Strings[1705].'</DIV>
                  <DIV CLASS="padtxs"><INPUT TYPE="Radio" ID="NT2" NAME="NotTim" '.((($NotVal & 2) == 2)?'CHECKED':'').'>'.$Strings[1706].'</DIV>
                  <DIV CLASS="padtxs"><INPUT TYPE="Radio" ID="NT3" NAME="NotTim" '.((($NotVal & 4) == 4)?'CHECKED':'').'>'.$Strings[1707].'</DIV>
               </DIV>
             </DIV>
              <DIV CLASS="fll w20">&nbsp;</DIV>
              <DIV CLASS="fll fra w150 h125">
                <DIV CLASS="padts padbs">
                  <DIV CLASS="algc b sz12">'.$Strings[1709].'<HR></DIV>
                  <DIV CLASS="padtxs"><INPUT TYPE="Radio" ID="NT4" NAME="NotFrm" '.((($NotVal & 8) == 8)?'CHECKED':'').'>'.$Strings[1710].'</DIV>
                  <DIV CLASS="padtxs"><INPUT TYPE="Radio" ID="NT5" NAME="NotFrm" '.((($NotVal & 16) == 16)?'CHECKED':'').'>'.$Strings[1711].'</DIV>
               </DIV>
             </DIV>
              <DIV CLASS="clr padts">'.$Strings[1703].'</DIV>
              <DIV CLASS="padts sz11">'.$Strings[1714].' '.$NotLst.'</DIV>
           </DIV>
         </DIV>
       </DIV>
     </DIV>';
     
    }
   
    $Response->Send();
  
  }
  
  function DisplayAccount() {
  
    global $UserID;
    global $Response;
    
    $Response->J = 'ClWin(); PopErr();';
    
    list ($QR, $DR, $T) =
      QuerySingle(
        "SELECT U.UserEmail, U.UserName, U.UserUsername, U.UserEmailVerified AS UEV
           FROM 1000_Users U
          WHERE U.UserID = ".$UserID.";");
          
    if ($QR < 1) {
    
      SysLogIt('Error searching for user account information.', StatusError, ActionSelect);
      
    } else {

      $Strings = GSA('1750,1751,1752,1753,1754,1755,1756,1757,1758,1759,1760,1761,1762,1763,1764,1765,1766,1767', 0, false, true);
      $DoVerify = false;
      
      if (is_null($DR['UserEmail'])) {
        $EMail = ' <SPAN CLASS="red">'.$Strings['1767'].'</SPAN>';
      } else {
        $EMail = StringAdjust($DR['UserEmail']);
        if ($DR['UEV'] != 1) {
          $EMail .= ' <SPAN CLASS="red">('.$Strings['1765'].')</SPAN>';
          $DoVerify = true;
        }
      }
      
      $Response->S = true;
      $Response->J = '';
      
      $Response->R = '
      <DIV CLASS="ttlw"><DIV>'.$Strings[1750].' '.StringAdjust($DR['UserUsername']).'</DIV><HR></DIV>
      <DIV CLASS="abs mvb algc padbs"><HR><INPUT CLASS="butt" ID="AccBut" TYPE="Button" VALUE="'.$Strings[1755].'" onClick="return ChkAcc(this);">';
      
      if ($DoVerify) $Response->R .= '&nbsp;&nbsp;&nbsp;<INPUT CLASS="butt padl" ID="VerBut" TYPE="Button" VALUE="'.$Strings[1766].'" onClick="VerEml(this);">';
      
      $Response->R .= '</DIV>
      <DIV CLASS="abs fullb flwa sz14">
        <DIV CLASS="padl padr sz14">
          <DIV CLASS="errmsg mrgbs mrgtxs dno" ID="AccMsg"></DIV>
          <DIV>'.$Strings[1759].'</DIV>
          <DIV CLASS="padt sz15 b">'.$Strings[1763].'</DIV>
          <DIV>'.$Strings[1762].'</DIV>
          <DIV CLASS="padl padts">
            <DIV><DIV CLASS="label">'.$Strings[1764].'</DIV><INPUT ID="FName" TYPE="text" CLASS="w300" MAXLENGTH=50 NAME="NewFullName" VALUE="'.StringAdjust($DR['UserName']).'"></DIV>
         </DIV>
          <DIV CLASS="padt sz15 b">'.$Strings[1751].'</DIV>
          <DIV>'.$Strings[1760].'</DIV>
          <DIV CLASS="padl padts">
            <DIV><DIV CLASS="label">'.$Strings[1752].'</DIV><INPUT ID="Pass0" TYPE="password" CLASS="w150" MAXLENGTH=50 NAME="NewPasswordOld"></DIV>
            <DIV CLASS="padtxs"><DIV CLASS="label">'.$Strings[1753].'</DIV><INPUT ID="Pass1" TYPE="password" CLASS="w150" MAXLENGTH=50 NAME="NewPasswordOne"></DIV>
            <DIV CLASS="padtxs"><DIV CLASS="label">'.$Strings[1754].'</DIV><INPUT ID="Pass2" TYPE="password" CLASS="w150" MAXLENGTH=50 NAME="NewPasswordTwo"></DIV>
         </DIV>
          <DIV CLASS="padt sz15 b">'.$Strings[1756].'</DIV>
          <DIV>'.$Strings[1761].'</DIV>
          <DIV CLASS="padl padts">
            <DIV CLASS="errmsg nomrgb mrgts dno" ID="AccEMlMsg"></DIV>
            <DIV><DIV CLASS="label">'.$Strings[1757].'</DIV>'.$EMail.'</DIV>
            <DIV CLASS="padtxs"><DIV CLASS="label">'.$Strings[1758].'</DIV><INPUT ID="EMail" TYPE="text" CLASS="w300" MAXLENGTH=50 NAME="NewEmail"></DIV>
         </DIV>
       </DIV>
     </DIV>';
     
    }
   
    $Response->Send();
  
  }  
  
  function DisplayShareOptions() {
  
    global $UserID;
    global $Response;
    
    $Response->J = 'ClWin(); PopErr();';
    
    if (isset($_POST['DID'])) {
      if (is_numeric($_POST['DID'])) {
      
        list ($QR, $DR, $T) =
          QuerySingle(
            "SELECT D.DealID
               FROM 4000_Deals D
              WHERE D.DealID = ".(int)$_POST['DID'].";");
              
        if ($QR < 0) {
        
          SysLogIt('Error searching for deal information for sharing.', StatusError, ActionSelect);
          
        } elseif ($QR > 0) {
        
          if (isset($_POST['Name']) && isset($_POST['Adr']) && isset($_POST['Note'])) {
          
            if ((trim($_POST['Name']) != '') && (trim($_POST['Adr']) != '')) {
            
              $Strings = GSA('1570,1571,1572,1573,1574,1575');
            
              $Msg = trim($_POST['Name']).' '.$Strings[1571].' http://www.dealplotter.com/?'.$DR['DealID'];
              if (trim($_POST['Note']) != '') $Msg .= ' '.$Strings[1572].' "'.Fix(trim($_POST['Note'])).'"';
              $Msg .= PHP_EOL.PHP_EOL.$Strings[1573].PHP_EOL.PHP_EOL.$Strings[1574];
              
              if (SendMail($Msg, trim($_POST['Name']).' '.$Strings[1575], trim($_POST['Adr']))) {
              
                $Note = 'NULL';
                if (trim($_POST['Note']) != '') $Note = "'".Pacify(trim($_POST['Note']))."'";
              
                if (!ExecCommand("INSERT INTO 4300_Deal_Shares (DealID, UserID, ShareDate, ShareName, ShareAdr, ShareNote)
                                 VALUES (".$DR['DealID'].",".$UserID.",".date('YmdHis').",'".Pacify(trim($_POST['Name']))."','".Pacify(trim($_POST['Adr']))."',".$Note.");"))
                  SysLogIt('Error adding share details.', StatusError, ActionInsert);
              
                $Response->S = true;
                $Response->J = 'PopC(\''.Pacify(Pacify($Strings[1570]), true).'\');';
                
              }
            
            } else {
            
              $Response->J = "ClRep(getI('MailMsg'), 'dno', 'din');";
            
            }
        
          } else {

          
            $UserFull = '';
            if ($UserID > 0) {
            
              list ($QR, $SDR, $T) =
                QuerySingle(
                  "SELECT U.UserName
                     FROM 1000_Users U
                    WHERE U.UserID = ".$UserID.";");
                    
              if ($QR > 0) $UserFull = $SDR['UserName'];
            
            }
        
            $Strings = GSA('1560,1561,1562,1563,1564,1565,1566,1567,1568,1569,1576');
            
            $Response->S = true;
            $Response->J = 'DoGP();';
            
            $Response->R = '
            <DIV CLASS="ttlw"><DIV>'.$Strings[1560].'</DIV><HR></DIV>
            <DIV CLASS="abs mvb algc padbs"><HR>
               <DIV CLASS="din">
                 <DIV CLASS="fll pnt butt tsfb wht mgrrxs" onClick="PWin(\'http://www.facebook.com/share.php?u=http://www.dealplotter.com/?'.$DR['DealID'].'\'); ClWin();"><DIV CLASS="padlm">'.$Strings[1561].'</DIV></DIV>
                 <DIV CLASS="fll pnt butt tstw wht" onClick="PWin(\'http://twitter.com/home?status='.$Strings[1576].' http://www.dealplotter.com/?'.$DR['DealID'].'\'); ClWin();"><DIV CLASS="padlm">'.$Strings[1562].'</DIV></DIV>
                 <DIV CLASS="gpb" ID="GPBS"><g:plusone annotation="none" href="http://www.dealplotter.com/?'.$DR['DealID'].'"></g:plusone></DIV>
              </DIV>
           </DIV>
            <DIV CLASS="abs fullb flwa sz13">
              <DIV CLASS="algc padbs">'.$Strings[1569].'</DIV>
              <DIV CLASS="errmsg mrgbxs dno" ID="MailMsg">'.$Strings[1568].'</DIV>
              <DIV><DIV CLASS="label">'.$Strings[1565].'</DIV><INPUT TYPE="text" ID="MailName" CLASS="w170" VALUE="'.Fix($UserFull).'" MAXLENGTH=30></DIV>
              <DIV><DIV CLASS="label">'.$Strings[1566].'</DIV><INPUT TYPE="text" ID="MailAdr" CLASS="w170" VALUE="" MAXLENGTH=30></DIV>
              <DIV><DIV CLASS="label">'.$Strings[1567].'</DIV><TEXTAREA ID="MailNote" CLASS="w170 h40"></TEXTAREA></DIV>
              <DIV CLASS="algc padtxs"><INPUT TYPE="Button" ID="NewMailBut" CLASS="din butt padr padl pnt" onClick="ChkNewMail(this,'.$DR['DealID'].');" VALUE="'.$Strings[1563].'"></DIV>
              <DIV CLASS="padtxs padbxs"><HR><INPUT TYPE="text" CLASS="w100p algc" VALUE="http://www.dealplotter.com/?'.$DR['DealID'].'"></DIV>
             ';
              
          }
          
        }
        
      }
    }
     
    $Response->Send();
  
  }
  
  function DisplayHistory() {
  
    global $UserID;
    global $Response;
    
    $Response->J = 'ClWin(); PopErr();';
    
    if (isset($_POST['DID'])) {
      if (is_numeric($_POST['DID'])) {
      
        list ($QR, $DR, $T) =
          QuerySingle(
            "SELECT D.DealID, D.StoreID, S.StoreName, SH.Filename
               FROM 4000_Deals D
              INNER JOIN 2000_Stores S ON D.StoreID = S.StoreID
               LEFT JOIN 2600_Store_History SH ON D.StoreID = SH.StoreID
              WHERE D.DealID = ".(int)$_POST['DID'].";");
              
        if ($QR < 0) {
        
          SysLogIt('Error searching for deal information for history.', StatusError, ActionSelect);
          
        } elseif ($QR > 0) {
        
          //if (1 == 1) {
          if (is_null($DR['Filename'])) {
            $Filename = DoHistoryGraph($DR['StoreID']);
          } else {
            $Filename = $DR['Filename'];
          }
          
          if ($Filename !== false) {
          
            $Strings = GSA('1695,1696');
          
            $Response->S = true;
            $Response->J = '';
            
            $Response->R = '
            <DIV CLASS="ttlw"><DIV>'.$Strings[1695].' '.StringAdjust($DR['StoreName']).'</DIV><HR></DIV>
            <DIV CLASS="abs fulls flwa sz13 algc">
              <OBJECT TYPE="image/svg+xml" CLASS="mgrx" WIDTH="400" DATA="/Hist/'.$Filename.'">
                '.$Strings[1696].'
             </OBJECT>
           </DIV>';
          
          }

        }
        
      }
    }
     
    $Response->Send();
  
  }   
  
  function DisplayNewFavoritePage() {
  
    global $UserID;
    global $Response;
    
    $Strings = GSA('1110,1207,1675,1676,1677,1678,1679,1680,1681,1682,1683');
    
    $Response->S = true;
    $Response->R = '
      <DIV CLASS="ttlw"><DIV>'.$Strings[1675].'<HR></DIV></DIV>
      <DIV CLASS="abs mvb algc padbs"><HR><INPUT CLASS="butt" ID="NewFavBut" TYPE="Button" VALUE="'.$Strings[1676].'" onClick="return ChkNewFav(this);"></DIV>
      <DIV CLASS="abs fullb fktbl flwa sz13">
        <DIV CLASS="errmsg nomrgb mrgtxs dno" ID="FavMsg">'.$Strings[1677].'</DIV>
        <DIV><DIV CLASS="slabel">'.$Strings[1678].'</DIV><INPUT ID="MName" TYPE="text" CLASS="w200"></DIV>
        <DIV CLASS="errmsg nomrgb mrgtxs dno" ID="LocMsg">'.$Strings[1683].'</DIV>
        <DIV><DIV CLASS="slabel">'.$Strings[1682].'</DIV><INPUT ID="PCode" TYPE="text" CLASS="w100"></DIV>
        <DIV><DIV CLASS="slabel">'.$Strings[1679].'</DIV><INPUT ID="FValue" TYPE="text" CLASS="w40 algc" VALUE="0" MAXLENGTH="3"></DIV>
        <DIV>
          <DIV CLASS="slabel">'.$Strings[1680].'</DIV>
          <INPUT ID="Expiry0" NAME="Expiry" TYPE="radio" VALUE="0" CHECKED>&nbsp;'.$Strings[1681].'<BR />
          <INPUT ID="Expiry1" NAME="Expiry" TYPE="radio" VALUE="1">
          <SELECT ID="ExpY" CLASS="w80">';

    for ($x=date('Y');$x<=date('Y')+5;$x++) {
      $Response->R .= '<OPTION VALUE="'.$x.'">'.$x.'</OPTION>';
    }
    
    $Response->R .= '
         </SELECT>
          <SELECT ID="ExpM" CLASS="w40">';

    for ($x=1;$x<=12;$x++) {
      $Response->R .= '<OPTION VALUE="'.$x.'">'.$x.'</OPTION>';
    }

    $Response->R .= '
         </SELECT>
          <SELECT ID="ExpD" CLASS="w40">';

    for ($x=1;$x<=31;$x++) {
      $Response->R .= '<OPTION VALUE="'.$x.'">'.$x.'</OPTION>';
    }

    $Response->R .= '
         </SELECT>
       </DIV>
     </DIV>';
      
    $Response->J = "Foc('MName');";
    $Response->Send();
  
  }

  function DisplayNewLocation() {
  
    global $Response;
    
    $Response->S = true;
    
    $Strings = GSA('1110,1207,1312');
    
    $Response->R = '
      <DIV CLASS="ttlw">'.$Strings[1312].'<HR></DIV>
      <DIV CLASS="abs fulls fktbl flwa">
        <DIV CLASS="algc sz12">
          <FORM onSubmit="ChkAddr(\'SubLoc\'); return false;" accept-charset="UTF-8">
            <DIV ID="LocMsg" CLASS=""></DIV>
            <INPUT TYPE="Hidden" NAME="Coords" ID="Coords">      
            <INPUT CLASS="wide algc" TYPE="text" NAME="InLoc" ID="InLoc" onChange="FndLoc=0;"><BR /><EM>'.$Strings[1207].'</EM><BR />
            <INPUT CLASS="butt mrgts" TYPE="submit" ID="SubLoc" VALUE="'.$Strings[1110].'">
         </FORM>
       </DIV>
    </DIV>';
    
    $Response->J = "Foc('InLoc');";
    
    $Response->Send();
  
  }
  
  function DisplayNameLocation() {
  
    global $Response;
    
    $Response->S = true;
    
    $Strings = GSA('1313,1314,1315,1316,1676');
    
    $Response->R = '
      <DIV CLASS="ttlw">'.$Strings[1313].'<HR></DIV>
      <DIV CLASS="abs mvb algc padbs"><HR><INPUT CLASS="butt" ID="LocNamBut" TYPE="Button" VALUE="'.$Strings[1676].'" onClick="return ChkLocNam(this);"></DIV>
      <DIV CLASS="abs fullb fktbl flwa">
        <DIV CLASS="algc">
          <DIV>'.$Strings[1314].':</DIV>
          <DIV><INPUT CLASS="boxmax" ID="LocNam" MAXLENGTH=20 VALUE="'.$Strings[1316].'"></DIV>
          <DIV CLASS="sz12">'.$Strings[1315].'</DIV>
       </DIV>
    </DIV>';
    
    $Response->J = "Foc('LocNam');";
    
    $Response->Send();
  
  }  
  
  function DisplayResetPass() {
  
    global $Response;
    
    $Response->S = true;
    
    $Strings = GSA('1080,1081,1082');
    
    $Response->R = '
      <DIV CLASS="ttlw">'.$Strings[1080].'<HR></DIV>
      <DIV CLASS="fulls">
        <DIV CLASS="algc sz14">
          <DIV CLASS="errmsg dno" ID="RstPwdMsg"></DIV>
          <DIV CLASS="algl padbxs">'.$Strings[1081].'</DIV>
          <DIV><INPUT CLASS="boxmax" ID="RPEMail" MAXLENGTH=50></DIV>
          <DIV><INPUT TYPE="button" CLASS="butt mrgts" VALUE="'.$Strings[1082].'" ID="RstPwdBut" onClick="ChkRstPwd(this);"></DIV>
       </DIV>
    </DIV>';
    
    $Response->J = "Foc('RPEMail');";
    
    $Response->Send();
  
  }
  
  function DisplayAdministration() {
  
    global $UserFlags;
    global $Response;
    
    $Strings = GSA('1500,1501,1502,1503,1504,1505,1506,1507,1508');
    
    if ($UserFlags >= 32) {
    
      $Response->S = true;
      $Response->R = '<DIV CLASS="ttlw">'.$Strings[1500].'<HR></DIV><DIV CLASS="abs fulls fktbl flwa">';
      if (($UserFlags & UserCanViewLog) == UserCanViewLog) $Response->R .= '<DIV CLASS="fklnk" onClick="VLog();">'.$Strings[1507].'</DIV>';
      if (($UserFlags & UserCanViewStatus) == UserCanViewStatus) $Response->R .= '<DIV CLASS="fklnk" onClick="VStt();">'.$Strings[1508].'</DIV>';
      if (($UserFlags & UserCanRunUpdate) == UserCanRunUpdate) $Response->R .= '<DIV CLASS="fklnk" onClick="RunUp();">'.$Strings[1501].'</DIV>';
      if (($UserFlags & UserCanEditTags) == UserCanEditTags) $Response->R .= '<DIV CLASS="fklnk" onClick="EdtTyp();">'.$Strings[1504].'</DIV>';
      if (($UserFlags & UserCanEditStrings) == UserCanEditStrings) $Response->R .= '<DIV CLASS="fklnk" onClick="EdtStr();">'.$Strings[1502].'</DIV>';
      if (($UserFlags & UserCanEditTags) == UserCanEditTags) $Response->R .= '<DIV CLASS="fklnk" onClick="EdtTag();">'.$Strings[1503].'</DIV>';
      if (($UserFlags & UserCanEditSources) == UserCanEditSources) $Response->R .= '<DIV CLASS="fklnk" onClick="EdtSrc();">'.$Strings[1506].'</DIV>';
      if (($UserFlags & UserCanEditDivisions) == UserCanEditDivisions) $Response->R .= '<DIV CLASS="fklnk" onClick="EdtDiv();">'.$Strings[1505].'</DIV>';
      $Response->R .= '</DIV>';
      $Response->J = '';
    
    } else {
      
      $Response->J = 'F5();';
      
    }
    
    $Response->Send();
  
  }
  
  function DisplayWarning() {

    global $Response;
    
    $Response->J = 'PopErr()';
    
    if (isset($_POST['I'])) {
      if (is_numeric($_POST['I'])) {
      
        $Strings = GSA('1375,1376,1377,1378');
  
        $Response->S = true;

        $Response->R = '<DIV CLASS="ttlw">'.$Strings[1375].'<HR></DIV>
                        <DIV CLASS="abs mvb algc"><INPUT TYPE="button" CLASS="butt mrgbxs pnt" VALUE="'.$Strings[1378].'" onClick="ShwWrn = true; Buy('.(int)$_POST['I'].'); ClWin();"></DIV>
                        <DIV CLASS="algc">
                          <DIV CLASS="sz14">'.$Strings[1376].'<BR /><BR /><BR /></DIV>
                       </DIV>';

      }
    }
      
    $Response->Send();
    
  }   
  
  function GetLanguages() {
  
    global $Response;
  
    list ($QR, $RS, $T) = QuerySet("SELECT LanguageID, LanguageName FROM 0000_Languages WHERE LanguageActive = 1 ORDER BY LanguageName;");      

    if ($QR < 1) {
      SysLogIt('Error counting languages.', StatusError, ActionSelect);
      $Response->Send();
    }
    
    $Response->R = '<DIV CLASS="ttlw">'.GS(1550).'<HR></DIV><DIV CLASS="fulls fktbl">';
    while ($DR = mysql_fetch_array($RS)) {
      $Response->R .= '<DIV CLASS="fklnk" onClick="SetLng('.$DR['LanguageID'].');">'.$DR['LanguageName'].'</DIV>';
    }
    $Response->R .= '</DIV>';
    $Response->S = true;
    $Response->Send();
  
  }
  
  function GetSorts() {

    global $Response;
    
    $Strings = GSA('1309,1310,1380,1381,1382,1383,1384');
  
    $Response->R = '<DIV CLASS="ttlw">'.$Strings[1310].'<HR></DIV><DIV CLASS="fulls fktbl">
      <DIV CLASS="fklnk" onClick="ChgSrt(0,\''.Pacify($Strings[1380]).'\');">'.$Strings[1309].' '.$Strings[1380].'</DIV>
      <DIV CLASS="fklnk" onClick="ChgSrt(1,\''.Pacify($Strings[1381]).'\');">'.$Strings[1309].' '.$Strings[1381].'</DIV>
      <DIV CLASS="fklnk" onClick="ChgSrt(2,\''.Pacify($Strings[1382]).'\');">'.$Strings[1309].' '.$Strings[1382].'</DIV>
      <DIV CLASS="fklnk" onClick="ChgSrt(3,\''.Pacify($Strings[1383]).'\');">'.$Strings[1309].' '.$Strings[1383].'</DIV>
      <DIV CLASS="fklnk" onClick="ChgSrt(4,\''.Pacify($Strings[1384]).'\');">'.$Strings[1309].' '.$Strings[1384].'</DIV>
     </DIV>';
     
    $Response->S = true;
    $Response->Send();
    
  }
  
  function GetTerms() {

    global $Response;
    
    $Terms = file_get_contents('./Scripts/Terms.txt', true);
  
    $Response->R = '<DIV CLASS="ttlw">'.GS(1010).'<HR></DIV><DIV CLASS="abs fulls fktbl flwa">'.StringAdjust($Terms, false, true).'</DIV>';
     
    $Response->S = true;
    $Response->Send();
    
  }

  function GetReviews() {
  
    global $Response;
    global $LanguageID;
    
    if (isset($_POST['DID']) && isset($_POST['O'])) {
      if (is_numeric($_POST['DID']) && is_numeric($_POST['O'])) {
      
      list ($QR, $DR, $T) =
        QuerySingle(
          "SELECT D.DealID, D.StoreID, S.StoreName, COUNT(UR.ReviewID) AS RCount
             FROM 4000_Deals D
            INNER JOIN 2000_Stores S ON D.StoreID = S.StoreID
             LEFT JOIN 1300_User_Reviews UR ON UR.StoreID = S.StoreID AND UR.Status = 1
            WHERE D.DealID = ".(int)$_POST['DID']."
            GROUP BY D.DealID;");
            
        if ($QR < 0) {
        
          SysLogIt('Error locating deal.', StatusError, ActionSelect);
        
        } elseif ($QR > 0) {
        
          list ($QR, $RS, $T) = QuerySet(
            'SELECT UR.Score, UR.Comments, COALESCE(U.UserName, U.UserUsername) AS Name, UNIX_TIMESTAMP(UR.ReviewDate) AS RDate
               FROM 1300_User_Reviews UR
              INNER JOIN 1000_Users U ON UR.UserID = U.UserID
              WHERE UR.StoreID = '.$DR['StoreID'].'
                AND UR.Status = 1
                AND UR.LanguageID = '.$LanguageID.'
              ORDER BY UR.ReviewID DESC
              LIMIT '.(int)$_POST['O'].','.ReviewsPerPage.';');
          
          if ($QR < 0) {
          
             SysLogIt('Error locating reviews for store with ID of '.$DR['StoreID'].'.', StatusError, ActionSelect);
          
          } else {
          
            $Response->S = true;
            $Response->C = '';
            
            $Strings = GSA('1480,1481,1482,1483,1484,1485');
          
            if ((int)$_POST['O'] === 0) $Response->R .= '<DIV CLASS="ttlw"><DIV>'.$Strings[1480].'</DIV><DIV CLASS="sz13">'.$Strings[1484].' '.$DR['StoreName'].'<HR></DIV></DIV><DIV CLASS="abs mvb algc" ID="RvwsB"></DIV><DIV CLASS="abs fullsb flwa" ID="RvwsD">';
         
            while ($SDR = mysql_fetch_array($RS)) {
              $Response->R .= '<DIV CLASS="padts"><DIV CLASS="w30 fll sz24 mgrrs dkgray fra algc"><B>'.$SDR['Score'].'</B></DIV><DIV CLASS="sz12">'.$Strings[1481].' '.$SDR['Name'].', '.$Strings[1482].' '.date('Y-m-d H:i:s', $SDR['RDate']).'</DIV><DIV>'.StringAdjust($SDR['Comments']).'</DIV></DIV>';
            }
            
            if ((int)$_POST['O'] === 0) $Response->R .= '</DIV>';
            
            $Response->D = '<DIV CLASS="sz12 mrgbxs"><HR>'.str_replace('%b', $DR['RCount'], str_replace('%a', (((int)$_POST['O']+ReviewsPerPage > $DR['RCount'])?$DR['RCount']:(int)$_POST['O']+ReviewsPerPage), $Strings[1485])).'</DIV>';
            if ((int)$_POST['O']+ReviewsPerPage < $DR['RCount']) $Response->C = '<DIV><INPUT TYPE="button" CLASS="butt mrgbxs pnt" VALUE="'.$Strings[1483].'" onClick="GetRvw('.$DR['DealID'].','.((int)$_POST['O']+ReviewsPerPage).');"></DIV>';
          
          }
          
        }

      }
    }
    
    $Response->Send();
  
  }
  
  function GetUserLocations() {

    global $Response;
    
    global $UserID;
    global $SessionID;
    
    list ($QR, $RS, $T) = QuerySet(
      'SELECT L.LocationID, L.LocationLatitude AS Lat, L.LocationLongitude AS Lng, L.CountryID AS CID, UL.UserLocationName AS UName
         FROM 0700_Sessions S
        INNER JOIN 1000_Users U ON S.UserID = U.UserID
        INNER JOIN 1100_User_Locations UL ON U.UserID = UL.UserID
        INNER JOIN 3000_Locations L ON L.LocationID = UL.LocationID
        WHERE S.SessionID = '.$SessionID.'
          AND U.UserID = '.$UserID.'
        ORDER BY UL.UserLocationName;');
        
    if ($QR < 0) {
    
      SysLogIt('Error searching for locations for user with ID of '.$UserID.'.', StatusError, ActionSelect);
    
    } elseif ($QR > 0) {
    
      $Strings = GSA('1100,1312,1317');
    
      $Response->R = '<DIV CLASS="ttlw">'.$Strings[1100].'<HR></DIV><DIV CLASS="abs mvb algc">';
      if ($QR < 5) $Response->R .= '<HR><INPUT TYPE="button" CLASS="butt mrgbxs pnt" VALUE="'.$Strings[1312].'" onClick="NewLoc();">';
      $Response->R .= '</DIV><DIV CLASS="fullsb fktbl">';
      
       while ($DR = mysql_fetch_array($RS)) {
         $Response->R .= '<DIV CLASS="fll sz18 b algc padrs w30" ID="LDC'.$DR['LocationID'].'"></DIV><DIV CLASS="flr w30 mrgtxs"><INPUT TYPE="image" CLASS="butt fklnk" SRC="/IF/Icon-Trash.png" onClick="PopQ(\''.Pacify(Pacify(str_replace('%a', StringAdjust($DR['UName']), $Strings[1317])), true).'\', \'DelLoc('.$DR['LocationID'].');\');" onMouseOver="DoHlp(this,1366);" onMouseOut="KlHlp();"></DIV><DIV CLASS="sz12"><SPAN CLASS="fklnk sz15" onClick="SetCLoc('.$DR['LocationID'].');">'.StringAdjust($DR['UName']).'</SPAN><BR />'.$DR['Lat'].', '.$DR['Lng'].'</DIV><DIV CLASS="clr"></DIV>';
       }
       
      $Response->R .= '</DIV>';
       
      $Response->J = 'UpLTot();';
      $Response->S = true;
      
    }
    
    $Response->Send();
    
  }  
  
  function GetDetails() {
  
    global $LanguageID;
    global $Response;
    global $UserID;
    
    $Strings = GSA('1000,1329,1330,1331,1332,1334,1339,1350,1367,1650,1651,1652,1653,1654,1655,1656,1657,1658,1659,1660,1661,1662,1663,1664,1665,1666,1667,1669,1670,1671,1672,1673', $LanguageID, false, true);
    
    if (isset($_POST['DID'])) {
      if (is_numeric($_POST['DID'])) {

        list ($QR, $DR, $T) =
          QuerySingle(
            "SELECT COALESCE(LSDa.StringText, LSDb.StringText) AS DText, D.DealPrice AS DPrice, D.DealQR AS QR, DC.MPrice, COALESCE(DC.DCount, 1) AS DCount,
                COALESCE(UR.RAvg, 0) AS RAvg, COALESCE(UR.RCount, 0) AS RCount, COALESCE(DCT.CCount, 0) AS CCount, COALESCE(URx.Score, 0) AS MyScore,
                S.StoreID, S.StoreName AS SName, S.StoreWebsite AS SWeb, L.LocationAddress AS Adr, DS.DealSourceName AS DSName, DS.DealSourceFileName AS FName,
                UNIX_TIMESTAMP(D.DateExpiry) AS ExpDate, COUNT(L.LocationID) AS LCount, UF.FavoriteID AS FavID, L.LocationLatitude AS Lat, L.LocationLongitude AS Lng, DSU.URLID
               FROM 4000_Deals D
              INNER JOIN 4100_Deal_Sources DS ON DS.DealSourceID = D.DealSourceID
              INNER JOIN 2000_Stores S ON D.StoreID = S.StoreID
               LEFT JOIN (SELECT StoreID, AVG(Score) AS RAvg, COUNT(ReviewID) AS RCount FROM 1300_User_Reviews WHERE Status = 1 GROUP BY StoreID) UR ON UR.StoreID = S.StoreID
               LEFT JOIN (SELECT StoreID, UserID, Score FROM 1300_User_Reviews) URx ON URx.UserID = ".$UserID." AND URx.StoreID = S.StoreID
               LEFT JOIN 2200_Store_Locations SL ON SL.StoreID = S.StoreID
               LEFT JOIN 3000_Locations L ON SL.LocationID = L.LocationID AND L.LocationLatitude != -1
               LEFT JOIN 0200_Language_Strings LSDa ON D.StringID = LSDa.StringID AND LSDa.LanguageID = ".$LanguageID."
               LEFT JOIN 0200_Language_Strings LSDb ON D.StringID = LSDb.StringID AND LSDb.LanguageID = 1
               LEFT JOIN (SELECT StoreID, COUNT(DealID) AS DCount, MIN(DealPrice) AS MPrice FROM 4000_Deals GROUP BY StoreID) DC ON DC.StoreID = S.StoreID
               LEFT JOIN (SELECT DealID, COUNT(ClickID) AS CCount FROM 4200_Deal_Clickthroughs WHERE DealID = ".(int)$_POST['DID']." GROUP BY DealID) DCT ON DCT.DealID = D.DealID
               LEFT JOIN 1200_User_Favorites UF ON UF.DealID = D.DealID AND UF.UserID = ".$UserID."
               LEFT JOIN (SELECT URLID, Latitude, Longitude FROM 4110_Deal_Source_URLs GROUP BY Latitude, Longitude) DSU ON DSU.Latitude = L.LocationLatitude AND DSU.Longitude = L.LocationLongitude
              WHERE D.DealID = ".(int)$_POST['DID']."
              GROUP BY D.DealID;");
              
        if ($QR < 0) {
        
          SysLogIt('Error finding deal with ID of '.(int)$_POST['DID'].'.', StatusError, ActionSelect);
        
        } elseif ($QR > 0) {
        
          $Response->S = true;
          $Response->C = (int)$_POST['DID'];
          
          $Response->R = array();
          $Response->R[2] = 0;
          $Response->R[3] = 0;
          
          //Do header
          //-------------------
          
          $Response->R[0] = '';
          if (!isset($_POST['SM'])) $Response->R[0] = ' <DIV CLASS="cls z3" onClick="HPanR();">'.Pacify($Strings[1000]).'</DIV>';
          
          $Response->R[0] .= '<DIV CLASS="b sz18 padr w100p nowr flwh">'.$DR['SName'].'</DIV>
                            <DIV CLASS="sz13">';
          if (!(is_null($DR['SWeb']) || trim($DR['SWeb']) == '')) $Response->R[0] .= '<DIV><A HREF="'.$DR['SWeb'].'" TARGET="_blank">'.$Strings[1650].'</A></DIV><DIV>';
          if ($DR['LCount'] > 0) $Response->R[0] .= '   <DIV>'.(($DR['LCount']==1)?$DR['Adr']:str_replace('%a', $DR['LCount'], $Strings[1665]));
          $Response->R[0] .= '</DIV><HR>';
          
          //Do buttons
          //-------------------
          
          $Response->R[0] .= '<DIV CLASS="sz14 w100p nowr flwh h30" ID="DetB">
                             <DIV CLASS="din butt dtb tibu mgrrxs" onClick="Buy('.(int)$_POST['DID'].')" onMouseOver="DoHlp(this,1305);" onMouseOut="KlHlp();"><DIV CLASS="padlm">'.$Strings[1330].'</DIV></DIV>';
                             
          if (!isset($_POST['SM'])) {
          
            //if (($UserID > 0) && (is_null($DR['FavID']))) $Response->R[0] .= ' <DIV CLASS="din butt dtb tisv mgrrxs" onClick="TogSav(1,'.(int)$_POST['DID'].'); RstDet('.(int)$_POST['DID'].');" onMouseOver="DoHlp(this,1306);" onMouseOut="KlHlp();"><DIV CLASS="padlm">'.$Strings[1331].'</DIV></DIV>';
            if ($UserID > 0) {
              if (is_null($DR['FavID'])) {
                $Response->R[0] .= ' <DIV CLASS="din butt dtb tisv mgrrxs" onClick="TogSav(1,'.(int)$_POST['DID'].',1);" onMouseOver="DoHlp(this,1306);" onMouseOut="KlHlp();"><DIV CLASS="padlm">'.$Strings[1331].'</DIV></DIV>';
              } else {
                $Response->R[0] .= ' <DIV CLASS="din dbutt dtb tisvd mgrrxs"><DIV CLASS="padlm">'.$Strings[1367].'</DIV></DIV>';
              }
            } else {
              $Response->R[0] .= ' <DIV CLASS="din dbutt dtb tidsv mgrrxs" onMouseOver="DoHlp(this,1334,1);" onMouseOut="KlHlp();"><DIV CLASS="padlm">'.$Strings[1331].'</DIV></DIV>';
            }
            
            if (is_null($DR['FavID'])) {
              $Response->R[0] .= '  <DIV CLASS="din butt dtb tihi mgrrxs" onClick="KlD('.(int)$_POST['DID'].'); HPanR();" onMouseOver="DoHlp(this,1307);" onMouseOut="KlHlp();"><DIV CLASS="padlm">'.$Strings[1332].'</DIV></DIV>';
            } else {
              $Response->R[0] .= '  <DIV CLASS="din butt dtb tihi mgrrxs" onClick="TogSav(0,'.(int)$_POST['DID'].',1);" onMouseOver="DoHlp(this,1352);" onMouseOut="KlHlp();"><DIV CLASS="padlm">'.$Strings[1339].'</DIV></DIV>';
            }
            
          }
          
          $Response->R[0] .= '   <DIV CLASS="din butt dtb tish mgrrxs" onClick="Share('.(int)$_POST['DID'].')" onMouseOver="DoHlp(this,1351);" onMouseOut="KlHlp();"><DIV CLASS="padlm">'.$Strings[1350].'</DIV></DIV>
                               </DIV>';
          
          $Response->R[0] .= '</DIV>';
          
          //Do content
          //-------------------
          
          $Response->D .= '<DIV CLASS="sz13 dkbl" ID="DetC">
                             <DIV CLASS="sech"><IMG SRC="/IF/H-Des.png" WIDTH=20 HEIGHT=20 ALT="" CLASS="valgm padrxs"><B>'.$Strings[1651].'</B></DIV>
                             <DIV CLASS="padls">'.$DR['DText'].'</DIV>
                             <DIV CLASS="padls">
                               <UL>';
          if (($DR['ExpDate'] > 0) && (date('Y', $DR['ExpDate']) > 1969)) {
          
            if ($DR['ExpDate'] < time()) {
              $DLeft = $Strings[1329];
            } else {
              $DLeft = round(($DR['ExpDate'] - time()) / 60 / 60 / 24);
              if ($DLeft < 180) $DLeft = '<span class="red">'.$DLeft.'</span>';
              $DLeft = $DLeft.' '.$Strings[1653];
            }

            $Response->D .= '<LI>'.$Strings[1652].' '.date('Y-m-d', $DR['ExpDate']).' ('.$DLeft.')</LI>';
            
          }
          
          $Response->D .= '     <LI>'.$Strings[1667].' <SPAN CLASS="fklnk" onClick="Buy('.(int)$_POST['DID'].')" onMouseOver="DoHlp(this,1305);" onMouseOut="KlHlp();">'.$DR['DSName'].'</SPAN>.</LI>
                              </UL>
                            </DIV>
                             <DIV CLASS="sech padts"><IMG SRC="/IF/H-Stat.png" WIDTH=20 HEIGHT=20 ALT="" CLASS="valgm padrxs"><B>'.$Strings[1654].'</B></DIV>
                             <DIV CLASS="padls">
                               <UL>
                                 <LI>'.str_replace('%a', (($DR['DCount']>1)?'<DIV CLASS="nbutt" onClick="DHist('.(int)$_POST['DID'].');" onMouseOver="DoHlp(this,1144);" onMouseOut="KlHlp();">'.$DR['DCount'].' '.$Strings[1672].'</DIV>':'<B>'.$DR['DCount'].'</B> '.$Strings[1673]), $Strings[1655]).'</LI>
                                 <LI>';
          $Response->D .= (is_null($DR['MPrice']) || $DR['DPrice'] <= $DR['MPrice'])? $Strings[1656]:$Strings[1657];
          $Response->D .= '     </LI>';
          
          if ($DR['CCount'] > 0) $Response->D .= '<LI>'.str_replace('%a', $DR['CCount'], $Strings[1658]).'</LI>';
          
          $Response->D .= '   </UL>
                            </DIV>
                             <DIV CLASS="sech"><IMG SRC="/IF/H-Rev.png" WIDTH=20 HEIGHT=20 ALT="" CLASS="valgm padrxs"><B>'.$Strings[1659].'</B></DIV>
                             <DIV CLASS="padls"><SPAN CLASS="fll sz24 mgrrxs dkgray fra algc"><B>'.(($DR['RAvg']==0)?'--':number_format($DR['RAvg'], 1)).'</B></SPAN>'.str_replace('%a', $DR['RCount'], $Strings[1660]).'<BR />';
                             
          if ($DR['RCount'] > 0) $Response->D .= '<DIV CLASS="nbutt" onClick="GetRvw('.(int)$_POST['DID'].',0)">'.$Strings[1661].'</DIV> &bull; ';
                             
          if ($UserID > 0) {
            $Response->D .= ((int)$DR['MyScore'] == 0)? '<DIV CLASS="nbutt" onClick="AddRvw('.(int)$_POST['DID'].');">'.$Strings[1662].'</DIV>':'<SPAN>'.str_replace('%a', (int)$DR['MyScore'], $Strings[1663]).'</SPAN>';
          } else {
            $Response->D .= '<SPAN CLASS="fklnk" onClick="NewAcct();">'.$Strings[1664].'</SPAN>';
          }
          
          $Response->D .= '   <DIV CLASS="clr"></DIV>
                           </DIV>';

          if ($DR['LCount'] > 1) {
          
            $Response->D .= '<DIV CLASS="sech padts"><IMG SRC="/IF/H-Adr.png" WIDTH=20 HEIGHT=20 ALT="" CLASS="valgm padrxs"><B>'.$Strings[1666].'</B></DIV><DIV CLASS="padls"><UL>';
            
            list ($SQR, $SRS, $T) = QuerySet(
              'SELECT LD.LocationID, LD.LocationAddress AS Adr
                 FROM 2200_Store_Locations SL
                INNER JOIN 3000_Locations LD ON LD.LocationID = SL.LocationID
                WHERE SL.StoreID = '.$DR['StoreID'].';');
               
            if ($SQR > 0) {
              
              while ($SDR = mysql_fetch_array($SRS)) {
                $Response->D .= '<LI>'.$SDR['Adr'].'</LI>';
              }
            
            } elseif ($SQR < 0) {
            
              SysLogIt('Error searching for saved deal\'s store locations.', StatusError, ActionSelect);
              $Response->S = false;
              $Response->Send();
            
            }

            $Response->D .= '</UL>
                           </DIV>';
          
          } elseif (is_null($DR['URLID'])) {
          
            if ($DR['LCount'] > 0) {
          
              $Response->D .= '<DIV CLASS="sech padt"><IMG SRC="/IF/H-Eye.png" WIDTH=20 HEIGHT=20 ALT="" CLASS="valgm padrxs"><B>'.$Strings[1669].'</B></DIV>
                               <DIV ID="GSVDIV" CLASS="mrgls gsv"></DIV>';
              $Response->R[2] = $DR['Lat'];
              $Response->R[3] = $DR['Lng'];
              
            }
            
          }
          
          if (!is_null($DR['QR'])) {
          
            $Response->D .= '<DIV CLASS="sech padt"><IMG SRC="/IF/H-Pho.png" WIDTH=20 HEIGHT=20 ALT="" CLASS="valgm padrxs"><B>'.$Strings[1670].'</B></DIV>
                             <DIV CLASS="mrgl padbxs">'.$Strings['1671'].'</DIV>
                             <DIV CLASS="mrgls algc"><IMG SRC="/QR/'.$DR['QR'].'" CLASS="rbrds fra padaxs" WIDTH=150 HEIGHT=150 ALT=""></DIV>';
          
          }
          
          $Response->D .= '</DIV>';
          
          if (isset($_POST['ULID'])) {
            if (is_numeric($_POST['ULID'])) {
              if ((int)$_POST['ULID'] > 0) SetFilter((int)$_POST['ULID'], FilterDeal, (int)$_POST['DID'], 0, -1, false);
            }
          }
          
          $Response->Send();
        
        }
              
      }
    }
    
    $Response->S = false;
    $Response->Send();
  
  }
  
?>
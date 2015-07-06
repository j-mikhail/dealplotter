<?php

  define("UserActive", 1);
  define("UserReminders", 2);
  
  define("UserCanEditStrings", 32);
  define("UserCanEditTags", 64);
  define("UserCanRunUpdate", 128);
  define("UserCanEditDivisions", 256);
  define("UserCanEditSources", 512);
  define("UserCanViewLog", 1024);
  define("UserCanViewStatus", 2048);
  
  define("FilterCategory", 1);
  define("FilterType", 2);
  define("FilterDeal", 3);

  $SessionID = 0;
  $SessionCoords = array();

  $UserID = 0;
  $UserName = '';
  $UserSort = 0;
  $UserFlags = 0;

  class FDeal {
  
    public $FID = 0;
    public $Desc = '';
    public $Icon = '';
    public $Value = 0;
    public $EDate = 0;
    public $DID = 0;
    public $Web = false;
    
    public $Locs = array();
  
  }  
  
  class SLocation {

    public $Dist = 0;
    public $Lat = 0;
    public $Lng = 0;
  
  }  
  
  function ProcessSignOn() {
  /*/////////////////////////////////////////////////////////////
       Author: Plottery Corp.
      Created: v1.0.0 - 2010-12-14
    Revisions: None
      Purpose: Verifies user submitted login data
      Returns: Nothing
  *//////////////////////////////////////////////////////////////
  
    global $UserID;
    global $UserFlags;
    
    global $LanguageID;
    global $Response;
    
    $Strings = GSA('1090,1091,1092,1093,1429');
    
    list ($OK, $Msgs) = ValidateForm(array(
                          array(TypePOST, 'DPUsername', MustExist, ValidateString, null, null, 1603),
                          array(TypePOST, 'DPPassword', MustExist, ValidateString, null, null, 1603),
                          array(TypePOST, 'DPSave', CanExist, ValidateRange, 1, 1, 1603) 
                        ));
    if ($OK) {

      list ($QR, $DR, $T) = QuerySingle("SELECT U.UserID, U.UserFlags, U.UserPassSalt, U.UserPassHash, L.LanguageID
                                            FROM 1000_Users U
                                            LEFT JOIN 0000_Languages L ON U.LanguageID = L.LanguageID
                                           WHERE U.UserUsername = '".Pacify($_POST['DPUsername'])."';");      
      
      if ($QR < 0) {
      
        SysLogIt('Error looking up user login. Requested username and password were: '.$_POST['DPUsername'].', '.$_POST['DPPassword'].'.', StatusError, ActionSelect);
        
      } elseif ($QR == 0) {
      
        ReturnResponse(false, $Strings[1090].' <BR /><DIV CLASS="nbutt ebutt" onClick="ForPwd();">'.$Strings[1429].'</DIV>', 'errmsg', "Foc('DPUsername');");
        
      } else {
      
        if (md5($DR['UserPassSalt'].$_POST['DPPassword']) != $DR['UserPassHash']) return ReturnResponse(false, $Strings[1090].' <DIV CLASS="nbutt ebutt" onClick="ForPwd();">'.$Strings[1429].'</DIV>', 'errmsg', "Foc('DPUsername');");
      
        $UserID = $DR['UserID'];
        $UserFlags = $DR['UserFlags'];
        
        if (!is_null($DR['LanguageID'])) $LanguageID = $DR['LanguageID'];
        
        $Portable = 0;
        
        if (isset($_POST['DPSave'])) {
          //CHANGEBACK: if ((int)$_POST['DPSave'] == 1 && $DR['UserFlags'] < UserCanEditStrings) $Portable = 1;
          if ((int)$_POST['DPSave'] == 1) $Portable = 1;
        }
        
        if ($UserFlags & UserActive) {
        
          if (CreateSession($UserID, $LanguageID, $Portable)) {
            $Response->S = true;
            $Response->J = 'F5();';
            //$Response->J = ((stripos($_SERVER['HTTP_USER_AGENT'], 'msie') === false)?'F5();':'SF5();');
            //$Response->J = ((stripos($_SERVER['HTTP_USER_AGENT'], 'msie') === false)?"F5();":"self.location = 'http://".SiteAddress."';");
            $Response->Send();
          }
        
        } else {

          //Disabled user
          FlushSession($UserID);
          ReturnResponse(false, $Strings[1091], 'errmsg');

        }
      
      }
      
      ReturnResponse(false, $Strings[1092], 'errmsg', "Foc('DPUsername');");

    } else {
    
      $Errors = '';
      foreach ($Msgs as $Msg) {
        $Errors .= GS($Msg).', ';
      }
    
      SysLogIt('Invalid login data received. Errors returned were: '.$Errors, StatusSecurity);
      ReturnResponse(false, $Strings[1093], 'errmsg', "Foc('DPUsername');");
      
    }
  
  }
  
  function CreateSession($UserID, $LanguageID, $Portable, $Latitude = 0, $Longitude = 0, $Country = 0) {
  /*/////////////////////////////////////////////////////////////
       Author: Plottery Corp.
      Created: v1.0.0 - 2010-12-14
    Revisions: None
      Purpose: Creates a new session for a given User ID
      Returns: Nothing
  *//////////////////////////////////////////////////////////////
  
    global $SessionID;
  
    if ($SessionID > 0) {
    
      //Session already exists, update instead
      
      if ($UserID > 0) {
        //This should only happen when an unregistered user on the map page signs up
        if (ExecCommand("UPDATE 0700_Sessions SET UserID = ".$UserID.", SessionAccessDate = ".date('YmdHis')." WHERE SessionID = ".$SessionID.";")) return true;
      } else {
        if (ExecCommand("UPDATE 0700_Sessions SET SessionAccessDate = ".date('YmdHis').", Latitude = ".(double)$Latitude.", Longitude = ".(double)$Longitude.", Country = ".(int)$Country." WHERE SessionID = ".$SessionID.";")) return true;
      }
      
      return SysLogIt('Error updating session with ID of '.$SessionID.'.', StatusError, ActionUpdate);
    
    } else {
    
      //Create new session
    
      if ($UserID > 0) {
        //Delete old user sessions
        if (!ExecCommand("DELETE FROM 0700_Sessions WHERE UserID = ".$UserID.";")) return SysLogIt('Error flushing old user sessions.', StatusError, ActionDelete);
      }
        
      //Create new key
      $RandomKey = dechex(mt_rand()).dechex(mt_rand()).dechex(mt_rand()).dechex(mt_rand()).dechex(mt_rand()).dechex(mt_rand());
      
      //Determine session expiry
      $Expiry = time() + (60 * 60);
      if ($Portable == 1) $Expiry = time() + (60 * 60 * 24 * 90);

      //Create session entry
      if ($UserID == 0) {
        $SQL = "INSERT INTO 0700_Sessions (Latitude, Longitude, Country, SessionKey, SessionIP, SessionPort, SessionCreateDate, SessionAccessDate) VALUES (".$Latitude.",".$Longitude.",".$Country.",'".$RandomKey."','".$_SERVER["REMOTE_ADDR"]."',".(int)$Portable.",".date('YmdHis').",".date('YmdHis').");";
      } else {
        $SQL = "INSERT INTO 0700_Sessions (UserID, SessionKey, SessionIP, SessionPort, SessionCreateDate, SessionAccessDate) VALUES (".$UserID.",'".$RandomKey."','".$_SERVER["REMOTE_ADDR"]."',".(int)$Portable.",".date('YmdHis').",".date('YmdHis').");";
      }
      
      if (ExecCommand($SQL)) {
      
        //Set cookies
        if (setcookie('SKEY', $RandomKey, $Expiry) && setcookie('LID', $LanguageID, $Expiry)) return true;
      
      } else {
        
        //Couldn't create session entry
        if ($UserID == 0) {
          SysLogIt('Error creating session for non-registered user.', StatusError, ActionInsert);
        } else {
          SysLogIt('Error creating session for user with ID of '.(int)$UserID.'.', StatusError, ActionInsert);
        }
        
      }
      
      return false;
      
    }

  }

  function ProcessNewAccount() {
  /*/////////////////////////////////////////////////////////////
       Author: Plottery Corp.
      Created: v1.0.0 - 2010-12-14
    Revisions: None
      Purpose: Creates a new user account
      Returns: Nothing
  *////////////////////////////////////////////////////////////// 
    
    global $LanguageID;
    global $UserID;
    
    /*
    list ($OK, $Msgs) = ValidateForm(array(
                          array(TypePOST, 'UName', MustExist, ValidateLength, 4, null, 9999),
                          array(TypePOST, 'Pass1', MustExist, ValidateLength, 6, null, 9999),
                          array(TypePOST, 'Pass2', MustExist, ValidateLength, 6, null, 9999),
                          array(TypePOST, 'EMail', MustExist, NoValidation, null, null, 9999),
                          array(TypePOST, 'FName', MustExist, NoValidation, null, null, 9999),
                          array(TypePOST, 'Terms', MustExist, ValidateRange, 1, 1, 1603),
                          array(TypePOST, 'RK',    MustExist, ValidateRange, 1, 1, 1603)
                          array(TypePOST, 'RC',    MustExist, ValidateRange, 1, 1, 1603)
                        ));
    */
    
    if (isset($_POST['UName']) && isset($_POST['Pass1']) && isset($_POST['Pass2']) && isset($_POST['EMail']) && isset($_POST['FName']) && isset($_POST['Terms']) && isset($_POST['RK']) && isset($_POST['RC'])) {
    
      $Strings = GSA('1420,1421,1422,1423,1424,1425,1426,1427,1428,1429,1430,1431');
      
      //Validate form data
      
      if (strlen(trim($_POST['UName'])) < 4) ReturnResponse(false, $Strings[1420], 'errmsg', "NewCap(); Foc('UName');", 'NewLoginMsg');
      if (strlen(trim($_POST['Pass1'])) < 6) ReturnResponse(false, $Strings[1421], 'errmsg', "NewCap(); Foc('Pass1');", 'NewLoginMsg');
      if (trim($_POST['Pass1']) != trim($_POST['Pass2'])) ReturnResponse(false, $Strings[1422], 'errmsg', "NewCap(); Foc('Pass1');", 'NewLoginMsg');

      if (trim($_POST['EMail']) != '' && (stripos(trim($_POST['EMail']), '@') < 1 || stripos(trim($_POST['EMail']), '.') < 3 || stripos(trim($_POST['EMail']), '.') < stripos(trim($_POST['EMail']), '@')))
        ReturnResponse(false, $Strings[1423], 'errmsg', "NewCap(); Foc('EMail');", 'NewOptsMsg');
      
      if ($_POST['Terms'] != 'OK') ReturnResponse(false, $Strings[1424], 'errmsg', "NewCap(); Foc('Terms');", 'NewAgrMsg');
      
      if (trim($_POST['RC']) == '' || trim($_POST['RK']) == '' || stripos(trim($_POST['RC']), ' ') < 1) ReturnResponse(false, $Strings[1425], 'errmsg', "NewCap(); Foc('recaptcha_response_field');", 'CaptchaMsg');
      
      //Check if username is taken
      
      list ($QR, $DR, $T) = QuerySingle("SELECT U.UserID FROM 1000_Users U WHERE U.UserUsername = '".Pacify(trim($_POST['UName']))."';");
      
      if ($QR < 0) {
        SysLogIt('Error looking up user. Requested username was: '.trim($_POST['UName']).'.', StatusError, ActionSelect);
      } elseif ($QR == 0) {
      
        //Contact ReCaptcha server
        
        $Parameters = Array();
        $Parameters['privatekey'] = '6Lfwmr8SAAAAAC8TzhWnOBLlew8UsR4zo7lToBK6';
        $Parameters['remoteip'] = $_SERVER["REMOTE_ADDR"];
        $Parameters['challenge'] = trim($_POST['RK']);
        $Parameters['response'] = trim($_POST['RC']);
        
        list ($Data, $DURL) = GetWebData('http://www.google.com/recaptcha/api/verify', $Parameters);
        SysLogIt('Response from ReCaptcha server was: '.$Data, StatusInfo);
        
        if (!(substr($Data, 0, 4) == 'true' || substr($Data, 0, 5) == 'false')) ReturnResponse(false, $Strings[1426].$Data, 'errmsg', "NewCap();", 'CaptchaMsg');
        
        $Data = explode("\n", $Data);
        if ($Data[0] == 'false') ReturnResponse(false, $Strings[1427], 'errmsg', "NewCap('".$Data[1]."');", 'CaptchaMsg');
        
        //Create user
        
        $UserName = "'".Pacify(trim($_POST['FName']))."'";
        if (trim($_POST['FName']) == '') $UserName = 'NULL';
        
        $UserEMail = "'".Pacify(trim($_POST['EMail']))."'";
        if (trim($_POST['EMail']) == '') $UserEMail = 'NULL';

        $PassSalt = dechex(mt_rand()).dechex(mt_rand()).dechex(mt_rand()).dechex(mt_rand()).dechex(mt_rand()).dechex(mt_rand()).dechex(mt_rand()).dechex(mt_rand()).dechex(mt_rand());
        
        if (ExecCommand("INSERT INTO 1000_Users (LanguageID, UserName, UserUsername, UserPassSalt, UserPassHash, UserEmail, UserFlags, UserCreateDate)
                          VALUES (".$LanguageID.",".$UserName.",'".Pacify(trim($_POST['UName']))."','".$PassSalt."','".md5($PassSalt.trim($_POST['Pass1']))."',".$UserEMail.",".UserActive.",".date('YmdHis').");")) {

          list ($QR, $DR, $T) = QuerySingle("SELECT last_insert_id() AS ID;");
          if ($QR > 0) {
          
            $UserID = $DR['ID'];
            if ($UserEMail != 'NULL') QueueVerification($UserID, Pacify(trim($_POST['EMail'])));
            if (CreateSession($UserID, $LanguageID, 1)) ReturnResponse(true, null, null, 'F5();');
          
          } else {
          
            SysLogIt('Error retrieving newly inserted user ID.', StatusError, ActionSelect);
            
          }

        } else {
        
          SysLogIt('Error creating user.', StatusError, ActionInsert);
        
        }
        
      } else {
      
        ReturnResponse(false, $Strings[1428].' <BR /><DIV CLASS="nbutt ebutt" onClick="ForPwd();">'.$Strings[1429].'</DIV>', 'errmsg', "Foc('UName');", 'NewLoginMsg');
      
      }

    } else {
    
      ReturnResponse(false, $Strings[1430], 'errmsg', "NewCap(); Foc('UName');", 'NewLoginMsg');
      
    }
    
    ReturnResponse(false, $Strings[1431], 'errmsg', "NewCap(); Foc('UName');", 'NewLoginMsg');
  
  }
  
  function ProcessAccount() {
  /*/////////////////////////////////////////////////////////////
       Author: Plottery Corp.
      Created: v1.0.0 - 2011-08-07
    Revisions: None
      Purpose: Saves changes to an existing user account
      Returns: Nothing
  *////////////////////////////////////////////////////////////// 
    
    global $LanguageID;
    global $UserID;
    
    if (isset($_POST['Pass0']) && isset($_POST['Pass1']) && isset($_POST['Pass2']) && isset($_POST['EMail']) && isset($_POST['FName'])) {
    
      $Strings = GSA('1421,1422,1423,1431,1769,1770');
      $Msg = $Strings[1769];
      
      //Validate form data
      
      if (($_POST['Pass0'] != '') || ($_POST['Pass1'] != '') || ($_POST['Pass2'] != '')) {
      
        if (strlen(trim($_POST['Pass1'])) < 6) ReturnResponse(false, $Strings[1421], 'errmsg', "Foc('Pass1');", 'AccMsg');
        if (trim($_POST['Pass1']) != trim($_POST['Pass2'])) ReturnResponse(false, $Strings[1422], 'errmsg', "Foc('Pass1');", 'AccMsg');
        
        //Check current password
        
        list ($QR, $DR, $T) = QuerySingle("SELECT U.UserPassSalt, U.UserPassHash
                                              FROM 1000_Users U
                                             WHERE U.UserID = ".$UserID.";");      

        if ($QR < 1) {
        
          SysLogIt('Error looking up salt and hash for user with ID of '.$UserID.'.', StatusError, ActionSelect);
          ReturnResponse(false, $Strings[1431], 'errmsg', "Foc('FName');", 'AccMsg');
          
        }

        if (md5($DR['UserPassSalt'].Pacify($_POST['Pass0'])) != $DR['UserPassHash']) return ReturnResponse(false, $Strings[1090], 'errmsg', "Foc('Pass0');");
          
      }
      
      if (trim($_POST['EMail']) != '' && (stripos(trim($_POST['EMail']), '@') < 1 || stripos(trim($_POST['EMail']), '.') < 3 || strripos(trim($_POST['EMail']), '.') < strripos(trim($_POST['EMail']), '@')))
        ReturnResponse(false, $Strings[1423], 'errmsg', "Foc('EMail');", 'AccEMlMsg');
      
      
      //Process name change
      
      if (trim($_POST['FName']) != '') {
      
        if (!ExecCommand("UPDATE 1000_Users SET UserName = '".Pacify(trim($_POST['FName']))."' WHERE UserID = ".$UserID.";")) 
          ReturnResponse(false, $Strings[1431], 'errmsg', "Foc('FName');", 'AccMsg');

      }
      
      //Process password change
      
      if (($_POST['Pass0'] != '') || ($_POST['Pass1'] != '') || ($_POST['Pass2'] != '')) {

        if (!ExecCommand("UPDATE 1000_Users SET UserPassHash = '".md5($DR['UserPassSalt'].trim($_POST['Pass1']))."' WHERE UserID = ".$UserID.";")) 
          ReturnResponse(false, $Strings[1431], 'errmsg', "Foc('Pass1');", 'AccMsg');

      }
      
      //Process email change
        
      if (trim($_POST['EMail']) != '') {
      
        if (!ExecCommand("UPDATE 1000_Users SET UserEmail = '".Pacify(trim($_POST['EMail']))."', UserEmailVerified = 0 WHERE UserID = ".$UserID.";")) 
          ReturnResponse(false, $Strings[1431], 'errmsg', "Foc('EMail');", 'AccEMlMsg');
          
        QueueVerification($UserID, Pacify(trim($_POST['EMail'])));
        $Msg .= ' '.$Strings[1770];

      }
        
      ReturnResponse(true, null, null, "PopC('".Pacify(Pacify($Msg), true)."');");
      
    } else {
    
      ReturnResponse(false, null, null, 'PopErr();');
      
    }
  
  }  
  
  function SendBadResponse() {
  
    exit();
  
  }
  
  function ProcessNewMessage() {
  /*/////////////////////////////////////////////////////////////
       Author: Plottery Corp.
      Created: v1.0.0 - 2010-12-14
    Revisions: None
      Purpose: Sends a new message
      Returns: Nothing
  *////////////////////////////////////////////////////////////// 
    
    global $LanguageID;
    global $UserID;
    
    $Strings = GSA('1604,1614');
    
    if (isset($_POST['MsgF']) && isset($_POST['MsgS']) && isset($_POST['MsgM']) && isset($_POST['MsgE'])) {
    
      if (strlen(trim($_POST['MsgM'])) < 0) ReturnResponse(false, Pacify($Strings[1604]), 'errmsg nomrgb mrgts din', "Foc('MsgM');", 'CtcMsg');
      
      if (is_numeric($_POST['MsgF']) && is_numeric($_POST['MsgS'])) {
      
        $From = $_POST['MsgE'];
        if ($UserID > 0) $From .= ' (User '.$UserID.')';
      
        if ( SaveMessage(MessageUser, $From,  GS((int)$_POST['MsgF']).':'.GS((int)$_POST['MsgS']), $_POST['MsgM']) ) {
          
          SendMail('User '.$UserID.PHP_EOL.PHP_EOL.Fix($_POST['MsgM']), Fix(GS((int)$_POST['MsgF']).':'.GS((int)$_POST['MsgS'])), 'jonathan@dealplotter.com', Fix($_POST['MsgE']));
          ReturnResponse(true, '', '', 'PopC(\''.Pacify(Pacify($Strings[1614]), true).'\');');
          
        }
        
      }
      
    }
    
    ReturnResponse(false, '', '', "PopErr();");
    
  }

  function ProcessNewReview() {
  /*/////////////////////////////////////////////////////////////
       Author: Plottery Corp.
      Created: v1.0.0 - 2010-12-14
    Revisions: None
      Purpose: Saves a review
      Returns: Nothing
  *////////////////////////////////////////////////////////////// 
    
    global $Response;
    global $LanguageID;
    global $UserID;
    
    $Response->J = 'PopErr();';
    
    $Strings = GSA('1452, 1458, 1459');
    
    if (isset($_POST['Rvw']) && isset($_POST['Scr']) && isset($_POST['DID'])) {
    
      list ($QR, $DR, $T) =
        QuerySingle(
          "SELECT D.DealID, S.StoreName, COALESCE(UR.ReviewID, 0) AS RID, D.StoreID
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
      
          if (strlen(trim($_POST['Rvw'])) < 0 || !is_numeric($_POST['Scr'])) {
          
            $Response->R = Pacify($Strings[1452]);
            $Response->C = 'errmsg nomrgb mrgtxs din';
            $Response->J = "Foc('Rvw');";
            $Response->D = 'RvwMsg';
          
          } else {
          
            if ((int)$_POST['Scr'] >= 1 && (int)$_POST['Scr'] <= 10) {
            
              if (ExecCommand("INSERT INTO 1300_User_Reviews (UserID, StoreID, LanguageID, Score, Comments, Status, ReviewDate)
                               VALUES (".$UserID.",".$DR['StoreID'].",".$LanguageID.",".(int)$_POST['Scr'].",'".Pacify(trim($_POST['Rvw']))."',0,".date('YmdHis').");")) {
               
                $Response->S = true;
                $Response->J = 'PopC(\''.Pacify(Pacify($Strings[1459]), true).'\'); RstDet('.(int)$_POST['DID'].');';
                
              } else {
              
                SysLogIt('Error inserting new review.', StatusError, ActionInsert);
              
              }
              
            }
            
          }
          
        }
        
      }
      
    }
    
    $Response->Send();
    
  }
  
  function ProcessNotifications() {
  
    global $Response;
    global $UserID;

    $Response->J = 'PopErr();';
    
    $Response->R = $_POST;
    
    if (isset($_POST['R']) && isset($_POST['V']) && isset($_POST['1']) && isset($_POST['2']) && isset($_POST['3']) && isset($_POST['4']) && isset($_POST['5'])) {
      if (is_numeric($_POST['R']) && is_numeric($_POST['V']) && is_numeric($_POST['1']) && is_numeric($_POST['2']) && is_numeric($_POST['3']) && is_numeric($_POST['4']) && is_numeric($_POST['5'])) {
      
        $Operator = ((int)$_POST['R'] == 0)?'& ~':'| ';
  
        if (!ExecCommand("UPDATE 1000_Users SET UserFlags = (UserFlags ".$Operator.UserReminders.") WHERE UserID = ".$UserID.";"))
          SysLogIt('Error updating reminder settings for user with ID of '.$UserID.'.', StatusError, ActionSelect);
        
        $Strings = GSA('1704');
        
        $TimeOfDay = 0;
        $Format = 0;
        
        if ((int)$_POST['1'] == 1) {
          $TimeOfDay = 1;
        } elseif ((int)$_POST['2'] == 1) {
          $TimeOfDay = 2;
        } elseif ((int)$_POST['3'] == 1) {
          $TimeOfDay = 4;
        }
        
        if ((int)$_POST['4'] == 1) {
          $Format = 8;
        } elseif ((int)$_POST['5'] == 1) {
          $Format = 16;
        }
        
        list ($QR, $DR, $T) =
          QuerySingle(
            "SELECT UN.NotificationID
               FROM 1400_User_Notifications UN
              INNER JOIN 1000_Users U ON UN.UserID = U.UserID
              WHERE UN.UserID = ".$UserID.";");
              
        if ($QR < 0) {
        
          SysLogIt('Error searching for user notification ID.', StatusError, ActionSelect);
          
        } else {
        
          $Value = 0;
          if (((int)$_POST['V'] == 1) && ($TimeOfDay > 0) && ($Format > 0)) $Value = ($TimeOfDay | $Format);
        
          if ($QR == 0) {
          
            if ((int)$_POST['V'] == 0) {
            
              $Response->S = true;
              $Response->J = 'PopC(\''.Pacify(Pacify($Strings[1704]), true).'\');';
            
            } elseif ($Value > 0) {
            
              $RandomKey = dechex(mt_rand()).dechex(mt_rand()).dechex(mt_rand());
            
              if (ExecCommand("INSERT INTO 1400_User_Notifications (UserID, DealID, Settings, CancelKey, CreateDate, SentDate)
                               VALUES (".$UserID.",0,".$Value.",'".$RandomKey."',".date('YmdHis').",0);")) {
                $Response->S = true;
                $Response->J = 'PopC(\''.Pacify(Pacify($Strings[1704]), true).'\');';
              } else {
                SysLogIt('Error creating notifications for user with ID of '.$UserID.'.', StatusError, ActionUpdate);
              }
            
            }
            
          } else {
          
            if (ExecCommand("UPDATE 1400_User_Notifications SET Settings = ".$Value." WHERE UserID = ".$UserID.";")) {
              $Response->S = true;
              $Response->J = 'PopC(\''.Pacify(Pacify($Strings[1704]), true).'\');';
            } else {
              SysLogIt('Error disabling notifications for user with ID of '.$UserID.'.', StatusError, ActionUpdate);
            }
          
          }
          
        }
        
      }
    }
    
    $Response->Send();
  
  }
  
  function ProcessNewFavorite() {
  
    global $Response;
    global $UserID;
    
    $Response->J = 'PopErr();';
    
    if (isset($_POST['ULID']) && isset($_POST['N']) && isset($_POST['V']) && isset($_POST['C']) && isset($_POST['Y']) && isset($_POST['M']) && isset($_POST['D'])) {
      if (is_numeric($_POST['ULID']) && is_numeric($_POST['V']) && is_numeric($_POST['Y']) && is_numeric($_POST['M']) && is_numeric($_POST['D'])) {
    
        if (trim($_POST['N']) == '') {
          $Response->J = "ClRep(getI('FavMsg'), 'dno' 'din'); Foc('MName');";
        } else {
        
          list ($QR, $DR, $T) = QuerySingle("SELECT UL.UserLocationID
                                                FROM 1100_User_Locations UL
                                               WHERE UL.UserID = ".$UserID."
                                                 AND UL.LocationID = ".(int)$_POST['ULID'].";");
          
          if ($QR < 1) {
          
            SysLogIt('Error searching for new favorite user location.', StatusError, ActionSelect);
          
          } else {
          
            $Lat = 0;
            $Lng = 0;
            $Ctry = 0;
            
            if (ValidCoords($_POST['C'], $Ctry, $Lat, $Lng)) {
        
              $Expiry = 0;
              if ( ( ((int)$_POST['Y'] >= date('Y')) && ((int)$_POST['Y'] <= (date('Y')+5)) ) &&
                   ( ((int)$_POST['M'] >= 1) && ((int)$_POST['M'] <= 12) ) &&
                   ( ((int)$_POST['D'] >= 1) && ((int)$_POST['D'] <= 31) ) ) $Expiry = mktime(0, 0, 0, (int)$_POST['M'], (int)$_POST['D']+1, (int)$_POST['Y']);
                   
              if (ExecCommand("INSERT INTO 1210_User_Manual_Favorites (UserID, LocationID, Description, DValue, Longitude, Latitude, Expiry)
                               VALUES (".$UserID.",".(int)$_POST['ULID'].",'".Pacify(trim($_POST['N']))."',".(int)$_POST['V'].",".$Lng.",".$Lat.",".(($Expiry==0)?0:date('YmdHis', $Expiry)).");")) {
                               
                list ($QR, $SDR, $T) =
                  QuerySingle(
                    'SELECT LAST_INSERT_ID() AS ID;');
                    
                if ($QR > 0) {
                
                  $ASDeal = new FDeal;
                  $ASDeal->FID = -$SDR['ID'];
                  $ASDeal->EDate = $Expiry;
                  $ASDeal->Desc = StringAdjust(trim($_POST['N']));
                  $ASDeal->Icon = "Fav";
                  $ASDeal->Value = (int)$_POST['V'];
                  $ASDeal->DID = 0;
                  
                  $ASLocation = new SLocation;
                  $ASLocation->Lat = $Lat;
                  $ASLocation->Lng = $Lng;
                  $ASLocation->Dist = 0;

                  $ASDeal->Locs[0] = $ASLocation;                  
                
                  $Response->C = $ASDeal;
                  $Response->S = true;
                  
                } else {
                
                  SysLogIt('Error selecting new manual favorite ID.', StatusError, ActionSelect);
                
                }
                
              } else {
              
                SysLogIt('Error inserting manual favorite.', StatusError, ActionInsert);
              
              }
              
            } else {
            
              SysLogIt('Invalid location specified for new favorite.', StatusError);
            
            }
               
          }
               
        }
    
      }
    }
    
    $Response->Send();
  
  }
  
  function ProcessSaveDeal() {
  /*/////////////////////////////////////////////////////////////
       Author: Plottery Corp.
      Created: v1.0.0 - 2011-02-13
    Revisions: None
      Purpose: Toggles the save state of a deal
      Returns: Nothing
  *////////////////////////////////////////////////////////////// 
    
    global $Response;
    global $UserID;
    
    $Response->J = 'PopErr();';
    
    $Strings = GSA('1658');
    
    if (isset($_POST['O']) && isset($_POST['DID']) && isset($_POST['ULID']) && isset($_POST['DT'])) {
      if (is_numeric($_POST['O']) && is_numeric($_POST['DID']) && is_numeric($_POST['ULID']) && is_numeric($_POST['DT'])) {
      
        if ((int)$_POST['DID'] >= 0) {
        
          list ($QR, $DR, $T) =
            QuerySingle(
              "SELECT UF.FavoriteID, UF.DealID, L.LocationLatitude AS Lat, L.LocationLongitude AS Lng
                 FROM 3000_Locations L 
                 LEFT JOIN 1200_User_Favorites UF ON UF.LocationID = L.LocationID AND UF.DealID = ".(int)$_POST['DID']." AND UF.UserID = ".$UserID."
                WHERE L.LocationID = ".(int)$_POST['ULID'].";");
                
        } else {
        
          list ($QR, $DR, $T) =
            QuerySingle(
              "SELECT UMF.FavoriteID, 0 AS DealID
                 FROM 1210_User_Manual_Favorites UMF
                WHERE UMF.FavoriteID = ".(-((int)$_POST['DID']))."
                  AND UMF.LocationID = ".(int)$_POST['ULID']."
                  AND UMF.UserID = ".$UserID.";");
        
        }
                  
        if ($QR < 0) {
        
          SysLogIt('Error searching for user favorite.', StatusError, ActionSelect);
          
        } elseif (!is_null($DR['FavoriteID']) && (int)$_POST['O'] == 0) {
    
          $Table = ((int)$_POST['DID'] < 0)?"1210_User_Manual_Favorites":"1200_User_Favorites";
          
          if (ExecCommand("DELETE FROM ".$Table." WHERE FavoriteID = ".$DR['FavoriteID'].";")) {
          
            $Response->S = true;
            $Response->R = 0;
            $Response->C = $DR['DealID'];
            $Response->D = ((int)$_POST['DID'] < 0)?-$DR['FavoriteID']:$DR['FavoriteID'];
            $Response->J = '';
            if (((int)$_POST['DT'] == 1) && ((int)$_POST['DID'] > 0)) $Response->J = 'RstDet('.(int)$_POST['DID'].');';
            
          } else {
          
            SysLogIt('Error deleting user favorite.', StatusError, ActionInsert);
          
          }
          
        } elseif (is_null($DR['FavoriteID']) && (int)$_POST['O'] == 1) {
        
          if (ExecCommand("INSERT INTO 1200_User_Favorites (DealID, LocationID, UserID) VALUES (".(int)$_POST['DID'].",".(int)$_POST['ULID'].",".$UserID.");")) {
          
            list ($QR, $SDR, $T) =
              QuerySingle(
                'SELECT LAST_INSERT_ID() AS ID;');
                
            if ($QR > 0) {
            
              $ASDeal = new FDeal;
              $ASDeal->FID = $SDR['ID'];
          
              list ($QR, $SDR, $T) =
                QuerySingle(
                  'SELECT UNIX_TIMESTAMP(D.DateExpiry) AS ExpDate, S.StoreID, S.StoreName AS SName, COALESCE(ST.Icon, SC.Icon, "Blank") AS Icon, D.DealValue AS DValue
                     FROM 4000_Deals D
                    INNER JOIN 2000_Stores S ON D.StoreID = S.StoreID
                     LEFT JOIN 2110_Store_Types ST ON S.TypeID = ST.TypeID
                     LEFT JOIN 2100_Store_Categories SC ON ST.CategoryID = SC.CategoryID
                    WHERE D.DealID = '.(int)$_POST['DID'].';');
                    
              if ($QR > 0) {
              
                //$Response->J .= $DR['Lat'].', '.$DR['Lng'];
                
                $ASDeal->EDate = ((($SDR['ExpDate'] == 0) || (date('Y', $SDR['ExpDate']) == 1969))? 0 : (int)$SDR['ExpDate']);
                $ASDeal->Desc = StringAdjust($SDR['SName']);
                $ASDeal->Icon = $SDR['Icon'];
                $ASDeal->Value = (double)$SDR['DValue'];
                $ASDeal->DID = (int)$_POST['DID'];
                
                list ($SSQR, $SSRS, $T) =
                  QuerySet(
                    'SELECT LD.LocationID, LD.LocationLatitude AS Lat, LD.LocationLongitude AS Lng, GetDistance('.$DR['Lat'].', '.$DR['Lng'].', LD.LocationLatitude, LD.LocationLongitude) AS Distance
                       FROM 2200_Store_Locations SL
                      INNER JOIN 3000_Locations LD ON LD.LocationID = SL.LocationID
                      WHERE SL.StoreID = '.$SDR['StoreID'].'
                     HAVING Distance <= 100
                         OR Lat = -1;');
                     
                if ($SSQR > 0) {
                
                  while ($SSDR = mysql_fetch_array($SSRS)) {
                  
                    if ($SSDR['Lat'] == -1) {
                      $ASDeal->Web = true;
                      break;
                    }

                    $ASLocation = new SLocation;
                    $ASLocation->Lat = (double)$SSDR['Lat'];
                    $ASLocation->Lng = (double)$SSDR['Lng'];
                    $ASLocation->Dist = (double)$SSDR['Distance'];

                    $ASDeal->Locs[$SSDR['LocationID']] = $ASLocation;

                  }
                  
                  $Response->S = true;
                  $Response->R = 1;
                  $Response->C = $ASDeal;
                  $Response->J = '';
                  if ((int)$_POST['DT'] == 1) $Response->J = 'RstDet('.(int)$_POST['DID'].');';

                } elseif ($SSQR < 0) {

                  SysLogIt('Error searching for deal\'s store locations.', StatusError, ActionSelect);

                }
                
              } else {
              
                SysLogIt('Error searching for favorite deal information.', StatusError, ActionSelect);
              
              }
              
            } else {
            
              SysLogIt('Error selecting new favorite ID.', StatusError, ActionSelect);
            
            }
              
          } else {
          
            SysLogIt('Error inserting new user favorite.', StatusError, ActionInsert);
          
          }

        }

        SetFilter((int)$_POST['ULID'], FilterDeal, (int)$_POST['DID'], 0, -1, false);
        
      }
    }
    
    $Response->Send();
    
  }  
  
  function ReturnResponse($Success, $Data, $CName, $AfterJS = '', $DIV = '') {
  
    global $Response;
    
    $Response->S = $Success;
    $Response->R = $Data;
    $Response->C = $CName;
    if ($DIV != '') $Response->D = $DIV;
    if ($AfterJS != '') $Response->J = $AfterJS;

    $Response->Send();
  
  }
  
  function ProcessLocation() {
  
    global $UserID;
    global $SessionID;
    global $LanguageID;
    global $Response;
  
    if (isset($_POST['Coords'])) {
    
      $Lat = 0;
      $Lng = 0;
      $Ctry = 0;
      
      if (ValidCoords($_POST['Coords'], $Ctry, $Lat, $Lng)) {
      
        if ($UserID > 0 && $SessionID > 0) {
        
          list ($QR, $DR, $T) = QuerySingle("SELECT COALESCE(COUNT(UserLocationID),0) AS Locs FROM 1100_User_Locations WHERE UserID = ".$UserID.";");
          if ($QR < 0) {
            SysLogIt('Error counting user locations for user with ID of '.$UserID.'.', StatusError, ActionSelect);
          } elseif ($QR == 0 || $DR['Locs'] < 5) {
          
            if (ExecCommand("UPDATE 0700_Sessions SET Latitude = ".$Lat.", Longitude = ".$Lng.", Country = ".$Ctry." WHERE SessionID = ".$SessionID.";")) {
              $Response->S = true;
              $Response->J = 'NamLoc();';
              $Response->Send();
            } else {
              SysLogIt('Error updating session with new location coordinates.', StatusError, ActionUpdate);
            }
          
          }
        
        } elseif (CreateSession(0, $LanguageID, 1, $Lat, $Lng, $Ctry)) {
        
          $Response->S = true;
          $Response->J = 'F5();';
          //$Response->J = ((stripos($_SERVER['HTTP_USER_AGENT'], 'msie') === false)?"F5();":"top.location = 'http://".SiteAddress."';");
          $Response->Send();
          
        }
        
      }
      
    }
    
    ReturnResponse(false, 'An error occured. Please try again later.', 'errmsg', "Foc('InLoc');");
  
  }
  
  function DeleteLocation() {
  
    global $UserID;
    global $Response;
    
    if (isset($_POST['LID'])) {
      if (is_numeric($_POST['LID'])) {
      
        list ($QR, $DR, $T) = QuerySingle("SELECT UserLocationID AS ULID FROM 1100_User_Locations WHERE LocationID = ".(int)$_POST['LID']." AND UserID = ".$UserID.";");
        if ($QR < 0) SysLogIt('Error finding user location with ID of '.(int)$_POST['LID'].'.', StatusError, ActionSelect);
          
        if ($QR > 0) {
          
          if (!(ExecCommand("DELETE FROM 1110_User_Filters WHERE UserLocationID = ".$DR['ULID'].";")))
            SysLogIt('Error deleting user filters for user location with ID of '.$DR['ULID'].'.', StatusError, ActionDelete);

          if (!(ExecCommand("DELETE FROM 3000_Locations WHERE LocationID = ".(int)$_POST['LID'].";")))
            SysLogIt('Error deleting location with ID of '.(int)$_POST['LID'].'.', StatusError, ActionDelete);
          
          if (ExecCommand("DELETE FROM 1100_User_Locations WHERE UserLocationID = ".$DR['ULID'].";")) {
          
            SysLogIt('Successfully deleted user location with ID of '.$DR['ULID'].'.', StatusInfo, ActionDelete);
            
            $Response->S = true;
            $Response->J = 'ShoLoc();';
          
          } else {
          
            SysLogIt('Error deleting user filters for user location with ID of '.$DR['ULID'].'.', StatusError, ActionDelete);
            
          }
            
        }
        
      }
    }
    
    $Response->Send();
  
  }
  
  function ProcessNameLocation() {
  
    global $UserID;
    global $SessionID;
    global $SessionCoords;
    
    global $Response;
  
    if (!($UserID == 0 || $SessionID = 0 || count($SessionCoords) == 0)) {
    
      list ($OK, $Msgs) = ValidateForm(array(array(TypePOST, 'LName', MustExist, ValidateString, null, null, 0)));
      
      if ($OK) {
      
        if (ExecCommand("INSERT INTO 3000_Locations (LocationLatitude, LocationLongitude, CountryID) VALUES (".$SessionCoords[0].",".$SessionCoords[1].",".(int)$SessionCoords[2].");")) {
        
          list ($QR, $DR, $T) = QuerySingle("SELECT last_insert_id() AS ID;");
          if ($QR > 0) {
          
            $LocationID = $DR['ID'];
            if (ExecCommand("INSERT INTO 1100_User_Locations (UserID, LocationID, UserLocationName) VALUES (".$UserID.",".$LocationID.",'".Pacify(substr(trim($_POST['LName']),0,20), true)."');")) {
            
              $Response->S = true;
              $Response->J = 'ClWin(); SetCLoc('.$LocationID.'); GetData(); PopC(\''.Pacify(Pacify('"'.substr(trim($_POST['LName']),0,20).'" '.GS(1365)), true).'\');';
              $Response->Send();
              
              ExecCommand("UPDATE 0700_Sessions SET Latitude = NULL, Longitude = NULL, Country = 0 WHERE SessionID = ".$SessionID.";");
              
            } else {
            
              SysLogIt('Error creating user location for location with ID of '.$LocationID.'.', StatusError, ActionInsert);
            
            }
            
          } else {
          
            SysLogIt('Error retrieving ID of newly created location.', StatusError, ActionSelect);
          
          }
          
        } else {
        
          SysLogIt('Error creating location.', StatusError, ActionInsert);
        
        }
      
      }
      
    }
    
    $Response->Send();
  
  }
  
  function ProcessResetPass() {
  
    global $Response;
    
    $Response->J = 'PopErr();';
    
    $Strings = GSA('1083,1084,1085,1086');
  
    if (isset($_POST['Name'])) {
      if (trim($_POST['Name']) != '') {
      
        $Query = "SELECT U.UserID, U.UserEmail, U.UserEmailVerified AS UEV, U.LanguageID AS LID FROM 1000_Users U WHERE (U.UserUsername LIKE '%".Pacify($_POST['Name'])."%'";
        if (stripos('@', $_POST['Name']) !== false) $Query .= " OR U.UserEmail LIKE '%".Pacify($_POST['Name'])."%'";
        $Query .= ") AND (UserFlags & ".UserActive.") = ".UserActive.";";
        
        list ($QR, $DR, $T) = QuerySingle($Query);
        if ($QR < 0) {
          
          SysLogIt('Error looking up data for password reset. Query was: '.Pacify($_POST['Name']).'.', StatusError, ActionSelect);
        
        } else {
        
          if ($QR == 0) ReturnResponse(false, Pacify($Strings[1084]).'<BR /><DIV CLASS="nbutt" onClick="NewAcct()">'.Pacify($Strings[1085]).'</DIV>', 'errmsg din', "Foc('RPEMail');", 'RstPwdMsg');
          if (is_null($DR['UserEmail']) || $DR['UEV'] != 1) ReturnResponse(false, Pacify($Strings[1083]).'<BR /><DIV CLASS="nbutt" onClick="NewAcct()">'.Pacify($Strings[1085]).'</DIV>', 'errmsg din', "Foc('RPEMail');", 'RstPwdMsg');
          
          if (QueueReset($DR['UserID'], Pacify(trim($DR['UserEmail'])))) {
            $Response->S = true;
            $Response->J = "PopC('".Pacify(Pacify($Strings[1086]), true)."');";;
          }

        }
      
      }
    }
    
    $Response->Send();
  
  }  
  
  function GetData() {
  
    global $UserID;
    global $UserSort;
    global $Response;
    global $SessionID;
    global $LanguageID;
    global $SessionCoords;
    
    $Locations = array();

    class ULocation {

      public $Lat = 0;
      public $Lng = 0;
      public $CurS = '';
      public $Desc = '';
      
      public $Deals = array();
      public $Saves = array();
      public $DFilt = array();
      public $TFilt = array();
      public $CFilt = array();
    
    }
    
    class Deal {
    
      public $EDate = 0;
      public $Desc = '';
      public $TID = 0;
      public $CID = 0;
      public $Icon = '';
      public $RPrice = 0;
      public $SPrice = 0;
      public $Web = false;
      
      public $Locs = array();
    
    }
        
    $Cats = array();
    
    class Cat {

      public $ID = 0;
      public $Name = '';
      public $Order = 0;
      public $Types = array();

     }
     
    class Type {
    
      public $ID = 0;
      public $Name = '';
      public $Order = 0;
    
    }
    
    //Get categories and types
    //------------------------------------
    
    list ($QR, $RS, $T) = QuerySet('SELECT SC.CategoryID, COALESCE(LSSC.StringText,"?") AS CName, ST.TypeID, COALESCE(LSST.StringText,"?") AS TName
                                      FROM 2110_Store_Types ST
                                      LEFT JOIN 0200_Language_Strings LSST ON LSST.StringID = ST.StringID AND LSST.LanguageID = '.$LanguageID.'
                                     INNER JOIN 2100_Store_Categories SC ON ST.CategoryID = SC.CategoryID
                                      LEFT JOIN 0200_Language_Strings LSSC ON LSSC.StringID = SC.StringID AND LSSC.LanguageID = '.$LanguageID.'
                                    ORDER BY CName ASC, TName ASC;');
    
    if ($QR < 0) $Response->Send();
    
    $LastID = 0;
    $Order = 0;
    
    if ($QR > 0) {
    
      while ($DR = mysql_fetch_array($RS)) {
      
        if ($LastID != $DR['CategoryID']) {
        
          $Order = (floor($Order/100)*100)+100;
        
          $ACat = new Cat();
          $ACat->ID = (int)$DR['CategoryID'];
          $ACat->Name = $DR['CName'];
          $ACat->Order = (int)$Order;
          
          $Cats[] = $ACat;
          $LastID = $DR['CategoryID'];
          
        }
        
        $Order++;
        
        $AType = new Type();
        $AType->ID = (int)$DR['TypeID'];
        $AType->Name = $DR['TName'];
        $AType->Order = (int)$Order;
        
        $ACat->Types[] = $AType;
      
      }
      
    }
    

    //Get user locations
    //------------------------------------
    
    $Sort = array();
    
    if ($UserID > 0) {

      list ($QR, $RS, $T) = QuerySet(
        'SELECT L.LocationID, L.LocationLatitude AS Lat, L.LocationLongitude AS Lng, L.CountryID AS CID, UL.UserLocationName AS UName, C.CountryCurrency AS CurS,
          GROUP_CONCAT(UFT.FilterSourceID) AS TFilt, GROUP_CONCAT(CONCAT_WS(",", UFC.FilterSourceID, UFC.FilterValue) SEPARATOR "|") AS CFilt, GROUP_CONCAT(UFD.Filters SEPARATOR "|") AS DFilt
           FROM 0700_Sessions S
          INNER JOIN 1000_Users U ON S.UserID = U.UserID
          INNER JOIN 1100_User_Locations UL ON U.UserID = UL.UserID
          INNER JOIN 3000_Locations L ON L.LocationID = UL.LocationID
          INNER JOIN 3100_Countries C ON C.CountryID = L.CountryID
           LEFT JOIN 1110_User_Filters UFC ON UFC.UserLocationID = UL.UserLocationID AND UFC.FilterType = '.FilterCategory.'
           LEFT JOIN 1110_User_Filters UFT ON UFT.UserLocationID = UL.UserLocationID AND UFT.FilterType = '.FilterType.'
           LEFT JOIN (
             SELECT UF.UserLocationID, CONCAT_WS(",", UF.FilterSourceID, UF.FilterValue) AS Filters
               FROM 1110_User_Filters UF
              INNER JOIN 4000_Deals D ON UF.FilterSourceID = D.DealID
              WHERE UF.FilterType = '.FilterDeal.'
                AND (D.DateEnds > '.date('YmdHis').' OR UF.FilterValue = 1)
           ) UFD ON UFD.UserLocationID = UL.LocationID
          WHERE S.SessionID = '.$SessionID.'
          GROUP BY UL.UserLocationID;');
          
      if ($QR == 0) {
      
        $Response->S = false;
        $Response->J = (count($SessionCoords) > 0)? 'NamLoc();':'NewLoc();';
        $Response->Send();
      }
      
      $Sort[$UserSort] = array('D' => GS(1380+$UserSort));
          
    } else {

      list ($QR, $RS, $T) = QuerySet(
        'SELECT 0 AS LocationID, S.Latitude AS Lat, S.Longitude AS Lng, S.Country AS CID, C.CountryCurrency AS CurS, "you" AS UName
           FROM 0700_Sessions S
          INNER JOIN 3100_Countries C ON C.CountryID = S.Country
          WHERE S.SessionID = '.$SessionID.';');
          
      if ($QR == 0) {
        $Response->S = false;
        $Response->J = 'CData(); PopErr();';
        $Response->Send();
      }
          
      $Sort[0] = array('D' => GS(1380));
    
    }
    
    if ($QR > 0) {
    
      while ($DR = mysql_fetch_array($RS)) {
      
        $Offset = 0;
        
        $AULocation = new ULocation;
        $AULocation->Lat = $DR['Lat'];
        $AULocation->Lng = $DR['Lng'];
        $AULocation->CurS = $DR['CurS'];
        $AULocation->Desc = StringAdjust($DR['UName']);
        
        if ($UserID > 0) {
        
          if (!is_null($DR['CFilt']) || $DR['CFilt'] == '') {
            $TempArray = explode('|', $DR['CFilt']);
            foreach ($TempArray as $TempValue) {
              if (stripos($TempValue, ',') !== false) {
                $TempItem = explode(',', $TempValue);
                $AULocation->CFilt[$TempItem[0]] = $TempItem[1];
              }
            }
          }
          
          if (!is_null($DR['TFilt']) || $DR['TFilt'] == '') {
            $AULocation->TFilt = explode(',', $DR['TFilt']);
          }
          
          if (!is_null($DR['DFilt']) || $DR['DFilt'] == '') {
            $TempArray = explode('|', $DR['DFilt']);
            foreach ($TempArray as $TempValue) {
              if (stripos($TempValue, ',') !== false) {
                $TempItem = explode(',', $TempValue);
                $AULocation->DFilt[$TempItem[0]] = $TempItem[1];
              }
            }
          }
          
        }
        
        //Get deals
        //------------------------------------        
        
        list ($SQR, $SRS, $T) = QuerySet(
          'SELECT D.DealID, UNIX_TIMESTAMP(D.DateEnds) AS DateEnds, COALESCE(LSDa.StringText, LSDb.StringText) AS Descr, D.DealPrice AS SPrice, D.DealValue AS RPrice,
              St.StoreID, ST.TypeID, COALESCE(SC.CategoryID, 0) AS CategoryID, COALESCE(STy.Icon, SC.Icon, "Blank") AS Icon, LD.LocationLatitude AS Lat
             FROM 4000_Deals D
            INNER JOIN 0200_Language_Strings LSDa ON D.StringID = LSDa.StringID AND LSDa.LanguageID = '.$LanguageID.'
            INNER JOIN 0200_Language_Strings LSDb ON D.StringID = LSDb.StringID AND LSDb.LanguageID = 1
            INNER JOIN 2000_Stores ST ON D.StoreID = St.StoreID
            INNER JOIN 2200_Store_Locations SL ON SL.StoreID = ST.StoreID
            INNER JOIN 3000_Locations LD ON LD.LocationID = SL.LocationID
             LEFT JOIN 2110_Store_Types STy ON ST.TypeID = STy.TypeID
             LEFT JOIN 2100_Store_Categories SC ON STy.CategoryID = SC.CategoryID
            WHERE D.DateEnds > '.date('YmdHis').'
              AND (GetDistance('.$AULocation->Lat.', '.$AULocation->Lng.', LD.LocationLatitude, LD.LocationLongitude) <= 100
               OR (LD.LocationLatitude = -1 AND LD.LocationLongitude = -1 AND (LD.CountryID = '.$DR['CID'].' OR LD.CountryID = 0)))
            GROUP BY D.DealID;');
            
        if ($SQR > 0) {
        
          while ($SDR = mysql_fetch_array($SRS)) {
          
            $ADeal = new Deal;
            $ADeal->EDate = (int)$SDR['DateEnds'];
            $ADeal->Desc = $SDR['Descr'];
            $ADeal->TID = (int)$SDR['TypeID'];
            $ADeal->CID = (int)$SDR['CategoryID'];
            $ADeal->Icon = $SDR['Icon'];
            $ADeal->RPrice = (double)$SDR['RPrice'];
            $ADeal->SPrice = (double)$SDR['SPrice'];
            $ADeal->Web = ($SDR['Lat'] == -1);
            
            if ($SDR['Lat'] != -1) {
            
              list ($SSQR, $SSRS, $T) = QuerySet(
                'SELECT LD.LocationID, LD.LocationLatitude AS Lat, LD.LocationLongitude AS Lng, GetDistance('.$AULocation->Lat.', '.$AULocation->Lng.', LD.LocationLatitude, LD.LocationLongitude) AS Distance, DSU.URLID
                   FROM 2200_Store_Locations SL
                  INNER JOIN 3000_Locations LD ON LD.LocationID = SL.LocationID
                   LEFT JOIN (SELECT URLID, Latitude, Longitude FROM 4110_Deal_Source_URLs GROUP BY Latitude, Longitude) DSU ON DSU.Latitude = LD.LocationLatitude AND DSU.Longitude = LD.LocationLongitude
                  WHERE SL.StoreID = '.$SDR['StoreID'].'
                 HAVING Distance <= 100;');
              
              if ($SSQR > 0) {
                
                while ($SSDR = mysql_fetch_array($SSRS)) {
              
                  $ASLocation = new SLocation;
                  if (is_null($SSDR['URLID'])) {
                    $ASLocation->Lat = (double)$SSDR['Lat'];
                    $ASLocation->Lng = (double)$SSDR['Lng'];
                  } else {
                    $ASLocation->Lat = ((double)$SSDR['Lat'] + ($Offset * 0.002 * ((($Offset & 1) == 1)?1:-1) ));
                    $ASLocation->Lng = ((double)$SSDR['Lng'] + ($Offset * 0.002 * ((($Offset & 2) == 2)?1:-1) ));
                    $Offset++;
                  }
                  $ASLocation->Dist = (double)$SSDR['Distance'];
                  
                  $ADeal->Locs[$SSDR['LocationID']] = $ASLocation;
                  
                }
              
              } elseif ($SSQR < 0) {
              
                SysLogIt('Error searching for store locations.', StatusError, ActionSelect);
                $Response->Send();
              
              }
              
            }
            
            $AULocation->Deals[$SDR['DealID']] = $ADeal;
          
          }
        
        } elseif ($SQR < 0) {
        
          SysLogIt('Error searching for deals.', StatusError, ActionSelect);
          $Response->Send();
        
        }
        
        //Get saved deals
        //------------------------------------        
        
        list ($SQR, $SRS, $T) = QuerySet(
          '(
           SELECT -UMF.FavoriteID AS FID, 0 AS DID, 0 AS StoreID, UMF.Description AS SName, UNIX_TIMESTAMP(UMF.Expiry) AS ExpDate, UMF.DValue AS DValue, UMF.Longitude AS Lng, UMF.Latitude AS Lat, "Fav" AS Icon
             FROM 1210_User_Manual_Favorites UMF
            WHERE UMF.UserID = '.$UserID.'
              AND UMF.LocationID = '.$DR['LocationID'].'
           ) UNION (
            SELECT UF.FavoriteID AS FID, UF.DealID AS DID, S.StoreID, S.StoreName AS SName, UNIX_TIMESTAMP(D.DateExpiry) AS ExpDate, D.DealValue AS DValue, 0 AS Lat, 0 AS Lng,
             COALESCE(ST.Icon, SC.Icon, "Fav") AS Icon
             FROM 1200_User_Favorites UF
            INNER JOIN 4000_Deals D ON UF.DealID = D.DealID
            INNER JOIN 2000_Stores S ON D.StoreID = S.StoreID
             LEFT JOIN 2110_Store_Types ST ON S.TypeID = ST.TypeID
             LEFT JOIN 2100_Store_Categories SC ON ST.CategoryID = SC.CategoryID
            WHERE UF.UserID = '.$UserID.'
              AND UF.LocationID = '.$DR['LocationID'].'
           );');
            
        if ($SQR > 0) {
        
          while ($SDR = mysql_fetch_array($SRS)) {
          
            $ASDeal = new FDeal;
            $ASDeal->EDate = (($SDR['ExpDate'] == 0) || (date('Y', $SDR['ExpDate']) == 1969))?0:(int)$SDR['ExpDate'];
            $ASDeal->Desc = StringAdjust($SDR['SName']);
            $ASDeal->Icon = $SDR['Icon'];
            $ASDeal->Value = (double)$SDR['DValue'];
            $ASDeal->DID = (int)$SDR['DID'];
            
            if ($SDR['FID'] > 0) {
            
              list ($SSQR, $SSRS, $T) = QuerySet(
                'SELECT LD.LocationID, LD.LocationLatitude AS Lat, LD.LocationLongitude AS Lng, GetDistance('.$AULocation->Lat.', '.$AULocation->Lng.', LD.LocationLatitude, LD.LocationLongitude) AS Distance
                   FROM 2200_Store_Locations SL
                  INNER JOIN 3000_Locations LD ON LD.LocationID = SL.LocationID
                  WHERE SL.StoreID = '.$SDR['StoreID'].'
                 HAVING Distance <= 100
                     OR Lat = -1;');
              
              if ($SSQR > 0) {
                
                while ($SSDR = mysql_fetch_array($SSRS)) {
                
                  if ($SSDR['Lat'] == -1) {
                    $ASDeal->Web = true;
                    break;
                  }
              
                  $ASLocation = new SLocation;
                  $ASLocation->Lat = (double)$SSDR['Lat'];
                  $ASLocation->Lng = (double)$SSDR['Lng'];
                  $ASLocation->Dist = (double)$SSDR['Distance'];
                  
                  $ASDeal->Locs[$DR['LocationID']] = $ASLocation;
                  
                }
              
              } elseif ($SSQR < 0) {
              
                SysLogIt('Error searching for saved deal\'s store locations.', StatusError, ActionSelect);
                $Response->Send();
              
              }
            
            } else {
            
              $ASLocation = new SLocation;
              $ASLocation->Lat = (double)$SDR['Lat'];
              $ASLocation->Lng = (double)$SDR['Lng'];
              
              $ASDeal->Locs[$DR['LocationID']] = $ASLocation;
            
            }
            
            $AULocation->Saves[$SDR['FID']] = $ASDeal;
          
          }
        
        } elseif ($SQR < 0) {
        
          SysLogIt('Error searching for saved deals.', StatusError, ActionSelect);
          $Response->Send();
        
        }

        //Location is good to go
        
        $Locations[$DR['LocationID']] = $AULocation;
        
      }
      
    } elseif ($QR < 0) {
    
      SysLogIt('Error searching for user locations.', StatusError, ActionSelect);
      $Response->Send();
      
    }   
    
    $Response->C = $Sort;
    $Response->D = $Cats;    
    $Response->R = $Locations;
    $Response->S = true;
    $Response->Send();
  
  }  
  
  function SetSorts() {
  
    global $Response;
    global $UserID;
    
    if ($UserID>0) {
    
      if (isset($_POST['S'])) {
        if (is_numeric($_POST['S'])) {
          if ((int)$_POST['S'] >= 0 && (int)$_POST['S'] <= 4) {
            if (ExecCommand('UPDATE 1000_Users SET UserSort = '.(int)$_POST['S'].' WHERE UserID = '.$UserID.';')) {
              $Response->S = true;
            } else {
              SysLogIt('Error updating sort option for user with ID of '.$UserID.'.', StatusError, ActionUpdate);
            }
          }
        }
      }
      
    }
        
    $Response->Send();
    
  }
  
  function ResetFilters() {
  
    global $UserID;
    global $Response;
    
    $Response->J = 'PopErr();';
    
    if ($UserID > 0) {
      if (isset($_POST['L'])) {
        if (is_numeric($_POST['L'])) {
        
          list ($QR, $DR, $T) = QuerySingle("SELECT UL.UserLocationID FROM 1100_User_Locations UL WHERE UL.UserID = ".$UserID." AND UL.LocationID = ".(int)$_POST['L'].";");
          if ($QR > 0) {

            if (!(ExecCommand('DELETE FROM 1110_User_Filters WHERE UserLocationID = '.$DR['UserLocationID'].';'))) {
            
              SysLogIt('Error reseting filters for user with ID of '.$UserID.'.', StatusError, ActionDelete);
              
            } else {
            
              $Response->S = true;
              $Response->J = 'Locs[CurLoc].CF.length = 0; Locs[CurLoc].TF.length = 0; Locs[CurLoc].DF.length = 0; UpLst();';
            
            }
            
          }
          
        }
      }
        
    }
    
    $Response->Send();
    
  }
  
  function CheckFilter() {
  
    global $UserID;
    
    if ($UserID > 0) {
      if (isset($_POST['L']) && isset($_POST['V'])) {
        if (is_numeric($_POST['L']) && is_numeric($_POST['V'])) {
        
          list ($QR, $DR, $T) = QuerySingle("SELECT UL.UserLocationID FROM 1100_User_Locations UL WHERE UL.UserID = ".$UserID." AND UL.LocationID = ".(int)$_POST['L'].";");
          if ($QR > 0) {
          
            if (isset($_POST['C'])) {
              SetFilter($DR['UserLocationID'], FilterCategory, (int)$_POST['C'], (int)$_POST['V'], 100, true);
            } elseif (isset($_POST['T'])) {
              SetFilter($DR['UserLocationID'], FilterType, (int)$_POST['T'], (int)$_POST['V'], 1, false);
            } elseif (isset($_POST['D'])) {
              SetFilter($DR['UserLocationID'], FilterDeal, (int)$_POST['D'], (int)$_POST['V'], -1, true);
            }
            
          }
          
        }
      }
    }
    
    exit();
  
  }
  
  function SetFilter($ULID, $FType, $SID, $DoVal, $DelVal, $DoUpdate) {
  
    global $UserID;
  
    $Filter = 'UserLocationID = '.$ULID.' AND FilterType = '.$FType.' AND FilterSourceID = '.$SID;
      
    if ($DoVal == $DelVal) {
    
      //Delete filter, if exists
      if (!(ExecCommand('DELETE FROM 1110_User_Filters WHERE '.$Filter.';')))
        SysLogIt('Error deleting category filter for user with ID of '.$UserID.'.', StatusError, ActionDelete);
        
    } else {
    
      list ($SQR, $SDR, $T) = QuerySingle('SELECT FilterID FROM 1110_User_Filters WHERE '.$Filter.';');
      if ($SQR < 0) {
      
        //Error looking up filter
        SysLogIt('Error looking up filter of type '.$FType.' for user with ID of '.$UserID.'.', StatusError, ActionSelect);
        
      } elseif ($SQR>0) {
      
        if ($DoUpdate) {
        
          //Update existing filter
          if (!(ExecCommand('UPDATE 1110_User_Filters SET FilterValue = '.$DoVal.' WHERE FilterID = '.(int)$SDR['FilterID'].';')))
            SysLogIt('Error updating filter of type '.$FType.' with ID of '.(int)$SDR['FilterID'].'.', StatusError, ActionUpdate);
            
        }
          
      } else {
      
        //No filter found, insert new filter
        if (!(ExecCommand('INSERT INTO 1110_User_Filters (UserLocationID, FilterSourceID, FilterType, FilterValue) VALUES ('.$ULID.','.$SID.','.$FType.','.$DoVal.');')))
          SysLogIt('Error creating filter of type '.$FType.' for user with ID of '.$UserID.'.', StatusError, ActionInsert);
          
      }
      
    }
    
  }
  
  function ValidCoords($InValue, &$Ctry, &$Lat, &$Lng) {
  
    if (stripos($InValue, '(') !== false && stripos($InValue, ',') !== false && stripos($InValue, ')') !== false) {
    
      $Parts = str_ireplace('(', '', $InValue);
      $Parts = str_ireplace(')', '', $Parts);
      $Parts = explode(",", $Parts);
      
      if (count($Parts) == 3) {
      
        $Lat = trim($Parts[0]);
        $Lng = trim($Parts[1]);
        $Ctry = GetCountry(trim($Parts[2]));
        
        if (is_numeric($Lat) && is_numeric($Lng)) {
        
          if ( $Lng >= -180 && $Lng <= 180 && $Lat >= -90 && $Lat <= 90 ) return true;
          
        }
        
      }
      
    }
    
    return false;
    
  }
  
  function DoVerification() {
  
    global $UserID;
    global $Response;
    
    $Response->J = 'PopErr();';
    
    $Strings = GSA('1770,1771');
    
    list ($QR, $DR, $T) = QuerySingle("SELECT UserID, UserEmail, UserEmailVerified AS UEV FROM 1000_Users WHERE UserID = ".$UserID.";");
    
    if ($QR < 0) {
    
      SysLogIt('Could not find email information for user with ID of '.$UserID.'.', StatusError, ActionSelect);
    
    } else {
    
      if ($DR['UEV'] == 1) {
      
        $Response->S = true;
        $Response->J = "PopC('".Pacify(Pacify($Strings[1771]), true)."');";
      
      } elseif (QueueVerification($DR['UserID'], $DR['UserEmail'])) {
        
        $Response->S = true;
        $Response->J = "PopC('".Pacify(Pacify($Strings[1770]), true)."');";
        
      }
    
    }
    
    $Response->Send();
  
  }
  
  function QueueVerification($UID, $EMail) {
  /*/////////////////////////////////////////////////////////////
       Author: Plottery Corp.
      Created: v1.0.0 - 2011-08-07
    Revisions: None
      Purpose: Adds an email verification request to the queue
      Returns: Nothing
  *////////////////////////////////////////////////////////////// 
  
    if (ExecCommand("UPDATE 1010_User_Email_Verifications SET VerificationExpiry = 0 WHERE VerificationExpiry > ".date('YmdHis')." AND UserID = ".$UID.";")) {
    
      $Key = dechex(mt_rand()).dechex(mt_rand());
      
      if (!ExecCommand("INSERT INTO 1010_User_Email_Verifications (VerificationEmail, UserID, VerificationKey, VerificationDate, VerificationExpiry)
                        VALUES ('".$EMail."',".$UID.",'".$Key."',".date('YmdHis').",".date('YmdHis', mktime(date('H'),date('i'),date('s'),date('n'),date('j')+2,date('Y'))).");"))
        return SysLogIt('Could not add verification entry for user with ID of '.$UID.'.', StatusError, ActionInsert);
        
      return true;

    }
  
  }
  
  function CheckVerification() {
  /*/////////////////////////////////////////////////////////////
       Author: Plottery Corp.
      Created: v1.0.0 - 2011-08-07
    Revisions: None
      Purpose: Activates an email address
      Returns: Nothing
  *//////////////////////////////////////////////////////////////   
  
    if (isset($_GET['Key'])) {
    
      list ($QR, $DR, $T) =
        QuerySingle(
          "SELECT UEV.VerificationID AS VID, UEV.UserID AS UID
             FROM 1010_User_Email_Verifications UEV
            WHERE UEV.VerificationExpiry >= ".date('YmdHis')."
              AND UEV.VerificationKey = '".Pacify($_GET['Key'])."';");
      
      if ($QR < 0) {
      
        SysLogIt('Error searching for validation with key of '.Pacify($_GET['Key']).'.', StatusError, ActionSelect);
      
      } else {
      
        $Strings = GSA('2007,2008');
    
        if ($QR > 0) {
        
          if (ExecCommand("UPDATE 1000_Users SET UserEmailVerified = 1 WHERE UserID = ".$DR['UID'].";")) {
          
            SysLogIt('Successfully verified email for user with ID of '.$DR['UID'].'.', StatusInfo);
            return DisplayMainScreen("SetBackMap(); DoRndLoc(); PopC('".Pacify(Pacify($Strings[2007]), true)."','GH();');");
          
          } else {

            SysLogIt('Could not add verification entry for user with ID of '.$UID.'.', StatusError, ActionInsert);

          }

        } else {
        
          return DisplayMainScreen("SetBackMap(); DoRndLoc(); PopErr('".Pacify(Pacify($Strings[2008]), true)."','GH();');");

        }

      }

    }
      
    return DisplayMainScreen('PopErr();');
  
  }
  
  function QueueReset($UID, $EMail) {
  /*/////////////////////////////////////////////////////////////
       Author: Plottery Corp.
      Created: v1.0.0 - 2011-08-07
    Revisions: None
      Purpose: Adds an email Reset request to the queue
      Returns: Nothing
  *////////////////////////////////////////////////////////////// 
  
    if (ExecCommand("UPDATE 1020_User_Password_Resets SET ResetExpiry = 0 WHERE ResetExpiry > ".date('YmdHis')." AND UserID = ".$UID.";")) {
    
      $Key = dechex(mt_rand()).dechex(mt_rand());
      
      if (!ExecCommand("INSERT INTO 1020_User_Password_Resets (ResetEmail, UserID, ResetKey, ResetDate, ResetExpiry)
                        VALUES ('".$EMail."',".$UID.",'".$Key."',".date('YmdHis').",".date('YmdHis', mktime(date('H'),date('i'),date('s'),date('n'),date('j')+2,date('Y'))).");"))
        return SysLogIt('Could not add reset entry for user with ID of '.$UID.'.', StatusError, ActionInsert);
        
      return true;

    }
  
  }
  
  function CheckReset() {
  /*/////////////////////////////////////////////////////////////
       Author: Plottery Corp.
      Created: v1.0.0 - 2011-08-07
    Revisions: None
      Purpose: Activates an email address
      Returns: Nothing
  *//////////////////////////////////////////////////////////////   
  
    if (isset($_GET['Key'])) {
    
      list ($QR, $DR, $T) =
        QuerySingle(
          "SELECT UPR.ResetID AS VID, UPR.UserID AS UID, U.UserPassSalt
             FROM 1020_User_Password_Resets UPR
            INNER JOIN 1000_Users U ON UPR.UserID = U.UserID
            WHERE UPR.ResetExpiry >= ".date('YmdHis')."
              AND UPR.ResetKey = '".Pacify($_GET['Key'])."';");
      
      if ($QR < 0) {
      
        SysLogIt('Error searching for password reset with key of '.Pacify($_GET['Key']).'.', StatusError, ActionSelect);
      
      } else {
    
        if ($QR > 0) {
        
          $Strings = GSA('2056,2057');
          $Key = dechex(mt_rand());
      
          if (ExecCommand("UPDATE 1000_Users SET UserPassHash = '".md5($DR['UserPassSalt'].$Key)."' WHERE UserID = ".$DR['UID'].";")) {
          
            SysLogIt('Successfully reset password for user with ID of '.$DR['UID'].'.', StatusInfo);
            return DisplayMainScreen("SetBackMap(); DoRndLoc(); PopC('".Pacify(Pacify($Strings[2056].' '.$Key), true)."','GH();');");
          
          } else {

            SysLogIt('Could not add password reset entry for user with ID of '.$DR['UID'].'.', StatusError, ActionInsert);

          }

        } else {
        
          return DisplayMainScreen("SetBackMap(); DoRndLoc(); PopErr('".Pacify(Pacify($Strings[2057]), true)."','GH();');");

        }

      }

    }
      
    return DisplayMainScreen('PopErr();');
  
  }  
  
  function CheckUnsubscribe() {
  /*/////////////////////////////////////////////////////////////
       Author: Plottery Corp.
      Created: v1.0.0 - 2011-08-14
    Revisions: None
      Purpose: Unsubscribes a user from all notifications
      Returns: Nothing
  *////////////////////////////////////////////////////////////// 

    if (isset($_GET['Key'])) {
    
      list ($QR, $DR, $T) =
        QuerySingle(
          "SELECT UN.NotificationID AS NID, UN.UserID AS UID
             FROM 1400_User_Notifications UN
            WHERE UN.CancelKey = '".Pacify($_GET['Key'])."';");
      
      if ($QR < 0) {
      
        SysLogIt('Error searching for cancel key '.Pacify($_GET['Key']).'.', StatusError, ActionSelect);
      
      } else {
      
        $Strings = GSA('2112');
    
        if ($QR > 0) {
        
          if (ExecCommand("UPDATE 1000_Users SET UserFlags = (UserFlags & ~".UserReminders.") WHERE UserID = ".$DR['UID'].";")) {
          
            SysLogIt('Successfully disabled reminders for user with ID of '.$DR['UID'].'.', StatusInfo);
            
            if (ExecCommand("UPDATE 1400_User_Notifications SET Settings = 0 WHERE NotificationID = ".$DR['NID'].";")) {
            
              SysLogIt('Successfully disabled digests for user with ID of '.$DR['UID'].'.', StatusInfo);
              return DisplayMainScreen("SetBackMap(); PopC('".Pacify(Pacify($Strings[2112]), true)."','GH();');");
              
            } else {
            
              SysLogIt('Could not disable digests for user with ID of '.$UID.'.', StatusError, ActionInsert);
              
            }
          
          } else {

            SysLogIt('Could not disable reminders for user with ID of '.$UID.'.', StatusError, ActionInsert);

          }

        }

      }

    }
      
    return DisplayMainScreen('PopErr();');
  
  }
?>
<?php

  session_set_cookie_params(0, '/', Cookies, false, true);

  function ReadCookies() {
  /*/////////////////////////////////////////////////////////////
       Author: Plottery
      Created: v1.0.0 - 2010-12-09
    Revisions: None
      Purpose: Reads browser cookies and retrieves session and user information
      Returns: True if valid data found, or false
  *//////////////////////////////////////////////////////////////  
      
    global $UserID;
    global $UserName;
    global $UserSort;
    global $UserFlags;
    
    global $SessionID;
    global $SessionCoords;
    
    global $LanguageID;
    global $LanguageCode;
    
    $UserID = 0;
    $UserFlags = 0;
    
    $SessionID = 0;
    
    //Check for language information
    if (isset($_COOKIE['LID'])) {
      if (is_numeric($_COOKIE['LID'])) {
        
        list ($QR, $DR, $T) = QuerySingle("SELECT LanguageID, LanguageCode FROM 0000_Languages WHERE LanguageID = ".(int)$_COOKIE['LID']." AND LanguageActive = 1;");
        if ($QR > 0) {
          $LanguageID = $DR['LanguageID'];
          $LanguageCode = $DR['LanguageCode'];
        }

      }
    }
        
    //Check for registered user session
    if (isset($_COOKIE['SKEY'])) {
      
      list ($QR, $DR, $T) = QuerySingle("SELECT S.SessionID, S.SessionPort, S.SessionIP, S.Latitude, S.Longitude, S.Country, U.UserID, U.UserFlags, U.UserSort, COALESCE(U.UserName, U.UserUsername) AS Name, L.LanguageID, L.LanguageCode
                                            FROM 0700_Sessions S
                                            LEFT JOIN 1000_Users U ON S.UserID = U.UserID
                                            LEFT JOIN 0000_Languages L ON U.LanguageID = L.LanguageID
                                           WHERE SessionKey = '".Pacify($_COOKIE['SKEY'])."';");

      if ($QR < 0) return SysLogIt('Error looking up session key. Requested key was: '.$_COOKIE['SKEY'], StatusError, ActionSelect);
      if ($QR > 0) {
      
          
        //Retrieve coordinates for non-registered users
        if (!(is_null($DR['Latitude']) || is_null($DR['Longitude']))) $SessionCoords = array($DR['Latitude'], $DR['Longitude'], $DR['Country']);      
      
        //Set session ID and update
        $SessionID = $DR['SessionID'];
        if (!ExecCommand("UPDATE 0700_Sessions SET SessionAccessDate = ".date('YmdHis')." WHERE SessionID = ".$SessionID.";")) SysLogIt('Error updating session with ID of '.$SessionID.'.', StatusError, ActionUpdate);
      
        if (is_null($DR['UserID'])) {
          
          //Retrieve coordinates for non-registered users
          return (count($SessionCoords) > 0);
        
        } else {
        
          //Retrieve data for registered users
          if (($DR['UserFlags'] & UserActive) == UserActive) {
          
            if ($DR['SessionPort'] == 1 || $DR['SessionIP'] == $_SERVER["REMOTE_ADDR"]) {
        
              //Regular user
              $UserID = $DR['UserID'];
              $UserName = $DR['Name'];
              $UserSort = $DR['UserSort'];
              $UserFlags = $DR['UserFlags'];
              
              if (!is_null($DR['LanguageID'])) {
                $LanguageID = $DR['LanguageID'];
                $LanguageCode = $DR['LanguageCode'];
              }
              
              if ($DR['SessionPort'] == 1) {
                setcookie('SKEY', $_COOKIE['SKEY'], time() + (60 * 60 * 24 * 90)); //Extend cookie another 90 days if portable session
              } else {
                setcookie('SKEY', $_COOKIE['SKEY'], time() + (60 * 60));
              }
              
              return true;
              
            } else {
            
              //Mismatched IP on non-portable session.
              FlushSession($DR['UserID']);
              return false;
            
            }
            
          } else {
          
            //Disabled user
            FlushSession($DR['UserID']);
            return false;
            
          }
          
        }
        
      }
      
      return true;
      
    }
    
    /*
    //Check for home location information
    if (isset($_COOKIE['LNG']) && isset($_COOKIE['LAT'])) {
      if (is_numeric($_COOKIE['LNG']) && is_numeric($_COOKIE['LAT'])) {
        if ( (double)$_COOKIE['LNG'] >= -180 && (double)$_COOKIE['LNG'] <= 180 && (double)$_COOKIE['LAT'] >= -90 && (double)$_COOKIE['LAT'] <= 90 ) return true;
      }
    }
    */
    
    return false;
    
  }
  
  function FlushSession($UserID = 0) {
  /*/////////////////////////////////////////////////////////////
       Author: Plottery
      Created: v1.0.0 - 2010-12-09
    Revisions: None
      Purpose: Delete sessions and cookies for user
      Returns: True if successful, or false
  *//////////////////////////////////////////////////////////////

    
    global $SessionID;

    setcookie('SKEY', '', time() - 3600);
    setcookie('LID', '', time() - 3600);
  
    if ($SessionID > 0) {
      if (!ExecCommand("DELETE FROM 0700_Sessions WHERE SessionID = ".(int)$SessionID.";")) return SysLogIt('Could not delete session with ID of '.(int)$SessionID.'.', StatusError, ActionDelete);
    }    
      
    if ($UserID > 0) {
      if (!ExecCommand("DELETE FROM 0700_Sessions WHERE UserID = ".(int)$UserID.";")) return SysLogIt('Could not delete session(s) for user with ID of '.(int)$UserID.'.', StatusError, ActionDelete);
    }
    
    return true;

  }
  
  function SignOut() {
  
    global $UserID;
    global $Response;
  
    $Response->S = FlushSession();
    $Response->J = 'RstVar(); F5();';
    //$Response->J = 'RstVar(); '.((stripos($_SERVER['HTTP_USER_AGENT'], 'msie') === false)?'F5();':'SF5();');
    $Response->Send();
  
  }
  
  
  function DisplayCookieError($LID = 1) {
  
?>


<?php      
  
  }

?>
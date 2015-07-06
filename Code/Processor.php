<?php

  //$SignInFunction = 'DisplayLoginPage';

  function ProcessURL() {
  /*/////////////////////////////////////////////////////////////
       Author: Plottery Corp.
      Created: v1.0.0 - 2009-06-12
    Revisions: None
      Purpose: Extract command name from URL query string
      Returns: Command name
  *//////////////////////////////////////////////////////////////
  
    $QueryCommand = (strpos($_SERVER['QUERY_STRING'], "&") === false) ? $_SERVER['QUERY_STRING'] : substr($_SERVER['QUERY_STRING'], 0, strpos($_SERVER['QUERY_STRING'], "&"));
 
    return $QueryCommand;
  
  }

  function ProcessCommand($GETCommand, $POSTCommand = null, $RequiresSession = false, $Permission = 0, $Parameters = null) {
  /*/////////////////////////////////////////////////////////////
       Author: Plottery Corp.
      Created: v1.0.0 - 2009-06-12
    Revisions: None
      Purpose: Checks current query string and POST data and redirects based on provided criteria
      Returns: Nothing
  *//////////////////////////////////////////////////////////////  
  
    global $UserID;
    global $UserFlags;
    
    global $SessionID;
    global $SignInFunction;
    global $BadCommandFunction;
    
    global $Response;
  
    if ($RequiresSession && ($SessionID == 0)) {
    
      $Response->J = 'RstVar(); F5();';
      $Response->Send();
      
      /*
      if (!CheckFunction($SignInFunction)) { GlobalFail('E1000 - Signin function is not properly configured.'); }
      call_user_func($SignInFunction, $_SERVER['QUERY_STRING']);
      return;
      */
      
    }
    
    if ($Permission > 0) {
    
      if ($UserID == 0 || ($UserFlags & $Permission) != $Permission) {
      
        /*
        if (!CheckFunction($SignInFunction)) { GlobalFail('E1000 - Signin function is not properly configured.'); }
        call_user_func($SignInFunction, $_SERVER['QUERY_STRING']);
        return;
        */
        
        $Response->J = 'F5();';
        $Response->Send();        
      
      }
    
    }
    
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
          
      if (!CheckFunction($POSTCommand)) { GlobalFail('E1004 - Function specified in POSTCommand is invalid.'); }
      $Command = $POSTCommand;
    
    } else {
      
      if (!CheckFunction($GETCommand)) { GlobalFail('E1006 - Function specified in GETCommand is invalid.'); }
      $Command = $GETCommand;
    
    }
  
    if (is_null($Parameters)) {
      call_user_func($Command);
    } else {
      call_user_func($Command, $Parameters);
    }

  }
  
  function CheckFunction($FunctionName) {
  /*/////////////////////////////////////////////////////////////
       Author: Plottery Corp.
      Created: v1.0.0 - 2009-06-12
    Revisions: None
      Purpose: Verifies whether a function exists
      Returns: Boolean
  *//////////////////////////////////////////////////////////////    
  
    if ($FunctionName == null) {
      return false;
    } else {
      return (function_exists($FunctionName));
    }
  
  }
  
  
  function GlobalFail($FailID = null) {
  /*/////////////////////////////////////////////////////////////
       Author: Plottery Corp.
      Created: v1.0.0 - 2009-06-12
    Revisions: None
      Purpose: Handles a global failure event
      Returns: Nothing
  *//////////////////////////////////////////////////////////////    
  
    global $GlobalFailFunction;
    global $DebugMode;
    
    if (function_exists($GlobalFailFunction)) {
      if ($DebugMode) {
        call_user_func($GlobalFailFunction, $FailID);
      } else {
        call_user_func($GlobalFailFunction);
      }
    } else {
      echo '<DIV STYLE="position:absolute; left: 50%; top: 50%; width: 500px; height: 150px; margin-top: -75px; margin-left: -250px; text-align:center; font-size: 10pt; font-family: Lucida Sans Unicode, Verdana, Arial, Helvetica, sans-serif; background-color: transparent;"><IMG SRC="/IF/LogoS.png" WIDTH=282 HEIGHT=76 ALT=""><BR>';
      if ($DebugMode) {
        exit('An internal error has occured.<BR>Debug data follows: '.$FailID);
      } else {
        exit('An internal error has occured.');
      }
    
    }
    
    exit();
    
  }
  
  function RedirectTo($Path = '', $ReturnMessage = '') {
  /*/////////////////////////////////////////////////////////////
       Author: Plottery Corp.
      Created: v1.0.0 - 2009-06-12
    Revisions: None
      Purpose: Sends a redirect to another page
      Returns: Nothing
  *//////////////////////////////////////////////////////////////    
  
    header("Location: http://".SiteAddress."/index.php".$Path);
  
  }


?>
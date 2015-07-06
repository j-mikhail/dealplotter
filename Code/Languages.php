<?php

  define("LanguageEnglish", 1);
  define("LanguageFrench", 2);

  $LanguageID = 1;
  $LanguageCode = 'en';
  
  function CountLanguages() {
  
    list ($QR, $DR, $T) = QuerySingle("SELECT COUNT(LanguageID) AS Langs FROM 0000_Languages WHERE LanguageActive = 1;");      

    if ($QR < 1) return SysLogIt('Error counting languages.', StatusError, ActionSelect);
    return $DR['Langs'];
  
  }  
  
  function SetLanguage() {
  /*/////////////////////////////////////////////////////////////
       Author: Plottery Corp.
      Created: v1.0.0 - 2009-07-07
    Revisions: None
      Purpose: Sets the language specified in the query string in a cookie
      Returns: Boolean
  *//////////////////////////////////////////////////////////////
  
    global $UserID;
    global $LanguageID;
    global $Response;
    
    if (isset($_POST['LID'])) {
      
      if ($LanguageID = CheckLanguage($_POST['LID'])) { 
      
        if ($UserID > 0) {
          if (!(ExecCommand('UPDATE 1000_Users SET LanguageID = '.$LanguageID.' WHERE UserID = '.$UserID.';'))) {
            SysLogIt('Error updating language for user with ID of '.$UserID.'.', StatusError, ActionUpdate);
          } else {
            $Response->S = true;
            $Response->J = 'SF5();';
          }
        } else {
          //Save cookie
          setcookie("LID", $LanguageID, time()+(60*60*24*365));
          $Response->S = true;
          $Response->J = 'SF5();';
        }
        
      }
      
    }
    
    $Response->Send();
  
  }
  
  function CheckLanguage($LID) {
  /*/////////////////////////////////////////////////////////////
       Author: Plottery Corp.
      Created: v1.0.0 - 2009-07-07
    Revisions: None
      Purpose: Checks if a specified Language ID is valid
      Returns: Boolean
  *//////////////////////////////////////////////////////////////
  
    if ($LID == '' || !is_numeric($LID) || (int)$LID < 1) { return false; }
    
    list ($QR, $DR, $T) = QuerySingle('SELECT LanguageID FROM 0000_Languages WHERE LanguageID = '.(int)$LID.' AND LanguageActive = 1;');
    if ($QR < 1) return false;
    
    return $DR['LanguageID'];
  
  }
  
  function GS($SID, $LID = 0, $Block = false, $HTML = false) {
  /*/////////////////////////////////////////////////////////////
       Author: Plottery Corp.
      Created: v1.0.0 - 2009-07-07
    Revisions: None
      Purpose: Read a string from the database
      Returns: String
  *//////////////////////////////////////////////////////////////
  
    //Global variables
    global $LanguageID;
    
    //Determine language to query
    $QueryLanguage = ($LID == 0)? $LanguageID:$LID;

    //Read string from DB
    list ($QR, $DR, $T) = QuerySingle('SELECT StringText AS S FROM 0200_Language_Strings WHERE StringID = '.$SID.' AND LanguageID = '.$QueryLanguage.';');
    if ($QR < 1) return '';
    
    return StringAdjust($DR['S'], $HTML, $Block);
    
  }
  
  function GSA($SIDs, $LID = 0, $Block = false, $HTML = false) {
  
    global $LanguageID;
    $QueryLanguage = ($LID == 0)? $LanguageID:$LID;
    
    ExecCommand("SET SESSION group_concat_max_len = 20480");
    
    list ($QR, $DR, $T) = QuerySingle("SELECT GROUP_CONCAT(CONCAT_WS('\\\\', StringID, StringText) SEPARATOR '|') AS AllStrings
                                          FROM 0200_Language_Strings
                                         WHERE StringID IN (".$SIDs.")
                                           AND LanguageID = ".$QueryLanguage."
                                         GROUP BY LanguageID
                                         ORDER BY StringID;");
    
    if ($QR < 1) return '';
    
    $TempArray = explode('|', $DR['AllStrings']);
    
    $RealArray = array();
    foreach ($TempArray as $TempItem) {
      $SplitItem = explode('\\', $TempItem);
      if (count($SplitItem) == 2) $RealArray[(int)$SplitItem[0]] = StringAdjust($SplitItem[1], $HTML, $Block);
    }
    
    //Fill in missing fields
    $IDs = explode(',', $SIDs);
    foreach ($IDs as $ID) {
      if (!array_key_exists($ID, $RealArray)) $RealArray[$ID] = '?';
    }
    
    return $RealArray;
  
  }
  
  function StringAdjust($String, $HTML = false, $Block = false) {
  
    //Pacify brackets if not set as HTML
    if (!$HTML) {
      $String = str_replace('<', '&lt;', $String);
      $String = str_replace('>', '&gt;', $String);
    }
    
    //Format appropriately
    if ($Block) {
      $String = str_replace(chr(13).chr(10), '<BR>', $String);
    }
    
    return (string)$String;
    
  }
  
  function CleanHTML($String, $CRLF = true) {
  
    $CleanString = $String;
    while (stripos($CleanString, '<') !== false) {
    
      $Start = stripos($CleanString, '<');
      $End = stripos($CleanString, '>', $Start);
      
      if ($End === false) {
        $CleanString = substr($CleanString, 0, $Start);
      } else {
        $CleanString = substr_replace($CleanString, ' ', $Start, $End - $Start + 1);
      }
    
    }
    
    if (stripos($CleanString, '>') !== false) $CleanString = substr($CleanString, stripos($CleanString, '>') + 1);

    $CleanString = trim(str_ireplace('&nbsp;', ' ', $CleanString));
    $CleanString = trim(str_ireplace(chr(9), ' ', $CleanString));
    if ($CRLF) {
      $CleanString = trim(str_ireplace(chr(10), ' ', $CleanString));
      $CleanString = trim(str_ireplace(chr(13), ' ', $CleanString));    
    }
    
    while (stripos($CleanString, '  ')) {
      $CleanString = trim(str_ireplace('  ', ' ', $CleanString));
    }
    
    return $CleanString;
  
  }
  
  function CleanAll(&$Array) {
  
    foreach ($Array as &$ArrItem) {
      if ((!is_numeric($ArrItem)) && (!is_array($ArrItem))) $ArrItem = CleanHTML($ArrItem);
    }
    
  }
  
    
  function CreateNewString($LID, $RangeStart, $RangeEnd, $Description, $StringText) {
  /*/////////////////////////////////////////////////////////////
       Author: Plottery Corp.
      Created: v1.0.0 - 2010-12-05
    Revisions: None
      Purpose: Creates a new string based on an available range of IDs
      Returns: Newly created string ID, or false
  *//////////////////////////////////////////////////////////////
  
    //Get next available string ID
    list ($QR, $DR, $T) = QuerySingle("SELECT MAX(StringID) AS MID FROM 0100_Strings WHERE StringID BETWEEN ".$RangeStart." AND ".$RangeEnd.";");
    if ($QR < 0) return SysLogIt('Error retrieving available string ID.', StatusError, ActionSelect);
    
    $StringID = (is_null($DR['MID']))? $RangeStart : ((int)$DR['MID']+1) ;

    //Insert new string header
    if (!InsertNewString("INSERT INTO 0100_Strings (StringID, Description) VALUES (".$StringID.",'".Pacify($Description)."');", $StringID)) return false;

    //Insert new string entry
    if (!ExecCommand("INSERT INTO 0200_Language_Strings (LanguageID, StringID, StringText) VALUES (".(int)$LID.",".$StringID.",'".Pacify($StringText)."');"))
      return SysLogIt('Error creating new string entry.', StatusError, ActionInsert);
    SysLogIt('Created new string entry.', StatusInfo, ActionInsert);
    
    return $StringID;
    
  }
  
  function InsertNewString($SQL, $ID) {
  
    if (!ExecCommand($SQL))
      return SysLogIt('Error creating new string header.', StatusError, ActionInsert);
      
    SysLogIt('Created new string header with ID of '.$ID.'.', StatusInfo, ActionInsert);
    return $ID;

  }
  
?>
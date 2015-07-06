<?php

  $DBConnection = '';
  
  function OpenDB() {
    /*/////////////////////////////////////////////////////////////
       Author: Plottery Corp.
      Created: v1.0.0 - 2009-07-07
    Revisions: None
      Purpose: Opens the database
      Returns: Nothing
  *//////////////////////////////////////////////////////////////

    global $DBConnection;
    
    $DBConnection = mysql_connect('localhost', 'root', 'XXX') or GlobalFail('E1015 - Unable to connect to database.');
    mysql_set_charset('utf8', $DBConnection);
    mysql_select_db("dealplotter", $DBConnection) or GlobalFail('E1016 - Unable to select database.');
    
  }
  
  function CloseDB() {
  /*/////////////////////////////////////////////////////////////
       Author: Plottery Corp.
      Created: v1.0.0 - 2009-07-07
    Revisions: None
      Purpose: Closes the database
      Returns: Nothing
  *//////////////////////////////////////////////////////////////
  
    global $DBConnection;
    mysql_close($DBConnection);
  
  }
  
  function GetMicroTime() { 
    
    list($usec, $sec) = explode(" ",microtime()); 
    return ((float)$usec + (float)$sec); 

  }
  
  function QuerySingle($Query) {
  /*/////////////////////////////////////////////////////////////
       Author: Plottery Corp.
      Created: v1.0.0 - 2009-07-07
    Revisions: None
      Purpose: Runs a query on the database where a single record
               is expected as a result
      Returns: Number of rows, record and query time -OR- false
  *//////////////////////////////////////////////////////////////
  
    global $DBConnection;
    
    $Time = GetMicroTime();
    $Result = mysql_query($Query, $DBConnection);
    if (!$Result) return false;
    
    if (mysql_num_rows($Result) == false || mysql_num_rows($Result) == 0) {
      return array(0, null, GetMicroTime() - $Time);
    } else {
      $Record = mysql_fetch_array($Result);
      if (!$Record) return false;
      return array(mysql_num_rows($Result), $Record, GetMicroTime() - $Time);
    }
    
  }
  
  function QuerySet($Query) {
  /*/////////////////////////////////////////////////////////////
       Author: Plottery Corp.
      Created: v1.0.0 - 2010-11-27
    Revisions: None
      Purpose: Runs a query on the database where a several records
               are expected as a result
      Returns: Number of rows, recordset and query time -OR- false
  *//////////////////////////////////////////////////////////////
  
    global $DBConnection;
    
    $Time = GetMicroTime();
    $Result = mysql_query($Query, $DBConnection);
    if (!$Result) return false;
    
    if (mysql_num_rows($Result) == false || mysql_num_rows($Result) == 0) {
      return array(0, null, GetMicroTime() - $Time);
    } else {
      return array(mysql_num_rows($Result), $Result, GetMicroTime() - $Time);
    }
    
  }  
  
  function ExecCommand($Command) {
  /*/////////////////////////////////////////////////////////////
       Author: Plottery Corp.
      Created: v1.0.0 - 2009-08-04
    Revisions: None
      Purpose: Executes a command on the database 
      Returns: Success (Boolean), Affected records (Integer)
  *//////////////////////////////////////////////////////////////

    $Resource = mysql_query($Command);
  
    if (!$Resource) {
      return false;
    } else {
      return array(true, mysql_affected_rows());
    }
  
  }
  
  function InsertAndRetrieveID($SQL, $Section) {
  /*/////////////////////////////////////////////////////////////
       Author: Plottery Corp.
      Created: v1.0.0 - 2010-12-05
    Revisions: None
      Purpose: Perform an INSERT and retrieve new record ID
      Returns: Newly created record ID, or false
  *//////////////////////////////////////////////////////////////
  
    //Insert new item
    if (!ExecCommand($SQL)) return SysLogIt('Error creating new '.$Section.'.', StatusError, ActionInsert);
    
    //Get new item record
    list ($QR, $DR, $T) = QuerySingle("SELECT last_insert_id() AS ID;");
    if ($QR < 1) return SysLogIt('Error retrieving newly inserted '.$Section.' ID.', StatusError, ActionSelect);

    SysLogIt('Created new '.$Section.' with ID of '.$DR['ID'].'.', StatusInfo, ActionInsert);
    return $DR['ID'];
              
  }
  
  function GetNewAccessKey() {
  /*/////////////////////////////////////////////////////////////
       Author: Plottery Corp.
      Created: v1.0.0 - 2010-12-07
    Revisions: None
      Purpose: Creates a new access key for administrative changes
      Returns: Access key, or false
  *//////////////////////////////////////////////////////////////  
  
    global $UserID;
    
    //Delete other keys by this user
    if (!ExecCommand("DELETE FROM 0600_Access_Keys WHERE UserID = ".$UserID.";"))
      return SysLogIt('Error flushing old user access keys.', StatusError, ActionDelete);
  
    $RandomKey = dechex(mt_rand()).dechex(mt_rand()).dechex(mt_rand());

    if (!ExecCommand("INSERT INTO 0600_Access_Keys (UserID, UniqueKey, CreateDate) VALUES (".$UserID.",'".$RandomKey."',".date('YmdHis').");"))
      return SysLogIt('Error creating access key for user with ID of '.(int)$UserID.'.', StatusError, ActionInsert);
    
    return $RandomKey;
  
  }
  
  function ValidAccessKey($InKey) {
  /*/////////////////////////////////////////////////////////////
       Author: Plottery Corp.
      Created: v1.0.0 - 2010-12-07
    Revisions: None
      Purpose: Verifies an existing access key
      Returns: True or false
  *//////////////////////////////////////////////////////////////
  
    if (!ExecCommand("DELETE FROM 0600_Access_Keys WHERE UNIX_TIMESTAMP(CreateDate) < ".mktime(date('H')-3,date('i'),date('s'),date('n'),date('j'),date('Y')).";"))
      return SysLogIt('Error flushing old access keys.', StatusError, ActionDelete);  
  
    list ($QR, $DR, $T) = QuerySingle("SELECT KeyID FROM 0600_Access_Keys WHERE UniqueKey = '".Pacify($InKey)."';");
    if ($QR < 0) return SysLogIt('Error searching for access key.', StatusError, ActionSelect);
    if ($QR == 0) return false;
    
    return (int)$DR['KeyID'];
  
  }
  
  function DeleteAccessKey($InKey) {
  
    if (!ExecCommand("DELETE FROM 0600_Access_Keys WHERE KeyID = ".$InKey.";"))
      return SysLogIt('Error deleting access key with ID of '.$InKey.'.', StatusError, ActionDelete);
      
    return true;

  }

?>
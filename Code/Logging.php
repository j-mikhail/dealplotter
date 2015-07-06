<?php

  /* Constants */
  
  define("StatusInfo", 1);
  define("StatusError", 2);
  define("StatusWarning", 3);
  define("StatusIntError", 4);
  define("StatusSecurity", 5);
  
  define("ActionNotSpecified", 0);
  define("ActionInsert", 1);
  define("ActionUpdate", 2);
  define("ActionSelect", 3);
  define("ActionDelete", 4);
  
  define("MessageSystem", 0);
  define("MessageUser", 1);
  
  /* Variables */
  
  $EchoToConsole = false;
  
  /* Functions */
  
  function SysLogIt($Msg, $Type = StatusInfo, $Action = ActionNotSpecified, $GroupID = 0) {
  
    global $EchoToConsole;
    global $Response;
    global $UserID;
    
    static $GID = 0;
    
    if ($GroupID > 0) {
      $GID = (float)(microtime(true)*10000);
    } elseif ($GroupID < 0) {
      $GID = 0;
    }
    
    $Caller = array_shift(debug_backtrace());
    $Message = $Msg;
    if ($Type == StatusError) {
      if ($Action == 0) {
        $LastError = error_get_last();
        $LastError = $LastError['message'];
        if ($LastError != 'Only variables should be passed by reference') $Message .= ' ('.$LastError.')';
      } else {
        $LastError = mysql_error();
        $Message .= ' ('.$LastError.')';
      }
    } else if ($Type == StatusIntError) {
      $Type = StatusError;
    }
  
    if (mysql_query("INSERT INTO 0500_System_Log (GroupID, UserID, Entrytype, EntryAction, EntryFunction, EntryMessage, EntryTimestamp, EntryIP) VALUES (".$GID.",".$UserID.",".$Type.",".$Action.",'".Pacify('Line '.$Caller['line'].' of '.$Caller['function'].' in '.$Caller['file'])."','".Pacify($Message)."',".date('YmdHis').",'".Pacify($_SERVER["REMOTE_ADDR"])."');") == false) { return false; }
    if ($EchoToConsole) { 
      if ($Type == StatusError) $Response->R .= '<B>';
      $Response->R .=  Pacify($Msg);
      if ($Type == StatusError) $Response->R .=  '</B>';
      $Response->R .=  '<BR />';
    }
    
    return ($Type == StatusInfo);
    
  }

  function SendMail($MsgBody, $MsgSub, $MsgToAdr = 'system@dealplotter.com', $MsgFromAdr = 'do.not.reply@dealplotter.com', $MsgFromName = 'dealplotter') {
      
    $From = $MsgFromAdr;
    if ((trim($From) == '') || (stripos($From, '@') === false)) $From = 'do.not.reply@dealplotter.com';
    
    $Headers = 'From: "'.Pacify($MsgFromName).'" <'.Pacify($From).'>'.PHP_EOL.
               'Reply-To: "'.Pacify($MsgFromName).'" <'.Pacify($From).'>'.PHP_EOL.
               'X-Mailer: PHP/'.phpversion();
               
    if (substr($MsgBody, 0, 6) == '<html>') $Headers .= PHP_EOL.'Content-type: text/html';

     if (!mail($MsgToAdr, $MsgSub, $MsgBody, $Headers)) return SysLogIt('Error sending email.', StatusError);
     
     return true;
    
  }  
  
  function SaveMessage($Type, $From, $Sub, $Body) {
  
    if (mysql_query("INSERT INTO 0800_Messages (MessageType, MessageFrom, MessageSubject, MessageBody) VALUES (".$Type.",'".Pacify($From)."','".Pacify($Sub)."','".Pacify($Body)."');") == false)
      return SysLogIt('Error creating message.', StatusError, ActionInsert);
    
    return true;
  
  }

?>
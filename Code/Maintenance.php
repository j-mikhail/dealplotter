<?php

  $DebugMode = false;
  
  $BadCommandFunction = '';
  $GlobalFailFunction = '';  
  
  date_default_timezone_set('America/New_York');
  ini_set('error_reporting', E_ALL); 
  ini_set('date.timezone', 'America/New_York');
  putenv("TZ=America/New_York");
  
  define('SiteAddress', 'www.dealplotter.com');
  define('Cookies', '.dealplotter.com');
  define('Limits', '');
  ini_set('display_errors', FALSE);

  require_once dirname(__FILE__).'/Processor.php'; //exit('E1007 - Essential Processor library not found.'); }
  require_once dirname(__FILE__).'/Interface.php'; //GlobalFail('E1008 - Essential Interface library not found.'); }
  require_once dirname(__FILE__).'/Sessions.php';  //GlobalFail('E1009 - Essential Session library not found.');
  require_once dirname(__FILE__).'/DB.php';        //GlobalFail('E1010 - Essential DB library not found.');
  require_once dirname(__FILE__).'/Cookies.php';   //GlobalFail('E1011 - Essential Cookie library not found.');
  require_once dirname(__FILE__).'/Languages.php'; //GlobalFail('E1012 - Essential Language library not found.');
  require_once dirname(__FILE__).'/Validator.php'; //GlobalFail('E1012 - Essential Language library not found.');
  require_once dirname(__FILE__).'/cURL.php';
  require_once dirname(__FILE__).'/Logging.php';
  require_once dirname(__FILE__).'/Sources.php';    
  
  function FlushSessions() {
  
    //Delete old sessions
    if (!ExecCommand("DELETE FROM 0700_Sessions
                        WHERE (SessionAccessDate < ".date('YmdHis', mktime(date('H'),date('i'),date('s'),date('n'),date('j')-90,date('Y'))).")
                           OR (SessionAccessDate < ".date('YmdHis', mktime(date('H')-3,date('i'),date('s'),date('n'),date('j'),date('Y')))." AND SessionPort = 0);")) SysLogIt('Error deleting old sessions.', StatusError, ActionDelete);
                           
    //Delete old logs
    if (!ExecCommand("DELETE FROM 0500_System_Log
                        WHERE EntryTimestamp < ".date('YmdHis', mktime(date('H'),date('i'),date('s'),date('n'),date('j')-14,date('Y')))."
                          AND EntryIP = '';")) SysLogIt('Error deleting old system log entries.', StatusError, ActionDelete);
                           
    if (!ExecCommand("DELETE FROM 0500_System_Log
                        WHERE EntryTimestamp < ".date('YmdHis', mktime(date('H'),date('i'),date('s'),date('n'),date('j')-90,date('Y'))).";")) SysLogIt('Error deleting old log entries.', StatusError, ActionDelete);

  }
  
  OpenDB();
  FlushSessions();
  UpDls();
  CloseDB();
  
  
?>
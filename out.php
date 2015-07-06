<?php

  //ini_set('error_reporting', E_ALL); 
  ini_set('display_errors', FALSE); 
    
  date_default_timezone_set('America/New_York');
  
  $UserID = 0;
  $UserName = '';
  $UserSort = 0;
  $UserFlags = 0;
  
  $SessionID = 0;
  $SessionCoords = array();
  
  $LanguageID = 0;  

  require_once './Code/Cookies.php';
  require_once './Code/DB.php';
  require_once './Code/Logging.php';
  require_once './Code/Validator.php';
  
  ReadCookies();
  
  if (!empty($_GET)) {
    if (is_array($_GET)) {
      foreach ($_GET as $ID => $Value) {
        if (is_numeric($ID)) {
        
          OpenDB();
          
          list ($QR, $DR, $T) = QuerySingle("SELECT D.DealURL, DS.DealSourceRefCode
                                                FROM 4000_Deals D
                                               INNER JOIN 4100_Deal_Sources DS ON DS.DealSourceID = D.DealSourceID
                                               WHERE D.DealID = ".(int)$ID.";");
                                               
          if ($QR > 0) {
          
            if (!ExecCommand("INSERT INTO 4200_Deal_Clickthroughs (DealID, UserID, ClickDate) VALUES (".(int)$ID.",".$UserID.",".date('YmdHis').");"))
              SysLogIt('Error inserting clickthrough data.', StatusError, ActionInsert);
          
            header('Location: '.$DR['DealURL'].$DR['DealSourceRefCode']);
            exit();
            
          }
          
          CloseDB();
          break;
        
        }
      }
    }
  }
  
  header('Location: /index.php');

?>
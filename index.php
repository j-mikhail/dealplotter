<?php

  /* Base Configuration */
  
  $DebugMode = ($_SERVER['SERVER_NAME'] == 'localhost');
  
  $BadCommandFunction = '';
  $GlobalFailFunction = '';
  
  date_default_timezone_set('America/New_York');
  putenv("TZ=America/New_York");
  ini_set('error_reporting', E_ALL); 
  ini_set('date.timezone', 'America/New_York');
  
  /* Sets */
  
  if ($DebugMode) {
    define('SiteAddress', 'localhost');
    define('Cookies', 'localhost');
    define('Limits', ' LIMIT 0,2');
    ini_set('display_errors', TRUE);
  } else {
    define('SiteAddress', 'www.dealplotter.com');
    define('Cookies', '.dealplotter.com');
    define('Limits', '');
    ini_set('display_errors', FALSE);
  }
  
  header("Cache-Control: no-cache, must-revalidate");
  header('Pragma: no-cache');  
  header('Content-type: text/html; charset=utf-8');
  
  /*
  $Data = file_get_contents('D:/wagjag.htm');
  
  $Blah = FindSubData($Text, '"companyPhone"', '>', '<', false);
  echo $Blah.'<br>';
  $Blah = FindSubData($Text, 'countdown_clock('.date('y'), ',', ')', false);
  echo $Blah.'<br>';
  $DateParts = explode(',', $Blah);
  $EndDate = mktime((int)$DateParts[2], (int)$DateParts[3], (int)$DateParts[4], (int)$DateParts[0], (int)$DateParts[1], (int)date('Y')); 
  echo date('YmdHis', $EndDate);
  
  exit();
  */
  
  /* Classes --------------------------------------------------------------------------------------------------------------------------------------------------------------- */

  class ResponseObject {

    public $S = false; //Success
    public $R; //Response
    public $C; //Class Name
    public $D; //DIV ID
    public $J; //Javascript
    
    public function Send() {
      exit('('.json_encode($this).')');
    }
  
  }  
  
  /* Includes */
  
  $Response = new ResponseObject();
  
  require_once './Code/Processor.php'; //exit('E1007 - Essential Processor library not found.'); }
  require_once './Code/Interface.php'; //GlobalFail('E1008 - Essential Interface library not found.'); }
  require_once './Code/Sessions.php';  //GlobalFail('E1009 - Essential Session library not found.');
  require_once './Code/DB.php';        //GlobalFail('E1010 - Essential DB library not found.');
  require_once './Code/Cookies.php';   //GlobalFail('E1011 - Essential Cookie library not found.');
  require_once './Code/Languages.php'; //GlobalFail('E1012 - Essential Language library not found.');
  require_once './Code/Validator.php'; //GlobalFail('E1012 - Essential Language library not found.');
  require_once './Code/cURL.php';
  require_once './Code/Logging.php';
  require_once './Code/Sources.php';  
    
  /* Commands */
  
  OpenDB();
  ReadCookies();
    
  if ($UserFlags >= 32) require_once './Code/Administration.php';

  if (is_numeric($_SERVER['QUERY_STRING'])) {
  
    GetSingleDeal();
  
  } else {
  
    switch(strtolower(ProcessURL())) {
    
      case '':
        DisplayMainScreen(); break;
        
      case 'account':
        ProcessCommand('DisplayAccount', 'ProcessAccount', true); break;
    
      case 'administration':
        ProcessCommand('DisplayAdministration', null, true); break;
      
      case 'content':
        ProcessCommand('GetMainContent', 'GetSingleContent'); break;
      
      case 'data':
        ProcessCommand('GetData', 'SetData', true); break;
        
      case 'dellocation':
        ProcessCommand('SendBadResponse', 'DeleteLocation', true); break;
        
      case 'details':
        ProcessCommand('SendBadResponse', 'GetDetails');
        
      case 'divisions':
        ProcessCommand('GetDivisions', 'SetDivisions', true, UserCanEditDivisions); break;

      case 'docats':
        ProcessCommand('ManageCategories', 'ProcessCategories', true, UserCanEditTags, $LanguageID); break;
        
      case 'filter':
        ProcessCommand('SendBadResponse', 'CheckFilter', true);

      case 'history':
        ProcessCommand('SendBadResponse', 'DisplayHistory'); break;
        
      case 'iewarn':
        ProcessCommand('IEWarn'); break;

      case 'langs':
        ProcessCommand('GetLanguages', 'SetLanguage'); break;
        
      case 'location':
        ProcessCommand('SendBadResponse', 'ProcessLocation'); break;
        
      case 'locations':
        ProcessCommand('GetUserLocations', null, true); break;
      
      case 'log':
        ProcessCommand('GetLog', 'GetLog', true, UserCanViewLog); break;

      case 'sendverify':
        ProcessCommand('DoVerification', null, true); break;

      case 'status':
        ProcessCommand('GetStatus', null, true, UserCanViewStatus); break;

      case 'login':
        ProcessCommand('SendBadResponse', 'ProcessSignOn'); break;
        
      case 'message':
        ProcessCommand('DisplayNewMessagePage', 'ProcessNewMessage'); break;
        
      case 'namelocation':
        ProcessCommand('DisplayNameLocation', 'ProcessNameLocation', true); break;
      
      case 'newaccount':
        ProcessCommand('DisplayNewAccountPage', 'ProcessNewAccount'); break;
        
      case 'newfavorite':
        ProcessCommand('DisplayNewFavoritePage', 'ProcessNewFavorite', true); break;
        
      case 'newlocation':
        ProcessCommand('DisplayNewLocation', null, true); break;
        
      case 'newreview':
        ProcessCommand('SendBadResponse', 'DisplayNewReviewPage', true); break;
        
      case 'notifications':
        ProcessCommand('DisplayNotifications', 'ProcessNotifications', true); break;

      case 'resetfilter':
        ProcessCommand('SendBadResponse', 'ResetFilters', true);
        
      case 'reset':
        ProcessCommand('CheckReset'); break;
        
      case 'resetpass':
        ProcessCommand('DisplayResetPass', 'ProcessResetPass'); break;
        
      case 'reviews':
        ProcessCommand('SendBadResponse', 'GetReviews', true); break;
        
      case 'savedeal':
        ProcessCommand('SendBadResponse', 'ProcessSaveDeal', true); break;

      case 'savereview':
        ProcessCommand('SendBadResponse', 'ProcessNewReview', true); break;
          
      case 'shaddap':
        ProcessCommand('CheckUnsubscribe'); break;
      
      case 'share':
        ProcessCommand('SendBadResponse', 'DisplayShareOptions'); break;

      case 'signout':
        ProcessCommand('SignOut'); break;
        
      case 'sorts':
        ProcessCommand('GetSorts', 'SetSorts', true); break;
        
      case 'sources':
        ProcessCommand('GetSources', 'SetSources', true, UserCanEditSources); break;

      case 'strings':
        ProcessCommand('GetStrings', 'SetStrings', true, UserCanEditStrings); break;
      
      case 'tags':
        ProcessCommand('GetTags', 'SetTags', true, UserCanEditTags); break;

      case 'terms':
        ProcessCommand('GetTerms'); break;
        
      case 'timezones':
        ProcessCommand('SendBadResponse', 'SetTimezones', true, UserCanEditDivisions); break;    

      case 'types':
        ProcessCommand('GetTypes', 'SetTypes', true, UserCanEditTags | UserCanRunUpdate); break;
      
      case 'update':
        ProcessCommand('WebUpDls', null, true, UserCanRunUpdate); break;
        
      case 'verify':
        ProcessCommand('CheckVerification'); break;
        
      case 'warn':
        ProcessCommand('SendBadResponse', 'DisplayWarning'); break;
      
      default:
        SendBadResponse();
        
    }
    
  }
  
  CloseDB();
  
  
?>
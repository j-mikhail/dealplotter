<?php

  function GetWebData($URL, $Parameters = null, $Opts = null, $Timeout = 30) {
  
    $CookieFile = dirname(__FILE__).'/Cookie.txt';
    $CurlSession = curl_init();

    curl_setopt($CurlSession, CURLOPT_URL, $URL);
    curl_setopt($CurlSession, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($CurlSession, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($CurlSession, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($CurlSession, CURLOPT_COOKIEFILE, $CookieFile);
    curl_setopt($CurlSession, CURLOPT_COOKIEJAR, $CookieFile);
    curl_setopt($CurlSession, CURLOPT_TIMEOUT, $Timeout);
    curl_setopt($CurlSession, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9.1.2) Gecko/20090729 Firefox/3.5.2 GTB5');
    
    if (!is_null($Opts)) curl_setopt($CurlSession, CURLOPT_COOKIE, $Opts);
     
    
    if ($Parameters != null) {
    
      $ParameterString = '';
      foreach ($Parameters as $Key => $Value) {
        $ParameterString .= $Key.'='.urlencode($Value).'&';
      } 
      rtrim($ParameterString,'&');
    
      curl_setopt($CurlSession, CURLOPT_POST, true);
      curl_setopt($CurlSession, CURLOPT_POSTFIELDS, $ParameterString);
      
    
    }
    
    //curl_setopt($CurlSession, CURLOPT_MUTE, true);

    if (!$Data = curl_exec($CurlSession)) {
      trigger_error(curl_error($CurlSession));
      curl_close($CurlSession);
      if (file_exists($CookieFile)) unlink($CookieFile);
      return array(false, null); 
    } else {
      $URL = curl_getinfo($CurlSession, CURLINFO_EFFECTIVE_URL); 
      curl_close($CurlSession);
      if (file_exists($CookieFile)) unlink($CookieFile);
      return array($Data, $URL);
    }
  
  }
  
?>
<?php

  date_default_timezone_set('America/New_York');
  putenv("TZ=America/New_York");
  ini_set('error_reporting', E_ALL); 
  ini_set('date.timezone', 'America/New_York');

  echo time();
  echo '<BR />';
  echo date('YmdHis', time());
  echo '<BR />';
  echo (time() + 139941);
  echo '<BR />';
  echo date('YmdHis', time()+ 139941);
  
?>
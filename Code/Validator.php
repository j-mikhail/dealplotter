<?php

  define("TypeGET", 0);
  define("TypePOST", 1);
  
  define("MustNotExist", -1);
  define("CanExist", 0);
  define("MustExist", 1);
  
  define("NoValidation", 0);
  define("ValidateRange", 1);
  define("ValidateQuery", 2);
  define("ValidateString", 3);
  define("ValidateLength", 4);

  function ValidateForm($InArray) {
  /*/////////////////////////////////////////////////////////////
       Author: Plottery Corp.
      Created: v1.0.0 - 2009-07-30
    Revisions: None
      Purpose: Validates a webform based on passed parameters
      Returns: (Boolean) Validation success, (String) Return message(s)
      
   Parameters: An array of sub-arrays, defining the validation.
               Each sub-array is structured as follows:
                Element 0 (Type):
                  0: GET variable
                  1: POST variable
                Element 1 (Name):
                  Name of variable
                Element 2 (Enforcement):
                 -1: Must not exist
                  0: Can exist
                  1: Must exist
                Element 3 (Validation):
                  0: No validation
                  1: Must exist between inclusive range (numeric only)
                  2: Must exist as result of DB query
                  3: Must not be blank (string only)
                  4: Must be at least X characters long (string only)
                Element 4 (Comparison):
                  Either a numeric value representing the lower limit of a range (ValidateRange),
                   or a number representing the minimum string length (ValidateLength),
                   or a string representing the query to run (ValidateQuery),
                   or null
                Element 5 (Comparison):
                  Either a numeric value representing the upper limit of a range (ValidateLength),
                   or null
                Element 6 (Error):
                  Numeric value representing an error that should be returned
  *//////////////////////////////////////////////////////////////  

    //Check if parameter is an array
    if (is_null($InArray) || !is_array($InArray)) { BadValidation('E1017'); }
    
    $ErrorRaised = false;
    $Errors = array();
    
    //Loop through sub-arrays
    foreach ($InArray as $Entry) {
      
      //Check if sub-array is structured correctly
      if (count($Entry) != 7) { BadValidation('E1018'); }
      if (!is_numeric($Entry[6])) { BadValidation('E1019'); }
      
      //Check if requested variable exists
      switch ($Entry[0]) {
          case TypeGET: $Exists = isset($_GET[$Entry[1]]); break;
          case TypePOST: $Exists = isset($_POST[$Entry[1]]); break;
         default: BadValidation('E1020');
      }
      
      if ( ($Entry[2] == -1 && $Exists) || ($Entry[2] == 1 && !$Exists) ) {
        $ErrorRaised = true; $Errors[] = $Entry[6];
      } elseif ($Exists) {
      
        //Read requested variable
        switch ($Entry[0]) {
          case TypeGET: $Value = $_GET[$Entry[1]]; break;
          case TypePOST: $Value = $_POST[$Entry[1]]; break;
        }

        switch ($Entry[3]) {
        
            case NoValidation: break;

            case ValidateRange:
              if (!is_numeric($Value)) {
                $ErrorRaised = true; $Errors[] = $Entry[6];
              } else {
                if (is_null($Entry[4]) && is_null($Entry[5])) { BadValidation('E1021'); }
                if ( (!is_null($Entry[4]) && (float)$Value < (float)$Entry[4]) || (!is_null($Entry[5]) && (float)$Value > (float)$Entry[5]) ) { $ErrorRaised = true; $Errors[] = $Entry[6]; }
              }
              break;

            case ValidateQuery:
              list ($QR, $DR, $T) = QuerySingle( str_replace('%a', $Value, $Entry[4]) );
              if ($QR < 1) { $ErrorRaised = true; $Errors[] = $Entry[6]; }
              break;

            case ValidateString:
              if (trim($Value) == '') { $ErrorRaised = true; $Errors[] = $Entry[6]; }
              break;
              
            case ValidateLength:
              if (strlen(trim($Value)) < (int)$Entry[4]) { $ErrorRaised = true; $Errors[] = $Entry[6]; }
              break;

          default: BadValidation('E1021');

        }

      }
    }
    
    return array(!$ErrorRaised, $Errors);
  
  }
  
  function BadValidation($ErrNum) {
  
     $BT = debug_backtrace();
     GlobalFail($ErrNum.' - Validation parameters are invalid.<BR>Calling function: '.$BT[2]['function']);
  
  }
  
  function Pacify($String, $Strip = false) {
  /*/////////////////////////////////////////////////////////////
       Author: Plottery Corp.
      Created: v1.0.0 - 2009-08-04
    Revisions: None
      Purpose: Cleans up a string to prevent injection/corruption
      Returns: Secured string
  *//////////////////////////////////////////////////////////////
  
    if(function_exists("mysql_real_escape_string")) {
      if ($Strip) return trim(mysql_real_escape_string(stripslashes($String)));
      return trim(mysql_real_escape_string($String));
    } else {
      GlobalFail('E1022 - Critical security libraries missing.');
    }
  
  }
  
  function Fix($InValue) {
    
    $OutVal = $InValue;
    $OutVal = str_replace(chr(146), chr(39), $OutVal);
    $OutVal = str_replace(chr(145), chr(39), $OutVal);
    $OutVal = str_replace('&#8217;', chr(39), $OutVal);
    $OutVal = str_replace('&#8211;', '-', $OutVal);
    $OutVal = str_replace('&#339;', 'oe', $OutVal);
    $OutVal = str_replace('"','""', $OutVal);
    
    return $OutVal;
  }
  
?>
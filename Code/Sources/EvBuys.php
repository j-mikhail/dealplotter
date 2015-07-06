<?php

  class EvBuys {

    public function GetNewDivisions($SID) {
    
      global $DebugMode;

      if (!CheckURL('EverybodyBuys', $SID, 'XML', 'XML')) return false;
      return UpStatus('EverybodyBuys', $SID);
        
    }
    
    public function GetDivisionData($SRID, $URL, $LID, $TZ, $Sub = false, $WebD = false) {
    
      global $DebugMode;
      
      list ($Data, $DURL) = GetWebData('http://www.everybodybuys.com/deals.xml', null, null, 180);
      if ($Data === false) return array(SysLogIt('Error getting XML data.', StatusError), null);
        
      if ($DebugMode) file_put_contents("EverybodyBuys.txt", $Data);
      
      //Convert XML to string and then decode into array
      $XMLArray = json_decode(json_encode((array) simplexml_load_string($Data)), true);
      
      if (array_key_exists("item", $XMLArray) === false) return array(SysLogIt('XML data structure not as anticipated.', StatusError), null);
      
      //Create deals array
      $RData = array();
      
      foreach ($XMLArray["item"] as $ItemEntry) {
      
        

        //Reset flag and reinitialize array
        $Webdeal = false;
        $ADeal = array();
        
        //Save basic data
        $ADeal['DRID'] = $ItemEntry["id"];
        $ADeal['Title'] = $ItemEntry["title"];
        $ADeal['Descr'] = $ItemEntry["description"];
        $ADeal['URL'] = $ItemEntry["url"];
        $ADeal['Country'] = $ItemEntry["market_country"];
      
        $ADeal['Price'] = (double)$ItemEntry["price"];
        $ADeal['Value'] = (double)$ItemEntry["value"];
        $ADeal['Status'] = 0;
        
        $ADeal['SDate'] = strtotime($ItemEntry["starting_time"]);
        $ADeal['EDate'] = strtotime($ItemEntry["ending_time"]);
        
        
        $ADeal['VDate'] = 0; 
        if (array_key_exists("restrictions", $ItemEntry)) {
          if (stripos($ItemEntry["restrictions"], 'Expires') !== false) {
            $VDate = FindSubData($ItemEntry["restrictions"], 'Expires', ' ', '|', false);
            if ($VDate !== false) {
              $VDate = strtotime($VDate);
              if ($VDate !== false) $ADeal['VDate'] = $VDate;
            }
          }
        }
        
        $ADeal['StoreName'] = $ItemEntry["merchant"];
        
        //Ensure URL is present
        if (array_key_exists("merchant_url", $ItemEntry)) {
          $ADeal['Website'] = $ItemEntry["merchant_url"];
        } else {
          $ADeal['Website'] = null;
        }
        
        /*
        //If more than 10 regions, likely web deal
        if (count($ItemEntry["regionAvailability"]) > 10) $Webdeal = true;
        
        //Check if USA is listed in regions, but source is Canada
        foreach ($ItemEntry["regionAvailability"] as $Region) {
          if (($Region == 'USA') && ($ItemEntry["market_country"] == 'Canada')) {
            //Set as webdeal
            $Webdeal = true;
            //Set country to none (available anywhere)
            $ADeal['Country'] = false;
            break;
          }
        }
        */
        
        $Locations = array();
        
        if (count($ItemEntry["locations"]) == 0) {
        
          //No locations, treat as address-less webdeal
          $Locations[] = array(null, -1, -1);
          
        } else {
        
          //Add each address to the locations array
          foreach ($ItemEntry["locations"] as $Location) {
            
            //Piece together address bits
            $Address = '';
            if (array_key_exists("address", $Location)) { if ($Location["address"] != 'N/A') $Address .= $Location["address"].', '; }
            if (array_key_exists("city", $Location)) { if ($Location["city"] != 'N/A') $Address .= $Location["city"].', '; }
            if (array_key_exists("state", $Location)) { if ($Location["state"] != 'N/A') $Address .= $Location["state"].', '; }
            if (array_key_exists("zip", $Location)) { if ($Location["zip"] != 'N/A') $Address .= $Location["zip"].', '; }
            if (array_key_exists("phone", $Location)) { if ($Location["phone"] != 'N/A') $Address .= $Location["phone"].', '; }
            
            //Remove trailing comma and space
            if ($Address == '') {
              $Address = null;
            } else {
              $Address = substr($Address, 0, -2);
            }
            //$Address = str_ireplace(', Array', '', $Address);
            
            if ($Webdeal) {
              //If previously flagged as webdeal, put -1, -1 as coordinates
              $Locations[] = array($Address, -1, -1);
            } elseif (array_key_exists("latitude", $Location) && array_key_exists("longitude", $Location)) {
              //Check if coordinates are listed
              $Locations[] = array($Address, (float)$Location["latitude"], (float)$Location["longitude"]);
            } else {
              //Otherwise, put 0, 0 to trigger geolocation
              $Locations[] = array($Address, 0, 0);
            }
          
          }
          
        }
        
        //Save locations
        $ADeal['Locations'] = $Locations;
        
        //Add deal to array
        $RData[] = $ADeal;
      
      }
      
      return array(true, $RData);

    }
    
  }

?>
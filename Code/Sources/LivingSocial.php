<?php

  class LivingSocial {

    public function GetNewDivisions($SID) {
    
      global $DebugMode;
    
      SysLogIt('Checking for new LivingSocial divisions.');
      if (!list ($Data, $DURL) = GetWebData('http://www.livingsocial.com/')) { return SysLogIt('Error getting web data.', StatusError); }

      SysLogIt('Parsing HTML.');
      if ($DebugMode) file_put_contents("LivingSocialCity.txt", $Data);
      
      //Using script
      /*
      
      if (!$DataArray = FindSubData($Data, '<body', '<script src="', '"', false, 0, false)) { return SysLogIt("Couldn't find script link.", StatusError); }
      if (!list ($Data, $DURL) = GetWebData($DataArray)) { return SysLogIt('Error getting script.', StatusError); }
      
      SysLogIt('Parsing Javascript.');
      
      if (!$DataArray = FindData($Data, 'dls.fte_cities =', ';', 17, 0, false)) { return SysLogIt("Couldn't find script data.", StatusError); }
      
      $DataArray = json_decode($DataArray, true);
      if (!is_null($DataArray)) {
      
        $Output = '';
      
        foreach ($DataArray as $DataItem) {
        
          foreach ($DataItem as $SubDataItem) {
          
            if (!CheckURL('LivingSocial', $SID, $SubDataItem['name'], (int)$SubDataItem['id'], GetCountry($SubDataItem['country']))) return false;
            $Output .= $SubDataItem['id'].': '.$SubDataItem['name'].chr(13).chr(10);
          
          }
        }
        
        if ($DebugMode) file_put_contents("LivingSocialCity.txt", $Output);

      } else {
      
        return SysLogIt('Error parsing data from script.', StatusError);
      
      }

      */    
      
      //Using in-line data
          
      if (!$DataArray = FindSubData($Data, "id='city_picker'", '>', '</select>', false)) { return SysLogIt("Couldn't find city content.", StatusError); }

      //Parse contents of city box
      $DataArray = explode('<option', $DataArray);
      
      foreach ($DataArray as $DataItem) {
      
        $DataItem = trim(str_ireplace(' selected=selected', '', $DataItem));
        
        if (stripos($DataItem, 'data-lang=') !== false) {
      
          $ID = FindData($DataItem, 'value="', '"', 7, 0, true);
          $Name = FindSubData($DataItem, 'value="', '>', '<', false);
          
          if ($Name !== false && $ID !== false) {
            if ($Name != 'test') {
                    
              if (!CheckURL('LivingSocial', $SID, $Name, (int)$ID)) return false;
              
            }
          }
          
        }
      
      }
      
      return UpStatus('LivingSocial', $SID);  
        
    }
    
    public function GetDivisionData($SRID, $URL, $LID, $TZ, $Sub = false, $WebD = false) {
    
      global $DebugMode;

      list ($Data, $DURL) = GetWebData('http://www.livingsocial.com/cities/'.$URL, null, 'seen-roadblock=1');
      if ($Data === false) return array(SysLogIt('Error getting web data.', StatusError), null);
      
      if ($DebugMode) file_put_contents("LivingSocial.txt", "http://www.livingsocial.com/cities/".$URL.chr(13).chr(10).chr(13).chr(10).$Data);
      
      if (stripos($Data, 'More Cities - LivingSocial') !== false) return array(true, false);
      
      $NewDeal = array (
        "ID" => array(0, 'og:url', '/deals/', '-', true),
        "Title" => array('', 'class="deal-title"', '<p>', '</p>', false),
        "Website" => array('', 'id="sfwt_full_1"', 'href="', '"', false),
        "PriceSale" => array('', 'class="deal-price', '</span>', '</div>', false),
        "PercOff" => array(0, 'id="percentage"', '>', '%', true),
        "StoreName" => array('', 'class="deal-title"', '<h1>', '</h1>', false),
        "Description" => array('', 'id="sfwt_full_1"', '<p>', '</p>', false),
        "Address" => array('', '<span class="street_1"', '>', '|', false, true),
        "Phone" => array('', 'class="phone"', '>', '<', false, true)
      );
      
      if (!GetNewDealData($Data, $NewDeal)) return array(SysLogIt('Error finding key deal information.', StatusError), null);
      
      $StartDate = time();
      
      $VExpiry = FindData($Data, 'expires on ', '</p>', 11, 3, false, 0, false);
      $VExpiry = ($VExpiry === false)? 0:strtotime($VExpiry);
      if ($VExpiry === false) $VExpiry = 0;
      
      $Expiry = FindData($Data, '<div id="countdown">', '</div>', 20, 0, false, 0, false);
      if ($Expiry === false) {
        SysLogIt('No countdown timer found. Switching to secondary detection.');
        $Days = FindSubData($Data, '<li class="last">', '<div class="value">', 'day', true);
        if ($Days === false) $Days = 0;
        $EndDate = mktime((5 - ($TZ + 5)), 0, 0, date('n'), (date('j') + ((date('H') > (5 - ($TZ + 5)))?1:0) + $Days), date('Y'));
      } else {
        $ExpParts = explode('<span class="colon">:</span>', $Expiry);
        foreach ($ExpParts as &$ExpPart) {
          $ExpPart = FindData($ExpPart, '<span class="num">', '</span>', 18, 0, true);
        }
        if ($ExpParts[0] === false || $ExpParts[1] === false || $ExpParts[2] === false) {
          $EndDate = mktime((5 - ($TZ + 5)), 0, 0, date('n'), (date('j') + (date('H') > (5 - ($TZ + 5)))?1:0), date('Y'));  
        } else {
          $EndDate = mktime(date('H')+$ExpParts[0], date('i')+$ExpParts[1], date('s')+$ExpParts[2], date('n'), date('j'), date('Y'));
        }
      }
      
      $Address = null;
      if ($NewDeal['Address'][0] !== false) $Address = CleanHTML($NewDeal['Address'][0]);
      //if ($NewDeal['Phone'][0] !== false) $Address .= $NewDeal['Phone'][0];
      if ($Address == '') $Address = null;
          
      $Coords = FindData($Data, 'maps?q=', '"', 7, 0, false, 0, false);
      if ($Coords !== false) {
        $Coords = explode(',', $Coords);
        if (($Coords === false) || (count($Coords) != 2)) $Coords = array(0, 0);
      } else {
        $Coords = array(0, 0);
      }
      
      $RData = array();
      
      $RData['DRID'] = $NewDeal['ID'][0];
      $RData['Title'] = $NewDeal['Title'][0].' - '.$NewDeal['StoreName'][0];
      $RData['Descr'] = $NewDeal['Description'][0];
      $RData['URL'] = 'http://www.livingsocial.com/deals/'.$NewDeal['ID'][0];
      $RData['Price'] = (double)$NewDeal['PriceSale'][0];
      $RData['Value'] = (double)($NewDeal['PriceSale'][0]/(1 - ($NewDeal['PercOff'][0] / 100)));
      $RData['Status'] = 0;
      
      $RData['SDate'] = $StartDate;
      $RData['EDate'] = $EndDate;
      $RData['VDate'] = $VExpiry;
      
      $RData['StoreName'] = $NewDeal['StoreName'][0];
      $RData['Website'] = $NewDeal['Website'][0];
      $RData['Locations'] = array(array($Address, $Coords[0], $Coords[1]));
      
      return array(true, array($RData));
      
    }
    
  }

?>
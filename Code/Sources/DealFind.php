<?php

  class DealFind {

    public function GetNewDivisions($SID) {
    
      global $DebugMode;
    
      SysLogIt('Checking for new DealFind divisions.');
      if (!list ($Data, $DURL) = GetWebData('http://www.dealfind.com/')) { return SysLogIt('Error getting web data.', StatusError); }
      
      if ($DebugMode) file_put_contents("DealFindCity.txt", $Data);
      
      SysLogIt('Parsing HTML.');
      
      if (!$DataArray = FindData($Data, ' - Select your city - </option>', '</select>', 31, 0, false)) { return SysLogIt("Couldn't find city content.", StatusError); }

      //Parse contents of city box
      $DataArray = explode('</option>', $DataArray);
      
      foreach ($DataArray as $DataItem) {
      
        $DataItem = trim(str_ireplace(' selected="selected"', '', $DataItem));
        
        if (stripos($DataItem, '<option value="') !== false) {
      
          $ID = FindData($DataItem, '<option value="', '">', 15, 0, false);
          
          $StartPos = stripos($DataItem, '">');
          if ($StartPos !== false && $ID !== false) {
          
            $Name = substr($DataItem, $StartPos+2);
            if ($Name != 'test') {
            
              if (!CheckURL('DealFind', $SID, $Name, $ID)) return false;
              
            }
            
          }
          
        }
      
      }
      
      return UpStatus('DealFind', $SID);
        
    }
    
    public function GetDivisionData($SRID, $URL, $LID, $TZ, $Sub = false, $WebD = false) {
    
      global $DebugMode;
      
      list ($Data, $DURL) = GetWebData('http://www.dealfind.com/'.$URL);
      if ($Data === false) return array(SysLogIt('Error getting web data.', StatusError), null);
        
      if ($DebugMode) file_put_contents("DealFind.txt", $Data);
    
      if (stripos($Data, 'insanely monstrous') !== false) return array(true, false);

      $NewDeal = array (
        "ID" => array(0, 'var DealID', '=', ';', true),
        "Title" => array('', 'var DealName', '"', '"', false),
        "URL" => array('', 'var AffiliateLinkURL', '"', '"', false),
        "PriceReg" => array(0, 'var RegularPriceHTML', "'$", "'", true),
        "PriceSale" => array(0, 'var OurPriceHTML', "'$", "'", true),
        "DealTime" => array(0, 'DealSeconds_Total', '=', ';', true),
        "DealElapsed" => array(0, 'DealSeconds_Elapsed', '=', ';', true),
        "DealStart" => array('', 'The deal went live', 'on', '<', false),
        "StoreName" => array('', 'itemprop="name"', '>', '<', false),
        "Description" => array('', 'class="dealText"', '>', '<div', false),
        "Website" => array('', 'itemprop="url"', 'href="', '"', false, true),
        "Address" => array('', 'itemprop="name"', 'itemprop="description">', chr(9).'</div>', false, true),
        "Coords" => array('', 'point = new GLatLng', '(', ')', false, true)
      );
      
      if (!GetNewDealData($Data, $NewDeal)) return array(SysLogIt('Error finding key deal information.', StatusError), null);
      
      $StartDate = time() - $NewDeal['DealElapsed'][0];
      $EndDate = $StartDate + $NewDeal['DealTime'][0];
      //Time measured in seconds, so no need for timezone adjustments
            
      $VExpiry = FindData($Data, 'Expires on', '.', 11, 0, false, 0, false);
      $VExpiry = ($VExpiry === false)? 0:strtotime($VExpiry);
      if ($VExpiry === false) $VExpiry = 0;
      
      $Coords = array(0, 0);
      if ($NewDeal['Coords'][0] !== false) $Coords = explode(',', $NewDeal['Coords'][0]);
      if (($Coords === false) || (count($Coords) != 2)) $Coords = array(0, 0);
      
      $Address = null;
      if ($NewDeal['Address'][0] !== false) $Address = CleanHTML($NewDeal['Address'][0]);
      if ($Address == '') $Address = null;      
      if (stripos($Address, 'map it') !== false) $Address = trim(substr($Address, 0, stripos($Address, 'map it')));
      if (stripos($Address, 'n/a') !== false) $Address = trim(substr($Address, 0, stripos($Address, 'n/a')));
      if ((stripos($Address, 'redeem online') !== false) || (stripos($Address, 'redeem by phone') !== false) || (stripos($Address, 'mobile service') !== false)) {
        $Address = null;
        $Coords = array(-1, -1);
      }      
            
      //$Website = FindData($NewDeal['MoreData'][0], 'a href="', '"', 8, 0, false, 0, false);
      //if ($Website === false) $Website = '';
      $Website = null;
      if ($NewDeal['Website'][0] !== false) $Website = $NewDeal['Website'][0];
      if (trim($Website) == '') $Website = null;
      
      $MoreDeals = array();
      $Start = stripos($Data, 'class="side-deal');
      while ($Start !== false) {
        $ID = FindData($Data, 'href="', '"', 6, 0, false, $Start, false); 
        if ($ID !== false) $MoreDeals[] = $ID;
        $Start = stripos($Data, 'class="side-deal', $Start + 20);
      }      
      
      /*
      //Parse address
      $Address = $NewDeal['MoreData'][0];
      $Address = str_ireplace('<br/>', ', ', $Address);
      $Address = str_ireplace('website', '', $Address);
      $Address = str_ireplace('map it', '', $Address);
      $Address = substr(trim(CleanHTML($Address)), 2);
      */
      
      /*
      $AddressArray = explode(chr(13).chr(10), $NewDeal['MoreData'][0]);
      foreach ($AddressArray as $AddressItem) {
        if (stripos($AddressItem, '                ') !== false && stripos($AddressItem, '                  ') === false) {
          $Address = trim(substr($AddressItem, 0, stripos($AddressItem, '<br><a')));
          $Address = CleanHTML($Address);
          break;
        }
      }
      */
      
      $RData = array();
      
      $RData['DRID'] = (int)$NewDeal['ID'][0];
      $RData['Title'] = $NewDeal['Title'][0];
      $RData['Descr'] = $NewDeal['Description'][0];
      $RData['URL'] = $NewDeal['URL'][0];
      $RData['Price'] = (double)$NewDeal['PriceSale'][0];
      $RData['Value'] = (double)$NewDeal['PriceReg'][0];
      $RData['Status'] = 0;
      
      $RData['SDate'] = $StartDate;
      $RData['EDate'] = $EndDate;
      $RData['VDate'] = $VExpiry;
      
      $RData['StoreName'] = $NewDeal['StoreName'][0];
      $RData['Website'] = $Website;
      $RData['Locations'] = array(array($Address, $Coords[0], $Coords[1]));
      
      if (count($MoreDeals) > 0) $RData['Deals'] = $MoreDeals;
      
      return array(true, array($RData));  
      
    }
    
  }

?>
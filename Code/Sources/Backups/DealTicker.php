<?php

  class DealTicker {

    public function GetNewDivisions($SID) {
    
      global $DebugMode;
    
      SysLogIt('Checking for new DealTicker divisions.');
      if (!list ($Data, $DURL) = GetWebData('http://www.dealticker.com/toronto_en_1categ.html')) { return SysLogIt('Error getting web data.', StatusError); }
      
      if ($DebugMode) file_put_contents("DealTickerCity.txt", $Data);
      
      SysLogIt('Parsing HTML.');

      $DataArray = FindSubData($Data, 'class="city_list"', '>', '</table>', false);
      if ($DataArray === false) return SysLogIt("Couldn't find city content.", StatusError);

      //Parse contents of city box
      $DataArray = explode('</td>', $DataArray);
      
      foreach ($DataArray as $DataItem) {
      
        if (stripos($DataItem, 'href=') !== false) {
      
          $ID = FindSubData($DataItem, 'a href=', 'dealticker.com', '"', false);
          $Name = FindSubData($DataItem, 'a href=', '>', '<', false);
          
          if ($Name !== false && $ID !== false) {
          
            if (stripos($Name, '&nbsp;') !== false) $Name = substr($Name, 0, stripos($Name, '&nbsp;'));
            
            if (!CheckURL('DealTicker', $SID, $Name, $ID)) return false;
              
          }
          
        }
      
      }  
      
      return UpStatus('DealTicker', $SID);
      
        
    }
    
    public function GetDivisionData($SRID, $URL, $LID, $TZ, $Sub = false, $WebD = false) {
    
      global $DebugMode;
      
      list ($Data, $DURL) = GetWebData('http://www.dealticker.com'.$URL);
      if ($Data === false) return array(SysLogIt('Error getting web data.', StatusError), null);
      
      if ($DebugMode) file_put_contents("DealTicker.txt", 'http://www.dealticker.com'.$URL.chr(13).chr(10).chr(13).chr(10).$Data);
      
      if ((stripos($Data, 'buy-tile.gif') === false) || (stripos($Data, 'We will be coming to your city soon') !== false) || (stripos($Data, 'No Longer Available') !== false)) return array(true, false);
      
      $NewDeal = array (
        "ID" => array(0, 'product_id:', '"', '"', true),
        "Title" => array('', 'meta name="description"', 'content="', '"', false),
        "Website" => array('', '/images/globe.gif', 'href="', '"', false, true),
        "PriceSale" => array(0, 'buy-tile.gif', '$', '<', true),
        "PriceReg" => array(0, '>Value<', '$', '<', true),
        "StoreName" => array('', 'Featured Business', '<b>', '</b>', false),
        "Description" => array('', '>Description<', '<tr>', '</tr>', false),
        "Address" => array('', 'Featured Business', '</tr><tr>', '<div', false, true),
        "EndD" => array(0, '"r_dd"', '>', '<', true, true),
        "EndH" => array(0, '"r_hh"', '>', '<', true, true),
        "EndM" => array(0, '"r_mm"', '>', '<', true, true),
        "EndS" => array(0, '"r_ss"', '>', '<', true, true)
      );
      
      if (!GetNewDealData($Data, $NewDeal)) return array(SysLogIt('Error finding key deal information.', StatusError), null);
      
      $StartDate = time();
      $EndDate = time();
      if ($NewDeal['EndD'][0] !== false) $EndDate += ((int)$NewDeal['EndD'][0] * 24 * 60 * 60);
      if ($NewDeal['EndH'][0] !== false) $EndDate += ((int)$NewDeal['EndH'][0] * 60 * 60);
      if ($NewDeal['EndM'][0] !== false) $EndDate += ((int)$NewDeal['EndM'][0] * 60);
      if ($NewDeal['EndS'][0] !== false) $EndDate += ((int)$NewDeal['EndS'][0]);
      if ($EndDate <= time()) return array(SysLogIt('Could not determine deal end date.', StatusError), null);
      
      $VExpiry = FindData($Data, 'Expiry date:', ')', 12, 0, false, 0, false);
      $VExpiry = ($VExpiry === false)? 0:strtotime($VExpiry);
      if ($VExpiry === false) $VExpiry = 0;
      
      $Address = array();
      if ($NewDeal['Address'][0] !== false) {
      
        if (stripos($NewDeal['Address'][0], 'located in') === false) {
        
          $Address = $NewDeal['Address'][0];
          $Address = trim(str_ireplace('<td>', ' ', $Address));
          $Address = trim(str_ireplace('</td>', ' ', $Address));
          $Address = trim(str_ireplace('<tr>', ' ', $Address));
          $Address = trim(str_ireplace('</tr>', ' ', $Address));
          $Address = trim(str_ireplace('&nbsp;', ' ', $Address));
          $Address = trim(str_ireplace('<br>', ' ', $Address));
          $Address = trim(str_ireplace(chr(9), ' ', $Address));
          $Address = trim(str_ireplace(chr(10), ' ', $Address));
          $Address = trim(str_ireplace(chr(13), ' ', $Address));
          while (stripos($Address, '  ')) {
            $Address = trim(str_ireplace('  ', ' ', $Address));
          }
          
          if (stripos($Address, '</span>') === false) {
            $Address = array($Address);
          } else {
          
            $Lines = explode('</span>', $Address);
            $Address = array();
            
            foreach ($Lines as &$Line) {
            
              if (stripos($Line, 'locations') === false) {
            
                if (substr($Line, 0, 5) == '<span') {
                  $Line = trim(substr($Line, stripos($Line, '>') + 1));
                } elseif (stripos($Line, '<span') !== false) {
                  $Line = trim(substr($Line, 0, stripos($Line, '<span')));
                }
                
                if (is_numeric(substr($Line, 0, stripos($Line, ' '))) && (strlen($Line) >= 25)) $Address[] = $Line;
                
              }
              
            }
            
          }
          
        }
        
      }
      
      $Locations = array();
      
      if (count($Address) > 0) {
        foreach ($Address as $Adr) {
          $Locations[] = array(CleanHTML($Adr), 0, 0);
        }
      } else {
        $Locations[] = array(null, 0, 0);
      }
       
      $MoreDeals = array();
      $Start = stripos($Data, 'Active DealTicker');
      while ($Start !== false) {
        $MoreDeals[] = '/product.php/product_id/'.FindData($Data, '/product.php/product_id/', "'", 24, 0, true, $Start, false);
        $Start = stripos($Data, 'Active DealTicker', $Start + 20);
      }
      
      $RData = array();
      
      $RData['DRID'] = '/product.php/product_id/'.$NewDeal['ID'][0];
      $RData['Title'] = $NewDeal['Title'][0];
      $RData['Descr'] = $NewDeal['Description'][0];
      $RData['URL'] = 'http://www.dealticker.com/product.php/product_id/'.$NewDeal['ID'][0];
      //$RDate['URL'] = 'http://www.DealTicker.com'.$URL.'/users/refer/4458/499/5fa1c87e50f3bef60631853dec007bc1';
      $RData['Price'] = (double)$NewDeal['PriceSale'][0];
      $RData['Value'] = (double)$NewDeal['PriceReg'][0];
      $RData['Status'] = 0;
      
      $RData['SDate'] = $StartDate;
      $RData['EDate'] = $EndDate;
      $RData['VDate'] = $VExpiry;
      
      $RData['StoreName'] = $NewDeal['StoreName'][0];
      $RData['Website'] = $NewDeal['Website'][0];
      $RData['Locations'] = $Locations;
      
      if (count($MoreDeals) > 0) $RData['Deals'] = $MoreDeals;
      
      return array(true, $RData);
      
    }
    
  }

?>
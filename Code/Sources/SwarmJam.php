<?php

  class SwarmJam {

    public function GetNewDivisions($SID) {
    
      global $DebugMode;
    
      SysLogIt('Checking for new SwarmJam divisions.');
      if (!list ($Data, $DURL) = GetWebData('http://www.swarmjam.com')) { return SysLogIt('Error getting web data.', StatusError); }
      
      if ($DebugMode) file_put_contents("SwarmJamCity.txt", $Data);
      
      SysLogIt('Parsing HTML.');
      
      $DataArray = FindSubData($Data, 'id="subscriptionselector"', '>', '</select>', false);
      if ($DataArray === false) return SysLogIt("Couldn't find city content.", StatusError);
      
      $DataArray = explode('/option>', $DataArray);
      
      foreach ($DataArray as $DataItem) {
      
        if (stripos($DataItem, 'value="') !== false) {
      
          $ID = FindData($DataItem, 'value="', '"', 7, 0, true);
          $Name = FindSubData($DataItem, 'value="', '>', '<', false);
          //$Name = CleanHTML($Name);
          
          if (($Name !== false) && ($ID !== false)) {
          
            if (!CheckURL('SwarmJam', $SID, $Name, $ID)) return false;
              
          }
          
        }
      
      }
      
      return UpStatus('SwarmJam', $SID);
        
    }
    
    public function GetDivisionData($SRID, $URL, $LID, $TZ, $Sub = false, $WebD = false) {
    
      global $DebugMode;
      
      if ($Sub) {
        list ($Data, $DURL) = GetWebData('http://www.swarmjam.com/waf.srv/sj/sj/cn/auction_ActionProdDet?ONSUCCESS=swarmofferdet.jsp&PRODID='.$URL);
      } else {
        list ($Data, $DURL) = GetWebData('http://www.swarmjam.com/waf.srv/sj/sj/cn/auction_ActionProdCat?ONSUCCESS=home.jsp&ONERR1=home.jsp&ONERR2=home.jsp&CATID='.$URL.'#');
      }      
      
      if ($Data === false) return array(SysLogIt('Error getting web data.', StatusError), null);
      if ($DebugMode) file_put_contents("SwarmJam.txt", $Data);
      
      if (stripos($Data, 'href="https://') === false) return array(true, false);    
    
      $NewDeal = array (
        "ID" => array(0, 'href="https://', '&PRODID=', '"', true),
        "Title" => array('', 'class="offerTitle"', '>', '</div>', false),
        "VendorID" => array(0, 'venddet.jsp', '&ONERR2=venddet.jsp&VENDID=', '"', true),
        "Website" => array('', '<p><a href="http:', '//', '"', false, true),
        "PriceSale" => array(0, '>Buy', '$', '<', true),
        "PriceReg" => array(0, '>Value:', '$', '<', true, true),
        "Savings" => array(0, '>Buy', '(', '%', true, true),
        "Description" => array('', 'More About This Deal', '<p>', '</p>', false),
        "AdrA" => array('', chr(9).'v_address =', '"', '"', false, true),
        "AdrC" => array('', chr(9).'v_city =', '"', '"', false, true),
        "AdrS" => array('', chr(9).'v_state =', '"', '"', false, true),
        "AdrP" => array('', chr(9).'v_state =', '</script>', '</p>', false, true),
        "EndD" => array(0, 'id="cx-cnt-int-day"', '>', '<', true, true),
        "EndH" => array(0, 'id="cx-cnt-int-hou"', '>', '<', true, true),
        "EndM" => array(0, 'id="cx-cnt-int-min"', '>', '<', true, true),
        "EndS" => array(0, 'id="cx-cnt-int-sec"', '>', '<', true, true),
        "Expiry" => array('', 'Expiry Date:', 'document.write("', '")', false, true)
      );
                        
      if (!GetNewDealData($Data, $NewDeal)) return array(SysLogIt('Error finding key deal information.', StatusError), null);
      
      list ($SubData, $DURL) = GetWebData('http://www.swarmjam.com/waf.srv/sj/sj/cn/auction_ActionProdVend?ONSUCCESS=venddet.jsp&ONERR2=venddet.jsp&VENDID='.(int)$NewDeal['VendorID'][0]);
      if ($SubData === false) return array(SysLogIt('Error retrieving vendor information.', StatusError), null);
      $StoreName = FindSubData($SubData, 'id="ctyx-content"', 'About ', '<');
      if ($StoreName === false) return array(SysLogIt('Error finding vendor information.', StatusError), null);
      
      if ($NewDeal['PriceReg'][0] !== false) {
        $PriceReg = (double)$NewDeal['PriceReg'][0];
      } elseif ($NewDeal['Savings'][0] !== false) {
        $PriceReg = ($NewDeal['PriceSale'][0] / ((100 - $NewDeal['Savings'][0]) / 100));
      } else {
        return array(SysLogIt('Error determining regular price.', StatusError), null);
      }
    
      $StartDate = time();
      $EndDate = time();
      if ($NewDeal['EndD'][0] !== false) $EndDate += ((int)$NewDeal['EndD'][0] * 24 * 60 * 60);
      if ($NewDeal['EndH'][0] !== false) $EndDate += ((int)$NewDeal['EndH'][0] * 60 * 60);
      if ($NewDeal['EndM'][0] !== false) $EndDate += ((int)$NewDeal['EndM'][0] * 60);
      if ($NewDeal['EndS'][0] !== false) $EndDate += ((int)$NewDeal['EndS'][0]);        
      if ($EndDate <= time()) return array(SysLogIt('Could not determine deal end date.', StatusError), null);
              
      $VExpiry = false;
      if ($NewDeal['Expiry'][0] !== false) $VExpiry = strtotime(str_ireplace('at', '', $NewDeal['Expiry'][0]));
      if ($VExpiry === false) $VExpiry = 0;
              
      $Coords = array(0, 0);
      
      $Locations = array();
      $Address = null;
      
      $LocURL = FindSubData($Data, 'Participating Locations', '>', 'Click to view locations', false);
      if ($LocURL !== false) {
      
        $LocURL = FindSubData($LocURL, 'href=', '"', '"', false);
        if ($LocURL !== false) {
          list ($SubData, $DURL) = GetWebData($LocURL);
          if ($SubData !== false) {

            $Start = stripos($SubData, 'class="location"');
            while ($Start !== false) {
            
              $NewAdr = FindData($SubData, chr(9).'v_address = "', '"', 14, 0, false, $Start, false);
              $NewCty = FindData($SubData, chr(9).'v_city = "', '"', 11, 0, false, $Start, false);
              $NewPro = FindData($SubData, chr(9).'v_state = "', '"', 12, 0, false, $Start, false);
              
              $Address = '';
              
              if ($NewAdr !== false) $Address .= $NewAdr.', ';
              if ($NewCty !== false) $Address .= $NewCty.', ';
              if ($NewPro !== false) $Address .= $NewPro.', ';
              $Address = rtrim(trim($Address), ',');
              $Address = CleanHTML($Address);
              
              if ($Address !== '') $Locations[] = array($Address, 0, 0);
              
              $Start = stripos($SubData, 'class="location"', $Start + 20);        
              
            }
          
          }
        }
        
      } else {
      
        $Address = '';
        if ($NewDeal['AdrA'][0] !== false) {
          $Address .= $NewDeal['AdrA'][0].', ';
          if ($NewDeal['AdrA'][0] == 'Online Vendor') $Coords = array(-1,-1);
        }
        if ($NewDeal['AdrC'][0] !== false) $Address .= $NewDeal['AdrC'][0].', ';
        if ($NewDeal['AdrS'][0] !== false) $Address .= $NewDeal['AdrS'][0].', ';
        if ($NewDeal['AdrP'][0] !== false) $Address .= $NewDeal['AdrP'][0].', ';
        $Address = rtrim(trim($Address), ',');
        $Address = CleanHTML($Address);
        if ($Address == '') $Address = null;
        
      }
      
      if (count($Locations) == 0) $Locations[] = array($Address, $Coords[0], $Coords[1]);
      
      $Website = null;
      if ($NewDeal['Website'][0] !== false) $Website = 'http://'.$NewDeal['Website'][0];      
      
      $MoreDeals = array();
      $Start = stripos($Data, 'class="side-details"');
      while ($Start !== false) {
        $New = FindData($Data, "&PRODID=", '"', 8, 0, true, $Start, false);
        if (($New !== false) && ($New > 0)) $MoreDeals[] = $New;
        $Start = stripos($Data, 'class="side-details"', $Start + 20);
      }

      $RData = array();
      
      $RData['DRID'] = $NewDeal['ID'][0];
      $RData['Title'] = $NewDeal['Title'][0];
      $RData['Descr'] = $NewDeal['Description'][0];
      $RData['URL'] = 'http://www.swarmjam.com/waf.srv/sj/sj/cn/auction_ActionProdDet?ONSUCCESS=swarmofferdet.jsp&PRODID='.$NewDeal['ID'][0];
      $RData['Price'] = (double)$NewDeal['PriceSale'][0];
      $RData['Value'] = $PriceReg;
      $RData['Status'] = 0;
      
      $RData['SDate'] = $StartDate;
      $RData['EDate'] = $EndDate;
      $RData['VDate'] = $VExpiry;
      
      $RData['StoreName'] = $StoreName;
      $RData['Website'] = $Website;
      $RData['Locations'] = $Locations;
      
      if (count($MoreDeals) > 0) $RData['Deals'] = $MoreDeals;
      
      return array(true, array($RData));
      
    }
    
  }
  

?>
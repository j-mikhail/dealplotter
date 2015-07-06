<?php

  class RedFlagDeals {

    public function GetNewDivisions($SID) {
    
      global $DebugMode;
    
      SysLogIt('Checking for new RedFlagDeals divisions.');
      if (!list ($Data, $DURL) = GetWebData('http://dealoftheday.redflagdeals.com/deal/toronto')) { return SysLogIt('Error getting web data.', StatusError); }
      
      if ($DebugMode) file_put_contents("RedFlagDealsCity.txt", $Data);
      
      SysLogIt('Parsing HTML.');
      
      $DataArray = FindSubData($Data, 'categoryListBoxV2', '>', '</div>'.chr(13).chr(10).chr(9).'</div>', false);
      if ($DataArray === false) return SysLogIt("Couldn't find city content.", StatusError);
      
      $DataArray = explode('</div>', $DataArray);
      
      foreach ($DataArray as $DataItem) {
      
        if (stripos($DataItem, 'categoryNameV2') !== false) {
      
          $ID = FindSubData($DataItem, 'selectCategory(', "'", "'", false);
          $Name = FindSubData($DataItem, 'categoryNameV2', '>', '<', false);
          
          if ($Name !== false && $ID !== false) {
          
            if (!CheckURL('RedFlagDeals', $SID, $Name, $ID)) return false;
              
          }
          
        }
      
      }
      
      return UpStatus('RedFlagDeals', $SID);
        
    }
    
    public function GetDivisionData($SRID, $URL, $LID, $TZ, $Sub = false, $WebD = false) {
    
      global $DebugMode;
      
      
      list ($Data, $DURL) = GetWebData('http://dealoftheday.redflagdeals.com/deal/'.$URL, null, 'dealoftheday.redflagdeals.com_splashIgnored=0');
      if ($Data === false) return array(SysLogIt('Error getting web data.', StatusError), null);
        
      if ($DebugMode) file_put_contents("RedFlagDeals.txt", $Data);
    
      $NewDeal = array (
        "Title" => array('', 'og:title', 'content="', '"', false),
        "URL" => array('', 'data-url=', '"http://dealoftheday.redflagdeals.com', '?', false),
        "StoreName1" => array('', 'merchantAddress_192', '<b>', '</b>', false, true),
        "StoreName2" => array('', 'name="keywords" content=', '"', ',', false, true),
        "Website" => array('', 'merchantAddress_192', 'href="', '"', false, true),
        "PriceSale" => array(0, 'detailsPageDealInfoPrice', '"wrappedPriceValue">', '<', true),
        "PriceReg" => array(0, 'origPriceValue', '$', '<', true),
        "Description" => array('', 'prodDescriptionText', '>', '</div>', false),
        "Address" => array('', 'addresses.push(', '"', '"', false, true),
        "TimeL" => array('', ' DealTimeClass', '(', ')', false),
        "Expiry" => array('', 'expires', ' ', '<', false, true)
      );
                        
      if (!GetNewDealData($Data, $NewDeal)) return array(SysLogIt('Error finding key deal information.', StatusError), null);
    
      $StartDate = time();
      $EndDate = time();
      
      $DateParts = explode(',', $NewDeal['TimeL'][0]);
      if ($DateParts === false) return array(SysLogIt('Could not determine deal end date.', StatusError), null);
      
      if (trim($DateParts[0]) != 'null') $EndDate += ((int)substr(trim($DateParts[0]),1,strlen(trim($DateParts[0]))-2) * 24 * 60 * 60);
      if (trim($DateParts[1]) != 'null') $EndDate += ((int)substr(trim($DateParts[1]),1,strlen(trim($DateParts[1]))-2) * 60 * 60);
      if (trim($DateParts[2]) != 'null') $EndDate += ((int)substr(trim($DateParts[2]),1,strlen(trim($DateParts[2]))-2) * 60);
      if (trim($DateParts[3]) != 'null') $EndDate += ((int)substr(trim($DateParts[3]),1,strlen(trim($DateParts[3]))-2));
      
      if ($EndDate <= time()) return array(SysLogIt('Could not determine deal end date.', StatusError), null);
      
      $StoreName = '';
      if (($StoreName == '') && ($NewDeal['StoreName1'][0] !== false)) $StoreName = $NewDeal['StoreName1'][0];
      if (($StoreName == '') && ($NewDeal['StoreName2'][0] !== false)) $StoreName = $NewDeal['StoreName2'][0];
      if ($StoreName == '') return array(SysLogIt('Could not determine store name.', StatusError), null);
              
      $VExpiry = false;
      if ($NewDeal['Expiry'][0] !== false) {
        $VExpiry = $NewDeal['Expiry'][0];
        if (stripos($VExpiry, '(') !== false) $VExpiry = substr($VExpiry, 0, stripos($VExpiry, '('));
        $VExpiry = strtotime($VExpiry);
      }
      if ($VExpiry === false) $VExpiry = 0;
              
      //Initialize location array
      $Locations = array();
      
      //Look for first instance of addresses.push
      $Start = stripos($Data, 'addresses.push"');
      while ($Start !== false) {
        
        //Get first content between parenthesis immediately after addresses.push
        $New = FindData($Data, '("', '")', 2, 0, false, $Start, false);
        //If found, add to locations array
        if ($New !== false) $Locations[] = array($New, 0, 0);
        
        //Check for next instance, starting at previous location + 20
        $Start = stripos($Data, 'addresses.push', $Start + 20);
      }
      //If no addresses were found, add null address
      if (count($Locations) == 0) $Locations[] = array(null, 0, 0);
      
      $Website = null;
      if ($NewDeal['Website'][0] !== false) $Website = $NewDeal['Website'][0];
      
      $MoreDeals = array();
      
      $Start = stripos($Data, 'id="sideDealsSection"');
      while ($Start !== false) {
      
        $New = FindData($Data, 'http://dealoftheday.redflagdeals.com/deal/', '"', 42, 0, false, $Start, false);
        if ($New !== false) $MoreDeals[] = $New;
        
        $Start = stripos($Data, 'id="sideDealsSection"', $Start + 20);
      }
      
      $RData = array();
      
      $RData['DRID'] = $URL;
      $RData['Title'] = $NewDeal['Title'][0];
      $RData['Descr'] = $NewDeal['Description'][0];
      $RData['URL'] = 'http://dealoftheday.redflagdeals.com'.$NewDeal['URL'][0];
      $RData['Price'] = (double)$NewDeal['PriceSale'][0];
      $RData['Value'] = (double)$NewDeal['PriceReg'][0];
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
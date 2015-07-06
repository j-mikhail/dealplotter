<?php

  class Dealsnoop {

    public function GetNewDivisions($SID) {
    
      global $DebugMode;
    
      SysLogIt('Checking for new Dealsnoop divisions.');
      if (!list ($Data, $DURL) = GetWebData('http://www.dealsnoop.com/')) { return SysLogIt('Error getting web data.', StatusError); }
      
      if ($DebugMode) file_put_contents("DealsnoopCity.txt", $Data);
      
      SysLogIt('Parsing HTML.');

      $DataArray = FindSubData($Data, 'id="city-rollover"', '<ul>', '</div>'.chr(13).chr(10), false);
      if ($DataArray === false) return SysLogIt("Couldn't find city content.", StatusError);

      //Parse contents of city box
      $DataArray = str_ireplace('</ul>', '</li>', $DataArray);
      $DataArray = explode('</li>', $DataArray);
      
      foreach ($DataArray as $DataItem) {
      
        if (stripos($DataItem, 'href=') !== false) {
      
          $ID = FindSubData($DataItem, 'a href=', '"..', '"', false);
          $Name = FindSubData($DataItem, 'a href=', '>', '<', false);
          
          if ($Name !== false && $ID !== false) {
          
            if (!CheckURL('Dealsnoop', $SID, $Name, $ID)) return false;
              
          }
          
        }
      
      }  
      
      return UpStatus('Dealsnoop', $SID);
      
        
    }
    
    public function GetDivisionData($SRID, $URL, $LID, $TZ, $Sub = false, $WebD = false) {
    
      global $DebugMode;
      
      list ($Data, $DURL) = GetWebData('http://www.dealsnoop.com'.$URL);
      if ($Data === false) return array(SysLogIt('Error getting web data.', StatusError), null);
      
      if ($DebugMode) file_put_contents("Dealsnoop.txt", 'http://www.dealsnoop.com'.$URL.chr(13).chr(10).chr(13).chr(10).$Data);
      
      if (stripos($Data, 'coming soon to your city') !== false) return array(true, false);
      
      $NewDeal = array (
        "ID" => array(0, 'id="deal_id"', 'value="', '"', true),
        "Title" => array('', 'og:title', 'content="', '"', false),
        "Website" => array('', 'id="deal-address"', 'href="', '"', false, true),
        "PriceSale" => array(0, 'id="deal-price"', '$', '<', true),
        "PriceReg" => array(0, 'id="treat-deal-value"', '$', '<', true),
        "StoreName" => array('', 'class="caps"', '>', '<', false),
        "Description" => array('', 'id="about-this-deal"', '>', '</p>', false),
        "Address" => array('', 'id="googlemap"', 'value="', '"', false, true),
        "EndY" => array(0, 'id="deal-expyear"', 'value="', '"', true),
        "EndM" => array(0, 'id="deal-expmonth"', 'value="', '"', true),
        "EndD" => array(0, 'id="deal-expday"', 'value="', '"', true),
        "EndH" => array(0, 'id="deal-exphour"', 'value="', '"', true),
        "EndN" => array(0, 'id="deal-expmin"', 'value="', '"', true),
        "EndS" => array(0, 'id="deal-expsec"', 'value="', '"', true)
      );
      
      if (!GetNewDealData($Data, $NewDeal)) return array(SysLogIt('Error finding key deal information.', StatusError), null);
      
      $StartDate = time();
      $EndDate = mktime((int)$NewDeal['EndH'][0], (int)$NewDeal['EndN'][0], (int)$NewDeal['EndS'][0], (int)$NewDeal['EndM'][0]+1, (int)$NewDeal['EndD'][0], (int)$NewDeal['EndY'][0]);
      if ($EndDate <= time()) return array(SysLogIt('Could not determine deal end date.', StatusError), null);
      
      $VExpiry = FindSubData($Data, 'id="deal-fp-description"', '<p>', 'expiry', false);
      if ($VExpiry !== false) {
        if (strripos($VExpiry, '.') !== false) $VExpiry = trim(substr($VExpiry, strripos($VExpiry, '.') + 1));
        if ($VExpiry !== false) $VExpiry = strtotime('+'.$VExpiry);
      }
      if ($VExpiry === false) $VExpiry = 0;
      
      $Website = null;
      if ($NewDeal['Website'][0] !== false) $Website = $NewDeal['Website'][0];      
      
      $Address = null;
      if ($NewDeal['Address'][0] !== false) $Address = $NewDeal['Address'][0];      

      $Locations = array();
      $Locations[] = array($Address, 0, 0);
       
      $MoreDeals = array();
      $Start = stripos($Data, 'class="more-deals-link"');
      while ($Start !== false) {
        $ID = FindData($Data, '../treats/?dsdeal=', "'", 18, 0, true, $Start, false);
        if ($ID !== false) $MoreDeals[] = '/treats/?dsdeal='.$ID;
        $Start = stripos($Data, 'class="more-deals-link"', $Start + 20);
      }
      
      $RData = array();
      
      $RData['DRID'] = (int)$NewDeal['ID'][0];
      $RData['Title'] = $NewDeal['Title'][0];
      $RData['Descr'] = $NewDeal['Description'][0];
      $RData['URL'] = 'http://www.dealsnoop.com/treats/?dsdeal='.(int)$NewDeal['ID'][0];
      $RData['Price'] = (double)$NewDeal['PriceSale'][0];
      $RData['Value'] = (double)$NewDeal['PriceReg'][0];
      $RData['Status'] = 0;
      
      $RData['SDate'] = $StartDate;
      $RData['EDate'] = $EndDate;
      $RData['VDate'] = $VExpiry;
      
      $RData['StoreName'] = $NewDeal['StoreName'][0];
      $RData['Website'] = $Website;
      $RData['Locations'] = $Locations;
      
      if (count($MoreDeals) > 0) $RData['Deals'] = $MoreDeals;
      
      return array(true, array($RData));
      
    }
    
  }

?>
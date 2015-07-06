<?php

  class PriceDodger {

    public function GetNewDivisions($SID) {
    
      SysLogIt('Checking for new PriceDodger divisions.');
      if (!list ($Data, $DURL) = GetWebData('http://www.pricedodger.com/')) { return SysLogIt('Error getting web data.', StatusError); }
      
      SysLogIt('Parsing HTML.');

      $DataArray = FindSubData($Data, 'class="cities-list', '>', '</ol>', false);
      if ($DataArray === false) return SysLogIt("Couldn't find city content.", StatusError);

      //Parse contents of city box
      $DataArray = explode('</li>', $DataArray);
      
      foreach ($DataArray as $DataItem) {
      
        if (stripos($DataItem, 'href=') !== false) {
      
          $ID = FindSubData($DataItem, 'a href=', '"', '"', false);
          $Name = FindSubData($DataItem, 'a href=', '>', '<', false);
          
          if ($Name !== false && $ID !== false) {
          
            if (!CheckURL('PriceDodger', $SID, $Name, $ID)) return false;
              
          }
          
        }
      
      }  
      
      return UpStatus('PriceDodger', $SID);
      
        
    }
    
    public function GetDivisionData($SRID, $URL, $LID, $TZ, $Sub = false, $WebD = false) {
    
      global $DebugMode;
      
      list ($Data, $DURL) = GetWebData('http://www.pricedodger.com'.$URL);
      if ($Data === false) return array(SysLogIt('Error getting web data.', StatusError), null);
      
      if ($DebugMode) file_put_contents("PriceDodger.txt", 'http://www.pricedodger.com'.$URL.chr(13).chr(10).chr(13).chr(10).$Data);
      
      if ((stripos($Data, 'id="deal_intro"') === false) || (stripos($Data, 'deal ended at') !== false)) return array(true, false);
      
      $NewDeal = array (
        "ID" => array('', 'id="deal_intro"', '/deal/', '"', false),
        "URL" => array('', 'id="deal_intro"', 'a href="', '"', false),
        "Title" => array('', 'id="deal_intro"', 'title="', '"', false),
        "Website" => array('', 'class="company_link', 'href="', '"', false, true),
        "PriceSale" => array(0, 'class="price">$', '>', '<', true),
        "PriceReg" => array(0, 'class="value">$', '>', '<', true),
        "StoreName" => array('', 'class="company_link', '<span ', '</span>', false),
        "Description" => array('', 'id="deal_desc"', '>', 'class="company', false),
        "Address" => array('', 'id="deal_desc"', '</u>', '</span>'.chr(9).chr(9), false, true),
        "Coords" => array('', 'maps.google.com', '?center=', '&', false, true),
        "EndDate" => array('', 'id="time_left"', '                        ', '<div>', false)
      );
      
      if (!GetNewDealData($Data, $NewDeal)) return array(SysLogIt('Error finding key deal information.', StatusError), null);
      
      $StartDate = time();
      $EndDate = time();
      if (stripos($NewDeal['EndDate'][0], 'days') !== false) {
        $EndDate += (60 * 60 * 24 * (int)substr($NewDeal['EndDate'][0], 0, stripos($NewDeal['EndDate'][0], 'days') - 1));
        $EndDate += ((int)FindData($NewDeal['EndDate'][0], '&nbsp;', 'H', 6, 0, false) * 60 * 60);
      } else {
        $EndDate += ((int)substr($NewDeal['EndDate'][0], 0, stripos($NewDeal['EndDate'][0], 'H')) * 60 * 60);
      }
      $EndDate += ((int)FindData($NewDeal['EndDate'][0], ';:&nbsp;', 'M', 8, 0, false) * 60);
      if ($EndDate <= time()) return array(SysLogIt('Could not determine deal end date.', StatusError), null);
      
      
      $VExpiry = FindData($Data, '>Expires', '<', 9, 0, false, 0, false);
      if ($VExpiry !== false) $VExpiry = strtotime($VExpiry);
      if ($VExpiry === false) $VExpiry = 0;
      
      $StoreName = $NewDeal['StoreName'][0];
      $StoreName = trim(str_replace('class="c">', ' ', $StoreName));
      $StoreName = trim(str_replace('class="c1">', ' ', $StoreName));
      
      $Address = null;
      if ($NewDeal['Address'][0] !== false) $Address = CleanHTML($NewDeal['Address'][0]);
      if ($Address == '') $Address = null;
          
      $Coords = array(0, 0);
      if ($NewDeal['Coords'][0] !== false) $Coords = explode(',', $NewDeal['Coords'][0]);
      if (($Coords === false) || (count($Coords) != 2)) $Coords = array(0, 0);
      
      $MoreDeals = array();
      $Start = stripos($Data, 'id="side_deal"');
      while ($Start !== false) {
        $New = FindData($Data, 'http://pricedodger.com/deal/', '"', 28, 0, false, $Start, false);
        if ($New !== false) $MoreDeals[] = '/deal/'.$New;
        $Start = stripos($Data, 'id="side_deal"', $Start + 20);
      }
      
      $RData = array();
      
      $RData['DRID'] = $NewDeal['ID'][0];
      $RData['Title'] = $NewDeal['Title'][0];
      $RData['Descr'] = $NewDeal['Description'][0];
      $RData['URL'] = 'http://www.pricedodger.com'.$NewDeal['URL'][0];
      $RData['Price'] = (double)$NewDeal['PriceSale'][0];
      $RData['Value'] = (double)$NewDeal['PriceReg'][0];
      $RData['Status'] = 0;
      
      $RData['SDate'] = $StartDate;
      $RData['EDate'] = $EndDate;
      $RData['VDate'] = $VExpiry;
      
      $RData['StoreName'] = $StoreName;
      $RData['Website'] = $NewDeal['Website'][0];
      $RData['Locations'] = array(array($Address, $Coords[0], $Coords[1]));
      
      if (count($MoreDeals) > 0) $RData['Deals'] = $MoreDeals;
      
      return array(true, array($RData));
      
    }
    
  }

?>
<?php

  class Kijiji {

    public function GetNewDivisions($SID) {
    
      global $DebugMode;
    
      SysLogIt('Checking for new Kijiji divisions.');
      if (!list ($Data, $DURL) = GetWebData('http://www.kijijideals.ca/deals/toronto/')) { return SysLogIt('Error getting web data.', StatusError); }
      
      if ($DebugMode) file_put_contents("KijijiCity.txt", $Data);
      
      SysLogIt('Parsing HTML.');
      
      $DataArray = FindSubData($Data, 'class="list"', '>', '</div>', false);
      if ($DataArray === false) return SysLogIt("Couldn't find city content.", StatusError);
      
      $DataArray = explode('</li>', $DataArray);
      
      foreach ($DataArray as $DataItem) {
      
        if (stripos($DataItem, 'http://www.kijijideals.ca/deals/') !== false) {
      
          $ID = FindSubData($DataItem, 'http://www.kijijideals.ca/deals', "/", "/", false);
          $Name = FindSubData($DataItem, 'http://www.kijijideals.ca/deals/', '>', '</a', false);
          //$Name = CleanHTML(str_ireplace(' - English', '', $Name));
          if ($Name !== false) $Name = CleanHTML($Name);
          
          if (($Name != '') && ($Name !== false) && ($ID !== false)) {
          
            if (!CheckURL('Kijiji', $SID, $Name, $ID)) return false;
              
          }
          
        }
      
      }
      
      return UpStatus('Kijiji', $SID);
        
    }
    
    public function GetDivisionData($SRID, $URL, $LID, $TZ, $Sub = false, $WebD = false) {
    
      global $DebugMode;

      list ($Data, $DURL) = GetWebData('http://www.kijijideals.ca/deals/'.$URL);
      if ($Data === false) return array(SysLogIt('Error getting web data.', StatusError), null);
      
      if ($DebugMode) file_put_contents("Kijiji.txt", "http://www.kijijideals.ca/deals/".$URL.chr(13).chr(10).chr(13).chr(10).$Data);
      
      if (stripos($Data, '/buy"') === false) return array(true, false);
      
      $NewDeal = array (
        "ID" => array('', 'id="toDeal"', 'kijijideals.ca/deals/', '"', false),
        "Title" => array('', 'id="toDeal">', '>', '</a>', false),
        "Website" => array('', 'class="location_box"', 'href="', '"', false, true),
        "PriceSale" => array(0, 'class="price"', '$', '<', true),
        "PriceReg" => array(0, 'class="value"', '$', '<', true),
        "StoreName" => array('', 'class="location_box"', '<p>', '<', false),
        "Description" => array('', 'About this deal', '<p>', '</p>', false),
        "Address" => array('', 'maps.google.com', '?q=', '"', false, true),
        "EndDate" => array('', 'ready(function()', 'setCountdown("', '"', false)
      );
      
      if (!GetNewDealData($Data, $NewDeal)) return array(SysLogIt('Error finding key deal information.', StatusError), null);
                      
      $StartDate = time();
      $EndDate = strtotime($NewDeal['EndDate'][0]);
      if ($EndDate === false) return array(SysLogIt('Could not determine deal end date.', StatusError), null);
      if ($EndDate < time()) return array(true, false);
      
      $VExpiry = FindData($Data, '<p>Expires', 'from', 11, 0, false, 0, false);
      if ($VExpiry !== false) $VExpiry = strtotime('+'.$VExpiry);
      if ($VExpiry === false) $VExpiry = 0;
      
      $Website = null;
      if ($NewDeal['Website'][0] !== false) $Website = $NewDeal['Website'][0];
      
      $Address = null;
      if ($NewDeal['Address'][0] !== false) $Address = CleanHTML($NewDeal['Address'][0]);
      if ($Address == '') $Address = null;
      
      $MoreDeals = array();
      $Start = stripos($Data, 'class="dealBox ');
      while ($Start !== false) {
        $ID = FindData($Data, 'href="http://www.kijijideals.ca/deals/', '"', 38, 0, false, $Start, false);
        if ($ID !== false) $MoreDeals[] = $ID;
        $Start = stripos($Data, 'class="dealBox ', $Start + 20);
      }      
      
      $RData = array();
      
      $RData['DRID'] = $NewDeal['ID'][0];
      $RData['Title'] = CleanHTML(str_ireplace("Today's Deal:", "", $NewDeal['Title'][0]));
      $RData['Descr'] = $NewDeal['Description'][0];
      $RData['URL'] = 'http://www.kijijideals.ca/deals/'.$NewDeal['ID'][0];
      $RData['Price'] = (double)$NewDeal['PriceSale'][0];
      $RData['Value'] = (double)$NewDeal['PriceReg'][0];
      $RData['Status'] = 0;
      
      $RData['SDate'] = $StartDate;
      $RData['EDate'] = $EndDate;
      $RData['VDate'] = $VExpiry;
      
      $RData['StoreName'] = $NewDeal['StoreName'][0];
      $RData['Website'] = $Website;
      $RData['Locations'] = array(array($Address, 0, 0));
      
      if (count($MoreDeals) > 0) $RData['Deals'] = $MoreDeals;
      
      return array(true, array($RData));
      
    }
    
  }

?>
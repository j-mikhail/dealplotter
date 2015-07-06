<?php

  class WagJag {

    public function GetNewDivisions($SID) {
    
      SysLogIt('Checking for new WagJag divisions.');
      if (!list ($Data, $DURL) = GetWebData('http://www.wagjag.com/')) { return SysLogIt('Error getting web data.', StatusError); }
      
      SysLogIt('Parsing HTML.');
      
      if (!$DataArray = FindSubData($Data, 'id="city_regions"', '>', '</form>', false)) { return SysLogIt("Couldn't find city content.", StatusError); }
      
      //Large Centres
      $DataArray = explode('class="city_name"', $DataArray);
      
      foreach ($DataArray as $DataItem) {
      
        if (stripos($DataItem, 'href="/?c=') !== false) {
      
          $ID = FindSubData($DataItem, 'href=', '"', '"', false);
          $Name = FindSubData($DataItem, 'href=', ">", "<", false);
          
          if ($Name !== false && $ID !== false) {
          
            if (!CheckURL('WagJag', $SID, $Name, $ID)) return false;
              
          }
          
        }
      
      }
           
      return UpStatus('WagJag', $SID);
        
    }
    
    public function GetDivisionData($SRID, $URL, $LID, $TZ, $Sub = false, $WebD = false) {
    
      global $DebugMode;

      //$TrueURL = (is_numeric($URL)?'?_page=home&city='.$URL:$URL);
      
      list ($Data, $DURL) = GetWebData('http://www.wagjag.com'.$URL);
      if ($Data === false) return array(SysLogIt('Error getting web data.', StatusError), null);
      
      if ($DebugMode) file_put_contents("WagJag.txt", "http://www.wagjag.com/".$URL.chr(13).chr(10).chr(13).chr(10).$Data);
      
      if ((stripos($Data, 'id="js_wagjag_id"') === false) || (stripos($Data, 'error with that wagjag') !== false)) return array(true, false);
      
      $NewDeal = array (
        "ID" => array(0, 'id="js_wagjag_id"', 'value="', '"', true),
        "Title" => array('', 'id="deal_headline"', '>', '</span>', false),
        "Website" => array('', '<br /><br /></p>', 'href="', '"', false, true),
        "PriceReg" => array(0, 'Regular Price:', '$', '<', true),
        "PriceSale" => array(0, 'Buy For', '$', '<', true),
        "StoreName" => array('', '<!-- <h1>About', ' ', '<', false),
        "Description" => array('', '<!-- Deal Highlights -->', 'Highlights</span>', '<!-- End Deal Information -->', false),
        "Addresses" => array('', 'var sites =', '[[', ']]', false, true),
        "EndDate" => array('', 'TargetDate = ', '"', 'GMT', false)
      );
      
      if (!GetNewDealData($Data, $NewDeal)) return array(SysLogIt('Error finding key deal information.', StatusError), null);
                      
      $StartDate = time();
      $EndDate = strtotime($NewDeal['EndDate'][0]);
      if ($EndDate === false) return array(SysLogIt('Could not determine deal end date.', StatusError), null);
      //Wagjags all seem to finish at midnight EST
      
      $VExpiry = FindData($Data, '<li>Expires', '</li>', 11, 0, false, 0, false);
      if (substr($VExpiry, 0, 3) == 'in ') {
        $VExpiry = strtotime(str_ireplace('in ', '+', $VExpiry));
      } else {
        $VExpiry = ($VExpiry === false)? 0:strtotime($VExpiry);
      }
      if ($VExpiry === false) $VExpiry = 0;
      
      $Title = CleanHTML($NewDeal['Title'][0]);
      
      $Locations = array();
      if ($NewDeal['Addresses'][0] !== false) {
        $Addresses = explode('],[', $NewDeal['Addresses'][0]);
        foreach ($Addresses as $Address) {
          $AdrName = FindData($Address, ', "', '"', 3, 0, false, 0, false);
          if ($AdrName !== false) {
          
            $Coords = FindData($Address, '",', ', ', 2, 0, false, 0, false);
            if (($Coords !== false) && (stripos($Coords, ',') !== false)) {
              $Coords = explode(',', $Coords);
              $Locations[] = array(CleanHTML($AdrName), $Coords[0], $Coords[1]);
            } else {
              $Locations[] = array(CleanHTML($AdrName), 0, 0);
            }
          
          }
        }
      }
      if (count($Locations) == 0) $Locations[] = array(null, 0, 0);
      
      $MoreDeals = array();
      $Start = stripos($Data, "/?wagjag=");
      while ($Start !== false) {
        $ID = FindData($Data, '/?wagjag=', "'", 9, 0, true, $Start, false); 
        if ($ID !== false) $MoreDeals[] = '/?wagjag='.$ID;
        $Start = stripos($Data, "/?wagjag=", $Start + 20);
      }
      
      $Website = null;
      if ($NewDeal['Website'][0] !== false) $Website = trim($NewDeal['Website'][0]);
      if ($Website == '') $Website = null;
      
      $RData = array();
      
      $RData['DRID'] = (int)$NewDeal['ID'][0];
      $RData['Title'] = $Title;
      $RData['Descr'] = $NewDeal['Description'][0];
      $RData['URL'] =  'http://www.wagjag.com/index.php?_page=home&wagjag='.(int)$NewDeal['ID'][0];
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
<?php

  class Fabfind {

    public function GetNewDivisions($SID) {
    
      ExecCommand("UPDATE 4100_Deal_Sources SET URLsLastUpdated = ".date('YmdHis')." WHERE DealSourceID = ".$SID.";");
      return true; //Success

      /*
      SysLogIt('Retrieving Fabfind homepage.');
      if (!list ($Data, $DURL) = GetWebData('http://www.Fabfind.ca/')) { return SysLogIt('Error getting web data.', StatusError); }
      
      //file_put_contents("Fabfind.txt", $Data);
      
      SysLogIt('Parsing HTML.');
      
      if ($DataArray = FindSubData($Dasta, 'id="cityBoxFancyAP"', '<table>', '</table>', false));
      if ($DataArray === false) return SysLogIt("Couldn't find city content.", StatusError);
      
      //Large Centres
      $DataArray = explode('</td>', $DataArray);
      
      foreach ($DataArray as $DataItem) {
      
        if (stripos($DataItem, 'otherCity') !== false) {
      
          $ID = FindData($DataItem, 'change-to-', '"', 10, 0, false, 0, false);
          $Name = FindSubData($DataItem, '<span><a', '>', '<', false);
          
          if ($Name !== false && $ID !== false) {
          
            if (!CheckURL('Fabfind', $SID, $Name, $ID)) return false;
              
          }
          
        }
      
      }      
      
      return UpStatus('Fabfind', $SID);
      */
        
    }
    
    public function GetDivisionData($SRID, $URL, $LID, $TZ, $Sub = false, $WebD = false) {
    
      global $DebugMode;
      
      list ($Data, $DURL) = GetWebData('http://toronto.fabfind.com/'.$URL, null, 'Preferences=Town=M2');
      if ($Data === false) return array(SysLogIt('Error getting web data.', StatusError), null);
        
      if ($DebugMode) file_put_contents("FabFind.txt", $Data);
    
      if (stripos($Data, 'Chatter.aspx?dealid=') === false) return array(true, false);
      
      $NewDeal = array (
        "ID" => array(0, 'Chatter.aspx?dealid', '=', '"', true),
        "Title" => array('', 'id="ctl00_contentTop_lblTitle"', '>', '</span', false),
        "PriceSale" => array(0, 'ctl00_contentTop_lblPrice', '>', '<', true),
        "PriceReg" => array(0, 'ctl00_contentTop_lblValue', '$', '<', true),
        "StoreName" => array('', 'ctl00_contentMain_lblBusinessName', '>', '<', false),
        "Website" => array('', 'linkBusinessWebsite', 'href="', '"', false, true),
        "Phone" => array('', 'ctl00_contentMain_lblPhone', 'Phone: ', '<', false, true),
        "Address" => array('', 'ctl00_contentMain_lblAddress', '>', '</span', false, true),
        "Description" => array('', 'ctl00_contentMain_lblDescription', '>', '</span>', false),
        "Coords" => array('', 'GMaps', 'GLatLng(', ')', false, true),
        "EndDate" => array(0, 'DealSecLeft', '+', "'", true)
      );
                    
      if (!GetNewDealData($Data, $NewDeal)) return array(SysLogIt('Error finding key deal information.', StatusError), null);
      
      $StartDate = time();
      $EndDate = time() + $NewDeal['EndDate'][0];
      //End date uses seconds left, so no Timezone calculations needed
      
      $VExpiry = FindSubData($Data, 'Certificate expires', '(', ')', false);
      if ($VExpiry === false) $VExpiry = FindSubData($Data, '<li>Expires', ' ', '<', false);
      $VExpiry = ($VExpiry === false)? 0 : strtotime($VExpiry);
      if ($VExpiry === false) $VExpiry = 0;
      
      $Address = ($NewDeal['Address'][0] === false)?'':trim(str_ireplace('<br />', ', ', $NewDeal['Address'][0]));
      if (stripos($Address, 'several locations') !== false) {
        $Address = array();
        $Start = stripos($Data, '&hnear=');
        while ($Start !== false) {
          $New = FindData($Data, '&hnear=', '&', 7, 0, false, $Start, false);
          if ($New !== false) $Address[] = array(str_ireplace('+', ' ', $New), 0, 0);
          $Start = stripos($Data, '&hnear=', $Start + 20);
        }
      }
      if (!is_array($Address)) $Address = array(array($Address, 0, 0));
      foreach ($Address as &$Adr) {
        if ($NewDeal['Phone'][0] !== false) $Adr[0] .= $NewDeal['Phone'][0];
        if ($Adr[0] == '') $Adr[0] = null;
      }
        
      /*
      $Coords = array(0, 0);
      if ($NewDeal['Coords'][0] !== false) $Coords = explode(',', $NewDeal['Coords'][0]);
      if (($Coords === false) || (count($Coords) != 2)) $Coords = array(0, 0);
      */
      
      $MoreDeals = array();
      $Start = stripos($Data, 'href="deal/');
      while ($Start !== false) {
        $New = FindData($Data, 'href="deal/', '"', 11, 0, false, $Start, false);
        if ($New !== false) $MoreDeals[] = '/deal/'.$New;
        $Start = stripos($Data, 'href="deal/', $Start + 20);
      }
      
      $Title = CleanHTML($NewDeal['Title'][0]);
      
      $RData = array();
      
      $RData['DRID'] = (int)$NewDeal['ID'][0];
      $RData['Title'] = $Title;
      $RData['Descr'] = $NewDeal['Description'][0];
      $RData['URL'] = 'http://toronto.fabfind.com/Default.aspx?DealID='.(int)$NewDeal['ID'][0];
      $RData['Price'] = (double)$NewDeal['PriceSale'][0];
      $RData['Value'] = (double)$NewDeal['PriceReg'][0];
      $RData['Status'] = 0;
      
      $RData['SDate'] = $StartDate;
      $RData['EDate'] = $EndDate;
      $RData['VDate'] = $VExpiry;
      
      $RData['StoreName'] = $NewDeal['StoreName'][0];
      $RData['Website'] = $NewDeal['Website'][0];
      $RData['Locations'] = $Address;
      
      if (count($MoreDeals) > 0) $RData['Deals'] = $MoreDeals;
      
      return array(true, array($RData));    
      
    }
    
  }

?>
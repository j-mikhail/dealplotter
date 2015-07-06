<?php

  class GrouponCA {

    public function GetNewDivisions($SID) {
    
      global $DebugMode;
    
      SysLogIt('Checking for new Groupon Canada divisions.');
      if (!list ($Data, $DURL) = GetWebData('http://www.groupon.ca/', null, 'user_locale=en_CA')) { return SysLogIt('Error getting web data.', StatusError); }
      
      if ($DebugMode) file_put_contents("GrouponCACity.txt", $Data);
      
      SysLogIt('Parsing HTML.');
      
      $DataArray = FindSubData($Data, 'id="citySelectBox"', '<ul', '</ul>', false);
      if ($DataArray === false) return SysLogIt("Couldn't find city content.", StatusError);
      
      //Large Centres
      $DataArray = explode('</li>', $DataArray);
      
      foreach ($DataArray as $DataItem) {
      
        if (stripos($DataItem, 'http://www.groupon.ca/') !== false) {
      
          $ID = FindData($DataItem, 'http://www.groupon.ca/', "'", 22, 0, false, 0, false);
          $Name = FindSubData($DataItem, 'http://www.groupon.ca/', '<span>', '</span>', false);
          
          if ($Name !== false && $ID !== false) {
          
            if ($Name == '') $Name = ucwords($ID);
            if (!CheckURL('Groupon Canada', $SID, $Name, $ID)) return false;
              
          }
          
        }
      
      }      
      
      return UpStatus('Groupon Canada', $SID);
        
    }
    
    public function GetDivisionData($SRID, $URL, $LID, $TZ, $Sub = false, $WebD = false) {
    
      global $DebugMode;
      
      list ($Data, $DURL) = GetWebData('http://www.groupon.ca/'.$URL, null, 'user_locale=en_CA'); //fr_CA
      if ($Data === false) return array(SysLogIt('Error getting web data.', StatusError), null);
        
      if ($DebugMode) file_put_contents("GrouponCA.txt", $Data);
    
      if (stripos($Data, '/payment') === false) return array(true, false);
          
      $NewDeal = array (
        "ID" => array('', 'http://www.facebook.com/share.php', '?u=http://www.groupon.ca', '"', false),
        "Title" => array('', 'id="contentDealTitle"', '<h1>', '</h1>', false),
        "PriceSale" => array(0, 'class="price"', 'C$', '<', true),
        "Savings" => array(0, 'class="row2"', 'C$', '<', true),
        "StoreName" => array('', 'class="merchantContact"', '>', '</h2>', false),
        "Website" => array('', 'class="merchantContact"', 'href="', '"', false, true),
        "Description" => array('', 'class="contentBoxNormalLeft"', '>', '<div', false),
        "EndDate" => array(0, 'id="currentTimeLeft"', 'value="', '"', true)
      );
                        
      if (!GetNewDealData($Data, $NewDeal)) return array(SysLogIt('Error finding key deal information.', StatusError), null);
    
      //Start, end dates
      $StartDate = time();
      $EndDate = time();
      $EndDate += ((int)$NewDeal['EndDate'][0] / 1000);
      if ($EndDate <= time()) return array(SysLogIt('Could not determine deal end date.', StatusError), null);
            
      //Store name
      $StoreName = CleanHTML($NewDeal['StoreName'][0]);
              
      //Expiry Date
      $VExpiry = FindData($Data, '<li>Expires', '<', 11, 0, false, 0, false);
      if ($VExpiry !== false) $VExpiry = strtotime($VExpiry);
      if ($VExpiry === false) $VExpiry = 0;
      
      $Website = null;
      if ($NewDeal['Website'][0] !== false) $Website = $NewDeal['Website'][0];      
                    
      //Address(es)
      if ((stripos($Data, '<strong>Locations') === false) && (stripos($Data, 'locations:</strong>') === false)) {
        
        $Addresses = FindSubData($Data, 'class="merchantContact"', '</h2>', '<a', false, 0, false);
        if ($Addresses !== false) $Addresses = array($Addresses);
        if ($Addresses === false) $Addresses = array();
      
      } else {
      
        $Addresses = FindSubData($Data, '<strong>Locations', '</strong>', '<p', false, 0, false);
        if ($Addresses === false) $Addresses = FindSubData($Data, 'locations:</strong>', '</p>', '<p', false, 0, false);
        if ($Addresses !== false) $Addresses = explode('<br /><br />', $Addresses);
        if ($Addresses === false) $Addresses = array();
              
      }
      
      $Locations = array();
      
      foreach ($Addresses as &$Adr) {
        if ((!is_null($Adr)) && ($Adr != '')) {
          if (stripos($Adr, 'online') !== false) {
            $Locations[] = array(CleanHTML($Adr), -1, -1);
          } else {
            $Locations[] = array(CleanHTML($Adr), 0, 0);
          }
        }
      }
      
      if (count($Locations) == 0) $Locations[] = array(null, 0, 0);
      
      //Side deals
        
      $Last = $NewDeal['ID'][0];
      $MoreDeals = array();
        
      $Start = stripos($Data, '"/deals/');
      while ($Start !== false) {
        $New = FindData($Data, '"/deals/', '"', 1, 0, false, $Start, false);
        if (($New !== false) && ($New != $Last)) {
          $MoreDeals[] = $New;
          $Last = $New;
        }
        $Start = stripos($Data, '"/deals/', $Start + 20);
      }
      
      $TitleFR = '';
      list ($Data, $DURL) = GetWebData('http://www.groupon.ca/'.$URL, null, 'user_locale=fr_CA');
      if ($Data !== false) {
        $TitleFR = FindSubData($Data, 'id="contentDealTitle"', '<h1>', '</h1>', false, 0, false);
        if ($TitleFR !== false) $TitleFR = CleanHTML($TitleFR);
      }
      
      //Save
        
      $RData = array();
      
      $RData['DRID'] = $NewDeal['ID'][0];
      $RData['Title'] = CleanHTML($NewDeal['Title'][0]);
      if (($TitleFR !== false) && ($TitleFR != '')) $RData['Title-fr'] = $TitleFR;
      $RData['Descr'] = $NewDeal['Description'][0];
      $RData['URL'] = 'http://www.groupon.ca'.$NewDeal['ID'][0];
      $RData['Price'] = (double)$NewDeal['PriceSale'][0];
      $RData['Value'] = (double)$NewDeal['PriceSale'][0] + (double)$NewDeal['Savings'][0];
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
<?php

  class StealTheDeal {

    public function GetNewDivisions($SID) {
    
      SysLogIt('Checking for new StealTheDeal divisions.');
      if (!list ($Data, $DURL) = GetWebData('http://www.stealthedeal.com/')) { return SysLogIt('Error getting web data.', StatusError); }
      
      //file_put_contents("StealTheDeal.txt", $Data);
      
      SysLogIt('Parsing HTML.');
      
      $DataArray = FindSubData($Data, '"locationPanel"', '<ul>', '</ul>', false);
      if ($DataArray === false) return SysLogIt("Couldn't find city content.", StatusError);
      
      //Large Centres
      $DataArray = explode('</li>', $DataArray);
      
      foreach ($DataArray as $DataItem) {
      
        if (stripos($DataItem, 'href=') !== false) {
      
          $ID = FindData($DataItem, 'href="/', '"', 7, 0, false, 0, false);
          $Name = FindSubData($DataItem, 'href=', '>', '<', false);
          
          if ($Name !== false && $ID !== false) {
          
            if ($Name == '') $Name = ucwords($ID);
            if (!CheckURL('StealTheDeal', $SID, $Name, $ID)) return false;
              
          }
          
        }
      
      }      
      
      return UpStatus('StealTheDeal', $SID);
        
    }
    
    public function GetDivisionData($SRID, $URL, $LID, $TZ, $Sub = false, $WebD = false) {
    
      global $DebugMode;
      
      list ($Data, $DURL) = GetWebData('http://www.stealthedeal.com/'.$URL);
      if ($Data === false) return array(SysLogIt('Error getting web data.', StatusError), null);
        
      if ($DebugMode) file_put_contents("StealTheDeal.txt", $Data);
    
      if (stripos($Data, 'og:url') === false) return array(true, false);
          
      $NewDeal = array (
        "ID" => array(0, 'og:url', 'stealthedeal.com/deal/', '"', true),
        "Title" => array('', 'og:title', 'content="', '"', false),
        "PriceSale" => array(0, 'dealPrice', '$', '<', true),
        "PriceReg" => array(0, '"valueTitle"', '$', '<', true),
        "Website" => array('', 'Merchant website:', 'href="', '"', false, true),
        "Description" => array('', 'DescriptionHtml Start', '>', '<!', false),
        "Address" => array('', 'maps.google', ';q=', '&', false, true),
        "Coords" => array('', 'maps.google', ';ll=', '&', false, true),
        "Address2" => array('', 'DetailsHtml', '</li>', '</ul>', false, true),
        "TZone" => array(0, 'TzOffset', '=', ';', true),
        "EndDate" => array('', 'TargetDate =', '"', '"', false)
      );
                        
      if (!GetNewDealData($Data, $NewDeal)) return array(SysLogIt('Error finding key deal information.', StatusError), null);
    
      $StartDate = time();
      $EndDate = strtotime($NewDeal['EndDate'][0]);
      $EndDate += -(((int)$NewDeal['TZone'][0] + 5) * 60 * 60);
      if ($EndDate <= time()) return array(SysLogIt('Could not determine deal end date.', StatusError), null);
            
      $StoreName = '';
      if (stripos($NewDeal['Title'][0], ' from ') !== false) {
        $StoreName = substr($NewDeal['Title'][0], stripos($NewDeal['Title'][0], ' from ') + 6); 
      } elseif (stripos($NewDeal['Title'][0], ' at ') !== false) {
        $StoreName = substr($NewDeal['Title'][0], stripos($NewDeal['Title'][0], ' at ') + 4);
      } elseif (stripos($NewDeal['Title'][0], ' by ') !== false) {
        $StoreName = substr($NewDeal['Title'][0], stripos($NewDeal['Title'][0], ' by ') + 4);
      } elseif (stripos($NewDeal['Title'][0], ' to ') !== false) {
        $StoreName = substr($NewDeal['Title'][0], stripos($NewDeal['Title'][0], ' to ') + 4);
      } elseif ((stripos($NewDeal['Title'][0], 'for a') !== false) && (stripos($NewDeal['Title'][0], 'voucher') !== false)) {
        $StoreName = substr($NewDeal['Title'][0], stripos($NewDeal['Title'][0], 'for a') + 5);
        $StoreName = trim(substr($StoreName, 0, stripos($NewDeal['Title'][0], 'voucher')));
        if (substr($StoreName, 0, 2) == 'n ') $StoreName = trim(substr($StoreName, 2));
      } elseif (stripos($NewDeal['Title'][0], ' with ') !== false) {
        $StoreName = substr($NewDeal['Title'][0], stripos($NewDeal['Title'][0], ' with ') + 6);
      }
      if (stripos($StoreName, '(') !== false) $StoreName = trim(substr($StoreName, 0, stripos($StoreName, '(') - 1));
            
      if ($StoreName == '') return array(SysLogIt('Could not determine store name.', StatusError), null);
              
      $VExpiry = 0;
      if (stripos($Data, 'Please use within') !== false) {
        $VExpiry = FindData($Data, 'Please use within', 'of', 17, 0, false, 0, false);
        if ($VExpiry !== false) $VExpiry = strtotime('+'.$VExpiry);
      } elseif (stripos($Data, 'Expires on') !== false) {
        $VExpiry = FindData($Data, 'Expires on', '<', 10, 0, false, 0, false);
        if ($VExpiry !== false) $VExpiry = strtotime($VExpiry);
      }
      if ($VExpiry === false) $VExpiry = 0;
              
      $Address = ($NewDeal['Address'][0] === false)?null:str_ireplace('+', ' ', $NewDeal['Address'][0]);
      if (is_null($Address) && ($NewDeal['Address2'][0] !== false)) $Address = CleanHTML(str_ireplace('map it', '', $NewDeal['Address2'][0]));
      if ($Address == '') $Address = null;
      
      $Coords = array(0, 0);
      if ($NewDeal['Coords'][0] !== false) $Coords = explode(',', $NewDeal['Coords'][0]);
      if (($Coords === false) || (count($Coords) != 2)) $Coords = array(0, 0);
      
      $Last = (int)$NewDeal['ID'][0];
      $MoreDeals = array();
      
      $Start = stripos($Data, 'class="sideBox sideDeal"');
      if ($Start !== false) $Start = stripos($Data, '/Deal/', $Start);
      
      while ($Start !== false) {
        $New = FindData($Data, "/Deal/", '"', 6, 0, true, $Start, false);
        if (($New !== false) && ($New > 0) && ($New != $Last)) {
          $MoreDeals[] = '/'.$URL.'/Deal/'.$New;
          $Last = $New;
        }
        $Start = stripos($Data, "/Deal/", $Start + 20);
      }
        
      $RData = array();
      
      $RData['DRID'] = (int)$NewDeal['ID'][0];
      $RData['Title'] = $NewDeal['Title'][0];
      $RData['Descr'] = $NewDeal['Description'][0];
      $RData['URL'] = 'http://www.stealthedeal.com/'.$URL;
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
<?php

  class Groupon {

    public function GetNewDivisions($SID) {
    
      global $DebugMode;
    
      SysLogIt('Checking for new Groupon divisions.');
      if (!list ($Data, $DURL) = GetWebData('http://www.groupon.com')) { return SysLogIt('Error getting web data.', StatusError); }
      
      if ($DebugMode) file_put_contents("GrouponCity.txt", $Data);
      
      SysLogIt('Parsing HTML.');
      
      $DataArray = FindSubData($Data, "id='subscription_division_id'", '>', '</select>', false);
      if ($DataArray === false) return SysLogIt("Couldn't find city content.", StatusError);
      
      $DataArray = explode('/option>', $DataArray);
      
      foreach ($DataArray as $DataItem) {
      
        if (stripos($DataItem, 'value=') !== false) {
      
          $ID = FindSubData($DataItem, "value=", "'", "'", false);
          $Name = FindSubData($DataItem, 'value=', '>', '<', false);
          
          if (($Name !== false) && ($ID !== false)) {

            if (!CheckURL('Groupon', $SID, $Name, $ID)) return false;

          }
          
        }
      
      }
      
      return UpStatus('Groupon', $SID);
        
    }
    
    public function GetDivisionData($SRID, $URL, $LID, $TZ, $Sub = false, $WebD = false) {
    
      global $DebugMode;

      list ($Data, $DURL) = GetWebData('http://www.groupon.com/'.$URL, null, 'visited=true');
      if ($Data === false) return array(SysLogIt('Error getting web data.', StatusError), null);
      
      if ($DebugMode) file_put_contents("Groupon.txt", 'http://www.groupon.com/'.$URL.chr(13).chr(10).chr(13).chr(10).$Data);
      
      if (stripos($Data, 'pledge_id') === false) return array(true, false);
      
      $NewDeal = array (
        "ID" => array('', 'Groupon.currentDeal.permalink', '"', '"', false),
        "Title" => array('', 'Groupon.currentDeal.title', '"', '"', false),
        "Website" => array('', "class='company_links", 'href="', '"', false, true),
        "PriceSale" => array(0, 'id="amount"', '$', '<', true),
        "PriceReg" => array(0, "id='deal_discount'", '$', '<', true),
        "StoreName" => array('', "class='name'", '>', '<', false),
        "Description" => array('', "class='pitch_content'", ">", '</div>', false),
        "Address" => array('', "class='address'", '<p>', '<a', false, true),
        "EndDate" => array(0, 'data-deadline=', "'", "'", true)
      );
      
      if (!GetNewDealData($Data, $NewDeal)) return array(SysLogIt('Error finding key deal information.', StatusError), null);
                      
      $StartDate = time();
      $EndDate = $NewDeal['EndDate'][0];
      if (($EndDate === false) || ($EndDate <= time())) return array(SysLogIt('Could not determine deal end date.', StatusError), null);
      
      $VExpiry = FindSubData($Data, '<li>Expires', ' ', '<', false);
      if ($VExpiry !== false) $VExpiry = strtotime($VExpiry);
      if ($VExpiry === false) $VExpiry = 0;
      
      $Website = null;
      if ($NewDeal['Website'][0] !== false) $Website = $NewDeal['Website'][0];
      
      $Address = null;
      if ($NewDeal['Address'][0] !== false) $Address = CleanHTML($NewDeal['Address'][0]);
      if ($Address == '') $Address = null;
      
      $RData = array();
      
      $RData['DRID'] = $NewDeal['ID'][0];
      $RData['Title'] = CleanHTML(str_ireplace("Today's Deal:", "", $NewDeal['Title'][0]));
      $RData['Descr'] = $NewDeal['Description'][0];
      $RData['URL'] = 'http://www.groupon.com/deals/'.$NewDeal['ID'][0];
      $RData['Price'] = (double)$NewDeal['PriceSale'][0];
      $RData['Value'] = (double)$NewDeal['PriceReg'][0];
      $RData['Status'] = 0;
      
      $RData['SDate'] = $StartDate;
      $RData['EDate'] = $EndDate;
      $RData['VDate'] = $VExpiry;
      
      $RData['StoreName'] = $NewDeal['StoreName'][0];
      $RData['Website'] = $Website;
      $RData['Locations'] = array(array($Address, 0, 0));
      
      return array(true, array($RData));
      
    }
    
  }

?>
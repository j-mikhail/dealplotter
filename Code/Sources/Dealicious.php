<?php

  class Dealicious {

    public function GetNewDivisions($SID) {
    
      global $DebugMode;
    
      SysLogIt('Checking for new Dealicious divisions.');
      if (!list ($Data, $DURL) = GetWebData('http://www.dealicious.ca')) { return SysLogIt('Error getting web data.', StatusError); }
      
      if ($DebugMode) file_put_contents("DealiciousCity.txt", $Data);
      
      SysLogIt('Parsing HTML.');
      
      $DataArray = FindSubData($Data, 'id="MyCity"', '>', '</select>', false);
      if ($DataArray === false) return SysLogIt("Couldn't find city content.", StatusError);
      
      $DataArray = explode('/option', $DataArray);
      
      foreach ($DataArray as $DataItem) {
      
        if (stripos($DataItem, '"IntroMyCityOptions"') !== false) {
      
          $ID = FindSubData($DataItem, 'value=', '"', '"', false);
          $Name = FindSubData($DataItem, 'value=', '>', '<', false);
          
          if (($Name !== false) && ($ID !== false)) {
          
            if (!CheckURL('Dealicious', $SID, $Name, $ID)) return false;
              
          }
          
        }
      
      }
      
      return UpStatus('Dealicious', $SID);
        
    }
    
    public function GetDivisionData($SRID, $URL, $LID, $TZ, $Sub = false, $WebD = false) {
    
      global $DebugMode;

      list ($Data, $DURL) = GetWebData('http://www.dealicious.ca/'.$URL);
      if ($Data === false) return array(SysLogIt('Error getting web data.', StatusError), null);
      
      if ($DebugMode) file_put_contents("Dealicious.txt", "http://www.dealicious.ca/".$URL.chr(13).chr(10).chr(13).chr(10).$Data);
      
      if (stripos($Data, 'manageDeal1') === false) return array(true, false);
      
      $NewDeal = array (
        "ID" => array('', '?dealId', '=', '"', false),
        "Title" => array('', 'class="OfferTitle_Homepage"', '</span>', '</div>', false),
        "Website" => array('', 'class="maplocation"', 'href="', '"', false, true),
        "PriceSale" => array(0, 'class="pricebox-left"', '<span>', '<', true),
        "PriceReg" => array(0, 'class="value"', '$', '<', true),
        "StoreName" => array('', 'class="maplocation"', '<h2>', '</h2>', false),
        "Description" => array('', '<!-- Company details', '<p>', '</p>', false),
        "Address" => array('', 'class="maplocation"', '</h2>', '</div>', false, true),
        "EndY" => array(0, "'year'", ':', ',', true),
        "EndM" => array(0, "'month'", ':', ',', true),
        "EndD" => array(0, "'day'", ':', ',', true),
        "EndH" => array(0, "'hour'", ':', ',', true),
        "EndN" => array(0, "'min'", ':', ',', true),
        "EndS" => array(0, "'sec'", ':', '}', true)
      );
      
      if (!GetNewDealData($Data, $NewDeal)) return array(SysLogIt('Error finding key deal information.', StatusError), null);
                      
      $StartDate = time();
      $EndDate = mktime((int)$NewDeal['EndH'][0], (int)$NewDeal['EndN'][0], (int)$NewDeal['EndS'][0], (int)$NewDeal['EndM'][0], (int)$NewDeal['EndD'][0], (int)$NewDeal['EndY'][0]);
      if ($EndDate <= time()) return array(SysLogIt('Could not determine deal end date.', StatusError), null);
      
      $VExpiry = FindSubData($Data, 'Expires on', '</strong>', '</div>', false);
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
      $RData['URL'] = 'http://www.dealicious.ca/index.php?deal='.$NewDeal['ID'][0];
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
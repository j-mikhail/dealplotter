<?php

  class Vieurbaine {

    public function GetNewDivisions($SID) {
    
      SysLogIt('Checking for new Vieurbaine / Citylinked divisions.');
      if (!list ($Data, $DURL) = GetWebData('http://www.citylinked.com/todays-deal.php?city=Montreal')) { return SysLogIt('Error getting web data.', StatusError); }
      
      //file_put_contents("Vieurbaine.txt", $Data);
      
      SysLogIt('Parsing HTML.');
      
      $DataArray = FindSubData($Data, 'class="tdropdown"', '<ul>', '</ul>', false);
      if ($DataArray === false) return SysLogIt("Couldn't find city content.", StatusError);
      
      //Large Centres
      $DataArray = explode('</li>', $DataArray);
      
      if (!CheckURL('Vieurbaine / Citylinked', $SID, 'Montreal', 'Montreal')) return false;
      
      foreach ($DataArray as $DataItem) {
      
        if (stripos($DataItem, 'href=') !== false) {
      
          $ID = FindSubData($DataItem, 'href=', '?city=', '"', false);
          $Name = FindSubData($DataItem, 'href=', '>', '<', false);
          
          if ($Name !== false && $ID !== false) {
          
            if ($Name == '') $Name = $ID;
            if (!CheckURL('Vieurbaine / Citylinked', $SID, $Name, $ID)) return false;
              
          }
          
        }
      
      }      
      
      return UpStatus('Vieurbaine / Citylinked', $SID);
        
    }
    
    public function GetDivisionData($SRID, $URL, $LID, $TZ, $Sub = false, $WebD = false) {
    
      global $DebugMode;
      
      if ($Sub) {
        list ($Data, $DURL) = GetWebData('http://www.citylinked.com/todays-deal.php?'.$URL); 
      } else {
        list ($Data, $DURL) = GetWebData('http://www.citylinked.com/todays-deal.php?city='.$URL); 
      }
      
      if ($Data === false) return array(SysLogIt('Error getting web data.', StatusError), null);
      if ($DebugMode) file_put_contents("Vieurbaine.txt", $Data);

      if (stripos($Data, 'og:url') === false) return array(true, false);
          
      $NewDeal = array (
        "ID" => array(0, 'og:url', '?deal=', '&', true),
        "Title" => array('', 'og:title', 'content="', '"/', false),
        "PriceSale" => array(0, 'itemprop="price"', '>', '$', true),
        "PriceReg" => array(0, 'line-through', '>', '$', true),
        "StoreName" => array('', 'var marker = createMarker(', 'point,"', '"', false),
        "Address" => array('', 'div id=\"gmapmarker\"', '<br \\/>', '<\\/div>', false),
        "Coords" => array('', 'var point = new GLatLng', '(', ')', false),
        "Website" => array('', 'itemprop="url"', 'href="', '"', false, true),
        "Description" => array('', 'og:description', 'content="', '"/>', false),
        "EndH" => array(0, 'id="hours"', '>', '<', true, true),
        "EndM" => array(0, 'id="mins"', '>', '<', true, true),
        "EndS" => array(0, 'id="secs"', '>', '<', true, true)
      );
                        
      if (!GetNewDealData($Data, $NewDeal)) return array(SysLogIt('Error finding key deal information.', StatusError), null);
    
      //Start, end dates
      $StartDate = time();
      $EndDate = time();
      if ($NewDeal['EndH'][0] !== false) $EndDate += ((int)$NewDeal['EndH'][0] * 60 * 60);
      if ($NewDeal['EndM'][0] !== false) $EndDate += ((int)$NewDeal['EndM'][0] * 60);
      if ($NewDeal['EndS'][0] !== false) $EndDate += ((int)$NewDeal['EndS'][0]);        
      if ($EndDate <= time()) return array(SysLogIt('Could not determine deal end date.', StatusError), null);
            
      //Expiry Date
      $VExpiry = FindData($Data, 'Promotional value expires', '</p>', 26, 0, false, 0, false);
      if ($VExpiry !== false) {
        if (stripos($VExpiry, ',') !== false) $VExpiry = substr($VExpiry, 0, stripos($VExpiry, ','));
        $VExpiry = strtotime(CleanHTML($VExpiry));
      }
      if ($VExpiry === false) $VExpiry = 0;
      
      //Website
      $Website = null;
      if ($NewDeal['Website'][0] !== false) $Website = $NewDeal['Website'][0];
                    
      //Address
      $Address = $NewDeal['Coords'][0];
      if (stripos($Address, ',') !== false) $Address = explode(',', $Address);
      if (($Address === false) || (count($Address) != 2)) $Address = array(0, 0);
      
      $Locations = array();
      $Locations[] = array(CleanHTML($NewDeal['Address'][0]), $Address[0], $Address[1]);
      
      $MoreDeals = array();
      if (stripos($Data, '?pagetype=sidedeal') !== false) $MoreDeals[] = "pagetype=sidedeal";
                  
      $TitleFR = false;
      list ($Data, $DURL) = GetWebData('http://www.vieurbaine.com/offre-du-jour.php?deal='.(int)$NewDeal['ID'][0]);
      if ($Data !== false) {
        $TitleFR = FindSubData($Data, 'og:title', 'content="', '"', false);
        if ($TitleFR !== false) $TitleFR = ucfirst(strtolower(CleanHTML($TitleFR)));
      }
      
      //Save
        
      $RData = array();
      
      $RData['DRID'] = (int)$NewDeal['ID'][0];
      $RData['Title'] = ucfirst(strtolower(CleanHTML($NewDeal['Title'][0])));
      if (($TitleFR !== false) && ($TitleFR != '')) $RData['Title-fr'] = $TitleFR;
      $RData['Descr'] = $NewDeal['Description'][0];
      $RData['URL'] = 'http://www.citylinked.com/todays-deal.php?deal='.(int)$NewDeal['ID'][0];
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
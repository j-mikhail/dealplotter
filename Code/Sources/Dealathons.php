<?php

  class Dealathons {

    public function GetNewDivisions($SID) {
    
      global $DebugMode;
    
      SysLogIt('Checking for new Dealathons divisions.');
      if (!list ($Data, $DURL) = GetWebData('http://www.dealathons.com/?city=13')) { return SysLogIt('Error getting web data.', StatusError); }
      
      if ($DebugMode) file_put_contents("DealathonsCity.txt", $Data);
      
      SysLogIt('Parsing HTML.');
      
      $DataArray = FindSubData($Data, 'class="city_select"', '>', '</select', false);
      if ($DataArray === false) return SysLogIt("Couldn't find city content.", StatusError);
      
      //Large Centres
      $DataArray = explode('</option>', $DataArray);
      
      foreach ($DataArray as $DataItem) {
      
        if (stripos($DataItem, 'value=') !== false) {
      
          $ID = (stripos($DataItem, 'value=""') === false)?FindSubData($DataItem, 'value=', '"', '"', false):'13';
          $Name = substr($DataItem, stripos($DataItem, '>') + 1);
          
          if ($Name !== false && $ID !== false) {
          
            if (!CheckURL('Dealathons', $SID, $Name, $ID)) return false;
              
          }
          
        }
      
      }      
      
      return UpStatus('Dealathons', $SID);
        
    }
    
    public function GetDivisionData($SRID, $URL, $LID, $TZ, $Sub = false, $WebD = false) {
    
      global $DebugMode;
      
      list ($Data, $DURL) = GetWebData('http://www.dealathons.com/?city='.$URL);
      if ($Data === false) return array(SysLogIt('Error getting web data.', StatusError), null);
        
      if ($DebugMode) file_put_contents("Dealathons.txt", $Data);
    
      if ((stripos($Data, '/checkout/') === false) || (stripos($Data, 'Dealathons Is Coming') !== false) || (stripos($Data, 'No Deal Available') !== false)) return array(true, false);
          
      $NewDeal = array (
        "ID" => array(0, '/checkout/', '/product/', '/', true),
        "Title" => array('', 'class="cont_top_cntr"', '<p>', '</p>', false),
        "PriceSale" => array(0, 'class="price_value"', '$', '<', true),
        "PriceReg" => array(0, '>Value<', '$', '<', true),
        "Description" => array('', 'class="highlights"', '>', '<!', false),
        "Coords" => array('', 'maps.google.ca', ';ll=', '&', false, true),
        "StartDate" => array('', 'var startdate =', '"', '";', false),
        "EndDate" => array('', 'var targetdate =', '"', '";', false)
      );
                        
      if (!GetNewDealData($Data, $NewDeal)) return array(SysLogIt('Error finding key deal information.', StatusError), null);
    
      $StartDate = strtotime($NewDeal['StartDate'][0]);
      $EndDate = strtotime($NewDeal['EndDate'][0]);
      if ($EndDate <= time()) return array(SysLogIt('Could not determine deal end date.', StatusError), null);
              
      $VExpiry = FindSubData($Data, '"bottom_description"', 'Expires ', '<', false);
      if ($VExpiry !== false) $VExpiry = strtotime($VExpiry);
      if ($VExpiry === false) $VExpiry = 0;
              
      $Address = array();
      $AdrBlock = FindData($Data, '<address>', '</address>', 9, 0, false, 0, false);
      if ($AdrBlock !== false) {
        if (stripos($AdrBlock, '<p>&nbsp;</p>') === false) {
          if (stripos($AdrBlock, '<br />') !== false) {
            $AdrBlock = substr($AdrBlock, stripos($AdrBlock, '<br />')+6);
          } elseif (stripos($AdrBlock, '</p>') !== false) {
            $AdrBlock = substr($AdrBlock, stripos($AdrBlock, '</p>')+6);
          }
          $AdrBlock = array($AdrBlock);
        } else {
          $AdrBlock = explode('<p>&nbsp;</p>', $AdrBlock);
        }
        foreach ($AdrBlock as &$Adr) {
          $Adr = CleanHTML($Adr);
          if ($Adr != '') $Address[] = $Adr;
        }
      }
      if (count($Address) == 0) $Address[] = null;
      
      
      $Locations = array();
      
      if (count($Address) == 1) {
              
        if ($NewDeal['Coords'][0] !== false) {
          $Coords = explode(',', $NewDeal['Coords'][0]);
          if (($Coords === false) || (count($Coords) != 2)) $Coords = array(0, 0);
        } else {
          $Coords = array(0, 0);
        }
        $Locations[] = array($Address[0], $Coords[0], $Coords[1]);
        
      } else {
      
        foreach ($Address as $Adr) {
          $Locations[] = array($Adr, 0, 0);
        }
      
      }
      
      if (count($Locations) == 0) return array(SysLogIt("Could not decipher addresses for division with ID of ".$SRID.".", StatusError), null);
      
      $StoreName = false;
      if (stripos($Data, 'pp-headline-address') !== false) {
        $StoreName = FindSubData($Data, 'pp-headline-address', '>', '<', false);
      } elseif (stripos($Data, '<address>') !== false) {
        $StoreName = FindData($Data, '<address>', '</address>', 9, 0, false, 0, false);
        if ($StoreName !== false) {
          if (stripos($StoreName, '<span') === false) {
            $StoreName = false;
          } else {
            $StoreName = FindData($StoreName, '<span', '</span>', 5, 0, false, 0, false);
            if ($StoreName !== false) {
              $StoreName = substr($StoreName, stripos($StoreName, '>')+1);
              $StoreName = CleanHTML($StoreName);
            }
          }
        }
      }
        
      if ($StoreName === false) return array(SysLogIt("Could not determine store name for division with ID of ".$SRID.".", StatusError), null);
        
      $RData = array();
      
      $RData['DRID'] = $NewDeal['ID'][0];
      $RData['Title'] = $NewDeal['Title'][0];
      $RData['Descr'] = $NewDeal['Description'][0];
      $RData['URL'] = 'http://www.dealathons.com/?city='.$URL;
      $RData['Price'] = (double)$NewDeal['PriceSale'][0];
      $RData['Value'] = (double)$NewDeal['PriceReg'][0];
      $RData['Status'] = 0;
      
      $RData['SDate'] = $StartDate;
      $RData['EDate'] = $EndDate;
      $RData['VDate'] = $VExpiry;
      
      $RData['StoreName'] = $StoreName;
      $RData['Website'] = null;
      $RData['Locations'] = $Locations;
      
      return array(true, array($RData));
    
    }
    
  }

?>
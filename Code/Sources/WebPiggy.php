<?php

  class WebPiggy {

    public function GetNewDivisions($SID) {
    
      global $DebugMode;
    
      SysLogIt('Checking for new WebPiggy divisions.');
      if (!list ($Data, $DURL) = GetWebData('https://www.webpiggy.com/')) { return SysLogIt('Error getting web data.', StatusError); }
      
      if ($DebugMode) file_put_contents("WebPiggyCity.txt", $Data);
      
      SysLogIt('Parsing HTML.');
      
      $DataArray = FindSubData($Data, 'id="city_list"', '>', '</ul>', false);
      if ($DataArray === false) return SysLogIt("Couldn't find city content.", StatusError);
      
      $DataArray = explode('/a></li>', $DataArray);
      
      foreach ($DataArray as $DataItem) {
      
        if (stripos($DataItem, 'a href="/') !== false) {
      
          $ID = FindSubData($DataItem, 'a href="', '/', '"', false);
          $Name = FindSubData($DataItem, 'a href="', '>', '<', false);
          
          if ($Name !== false && $ID !== false) {
          
            if (!CheckURL('WebPiggy', $SID, $Name, $ID)) return false;
              
          }
          
        }
      
      }
      
      return UpStatus('WebPiggy', $SID);
        
    }
    
    public function GetDivisionData($SRID, $URL, $LID, $TZ, $Sub = false, $WebD = false) {
    
      global $DebugMode;
      
      if ($Sub) {
        list ($Data, $DURL) = GetWebData('https://www.webpiggy.com/deals/side_deal/'.$URL, null);
      } else {
        list ($Data, $DURL) = GetWebData('https://www.webpiggy.com/'.$URL, null);
      }
      
      if ($Data === false) return array(SysLogIt('Error getting web data.', StatusError), null);
        
      if ($DebugMode) file_put_contents("WebPiggy.txt", $Data);
      
      if (stripos($Data, 'id="deal_action"') === false) return array(true, false);    
    
      $NewDeal = array (
        "ID" => array(0, 'id="deal_action"', '/deals/buy/', '"', true),
        "Title" => array('', 'id="centre_title">', '">', '<', false),
        "Website1" => array('', 'deal_desc_col2', '<br/><a href="http://', '"', false, true),
        "Website2" => array('', 'deal_desc_col2', '<a href="', '"target="_blank"', false, true),
        "PriceSale" => array(0, 'id="deal_price"', '$', '<', true),
        "PriceReg" => array(0, '<li>Value', '$', '<', true),
        "StoreName1" => array('', '<br/><a href="http://', '>', '<', false, true),
        "StoreName2" => array('', 'id="deal_desc_col2"', '"_blank">', '<', false, true),
        "Description" => array('', 'deal_desc_col2', '<p>', '</p>', false),
        "Ends" => array('', '<p>', 'var dthen = new Date("', '");', false),
        "Address2" => array('', 'maps.google.ca', '&hnear=', '&', false, true),
        "Address1" => array('', 'maps.google.ca', '&q=', '&', false, true)
      );
                        
      if (!GetNewDealData($Data, $NewDeal)) return array(SysLogIt('Error finding key deal information.', StatusError), null);
    
      $StartDate = time();
      $EndDate = strtotime($NewDeal['Ends'][0]);
      if ($EndDate <= time()) return array(SysLogIt('Could not determine deal end date.', StatusError), null);
      
      $VExpiry = FindData($Data, 'Expiry ', '.', 7, 0, false, 0, false);
      if ($VExpiry !== false) $VExpiry = strtotime($VExpiry);
      if ($VExpiry === false) $VExpiry = 0;
              
      $Address = '';
      if ($NewDeal['Address1'][0] !== false) $Address = CleanHTML(str_replace('+', ' ', $NewDeal['Address1'][0]));
      if (($Address == '') && ($NewDeal['Address2'][0] !== false)) $Address = CleanHTML(str_replace('+', ' ', $NewDeal['Address2'][0]));
      if ($Address == '') $Address = null;
      
      $Website = null;
      if ($NewDeal['Website1'][0] !== false) {
        $Website = 'http://'.$NewDeal['Website1'][0];
      } elseif ($NewDeal['Website2'][0] !== false) {
        $Website = 'http://'.$NewDeal['Website2'][0];
      }
      if ($Website == '') $Website = null;
      
      $StoreName = '';
      if ($NewDeal['StoreName1'][0] !== false) {
        $StoreName = 'http://'.$NewDeal['StoreName1'][0];
      } elseif ($NewDeal['StoreName2'][0] !== false) {
        $StoreName = 'http://'.$NewDeal['StoreName2'][0];
      }
      if ($StoreName == '') return array(SysLogIt('Could not determine store name.', StatusError), null);
      
      $MoreDeals = array();

      $Start = stripos($Data, 'id="side_deal"');
      if ($Start !== false) {
        $Start = stripos($Data, 'class="deal_talk_content"', $Start);
        while ($Start !== false) {
          $New = FindData($Data, 'href="/deals/side_deal/', '"', 23, 0, true, $Start, false);
          if (($New !== false) && ($New > 0)) $MoreDeals[] = $New;
          $Start = stripos($Data, 'class="deal_talk_content"', $Start + 20);        
        }
      }

      $RData = array();
      
      $RData['DRID'] = $NewDeal['ID'][0];
      $RData['Title'] = $NewDeal['Title'][0];
      $RData['Descr'] = $NewDeal['Description'][0];
      $RData['URL'] = 'https://www.webpiggy.com/deals/side_deal/'.$NewDeal['ID'][0];
      $RData['Price'] = (double)$NewDeal['PriceSale'][0];
      $RData['Value'] = (double)$NewDeal['PriceReg'][0];
      $RData['Status'] = 0;
      
      $RData['SDate'] = $StartDate;
      $RData['EDate'] = $EndDate;
      $RData['VDate'] = $VExpiry;
      
      $RData['StoreName'] = $StoreName;
      $RData['Website'] = $Website;
      $RData['Locations'] = array(array($Address, 0, 0));
      
      if (count($MoreDeals) > 0) $RData['Deals'] = $MoreDeals;
      
      return array(true, array($RData));

    }

  }

?>
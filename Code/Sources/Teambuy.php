<?php

  class Teambuy {

    public function GetNewDivisions($SID) {
    
      SysLogIt('Checking for new Teambuy divisions.');
      if (!list ($Data, $DURL) = GetWebData('http://www.teambuy.ca/')) { return SysLogIt('Error getting web data.', StatusError); }
      
      //file_put_contents("Teambuy.txt", $Data);
      
      SysLogIt('Parsing HTML.');
      
      $DataArray = FindSubData($Data, 'id="cityBoxFancyAP"', '<table>', '</table>', false);
      if ($DataArray === false) return SysLogIt("Couldn't find city content.", StatusError);
      
      //Large Centres
      $DataArray = explode('</td>', $DataArray);
      
      foreach ($DataArray as $DataItem) {
      
        if (stripos($DataItem, 'otherCity') !== false) {
      
          $ID = FindData($DataItem, 'change-to-', '"', 10, 0, false, 0, false);
          $Name = FindSubData($DataItem, '<span><a', '>', '<', false);
          
          if ($Name !== false && $ID !== false) {
          
            if (!CheckURL('Teambuy', $SID, $Name, $ID)) return false;
              
          }
          
        }
      
      }      
      
      return UpStatus('Teambuy', $SID);
        
    }
    
    public function GetDivisionData($SRID, $URL, $LID, $TZ, $Sub = false, $WebD = false) {
    
      global $DebugMode;

      if ($Sub) {
        list ($Data, $DURL) = GetWebData('http://www.teambuy.ca/'.$URL, null, 'language=english');
      } else {
        list ($Data, $DURL) = GetWebData('http://www.teambuy.ca/'.$URL.'/all-buys/local', null, 'language=english');
      }
      if ($Data === false) return array(SysLogIt('Error getting web data.', StatusError), null);
      
      if ($DebugMode) file_put_contents("Teambuy.txt", $Data);
      
      if ($Sub) {
      
        //if (stripos($Data, '/buy/') === false) return array(true, false);
        
        $Prices = FindSubData($Data, 'PRICE:', 'class="ddValues">', '<', false);
        if ($Prices == 'Varies') return array(SysLogIt('Deal has variable pricing. Skipping...', StatusInfo), false);
          
        $NewDeal = array (
          "City" => array('', 'og:url', 'content="http://www.teambuy.ca/', '/', false),
          "ID" => array(0, 'og:url" content="http://www.teambuy.ca/', '/', '/', true),
          "Title" => array('', '<title>', '| ', '<', false),
          "PriceSale" => array(0, 'PRICE:', '$', '<', true),
          "PriceReg" => array(0, 'VALUE:', '$', '<', true),
          "StoreName" => array('', 'id="companyName"', '>', '<', false),
          "Website" => array('', 'id="companyWebsite"', 'href="', '"', false, true),
          "Description" => array('', 'id="writeUpContent"', '<p>', '</p>', false),
          "EndDate" => array('', 'var futuredate', '"timercontainer", "', '")', false)
        );
        
        if (!GetNewDealData($Data, $NewDeal)) return array(SysLogIt('Error finding key deal information.', StatusError), null);
                          
        $StartDate = time();
        $EndDate = strtotime($NewDeal['EndDate'][0].' 12:00 AM');
        if ($EndDate === false) return array(SysLogIt('Could not determine deal end date.', StatusError), null);
        
        //$EndDate += -(((int)$TZ + 5) * 60 * 60);
        //All teambuys seem to finish at 12AM EST
              
        $VExpiry = FindData($Data, 'id="dealExpiryWide">Expires: ', '<', 29, 0, false, 0, false);
        $VExpiry = ($VExpiry === false)? 0:strtotime($VExpiry);
        if ($VExpiry === false) $VExpiry = 0;
        
        $Locations = array();
        $Start = stripos($Data, '&geocode');
        while ($Start !== false) {
          $New = FindData($Data, "&hnear=", '&', 7, 0, false, $Start, false);
          if ($New !== false) $Locations[] = array(CleanHTML(urldecode($New)), 0, 0);
          $Start = stripos($Data, "&geocode", $Start + 20);
        }
        if (count($Locations) == 0) $Locations[] = array(null, 0, 0);
        
        $RData = array();
        
        $RData['DRID'] = (int)$NewDeal['ID'][0];
        $RData['Title'] = $NewDeal['Title'][0];
        $RData['Descr'] = $NewDeal['Description'][0];
        $RData['URL'] = 'http://www.teambuy.ca/'.$NewDeal['City'][0].'/referral/92yyby6y/'.(int)$NewDeal['ID'][0].'/';
        $RData['Price'] = (double)$NewDeal['PriceSale'][0];
        $RData['Value'] = (double)$NewDeal['PriceReg'][0];
        $RData['Status'] = 0;
        
        $RData['SDate'] = $StartDate;
        $RData['EDate'] = $EndDate;
        $RData['VDate'] = $VExpiry;
        
        $RData['StoreName'] = $NewDeal['StoreName'][0];
        $RData['Website'] = $NewDeal['Website'][0];
        $RData['Locations'] = $Locations;
                
        return array(true, array($RData));
        
        
      } else {
      
        $RData = array();
      
        $RData['GW'] = true;
        $RData['EDate'] = 0;
            
        $MoreDeals = array();

        $Last = '';
        $Start = stripos($Data, 'deal_id-');
        while ($Start !== false) {
          $New = FindSubData($Data, 'deal_id-', 'href="http://www.teambuy.ca', '"', false, $Start, false);
          if (($New !== false) && ($New != $Last)) {
            $MoreDeals[] = $New;
            $Last = $New;
          }
          $Start = stripos($Data, 'deal_id-', $Start + 20);
        }
          
        if (count($MoreDeals) > 0) $RData['Deals'] = $MoreDeals;
        
        return array(true, array($RData));

      }

    }
    
  }

?>
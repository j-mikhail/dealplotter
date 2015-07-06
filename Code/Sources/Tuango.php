<?php

  class Tuango {

    public function GetNewDivisions($SID) {
    
      global $DebugMode;
    
      SysLogIt('Checking for new Tuango divisions.');
      if (!list ($Data, $DURL) = GetWebData('http://www.tuango.ca/en/deal/montreal/')) { return SysLogIt('Error getting web data.', StatusError); }
      
      if ($DebugMode) file_put_contents("TuangoCity.txt", $Data);
      
      SysLogIt('Parsing HTML.');

      $DataArray = FindSubData($Data, 'id="merchantCategoriesDropDownBox"', '>', '<div style', false);
      if ($DataArray === false) return SysLogIt("Couldn't find city content.", StatusError);

      //Parse contents of city box
      $DataArray = explode('</div>', $DataArray);
      
      foreach ($DataArray as $DataItem) {
      
        if (stripos($DataItem, 'selectCategory(') !== false) {
      
          $ID = FindSubData($DataItem, 'selectCategory(', ", '", "'", false);
          $Name = FindSubData($DataItem, 'class="categoryNameV2"', '>', '<', false);
          
          if ($Name !== false && $ID !== false) {
          
            if (!CheckURL('Tuango', $SID, $Name, $ID)) return false;
              
          }
          
        }
      
      }  
      
      return UpStatus('Tuango', $SID);
      
        
    }
    
    public function GetDivisionData($SRID, $URL, $LID, $TZ, $Sub = false, $WebD = false) {
    
      global $DebugMode;
      
      list ($Data, $DURL) = GetWebData('http://www.tuango.ca/en/deal/'.$URL);
      if ($Data === false) return array(SysLogIt('Error getting web data.', StatusError), null);
      
      if ($DebugMode) file_put_contents("Tuango.txt", 'http://www.tuango.ca/en/deal/'.$URL.chr(13).chr(10).chr(13).chr(10).$Data);
      
      if ($Sub) {
      
        if (stripos($Data, 'purchase?') === false) return array(true, false);
        
        $NewDeal = array (
          "ID" => array('', 'class="dealName"', '/en/', '"', false),
          "Title" => array('', 'class="dealName"', '>', '</p>', false),
          "PriceSale" => array(0, 'class="dealPrice"', '$', '<', true),
          "PriceReg" => array(0, 'class="infoNumbers"', '$', '<', true),
          "StoreName" => array('', 'companyName', '<h2>', '</h2>', false),
          "Address" => array('', 'companyName', '</h2>', '<', false, true),
          "Website" => array('', 'companyName', 'href="', '"', false, true),
          "Description" => array('', 'companyDetails', '<p>', '</div>', false),
          "EndDate" => array('', 'TargetDate =', '"', '"', false)
        );
        
        if (!GetNewDealData($Data, $NewDeal)) return array(SysLogIt('Error finding key deal information.', StatusError), null);
        
        $StartDate = time();
        $EndDate = strtotime($NewDeal['EndDate'][0]);
        if ($EndDate === false) return array(SysLogIt('Could not determine deal end date.', StatusError), null);
        
        $VExpiry = FindSubData($Data, '/ Ends', ' ', '<', false);
        if ($VExpiry !== false) $VExpiry = strtotime($VExpiry);
        if ($VExpiry === false) $VExpiry = 0;
        
        $Website = null;
        if ($NewDeal['Website'][0] !== false) $Website = $NewDeal['Website'][0];      
        
        $Locations = array();
        $Address = null;
        if ($NewDeal['Address'][0] !== false) $Address = $NewDeal['Address'][0];
        if (stripos($Address, 'website') !== false) {
          $Locations[] = array(null, -1, -1);
        } else {
          $Locations[] = array($Address, 0, 0);
        }
         
        $TitleFR = false;
        list ($Data, $DURL) = GetWebData('http://www.tuango.ca/fr/'.$NewDeal['ID'][0]);
        if ($Data !== false) {
          $TitleFR = FindSubData($Data, 'class="dealName"', '</span>', '</p>', false);
          if ($TitleFR !== false) $TitleFR = CleanHTML($TitleFR);
        }
        $RData = array();
        
        $RData['DRID'] = $NewDeal['ID'][0];
        $RData['Title'] = CleanHTML(str_ireplace("Today's Deal:", "", $NewDeal['Title'][0]));
        if (($TitleFR !== false) && ($TitleFR != '')) $RData['Title-fr'] = $TitleFR;
        $RData['Descr'] = $NewDeal['Description'][0];
        $RData['URL'] = 'http://www.tuango.ca/en/'.$NewDeal['ID'][0];
        $RData['Price'] = (double)$NewDeal['PriceSale'][0];
        $RData['Value'] = (double)$NewDeal['PriceReg'][0];
        $RData['Status'] = 0;
        
        $RData['SDate'] = $StartDate;
        $RData['EDate'] = $EndDate;
        $RData['VDate'] = $VExpiry;
        
        $RData['StoreName'] = $NewDeal['StoreName'][0];
        $RData['Website'] = $Website;
        $RData['Locations'] = $Locations;
        
        return array(true, array($RData));
        
      } else {
      
        $RData = array();
      
        $RData['GW'] = true;
        $RData['EDate'] = 0;
      
        $MoreDeals = array();
        $Start = stripos($Data, 'class="gbItemInfo"');
        while ($Start !== false) {
          $ID = FindData($Data, "div", "href='/en/deal", "'", false, $Start, false);
          if ($ID !== false) $MoreDeals[] = $ID;
          $Start = stripos($Data, 'class="gbItemInfo"', $Start + 20);
        }

        if (count($MoreDeals) > 0) $RData['Deals'] = $MoreDeals;
        
        return array(true, array($RData));        
        
      }
    
    }
    
  }

?>
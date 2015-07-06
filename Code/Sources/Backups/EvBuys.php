<?php

  class EvBuys {

    public function GetNewDivisions($SID) {
    
      global $DebugMode;
    
      SysLogIt('Checking for new EverybodyBuys divisions.');
      if (!list ($Data, $DURL) = GetWebData('http://everybodybuys.com/')) { return SysLogIt('Error getting web data.', StatusError); }
      
      if ($DebugMode) file_put_contents("EverybodyBuysCity.txt", $Data);
      
      SysLogIt('Parsing HTML.');
      
      $DataArray = FindSubData($Data, 'id="js-change-city"', '>', '</select>', false);
      if ($DataArray === false) return SysLogIt("Couldn't find city content.", StatusError);
      
      $DataArray = explode('/option', $DataArray);
      
      foreach ($DataArray as $DataItem) {
      
        if (stripos($DataItem, 'value=') !== false) { //IF no value=, strip tag
      
          $ID = FindSubData($DataItem, 'value=', "'", "'", false);
          $Name = FindSubData($DataItem, 'value=', '>', '<', false);
          
          if (($Name !== false) && ($ID !== false)) {
                     
            if (stripos($Name, '(') !== false) $Name = trim(substr($Name, 0, stripos($Name, '(')));
            if (!CheckURL('EverybodyBuys', $SID, $Name, $ID)) return false;
              
          }
          
        }
      
      }
      
      return UpStatus('EverybodyBuys', $SID);
        
    }
    
    public function GetDivisionData($SRID, $URL, $LID, $TZ, $Sub = false, $WebD = false) {
    
      global $DebugMode;
      
      list ($Data, $DURL) = GetWebData('http://www.everybodybuys.com/'.$URL);
      if ($Data === false) return array(SysLogIt('Error getting web data.', StatusError), null);
        
      if ($DebugMode) file_put_contents("EverybodyBuys.txt", $Data);
      
      if (stripos($Data, 'class="subscriptions-content-form round-10 clearfix"') !== false) return array(true, false); //IF there is no deal, return false


      $NewDeal = array (
        "ID" => array('', 'class="title"', 'href="', '"', false),
        "Title" => array('', 'class="title"', 'title="', '"', false), //Gets Title Tag from <h2>
        "Website" => array('', chr(13).chr(10).'                        <span class="ak-subtitle"', 'href="', '"', false, true),
        "Address" => array('', 'id="map_adress"', '>', '<br />', false), //Not #
        "PriceReg" => array('', 'class="ak-small-info ak-reg"', '$', '<', false), //reg: $240.00 -- needs cleaning
        "PriceSale" => array(0, 'class="ak-value ak-num"', '>', '<', true),
        "StoreName" => array('', chr(13).chr(10).'                      <div class="ak-subside2">', 'class="ak-subtitle">', "<", false),
        "EndDate" => array(0, 'ak-value ak-time-left', 'js-time hide">', '<', true),
        "Description" => array('', 'Daily Write Up', '<p>', 'clear: left', false) 
      );

	
      
      if (!GetNewDealData($Data, $NewDeal)) return array(SysLogIt('Error finding key deal information.', StatusError), null);
      
      if ((int)$NewDeal['EndDate'][0] <= 0) return array(true, false);
      
      $StartDate = time();
      $EndDate = $StartDate + (int)$NewDeal['EndDate'][0];
      
      if ($EndDate <= time()) return array(SysLogIt('Could not determine deal end date.', StatusError), null);
      //Only operating in TO, so timezone always EST
      
      //$DateParts = explode(',', $DateEnds);
      //$EndDate = mktime((int)$DateParts[2], (int)$DateParts[3], (int)$DateParts[4], (int)$DateParts[0], (int)$DateParts[1], (int)date('Y'));
        
      //No Expiry, it will return 0 anyway
	    $VExpiry = FindData($Data, 'Expiries', '</span>', 8, 0, false, 0, false);
      $VExpiry = ($VExpiry === false)? 0:strtotime($VExpiry);
      if ($VExpiry === false) $VExpiry = 0;
   
	    $Address = null;
      if ($NewDeal['Address'][0] !== false) $Address = CleanHTML($NewDeal['Address'][0]);
      if ($Address == '') $Address = null;
      
      $Website = null;
      if ($NewDeal['Website'][0] !== false) $Website = $NewDeal['Website'][0];      
	
	    //Need function prototype, would like to know what the function does with the needle. 
	
	    $MoreDeals = array();
      $Start = stripos($Data, "<div style='position: relative;'>");
      while ($Start !== false) {
        $ID = FindData($Data, 'href="', '"', 6, 0, false, $Start, false); 
        if ($ID !== false) $MoreDeals[] = $ID;
        $Start = stripos($Data, "<div style='position: relative;'>", $Start + 20); //Not sure if I need an 'offset' here?? 
      }

            
      $RData = array();
      
      $RData['DRID'] = $NewDeal['ID'][0];
      $RData['Title'] = $NewDeal['Title'][0];
      $RData['Descr'] = $NewDeal['Description'][0];
      $RData['URL'] = 'http://www.everybodybuys.com'.$URL; //Does the dash from the end of URL get stripped?
	  
      $RData['Price'] = (double)$NewDeal['PriceSale'][0];
      $RData['Value'] = (double)$NewDeal['PriceReg'][0];
      $RData['Status'] = 0;
      
      $RData['SDate'] = $StartDate;
      $RData['EDate'] = $EndDate;
      $RData['VDate'] = $VExpiry;
      
      $RData['StoreName'] = $NewDeal['StoreName'][0];
      $RData['Website'] = $Website;
      $RData['Locations'] = array(array($Address, 0, 0));

      if (count($MoreDeals) > 0) $RData['Deals'] = $MoreDeals;
      
      return array(true, $RData);

    }
    
  }

?>
<?php


class DealOn {
	
	public function GetNewDivisions($SID) {

      global $DebugMode;

      SysLogIt('Checking for new DealOn divisions.');
      if (!list ($Data, $DURL) = GetWebData('http://www.dealon.com/')) { return SysLogIt('Error getting web data.', StatusError); }

      if ($DebugMode) file_put_contents("DealOn.txt", $Data);

      SysLogIt('Parsing HTML.');

      $DataArray = FindSubData($Data, 'id="city_id"', '>', '</select>', false); //Go to the 1st ending of Label
      if ($DataArray === false) return SysLogIt("Couldn't find city content.", StatusError);

      $DataArray = explode('/option', $DataArray); //explode by option

      foreach ($DataArray as $DataItem) {

        if (stripos($DataItem, 'value=') !== false) {

          $ID = FindSubData($DataItem, 'value=', '"', '"', false); //Get Sublink from URL
          $Name = FindSubData($DataItem, 'value=', '>', '<', false); //Get Name of the City

          if (($Name !== false) && ($ID !== false)) {

            if (!CheckURL('DealOn', $SID, $Name, $ID)) return false;

          }

        }

      }

      return UpStatus('DealOn', $SID);

    }

	public function GetDivisionData($SRID, $URL, $LID, $TZ, $Sub = false, $WebD = false) {
    
      global $DebugMode;

	  //There is an automatic forward to NYC, http://www.dealon.com/new-york/deal/today/
	  //What does $Sub look like, and $URL
	  //I am still having issues with chr(13) etc. Ask Jonathan
	  //Lets go over the date conversion, +1 with JavaScript needs checking

      if ($Sub) {
        list ($Data, $DURL) = GetWebData('http://www.dealon.com/'.$URL); 
      } else {
        list ($Data, $DURL) = GetWebData('http://www.dealon.com/'.$URL.'/deal/today');
      }
	
      if ($Data === false) return array(SysLogIt('Error getting web data.', StatusError), null);
      
      if ($DebugMode) file_put_contents("DealOn.txt", "http://www.dealon.com/".$URL.chr(13).chr(10).chr(13).chr(10).$Data);
      
      if (stripos($Data, 'class="page-title"') !== false) return array(true, false);
      
      $NewDeal = array (
        "ID" => array('', 'class="deal-meter-buy"', 'href="/user/buy', '"', false),
        "Title" => array('', 'id="deal-title"', '>', '<', false),
        "Website" => array('', 'class="deal-details-right-column"', 'href="', '"', false, true),
        "PriceSale" => array(0, 'class="deal-meter-buy"', '$', '<', true),
        "PriceReg" => array(0, 'class="deal-discount"', '$', '<', true),
        "StoreName" => array('', '<h2>Company Information</h2>', '>', '<', false),
        "Description" => array('', '<h2>About This Deal</h2>', '<p>', '</div>', false),
        "Address" => array('', '<h2>Company Information</h2>', '<br/>', '<a', false, true), 
        "EndDate" => array('', "('#countdown')", 'UTCDate(', ')', false)
      );
      
      if (!GetNewDealData($Data, $NewDeal)) return array(SysLogIt('Error finding key deal information.', StatusError), null);
                      
      $StartDate = time();
      
	    list ($DateO, $DateY, $DateM, $DateD, $DateH, $DateN, $DateS, $DateI) = explode(',', $NewDeal['EndDate'][0]);
      $EndDate = mktime((int)$DateH + (-5 - (int)$DateO), (int)$DateN, (int)$DateS, (int)$DateM+1, (int)$DateD, (int)$DateY); //Need to Add + 1, because JavaScript counts 0 to 11
	    //Based on their plugin usage http://keith-wood.name/countdown.html
		
      if ($EndDate <= time()) return array(SysLogIt('Could not determine deal end date.', StatusError), null);


      $VExpiry = FindData($Data, 'Expiration Date:', '<', 17, 0, false, 0, false);
      $VExpiry = ($VExpiry === false)? 0:strtotime($VExpiry);
      if ($VExpiry === false) $VExpiry = 0;
      
      $Website = null;
      if ($NewDeal['Website'][0] !== false) $Website = $NewDeal['Website'][0];
      if ($Website == '') $Website = null;
      
      $Coords = array(0, 0);
      $Address = null;
      if ($NewDeal['Address'][0] !== false) $Address = CleanHTML($NewDeal['Address'][0]);
      if ($Address == '') $Address = null;
      if ($Address == 'Visit Online:') {
        $Address = null;
        $Coords = array(-1, -1);
      }
      
      $MoreDeals = array();
      $Start = stripos($Data, 'class="bonus-deals"');
      while ($Start !== false) {
        $ID = FindData($Data, 'href="', '"', 6, 0, false, $Start, false); //What is 24, 0 for??
        if ($ID !== false) $MoreDeals[] = $ID;
        $Start = stripos($Data, '											<h3>', $Start + 20);
      }      

      $RData = array();
      
      $RData['DRID']  = $NewDeal['ID'][0];
      $RData['Title'] = $NewDeal['Title'][0];
      $RData['Descr'] = $NewDeal['Description'][0];
      if ($Sub) {
        $RData['URL']   = 'http://www.dealon.com'.$URL;
      } else {
        $RData['URL']   = 'http://www.dealon.com/'.$URL.$NewDeal['ID'][0];
      }
      $RData['Price'] = (double)$NewDeal['PriceSale'][0];
      $RData['Value'] = (double)$NewDeal['PriceReg'][0];
      $RData['Status'] = 0;
      
      $RData['SDate'] = $StartDate;
      $RData['EDate'] = $EndDate;
      $RData['VDate'] = $VExpiry;
      
      $RData['StoreName'] = $NewDeal['StoreName'][0];
      $RData['Website'] = $Website;
      $RData['Locations'] = array(array($Address, $Coords[0], $Coords[1]));
      
      if (count($MoreDeals) > 0) $RData['Deals'] = $MoreDeals;
      
      return array(true, array($RData));
      
    }
    
  }

?>
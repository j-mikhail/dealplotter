<?php


class Maddeal {
	
	public function GetNewDivisions($SID) {

      global $DebugMode;

      SysLogIt('Checking for new Maddeal divisions.');
      if (!list ($Data, $DURL) = GetWebData('http://www.maddeal.com')) { return SysLogIt('Error getting web data.', StatusError); }

      if ($DebugMode) file_put_contents("MadDealCity.txt", $Data);

      SysLogIt('Parsing HTML.');

      $DataArray = FindSubData($Data, 'id="ctl00_ddlCityPopup"', '>', '</select>', false); //Go to the 1st ending of Label
      if ($DataArray === false) return SysLogIt("Couldn't find city content.", StatusError);

      $DataArray = explode('/option', $DataArray); //explode by option

      foreach ($DataArray as $DataItem) {

        if (stripos($DataItem, 'OptionGroup=') !== false) {

          $ID = FindSubData($DataItem, 'value=', '"', '"', false); //Get Sublink from URL
          $Name = FindSubData($DataItem, 'OptionGroup=', '>', '<', false); //Get Name of the City

          if (($Name !== false) && ($ID !== false)) {

            if (!CheckURL('Maddeal', $SID, $Name, $ID)) return false;

          }

        }

      }

      return UpStatus('Maddeal', $SID);

    }

	public function GetDivisionData($SRID, $URL, $LID, $TZ, $Sub = false, $WebD = false) {
    
      global $DebugMode;

      if ($Sub) {
        list ($Data, $DURL) = GetWebData('http://www.maddeal.com'.$URL);
      } else {
        list ($Data, $DURL) = GetWebData('http://www.maddeal.com'.$URL.'/');
      }
      if ($Data === false) return array(SysLogIt('Error getting web data.', StatusError), null);
      
      if ($DebugMode) file_put_contents("Maddeal.txt", "http://www.maddeal.com".$URL.chr(13).chr(10).chr(13).chr(10).$Data);
      
      if (stripos($Data, 'NoDeal.jpg') !== false) return array(true, false);
      
      $NewDeal = array (
        "ID" => array(0, '?did', '=', '"', true),
        "Title" => array('', 'class="boldheaderarial"', '>', '<', false),
        "Website" => array('', 'id="ctl00_cphBody_busWebsite"', 'href="', '"', false, true),
        "PriceSale" => array(0, 'id="ctl00_cphBody_dealPrice"', '>', '<', true),
        "PriceReg" => array('', 'id="ctl00_cphBody_dealValue"', '>', '<', false),
        "StoreName" => array('', 'id="ctl00_cphBody_busName"', '>', '<', false),
        "Description" => array('', 'id="ctl00_cphBody_decription"', '<p>', '<br />', false),
        "Address" => array('', 'id="ctl00_cphBody_busLocation"><a', '>', '</a>', false, true), 
		    "Coords" => array('', 'id="ctl00_cphBody_busLocation"', '(', ')', false, true), //Pulls 43.690736,-79.398685
        "EndDate" => array('', 'id="ctl00_cphBody_remaining"', '>', '<', false) //Pull in HH:MM:SS
      );
      
      if (!GetNewDealData($Data, $NewDeal)) return array(SysLogIt('Error finding key deal information.', StatusError), null);
                      
      $StartDate = time();
      $EndDate = time();
      //Comming in HH:MM:SS
      list($hours,$minutes,$seconds) = explode(':', $NewDeal['EndDate'][0]);
		
      $EndDate += (int)$hours * 60 * 60; //Hours
      $EndDate += (int)$minutes * 60; //Minutes
      $EndDate += (int)$seconds; //Seconds
		
      if ($EndDate <= time()) return array(SysLogIt('Could not determine deal end date.', StatusError), null);


      $VExpiry = FindData($Data, chr(9).chr(9).'Expires', '<', 9, 0, false, 0, false);
      $VExpiry = ($VExpiry === false)? 0:strtotime($VExpiry);
      if ($VExpiry === false) $VExpiry = 0;
      
      $Website = null;
      if ($NewDeal['Website'][0] !== false) $Website = $NewDeal['Website'][0];
      
      $Address = null;
      if ($NewDeal['Address'][0] !== false) $Address = CleanHTML($NewDeal['Address'][0]);
      if ($Address == '') $Address = null;
      
      $Coords = array(0, 0);
      if ($NewDeal['Coords'][0] !== false) $Coords = explode(',', $NewDeal['Coords'][0]);
      if (($Coords === false) || (count($Coords) != 2)) $Coords = array(0, 0);      
      
      $MoreDeals = array();
      $Start = stripos($Data, 'id="ctl00_cphBodyLeft_divLeft"');
      while ($Start !== false) {
        $ID = FindData($Data, "document.location.href='", "'", 24, 0, false, $Start, false);
        if ($ID !== false) $MoreDeals[] = $ID;
        $Start = stripos($Data, 'id="ctl00_cphBodyLeft_divLeft"', $Start + 20);
      }      
      
      $RData = array();
      
      $RData['DRID'] = (int)$NewDeal['ID'][0];
      $RData['Title'] = $NewDeal['Title'][0];
      $RData['Descr'] = $NewDeal['Description'][0];
      if ($Sub) {
        $RData['URL'] = 'http://www.maddeal.com'.$URL.'&q=P22932';
      } else {
        $RData['URL'] = 'http://www.maddeal.com'.$URL.'?did='.$NewDeal['ID'][0].'&q=P22932'; //http://www.maddeal.com/Canada/ON/Toronto/?did=216 
      }
      $RData['Price'] = (double)$NewDeal['PriceSale'][0];
      $RData['Value'] = (double)substr($NewDeal['PriceReg'][0], 1);
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
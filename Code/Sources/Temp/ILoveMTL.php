<?php

  class ILoveMTL {

    public function GetNewDivisions($SID) {
    
      CheckURL('ILoveMTL', $SID, 'Montreal', 'Montreal');      
      return UpStatus('ILoveMTL', $SID);
        
    }
    
    public function GetDivisionData($SRID, $URL, $LID, $TZ, $Sub = false, $WebD = false) {
    
      global $DebugMode;
      
      if ($Sub) {
        list ($Data, $DURL) = GetWebData('http://www.ilovemtl.ca/en/'.$URL);
      } else {
        list ($Data, $DURL) = GetWebData('http://www.ilovemtl.ca/en/');
      }
      
      if ($Data === false) return array(SysLogIt('Error getting web data.', StatusError), null);
        
      if ($DebugMode) file_put_contents("ILoveMTL.txt", $Data);
    
      if (stripos($Data, '/cart/') === false) return array(true, false);
          
      if ($WebD) {
      
        $NewDeal = array (
          "ID" => array('', 'og:url', 'content="http://www.ilovemtl.ca/en/deal/', '"', false),
          "Title" => array('', 'og:title', 'content="', '"', false),
          "PriceSale" => array(0, 'id="promo-price"', '<span>', '$', true),
          "PriceReg" => array(0, 'id="price-details ta-center fs-16"', 'Value', '$', true),
          "StoreName" => array('', 'class="location-name unone b open-map"', '>', '<', false),
          "Description" => array('', 'About this deal', '</div>', '</div>', false),
          "EndDate" => array('', 'var endtime =', 'new Date(', ')', false)
        );

      } else {
      
        $NewDeal = array (
          "ID" => array('', 'og:url', 'content="http://www.ilovemtl.ca/en/deal/', '"', false),
          "Title" => array('', '"promo-title"', '</span>', '</div>', false),
          "PriceSale" => array(0, 'id="promo-price"', '<span>', '$', true),
          "PriceReg" => array(0, 'class="price-details', '>Value', '$', true),
          "StoreName" => array('', 'class="location-name', '>', '<', false),
          "Description" => array('', 'About this deal', '<p>', '</div>', false),
          "EndDate" => array('', 'var endtime =', 'new Date(', ')', false)
        );
      
      }
                        
      if (!GetNewDealData($Data, $NewDeal)) return array(SysLogIt('Error finding key deal information.', StatusError), null);
    
      //Start, end dates
      $StartDate = time();

      $EndDate = 0;
      $DateParts = explode(',', $NewDeal['EndDate'][0]);
      if ($DateParts !== false) $EndDate = mktime((int)trim($DateParts[3]), (int)trim($DateParts[4]), (int)trim($DateParts[5]), (int)trim($DateParts[1])+1, (int)trim($DateParts[2]), (int)trim($DateParts[0]));
      if ($EndDate === false) $EndDate = 0;
      if ($EndDate <= time()) return array(SysLogIt('Could not determine deal end date.', StatusError), null);
            
      //Expiry Date
      $VExpiry = FindSubData($Data, '<li>Valid', ' to ', '</li>', false, 0, false);
      if (stripos($VExpiry, '.') !== false) $VExpiry = substr($VExpiry, 0, stripos($VExpiry, '.'));
      if ($VExpiry !== false) $VExpiry = strtotime($VExpiry);
      if ($VExpiry === false) $VExpiry = 0;
      
      $Website = null;
      //if ($NewDeal['Website'][0] !== false) $Website = $NewDeal['Website'][0];      
                    
      //Address(es)
      $Locations = array();
      
      $Start = stripos($Data, '&markers=');
      while ($Start !== false) {
      
        $Coords = FindData($Data, '&markers=', ',greena', 9, 0, false, $Start, false);
        if ($Coords !== false) $Coords = explode(',', $Coords);
        
        $Adr = FindData($Data, '&q=', '&ie=', 3, 0, false, $Start, false);
        $Adr = ($Adr === false)?null:urldecode($Adr);
        
        if (count($Coords) == 2) $Locations[] = array($Adr, $Coords[0], $Coords[1]);

        $Start = stripos($Data, '&markers=', $Start + 20);
      }

      //Side deals - No longer on the website

      $MoreDeals = array();
      if (!$Sub) {

        $Last = '';
        $Start = stripos($Data, 'class="sidedeal-img"');
        while ($Start !== false) {
          $New = FindData($Data, 'href="http://www.ilovemtl.ca/en/', '"', 32, 0, false, $Start, false);
          if (($New !== false) && ($New != $Last)) {
            $MoreDeals[] = array($New, (stripos($New, 'escapes') !== false));
            $Last = $New;
          }
          $Start = stripos($Data, 'class="sidedeal-img"', $Start + 20);
        }
        
      }
      
      //French title
      
      if ($WebD) {

        $TitleFR = '';
        list ($Data, $DURL) = GetWebData('http://www.ilovemtl.ca/fr/deal/'.$NewDeal['ID'][0]);
        if ($Data !== false) {
          $TitleFR = FindSubData($Data, 'og:title', 'content="', '"', false, 0, false);
          if ($TitleFR !== false) $TitleFR = CleanHTML($TitleFR);
        }
      
      
      } else {      
      
        $TitleFR = '';
        list ($Data, $DURL) = GetWebData('http://www.ilovemtl.ca/fr/deal/'.$NewDeal['ID'][0]);
        if ($Data !== false) {
          $TitleFR = FindSubData($Data, '"promo-title"', '</span>', '</div>', false, 0, false);
          if ($TitleFR !== false) $TitleFR = CleanHTML($TitleFR);
        }
      
      }
      
      //Save
        
      $RData = array();
      
      $RData['DRID'] = $NewDeal['ID'][0];
      $RData['Title'] = CleanHTML($NewDeal['Title'][0]);
      if (($TitleFR !== false) && ($TitleFR != '')) $RData['Title-fr'] = $TitleFR;
      $RData['Descr'] = $NewDeal['Description'][0];
      
      if ($WebD) {
        $RData['URL'] = 'http://www.ilovemtl.ca/en/escapes/'.$NewDeal['ID'][0];
      } else {
        $RData['URL'] = 'http://www.ilovemtl.ca/en/deal/'.$NewDeal['ID'][0];
      }
      
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
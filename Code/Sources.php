<?php

  define("DealSoldOut", 1);
  
  function WebUpDls() {
  
    global $Response;
  
    $Response->R = '<DIV CLASS="ttlw">Deal Update Results<HR></DIV><DIV CLASS="abs fulls fktbl flwa">';
    
    UpDls(true);

    $Response->R .= '</DIV>';
    $Response->S = true;
    $Response->Send();
  
  }
  
  function UpDls($ConsoleEcho = false) {
    
    /*
	global $EchoToConsole;
    $EchoToConsole = $ConsoleEcho;
    
    $StartTime = time();
    if (!ini_get('safe_mode')) set_time_limit(780);
    
    if (!SendVerifications($StartTime)) SysLogIt('Error sending out verification emails.', StatusError);
    if (!SendResets($StartTime)) SysLogIt('Error sending out password resets.', StatusError);
    if (!SendDigests($StartTime)) SysLogIt('Error sending out daily digests.', StatusError);
    
    list ($QR, $RS, $T) = QuerySet('SELECT DealSourceFileName FROM 4100_Deal_Sources WHERE DealSourceStatus & 1 = 1;');
    if ($QR > 0) {
    
      while ($DR = mysql_fetch_array($RS)) {
        try {
          require_once dirname(__FILE__).'/Sources/'.$DR['DealSourceFileName'].'.php';
        } catch(Exception $Exp) {
          SysLogIt('Error occured during deal update: '.Pacify($Exp->getMessage()).'.', StatusError);
          $EchoToConsole = false;
          return false;
        }
      }
    
      if (!CheckForNewDivisions($StartTime)) SysLogIt('Error checking for new divisions for source with filename of '.$DR['DealSourceFileName'].'.', StatusError);
      if (!CheckForNewDeals($StartTime)) SysLogIt('Error checking for new deals for source with filename of '.$DR['DealSourceFileName'].'.', StatusError);
      
    }
    
    $EchoToConsole = false;
	*/
	
	return true;
    
  }
  
  function CheckForNewDivisions($ST) {

    //Read deal sources once a week
    list ($QR, $RS, $T) = QuerySet('SELECT DealSourceID AS ID, DealSourceName AS DN, DealSourceFileName AS FN FROM 4100_Deal_Sources WHERE DealSourceStatus & 1 = 1 AND UNIX_TIMESTAMP(URLsLastUpdated) < '.mktime(date('H'),date('i'),date('s'),date('n'),date('j')-7,date('Y')).';');
    if ($QR < 0) { return SysLogIt('Query returned an error.', StatusError, ActionSelect); }
    if ($QR > 0) {
      while ($DR = mysql_fetch_array($RS)) {
      
        if ((time() - $ST) > 720) return SysLogIt('Division parsing exceeded 12 minutes; stopping until next cycle.', StatusInfo, ActionNotSpecified, -1);
      
        try {
        
          SysLogIt('Retrieving '.$DR['DN'].' divisions.', StatusInfo, ActionNotSpecified, 1);
          
          $AClass = $DR['FN'];
          $TheClass = new $AClass();
          $TheClass->GetNewDivisions($DR['ID']);
          
          SysLogIt('Finished retrieving '.$DR['DN'].' divisions.', StatusInfo, ActionNotSpecified, -1);
          
        } catch(Exception $Exp) {
          SysLogIt('Error getting divisions for source with ID of '.$DR['ID'].'.', StatusError);
          UpStatus($DR['DN'], $DR['ID']);
        }
      }
    }
    
    return true;
  
  }
  
  function CheckForNewDeals($ST) {

    //Read deal update times
    list ($QR, $RS, $T) = QuerySet('SELECT DealSourceID AS SID, DealSourceName AS DN, DealSourceFileName AS FN, DealSourceResetTime AS RT FROM 4100_Deal_Sources WHERE DealSourceStatus & 1 = 1;');
    //list ($QR, $RS, $T) = QuerySet('SELECT DealSourceID AS ID, DealSourceFileName AS FN FROM 4100_Deal_Sources WHERE DealSourceStatus & 1 = 1 AND UNIX_TIMESTAMP(DealsLastUpdated) <= '.mktime(date('H')-6,date('i'),date('s'),date('n'),date('j'),date('Y')).';');
    if ($QR < 0) { return SysLogIt('Query returned an error', StatusError, ActionSelect); }
    if ($QR > 0) {
      while ($DR = mysql_fetch_array($RS)) {
      
        if ((time() - $ST) > 720) return SysLogIt('Deal parsing exceeded 12 minutes; stopping until next cycle.', StatusInfo, ActionNotSpecified, -1);
      
        try {
        
          $AClass = $DR['FN'];
          $TheClass = new $AClass();

          //Retrieve districts
          list ($QR, $SRS, $T) = QuerySet('SELECT RemoteID AS SRID, CountryID AS CID, URL, LanguageID AS LID, TimeZone AS TZ, Latitude AS LAT, Longitude AS LNG FROM 4110_Deal_Source_URLs WHERE Latitude <> 0 AND Longitude <> 0 AND DealSourceID = '.$DR['SID'].' AND (NextUpdate < '.date('YmdHis').' OR NextUpdate = 0) AND Status = 1 '.Limits.';');
          if ($QR < 0) return SysLogIt('Error while searching for '.$DR['DN'].' divisions.', StatusError, ActionSelect);
          if ($QR > 0) {
          
            SysLogIt('Retrieving '.$DR['DN'].' deals.');
          
            while ($SDR = mysql_fetch_array($SRS)) {
            
              if ((time() - $ST) > 720) return SysLogIt('Script exceeded 12 minutes; stopping until next cycle.', StatusInfo, ActionNotSpecified, -1);
            
              $Next = 0;
              SysLogIt('Processing '.$DR['DN'].' division with ID of '.$SDR['SRID'].' ('.$SDR['URL'].')', StatusInfo, ActionNotSpecified, 1);
              
              list ($S, $DArr) = $TheClass->GetDivisionData($SDR['SRID'], $SDR['URL'], $SDR['LID'], $SDR['TZ']);
              
              if ($S) {
              
                if ($DArr !== false) {
                
                  foreach ($DArr as $D) {
                
                    CleanAll($D);
                    
                    if (!array_key_exists('GW', $D)) {
                      ProcessDeal($D, $DR['SID'], $DR['DN'], $SDR['SRID'], $SDR['LID'], $SDR['CID'], $SDR['LAT'], $SDR['LNG']);
                      if (($Next == 0) || ($D['EDate'] < $Next)) $Next = $D['EDate'];
                    }

                    if (array_key_exists('Deals', $D)) {
                      foreach ($D['Deals'] as $DItem) {
                      
                        $DURL = (is_array($DItem))?$DItem[0]:$DItem;
                        $WebD = (is_array($DItem))?$DItem[1]:false;
                        
                        SysLogIt('Processing '.$DR['DN'].' sub-deal ('.$DURL.').', StatusInfo, ActionNotSpecified, 1);
                        list ($S, $SDArr) = $TheClass->GetDivisionData($SDR['SRID'], $DURL, $SDR['LID'], $SDR['TZ'], true, $WebD);
                        
                        if ($S) {
                        
                          if ($SDArr !== false) {
                          
                            foreach ($SDArr as $SD) {
                              CleanAll($SD);
                              ProcessDeal($SD, $DR['SID'], $DR['DN'], $SDR['SRID'], $SDR['LID'], $SDR['CID'], $SDR['LAT'], $SDR['LNG']);
                              if (($Next == 0) || ($SD['EDate'] < $Next)) $Next = $SD['EDate'];
                            }

                          }
                          
                        }
                        
                      }
                    }
                    
                  }

                  SetNextUpdate($DR['SID'], $SDR['SRID'], $DR['RT'], $SDR['TZ'], $Next);

                } else {
                
                  SysLogIt('No active deal for division with ID of '.$SDR['SRID'].'.');
                  
                  //Exceptions
                  if (($DR['SID'] == 9) && (date('H') < 10)) $Next = mktime(date('H')+3,date('i'),date('s'),date('n'),date('j'),date('Y'));
                  
                  SetNextUpdate($DR['SID'], $SDR['SRID'], $DR['RT'], $SDR['TZ'], $Next);
                
                }
                                
              } else {
              
                SetNextUpdate($DR['SID'], $SDR['SRID'], 0, 0, 0);
                
              }
              
            }
            
            SysLogIt('Finished retrieving '.$DR['DN'].' deals.', StatusInfo, ActionNotSpecified, -1);
            
          }
          
        } catch(Exception $Exp) {
        
          SysLogIt('Error getting deals for source with ID of '.$DR['SID'].'.', StatusError);
          
        }
        
      }
    }
    
    return true;
  
  }
  
  function ProcessDeal($D, $SID, $SName, $SRID, $LID, $CID, $Lat, $Lng) {
  
    //Remove HTML from all text strings
    foreach ($D as &$DItem) {
      if (!(is_numeric($DItem) || is_array($DItem))) $DItem = StringAdjust(CleanHTML($DItem));
    }

    //Remove broken or incomplete addresses
    foreach ($D['Locations'] as &$DLoc) {
      if ((strlen($DLoc[0]) < 10) || (substr_count($DLoc[0], ' ') < 3)) $DLoc[0] = null;
    }

    if (strlen($D['Title']) < 15) return SysLogIt('Title appears too short. Check parser.', StatusError);
    if ($D['Price'] > $D['Value']) return SysLogIt('Prices appear invalid. Check parser.', StatusError);
    if ((time() + (30 * 24 * 60 * 60)) < $D['EDate']) return SysLogIt('End date too far into future. Check parser.', StatusError);

    if (trim($D['DRID']) == '') return SysLogIt('ID is invalid. Check parser.', StatusError);
    if (trim($D['StoreName']) == '') return SysLogIt('Store name is invalid. Check parser.', StatusError);
    
    //Match category
    $TypeID = (int)GetTypeFromKeywords($D['StoreName'].' '.$D['Title'].' '.$D['Descr']);
    
    $DoHist = false;
    
    if ($StoreID = GetStoreID($SID, $TypeID, $D['StoreName'], $D['Website'], $D['Locations'], $CID, $Lat, $Lng, $DoHist)) {
      if ($DealID = GetDealID($SID, $LID, $CID, $StoreID, $D, $DoHist)) {
        SysLogIt('Successfully parsed a deal for division with ID of '.$SRID.'.');
      }
    }
  
  }
  
  function FindData(&$Haystack, $Start, $End, $TrimL, $TrimR, $IsNum, $InitialOffset = 0, $DoError = true) {
  
    $StartPos = stripos($Haystack, $Start, $InitialOffset);
    if ($StartPos === false) return (($DoError)?SysLogIt('Could not find '.$Start.'.', StatusError):false);
    
    $EndPos = stripos($Haystack, $End, $StartPos + strlen($Start));
    if ($EndPos === false) return (($DoError)?SysLogIt('Could not find '.$End.'.', StatusError):false);
    
    $Value = trim(substr($Haystack, $StartPos + $TrimL, $EndPos - $StartPos - $TrimL - $TrimR));
    
    if ($IsNum) { 
      $Value = str_replace(',', '', $Value);
      if (!is_numeric($Value)) return SysLogIt('"'.substr($Value, 0, 20).'" was not numeric as expected.', StatusError);
      $Value = (double)$Value;
    }
    
    return $Value;
  
  }
  
  function FindSubData(&$Haystack, $FirstMatch, $SecondMatch, $EndMatch, $IsNum = false, $InitialOffset = 0, $DoError = true) {
  
    $StartPos = stripos($Haystack, $FirstMatch, $InitialOffset);
    if ($StartPos === false) {
      return false;
    } else {
      $StartPos = stripos($Haystack, $SecondMatch, $StartPos + strlen($FirstMatch));
      if ($StartPos === false) {
        return false;
      } else {
        $EndPos = stripos($Haystack, $EndMatch, $StartPos + strlen($SecondMatch));
        if ($EndPos === false) {
          return false;
        } else {
        
          $Value = trim(substr($Haystack, $StartPos + strlen($SecondMatch), $EndPos - $StartPos - strlen($SecondMatch)));
          
          if ($IsNum) {
            $Value = str_replace(',', '', $Value);
            if (!is_numeric($Value)) return SysLogIt('"'.substr($Value, 0, 20).'" was not numeric as expected.', StatusError);
            $Value = (double)$Value;
          }
          
          return $Value;
          
        }
      }
    }
    
  }
  
  function GetNewDealData(&$Data, &$NewDeal) {

    foreach ($NewDeal as &$NewDealItem) {
      $NewDealItem[0] = FindSubData($Data, $NewDealItem[1], $NewDealItem[2], $NewDealItem[3], $NewDealItem[4]);
      if ($NewDealItem[0] === false) {
        if (count($NewDealItem) < 6) return SysLogIt('Could not find '.StringAdjust($NewDealItem[1]).'.', StatusError);
      }
    }
    
    return true;
  
  }  
  
  function CheckURL($Name, $SID, $RID, $URL, $Ctry = 0) {
  
    SysLogIt('Processing item with ID of '.$RID);

    list ($QR, $DR, $T) = QuerySingle("SELECT UrlID FROM 4110_Deal_Source_URLs WHERE RemoteID = '".Pacify($RID)."' AND DealSourceID = ".$SID.";");
    if ($QR < 0) return SysLogIt('Error looking up '.$Name.' divisions.', StatusError, ActionSelect);
    if ($QR == 0) {
      if (!ExecCommand("INSERT INTO 4110_Deal_Source_URLs (DealSourceID, LanguageID, CountryID, RemoteID, URL, TimeZone, Status, LastVerify, NextUpdate) VALUES (".$SID.",1,".$Ctry.",'".Pacify($RID)."','".Pacify($URL)."',0,1,".date('YmdHis').",0);"))
       return SysLogIt('Error inserting new '.$Name.' division with remote ID of '.Pacify($RID).'.', StatusError, ActionInsert);
      SysLogIt('Saved new '.$Name.' division: '.$RID, StatusInfo, ActionInsert);
    } else {
      if (!ExecCommand("UPDATE 4110_Deal_Source_URLs SET URL = '".Pacify($URL)."', Status = 1, LastVerify = ".date('YmdHis')." WHERE DealSourceID = ".$SID." AND RemoteID = '".Pacify($RID)."';"))
        return SysLogIt('Error updating '.$Name.' division with remote ID of '.Pacify($RID).'.', StatusError, ActionUpdate);
      SysLogIt('Updated '.$Name.' division: '.$RID, StatusInfo, ActionUpdate);
    }
    
    return true;
              
  }
  
  function UpStatus($Name, $SID) {
  
    if (!ExecCommand("UPDATE 4110_Deal_Source_URLs SET Status = 0 WHERE LastVerify < ".date('YmdHis', mktime(0,0,0))." AND DealSourceID = ".$SID.";"))
      return SysLogIt('Error setting '.$Name.' URL statuses.', StatusError, ActionUpdate);

    if (!ExecCommand("UPDATE 4100_Deal_Sources SET URLsLastUpdated = ".date('YmdHis')." WHERE DealSourceID = ".$SID.";"))
      return SysLogIt('Error updating '.$Name.' URL processing date.', StatusError, ActionUpdate);
      
    SysLogIt('Updated '.$Name.' URL processing date.', StatusInfo, ActionUpdate);
    return true; //Success!

  }
  
  function DoGeocode($URL) {
  
    list ($CData, $DURL) = GetWebData('http://maps.googleapis.com/maps/api/geocode/json?address='.$URL.'&sensor=false');
    if ($CData !== false) {

      $DataArray = json_decode($CData, true);
      //if (json_last_error() == JSON_ERROR_NONE) {
      if (!is_null($DataArray)) {
    
        if ($DataArray['status'] == 'OK') {
        
          $Ctry = '';
          foreach ($DataArray['results'][0]['address_components'] as $AdrPart) {
            if ($AdrPart['types'][0] == 'country') {
              $Ctry = $AdrPart['short_name'];
              break;
            }
          }
        
          return array($DataArray['results'][0]['geometry']['location']['lat'], $DataArray['results'][0]['geometry']['location']['lng'], $Ctry);
          
        } else {
        
          return SysLogIt('Geolocating API returned status of '.$DataArray['status'].'.', StatusWarning);
        
        }
        
      } else {
      
        return SysLogIt('JSON decode error: '.json_last_error().'.', StatusError);
      
      }
      
    } else {
    
      return SysLogIt('Error retrieving data from geolocating API.', StatusError);
    
    }
                  
  }  
  
  function GetStoreID($SID, $TypeID, $StoreName, $StoreWebsite, $Locations, $CID, $Lat, $Lng, &$DoHist) {
  
    GeocodeLocations($Locations, $CID, $Lat, $Lng);
  
    $AdrIn = '';
    $LocIn = '';
    foreach ($Locations as $Location) {
      if (!is_null($Location[0]) && (strlen($Location[0]) > 0)) $AdrIn .= "'".Pacify($Location[0])."',";
      $LocIn .= round((double)$Location[2],5).',';
    }
    $AdrIn = rtrim($AdrIn, ',');
    $LocIn = rtrim($LocIn, ',');
    if (strlen($LocIn) == 0) return false;
    
    //Look for store
    list ($QR, $RS, $T) = QuerySet("SELECT S.StoreID, S.TypeID, L.LocationID, D.DealID
                                      FROM 2000_Stores S
                                      LEFT JOIN 2200_Store_Locations SL ON SL.StoreID = S.StoreID
                                      LEFT JOIN 3000_Locations L ON SL.LocationID = L.LocationID AND ((ROUND(L.LocationLongitude,5) IN (".$LocIn.")) ".((strlen($AdrIn) > 0)?" OR (L.LocationAddress IN (".$AdrIn."))":"").")
                                      LEFT JOIN 4000_Deals D ON D.StoreID = S.StoreID AND D.DealSourceID = ".$SID." AND D.DateEnds > ".date('YmdHis')."
                                     WHERE S.StoreName = '".Pacify($StoreName)."';");

    if ($QR < 0) return SysLogIt('Error searching for store.', StatusError, ActionSelect);
    
    $StoreID = 0;
    
    if ($QR > 0) {
      while ($DR = mysql_fetch_array($RS)) {
      
        $StoreID = $DR['StoreID'];
        $OldTypeID = $DR['TypeID'];
        
        $LocationID = 0;
        $DealID = 0;
        if (!is_null($DR['LocationID'])) $LocationID = $DR['LocationID'];
        if (!is_null($DR['DealID'])) $DealID = $DR['DealID'];
        
        if (($StoreID > 0) && ($DealID > 0)) break;
        
      }
    }
    
    if ($StoreID > 0) {
    
      $DoHist = true;

      if ($OldTypeID != $TypeID) {
      
        //Update store type
        if (ExecCommand("UPDATE 2000_Stores SET TypeID = ".$TypeID." WHERE StoreID = ".$StoreID.";")) {
          SysLogIt('Updated store with ID of '.$StoreID.' from type '.$OldTypeID.' to '.$TypeID.'.', StatusInfo, ActionUpdate); 
        } else {
          SysLogIt('Error updating type of store with ID of '.$StoreID.'.', StatusError, ActionUpdate); 
        }
      
      }

      //Store found and any one location found
      if ($LocationID > 0) {
      
        if ($Lat != -1) {
          foreach ($Locations as $Location) {
            if ($Location[1] > 0) {
              $Dist = GetDistance($Location[1], $Location[2], $Lat, $Lng);
              if ($Dist > 1000) return SetToWeb($StoreID, 'location/division mismatch (distance of '.$Dist.')');
            }
          }
        }

        //TODO: Check if any new addresses
        
        SysLogIt('Store already exists with ID of '.$StoreID.' and no need to update.');
        return $StoreID;
        
      }

      //Store found, no locations found, but active deal
      if (($LocationID == 0) && ($DealID > 0)) return CheckLocations($Locations, $StoreID, $Lat, $Lng);

    }
    
    //No store found
    //or, store found, but no matching locations and no active deal
    
    //Prepare Website
    if (is_null($StoreWebsite) || (trim($StoreWebsite) == '')) {
      $Website = 'NULL';
    } else {
      $Website = Pacify($StoreWebsite);
      if (!( (strtolower(substr($Website, 0, 7)) == 'http://') || (strtolower(substr($Website, 0, 8)) == 'https://') )) $Website = 'http://'.$Website;
      $Website = "'".$Website."'";
    }    
    
    //Insert new store
    if (!ExecCommand("INSERT INTO 2000_Stores (TypeID, StoreName, StoreWebsite, StoreAddDate, StoreLastUpdate)
                      VALUES (".$TypeID.",'".Pacify($StoreName)."',".$Website.",".date('YmdHis').",".date('YmdHis').");"))
      return SysLogIt('Error creating new store.', StatusError, ActionInsert); 
      
    //Get new store record
    list ($QR, $DR, $T) = QuerySingle("SELECT last_insert_id() AS ID;");
    if ($QR < 0) return SysLogIt('Error retrieving newly inserted store ID.', StatusError, ActionSelect);
    
    $StoreID = $DR['ID'];
    SysLogIt('Created new store with ID of '.$StoreID.'.', StatusInfo, ActionInsert);
    
    return AddLocationsTo($Locations, $StoreID, $Lat, $Lng);
    
  }
  
  function GeocodeLocations(&$Locations, $CID, $Lat, $Lng) {
  
    foreach ($Locations as &$Location) {
    
      $Location[3] = $CID;
    
      if ($Location[1] == 0 || $Location[2] == 0 || !is_numeric($Location[1]) || !is_numeric($Location[2])) {
      
        if (!is_null($Location[0])) {
        
          //SysLogIt('Geocoding address.');
          
          if (is_null($Lat) || is_null($Lng)) {
            $Coords = DoGeocode(urlencode(utf8_encode($Location[0])));
          } else {
            $Coords = DoGeocode(urlencode(utf8_encode($Location[0])).'&bounds='.(double)$Lat.','.(double)$Lng.'|'.(double)$Lat.','.(double)$Lng);
          }
          
          if ($Coords !== false) {
            $Location[1] = $Coords[0];
            $Location[2] = $Coords[1];
            $Location[3] = GetCountry($Coords[2], $CID);
          } elseif (!(is_null($Lat) || is_null($Lng) || $Lat == -1)) {
            SysLogIt('Bad geocode; using default division coordinates.');
            $Location[1] = $Lat;
            $Location[2] = $Lng;
          } else {
            SysLogIt('Not a valid address and no default coordinates.', StatusError);
          }
          
        } else {
        
          //Can't geocode: no address.
          
          if (!(is_null($Lat) || is_null($Lng) || $Lat == -1)) {
            SysLogIt('No address; using default division coordinates.');
            $Location[1] = $Lat;
            $Location[2] = $Lng;
          }
          
        }
        
      }
      
    }
  
  }
  
  function AddLocationsTo($Locations, $StoreID, $Lat, $Lng) {
  
    foreach ($Locations as $Location) {
    
      //Look for store
      list ($QR, $DR, $T) = QuerySingle("SELECT 
                                           FROM 2200_Store_Locations SL ON SL.StoreID = S.StoreID
                                          INNER JOIN 3000_Locations L ON SL.LocationID = L.LocationID
                                          WHERE S.StoreID = ".$StoreID." AND L.LocationAddress = '".Pacify($Location[0])."';");
                                          
      if ($QR < 0) return SysLogIt('Error searching for store address.', StatusError, ActionSelect);      
      
      if ($QR == 0) {
    
        //Insert new location
        if (!ExecCommand("INSERT INTO 3000_Locations (LocationAddress, LocationLatitude, LocationLongitude, CountryID)
                          VALUES (".((is_null($Location[0]))?'NULL':"'".Pacify($Location[0])."'").",".(double)$Location[1].",".(double)$Location[2].",".$Location[3].");"))
          return SysLogIt('Error creating new location.', StatusError, ActionInsert);
        
        //Get new location record
        list ($QR, $DR, $T) = QuerySingle("SELECT last_insert_id() AS ID;");
        if ($QR < 0) return SysLogIt('Error retrieving newly inserted location ID.', StatusError, ActionSelect);
        
        $LocationID = $DR['ID'];
        SysLogIt('Created new location with ID of '.$LocationID.' and coordinates of '.(double)$Location[1].', '.(double)$Location[2].'.', StatusInfo, ActionInsert);

        //Insert new store/location link
        if (!ExecCommand("INSERT INTO 2200_Store_Locations (StoreID, LocationID) VALUES (".$StoreID.",".$LocationID.");"))
          return SysLogIt('Error creating new store/location link.', StatusError, ActionInsert); 
        SysLogIt('Created new store/location link.', StatusInfo, ActionInsert);
        
      }
      
    }
    
    return $StoreID;
    
  }
  
  function CheckLocations($Locations, $StoreID, $Lat, $Lng) {
  
    foreach ($Locations as $Location) {
     if ($Location[1] == -1) return SetToWeb($StoreID, 'web data');
    }
    
    if ($Lat != -1) {
  
      //Look for locations
      list ($QR, $RS, $T) = QuerySet("SELECT SL.LocationID, L.LocationLatitude AS Lat, L.LocationLongitude AS Lng
                                        FROM 2200_Store_Locations SL
                                       INNER JOIN 3000_Locations L ON SL.LocationID = L.LocationID
                                       WHERE SL.StoreID = ".$StoreID.";");
                                          
      if ($QR < 0) return SysLogIt('Error searching for store locations.', StatusError, ActionSelect);
      
      if ($QR > 0) {
      
        while ($DR = mysql_fetch_array($RS)) {

          $Dist = GetDistance($DR['Lat'], $DR['Lng'], $Lat, $Lng);
          if ($Dist > 1000) return SetToWeb($StoreID, 'distance of current division (distance of '.$Dist.')');
        
        }
      
      }
      
    }
    
    return AddLocationsTo($Locations, $StoreID, $Lat, $Lng);
  
  }
  
  function SetToWeb($StoreID, $Reason) {
  
    //Get all locations
    list ($QR, $DR, $T) = QuerySingle("SELECT GROUP_CONCAT(L.LocationID) AS Locs
                                         FROM 2200_Store_Locations SL
                                        INNER JOIN 3000_Locations L ON SL.LocationID = L.LocationID
                                        WHERE SL.StoreID = ".$StoreID."
                                        GROUP BY SL.StoreID;");
                                        
    if ($QR < 1) return SysLogIt('Error searching for grouped store locations.', StatusError, ActionSelect);
    
    //Insert new store/location link
    if (!ExecCommand("UPDATE 3000_Locations SET LocationLatitude = -1, LocationLongitude = -1 WHERE LocationID IN (".$DR['Locs'].");"))
      return SysLogIt('Error setting store locations to web mode.', StatusError, ActionUpdate); 
  
    SysLogIt('Set locations of store with ID of '.$StoreID.' to web deal based on '.Pacify($Reason).'.', StatusInfo, ActionUpdate);
    return $StoreID;
          
  }
  
  function GetDistance($LatA, $LngA, $LatB, $LngB) {

    $dLat = deg2rad($LatB - $LatA);
    $dLng = deg2rad($LngB - $LngA);
    $A = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($LatA)) * cos(deg2rad($LatB)) * sin($dLng/2) * sin($dLng/2);
    return round(6371 * 2 * atan2(sqrt($A), sqrt(1-$A)), 1);
  
  }
  
  function GetDealID($Source, $LanguageID, $CountryID, $StoreID, $D, $DoHist) {
  
    //Find deal
    list ($QR, $DR, $T) = QuerySingle("SELECT D.DealID, D.DealPrice, D.DealValue, D.DealStatus, D.StringID, LS.StringText AS Title, D.DealURL
                                         FROM 4000_Deals D
                                        INNER JOIN 0200_Language_Strings LS ON D.StringID = LS.StringID AND LS.LanguageID = ".$LanguageID."
                                        WHERE D.DealSourceID = ".$Source."
                                          AND ((D.RemoteID = '".Pacify($D['DRID'])."')
                                           OR (LS.StringText = '".Pacify($D['Title'])."' AND LENGTH(LS.StringText) > 15))
                                          AND D.DateEnds >= ".date('YmdHis').";");
    if ($QR < 0) return SysLogIt('Error searching for deal.', StatusError, ActionSelect);
    
    if ($QR == 0) {
    
      if ($StringID = CreateNewString($LanguageID, ($Source * 1000000), ((($Source + 1) * 1000000) - 1), 'Title for deal with remote ID of '.$D['DRID'], $D['Title'])) {
      
        $CID = $CountryID;
        if (array_key_exists("Country", $D)) $CID = GetCountry($D['Country'], $CountryID);
          
        //Insert new deal
        if (!ExecCommand("INSERT INTO 4000_Deals (DealSourceID, CountryID, StoreID, StringID, RemoteID, DealURL, DateListed, DateEnds, DateExpiry, DealPrice, DealValue, DealStatus)
                          VALUES (".$Source.",".$CID.",".$StoreID.",".$StringID.",'".Pacify($D['DRID'])."','".Pacify($D['URL'])."',".date('YmdHis', $D['SDate']).",".date('YmdHis', $D['EDate']).",".date('YmdHis', $D['VDate']).",".round((double)$D['Price']).",".round((double)$D['Value']).",".$D['Status'].");"))
          return SysLogIt('Error creating new deal.', StatusError, ActionInsert);
          
        //Get new store record
        list ($QR, $DR, $T) = QuerySingle("SELECT last_insert_id() AS ID;");
        if ($QR < 0) return SysLogIt('Error retrieving newly inserted deal ID.', StatusError, ActionSelect);
        
        $DealID = $DR['ID'];
        SysLogIt('Created new deal with ID of '.$DealID.'.', StatusInfo, ActionInsert);
        
        if ($DoHist) DoHistoryGraph($StoreID);
        DoQR($DealID, $StoreID, $D);

      } else {
       
        return false;
      
      }

    } else {
    
      $DealID = $DR['DealID'];
      $StringID = $DR['StringID'];
    
      //Update deal data if necessary
      if ( ((int)$DR['DealPrice'] != (int)$D['Price']) || ((int)$DR['DealValue'] != (int)$D['Value']) ) { // || ($DR['DealURL'] != $D['URL']) ) {
      
        if (!ExecCommand("UPDATE 4000_Deals SET DealPrice = ".(double)$D['Price'].", DealValue = ".(double)$D['Value'].", DealStatus = ".($DR['DealStatus'] | $D['Status']).", DealURL = '".Pacify($D['URL'])."' WHERE DealID = ".$DR['DealID'].";"))
          return SysLogIt('Error updating deal with ID of '.$DR['DealID'].'.', StatusError, ActionUpdate);
        
        SysLogIt('Updated deal with ID of '.$DR['DealID'].'.', StatusInfo, ActionUpdate);
        DoQR($DealID, $StoreID, $D, true);
          
      } else {
      
        SysLogIt('Deal already exists with ID of '.$DealID.' and no need to update.');
      
      }
      
      //Update string if necessary
      if ($DR['Title'] != $D['Title']) {
      
        if (!ExecCommand("UPDATE 0200_Language_Strings SET StringText = '".Pacify($D['Title'])."' WHERE StringID = ".$StringID." AND LanguageID = ".$LanguageID.";"))
          return SysLogIt('Error updating string with ID of '.$StringID.'.', StatusError, ActionUpdate);
        SysLogIt('Updated string with ID of '.$StringID.'.', StatusInfo, ActionUpdate);

      }
      
    }
    
    //Do translations
    if ($StringID > 0) TranslateOtherLanguages($D, $LanguageID, $StringID);
    
    return $DealID;
    
  }
  
  function DoQR($DID, $SID, $D, $Clear = false) {
  
    $Filepath = dirname(__FILE__).'/../QR/';
  
    if ($Clear) {
    
      //Find existing QR
      list ($QR, $DR, $T) = QuerySingle("SELECT DealQR FROM 4000_Deals WHERE DealID = ".$DID.";");
      if ($QR < 0) return SysLogIt('Error searching for existing QR code.', StatusError, ActionSelect);
      
      if ($QR > 0) {
        if (unlink($Filepath.$DR['DealQR'])) {
          SysLogIt('Deleted old QR code for deal with ID of '.$DID.'.');
        } else {
          SysLogIt('Error deleting old QR code for deal with ID of '.$DID.'. File '.Pacify($DR['DealQR']).' is orphaned.', StatusError);
        }
      }
    
    }
  
    $String = '$'.(double)$D['Value'].' @ '.$D['StoreName'].chr(13).chr(10);
    foreach ($D['Locations'] as $ALoc) {
      if (!(($ALoc[1] == -1) || is_null($ALoc[0]) || ($ALoc[0] == ''))) $String .= $ALoc[0].chr(13).chr(10);
    }
    if (!(is_null($D['Website']) || ($D['Website'] == ''))) $String .= $D['Website'].chr(13).chr(10);
    $String .= 'http://www.dealplotter.com/?'.$DID;
    $String = 'http://chart.apis.google.com/chart?chs=150x150&cht=qr&chld=M|0&chl='.urlencode($String);
  
    $Filename = md5($DID.'-'.$SID.'-'.time()).'.png';
    if (file_exists($Filepath.$Filename)) unlink($Filepath.$Filename);
    
    $CurlSession = curl_init();
     
    curl_setopt($CurlSession, CURLOPT_URL, $String);
    curl_setopt($CurlSession, CURLOPT_HEADER, 0);
    curl_setopt($CurlSession, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($CurlSession, CURLOPT_BINARYTRANSFER, 1);
    
    if (!$Data = curl_exec($CurlSession)) {
    
      trigger_error(curl_error($CurlSession));
      SysLogIt('Error generating QR Code for deal with ID of '.$DID.'.', StatusError);
      curl_close($CurlSession);
      
    } else {
    
      $File = fopen($Filepath.$Filename, 'x');
      fwrite($File, $Data);
      fclose($File);
      curl_close($CurlSession);
      
      $FileCheck = getimagesize($Filepath.$Filename);
      if ($FileCheck !== false) {
        if ($FileCheck[2] == 3) {
          if (!ExecCommand("UPDATE 4000_Deals SET DealQR = '".Pacify($Filename)."' WHERE DealID = ".$DID.";"))
            return SysLogIt('Error setting QR Code for deal with ID of '.$StringID.'. File '.$Filename.' is orphaned.', StatusError, ActionUpdate);
          return SysLogIt('Created QR Code.', StatusInfo);
        }
      }
      SysLogIt('Generated QR code image was not of type PNG for deal with ID of '.$DID.'.', StatusError);
      unlink($Filepath.$Filename);
      
    }
    
  }
  
  function GetTypeFromKeywords($Text) {
  
    //Prepare text for parsing
    $Text = str_ireplace('</', ' ', $Text);
    $Text = str_ireplace('/>', ' ', $Text);
    $Text = str_ireplace('<', ' ', $Text);
    $Text = str_ireplace('>', ' ', $Text);
    $Text = str_ireplace(',', ' ', $Text);
    $Text = str_ireplace('.', ' ', $Text);
    $Text = str_ireplace('!', ' ', $Text);
    $Text = str_ireplace('?', ' ', $Text);
    $Text = str_ireplace(chr(10), ' ', $Text);
    $Text = str_ireplace(chr(13), ' ', $Text);
    $Text = str_ireplace("'s ", ' ', $Text);
    
    while (stripos($Text, '  ') !== false) { $Text = str_replace('  ', ' ', $Text); }
    
    $Text = ' '.$Text.' ';
        
    //Retrieve keywords
    list ($QR, $RS, $T) = QuerySet('SELECT ST.TypeID, CONCAT_WS(", ", SCK.Keywords,STK.Keywords) AS Keywords
                                      FROM 2100_Store_Categories SC
                                     INNER JOIN 2101_Store_Category_Keywords SCK ON SCK.CategoryID = SC.CategoryID
                                     INNER JOIN 2110_Store_Types ST ON ST.CategoryID = SC.CategoryID
                                     INNER JOIN 2111_Store_Type_Keywords STK ON STK.TypeID = ST.TypeID;');
    
    if ($QR < 1) return false;
      
    $Keywords = array();
    $KeywordScores = array();
  
    while ($DR = mysql_fetch_array($RS)) {
      $Keywords[(int)$DR['TypeID']] = explode(', ', $DR['Keywords']);
      $KeywordScores[(int)$DR['TypeID']] = 0;
    }
    
    //var_dump($Keywords); echo '<br><br>';
    
    //Match it all up, yo
    foreach ($Keywords as $ID => $Value) {
      foreach ($Value as $Keyword) {
        
        //if ((int)substr_count(strtolower($Text), ' '.strtolower($Keyword).' ') > 0) echo 'Found keyword '.strtolower($Keyword).' in ID '.$ID.'<br>';
        if (substr($Keyword, 0, 1) == '*') {
          $KeywordScores[$ID] += (int)substr_count(strtolower($Text), strtolower(substr($Keyword,1)).' ');
          $KeywordScores[$ID] += (int)substr_count(strtolower($Text), strtolower(substr($Keyword,1)).'s '); 
        } elseif (substr($Keyword, -1) == '*') {
          $KeywordScores[$ID] += (int)substr_count(strtolower($Text), ' '.strtolower(substr($Keyword,0,-1)));
          $KeywordScores[$ID] += (int)substr_count(strtolower($Text), ' '.strtolower(substr($Keyword,0,-1)).'s');
        } else {
          $KeywordScores[$ID] += (int)substr_count(strtolower($Text), ' '.strtolower($Keyword).' ');
          $KeywordScores[$ID] += (int)substr_count(strtolower($Text), ' '.strtolower($Keyword).'s '); //Try same term with s at end
        }
      }
    }
    
    //Sort scores
    asort($KeywordScores, SORT_NUMERIC);
    end($KeywordScores);
    
    if (current($KeywordScores) < 2) {
      SysLogIt('Could not match category; manual assignment required.', StatusWarning);
      return 0;
      //var_dump($KeywordScores);  echo '<br><br>';
    }
    
    SysLogIt('Matched deal against type '.key($KeywordScores).' with a score of '.current($KeywordScores).'.');
    
    return (int)key($KeywordScores);
  
  }
  
  function TranslateOtherLanguages($D, $LID, $SID) {
  
    global $Response;
  
    //Find original language
    list ($QR, $DR, $T) =
      QuerySingle(
        "SELECT LanguageID, LanguageCode, LanguageName
           FROM 0000_Languages
          WHERE LanguageID = ".$LID.";");
    if ($QR < 0) return SysLogIt('Error searching for language.', StatusError, ActionSelect);
    
    //Find remaining languages
    list ($QR, $RS, $T) =
      QuerySet(
        'SELECT L.LanguageID, L.LanguageCode, L.LanguageName
           FROM 0000_Languages L
           LEFT JOIN 0200_Language_Strings LS ON LS.LanguageID = L.LanguageID AND LS.StringID = '.$SID.'
          WHERE L.LanguageID != '.$LID.'
            AND L.LanguageActive = 1
            AND LS.LinKID IS NULL;');
    
    if ($QR < 0) return SysLogIt('Error searching for remaining languages.', StatusError, ActionSelect);
    
    if ($QR > 0) {
    
      while ($SDR = mysql_fetch_array($RS)) {
      
        if (array_key_exists('Title-'.$SDR['LanguageCode'], $D)) {
        
          if (!ExecCommand("INSERT INTO 0200_Language_Strings (LanguageID, StringID, StringText) VALUES (".$SDR['LanguageID'].",".$SID.",'".Pacify($D['Title-'.$SDR['LanguageCode']])."');"))
            return SysLogIt('Error creating new string entry from source data.', StatusError, ActionInsert);
            
          SysLogIt('Translated description from '.$DR['LanguageName'].' to '.$SDR['LanguageName'].' using web data.', StatusInfo, ActionNotSpecified);
        
        } else {
      
          $Found = 0;
          
          if (list ($Data, $DURL) = GetWebData('https://www.googleapis.com/language/translate/v2?key=AIzaSyDRTAh6syp8m2WCO1IcjIM4ETaHuq0ZyNQ&q='.urlencode($D['Title']).'&source='.$DR['LanguageCode'].'&target='.$SDR['LanguageCode'])) {
          
            $DataArray = json_decode($Data, true);
            if (!is_null($DataArray)) {
            //if (json_last_error() == JSON_ERROR_NONE) { 
            
              if (array_key_exists('data', $DataArray)) {
                if (array_key_exists('translations', $DataArray['data'])) {
                  if (array_key_exists('translatedText', $DataArray['data']['translations'][0])) {
                  
                    $Found = 1;
                  
                    if (!ExecCommand("INSERT INTO 0200_Language_Strings (LanguageID, StringID, StringText) VALUES (".$SDR['LanguageID'].",".$SID.",'".Pacify($DataArray['data']['translations'][0]['translatedText'])."');"))
                      return SysLogIt('Error creating new string entry from Google Translation.', StatusError, ActionInsert);
                      
                    SysLogIt('Translated description from '.$DR['LanguageName'].' to '.$SDR['LanguageName'].'.', StatusInfo, ActionNotSpecified);
                      
                  }
                }
              } elseif (array_key_exists('error', $DataArray)) {
                
                if (array_key_exists('code', $DataArray['error']) && array_key_exists('message', $DataArray['error'])) {
                
                  $Found = 1;
                  SysLogIt('Error translating, code '.$DataArray['error']['code'].': '.$DataArray['error']['message'].'.', StatusError, ActionNotSpecified);
                
                }
              
              }
              
              if ($Found == 0) return SysLogIt('Unknown response received while translating.', StatusWarning, ActionNotSpecified);
            
            } else {
            
              return SysLogIt('Error decoding web data. Error was: '.json_last_error().'.', StatusError, ActionNotSpecified);
            
            }
          
          } else {
          
            return SysLogIt('Error retrieving web data.', StatusError, ActionNotSpecified);
          
          }
          
        }
        
      }
      
    }
  
  }
  
  function SetNextUpdate($SID, $RID, $ResetTime, $TZone, $Date = 0) {
  
    $UpDate = 0;
    
    if ($Date != 0) {
    
      $UpDate = ($Date + (5 * 60));
    
    } else {
    
      $TrueReset = ($ResetTime - ($TZone + 5));
      if ($TrueReset < 0) $TrueReset += 24;
    
      if (($ResetTime == 0) && ($TZone == 0)) {
        $UpDate = mktime(date('H')+6,date('i'),date('s'),date('n'),date('j'),date('Y'));
      } elseif ((int)date('H') < $TrueReset) {
        $UpDate = mktime($TrueReset, 0 + 5, 0, date('n'), date('j'), date('Y'));
      } else {
        $UpDate = mktime($TrueReset, 0 + 5, 0, date('n'), date('j') + 1, date('Y'));
      }
      
    }
    
    if ($UpDate < time()) {
      SysLogIt('Date calculatation incorrect. Determined date was '.date('Y-m-d H:i:s', $UpDate), StatusError);
      $UpDate = mktime(date('H')+8,date('i'),date('s'),date('n'),date('j'),date('Y'));
    }
    
    if (ExecCommand("UPDATE 4110_Deal_Source_URLs SET NextUpdate = ".date('YmdHis', $UpDate)." WHERE RemoteID = '".Pacify($RID)."' AND DealSourceID = ".$SID.";")) {
      SysLogIt('Set next update to '.date('Y-m-d H:i:s', $UpDate).'.');
    } else {
      SysLogIt('Could not set next update.', StatusError);
    }
  
  }
  
  function GetCountry($InCode, $Default = 0) {
  
    if ($InCode === false) return 0;
   
    switch (strtolower($InCode)) {
    
      case 'ca':
      case 'canada': return 1; 
      
      case 'us':
      case 'united states': return 2;
      
      case 'gb':
      case 'united kingdom': return 3;
      
      case 'au':
      case 'australia': return 4;
      
      case 'nl':
      case 'netherlands': return 5;
      
      case 'nz':
      case 'new zealand': return 6;
      
      case 'ie':
      case 'ireland': return 7;
      
      case 'fr':
      case 'france': return 8;

      default:
        SysLogIt('Selecting default country for division.');
        return $Default;
      
    }

    return 0;
    
  }
  
  function SendVerifications($ST) {
  
    //Read deal sources once a week
    list ($QR, $RS, $T) = QuerySet('SELECT UEV.VerificationID, UEV.VerificationKey, UEV.VerificationEmail, U.LanguageID
                                      FROM 1010_User_Email_Verifications UEV
                                     INNER JOIN 1000_Users U ON UEV.UserID = U.UserID
                                     WHERE UEV.VerificationSent = 0;');
    
    if ($QR < 0) { return SysLogIt('Query returned an error.', StatusError, ActionSelect); }
    if ($QR > 0) {
    
      SysLogIt('Sending email verifications...', StatusInfo, ActionNotSpecified, 1);
    
      while ($DR = mysql_fetch_array($RS)) {
      
        if ((time() - $ST) > 90) return SysLogIt('Sending of verifications has exceeded 90 seconds; stopping until next cycle.', StatusInfo, ActionNotSpecified, -1);
        
        $Strings = GSA('2000,2001,2002,2003,2004,2005,2006', $DR['LanguageID']);
      
        try {
        
          $Msg = $Strings[2001].chr(13).chr(10).chr(13).chr(10).
                 $Strings[2002].chr(13).chr(10).chr(13).chr(10).
                 $Strings[2003].chr(13).chr(10).chr(13).chr(10).
                 'http://www.dealplotter.com/?Verify&Key='.$DR['VerificationKey'].chr(13).chr(10).chr(13).chr(10).
                 $Strings[2004].chr(13).chr(10).chr(13).chr(10).
                 $Strings[2005].chr(13).chr(10).$Strings[2006];
        
          if (SendMail($Msg, $Strings[2000], $DR['VerificationEmail'])) {
          
            SysLogIt('Successfully sent verification with ID of '.$DR['VerificationID'].'.', StatusInfo, ActionNotSpecified, 0);

            if (!ExecCommand("UPDATE 1010_User_Email_Verifications SET VerificationSent = ".date('YmdHis')." WHERE VerificationID = ".$DR['VerificationID'].";"))
              SysLogIt('Error updating verification entry with ID of '.$DR['VerificationID'].'.', StatusError, ActionNotSpecified, 0);

          } else {
            SysLogIt('Error sending verification email with ID of '.$DR['VerificationID'].'.', StatusError);
          }
          
        } catch(Exception $Exp) {
        
          SysLogIt('Error sending verification email with ID of '.$DR['VerificationID'].'.', StatusError);
          
        }

      }
      
      SysLogIt('Finished sending verifications.', StatusInfo, ActionNotSpecified, 0);
      
    }
    
    return true;
  
  }

  function SendResets($ST) {
  
    //Read deal sources once a week
    list ($QR, $RS, $T) = QuerySet('SELECT UPR.ResetID, UPR.ResetKey, UPR.ResetEmail, U.LanguageID
                                      FROM 1020_User_Password_Resets UPR
                                     INNER JOIN 1000_Users U ON UPR.UserID = U.UserID
                                     WHERE UPR.ResetSent = 0;');
    
    if ($QR < 0) { return SysLogIt('Query returned an error.', StatusError, ActionSelect); }
    if ($QR > 0) {
    
      SysLogIt('Sending password resets...', StatusInfo, ActionNotSpecified, 1);
    
      while ($DR = mysql_fetch_array($RS)) {
      
        if ((time() - $ST) > 180) return SysLogIt('Sending of password resets has exceeded 3 minutes; stopping until next cycle.', StatusInfo, ActionNotSpecified, -1);
        
        $Strings = GSA('2050,2051,2052,2053,2054,2055,2058', $DR['LanguageID']);
      
        try {
        
          $Msg = $Strings[2058].chr(13).chr(10).chr(13).chr(10).
                 $Strings[2051].chr(13).chr(10).chr(13).chr(10).
                 $Strings[2052].chr(13).chr(10).chr(13).chr(10).
                 'http://www.dealplotter.com/?Reset&Key='.$DR['ResetKey'].chr(13).chr(10).chr(13).chr(10).
                 $Strings[2053].chr(13).chr(10).chr(13).chr(10).
                 $Strings[2054].chr(13).chr(10).$Strings[2055];
        
          if (SendMail($Msg, $Strings[2050], $DR['ResetEmail'])) {
          
            SysLogIt('Successfully sent password reset with ID of '.$DR['ResetID'].'.', StatusInfo, ActionNotSpecified, 0);

            if (!ExecCommand("UPDATE 1020_User_Password_Resets SET ResetSent = ".date('YmdHis')." WHERE ResetID = ".$DR['ResetID'].";"))
              SysLogIt('Error updating reset entry with ID of '.$DR['ResetID'].'.', StatusError, ActionNotSpecified, 0);

          } else {
            SysLogIt('Error sending password reset email with ID of '.$DR['ResetID'].'.', StatusError);
          }
          
        } catch(Exception $Exp) {
        
          SysLogIt('Error sending password reset email with ID of '.$DR['ResetID'].'.', StatusError);
          
        }

      }
      
      SysLogIt('Finished sending password resets.', StatusInfo, ActionNotSpecified, 0);
      
    }
    
    return true;
  
  }
  
  function SendDigests($ST) {
  
    if ((int)date('G') >= 18) {
      $TimeOfDay = 4;
    } elseif ((int)date('G') >= 12) {
      $TimeOfDay = 2;
    } elseif ((int)date('G') >= 7) {
      $TimeOfDay = 1;
    } else {
      return true;
    }
    
    //Read users
    list ($QR, $RS, $T) = QuerySet('SELECT UN.NotificationID AS NID, UN.UserID, UN.DealID, UN.Settings, UN.CancelKey, U.LanguageID AS LID, U.UserEmail
                                      FROM 1400_User_Notifications UN
                                     INNER JOIN 1000_Users U ON UN.UserID = U.UserID
                                     WHERE UN.SentDate < '.date('YmdHis', mktime(date('H')-12,date('i'),date('s'),date('n'),date('j'),date('Y'))).'
                                       AND ((UN.Settings & '.$TimeOfDay.') = '.$TimeOfDay.')
                                       AND ((U.UserFlags & '.UserActive.') = '.UserActive.')
                                       AND (U.UserEmailVerified = 1);');


    if ($QR < 0) { return SysLogIt('Error searching for user notification settings.', StatusError, ActionSelect); }
    if ($QR > 0) {
    
      SysLogIt('Sending daily digests ('.$QR.' pending)', StatusInfo, ActionNotSpecified, 1);
      
      $Strings = array();
      
      list ($QR, $SRS, $T) = QuerySet("SELECT LanguageID FROM 0000_Languages WHERE LanguageActive = 1;");
      while ($SDR = mysql_fetch_array($SRS)) {
        $Strings[$SDR['LanguageID']] = GSA('2100,2101,2102,2103,2104,2105,2106,2107,2108,2109,2110,2111,2113,2114,2115', $SDR['LanguageID'], false, true);
      }
    
      while ($DR = mysql_fetch_array($RS)) {
      
        if ((time() - $ST) > 300) return SysLogIt('Sending of digests has exceeded 5 minutes; stopping until next cycle.', StatusInfo, ActionNotSpecified, -1);
        
        $MaxDealID = 0;
      
        if (($DR['Settings'] & 16) == 16) {
          $Output = '<html><head><title>Dealplotter - '.$Strings[$DR['LID']][2100].' - '.date('Y-m-d').'</title><meta http-equiv="Content-Type" content="text/html;charset=UTF-8" /><style type="text/css">A { text-decoration: none; } A:hover { border-bottom: 1px solid #0179ff; } TD EM { font-style: normal; }</style></head><body bgcolor="#FFFFFF" style="font-family: Calibri, sans-serif; font-size: 13px;"><table style="border: 1px solid #E0E0E0; border-radius: 5px; -moz-border-radius: 5px; -webkit-border-radius: 5px; background-color: #F8F8F8;" WIDTH="100%"><tr><td width="210"><a href="http://www.dealplotter.com"><img src="http://www.dealplotter.com/IF/LogoXS.png" border=0></a></td><td><h2 style="font-size: 18px;"><b>'.$Strings[$DR['LID']][2100].'<br />'.date('Y-m-d').'</b></h2></tr></tr></table><table border=0 cellpadding=3 style="font-size: 13px;">';
        } else {
          $Output = 'Dealplotter - '.$Strings[$DR['LID']][2100].' - '.date('Y-m-d').chr(13).chr(10).chr(13).chr(10);
        }
    
        list ($QR, $SRS, $T) = QuerySet(
          'SELECT L.LocationID, L.LocationLatitude AS Lat, L.LocationLongitude AS Lng, L.CountryID AS CID, UL.UserLocationName AS UName, C.CountryCurrency AS CurS,
            COALESCE(GROUP_CONCAT(UFT.FilterSourceID),0) AS TFilt, COALESCE(GROUP_CONCAT(UFD.FilterSourceID),0) AS DFilt,
            GROUP_CONCAT(CONCAT_WS(",", UFC.FilterSourceID, UFC.FilterValue) SEPARATOR "|") AS CFilt
             FROM 1000_Users U
            INNER JOIN 1100_User_Locations UL ON U.UserID = UL.UserID
            INNER JOIN 3000_Locations L ON L.LocationID = UL.LocationID
            INNER JOIN 3100_Countries C ON C.CountryID = L.CountryID
             LEFT JOIN 1110_User_Filters UFC ON UFC.UserLocationID = UL.UserLocationID AND UFC.FilterType = '.FilterCategory.'
             LEFT JOIN 1110_User_Filters UFT ON UFT.UserLocationID = UL.UserLocationID AND UFT.FilterType = '.FilterType.'
             LEFT JOIN (
               SELECT UF.UserLocationID, UF.FilterSourceID
                 FROM 1110_User_Filters UF
                INNER JOIN 4000_Deals D ON UF.FilterSourceID = D.DealID
                WHERE UF.FilterType = '.FilterDeal.'
                  AND D.DateEnds > '.date('YmdHis').'
                  AND UF.FilterValue = 1
             ) UFD ON UFD.UserLocationID = UL.LocationID
            WHERE U.UserID = '.$DR['UserID'].'
            GROUP BY UL.UserLocationID;');
            
        if ($QR < 0) { return SysLogIt('Error searching for user location settings.', StatusError, ActionSelect); }
        if ($QR > 0) {
        
          while ($SDR = mysql_fetch_array($SRS)) {
          
            if ($SDR['CFilt'] == '') {
              $Categories = array();
            } else {
              $Categories = explode("|", $SDR['CFilt']);
              foreach ($Categories as $Key => $Category) {
                $Categories[$Key] = explode(',', $Category);
              }
            }
          
            if (($DR['Settings'] & 16) == 16) {
              $Output .= '<tr><td colspan=3><h2 style="font-size: 18px;"><b><br />'.$Strings[$DR['LID']][2101].' '.StringAdjust($SDR['UName']).'';
            } else {
              $Output .= chr(13).chr(10).$Strings[$DR['LID']][2101].' '.StringAdjust($SDR['UName']);
            }
        
            list ($QR, $SSRS, $T) = QuerySet(
              'SELECT D.DealID, UNIX_TIMESTAMP(D.DateEnds) AS DateEnds, COALESCE(LSDa.StringText, LSDb.StringText) AS Descr, D.DealPrice AS SPrice, D.DealValue AS RPrice,
                  COALESCE(STy.Icon, SC.Icon, "Blank") AS Icon, LD.LocationLatitude AS Lat,
                  GetDistance('.$SDR['Lat'].', '.$SDR['Lng'].', LD.LocationLatitude, LD.LocationLongitude) AS Dist,
                  COALESCE(STy.CategoryID, 0) AS CAID
                 FROM 4000_Deals D
                 LEFT JOIN 0200_Language_Strings LSDa ON D.StringID = LSDa.StringID AND LSDa.LanguageID = '.$DR['LID'].'
                 LEFT JOIN 0200_Language_Strings LSDb ON D.StringID = LSDb.StringID AND LSDb.LanguageID = 1
                INNER JOIN 2000_Stores ST ON D.StoreID = St.StoreID
                INNER JOIN 2200_Store_Locations SL ON SL.StoreID = ST.StoreID
                INNER JOIN 3000_Locations LD ON LD.LocationID = SL.LocationID
                 LEFT JOIN 2110_Store_Types STy ON ST.TypeID = STy.TypeID
                 LEFT JOIN 2100_Store_Categories SC ON STy.CategoryID = SC.CategoryID
                WHERE D.DateEnds > '.date('YmdHis').'
                  AND (GetDistance('.$SDR['Lat'].', '.$SDR['Lng'].', LD.LocationLatitude, LD.LocationLongitude) <= 100
                   OR (LD.LocationLatitude = -1 AND LD.LocationLongitude = -1 AND (LD.CountryID = '.$SDR['CID'].' OR LD.CountryID = 0)))
                  AND (ST.TypeID NOT IN ('.$SDR['TFilt'].'))
                  AND (D.DealID NOT IN ('.$SDR['DFilt'].'))
                  AND (D.DealID > '.$DR['DealID'].')
                GROUP BY D.DealID
                ORDER BY Dist;');
                
            if ($QR == 0) {
            
              if (($DR['Settings'] & 16) == 16) {
                $Output .= '<hr style="border: 0; height: 1px; background-color: #C0C0C0;"></b></h2></td></tr><tr><td colspan=3><div style="color: #FF0000; font-weight: bold; font-size: 14px;">'.$Strings[$DR['LID']][2102].'</div>'.$Strings[$DR['LID']][2103].'</td></tr>';
              } else {
                $Output .= chr(13).chr(10).'-------------------------------------------------'.chr(13).chr(10).chr(13).chr(10).$Strings[$DR['LID']][2102].chr(13).chr(10).$Strings[$DR['LID']][2103].chr(13).chr(10).chr(13).chr(10);
              }            
            
            } elseif ($QR > 0) {
            
              $Total = 0;
              $SubOut = '';
            
              while ($SSDR = mysql_fetch_array($SSRS)) {
              
                if ($SSDR['DealID'] > $MaxDealID) $MaxDealID = $SSDR['DealID'];
              
                $Match = true;
                if ($SSDR['Lat'] != -1) {
                  foreach ($Categories as $Category) {
                    if ($Category[0] == $SSDR['CAID']) {
                      if ($SSDR['Dist'] > $Category[1]) {
                        $Match = false;
                        break;
                      }
                    }
                  }
                }
              
                if ($Match) {
                
                  if ($SSDR['Lat'] == -1) {
                    $Dist = 'Web';
                  } else {
                    $Dist = $SSDR['Dist'].' km';
                  }
                
                  $Days = (int)(($SSDR['DateEnds'] - time()) / 60 / 60 / 24);
                  if ($Days == 0) {
                    $When = $Strings[$DR['LID']][2104];
                  } elseif ($Days == 1) {
                    $When = $Strings[$DR['LID']][2105];
                  } else {
                    $When = str_ireplace('%a', $Days, $Strings[$DR['LID']][2106]);
                  }
                
                  if (($DR['Settings'] & 16) == 16) {
                    $SubOut .= '<tr valign="top"><td><a href="http://www.dealplotter.com/?'.$SSDR['DealID'].'"><img src="http://www.dealplotter.com/IF/Marker-'.$SSDR['Icon'].'.png" border=0></a></td><td style="padding-top: 8px;"><a style="color: #000080;" href="http://www.dealplotter.com/out.php?'.$SSDR['DealID'].'"><em>'.$SSDR['Descr'].'</em></a><div style="padding-top: 8px; font-size: 11px;"><a href="http://www.dealplotter.com/?'.$SSDR['DealID'].'"><em>'.$Strings[$DR['LID']][2107].'</em></a></div></td><td><table style="width: 90px; text-align: center; border: 1px solid #E0E0E0; border-radius: 5px; -moz-border-radius: 5px; -webkit-border-radius: 5px; font-weight: bold; color: #000080;" cellspacing=0><tr><td style="font-size: 15px; color: #00C000;"><span style="text-decoration: line-through; color: #404040; font-size: 12px;"><span style="color: #C0C0C0;"><del>'.number_format($SSDR['RPrice']).'</del></span></span> '.$SDR['CurS'].number_format($SSDR['SPrice']).'</td></tr><tr><td style="font-size: 14px;">'.$Dist.'</td></tr><tr><td><h6 style="font-size: 11px;">'.$When.'</h6></td></tr></table></td></tr>';
                  } else {
                    $SubOut .= $SSDR['Descr'].chr(13).chr(10).
                               '- '.$Strings[$DR['LID']][2113].': '.$SDR['CurS'].number_format($SSDR['RPrice']).chr(13).chr(10).
                               '- '.$Strings[$DR['LID']][2114].': '.$SDR['CurS'].number_format($SSDR['SPrice']).chr(13).chr(10).
                               '- '.$Strings[$DR['LID']][2115].': http://www.dealplotter.com/out.php?'.$SSDR['DealID'].chr(13).chr(10).
                               '- '.$Strings[$DR['LID']][2107].': http://www.dealplotter.com/?'.$SSDR['DealID'].chr(13).chr(10).chr(13).chr(10);

                  }
                  
                  $Total++;
                  
                }
              
              }
              
              if ($Total == 0) {
            
                if (($DR['Settings'] & 16) == 16) {
                  $Output .= '<hr style="border: 0; height: 1px; background-color: #C0C0C0;"></td></tr><tr><td colspan=3><div style="color: #FF0000; font-weight: bold; font-size: 14px;">'.$Strings[$DR['LID']][2102].'</div>'.$Strings[$DR['LID']][2103].'</td></tr>';
                } else {
                  $Output .= chr(13).chr(10).'-------------------------------------------------'.chr(13).chr(10).chr(13).chr(10).$Strings[$DR['LID']][2102].chr(13).chr(10).$Strings[$DR['LID']][2103].chr(13).chr(10).chr(13).chr(10);
                }   
              
              } else {
              
                if (($DR['Settings'] & 16) == 16) {
                  $Output .= ' <span style="color: #000080;">('.$Total.')</span><hr style="border: 0; height: 1px; background-color: #C0C0C0;"></td></tr>';
                } else {
                  $Output .= ' ('.$Total.')'.chr(13).chr(10).'-------------------------------------------------'.chr(13).chr(10).chr(13).chr(10);
                }
                
                if ($Total > 15) {
                  if (($DR['Settings'] & 16) == 16) {
                    $Output .= '<tr><td colspan=3><table cellpadding=8 style="border: 1px solid #E0E0E0; border-radius: 5px; -moz-border-radius: 5px; -webkit-border-radius: 5px; background-color: #F8F8F8;"><tr><td width=20><img src="http://www.dealplotter.com/IF/Icon-Info.png"></td><td>'.$Strings[$DR['LID']][2111].' '.$Strings[$DR['LID']][2103].'</td></tr></table></td></tr>';
                  } else {
                    $Output .= $Strings[$DR['LID']][2111].' '.$Strings[$DR['LID']][2103].chr(13).chr(10).chr(13).chr(10);
                  }
                }
                
                $Output .= $SubOut;
                
              }

            }
            
          }
        
        }
        
        if (($DR['Settings'] & 16) == 16) {
          $Output .= '</table><br /><table style="font-size: 13px; border: 1px solid #E0E0E0; border-radius: 5px; -moz-border-radius: 5px; -webkit-border-radius: 5px; background-color: #F8F8F8;" cellpadding=8><tr><td>'.$Strings[$DR['LID']][2108].' <b><a href="http://www.dealplotter.com/?Shaddap&Key='.$DR['CancelKey'].'">'.$Strings[$DR['LID']][2109].'</a></b> '.$Strings[$DR['LID']][2110].'</td></tr></table></body></html>';
        } else {
          $Output .= CleanHTML($Strings[$DR['LID']][2108]).': http://www.dealplotter.com/?Shaddap&Key='.$DR['CancelKey'].' '.CleanHTML(str_ireplace('<br />', chr(13).chr(10), $Strings[$DR['LID']][2110]), false);
        }

        if (SendMail($Output, $Strings[$DR['LID']][2100].' - '.date('Y-m-d'), $DR['UserEmail'])) {
        
          SysLogIt('Successfully sent digest for user with ID of '.$DR['UserID'].'.', StatusInfo, ActionNotSpecified, 0);

          if (!ExecCommand("UPDATE 1400_User_Notifications SET SentDate = ".date('YmdHis').", DealID = ".$MaxDealID." WHERE NotificationID = ".$DR['NID'].";"))
            SysLogIt('Error updating notification entry with ID of '.$DR['NID'].'.', StatusError, ActionNotSpecified, 0);

        } else {
        
          SysLogIt('Error sending digest for user with ID of '.$DR['UserID'].'.', StatusError);
          
        }
       
        
        //file_put_contents("Mail.html", $Output);

      }
      
      SysLogIt('Finished sending digests.', StatusInfo, ActionNotSpecified, 0);
    
    }
    
    return true;
  
  }
  
  function DoHistoryGraph($SID) {
  
    $Filepath = dirname(__FILE__).'/../Hist/';
  
    list ($QR, $DR, $T) =
      QuerySingle(
        "SELECT SH.HistoryID AS HID, SH.Filename AS FName, COUNT(D.DealID) AS Deals, MAX(GREATEST(D.DealPrice, D.DealValue)) AS MaxVal,
            AVG(D.DealValue) AS AvgVal, AVG(D.DealPrice) AS AvgPrc
           FROM 4000_Deals D
           LEFT JOIN 2600_Store_History SH ON D.StoreID = SH.StoreID
          WHERE D.StoreID = ".$SID."
          GROUP BY D.StoreID;");
    
    if ($QR < 0) return SysLogIt('Error searching deals for history.', StatusError, ActionSelect);
      
    if ($QR > 0) {
    
      list ($QR, $SDR, $T) =
        QuerySingle(
          'SELECT GROUP_CONCAT(X.DealID) AS DIDs, GROUP_CONCAT(X.DealPrice) AS DPrcs, GROUP_CONCAT(X.DealValue) AS DVals, GROUP_CONCAT(X.DealSourceName) AS SNams, GROUP_CONCAT(X.DEnd) AS DEnds
             FROM (
               SELECT D.StoreID, D.DealID, D.DealPrice, D.DealValue, DS.DealSourceName, UNIX_TIMESTAMP(D.DateEnds) AS DEnd
                 FROM 4000_Deals D
                INNER JOIN 4100_Deal_Sources DS ON D.DealSourceID = DS.DealSourceID
                WHERE D.StoreID = '.$SID.'
                ORDER BY D.DateEnds
             ) X
             GROUP BY X.StoreID;');
            
      if ($QR < 0) return SysLogIt('Error searching deal details for history.', StatusError, ActionSelect);
        
      if ($QR > 0) {
      
        if ($DR['Deals'] <= 1) return false;
      
        if (!is_null($DR['FName'])) {
          if (file_exists($Filepath.$DR['FName'])) unlink($Filepath.$DR['FName']);
        }
        
        $Filename = md5($SID.time()).'.svg';
        $LMarg = 15;
        $RMarg = 30;
        $TMarg = 25;
        $VHeight = 80;
        $HSpacing = (int)((400 - $LMarg - $RMarg) / ($DR['Deals'] - 1));
        
        $DPrcs = explode(',', $SDR['DPrcs']);
        $DVals = explode(',', $SDR['DVals']);
        $SNams = explode(',', $SDR['SNams']);
        $DEnds = explode(',', $SDR['DEnds']);
        
        $LPrc = 0;
        $MSav = 0;
        $MSvP = 0;
        
        for ($x=0; $x<$DR['Deals']; $x++) {
          if (((int)$DPrcs[$x] < $LPrc) || ($LPrc == 0)) $LPrc = (int)$DPrcs[$x];
          if (((int)$DVals[$x]-(int)$DPrcs[$x]) > $MSav) $MSav = (int)$DVals[$x]-(int)$DPrcs[$x];
          if ((int)(100-($DPrcs[$x]/$DVals[$x]*100)) > $MSvP) $MSvP = (int)(100-($DPrcs[$x]/$DVals[$x]*100));
        }
      
        $Output = '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="400" height="'.($TMarg+$VHeight+20+(($DR['Deals']*20)+10)+10+30).'">';
        
        //Grid
        
        for ($x=0; $x<$DR['Deals']; $x++) {
          $Output .= '<path d="M'.$LMarg.','.($TMarg+($VHeight-((int)($DPrcs[$x] / $DR['MaxVal'] * $VHeight)))).' L'.($LMarg+(($DR['Deals']-1)*$HSpacing)).','.($TMarg+($VHeight-((int)($DPrcs[$x] / $DR['MaxVal'] * $VHeight)))).'" style="stroke: #C0C0C0; stroke-width: 1; fill: none;"/>';
          $Output .= '<path d="M'.$LMarg.','.($TMarg+($VHeight-((int)($DVals[$x] / $DR['MaxVal'] * $VHeight)))).' L'.($LMarg+(($DR['Deals']-1)*$HSpacing)).','.($TMarg+($VHeight-((int)($DVals[$x] / $DR['MaxVal'] * $VHeight)))).'" style="stroke: #C0C0C0; stroke-width: 1; fill: none;"/>';
          $Output .= '<path d="M'.$LMarg.','.($TMarg+($VHeight-((int)(($DVals[$x]-$DPrcs[$x]) / $DR['MaxVal'] * $VHeight)))).' L'.($LMarg+(($DR['Deals']-1)*$HSpacing)).','.($TMarg+($VHeight-((int)(($DVals[$x]-$DPrcs[$x]) / $DR['MaxVal'] * $VHeight)))).'" style="stroke: #C0C0C0; stroke-width: 1; fill: none;"/>';
        }
        
        $Output .= '<path d="M'.$LMarg.','.$TMarg.' L'.($LMarg+(($DR['Deals']-1)*$HSpacing)).','.$TMarg.'" style="stroke: #607080; stroke-width: 1; fill: none;"/>';
        $Output .= '<text x="'.($LMarg + (400 - $RMarg) - 10).'" y="'.($TMarg+5).'" style="font-family: Arial; font-size:12px; stroke: #607080;">'.$DR['MaxVal'].'</text>';
        
        $Output .= '<path d="M'.$LMarg.','.($TMarg+$VHeight).' L'.($LMarg+(($DR['Deals']-1)*$HSpacing)).','.($TMarg+$VHeight).'" style="stroke: #607080; stroke-width: 1; fill: none;"/>';
        $Output .= '<text x="'.($LMarg + (400 - $RMarg) - 10).'" y="'.($TMarg+$VHeight+5).'" style="font-family: Arial; font-size:12px; stroke: #607080;">0</text>';
        
        for ($x=0; $x<$DR['Deals']; $x++) {
          $Output .= '<path d="M'.($LMarg+($x*$HSpacing)).','.$TMarg.' L'.($LMarg+($x*$HSpacing)).','.($TMarg + $VHeight).'" style="stroke: #607080; stroke-width: 1; fill: none;"/>';
        }
        
        //Text
        for ($x=0; $x<$DR['Deals']; $x++) {
          $Output .= '<circle cx="'.($LMarg+($x*$HSpacing)).'" cy="'.($TMarg-15).'" r="8" style="fill:#000000"/>';
          $Output .= '<text x="'.($LMarg+($x*$HSpacing)).'" y="'.($TMarg-11).'" style="font-family: Arial; font-size:10px; fill: #FFFFFF; stroke: #FFFFFF;" text-anchor="middle">'.($x+1).'</text>';
        }
        /*
        for ($x=0; $x<$DR['Deals']; $x++) {
          $Output .= '<text x="'.($LMarg+0+($x*$HSpacing)).'" y="'.($TMarg-10).'" transform="rotate(270 '.($LMarg+0+($x*$HSpacing)).','.($TMarg-10).')" style="font-family: Arial; font-size:12px;">'.date('Y.m.d', $DEnds[$x]).'</text>';
          $Output .= '<text x="'.($LMarg+10+($x*$HSpacing)).'" y="'.($TMarg-10).'" transform="rotate(270 '.($LMarg+10+($x*$HSpacing)).','.($TMarg-10).')" style="font-family: Arial; font-size:12px;">'.$SNams[$x].'</text>';
        }
        */

        //Values
        $Output .= '<path d="';
        for ($x=0; $x<$DR['Deals']; $x++) {
          $Output .= (($x==0)?'M':'L').($LMarg+($x*$HSpacing)).','.($TMarg+($VHeight-((int)($DVals[$x] / $DR['MaxVal'] * $VHeight)))).' ';
        }
        $Output .= '" style="stroke: #974d57; stroke-width: 2; fill: none;"/>';

        for ($x=0; $x<$DR['Deals']; $x++) {
          $Output .= '<circle cx="'.($LMarg+($x*$HSpacing)).'" cy="'.($TMarg+($VHeight-((int)($DVals[$x] / $DR['MaxVal'] * $VHeight)))).'" r="4" style="fill:#974d57"/>';
        }
        
        //Prices
        $Output .= '<path d="';
        for ($x=0; $x<$DR['Deals']; $x++) {
          $Output .= (($x==0)?'M':'L').($LMarg+($x*$HSpacing)).','.($TMarg+($VHeight-((int)($DPrcs[$x] / $DR['MaxVal'] * $VHeight)))).' ';
        }
        $Output .= '" style="stroke: #448541; stroke-width: 2; fill: none;"/>';

        for ($x=0; $x<$DR['Deals']; $x++) {
          $Output .= '<circle cx="'.($LMarg+($x*$HSpacing)).'" cy="'.($TMarg+($VHeight-((int)($DPrcs[$x] / $DR['MaxVal'] * $VHeight)))).'" r="4" style="fill:#448541"/>';
        }

        //Savings
        $Output .= '<path d="';
        for ($x=0; $x<$DR['Deals']; $x++) {
          $Output .= (($x==0)?'M':'L').($LMarg+($x*$HSpacing)).','.($TMarg+($VHeight-((int)(($DVals[$x] - $DPrcs[$x]) / $DR['MaxVal'] * $VHeight)))).' ';
        }
        $Output .= '" style="stroke: #0000FF; stroke-width: 2; fill: none;"/>';

        for ($x=0; $x<$DR['Deals']; $x++) {
          $Output .= '<circle cx="'.($LMarg+($x*$HSpacing)).'" cy="'.($TMarg+($VHeight-((int)(($DVals[$x] - $DPrcs[$x]) / $DR['MaxVal'] * $VHeight)))).'" r="4" style="fill:#0000FF"/>';
          //$Output .= '<text x="'.($LMarg+3+($x*$HSpacing)).'" y="'.(($TMarg+($VHeight-((int)(($DVals[$x] - $DPrcs[$x]) / $DR['MaxVal'] * $VHeight))))-5).'" style="font-family: Arial; font-size:10px; fill: #0000FF;">'.(int)($DPrcs[$x] / $DVals[$x] * 100).'%</text>';
        }
        
        //Tables
        for ($x=0; $x<$DR['Deals']; $x++) {
          if (($x % 2) != 0) $Output .= '<rect x="0" y="'.($TMarg+$VHeight+25+($x*20)).'" width="400" height="20" style="stroke-width: 0; fill: #F0F0F0;" />';
        }
        
        $Output .= '<rect x="0" y="'.($TMarg+$VHeight+20).'" rx="10" ry="10" width="400" height="'.((($DR['Deals']+1)*20)+20).'" style="stroke: #C0C0C0; fill: none;" />';
        
        for ($x=0; $x<$DR['Deals']; $x++) {
          if ((int)$DPrcs[$x] == $LPrc) $Output .= '<rect x="298" y="'.($TMarg+$VHeight+25+($x*20)).'" rx="5" ry="5" width="40" height="20" style="stroke: #009900; stroke-width: 1; fill: none;" />';
          if (((int)$DVals[$x] - (int)$DPrcs[$x]) == $MSav) $Output .= '<rect x="248" y="'.($TMarg+$VHeight+25+($x*20)).'" rx="5" ry="5" width="40" height="20" style="stroke: #009900; stroke-width: 1; fill: none;" />';
          if ((int)(100-($DPrcs[$x]/$DVals[$x]*100)) == $MSvP) $Output .= '<rect x="348" y="'.($TMarg+$VHeight+25+($x*20)).'" rx="5" ry="5" width="40" height="20" style="stroke: #009900; stroke-width: 1; fill: none;" />';
          $Output .= '<text x="0" y="'.($TMarg+$VHeight+40+($x*20)).'" style="font-family: Arial; font-size:12px;">';
          $Output .= '<tspan x="5" style="font-weight: bold;">'.($x+1).'.</tspan>';
          $Output .= '<tspan x="22">'.date('Y.m.d', $DEnds[$x]).'</tspan>';
          $Output .= '<tspan x="90">'.$SNams[$x].'</tspan>';
          $Output .= '<tspan x="200" style="fill: #974d57">'.$DVals[$x].'</tspan>';
          $Output .= '<tspan x="240">-</tspan>';
          $Output .= '<tspan x="250" style="fill: #0000FF">'.($DVals[$x]-$DPrcs[$x]).'</tspan>';
          $Output .= '<tspan x="290">=</tspan>';
          $Output .= '<tspan x="300" style="fill: #448541">'.$DPrcs[$x].'</tspan>';
          $Output .= '<tspan x="350" style="fill: #0000FF">('.(int)(100-($DPrcs[$x] / $DVals[$x] * 100)).'%)</tspan>';
          $Output .= '</text>';
        }
        
        $Output .= '<line x1="0" y1="'.(($TMarg+$VHeight+20)+(($DR['Deals']*20)+10)).'" x2="400" y2="'.(($TMarg+$VHeight+20)+(($DR['Deals']*20)+10)).'" style="stroke: #C0C0C0;" />';
        $Output .= '<text x="0" y="'.(($TMarg+$VHeight+20)+(($DR['Deals']*20)+10)+20).'" style="font-family: Arial; font-size:12px; font-weight: bold;">';
        $Output .= '<tspan x="20" style="font-weight: bold;">=</tspan>';
        $Output .= '<tspan x="200" style="fill: #974d57">'.(int)$DR['AvgVal'].'</tspan>';
        $Output .= '<tspan x="240">-</tspan>';
        $Output .= '<tspan x="250" style="fill: #0000FF">'.((int)$DR['AvgVal']-(int)$DR['AvgPrc']).'</tspan>';
        $Output .= '<tspan x="290">=</tspan>';
        $Output .= '<tspan x="300" style="fill: #448541">'.(int)$DR['AvgPrc'].'</tspan>';
        $Output .= '<tspan x="350" style="fill: #0000FF">('.(int)(100-((int)$DR['AvgPrc'] / (int)$DR['AvgVal'] * 100)).'%)</tspan>';
        $Output .= '</text>';
        $Output .= '<image width="11" height="12" x="5" y="'.(($TMarg+$VHeight+20)+(($DR['Deals']*20)+10)+8).'" xlink:href="/IF/Avg.png" />';
        
        $Output .= '</svg>';
        
        $File = fopen($Filepath.$Filename, 'x');
        if ($File === false) return SysLogIt('Error opening history graph for output.', StatusError);
        fwrite($File, $Output);
        fclose($File);
        
        if (file_exists($Filepath.$Filename)) {
        
          if (is_null($DR['HID'])) {

            if (!ExecCommand("INSERT INTO 2600_Store_History (StoreID, Filename, LastUpdated) VALUES (".$SID.", '".Pacify($Filename)."', ".date('YmdHis').");"))
              return SysLogIt('Error adding history graph for store with ID of '.$SID.'. File '.$Filename.' is orphaned.', StatusError, ActionInsert);

          } else {

            if (!ExecCommand("UPDATE 2600_Store_History SET Filename = '".Pacify($Filename)."', LastUpdated = ".date('YmdHis')." WHERE HistoryID = ".$DR['HID'].";"))
              return SysLogIt('Error updating history graph for store with ID of '.$SID.'. File '.$Filename.' is orphaned.', StatusError, ActionUpdate);

          }
          
          SysLogIt('Created history graph for store with ID of '.$SID.'.', StatusInfo);
          return $Filename;
          
        }
        
        SysLogIt('Could not find generated history file for store with ID of '.$SID.'.', StatusError);
        unlink($Filepath.$Filename);
      
      }

    } else { 
    
      return SysLogIt('Could not find deals for history.', StatusError, ActionSelect);
    
    }
    
    return false;
  
  }
  
?>
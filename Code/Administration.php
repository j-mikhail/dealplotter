<?php 
  
  function GetTags($LID = 1) {  
  /*/////////////////////////////////////////////////////////////
       Author: Plottery Corp.
      Created: v1.0.0 - 2010-12-19
    Revisions: None
      Purpose: Display category and type keywords
      Returns: Nothing
  *//////////////////////////////////////////////////////////////
  
    global $Response;

    if ($RandomKey = GetNewAccessKey()) {
    
      $Response->J = 'F5();';
      
     //Retrieve categories, types and keywords
      list ($QR, $RS, $T) = QuerySet('SELECT SC.CategoryID, SC.Icon AS XIcon, LSSC.StringText AS CategoryName, SCT.KeywordsID AS XKeywordsID, SCT.Keywords AS XKeywords, ST.TypeID, ST.Icon AS Icon, LSST.StringText AS TypeName, STT.KeywordsID, STT.Keywords
                                         FROM 2100_Store_Categories SC
                                           LEFT JOIN 0200_Language_Strings LSSC ON LSSC.StringID = SC.StringID AND LSSC.LanguageID = '.$LID.'
                                         LEFT JOIN 2101_Store_Category_Keywords SCT ON SCT.CategoryID = SC.CategoryID AND SCT.LanguageID = '.$LID.'
                                         LEFT JOIN 2110_Store_Types ST ON ST.CategoryID = SC.CategoryID
                                           LEFT JOIN 0200_Language_Strings LSST ON LSST.StringID = ST.StringID AND LSST.LanguageID = '.$LID.'
                                         LEFT JOIN 2111_Store_Type_Keywords STT ON STT.TypeID = ST.TypeID AND STT.LanguageID = '.$LID.'
                                        ORDER BY CategoryName ASC, TypeName ASC;');

      if ($QR < 0) { 
        
        SysLogIt('Query returned an error.', StatusError, ActionSelect);
        
      } else {
      
        $Response->S = true;
        $Response->R = '<DIV CLASS="ttlw"><FORM onSubmit="SavTag(); return false;"><INPUT TYPE="hidden" ID="Key" VALUE="'.$RandomKey.'"><INPUT TYPE="submit" CLASS="butt" VALUE="Save"><INPUT TYPE="button" CLASS="mrgl butt shwl rbrds mgrr" VALUE="New Category" onClick="AddCat();"><SPAN CLASS="" ID="StrMsg"></SPAN><HR></DIV><DIV CLASS="abs fulls flwa"><TABLE CELLPADDING=2 CELLSPACING=0 BORDER=0 WIDTH="100%" ID="MchTbl"><TBODY>';

        $NumCategories = 0;
        $LastCategory = 0;
        
        while ($DR = mysql_fetch_array($RS)) {
          
          if ($DR['CategoryID'] != $LastCategory) {
          
            $Response->R .= '<TR><TD><INPUT CLASS="w150" ID="C'.$DR['CategoryID'].'" VALUE="'.$DR['CategoryName'].'" onChange="Mark(this);"><INPUT CLASS="w300 mrgl" ID="X'.$DR['XKeywordsID'].'" VALUE="'.$DR['XKeywords'].'" onChange="Mark(this);"><INPUT CLASS="w80 mrgl" ID="J'.$DR['CategoryID'].'" VALUE="'.$DR['XIcon'].'" onChange="Mark(this);"><INPUT TYPE="Button" CLASS="butt mrgl" VALUE="New Type" onClick="AddTyp(this,-'.$DR['CategoryID'].')"></TD></TR>';
            
            $LastCategory = $DR['CategoryID'];
            $NumCategories++;
          
          }
          
          if (!is_null($DR['TypeID'])) $Response->R .= '<TR><TD><INPUT CLASS="w150 mrgl" ID="T'.$DR['TypeID'].'" VALUE="'.$DR['TypeName'].'" onChange="Mark(this);"><INPUT CLASS="w70p mrgl" ID="K'.$DR['KeywordsID'].'" VALUE="'.$DR['Keywords'].'" onChange="Mark(this);"><INPUT CLASS="w80 mrgl" ID="I'.$DR['TypeID'].'" VALUE="'.$DR['Icon'].'" onChange="Mark(this);"></TD></TR>';
          
        }
        
        $Response->R .= '</TBODY></TABLE></FORM></DIV>';
        $Response->J = 'AddScr("/Scripts/Admin.js"); NewCat = '.-$NumCategories.';';
        
      }
      
    }
    
    $Response->Send();
    
  }
  
  function SetTags($LID = 1) {
  /*/////////////////////////////////////////////////////////////
       Author: Plottery Corp.
      Created: v1.0.0 - 2010-12-19
    Revisions: None
      Purpose: Saves category and type keywords
      Returns: Nothing
  *//////////////////////////////////////////////////////////////
  
    global $Response;
    
    $Response->J = 'F5();';   
  
    if (isset($_POST['Key'])) {
    
      if ($AKey = ValidAccessKey($_POST['Key'])) {
  
        //Validation
        
        //Save
        DeleteAccessKey($AKey);
        $Response->J = 'EdtTag(true);';
        
        $TypeID = 0;
        $LastID = 0;
        $LastKey = '';
        $CategoryID = 0;

        foreach ($_POST as $Key => $Value) {
        
          if ($Key != 'Key') {
          
            $ID = (int)substr($Key, 1);
          
            switch (strtolower(substr($Key, 0, 1))) {
            
              case 'c':
              
                if ($ID < 0) {
                
                  //Create new category and matching string
                  if (!$StringID = CreateNewString($LID, 10000, 19999, 'Category', $Value)) $Response->Send();
                  if (!$CategoryID = InsertAndRetrieveID("INSERT INTO 2100_Store_Categories (CategoryName, StringID) VALUES ('".Pacify($Value)."',".$StringID.");", "category")) $Response->Send();
                  $LastID = $ID;
                  
                } else {
                
                  //Find category ID
                  list ($QR, $DR, $T) = QuerySingle("SELECT SC.CategoryID, SC.StringID FROM 2100_Store_Categories SC WHERE SC.CategoryID = ".(int)$ID.";");                  
                  if ($QR < 1) {
                    SysLogIt('Error retrieving category with ID of '.$ID.'.', StatusError, ActionSelect); 
                    $Response->Send();
                  }
                  
                  if ($LID > 1) {
                    //Update category
                    if (ExecCommand("UPDATE 2100_Store_Categories SET CategoryName = '".Pacify($Value)."' WHERE CategoryID = ".$DR['CategoryID'].";")) {
                      SysLogIt('Updated category with ID of '.$DR['CategoryID'].'.', StatusInfo, ActionUpdate);
                    } else {
                      SysLogIt('Error updating category with ID of '.$DR['CategoryID'].'.', StatusError, ActionUpdate);
                      $Response->Send();
                    }
                  }
                  
                  //Update string
                  if (ExecCommand("UPDATE 0200_Language_Strings SET StringText = '".Pacify($Value)."' WHERE StringID = ".$DR['StringID']." AND LanguageID = ".$LID.";")) {
                    SysLogIt('Updated string with ID of '.$DR['CategoryID'].'.', StatusInfo, ActionUpdate);
                  } else {
                    SysLogIt('Error updating string with ID of '.$DR['StringID'].'.', StatusError, ActionUpdate);
                    $Response->Send();
                  }
                  
                }
                break;
                
              //-----------
                
              case 'x':
              
                if ($ID < 0 && $LastID = $ID && $CategoryID > 0) {
                
                  //Create new category keywords
                  if (ExecCommand("INSERT INTO 2101_Store_Category_Keywords (CategoryID, LanguageID, Keywords) VALUES (".$CategoryID.",".$LID.",'".Pacify($Value)."');")) {
                    SysLogIt('Created keywords for category with ID of '.$CategoryID.'.', StatusInfo, ActionInsert);                  
                  } else {
                    SysLogIt('Error creating keywords for category with ID '.$CategoryID.'.', StatusError, ActionInsert);                  
                    $Response->Send();
                  }            
                  
                } elseif ($ID > 0) {
                
                  //Update existing category keywords
                  if (ExecCommand("UPDATE 2101_Store_Category_Keywords SET Keywords = '".Pacify($Value)."' WHERE KeywordsID = ".$ID.";")) {
                    SysLogIt('Updated category keywords with ID of '.$ID.'.', StatusInfo, ActionUpdate);                  
                  } else {
                    SysLogIt('Error updating category keywords with ID of '.$ID.'.', StatusError, ActionUpdate);
                    $Response->Send();
                  }            
                  
                }
                
                break;
                
              //-----------
                
              case 'j':
              
                $UpdateID = 0;
                if ($ID < 0 && $LastID = $ID && $CategoryID > 0) {
                  $UpdateID = $CategoryID;
                } else {
                  $UpdateID = $ID;
                }
                
                if ($UpdateID > 0 && trim($Value) != '') {
                
                  //Update existing category keywords
                  if (ExecCommand("UPDATE 2100_Store_Categories SET Icon = '".Pacify(trim($Value))."' WHERE CategoryID = ".$UpdateID.";")) {
                    SysLogIt('Updated icon for category with ID of '.$UpdateID.'.', StatusInfo, ActionUpdate);                  
                  } else {
                    SysLogIt('Error updating icon for category with ID of '.$UpdateID.'.', StatusError, ActionUpdate);
                    $Response->Send();
                  }            
                  
                }
                
                break;
                
              //-----------
                
              case 't':
              
                if (stripos($ID, '-', 1) !== false) $ID = (int)substr($ID, 0, stripos($ID, '-', 1));
                                
                if ($ID < 0) {
                
                  //Create new type and matching string
                  if (!$StringID = CreateNewString($LID, 10000, 19999, 'Type', $Value)) $Response->Send();
                  if (!$TypeID = InsertAndRetrieveID("INSERT INTO 2110_Store_Types (CategoryID, TypeName, StringID) VALUES (".-$ID.",'".Pacify($Value)."',".$StringID.");", "type")) $Response->Send();
                  
                  $LastID = $ID;
                  $LastKey = substr($Key, 1);
                
                } else {
                
                  //Find type ID
                  list ($QR, $DR, $T) = QuerySingle("SELECT TypeID, StringID FROM 2110_Store_Types WHERE TypeID = ".(int)$ID.";");                  
                  if ($QR < 1) {
                    SysLogIt('Error retrieving type with ID of '.$ID.'.', StatusError, ActionSelect); 
                    $Response->Send();
                  }
                  
                  if ($LID > 1) {
                    //Update type
                    if (ExecCommand("UPDATE 2110_Store_Types SET TypeName = '".Pacify($Value)."' WHERE TypeID = ".$DR['TypeID'].";")) {
                      SysLogIt('Updated type with ID of '.$DR['TypeID'].'.', StatusInfo, ActionUpdate);
                    } else {
                      SysLogIt('Error updating type with ID of '.$DR['TypeID'].'.', StatusError, ActionUpdate);
                      $Response->Send();
                    }
                  }
                  
                  //Update string
                  if (ExecCommand("UPDATE 0200_Language_Strings SET StringText = '".Pacify($Value)."' WHERE StringID = ".$DR['StringID']." AND LanguageID = ".$LID.";")) {
                    SysLogIt('Updated string with ID of '.$DR['TypeID'].'.', StatusInfo, ActionUpdate);
                  } else {
                    SysLogIt('Error updating string with ID of '.$DR['StringID'].'.', StatusError, ActionUpdate);
                    $Response->Send();
                  }                
                }
                
                break;
              
              //-----------
              
              case 'k':
              
                if (stripos($ID, '-', 1) !== false) $ID = (int)substr($ID, 0, stripos($ID, '-', 1));
                
                if ($ID < 0 && substr($Key, 1) == $LastKey && $TypeID > 0) {
                
                  //Create new type keywords
                  if (ExecCommand("INSERT INTO 2111_Store_Type_Keywords (TypeID, LanguageID, Keywords) VALUES (".$TypeID.",".$LID.",'".Pacify($Value)."');")) {
                    SysLogIt('Created keywords for type with ID '.$TypeID.'.', StatusInfo, ActionInsert);                  
                  } else {
                    SysLogIt('Error creating keywords for type with ID '.$TypeID.'.', StatusError, ActionInsert);                  
                    $Response->Send();
                  }            
                  
                } elseif ($ID > 0) {
                
                  //Update existing type keywords
                  if (ExecCommand("UPDATE 2111_Store_Type_Keywords SET Keywords = '".Pacify($Value)."' WHERE KeywordsID = ".$ID.";")) {
                    SysLogIt('Updated type keywords with ID '.$ID.'.', StatusInfo, ActionUpdate);                  
                  } else {
                    SysLogIt('Error updating type keywords with ID of '.$ID.'.', StatusError, ActionUpdate);
                    $Response->Send();
                  }            
                  
                }

                break;

              //-----------
                
              case 'i':
              
                if (stripos($ID, '-', 1) !== false) $ID = (int)substr($ID, 0, stripos($ID, '-', 1));
              
                $UpdateID = 0;
                if ($ID < 0 && substr($Key, 1) == $LastKey && $TypeID > 0) {
                  $UpdateID = $TypeID;
                } else {
                  $UpdateID = $ID;
                }
                
                if ($UpdateID > 0 && trim($Value) != '') {
                
                  //Update existing type keywords
                  if (ExecCommand("UPDATE 2110_Store_Types SET Icon = '".Pacify(trim($Value))."' WHERE TypeID = ".$UpdateID.";")) {
                    SysLogIt('Updated icon for type with ID of '.$UpdateID.'.', StatusInfo, ActionUpdate);                  
                  } else {
                    SysLogIt('Error updating icon for type with ID of '.$UpdateID.'.', StatusError, ActionUpdate);
                    $Response->Send();
                  }            
                  
                }
                
                break;
                
            }
            
          }
          
        }
        
        $Response->S = true;
        $Response->Send();
        
      }
      
    }
    
  }
  
  function GetSources($LID = 1) {  
  /*/////////////////////////////////////////////////////////////
       Author: Plottery Corp.
      Created: v1.0.0 - 2011-01-17
    Revisions: None
      Purpose: Display category and type keywords
      Returns: Nothing
  *//////////////////////////////////////////////////////////////
  
    global $Response;

    if ($RandomKey = GetNewAccessKey()) {
    
      $Response->J = 'F5();';
      
     //Retrieve categories, types and keywords
      list ($QR, $RS, $T) = QuerySet('SELECT * FROM 4100_Deal_Sources;');

      if ($QR < 0) { 
        
        SysLogIt('Query returned an error.', StatusError, ActionSelect);
        
      } else {
      
        $Response->S = true;
        $Response->R = '<DIV CLASS="ttlw"><FORM onSubmit="SavSrc(); return false;"><INPUT TYPE="hidden" ID="Key" VALUE="'.$RandomKey.'"><INPUT TYPE="submit" CLASS="butt" VALUE="Save"><INPUT TYPE="button" CLASS="mrgl butt shwl rbrds mgrr" VALUE="New Source" onClick="AddSrc();"><SPAN CLASS="" ID="StrMsg"></SPAN><HR></DIV><DIV CLASS="abs fulls flwa"><TABLE CELLPADDING=2 CELLSPACING=0 BORDER=0 ID="SrcTbl"><TBODY>';

        if ($QR > 0) {
        
          while ($DR = mysql_fetch_array($RS)) {
            
            $Response->R .= '<TR>
                               <TD><INPUT CLASS="w100" ID="D'.$DR['DealSourceID'].'" VALUE="'.$DR['DealSourceName'].'" onChange="Mark(this);"></TD>
                               <TD><INPUT CLASS="w300" ID="H'.$DR['DealSourceID'].'" VALUE="'.$DR['DealSourceHomepage'].'" onChange="Mark(this);"></TD>
                               <TD><INPUT CLASS="w100" ID="F'.$DR['DealSourceID'].'" VALUE="'.$DR['DealSourceFileName'].'" onChange="Mark(this);"></TD>
                               <TD><INPUT CLASS="w150" ID="R'.$DR['DealSourceID'].'" VALUE="'.$DR['DealSourceRefCode'].'" onChange="Mark(this);"></TD>
                               <TD><SELECT ID="S'.$DR['DealSourceID'].'" CLASS="w100 mrgls" onChange="Mark(this);">';
            
            for ($x=0;$x<2;$x++) {
              $Response->R .= '<OPTION VALUE="'.$x.'"';
              if ($x == $DR['DealSourceStatus']) $Response->R .= ' SELECTED';
              $Response->R .= '>'.GS(1545+$x).'</OPTION>';
            }
            
            $Response->R .= '   </SELECT></TD>
                               <TD><INPUT CLASS="w80" ID="E'.$DR['DealSourceID'].'" VALUE="'.$DR['DealSourceResetTime'].'" onChange="Mark(this);"></TD>
                            </TR>';
            
          }
          
        }
        
        $Response->R .= '</TBODY></TABLE></FORM></DIV>';
        $Response->J = 'AddScr("/Scripts/Admin.js"); NewSrc = 0;';
        
      }
      
    }
    
    $Response->Send();
    
  }
  
  function SetSources() {
  /*/////////////////////////////////////////////////////////////
       Author: Plottery Corp.
      Created: v1.0.0 - 
    Revisions: None
      Purpose:
      Returns: Nothing
  *//////////////////////////////////////////////////////////////
  
    global $Response;
    
    $Response->J = 'F5();';   
  
    if (isset($_POST['Key'])) {
    
      if ($AKey = ValidAccessKey($_POST['Key'])) {
  
        //Validation
        
        //Save
        DeleteAccessKey($AKey);
        $Response->J = 'EdtSrc(true);';
        
        $LastSourceID = 0;
        $PreHTML = '';
        $PostHTML = '';
        
        foreach ($_POST as $Key => $Value) {
        
          if ($Key != 'Key') {
          
            $ID = (int)substr($Key, 1);
            
            if ($ID != $LastSourceID) {
              if ($LastSourceID != 0) {
                
                if ($LastSourceID < 0) {
                
                  $PreHTML = substr($PreHTML, 0, -1);
                  $PostHTML = substr($PostHTML, 0, -1);
                
                  if (ExecCommand("INSERT INTO 4100_Deal_Sources (".$PreHTML.") VALUES (".$PostHTML.");")) {
                    SysLogIt('Successfully created new source.', StatusInfo, ActionInsert);
                  } else {
                    SysLogIt('Error creating new source.', StatusError, ActionInsert);
                  }
                  
                } else {
                
                  $PreHTML = substr($PreHTML, 0, -1);
                
                  if (ExecCommand("UPDATE 4100_Deal_Sources SET ".$PreHTML." WHERE DealSourceID = ".$LastSourceID.";")) {
                    SysLogIt('Successfully updated source with ID of '.$LastSourceID.'.', StatusInfo, ActionUpdate);
                  } else {
                    SysLogIt('Error updating source with ID of '.$LastSourceID.'.', StatusError, ActionUpdate);
                  }
                  
                }
                
              }
              
              $LastSourceID = $ID;
              $PreHTML = '';
              $PostHTML = '';              
              
            }
            
            switch (strtolower(substr($Key, 0, 1))) {
            
              case 'd':
                if ($ID < 0) {
                  $PreHTML .= 'DealSourceName,';
                  $PostHTML .= "'".Pacify($Value)."',";
                  if (isset($_POST['S'.$ID])) {
                    $PreHTML .= 'DealSourceStatus,';
                    $PostHTML .= (int)$_POST['S'.$ID].",";
                  }
                } else {
                  $PreHTML .= "DealSourceName = '".Pacify($Value)."',";
                }
                break;
                
              case 'h':
                if ($ID < 0) {
                  $PreHTML .= 'DealSourceHomepage,';
                  $PostHTML .= "'".Pacify($Value)."',";
                } else {
                  $PreHTML .= "DealSourceHomepage = '".Pacify($Value)."',";
                }
                break;
                
              case 'f':
                if ($ID < 0) {
                  $PreHTML .= 'DealSourceFileName,';
                  $PostHTML .= "'".Pacify($Value)."',";
                } else {
                  $PreHTML .= "DealSourceFileName = '".Pacify($Value)."',";
                }
                break;
                
              case 'r':
                if ($ID < 0) {
                  $PreHTML .= 'DealSourceRefCode,';
                  $PostHTML .= "'".Pacify($Value)."',";
                } else {
                  $PreHTML .= "DealSourceRefCode = '".Pacify($Value)."',";
                }
                break;
                
              case 's':
                if ($ID > 0) $PreHTML .= "DealSourceStatus = ".(int)$Value.",";
                break;                
                
              case 'e':
                if ($ID < 0) {
                  $PreHTML .= 'DealSourceResetTime,';
                  $PostHTML .= (int)$Value.",";
                } else {
                  $PreHTML .= "DealSourceResetTime = ".(int)$Value.",";
                }
                break;                
            }
          
          }
          
        }
        
        if ($LastSourceID != 0) {
          
          if ($LastSourceID < 0) {
          
            $PreHTML = substr($PreHTML, 0, -1);
            $PostHTML = substr($PostHTML, 0, -1);
          
            if (ExecCommand("INSERT INTO 4100_Deal_Sources (".$PreHTML.") VALUES (".$PostHTML.");")) {
              SysLogIt('Successfully created new source.', StatusInfo, ActionInsert);
            } else {
              SysLogIt('Error creating new source.', StatusError, ActionInsert);
            }
            
          } else {
          
            $PreHTML = substr($PreHTML, 0, -1);
          
            if (ExecCommand("UPDATE 4100_Deal_Sources SET ".$PreHTML." WHERE DealSourceID = ".$LastSourceID.";")) {
              SysLogIt('Successfully updated source with ID of '.$LastSourceID.'.', StatusInfo, ActionUpdate);
            } else {
              SysLogIt('Error updating source with ID of '.$LastSourceID.'.', StatusError, ActionUpdate);
            }
            
          }

        }
        
        $Response->S = true;
        $Response->Send();        
        
      }
      
    }
    
  }
  
  function GetDivisions($LID = 1) {  
  /*/////////////////////////////////////////////////////////////
       Author: Plottery Corp.
      Created: v1.0.0 - 2011-02-21
    Revisions: None
      Purpose: Display divisions
      Returns: Nothing
  *//////////////////////////////////////////////////////////////
  
    global $Response;

    if ($RandomKey = GetNewAccessKey()) {
    
      $Response->J = 'F5();';
      
     //Retrieve categories, types and keywords
      list ($QR, $RS, $T) = QuerySet('SELECT * FROM 4110_Deal_Source_URLs ORDER BY RemoteID;');

      if ($QR < 0) { 
        
        SysLogIt('Query returned an error.', StatusError, ActionSelect);
        
      } else {
      
        $Response->S = true;
        $Response->R = '<DIV CLASS="ttlw"><FORM onSubmit="SavDiv(); return false;"><INPUT TYPE="hidden" ID="Key" VALUE="'.$RandomKey.'"><INPUT TYPE="submit" CLASS="butt" VALUE="Save"><INPUT TYPE="button" CLASS="mrgl butt shwl rbrds mgrr" VALUE="New Division" onClick="AddDiv();"><INPUT TYPE="button" CLASS="mrgl butt shwl rbrds mgrr" VALUE="Get Timezones" onClick="FndTZ();"><SPAN CLASS="" ID="StrMsg"></SPAN><HR></DIV><DIV CLASS="abs fulls flwa"><TABLE CELLPADDING=2 CELLSPACING=0 BORDER=0 ID="DivTbl"><TBODY>';

        while ($DR = mysql_fetch_array($RS)) {
          
          $Response->R .= '<TR>
                             <TD><INPUT CLASS="w30" ID="D'.$DR['URLID'].'" VALUE="'.$DR['DealSourceID'].'" onChange="Mark(this);"></TD>
                             <TD><INPUT CLASS="w30" ID="L'.$DR['URLID'].'" VALUE="'.$DR['LanguageID'].'" onChange="Mark(this);"></TD>
                             <TD><INPUT CLASS="w30" ID="C'.$DR['URLID'].'" VALUE="'.$DR['CountryID'].'" onChange="Mark(this);"></TD>
                             <TD><INPUT CLASS="w150" ID="R'.$DR['URLID'].'" VALUE="'.$DR['RemoteID'].'" onChange="Mark(this);"></TD>
                             <TD><INPUT CLASS="w150" ID="U'.$DR['URLID'].'" VALUE="'.$DR['URL'].'" onChange="Mark(this);"></TD>
                             <TD><INPUT CLASS="w30" ID="T'.$DR['URLID'].'" VALUE="'.$DR['TimeZone'].'" onChange="Mark(this);"></TD>
                             <TD><INPUT CLASS="w100" ID="A'.$DR['URLID'].'" VALUE="'.$DR['Latitude'].'" onChange="Mark(this);"></TD>
                             <TD><INPUT CLASS="w100" ID="O'.$DR['URLID'].'" VALUE="'.$DR['Longitude'].'" onChange="Mark(this);"></TD>
                             <TD><INPUT CLASS="w100" ID="N'.$DR['URLID'].'" VALUE="'.$DR['NextUpdate'].'" onChange="Mark(this);"></TD>
                             <TD><INPUT TYPE="Button" CLASS="butt pnt" onClick="FndLL('.$DR['URLID'].');" VALUE="LL"></TD>
                          </TR>';

        }
        
        $Response->R .= '</TBODY></TABLE></FORM></DIV>';
        $Response->J = 'AddScr("/Scripts/Admin.js"); NewDiv = 0;';
        
      }
      
    }
    
    $Response->Send();
    
  }
  
  function SetDivisions() {
  /*/////////////////////////////////////////////////////////////
       Author: Plottery Corp.
      Created: v1.0.0 - 
    Revisions: None
      Purpose:
      Returns: Nothing
  *//////////////////////////////////////////////////////////////
  
    global $Response;
    
    $Response->J = 'F5();';   
  
    if (isset($_POST['Key'])) {
    
      if ($AKey = ValidAccessKey($_POST['Key'])) {
  
        //Validation
        
        //Save
        DeleteAccessKey($AKey);
        $Response->J = 'EdtDiv(true);';
        
        $LastURLID = 0;
        $PreHTML = '';
        $PostHTML = '';
        
        foreach ($_POST as $Key => $Value) {
        
          if ($Key != 'Key') {
          
            $ID = (int)substr($Key, 1);
            
            if ($ID != $LastURLID) {
              if ($LastURLID != 0) {
                
                if ($LastURLID < 0) {
                
                  $PreHTML = substr($PreHTML, 0, -1);
                  $PostHTML = substr($PostHTML, 0, -1);
                
                  if (ExecCommand("INSERT INTO 4110_Deal_Source_URLs (".$PreHTML.") VALUES (".$PostHTML.");")) {
                    SysLogIt('Successfully created new division.', StatusInfo, ActionInsert);
                  } else {
                    SysLogIt('Error creating new division.', StatusError, ActionInsert);
                  }
                  
                } else {
                
                  $PreHTML = substr($PreHTML, 0, -1);
                
                  if (ExecCommand("UPDATE 4110_Deal_Source_URLs SET ".$PreHTML." WHERE URLID = ".$LastURLID.";")) {
                    SysLogIt('Successfully updated division with ID of '.$LastURLID.'.', StatusInfo, ActionUpdate);
                  } else {
                    SysLogIt('Error updating division with ID of '.$LastURLID.'.', StatusError, ActionUpdate);
                  }
                  
                }
                
              }
              
              $LastURLID = $ID;
              $PreHTML = '';
              $PostHTML = '';              
              
            }
            
            switch (strtolower(substr($Key, 0, 1))) {
            
              case 'd':
                if ($ID < 0) {
                  $PreHTML .= 'DealSourceID,';
                  $PostHTML .= (int)$Value.",";
                } else {
                  $PreHTML .= "DealSourceID = ".(int)$Value.",";
                }
                break;
                
              case 'l':
                if ($ID < 0) {
                  $PreHTML .= 'LanguageID,';
                  $PostHTML .= (int)$Value.",";
                } else {
                  $PreHTML .= "LanguageID = ".(int)$Value.",";
                }
                break;
                
              case 'c':
                if ($ID < 0) {
                  $PreHTML .= 'CountryID,';
                  $PostHTML .= (int)$Value.",";
                } else {
                  $PreHTML .= "CountryID = ".(int)$Value.",";
                }
                break;

              case 'r':
                if ($ID < 0) {
                  $PreHTML .= 'RemoteID,';
                  $PostHTML .= "'".Pacify($Value)."',";
                } else {
                  $PreHTML .= "RemoteID = '".Pacify($Value)."',";
                }
                break;
                
              case 'u':
                if ($ID < 0) {
                  $PreHTML .= 'URL,';
                  $PostHTML .= "'".Pacify($Value)."',";
                } else {
                  $PreHTML .= "URL = '".Pacify($Value)."',";
                }
                break;

              case 't':
                if ($ID < 0) {
                  $PreHTML .= 'TimeZone,';
                  $PostHTML .= (int)$Value.",";
                } else {
                  $PreHTML .= "TimeZone = ".(int)$Value.",";
                }
                break;
                
              case 'a':
                if ($ID < 0) {
                  $PreHTML .= 'Latitude,';
                  $PostHTML .= (double)$Value.",";
                } else {
                  $PreHTML .= "Latitude = ".(double)$Value.",";
                }
                break;
                
              case 'o':
                if ($ID < 0) {
                  $PreHTML .= 'Longitude,';
                  $PostHTML .= (double)$Value.",";
                } else {
                  $PreHTML .= "Longitude = ".(double)$Value.",";
                }
                break;
                
              case 'n':
                if ($ID < 0) {
                  $PreHTML .= 'NextUpdate,';
                  $PostHTML .= (int)$Value.",";
                } else {
                  $PreHTML .= "NextUpdate = ".(int)$Value.",";
                }
                break;
                
                
            }
          
          }
          
        }
        
        if ($LastURLID != 0) {
          
          if ($LastURLID < 0) {
          
            $PreHTML = substr($PreHTML, 0, -1);
            $PostHTML = substr($PostHTML, 0, -1);
          
            if (ExecCommand("INSERT INTO 4110_Deal_Source_URLs (".$PreHTML.") VALUES (".$PostHTML.");")) {
              SysLogIt('Successfully created new division.', StatusInfo, ActionInsert);
            } else {
              SysLogIt('Error creating new division.', StatusError, ActionInsert);
            }
            
          } else {
          
            $PreHTML = substr($PreHTML, 0, -1);
          
            if (ExecCommand("UPDATE 4110_Deal_Source_URLs SET ".$PreHTML." WHERE URLID = ".$LastURLID.";")) {
              SysLogIt('Successfully updated division with ID of '.$LastURLID.'.', StatusInfo, ActionUpdate);
            } else {
              SysLogIt('Error updating division with ID of '.$LastURLID.'.', StatusError, ActionUpdate);
            }
            
          }

        }
        
        $Response->S = true;
        $Response->Send();        
        
      }
      
    }
    
  }
  
  function SetTimezones() {

    global $Response;
    
    $Response->J = 'F5();';   
  
    if (isset($_POST['Key'])) {
    
      if ($AKey = ValidAccessKey($_POST['Key'])) {
  
        DeleteAccessKey($AKey);
        $Response->J = 'EdtDiv(true);';
        
        list ($QR, $RS, $T) = QuerySet('SELECT URLID, Latitude, Longitude FROM 4110_Deal_Source_URLs WHERE Latitude != 0 AND Longitude != 0 AND TimeZone = 0 '.Limits.';');

        if ($QR < 0) { 
          
          SysLogIt('Could not search for missing timezones.', StatusError, ActionSelect);
          
        } elseif ($QR > 0) {
        
          while ($DR = mysql_fetch_array($RS)) {
          
            list ($CData, $DURL) = GetWebData('http://api.geonames.org/timezoneJSON?formatted=true&lat='.$DR['Latitude'].'&lng='.$DR['Longitude'].'&username=dealplotter');
            if ($CData !== false) {

              $DataArray = json_decode($CData, true);
              if (!is_null($DataArray)) {
              
                if (array_key_exists('gmtOffset', $DataArray)) {
                  if (ExecCommand("UPDATE 4110_Deal_Source_URLs SET TimeZone = ".(int)$DataArray['gmtOffset']." WHERE URLID = ".$DR['URLID'].";"))
                    SysLogIt('Set time zone for division with ID of '.$DR['URLID'].' to '.(int)$DataArray['gmtOffset'].'.', StatusInfo, ActionUpdate);
                }
              
              }
              
            }
          
          }
          
          $Response->S = true;
        
        }

      }

    }
    
    $Response->Send();
  
  }

  
  function GetStrings() {
  /*/////////////////////////////////////////////////////////////
       Author: Plottery Corp.
      Created: v1.0.0 - 2010-12-09
    Revisions: None
      Purpose: Display language strings
      Returns: Nothing
  *//////////////////////////////////////////////////////////////
  
    global $Response;
  
    if ($RandomKey = GetNewAccessKey()) {
  
      $Response->J = 'F5();';
        
      if ($NumLanguages = CountLanguages()) {

        list ($QR, $RS, $T) = QuerySet('SELECT S.StringID, S.Description, LS.LinkID, LS.LanguageID, LS.StringText AS SText
                                          FROM 0100_Strings S
                                          LEFT JOIN 0200_Language_Strings LS ON S.StringID = LS.StringID
                                         WHERE S.StringID < 100000
                                         ORDER BY S.StringID ASC, LS.LanguageID ASC;');
        
        if ($QR < 0) {
        
          SysLogIt('Error searching for strings.', StatusError, ActionSelect);
          
        } else {
        
          $Response->S = true;
          $Response->J = 'AddScr("/Scripts/Admin.js");';
          $Response->R = '<DIV CLASS="ttlw"><FORM onSubmit="SavStr(); return false;"><INPUT TYPE="hidden" ID="Key" VALUE="'.$RandomKey.'"><INPUT TYPE="submit" CLASS="butt" VALUE="Save"><INPUT TYPE="button" CLASS="mrgl butt shwl rbrds mgrr" VALUE="New String" onClick="AddStr('.$NumLanguages.');"><SPAN CLASS="" ID="StrMsg"></SPAN><HR></DIV><DIV CLASS="abs fulls flwa"><TABLE CELLPADDING=2 CELLSPACING=0 BORDER=0 ID="StrTbl"><TBODY>';
          
          $LastID = 0;
          $ExpectedLanguageID = 0;
        
          if ($QR > 0) {
          
            while ($DR = mysql_fetch_array($RS)) {
            
              if ($DR['StringID'] != $LastID) {
              
                if ($LastID > 0) {
                  for ($x=$ExpectedLanguageID; $x<=$NumLanguages; $x++) {
                    $Response->R .= '<TD><INPUT CLASS="w300" TYPE="input" ID="X'.$LastID.'-'.$x.'" onChange="Mark(this);"></TD>';
                  }
                  $Response->R .= '</TR>';
                }
                
                $Response->R .= '<TR><TD><INPUT CLASS="w80" TYPE="input" ID="S'.$DR['StringID'].'" VALUE="'.$DR['StringID'].'" onChange="Mark(this);"></TD><TD><INPUT CLASS="w150" TYPE="input" ID="D'.$DR['StringID'].'" VALUE="'.$DR['Description'].'" onChange="Mark(this);"></TD>';
                $LastID = $DR['StringID'];
                $ExpectedLanguageID = 1;
                
              }
              
              if (!is_null($DR['LanguageID'])) {
                for ($x=$ExpectedLanguageID; $x<$DR['LanguageID']; $x++) {
                  $Response->R .= '<TD><INPUT CLASS="w300" TYPE="input" ID="X'.$DR['StringID'].'-'.$x.'" onChange="Mark(this);"></TD>';
                }
                
                $Response->R .= '<TD><INPUT CLASS="w300" TYPE="input" ID="L'.$DR['LinkID'].'" VALUE="'.$DR['SText'].'" onChange="Mark(this);"></TD>';
                $ExpectedLanguageID = $DR['LanguageID']+1;
              }
              
            }
          
            for ($x=$ExpectedLanguageID; $x<=$NumLanguages; $x++) {
              $Response->R .= '<TD><INPUT CLASS="w300" TYPE="input" ID="X'.$LastID.'-'.$x.'" onChange="Mark(this);"></TD>';
            }
            $Response->R .= '</TR>';
            
          }
          
          $Response->R .= '</TBODY></TABLE></FORM></DIV>';
          
        }
        
      }
      
      $Response->Send();
      
    }
    
  }
  
  function SetStrings() {
  /*/////////////////////////////////////////////////////////////
       Author: Plottery Corp.
      Created: v1.0.0 - 2010-12-09
    Revisions: None
      Purpose: Set language strings
      Returns: Nothing
  *//////////////////////////////////////////////////////////////
  
    global $Response;
    
    $Response->J = 'F5();';  
  
    if (isset($_POST['Key'])) {
    
      if ($AKey = ValidAccessKey($_POST['Key'])) {
      
        if ($NumLanguages = CountLanguages()) {
        
          //Validation
          foreach ($_POST as $Key => $Value) {
           
            $ID = (int)substr($Key, 1);
          
            switch (strtolower(substr($Key, 0, 1))) {
            
              case 's':
                if (!is_numeric($Value) || (int)$Value == 0) {
                  $Response->R = 'Valid number required.';
                  $Response->J = "Foc('".$Key."');";
                  $Response->Send();
                }
                
                if ($ID < 0) {
                  list ($QR, $DR, $T) = QuerySingle("SELECT StringID FROM 0100_Strings WHERE StringID = ".(int)$Value.";");
                  if ($QR > 0) {
                    $Response->R = 'This ID is already in use.';
                    $Response->J = "Foc('".$Key."');";
                    $Response->Send();
                  }
                }

                for ($x=1; $x<=$NumLanguages; $x++) {
                  if (!isset($_POST['X'.$ID.'-'.$x])) {
                    $Response->R = 'Missing strings for language with ID of '.$x.'.';
                    $Response->J = "Foc('".$Key."');";
                    $Response->Send();                    
                  }
                }

                break;
                
              case 'd':
                if (trim($Value) == '') {
                  $Response->R = 'Field can not be blank.';
                  $Response->J = "Foc('".$Key."');";
                  $Response->Send();              
                }
                break;
                
            }
            
          }
          
          //Saving
          DeleteAccessKey($AKey);
          $Response->J = 'EdtStr(true);';
          
          $LastID = 0;
          $StringID = 0;
          
          foreach ($_POST as $Key => $Value) {
          
            $ID = (int)substr($Key, 1);
            $UpdateID = 0;
          
            switch (strtolower(substr($Key, 0, 1))) {
            
              case 's':
                if ($ID < 0) {
                  if (!($StringID = InsertNewString("INSERT INTO 0100_Strings (StringID) VALUES (".(int)$Value.");", (int)$Value))) $Response->Send();
                  $LastID = $ID;
                }
                break;
                
              case 'd':
                if ($ID < 0 && $LastID == $ID && $StringID > 0) {
                  $UpdateID = $StringID;
                } elseif ($ID > 0) {
                  $UpdateID = $ID;
                }
                
                if ($UpdateID > 0) {
                
                  if (ExecCommand("UPDATE 0100_Strings SET Description = '".Pacify($Value)."' WHERE StringID = ".$UpdateID.";")) {
                    SysLogIt('Updated string description with ID of '.$UpdateID.'.', StatusInfo, ActionUpdate);   
                  } else {
                    SysLogIt('Error updating string description with ID of '.$UpdateID.'.', StatusError, ActionUpdate);   
                    $Response->Send();
                  }
                  
                }
                break;
                
              case 'l':
                if (ExecCommand("UPDATE 0200_Language_Strings SET StringText = '".Pacify($Value)."' WHERE LinkID = ".$ID.";")) {
                  SysLogIt('Updated language string with ID of '.$ID.'.', StatusInfo, ActionUpdate);   
                } else {
                  SysLogIt('Error updating language string with ID of '.$ID.'.', StatusError, ActionUpdate);   
                  $Response->Send();
                }
                break;
                
              case 'x':
                $ID = (int)substr($Key, 1, strlen($Key)-3);
                $Lang = (int)substr($Key, strlen($Key)-1);
                if ($Lang > 0 && $Lang <= $NumLanguages) {
                  
                  if ($ID < 0 && $LastID = $ID && $StringID > 0) {
                    $UpdateID = $StringID;
                  } elseif ($ID > 0) {
                    $UpdateID = $ID;
                  }
                  
                  if ($UpdateID > 0) {
                  
                    if (!(InsertAndRetrieveID("INSERT INTO 0200_Language_Strings (LanguageID, StringID, StringText) VALUES (".$Lang.",".$UpdateID.",'".Pacify($Value)."');", 'language string'))) $Response->Send();
                    
                  }
                  
                }
                break;
              
            }
            
          }
          
          $Response->S = true;
            
        }
        
      }
      
    }
    
    $Response->Send();
    
  }
  
  function GetTypes() {
  
    global $Response;
  
    if ($RandomKey = GetNewAccessKey()) {
  
      $Response->J = 'F5();';
      
      list ($TQR, $TRS, $T) = QuerySet('SELECT LSSC.StringText AS CategoryName, ST.TypeID, LSST.StringText AS TypeName
                                          FROM 2110_Store_Types ST
                                           INNER JOIN 0200_Language_Strings LSST ON LSST.StringID = ST.StringID AND LSST.LanguageID = 1
                                         INNER JOIN 2100_Store_Categories SC ON ST.CategoryID = SC.CategoryID
                                           INNER JOIN 0200_Language_Strings LSSC ON LSSC.StringID = SC.StringID AND LSSC.LanguageID = 1
                                        ORDER BY CategoryName ASC, TypeName ASC;');

      if ($TQR < 0) {
      
        SysLogIt('Error searching for strings.', StatusError, ActionSelect);
        
      } else {       
      
        list ($QR, $RS, $T) = QuerySet('SELECT D.StoreID, LS.StringText AS DealText, S.TypeID
                                          FROM 4000_Deals D
                                         INNER JOIN 2000_Stores S ON D.StoreID = S.StoreID
                                         INNER JOIN 0200_Language_Strings LS ON D.StringID = LS.StringID AND LS.LanguageID = 1
                                         WHERE D.DateEnds > '.date('YmdHis').'
                                         GROUP BY D.DealID;');
        
        if ($QR < 0) {
        
          SysLogIt('Error searching for strings.', StatusError, ActionSelect);
          
        } else {
        
          $Response->S = true;
          $Response->J = 'AddScr("/Scripts/Admin.js");';
          $Response->R = '<DIV CLASS="ttlw"><FORM onSubmit="SavTyp(); return false;"><INPUT TYPE="hidden" ID="Key" VALUE="'.$RandomKey.'"><INPUT TYPE="submit" CLASS="butt" VALUE="Save"><HR></DIV><DIV CLASS="abs fulls flwa"><TABLE CELLPADDING=2 CELLSPACING=0 BORDER=0 ID="TypTbl"><TBODY>';
          
          $Count = 0;
          
          while ($DR = mysql_fetch_array($RS)) {
          
            $Count++;
          
            $Response->R .= '<TR '.(($Count % 2 == 0)?'CLASS="row"':'').'><TD>'.$DR['DealText'].'</TD><TD><SELECT ID="'.$DR['StoreID'].'" onChange="Mark(this);"><OPTION VALUE="0" ';
              if ($DR['TypeID'] == 0) $Response->R .= 'SELECTED';
            $Response->R .= '>*COULD NOT AUTOMATCH*</OPTION>';
            
            if ($TQR > 0) {
              mysql_data_seek($TRS, 0);
              while ($TDR = mysql_fetch_array($TRS)) {
                $Response->R .= '<OPTION VALUE="'.$TDR['TypeID'].'"';
                  if ($DR['TypeID'] == $TDR['TypeID']) $Response->R .= 'SELECTED';
                $Response->R .= '>'.$TDR['CategoryName'].' -> '.$TDR['TypeName'].'</OPTION>';
              }
            }
            
            $Response->R .= '</TD></TR>';
          
          }
          
          $Response->R .= '</TBODY></TABLE></FORM></DIV>';

        }
        
      }
      
    }

    $Response->Send();    
  
  }
  
  function SetTypes() {
  /*/////////////////////////////////////////////////////////////
       Author: Plottery Corp.
      Created: v1.0.0 - 2010-12-09
    Revisions: None
      Purpose: Set deal types
      Returns: Nothing
  *//////////////////////////////////////////////////////////////
  
    global $Response;
    
    $Response->J = 'F5();';  
  
    if (isset($_POST['Key'])) {
    
      if ($AKey = ValidAccessKey($_POST['Key'])) {
      
        //Saving
        DeleteAccessKey($AKey);
      
        //Validation
        foreach ($_POST as $Key => $Value) {
          if (is_numeric($Key) && is_numeric($Value)) {
          
            if (ExecCommand("UPDATE 2000_Stores SET TypeID = '".(int)$Value."' WHERE StoreID = ".(int)$Key.";")) {
              SysLogIt('Updated store with ID of '.(int)$Key.' to type with ID of '.(int)$Value.'.', StatusInfo, ActionUpdate);   
            } else {
              SysLogIt('Error updating store with ID of '.(int)$Key.'.', StatusError, ActionUpdate);   
              $Response->Send();
            }      
            
          }
        }
        
        $Response->J = 'EdtTyp(true);';
        $Response->S = true;
        
      }
      
    }
    
    $Response->Send();
    
  }
  
  function GetLog() {  
  /*/////////////////////////////////////////////////////////////
       Author: Plottery Corp.
      Created: v1.0.0 - 2011-04-09
    Revisions: None
      Purpose: Display log
      Returns: Nothing
  *//////////////////////////////////////////////////////////////
  
    global $Response;
    $Response->J = 'PopErr();';

    if ($RandomKey = GetNewAccessKey()) {
    
      $LastGID = 0;
      
      if (isset($_POST['G'])) {
      
        if (is_numeric($_POST['G'])) {
        
          list ($QR, $RS, $T) =
            QuerySet('
              SELECT SL.EntryType AS ETP, SL.EntryMessage AS EM, GroupID AS GID, SL.EntryFunction AS EFu
                FROM 0500_System_Log SL
               WHERE SL.GroupID = '.(float)$_POST['G'].'
               ORDER BY SL.EntryID DESC
               LIMIT 1,300;');
               
          if ($QR < 0) { 
            
            SysLogIt('Error searching for group entries.', StatusError, ActionSelect);
            
          } elseif ($QR > 0) {
          
            $Response->S = true;
            $Response->D = (float)$_POST['G'];
            $Response->R = '';
            
            while ($DR = mysql_fetch_array($RS)) {
            
              if ($DR['ETP'] == StatusError) $Response->R .= '<B>';
              $Response->R .= $DR['EM'];
              if ($DR['ETP'] == StatusError) $Response->R .= '</B>';
              $Response->R .= '<BR />';
              
            }
            
            $Response->R = rtrim($Response->R, '<BR />');
            $Response->R = substr($Response->R, 0, strripos($Response->R, '<BR />'));
          
          }
          
        }
      
      } else {
      
        $Offset = 0;
        if (isset($_POST['O'])) {
          if (is_numeric($_POST['O'])) $Offset = $_POST['O'];
        }
      
        list ($QR, $RS, $T) =
          QuerySet('
            SELECT SL.EntryID AS EID, SL.EntryType AS ETP, UNIX_TIMESTAMP(SL.EntryTimestamp) AS TS, SL.EntryMessage AS EM, SL.EntryIP AS EIP,
                   COALESCE(U.UserUsername, "Guest") AS UName, SL.UserID AS UID, GroupID AS GID, SL.EntryFunction AS EFu
              FROM 0500_System_Log SL
              LEFT JOIN 1000_Users U ON SL.UserID = U.UserID
             ORDER BY SL.EntryID DESC
             LIMIT '.$Offset.',1000;');
        
        if ($QR < 0) { 
          
          SysLogIt('Error searching for log entries.', StatusError, ActionSelect);
          
        } else {
        
          $Response->S = true;
          if ($Offset == 0) $Response->R = '<DIV CLASS="ttlw"><INPUT TYPE="hidden" ID="Key" VALUE="'.$RandomKey.'"><INPUT TYPE="submit" CLASS="butt" VALUE="Load More Records" onClick="GetRec();"><SPAN CLASS="" ID="StrMsg"></SPAN><HR></DIV><DIV ID="LogDiv" CLASS="abs fulls flwa"><TABLE CELLPADDING=1 CELLSPACING=0 BORDER=0 ID="LogTbl">';
          
          if ($QR > 0) {
          
            $Entries = 0;
            $LastGID = 0;

            while ($DR = mysql_fetch_array($RS)) {
            
              $RowClass = 'sz14';
              if ($DR['ETP'] > 1) $RowClass .= ' b';
              if ($Entries % 2 == 0) $RowClass .= ' row';
              
              if (($DR['GID'] != $LastGID) || ($DR['GID'] == 0)) {
              
                if ($LastGID > 0) {
                
                  $Response->R .= '<TR CLASS="sz14 '.$RowC.'">
                                     <TD CLASS="padrs padls" NOWRAP><DIV></DIV></TD>
                                     <TD CLASS="padrs" NOWRAP><DIV></DIV></TD>
                                     <TD CLASS="padrs" ID="LOGG'.$LastGID.'"><DIV CLASS="'.(($Errs > 0)?'b':'').'">&nbsp;&nbsp;+'.($Etrs-1).' entries: '.$Errs.' error(s), '.$Wrns.' warning(s)</DIV></TD>
                                     <TD CLASS="padrs"><DIV></DIV></TD>
                                     <TD CLASS="padrs"><DIV></DIV></TD>
                                     <TD CLASS="padrs"><DIV></DIV></TD>
                                  </TR>
                                   <TR CLASS="sz14 '.$RowC.'">
                                     <TD CLASS="padrs padls" NOWRAP><DIV></DIV></TD>
                                     <TD CLASS="padrs" NOWRAP>'.date('Y-m-d H:i:s', $LstTS).'<DIV></DIV></TD>
                                     <TD CLASS="padrs"><DIV>'.$LstMsg.'</DIV></TD>
                                     <TD CLASS="padrs"><DIV></DIV></TD>
                                     <TD CLASS="padrs"><DIV></DIV></TD>
                                     <TD CLASS="padrs"><DIV></DIV></TD>
                                  </TR>';
                }
              
                $Errs = 0;
                $Wrns = 0;
                $Etrs = 0;
                $RowC = $RowClass;
                
                $Entries++;
              
                $Response->R .= '<TR ID="LOGE'.$DR['EID'].'" CLASS="sz14 '.$RowClass.'">
                                   <TD CLASS="padrs padls" NOWRAP>'.((($DR['GID'] != $LastGID) && ($DR['GID'] > 0))?'<DIV CLASS="fklnk" onClick="GetGrp(this,'.$DR['GID'].')">[+]</DIV>':'<DIV></DIV>').'</TD>
                                   <TD CLASS="padrs" NOWRAP><DIV>'.date('Y-m-d H:i:s', $DR['TS']).'</DIV></TD>
                                   <TD CLASS="padrs"><DIV>'.$DR['EM'].'</DIV></TD>
                                   <TD CLASS="padrs"><DIV>'.(($DR['ETP'] > StatusInfo)?$DR['EFu']:'').'</DIV></TD>
                                   <TD CLASS="padrs"><DIV>'.$DR['EIP'].'</DIV></TD>
                                   <TD CLASS="padrs" NOWRAP><DIV>'.$DR['UName'].' ('.$DR['UID'].')</DIV></TD>
                                </TR>';
              
              } else {
              
                $LstTS = $DR['TS'];
                $LstMsg = $DR['EM'];
                $Etrs++;
                if ($DR['ETP'] == StatusError) $Errs++;
                if ($DR['ETP'] == StatusWarning) $Wrns++;
                
              }
              
              $LastGID = $DR['GID'];
            
            }
            
            if ($Offset == 0) $Response->R .= '</TABLE></DIV>';
            
          }
          
          $Response->J = 'AddScr("/Scripts/Admin.js"); NxtSet = '.($Offset+1000).';';
          
        }
        
      }
      
    }
    
    $Response->Send();
    
  }
  
  function GetStatus() {  
  /*/////////////////////////////////////////////////////////////
       Author: Plottery Corp.
      Created: v1.0.0 - 2011-04-09
    Revisions: None
      Purpose: Display status
      Returns: Nothing
  *//////////////////////////////////////////////////////////////
  
    global $Response;
    $Response->J = 'F5();';

    if ($RandomKey = GetNewAccessKey()) {
    
      list ($QR, $RS, $T) =
        QuerySet('
          SELECT DS.DealSourceName AS Name, COALESCE(Dx.DCnt, 0) AS DCount, COALESCE(Dy.ACnt, 0) AS ACount
            FROM 4100_Deal_Sources DS
            LEFT JOIN (SELECT D.DealSourceID, COUNT(D.DealID) AS DCnt FROM 4000_Deals D WHERE D.DateEnds > '.date('YmdHis').' GROUP BY D.DealSourceID) Dx ON DS.DealSourceID = Dx.DealSourceID
            LEFT JOIN (SELECT D.DealSourceID, ROUND(COUNT(D.DealID)/30, 0) AS ACnt FROM 4000_Deals D WHERE D.DateListed > '.date('YmdHis', mktime(date('H'),date('i'),date('s'),date('n'),date('j')-30,date('Y'))).' GROUP BY D.DealSourceID) Dy ON DS.DealSourceID = Dy.DealSourceID
           WHERE DS.DealSourceStatus & 1 = 1
           GROUP BY DS.DealSourceID
           ORDER BY DS.DealSourceName ASC;');
      
      if ($QR < 0) { 
        
        SysLogIt('Error searching for source deal counts.', StatusError, ActionSelect);
        
      } else {
      
        $Response->S = true;
        $Response->R = '<DIV CLASS="ttlw">Status<INPUT TYPE="hidden" ID="Key" VALUE="'.$RandomKey.'"><HR></DIV><DIV ID="SttDiv" CLASS="abs fulls flwa algc"><TABLE CELLPADDING=1 CELLSPACING=0 BORDER=0 ID="SttTbl" CLASS="fra rbrds mgrx"><TBODY><TR><TD COLSPAN=3 CLASS="b padbs algc">Deal Counts<HR></TD></TR><TR CLASS="sz10 b algc"><TD CLASS="padls algl">Name</TD><TD>Cur.</TD><TD>Avg.</TD></TR>';
        
        if ($QR > 0) {
        
          $Entries = 0;
        
          while ($DR = mysql_fetch_array($RS)) {
          
            $Entries++;
            
            $RowClass = 'sz14';
            if ($Entries % 2 == 0) $RowClass .= ' row';
            
            $Response->R .= '<TR CLASS="sz14 '.$RowClass.'">
                               <TD CLASS="padrs padls" NOWRAP><DIV>'.$DR['Name'].'</DIV></TD>
                               <TD CLASS="padrs padls algc" NOWRAP><DIV>'.$DR['DCount'].'</DIV></TD>
                               <TD CLASS="padrs padls algc" NOWRAP><DIV>'.$DR['ACount'].'</DIV></TD>
                            </TR>';

          }
          
        }
        
        $Response->R .= '</TBODY></TABLE></DIV>';
        $Response->J = '';
        
      }
      
    }
    
    $Response->Send();
    
  }

?>


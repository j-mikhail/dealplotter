//////////////////////////////////////////////////////////////////////////
// GLOBAL VARIABLES
//////////////////////////////////////////////////////////////////////////

var request = false;
var FndLoc = 0;

var loadstr, closestr, errormsg;

//////////////////////////////////////////////////////////////////////////
// SHORTCUTS
//////////////////////////////////////////////////////////////////////////

function getI(ID) { return document.getElementById(ID); }
function getN(Name) { return document.getElementsByName(Name); }
function getT(Tag) { return document.getElementsByTagName(Tag); }

function Enc(X) { return encodeURIComponent(X); }
function TR(ID) { return trim(getI(ID).value); }
function IsE(ID) { return (TR(ID) == ''); }
function IsR() { return (Locs[CurLoc].ID > 0); }
function Fix(S) { try { return decodeURIComponent(escape(S)); } catch(err) { return S; } }

//////////////////////////////////////////////////////////////////////////
// MAP FUNCTIONS
//////////////////////////////////////////////////////////////////////////

var LSet = 0;
var MMap;
var GeoC = new google.maps.Geocoder();
var NfoW = new google.maps.InfoWindow();
var PanT;

var NWOpt = { maxWidth: 350 }

var CurLoc = -1, FstLoc = -1, PenLoc = -1;

var OptLck = {
  zoom: 10,
  mapTypeControl: false,
  navigationControl: false,
  streetViewControl: false,
  disableDefaultUI: true,
  scaleControl: false,
  scrollwheel: false,
  draggable: false,
  mapTypeId: google.maps.MapTypeId.SATELLITE
};

var OptStd = {
  zoom: 11,
  mapTypeControl: false,
  navigationControl: false,
  streetViewControl: false,
  disableDefaultUI: true,
  scaleControl: false,
  scrollwheel: true,
  draggable: true,    
  mapTypeId: google.maps.MapTypeId.ROADMAP
};

var OptWeb = {
  zoom: 11,
  mapTypeControl: false,
  navigationControl: false,
  streetViewControl: false,
  disableDefaultUI: true,
  scaleControl: false,
  scrollwheel: false,
  draggable: true,
  mapTypeId: google.maps.MapTypeId.TERRAIN
};

function SetBackMap() {
  
  MMap = new google.maps.Map(getI("backmap"), OptLck);
  
  google.maps.event.addListener(MMap, 'dragstart', function() {
    clearTimeout(PanT);
  });
  
  getI('CpR').className = 'wht';
  
}

//-----------------

function MapDet(Lat, Lng) {

  MMap.setOptions(OptStd);
  newLocation = new google.maps.LatLng(Lat, Lng);
  CtrOn(newLocation);
  getI('CpR').className = '';

}

function CtrOn(L) { MMap.setCenter(L); }
function PanTo(L) { MMap.panTo(L); }
function PanH() { if (!AcWin) PanTo(Locs[CurLoc].L.getPosition()); };
function PanM(DID,LID) {

  switch (CurTab) {
    case 1: MArr = Locs[CurLoc].D; break;
    case 2: MArr = Locs[CurLoc].F; break;
    case 3: MArr = Locs[CurLoc].W; break;
  }
  
  MIdx = FndIdxID(MArr, DID);
  if (MIdx !== false) {
    LIdx = FndIdxID(MArr[MIdx].L, LID);
    if (LIdx !== false) {
      M = MArr[MIdx].L[LIdx].L;
      P = M.getPosition();
      PanTo(P);
    }
  }
  
}
//function SPanM(X,I) { PanTmr = setTimeout('PanM('+X+','+I+')', 5000); }
//function EPanM() { clearTimeout(PanTmr); }

function GotoM() {

  if (!(CurLoc in Locs)) {
    for (Lx in Locs) {
      FstLoc = Lx;
      CurLoc = Lx;
      break;
    }
  }
  
  MapDet(Locs[CurLoc].A, Locs[CurLoc].O);
  
}

function SetEvt(LID,M,DID) {
  google.maps.event.addListener(M, 'click', function() { GetDet(DID); PanM(DID,LID); } );
  google.maps.event.addListener(M, 'mouseover', function() { if (PanOL) { HiTmr = setTimeout('HighLM('+DID+')', 200); } else { ShoIWD(M,DID,LID); } } );
  google.maps.event.addListener(M, 'mouseout', function() { if (PanOL) { clearTimeout(HiTmr); UHighLM(DID); } else { CloIW(); } } );
}

function SetHEvt(M,E) {

  google.maps.event.addListener(M, 'mouseover', function() { ShoIW(M,E); } );
  google.maps.event.addListener(M, 'mouseout', function() { CloIW(); } );
  
}

function ShoIWD(M,DID,LID) {

  switch (CurTab) {
    case 1: MArr = Locs[CurLoc].D; break;
    case 2: MArr = Locs[CurLoc].F; break;
    case 3: MArr = Locs[CurLoc].W; break;
  }
  
  MIdx = FndIdxID(MArr, DID);
  if (MIdx !== false) {
    D = MArr[MIdx];
    LIdx = FndIdxID(D.L, LID);
    if (LIdx !== false) {  
      L = D.L[LIdx];
      if (CurTab!=2) {
        CIdx = FndIdxID(Cats, D.C);
        if (CIdx !== false) {
          TIdx = FndIdxID(Cats[CIdx].T, D.T);
          if (TIdx !== false) ShoIW(M, '<DIV CLASS="Deal"><DIV CLASS="ctrls rbrds"><DIV CLASS="sz12 gray"><SPAN CLASS="dkgray s"><SPAN CLASS="gray">'+addC(D.RP)+'</SPAN></SPAN> <SPAN CLASS="dkgr sz15">'+Locs[CurLoc].S+''+addC(D.SP)+'</SPAN> (<SPAN NAME="svngs" CLASS="sav">-'+Math.round((1-(D.SP/D.RP))*100)+'%</SPAN>)</DIV>'+((CurTab==1)?'<DIV CLASS="sav sz15">'+L.D+' km</DIV>':'')+'<DIV ID="TX'+D.ID+'"></DIV></DIV><SPAN CLASS="pnt">'+D.E+'</SPAN><BR /><SPAN CLASS="pnt print dkgray"><BR />'+GS(1308)+' '+Cats[CIdx].N+': '+Cats[CIdx].T[TIdx].N+'</SPAN></DIV>');
        }
      } else {
        if (D.D == 0) {
          DLeft = "Never";
        } else {
          DLeft = Math.round((D.D - (new Date())) / 1000 / 60 / 60 / 24) + ' days';
        }      
        ShoIW(M, '<DIV CLASS="Deal"><DIV CLASS="wctrls rbrds"><SPAN CLASS="sav">'+D.V+'</SPAN> &bull; <SPAN>'+DLeft+'</SPAN></DIV><SPAN CLASS="pnt">'+D.E+'</SPAN></DIV>');
      }
    }
  }

}

function ShoIW(M,T) {
  NfoW.setContent(T);
  NfoW.setOptions(NWOpt);
  NfoW.open(MMap, M);
}

function CloIW() {
  NfoW.setContent('');
  NfoW.close();
}

//-----------------

function GetLocation() {

  //var RandomLocation = new google.maps.LatLng((Math.random()*180)+1-90, (Math.random()*360)+1-180);
  DoRndLoc();
  LSet = 0;

  // Try W3C Geolocation (Preferred)
  if(navigator.geolocation) {
    
    var Timeout = setTimeout(function() {
      clearTimeout(Timeout);
      SetNoLocMsg();
    }, 1000 * 20);
  
    navigator.geolocation.getCurrentPosition(function(position) {
    
      clearTimeout(Timeout);
      
      if (getI('LocMsg')) {
      
        LSet = 1;
      
        initialLocation = new google.maps.LatLng(position.coords.latitude,position.coords.longitude);
        MMap.setCenter(initialLocation);
        GetLocationName(initialLocation);
        
        //getI('Coords').value = initialLocation;
        //FndLoc = 1;
        
      }
        
    }, function() {
    
      clearTimeout(Timeout);
      SetNoLocMsg();
      
    });
  // Browser doesn't support Geolocation
  } else {
    SetNoLocMsg();
  }
  
}

function DoRndLoc() {

  var RandomLocation = new google.maps.LatLng((Math.random()*17)+1+34, -((Math.random()*41)+1+77));
  MMap.setCenter(RandomLocation);

}

function RstLoc() {

  FndLoc = 0;
  ClDIV('LocMsg');
  getI('SubLoc').value = GS(1110);
  getI('Coords').value = '';
  
}

function SetNoLocMsg() {

  if (getI('LocMsg') != null && LSet == 0) {
  
    UpDIV('LocMsg', GS(1204), 'nfomsg');
    if (getI('DPUsername') == '' && getI('DPPassword') == '') Foc('InLoc');
    
  }
  
  LSet = 1;

}

//-----------------

function GetLocationName(foundLocation) {

  GeoC.geocode({'latLng': foundLocation}, function(results, status) {
    if (status == google.maps.GeocoderStatus.OK) {
      if (results[0]) {
      
        getI('InLoc').value = results[0].formatted_address;
        UpDIV('LocMsg', GS(1217), 'nfomsg');
        
        getI('SubLoc').value = GS(1216);
        
        var Ctry = -1;
        for (x=0; x<results[0].address_components.length; x++) {
          if (results[0].address_components[x].types[0] == 'country') {
            Ctry = x;
            break;
          }
        }
        if (Ctry == -1) { PopErr(); return false; }
        
        getI('Coords').value = results[0].geometry.location+', '+results[0].address_components[Ctry].short_name;
        
        FndLoc = 1;
        //ClDIV('LocMsg');
      }
    }
  });
  
}

//-----------------

function ChkAddr(B) {

  Foc('InLoc');
  
  var InQ = getI("InLoc").value;
  if (InQ == '') {
  
    UpDIV('LocMsg', GS(1202), 'wrnmsg');
  
  } else {
  
    if (FndLoc==1) {
    
      SubAdr(getI('Coords').value);
    
    } else {
  
      GeoC.geocode( {'address': InQ}, function(results, status) {
        if (status == google.maps.GeocoderStatus.OK) {
          if (results.length == 1) {
          
            var Ctry = -1;
            for (x=0; x<results[0].address_components.length; x++) {
              if (results[0].address_components[x].types[0] == 'country') {
                Ctry = x;
                break;
              }
            }
            if (Ctry == -1) { PopErr(); return false; }
          
            var PData = results[0].geometry.location+', '+results[0].address_components[Ctry].short_name;
            getI('Coords').value = PData;
            FndLoc = 1;
            SubAdr(PData);
            
          } else {
            var ResultsString = '';
            for (x=0;x<results.length;x++) {

              var Ctry = -1;
              for (y=0; y<results[x].address_components.length; y++) {
                if (results[x].address_components[y].types[0] == 'country') {
                  Ctry = y;
                  break;
                }
              }
              if (Ctry == -1) { PopErr(); return false; }
            
              ResultsString += '<SPAN CLASS="sz14 fklnk" onClick="SubAdr(this.id);" ID="'+results[x].geometry.location+', '+results[x].address_components[Ctry].short_name+'">'+results[x].formatted_address+'</SPAN><BR />';
              
            }
            UpDIV('LocMsg', GS(1206)+'<DIV CLASS="rslts flwa">'+ResultsString+'</DIV>', 'wrnmsg');
          }
        } else if (status == google.maps.GeocoderStatus.ZERO_RESULTS) {
          UpDIV('LocMsg', GS(1205), 'wrnmsg');
        } else {
          UpDIV('LocMsg', errormsg, 'errmsg');
        }
      });
      
    }
    
  }
  
}

//////////////////////////////////////////////////////////////////////////
// DATA FUNCTIONS
//////////////////////////////////////////////////////////////////////////

var Locs = new Array();
var Cats = new Array();
var Dets = new Array();

var DlCont, FvCont;

var CurSrtV = 0;
var CurSrtN = '';
var ShwWrn = 0;

var Opts = Array(0,10,25,50,100);

var Shw =
  new google.maps.MarkerImage('/IF/Marker-Back.png',
  new google.maps.Size(44,28),
  new google.maps.Point(0,0),
  new google.maps.Point(2,26));
  
var ShwH =
  new google.maps.MarkerImage('/IF/Marker-BackHome.png',
  new google.maps.Size(41,28),
  new google.maps.Point(0,0),
  new google.maps.Point(2,26));
  
function Run(R) { if (R != '') (window.execScript)? window.execScript(R):eval(R); }
  
function FndIdx(A,E) { x=0; for (y in A) { if (y == E) return x; x++; } return false; }
function FndIdxID(A,E) { for (x=0;x<A.length;x++) { if (A[x].ID == E) return x; } return false; }
function FndIdxDID(A,D) { for (x=0;x<A.length;x++) { if (A[x].DID == D) return x; } return false; }

function Deal(ID,E,T,C,D,RP,SP) { this.ID = ID; this.E = Fix(E); this.T = T; this.C = C; this.D = D; this.RP = RP; this.SP = SP; this.F = 0; this.L = new Array(); }
function FSav(ID,E,D,V,DID,W) { this.ID = ID; this.E = Fix(E); this.D = D; this.V = V; this.DID = DID; this.W = W; this.L = new Array(); }
function ULoc(ID,E,A,O,S,I) { this.ID = ID; this.E = Fix(E); this.A = A; this.O = O; this.S = S; M = GetMk(A,O,I,E); this.L = M; this.D = new Array(); this.F = new Array(); this.W = new Array(); this.CF = new Array(); this.TF = new Array(); this.DF = new Array(); this.DTC = 0; this.DNC = 0; this.FTC = 0; this.WTC = 0; this.WNC = 0; }
function DLoc(X,ID,E,A,O,I,D) { this.ID = ID; this.D = D; this.A = A; this.O = O; M = GetMk(A,O,I,E); this.L = M; SetEvt(ID,M,X); }
function ACat(ID,N,O) { this.ID = ID, this.N = N; this.O = O; this.T = new Array(); }
function ATyp(ID,N,O) { this.ID = ID, this.N = N; this.O = O; }

function GetMk(A,O,I,E) {
  
  var LL = new google.maps.LatLng(A,O);
  var IC =
    new google.maps.MarkerImage('/IF/Marker-'+I+'.png',
    new google.maps.Size(41,61),
    new google.maps.Point(0,0),
    new google.maps.Point(20,59));
    
  var MK = new google.maps.Marker({
    position: LL, 
    title: E,
    map: null,
    shadow: ((I=='Home')?ShwH:Shw),
    icon: IC
  });
  
  return MK;
}

function SetPos(DL) { var LL = new google.maps.LatLng(DL.A,DL.O); DL.L.setPosition(LL); }
function SetLoc(A,P) { for (x in A) { SetM(A[x].L, P); SBM(A[x].L); } }
function SetM(M,P) { M.setMap(P); }

function SetSrt(V, N) { CurSrtV = V; CurSrtN = N; }
function Sort(DoTab,DoLst) {
  
  SetCur('cwt');
  
  if (DoTab == 1 || DoTab == 3 || DoTab === undefined) {
  
    D = ((DoTab==3)?Locs[CurLoc].W:Locs[CurLoc].D);
    
    switch (parseInt(CurSrtV)) {
      case 1: D.sort( function(a, b) { return (1-(a.RP/a.SP)) - (1-(b.RP/b.SP)) } ); break;
      case 2: D.sort( function(a, b) { return a.D - b.D } ); break;
      case 3: D.sort( function(a, b) {
      
        if ((a.C==0) && (b.C==0)) {
          return 0;
        } else if (a.C==0) {
          return 1;
        } else if (b.C==0) {
          return -1;
        }      
      
        aCIdx = FndIdxID(Cats, a.C);
        if (aCIdx !== false) {
          aTIdx = FndIdxID(Cats[aCIdx].T, a.T);
          if (aTIdx !== false) {
            bCIdx = FndIdxID(Cats, b.C);
            if (bCIdx !== false) {
              bTIdx = FndIdxID(Cats[bCIdx].T, b.T);
              if (bTIdx !== false) {
                return Cats[aCIdx].T[aTIdx].O - Cats[bCIdx].T[bTIdx].O;
              }
            }
          }
        }
        return 0;
      } ); break;
      
     case 4: D.sort( function(a, b) { return b.ID - a.ID } ); break;
     default: D.sort( function(a, b) { aD = 0; bD = 0; for (x in a.L) { aD = a.L[x].D; break; } for (x in b.L) { bD = b.L[x].D; break; } return aD - bD } );
    }
    
  }
  
  if (DoTab == 2 || DoTab === undefined) {
      
    F = Locs[CurLoc].F;
    F.sort( function(a, b) {
    
      if ((a.D==0) && (b.D==0)) {
        return (a.E - b.E);
      } else if (a.D==0) {
        return 1;
      } else if (b.D==0) {
        return -1;
      } else {
        return (a.D - b.D);
      }
      
    } );
      
  }
  
  if (DoLst === undefined || DoLst === true) UpLst();
  SetCur('cn');

}

function UpLst(Ig) {

  clearTimeout(UpTmr);
  SB = getI('Search');
  
  for (Lx in Locs) {
  
    L = Locs[Lx];
      
    //Reset counters
    L.DTC = 0;
    L.DNC = 0;
    L.FTC = 0;
    L.WTC = 0;
    L.WNC = 0;
    
    UseMap = ((Lx == CurLoc)?MMap:null);
    SetM(L.L, UseMap);

    
    if (Lx == CurLoc) {
    
      switch (CurTab) {

        case 1:
        case 3:
          //Do deal lists
          HTML = '<DIV ID="PanLT"><DIV CLASS="w100p nowr flwh">'+GS(1302)+' ';
          HTML += (IsR())? '<DIV CLASS="nbutt drop" onMouseOver="PanH(); BM(Locs[CurLoc].L); DoHlp(this,1348);" onMouseOut="SBM(Locs[CurLoc].L); KlHlp();" onClick="ShoLoc();">'+L.E+'</DIV>':L.E;
          HTML += ', '+GS(1303)+' <DIV CLASS="nbutt drop" onClick="ShoSrt();" onMouseOver="DoHlp(this,1349);" onMouseOut="KlHlp();">'+CurSrtN+'</DIV>.</DIV><DIV CLASS="padtxs sz12">'+GS(1304)+'</DIV></DIV><DIV ID="PanLC" CLASS="abs fullss flwa padrs">';
          if (Ig == true) HTML += '<DIV CLASS="nfomsg nomrgb sz13 mrgtxs">'+GS(1319)+' <SPAN CLASS="fklnk" onClick="UpLst();">'+GS(1320)+'</SPAN></DIV>';
          if ((SB.value != '') && (SB.value != GS(1358))) HTML += '<DIV CLASS="nfomsg nomrgb sz13 mrgtxs">'+GS(1361)+' "'+SB.value+'". <SPAN CLASS="fklnk" onClick="ClrSch();">'+GS(1362)+'</SPAN></DIV>';
          break;
          
        case 2:
          //Do favorites list
          HTML = '<DIV ID="PanLT"><DIV CLASS="w100p nowr flwh">'+GS(1302)+' ';
          HTML += (IsR())? '<SPAN CLASS="fklnk" onMouseOver="PanH(); BM(Locs[CurLoc].L); DoHlp(this,1348);" onMouseOut="SBM(Locs[CurLoc].L); KlHlp();" onClick="ShoLoc();">'+L.E+'</SPAN>':L.E;
          HTML += ', '+GS(1321)+'</DIV><DIV CLASS="padtxs sz12">'+GS(1322)+'</DIV></DIV>';
          if (IsR()) HTML += '<DIV CLASS="mvb abs algc"><HR><INPUT TYPE="button" CLASS="butt mrgbxs pnt" VALUE="'+GS(1323)+'" onClick="NewFav();" onMouseOver="DoHlp(this,1354);" onMouseOut="KlHlp();"></DIV>';
          HTML += '<DIV ID="PanLC" CLASS="abs fullsx flwa padrs">';
          if ((SB.value != '') && (SB.value != GS(1358))) HTML += '<DIV CLASS="nfomsg nomrgb sz13 mrgtxs">'+GS(1361)+' "'+SB.value+'". <SPAN CLASS="fklnk" onClick="ClrSch();">'+GS(1362)+'</SPAN></DIV>';
          break;
          
      }
      
    }
    
    //Deals
    
    for (Dx in L.D) {
    
      D = L.D[Dx];
      Dst = 100;
      
      for (Ly in D.L) {
        if (D.L[Ly].D < Dst) Dst = D.L[Ly].D;
      }
      
      SchMch = ((SB.value == '') || (SB.value == GS(1358)) || (D.E.toLowerCase().indexOf(SB.value.toLowerCase()) > -1));
      Match = ( (((!(D.ID in L.DF)) || L.DF[D.ID] != 1) && ((!(D.T in L.TF))) && ((!(D.C in L.CF)) || (L.CF[D.C] >= Dst))) || (Ig == true) ) && SchMch;
      if (Match) L.DTC++;
      if (!(D.ID in L.DF) && Match) L.DNC++;
      
      if ((Lx == CurLoc) && (CurTab == 1) && Match) {
      
        FIdx = FndIdxDID(L.F, D.ID);
        CIdx = FndIdxID(Cats, D.C);
        if (CIdx !== false) {
          TIdx = FndIdxID(Cats[CIdx].T, D.T);
          if (TIdx !== false) {
            HTML += '<DIV CLASS="deal" ID="D'+D.ID+'" onMouseOver="HighL(this,'+D.ID+')" onMouseOut="UHighL(this,'+D.ID+');">';
            HTML += '<DIV ID="SN'+D.ID+'" CLASS="fll '+((FIdx===false)?'dno':'din')+'"><SPAN CLASS="mgrrxs fra ltrd algc b sz14">Saved</SPAN></DIV>';
            if (!(D.ID in L.DF) && (FIdx===false)) HTML += '<SPAN ID="N'+D.ID+'" CLASS="fll mgrrxs fra ltbl algc b sz14">New</SPAN>';
            HTML += '<DIV CLASS="ctrls rbrds"><DIV CLASS="sz12 gray"><SPAN CLASS="dkgray s"><SPAN CLASS="gray">'+addC(D.RP)+'</SPAN></SPAN> <SPAN CLASS="dkgr sz15">'+L.S+''+addC(D.SP)+'</SPAN> (<SPAN NAME="svngs" CLASS="sav">-'+Math.round((1-(D.SP/D.RP))*100)+'%</SPAN>)</DIV><DIV CLASS="sav sz15">'+Dst+' km'+((D.L.length > 1)?'+':'')+'</DIV><DIV ID="T'+D.ID+'"></DIV><DIV><INPUT CLASS="sbutt tibu" TYPE="button" onClick="Buy('+D.ID+')" onMouseOver="DoHlp(this,1305);" onMouseOut="KlHlp();">';
            if (IsR()) {
              HTML += '<INPUT CLASS="sbutt tisv" TYPE="button" VALUE="" onClick="TogSav(1,'+D.ID+',0);" onMouseOver="DoHlp(this,1306);" onMouseOut="KlHlp();">';
            } else {
              HTML += '<INPUT CLASS="dsbutt tidsv" TYPE="button" VALUE="" onMouseOver="DoHlp(this,1334,1);" onMouseOut="KlHlp();">';
            }
            HTML += '<INPUT CLASS="sbutt tihi" TYPE="button" onClick="KlD('+D.ID+')" onMouseOver="DoHlp(this,1307);" onMouseOut="KlHlp();"><INPUT CLASS="sbutt tish" TYPE="button" onClick="Share('+D.ID+')" onMouseOver="DoHlp(this,1351);" onMouseOut="KlHlp();"></DIV></DIV><SPAN ID="EX'+D.ID+'" CLASS=""><SPAN CLASS="pnt dkbl" onClick="GetDet('+D.ID+')">'+D.E+'</SPAN></SPAN><BR /><SPAN CLASS="pnt print dkgray" onClick="EdtFlt('+Cats[CIdx].T[TIdx].ID+')"><BR />'+GS(1308)+' '+Cats[CIdx].N+': '+Cats[CIdx].T[TIdx].N+'</SPAN><DIV CLASS="clr"></DIV></DIV>';
          }
        }
        
      }
      
      for (Ly in D.L) {
        LL = D.L[Ly];
        
        UseMap = ( Match && (Lx == CurLoc) && (CurTab == 1) && ( (!(D.C in L.CF)) || (L.CF[D.C] >= LL.D) ) )? MMap:null; //SetM(LL.L, UseMap);
        SetM(LL.L, UseMap);
        if (UseMap != null) LL.L.setAnimation(google.maps.Animation.DROP);
        
      }
    }

    //Web deals
    
    for (Wx in L.W) {
    
      W = L.W[Wx];
            
      SchMch = ((SB.value == '') || (SB.value == GS(1358)) || (W.E.toLowerCase().indexOf(SB.value.toLowerCase()) > -1));
      Match = ( (((!(W.ID in L.DF)) || L.DF[W.ID] != 1) && ((!(W.T in L.TF))) && (!(W.C in L.CF))) || (Ig == true) ) && SchMch;
      if (Match) L.WTC++;
      if (!(W.ID in L.DF) && Match) L.WNC++;
      
      if ((Lx == CurLoc) && (CurTab == 3) && Match) {
      
        FIdx = FndIdxDID(L.F, W.ID);
        CIdx = FndIdxID(Cats, W.C);
        if (CIdx !== false) {
          TIdx = FndIdxID(Cats[CIdx].T, W.T);
          if (TIdx !== false) {
            HTML += '<DIV CLASS="deal" ID="D'+W.ID+'" onMouseOver="HighL(this,'+W.ID+')" onMouseOut="UHighL(this,'+W.ID+');">';
            HTML += '<DIV ID="SN'+W.ID+'" CLASS="fll '+((FIdx===false)?'dno':'din')+'"><SPAN CLASS="mgrrxs fra ltrd algc b sz14">Saved</SPAN></DIV>';
            if (!(W.ID in L.DF) && (FIdx===false)) HTML += '<SPAN ID="N'+W.ID+'" CLASS="fll mgrrxs fra ltbl algc b sz14">New</SPAN>';
            HTML += '<DIV CLASS="ctrls rbrds"><DIV CLASS="sz12 gray"><SPAN CLASS="dkgray s"><SPAN CLASS="gray">'+addC(W.RP)+'</SPAN></SPAN> <SPAN CLASS="dkgr sz15">'+L.S+''+addC(W.SP)+'</SPAN> (<SPAN NAME="svngs" CLASS="sav">-'+Math.round((1-(W.SP/W.RP))*100)+'%</SPAN>)</DIV><DIV CLASS="sav sz15">'+Dst+' km'+((W.L.length > 1)?'+':'')+'</DIV><DIV ID="T'+W.ID+'"></DIV><DIV><INPUT CLASS="sbutt tibu" TYPE="button" onClick="Buy('+W.ID+')" onMouseOver="DoHlp(this,1305);" onMouseOut="KlHlp();">';
            if (IsR()) HTML += '<INPUT CLASS="sbutt tisv" TYPE="button" VALUE="" onClick="TogSav(1,'+W.ID+',0);" onMouseOver="DoHlp(this,1306);" onMouseOut="KlHlp();">';
            HTML += '<INPUT CLASS="sbutt tihi" TYPE="button" onClick="KlD('+W.ID+')" onMouseOver="DoHlp(this,1307);" onMouseOut="KlHlp();"><INPUT CLASS="sbutt tish" TYPE="button" onClick="Share('+W.ID+')" onMouseOver="DoHlp(this,1351);" onMouseOut="KlHlp();"></DIV></DIV><SPAN ID="EX'+W.ID+'" CLASS=""><SPAN CLASS="pnt dkbl" onClick="GetDet('+W.ID+')">'+W.E+'</SPAN></SPAN><BR /><SPAN CLASS="pnt print dkgray" onClick="EdtFlt('+Cats[CIdx].T[TIdx].ID+')"><BR />'+GS(1308)+' '+Cats[CIdx].N+': '+Cats[CIdx].T[TIdx].N+'</SPAN><DIV CLASS="clr"></DIV></DIV>';
            //HTML += '<DIV CLASS="ctrls rbrds"><DIV NAME="svngs" CLASS="sav">'+Math.round((1-(W.SP/W.RP))*100)+'%</DIV><DIV ID="T'+W.ID+'"></DIV><DIV><IMG SRC="/IF/Button-Buy.png" CLASS="qb" ALT="'+GS(1305)+'" WIDTH="17" HEIGHT="18" onClick="Buy('+W.ID+')" onMouseOver="DoHlp(this,1305);" onMouseOut="KlHlp();">';
            //if (IsR()) HTML += '<IMG SRC="/IF/Button-Save.png" CLASS="qb" ALT="'+GS(1306)+'" WIDTH="17" HEIGHT="18" onClick="TogSav(1,'+W.ID+',0);" onMouseOver="DoHlp(this,1306);" onMouseOut="KlHlp();">';
            //HTML += '<IMG SRC="/IF/Button-Hide.png" CLASS="qb" ALT="'+GS(1307)+'" WIDTH="17" HEIGHT="18" onClick="KlD('+W.ID+')" onMouseOver="DoHlp(this,1307);" onMouseOut="KlHlp();"><IMG SRC="/IF/Button-Share.png" CLASS="qb" ALT="'+GS(1351)+'" WIDTH="17" HEIGHT="18" onClick="Share('+W.ID+')" onMouseOver="DoHlp(this,1351);" onMouseOut="KlHlp();"></DIV></DIV><SPAN ID="EX'+W.ID+'" CLASS=""><SPAN CLASS="pnt dkbl" onClick="GetDet('+W.ID+')">'+W.E+'</SPAN></SPAN><BR /><SPAN CLASS="pnt print dkgray" onClick="EdtFlt('+Cats[CIdx].T[TIdx].ID+')"><BR />'+GS(1308)+' '+Cats[CIdx].N+': '+Cats[CIdx].T[TIdx].N+'</SPAN><DIV CLASS="clr"></DIV></DIV>';
          }
        }
        
      }
      
      LL = W.L[0];
      
      UseMap = ( Match && (Lx == CurLoc) && (CurTab == 3) && (!(W.C in L.CF)) )? MMap:null; //SetM(LL.L, UseMap);
      SetM(LL.L, UseMap);
      if (UseMap != null) LL.L.setAnimation(google.maps.Animation.DROP);
      
    }
    
    if ((Lx == CurLoc) &&
       ((Locs[CurLoc].DTC == 0 && CurTab == 1) ||
        (Locs[CurLoc].WTC == 0 && CurTab == 3))) HTML += '<DIV CLASS="red mrgts">'+GS(1324)+'</DIV>';
        
    if (CurTab != 2) HTML += '<DIV CLASS="sz13 padt" ID="PanLCe"></DIV></DIV>';
    
    //Favorites
    
    for (Fx in L.F) {
      F = L.F[Fx];
      
      if (F.D == 0) {
        DLeft = GS(1340);
      } else if (F.D < new Date()) {
        DLeft = GS(1329);
      } else {
        DLeft = Math.round((F.D - (new Date())) / 1000 / 60 / 60 / 24);
        if (DLeft <= 14) L.FTC++;
        DLeft += ' '+GS(1341);
      }

      UseMap = (((Lx == CurLoc) && (CurTab == 2))?MMap:null);
      Expd = ((F.D < new Date()) && (F.D != 0));
      SchMch = ((SB.value == '') || (SB.value == GS(1358)) || (F.E.toLowerCase().indexOf(SB.value.toLowerCase()) > -1));        

      if ((Lx == CurLoc) && (CurTab == 2) && SchMch) {

        HTML += '<DIV CLASS="deal" ID="F'+F.ID+'" onMouseOver="HighL(this,'+F.ID+')" onMouseOut="UHighL(this,'+F.ID+');">'+
                '<DIV CLASS="wctrls rbrds"><INPUT CLASS="sbutt tihi flr padlxs" TYPE="button" onClick="TogSav(0,'+((F.ID<0)?F.ID:F.DID)+',0);" onMouseOver="DoHlp(this,1352);" onMouseOut="KlHlp();"><SPAN NAME="value" CLASS="sav">'+Locs[CurLoc].S+F.V+'</SPAN><BR /><SPAN CLASS="'+((Expd)?'red':'')+'">'+DLeft+'</SPAN></DIV><SPAN CLASS="'+((Expd)?'s red':'')+'"><SPAN '+((F.ID>0)?'CLASS="pnt dkbl" onClick="GetDet('+F.DID+')"':'CLASS="dkbl"')+'>'+F.E+'</SPAN></SPAN><DIV CLASS="clr"></DIV></DIV>';

      }
      
      for (Ly in F.L) {
      
        LL = F.L[Ly];
        
        SetM(LL.L, UseMap);
        if (UseMap != null) LL.L.setAnimation(google.maps.Animation.DROP);
        
      }
      
    }
    
    if (CurTab == 2) {
      if ((Locs[CurLoc].F.length == 0) && (Lx == CurLoc)) {
        HTML += ((IsR())?'<DIV CLASS="red mrgts">'+GS(1325)+'</DIV>':'<DIV CLASS="red mrgts">'+GS(1334)+'</DIV><DIV CLASS="mrgts">'+GS(1335)+' <SPAN CLASS="fklnk" onClick="NewAcct();">'+GS(1336)+'</SPAN>? '+GS(1337)+'</DIV>');
      }
      HTML += '</DIV>';
    }

  }
  
  UpTot();
  KlHlp();
  UpDIV('PanLM', HTML); 
  
  switch (CurTab) {
    case 1:
    case 3: if (Ig != true) ChkFe(); ChkPe(); UpTim(); break;
    case 2: break;
  }
   
}


function UpTot() {
  UpDIV('DTCount', Locs[CurLoc].D.length, ((Locs[CurLoc].D.length == 0)?'gray':''));
  UpDIV('DMCount', Locs[CurLoc].DTC, ((Locs[CurLoc].DTC == 0)?'gray':''));
  UpDIV('DNCount', Locs[CurLoc].DNC, ((Locs[CurLoc].DNC == 0)?'gray':'ltbl'));
  UpDIV('WTCount', Locs[CurLoc].W.length, ((Locs[CurLoc].W.length == 0)?'gray':''));
  UpDIV('WMCount', Locs[CurLoc].WTC, ((Locs[CurLoc].WTC == 0)?'gray':''));
  UpDIV('WNCount', Locs[CurLoc].WNC, ((Locs[CurLoc].WNC == 0)?'gray':'ltbl'));
  UpDIV('FTCount', Locs[CurLoc].F.length, ((Locs[CurLoc].F.length == 0)?'gray':''));
  UpDIV('FECount', Locs[CurLoc].FTC, ((Locs[CurLoc].FTC == 0)?'gray':'ltrd'));
}

function UpLTot() {

  for (Lx in Locs) {
    UpDIV('LDC'+Locs[Lx].ID, Locs[Lx].DNC+' <SPAN CLASS="sz11">of '+Locs[Lx].DTC+'</SPAN>');
  }

}

function ChkPe() {

  D = getN('svngs');
  for (x=0;x<D.length;x++) {
    P = parseInt(D[x].innerHTML.replace('-','').replace('%',''));
    if (P >= 50) {
      D[x].className += ' dkgr';
    } else if (P < 25) {
      D[x].className += ' red';
    } else {
      D[x].className += ' org';
    }
  }

}

function ChkFe() {

  DL = ((CurTab==1)?Locs[CurLoc].D.length:Locs[CurLoc].W.length);
  DC = ((CurTab==1)?Locs[CurLoc].DTC:Locs[CurLoc].WTC);
  SB = getI('Search');
  HTML = ( (DC == DL) || ((SB.value != '') && (SB.value != GS(1358))) )? '':'<HR><DIV>'+GS(1326).replace('%a', (DL - DC))+'<UL><LI><SPAN CLASS="fklnk" onClick="UpLst(true);">'+GS(1327)+'</SPAN></LI><LI><SPAN CLASS="fklnk" onClick="RstFlt();">'+GS(1328)+'</SPAN></LI></UL></DIV></DIV>';
  UpDIV('PanLCe', HTML);

}

function BM(M) { if (!AcWin) M.setAnimation(google.maps.Animation.BOUNCE); }
function SBM(M) { M.setAnimation(null); }

function HighL(E,DID) {

  if (AcWin) return;
  
  clearTimeout(PanT);
  E.className += " hlite rbrds";
  
  G = getI('PanLC');
  if (E.offsetTop < G.scrollTop) G.scrollTop = E.offsetTop;
  if ((E.offsetTop + E.offsetHeight) > (G.scrollTop + G.offsetHeight)) G.scrollTop = (E.offsetTop + E.offsetHeight) - G.offsetHeight;
  
  if (DID !== undefined) {
  
    Dst = 200;
    
    switch (CurTab) {
      case 1: DA = Locs[CurLoc].D; break;
      case 2: DA = Locs[CurLoc].F; break;
      case 3: DA = Locs[CurLoc].W; break;
    }
      
    Idx = FndIdxID(DA, DID);
    if (Idx !== false) {
      M = DA[Idx];
      if (M.W && (CurTab == 2)) return;
      for (Lx in M.L) {
        LL = M.L[Lx];
        if (LL.D < Dst) {
          LNear = LL.L;
          Dst = LL.D;
        }
        BM(LL.L);
      }
      P = LNear.getPosition();
      PanTo(P);
    }
          
  }

}

function HighLM(D) { HighL(getI(((CurTab==2)?'F':'D')+D)); }

function UHighL(E,DID) {

  E.className = "deal";
  
  if (DID !== undefined) {
  
    switch (CurTab) {
      case 1: DA = Locs[CurLoc].D; break;
      case 2: DA = Locs[CurLoc].F; break;
      case 3: DA = Locs[CurLoc].W; break;
    }  
  
    Idx = FndIdxID(DA, DID);
    if (Idx !== false) {
      M = DA[Idx];
      if (M.W && (CurTab == 2)) return;
      for (Lx in M.L) {
        LL = M.L[Lx].L;
        SBM(LL);
      }
      PanT = setTimeout('PanH()', 4000);
    }
  }
    
}

function UHighLM(D) { UHighL(getI(((CurTab==2)?'F':'D')+D)); }

function ScrTo() {

}

function TimeL(EDat, EID) {
  
  NDat = new Date();
  if (EDat > NDat) {
    dd = Math.floor((EDat-NDat)/86400000);
    hh = Math.floor((EDat-NDat-(dd*86400000))/3600000);
    mm = Math.floor((EDat-NDat-(dd*86400000)-(hh*3600000))/60000);
    ss = Math.floor((EDat-NDat-(dd*86400000)-(hh*3600000)-(mm*60000))/1000);
    return dd+':'+pad(hh)+':'+pad(mm)+':'+pad(ss);
  } else {
    ESPAN = getI('EX'+EID);
    if (ESPAN.className != 's red') ESPAN.className = 's red';
    return '<SPAN CLASS="red">'+GS(1329)+'</SPAN>';
  }

}

function UpTim() {

  if (PanOL) {
    if (CurLoc in Locs) {
      
      switch (CurTab) {
        case 1: DA = Locs[CurLoc].D; break;
        case 3: DA = Locs[CurLoc].W; break;
      }        
    
      for (Dx in DA) {
        TDiv = getI('T'+DA[Dx].ID);
        if (TDiv != null) TDiv.innerHTML = TimeL(DA[Dx].D, DA[Dx].ID);
        TDiv = getI('TX'+DA[Dx].ID);
        if (TDiv != null) TDiv.innerHTML = TimeL(DA[Dx].D, DA[Dx].ID);
      }
      
    }
  }
  
  UpTmr = setTimeout('UpTim()', 1000);

}

//-----------------

function CData() {

  for (Lx in Locs) {
    for (Dx in Locs[Lx].D) {
      SetLoc(Locs[Lx].D[Dx].L, null);
    }
    for (Fx in Locs[Lx].F) {
      SetLoc(Locs[Lx].F[Fx].L, null);
    }
    for (Wx in Locs[Lx].W) {
      SetLoc(Locs[Lx].W[Wx].L, null);
    }    
    SetM(Locs[Lx].L, null);
  }
  
  Locs.length = 0;
  Cats.length = 0;
  
  getI('CpR').className = 'wht';
  clearTimeout(DataTmr);
  KlHlp();

}

function VldObj(O) {
  if (null == O) return false;
  if ("undefined" == typeof(O)) return false;
  return true;
}

function RstVar() { CurLoc = -1; CurTab = 1; PanOL = false; MMap.setOptions(OptLck); }

//////////////////////////////////////////////////////////////////////////
// AJAX FUNCTIONS
//////////////////////////////////////////////////////////////////////////

function Req(T, R, GF, BF, D, S) {

  SetCur('cwt');

  if (window.XMLHttpRequest) {
    RObj = new XMLHttpRequest();
  } else {
    RObj = new ActiveXObject("Microsoft.XMLHTTP");
  };
  
  Time = (S === undefined)? 60:S;
  
  var Y = setTimeout(function() {
    RObj.abort();
    SetCur('cn');
  }, 1000 * Time);
  
  RObj.onreadystatechange = function() {
    if (RObj.readyState == 4) {
      
      SetCur('cn');
      clearTimeout(Y);
      if (window.opera) opera.postError('Response was: '+RObj.responseText);
      
      if (GF == null) return;
      
      if (RObj.status == 200 || RObj.status == 304) {
        if (RObj.responseText != '' && RObj.responseText.indexOf('<') !== 0) {
          Z = eval(unescape(RObj.responseText.replace("'", "\'")));
          if ('S' in Z && 'R' in Z && 'C' in Z && 'D' in Z && 'J' in Z) {
            if (GF !== undefined) {
              if (GF instanceof Array) {
                Rep(Z, GF[0], GF[1]);
                if (GF.length == 3 && getI(GF[2])) getI(GF[2]).disabled = false;
              } else {  
                var Func = window[GF];
                if (typeof Func === 'function') Func(Z);
              }
              
              return;
            }
          }
        }
      }
      
      if (BF !== undefined) { var Func = window[BF]; if (typeof Func === 'function') { Func(); } else { DIVErr(BF); } }
      
    }
  }
  
  RObj.open(T, R, true);
  if (T == 'POST') {
    RObj.setRequestHeader("Content-type","application/x-www-form-urlencoded");
    RObj.setRequestHeader("Content-length", D.length);
    RObj.setRequestHeader("Connection", "close");
    RObj.send(D);
  } else {
    RObj.send();
  }

}

function RepHdl(R, DIV, UT, UF, UC, JT, JF) {

  D = (DIV=='')? R['D']:DIV;

  if ((UT && R['S']) || (UF && !R['S'])) { if (UC) { UpDIV(D, R['R'], R['C']); } else { UpDIV(D, R['R']); } }
  if ((JT && R['S']) || (JF && !R['S'])) { Run(R['J']); }

}

function Rep(R, DIV, K) { RepHdl(R, DIV, K & 16, K & 8, K & 4, K & 2, K & 1); }


//////////////////////////////////////////////////////////////////////////
// UI FUNCTIONS
//////////////////////////////////////////////////////////////////////////

var PanOL = false;
var AcWin = false;
var FBLoad = false;

var CurTab = 1;

var UpTmr, PanTmr, HiTmr, SchTmr, DataTmr;
var Strgs = new Array();

function SetCur(C) { document.body.className = 'bkg '+C; }

function SetStr(Strs) {

  for (Sx in Strs) {
    if (!(Sx in Strgs)) Strgs[Sx] = Fix(Strs[Sx]);
  }

}

function GS(N) {
  return ((Strgs[N])?Strgs[N]:'?');
}

function UpDIV(ID, HTML, C) {

  getI(ID).innerHTML = HTML;
  if (C !== undefined) getI(ID).className = C;

}

function ClDIV(ID) { UpDIV(ID, '', 'hid'); }
function DIVErr(ID) { UpDIV(ID, errormsg, 'errmsg'); }

//-----------------

function ClsRep(Name, B, A) {

  I = getN(Name);
  for (x=0;x<I.length;x++) {
    ClRep(I[x],B,A);
  } 

}

function ClRep(Obj,B,A) {
  Obj.className = Obj.className.replace(B, A);
}

function ClRepI(ObjI,B,A) {
  I = getI(ObjI);
  ClRep(I,B,A);
}

//-----------------

function PopUp(Clss, SMsg) {

  AcWin = true;
  msgstr = (SMsg === undefined)? loadstr:'<DIV CLASS="fullx" ID="PUC"><DIV CLASS="load ctr dkgr b">'+GS(1003)+'...</DIV></DIV>';

  FaOut();
  UpDIV('PopUp', closestr+msgstr, 'visible box z2 fdl '+Clss);
  
}

//-----------------

function FaOut() {

  ClDIV('PopUp');
  ClsRep('Box', 'fdl', 'fdh');

}

//-----------------



function ClWin() {

  AcWin = false;
  ClDIV('PopUp');
  ClsRep('Box', 'fdh', 'fdl');

}

//-----------------

function Foc(Control) {

  var Ctrl = getI(Control);
  if (Ctrl !== undefined) {
    Ctrl.focus();
    if (Ctrl.value != '') Ctrl.select();
  }

}

//-----------------

function SetTab(T) {

  if (T==CurTab) {
    TogPa();
  } else {
  
    for (x=1;x<=3;x++) {
      TB = getI('PanL'+x);
      TB.className = TB.className.replace(((T==x)?'fdm':'fdz'), ((T==x)?'fdz':'fdm'));
      TB = getI('PanLT'+x);
      TB.className = TB.className.replace(((T==x)?'ltu':'lts'), ((T==x)?'lts':'ltu'));
    }
    
    switch (T) {
      case 1: UpDIV('PanLTi', GS(1359)); break;
      case 2: UpDIV('PanLTi', GS(1360)); break;
      case 3: UpDIV('PanLTi', GS(1363)); break;
    }
    
    if ((T != 3) && (CurTab == 3)) MMap.setOptions(OptStd);
    if ((T == 3) && (CurTab != 3)) MMap.setOptions(OptWeb);
    
    if ((T == 3) && (CurLoc != FstLoc)) {
      CurLoc = FstLoc;
      GotoM();
    }
    
    CurTab=T;
    UpLst();
    PanH();
    
  }

}

function TogPa() {

  PanOL = !PanOL;
  if (PanOL) CloIW();
  setTimeout('MovPaL()', 25);
  
}

//-----------------

function MovPaL() {

  PL = getI('PanelLeft');
  
  PLoL = parseInt(PL.offsetLeft);
  PLoW = parseInt(PL.offsetWidth);
  
  if (!PanOL && PL.offsetLeft > -PLoW+20) {
    PL.style.left =  ((PLoL - 40) <= (-PLoW))? (-PLoW)+'px':(PLoL - 40)+'px';
    setTimeout('MovPaL()', 25);
  } else if (PanOL && PL.offsetLeft < 20) {
    PL.style.left = ((PLoL + 40) >= 20)? '20px':(PLoL + 40)+'px';
    setTimeout('MovPaL()', 25);
  }
  
}

function DoPanR(D) {

  UpDIV('PanRT', D[0]);
  UpDIV('PanRC', D[1]);
  //UpDIV('PanRB', D[2]);
  if ((D[3] != 0) && (D[4] != 0)) ChkGSV(D[3], D[4]);
  
  ClRep(getI('PanelRight'), 'hid', 'vis');
  
}

function ClrPanR() {

  UpDIV('PanRT', '');
  UpDIV('PanRC', loadstr);
  //UpDIV('PanRB', '');
  
  ClRep(getI('PanelRight'), 'hid', 'vis');
  
}

function HPanR() {
  ClRep(getI('PanelRight'), 'vis', 'hid');
}

function PopErr(S,J) { AcWin = true; FaOut(); UpDIV('PopUp', '<DIV CLASS="errbox">'+unescape((S===undefined)?errormsg:S)+'</DIV><INPUT TYPE="button" CLASS="butt mrgts" VALUE="OK" onClick="'+((J===undefined)?'ClWin();':J)+'">', 'box small visible z3 ctr algc'); }
function PopQ(S,J) { AcWin = true; FaOut(); UpDIV('PopUp', '<DIV CLASS="qbox">'+unescape(S)+'</DIV><INPUT TYPE="button" CLASS="butt mrgts" VALUE="Yes" onClick="'+J+'">&nbsp;<INPUT TYPE="button" CLASS="butt mrgts" VALUE="No" onClick="ClWin();">', 'box small visible z3 ctr algc'); }
function PopC(S,J) { AcWin = true; FaOut(); UpDIV('PopUp', '<DIV CLASS="cbox">'+unescape(S)+'</DIV><INPUT TYPE="button" CLASS="butt mrgts" VALUE="OK" onClick="'+((J===undefined)?'ClWin();':J)+'">', 'box small visible z3 ctr algc'); }

function GH() { top.location = '/'; }
function Buy(DID) { if (ShwWrn || IsR()) { RmNew(DID); PWin('./out.php?'+DID); if (IsR()) setTimeout('ChkSav('+DID+');',2000); } else { ShoWrn(DID); } }
function PWin(U) {

  NewWin = window.open(U);
  if (!(NewWin && NewWin.top)) self.location = U;

}

function GetPos(Obj) {

  for (var PosX=0, PosY=0; Obj != null; PosX += Obj.offsetLeft, PosY += (Obj.offsetTop - Obj.scrollTop), Obj = Obj.offsetParent);
  return {x: PosX, y: PosY};
  
}

function DoHlp(Ctrl, Str, Under, Tab, TStr) {

  StrN = Str;
  if (Tab !== undefined) {
    if (CurTab == Tab) StrN = TStr;
  }
  
  UpDIV('Hlp', GS(StrN));
  
  HDIV = getI('Hlp');
  ClRep(HDIV, 'hid', 'show');
  
  CtrlPos = GetPos(Ctrl);
  
  HDIV.style.left = (CtrlPos.x - (HDIV.offsetWidth / 2) + (Ctrl.offsetWidth / 2))+'px';
  if (HDIV.offsetLeft < 0) HDIV.style.left = 0+'px';
  
  HDIV.style.top = ((Under)?(CtrlPos.y + Ctrl.offsetHeight + 5):(CtrlPos.y - HDIV.offsetHeight - 5))+'px';
    
}

function KlHlp() { ClRep(getI('Hlp'), 'show', 'hid'); }

function ChkSch() {

  SB = getI('Search');
  if (SB.value == GS(1358)) {
    SB.value = '';
    ClRep(SB, 'ltgr', 'ltbl');
  }

}

function ClrSch() {

  SB = getI('Search');
  SB.value = GS(1358);
  SB.className = 'schbox ltgr';
  UpLst();

}

function STmr() {

  clearTimeout(SchTmr);
  SchTmr = setTimeout('UpLst()', 400);

}

function DoGP() { gapi.plusone.go("GPB"); if (getI('GPBS')) gapi.plusone.go("GPBS"); }

//////////////////////////////////////////////////////////////////////////
// CONTENT FUNCTIONS
//////////////////////////////////////////////////////////////////////////

function AddScr(P) {

  
  T = getT('script');
  for (x=0;x<T.length;x++) {
    if (T[x].src == P) return;
  }

  S = document.createElement('script');
  S.type = 'text/javascript';
  S.src = P;
  
  H = getT('head')[0];
  H.appendChild(S);

}

//-----------------

function Init() { SetBackMap(); F5(); }
function InitD(DID) { SetBackMap(); Req('POST', '/index.php?Content', 'SetScr', 'PopErr', 'DID='+Enc(DID)); }

function F5() { ClWin(); CData(); Req('GET', '/index.php?Content', 'SetScr', 'PopErr'); }
function SF5() { window.location.reload(true); }

function SetScr(R) {

  if (R['S']) {
    UpDIV('MainContent', R['R']);
    SetStr(R['C']);
    Run(R['J']);
  }

}

//-----------------

function CkLog(B) {

  getI(B).disabled = true;

  if (IsE('DPUsername') || IsE('DPPassword')) {
    UpDIV('LoginMsg', GS(1215), 'wrnmsg');
    getI(B).disabled = false;
  } else {
    Req('POST', '/index.php?Login', Array('LoginMsg', 15, 'SubLog'), 'LoginMsg', 'DPUsername='+Enc(getI('DPUsername').value)+'&DPPassword='+Enc(getI('DPPassword').value));
  }

}

function ChkNot(B) {

  B.disabled = true;
  P = 'R=' + +(getI('RDY').checked) + '&V=' + +(getI('NTY').checked);
  
  for (x=1; x<=5; x++) {
    P = P + '&' + x + '=' + +(getI('NT'+x).checked);
  }
  
  Req('POST', '/index.php?Notifications', Array('NotMsg', 15, 'NotBut'), 'NotMsg', P);

}


//-----------------

function GetData() { DataTmr = setTimeout('GetData();', 1000 * 60 * 60); Req('GET', '/index.php?Data', 'ParData'); }

//-----------------

function ParData(R) {

  AllTabs = (Cats.length == 0);

  if (R['S']) {
    
    var Angle = 3;

    //Reset and repopulate categories and types
    if (R['D'] != null) {
    
      Cats.length = 0;
      aC = new ACat(0, '--', '-1');
      aT = new ATyp(0, '--', '-1');
      aC.T.push(aT);
      Cats.push(aC);
      
      for (Cx in R['D']) {
        aC = new ACat(R['D'][Cx]['ID'], R['D'][Cx]['Name'], R['D'][Cx]['Order']);
        for (Tx in R['D'][Cx]['Types']) {
          aT = new ATyp(R['D'][Cx]['Types'][Tx]['ID'], R['D'][Cx]['Types'][Tx]['Name'], R['D'][Cx]['Types'][Tx]['Order']);
          aC.T.push(aT);
        }
        Cats.push(aC);
      }
    
    }
    
    //Read sort value
    if (R['C'] != null) {
      for (Cx in R['C']) {
        SetSrt(Cx, R['C'][Cx]['D']);
        break;
      }
    }
    
    for (Lx in Locs) {
    
      //Check if location is still valid
      if (Locs[Lx].ID in R['R']) {
      
        ID = Locs[Lx].ID;
      
        //Clear obsolete filters
        for (Fx in Locs[Lx].CF) {
          if (!(Fx in R['R'][ID]['CFilt'])) delete Locs[Lx].CF[Fx];
        }
        
        for (Fx in Locs[Lx].TF) {
          if (!(Fx in R['R'][ID]['TFilt'])) delete Locs[Lx].TF[Fx];
        }

        for (Fx in Locs[Lx].DF) {
          if (!(Fx in R['R'][ID]['DFilt'])) delete Locs[Lx].DF[Fx];
        }

        //Clear obsolete local deals
        for (Dx in Locs[Lx].D) {
          if (!(Locs[Lx].D[Dx].ID in R['R'][ID]['Deals'])) {
            SetLoc(Locs[Lx].D[Dx].L, null);
            Locs[Lx].D.splice(Dx, 1);
          }
        }
        
        //Clear obsolete web deals
        for (Wx in Locs[Lx].W) {
          if (!(Locs[Lx].W[Wx].ID in R['R'][ID]['Deals'])) {
            SetLoc(Locs[Lx].W[Wx].L, null);
            Locs[Lx].W.splice(Wx, 1);
          }
        }        
        
        //Clear obsolete saves
        for (Fx in Locs[Lx].F) {
          if (!(Locs[Lx].F[Fx].ID in R['R'][ID]['Saves'])) {
            SetLoc(Locs[Lx].F[Fx].L, null);
            Locs[Lx].F.splice(Fx, 1);
          }
        }        
        
      //Otherwise, remove it
      } else {
        SetM(Locs[Lx].L, null);
        for (Fx in Locs[Lx].F) { SetLoc(Locs[Lx].F[Fx].L, null); }
        for (Wx in Locs[Lx].W) { SetLoc(Locs[Lx].W[Wx].L, null); }
        for (Dx in Locs[Lx].D) { SetLoc(Locs[Lx].D[Dx].L, null); }
        Locs.splice(Lx, 1); 
      }
    }
    
    for (Lx in R['R']) {
    
      L = R['R'][Lx];
      
      //Check user location
      LIdx = FndIdxID(Locs, Lx);
      if (LIdx === false) {
        aL = new ULoc(Lx, L['Desc'], L['Lat'], L['Lng'], L['CurS'], 'Home');
      } else {
        aL = Locs[LIdx];
      }
      
      for (Dx in L['Deals']) {
      
        D = L['Deals'][Dx];
        DW = ((D['Web'])?aL.W:aL.D);
        
        //Check deal
        DIdx = FndIdxID(DW, Dx);
        if (DIdx === false) {
          aD = new Deal(Dx, D['Desc'], D['TID'], D['CID'], new Date(D['EDate'] * 1000), D['RPrice'], D['SPrice']);
        } else {
          aD = DW[DIdx];
        }
          
        if (D['Web']) {
        
          if (aD.L.length == 0) {
            aDL = new DLoc(Dx, 0, D['Desc'], 0, 0, D['Icon'], -1);
            aD.L.push(aDL);
          }
          
          aDL = aD.L[0];
                    
          Angle += ((Angle < 6)?0.4:0.2);

          aDL.A = parseFloat(aL.A) - (0.01 * Angle * Math.cos(Angle)) - 0.035;
          aDL.O = parseFloat(aL.O) - (0.02 * Angle * Math.sin(Angle)) + 0.01;
          
          SetPos(aDL);
          
        } else {
        
          for (Ly in D['Locs']) {
          
            DL = D['Locs'][Ly];
            
            //Check deal location
            DLIdx = FndIdxID(aD.L, Ly);
            if (DLIdx === false) {
              aDL = new DLoc(Dx, Ly, D['Desc'], DL['Lat'], DL['Lng'], D['Icon'], DL['Dist']);
              aD.L.push(aDL);
            } else {
              aDL = aD.L[DLIdx];
            }
            
          }
          
        }
        
        if (DIdx === false) DW.push(aD);
        
      }
      
      for (Fx in L['Saves']) {
      
        F = L['Saves'][Fx];
        
        //Check save
        FIdx = FndIdxID(aL.F, Fx);
        if (FIdx === false) {
          aF = new FSav(Fx, F['Desc'], ((F['EDate']==0)?0:(new Date(F['EDate'] * 1000))), F['Value'], F['DID'], F['Web']);
          
          if (!F['Web']) {
            for (Ly in F['Locs']) {
              FL = F['Locs'][Ly];
              
              //Check saved deal location
              FLIdx = FndIdxID(aF.L, Ly);
              if (FLIdx === false) {
                aFL = new DLoc(Fx, Ly, F['Desc'], FL['Lat'], FL['Lng'], F['Icon'], FL['Dist']);
                aF.L.push(aFL);
              }
              
            }
          }
          
          aL.F.push(aF);

        }
      
      }
      
      if (LIdx === false) Locs.push(aL);
      
      Idx = Locs.length-1;
      
      //Update filters
      if (L['CFilt'] != null) {
        for (Fx in L['CFilt']) {
          F = L['CFilt'][Fx];
          if (!(Fx in Locs[Idx].CF)) Locs[Idx].CF[Fx] = F;
        }
      }
      
      if (L['TFilt'] != null) {
        for (Fx in L['TFilt']) {
          F = L['TFilt'][Fx];
          if (!(Fx in Locs[Idx].TF)) Locs[Idx].TF[F] = null;
        }
      }
      
      if (L['DFilt'] != null) {
        for (Fx in L['DFilt']) {
          F = L['DFilt'][Fx];
          if (!(Fx in Locs[Idx].DF)) Locs[Idx].DF[Fx] = F;
        }
      }

    }
        
    if (PenLoc > -1) { CurLoc = PenLoc; PenLoc = -1; }
    
    GotoM();
    if (AllTabs) { Sort(); } else { Sort(1); }
    if ((Locs[CurLoc].DTC == 0) && (Locs[CurLoc].WTC > 0) && (CurTab == 1)) SetTab(3);
    if ((Locs[CurLoc].DTC > 0) && (CurTab != 1)) SetTab(1);
    
  } else {
  
    Run(R['J']);
  
  }

}

function SngDl(DID,E,A,O,S,I) {

  aL = new ULoc(DID,E,A,O,S,I);
  Locs.push(aL);
  GotoM();
  SetM(aL.L, MMap);
  GetDet(DID,1);

}

//-----------------

function ChkLocNam(B) {

  B.disabled = true;

  if (IsE('LocNam')) {
    Foc('LocNam');
    B.disabled = false;
  } else {
    Req('POST', '/index.php?NameLocation', 'ProLocName', 'PopErr', 'LName='+Enc(getI('LocNam').value));
  }

}

function ProLocName(R) {

  getI('LocNamBut').disabled = false;
  if (!R['S']) Foc('LocNam');
  Run(R['J']);

}

//-----------------

function ChkRstPwd(B) {

  B.disabled = true;

  if (IsE('RPEMail')) {
    Foc('RPEMail');
    B.disabled = false;
  } else {
    Req('POST', '/index.php?ResetPass', Array('RstPwdMsg', 15, 'RstPwdBut'), 'PopErr', 'Name='+Enc(getI('RPEMail').value));
  }

}

//-----------------

function ProSrt(R) {

  if (R['S']) {
    SetSrt(parseInt(R['D']), R['R']);
    Sort(1);
  }
  
}

//-----------------

function NewAcct() { PopUp('big'); Req('GET', '/index.php?NewAccount', 'ShNewAcct', 'PopupContent'); }
function ShNewAcct(R) {

  if (R['S']) {
    UpDIV('PUC', R['R']);
    Run(R['J']);  
  } else {
    UpDIV('PUC', errormsg, 'errmsg');
  }

}

//-----------------

function ChkNewMsg(B) {

  var MsgF = getI('MsgF').value;
  var MsgS = getI('MsgS').value;
  var MsgE = TR('MsgE');
  var MsgM = TR('MsgM');
  
  if (MsgM == '') {
  
    ClRep(getI('CtcMsg'), 'dno', 'din');
    
  } else {
  
    B.disabled = true;
    Req('POST', '/index.php?Message', Array('', 15, 'NewMsgBut'), 'CtcMsg', 'MsgF='+Enc(MsgF)+'&MsgS='+Enc(MsgS)+'&MsgE='+Enc(MsgE)+'&MsgM='+Enc(MsgM));
  
  }
  
  return false;

}

function ChkNewRvw(B,DID) {

  var Scr = 0;
  var Rvw = TR('Rvw');
  
  for ($x=1;$x<=10;$x++) {
    if (getI('S'+$x).checked) {
      Scr = $x;
      break;
    }
  }
  
  if (Rvw == '' || Scr == 0) {
  
    ClRep(getI('RvwMsg'), 'dno', 'din');
    
  } else {
  
    B.disabled = true;
    Req('POST', '/index.php?SaveReview', Array('', 15, 'NewRvwBut'), 'RvwMsg', 'DID='+Enc(DID)+'&Scr='+Enc(Scr)+'&Rvw='+Enc(Rvw));
  
  }
  
  return false;

}

function ChkNewMail(B,DID) {

  var MName = TR('MailName');
  var MAdr = TR('MailAdr');
  var MNote = TR('MailNote');
  
  if (MName == '' || MAdr == '') {
  
    ClRep(getI('MailMsg'), 'dno', 'din');
  
  } else {
  
    B.disabled = true;
    Req('POST', '/index.php?Share', Array('', 15, 'NewMailBut'), 'MailMsg', 'DID='+Enc(DID)+'&Name='+Enc(MName)+'&Adr='+Enc(MAdr)+'&Note='+Enc(MNote));
  
  }

}

function ChkAcc(B) {

  ClDIV('AccMsg');
  ClDIV('AccEMlMsg');

  var PassO = TR('Pass0');
  var PassA = TR('Pass1');
  var PassB = TR('Pass2');

  if ( ((PassO.length > 0) || (PassA.length > 0) || (PassB.length > 0)) && ((PassO.length == 0) || (PassA.length == 0) || (PassB.length == 0)) ) return BadNewAcct(GS(1768), 'AccMsg', 'Pass0');
  if ((PassO.length > 0) || (PassA.length > 0) || (PassB.length > 0)) {
    if (PassA.length < 6) return BadNewAcct(GS(1421), 'AccMsg', 'Pass1');
    if (PassB != PassA) return BadNewAcct(GS(1422), 'AccMsg', 'Pass1');
  }
  
  var FName = TR('FName');
  var EMail = TR('EMail');
  
  if (EMail != '' && (EMail.indexOf('@') < 1 || EMail.lastIndexOf('.') < 3 || EMail.lastIndexOf('.') < EMail.indexOf('@')))
    return BadNewAcct(GS(1423), 'AccEMlMsg', 'EMail');
  
  B.disabled = true;
  Req('POST', '/index.php?Account', Array('', 15, 'AccBut'), 'AccMsg', 'Pass0='+Enc(PassO)+'&Pass1='+Enc(PassA)+'&Pass2='+Enc(PassB)+'&FName='+Enc(FName)+'&EMail='+Enc(EMail));
  
  return false;

}

function VerEml(B) {

  B.disabled = true;
  Req('GET', '/index.php?SendVerify', Array('', 15, 'VerBut'), 'AccMsg');

}

function ChkNewFav(B,DID) {

  B.disabled = true;
  
  if (IsR()) {
  
    var YY = 0;
    var MM = 0;
    var DD = 0;
  
    if (getI('Expiry1').checked) {
      YY = getI('ExpY').value;
      MM = getI('ExpM').value;
      DD = getI('ExpD').value;
    }

    var FValue = parseInt(getI('FValue').value);
    if (isNaN(FValue)) FValue = 0;
    
    var MName = TR('MName');
    if (MName == '') {
    
      ClRep(getI('FavMsg'), 'dno', 'din');
      
    } else {
    
      var InQ = TR('PCode');
      if (InQ == '') {
      
        ClRep(getI('LocMsg'), 'dno', 'din');
        
      } else {

        GeoC.geocode( {'address': InQ}, function(results, status) {
          if (status == google.maps.GeocoderStatus.OK) {

            var Ctry = -1;
            for (x=0; x<results[0].address_components.length; x++) {
              if (results[0].address_components[x].types[0] == 'country') {
                Ctry = x;
                break;
              }
            }
            if (Ctry == -1) { PopErr(); return false; }
          
            var CC = results[0].geometry.location+', '+results[0].address_components[Ctry].short_name;;
            Req('POST', '/index.php?NewFavorite', 'ProNewFav', 'FavMsg', 'ULID='+Enc(Locs[CurLoc].ID)+'&N='+Enc(MName)+'&V='+Enc(FValue)+'&C='+Enc(CC)+'&Y='+Enc(YY)+'&M='+Enc(MM)+'&D='+Enc(DD));
            
          } else {
            ClRep(getI('LocMsg'), 'dno', 'din');
          }
        });
        
      }
      
    }
    
  }
  
  B.disabled = false;
  return false;

}

function ProNewFav(R) {

  getI('NewFavBut').disabled = false;

  if (R['S']) {
    F = R['C'];
    aF = new FSav(F['FID'], F['Desc'], ((F['EDate']==0)?0:(new Date(F['EDate'] * 1000))), F['Value'], F['DID']);
    FL = F['Locs'][0];
    aFL = new DLoc(F['FID'], 0, F['Desc'], FL['Lat'], FL['Lng'], F['Icon'], FL['Dist']);
    aF.L.push(aFL);
    Locs[CurLoc].F.push(aF);
    Sort(2);
    ClWin();
  } else {
    Run(R['J']);
  }

}

function ChkNewAcct(B) {

  var UName = TR('UName');
  var PassA = TR('Pass1');
  var PassB = TR('Pass2');

  if (UName.length < 4) return BadNewAcct(GS(1420), 'NewLoginMsg', 'UName');
  if (PassA.length < 6) return BadNewAcct(GS(1421), 'NewLoginMsg', 'Pass1');
  if (PassB != PassA) return BadNewAcct(GS(1422), 'NewLoginMsg', 'Pass1');
  
  ClDIV('NewLoginMsg');
  
  //--
  
  var FName = TR('FName');
  var EMail = TR('EMail');
  
  if (EMail != '' && (EMail.indexOf('@') < 1 || EMail.lastIndexOf('.') < 3 || EMail.lastIndexOf('.') < EMail.indexOf('@')))
    return BadNewAcct(GS(1423), 'NewOptsMsg', 'EMail');
  
  ClDIV('NewOptsMsg');
  
  //--
  
  var Terms = getI('Terms');
    
  if (Terms.checked != true) return BadNewAcct(GS(1424), 'NewAgrMsg', 'Terms');
  
  ClDIV('NewAgrMsg');
  
  //--
  var CapKey = trim(getI('recaptcha_challenge_field').value);
  var Captcha = trim(getI('recaptcha_response_field').value);
  
  if (Captcha == '' || Captcha.indexOf(' ') < 1) return BadNewAcct(GS(1425), 'CaptchaMsg', 'recaptcha_response_field');
  
  ClDIV('CaptchaMsg');
  
  //--
  
  B.disabled = true;
  Req('POST', '/index.php?NewAccount', Array('', 15, 'NewAcctBut'), 'NewLoginMsg', 'UName='+Enc(UName)+'&Pass1='+Enc(PassA)+'&Pass2='+Enc(PassB)+'&FName='+Enc(FName)+'&EMail='+Enc(EMail)+'&Terms='+Enc(Terms.value)+'&RK='+Enc(CapKey)+'&RC='+Enc(Captcha));
  
  //--
  
  return false;

}

//-----------------

function BadNewAcct(M, I, B) {

  UpDIV(I, M, 'errmsg');
  if (B !== undefined) Foc(B);

  return false;

}

//-----------------

function SubAdr(C) { Req('POST', '/index.php?Location', Array('LocMsg', 15), 'LocMsg', 'Coords='+Enc(C)); }
function SetLng(L) { Req('POST', '/index.php?Langs', Array('', 2), 'PopErr', 'LID='+Enc(L)); }
function DelLoc(L) { Req('POST', '/index.php?DelLocation', Array('', 2), 'PopErr', 'LID='+Enc(L)); }

function AddRvw(D) { PopUp('med'); Req('POST', '/index.php?NewReview', Array('PUC', 19), 'PopErr', 'DID='+Enc(D)); }
function ShoWrn(I) { PopUp('small'); Req('POST', '/index.php?Warn', Array('PUC', 17), 'PopErr', 'I='+Enc(I)); }

function Share(DID) { if (!AcWin) { PopUp('med'); Req('POST', '/index.php?Share', Array('PUC', 19), 'PopErr', 'DID='+Enc(DID)); } }
function DHist(DID) { if (!AcWin) { PopUp('wmed'); Req('POST', '/index.php?History', Array('PUC', 19), 'PopErr', 'DID='+Enc(DID)); } }

function GetRvw(D,O) { if (!AcWin || O>0) { F = 'UpRvw'; if (!getI('RvwsD')) { PopUp('big'); F = 'ShwRvw'; } Req('POST', '/index.php?Reviews', F, 'PopErr', 'DID='+Enc(D)+'&O='+Enc(O)); } }
  
function SetCLoc(L,U) { LIdx = FndIdxID(Locs, L); if (LIdx !== false) { PenLoc = LIdx; ClWin(); HPanR(); if (U === undefined) GetData(); } }

function PopwMsg(D, M) { (M === undefined)? PopUp(D):PopUp(D, M); }
function ChgSrt(S,N) { SetSrt(S,N); Sort(1, false); Sort(3, false); UpLst(); ClWin(); if (IsR()) Req('POST', '/index.php?Sorts', null, null, 'S='+Enc(S)); }
function SignOut() { CData(); Req('GET', '/index.php?SignOut', Array('', 2), 'PopErr'); }

function ChgLng() { DoOp('small', 'Langs', 16); }
function ShoSrt() { DoOp('small', 'Sorts', 16); }
function ForPwd() { DoOp('small', 'ResetPass', 19); }
function Admin()  { DoOp('med', 'Administration', 19); }
function ShoLoc() { DoOp('med', 'Locations', 18); }
function ShoCtc() { DoOp('big', 'Message', 18); }
function ShoTrm() { DoOp('big', 'Terms', 18); }
function NewLoc() { DoOp('med', 'NewLocation', 18); }
function NewFav() { DoOp('med', 'NewFavorite', 18); }
function NamLoc() { DoOp('med', 'NameLocation', 18); }
function IEWarn() { DoOp('med', 'IEWarn', 18); }
function EdtNot() { DoOp('big', 'Notifications', 18); }
function EdtAct() { DoOp('big', 'Account', 18); }

function DoOp(D,U,C) { PopUp(D); Req('GET', '/index.php?'+U, Array('PUC', C), 'PopErr'); }

function RunUp()  { PopUp('big'); Req('GET', '/index.php?Update', Array('PUC', 24), 'PopErr', '', 600); }
function VLog() { PopUp('huge'); Req('GET', '/index.php?Log', Array('PUC', 19), 'PopErr'); }
function VStt() { PopUp('big'); Req('GET', '/index.php?Status', Array('PUC', 19), 'PopErr'); }

function EdtStr(M) { PopwMsg('huge', M); Req('GET', '/index.php?Strings', Array('PUC', 19), 'PopErr'); }
function EdtTag(M) { PopwMsg('huge', M); Req('GET', '/index.php?Tags', Array('PUC', 19), 'PopErr'); }
function EdtTyp(M) { PopwMsg('huge', M); Req('GET', '/index.php?Types', Array('PUC', 19), 'PopErr'); }
function EdtSrc(M) { PopwMsg('huge', M); Req('GET', '/index.php?Sources', Array('PUC', 19), 'PopErr'); }
function EdtDiv(M) { PopwMsg('huge', M); Req('GET', '/index.php?Divisions', Array('PUC', 19), 'PopErr'); }

function ShwRvw(R) { if (R['S']) { UpDIV('PUC', R['R']); return RvwB(R); } PopErr(); }
function UpRvw(R) { if (R['S']) { getI('RvwsD').innerHTML += R['R']; return RvwB(R); } PopErr(); }
function RvwB(R) { getI('RvwsB').innerHTML = R['D']+R['C']; }

function EdtFlt(ID) {

  HTML = '<DIV CLASS="ttlw">Settings for '+Locs[CurLoc].E+'<HR><DIV CLASS="flr"><DIV CLASS="fll algr sz13 b padrs padtxs">'+GS(1345)+':</DIV>';
  for (x=0;x<Opts.length;x++) {
    HTML += '<DIV CLASS="fll opt"><INPUT TYPE="Button" CLASS="butt pnt opt" VALUE="'+Opts[x]+'" onClick="TogAll('+x+');"></DIV>';
  }  
  HTML +='<DIV CLASS="fll wscr">&nbsp;</DIV></DIV></DIV><DIV CLASS="abs mvb algc sz13"><HR>'+GS(1342);
  if (!IsR()) HTML += ' '+GS(1343)+' <SPAN CLASS="fklnk" onClick="NewAcct();">'+GS(1344)+'</SPAN>.';
  HTML += '</DIV><DIV ID="PUCL" CLASS="abs fullsb flwa">';
  for (Cx in Cats) {
  
    if (Cats[Cx].ID > 0) {
  
      HTML += '<DIV CLASS="padt brdb"><DIV CLASS="flr">';
      for (x=0;x<Opts.length;x++) {
        HTML += '<DIV CLASS="fll opt">'+Opts[x]+'<BR />km<BR /><INPUT TYPE="Radio" VALUE="'+Opts[x]+'" ID="RC'+Cats[Cx].ID+'-'+x+'" NAME="RC'+Cats[Cx].ID+'" onClick="TogCat(this); SetTW('+Cats[Cx].ID+');"';
        if (((Cats[Cx].ID in Locs[CurLoc].CF) && (Opts[x] == Locs[CurLoc].CF[Cats[Cx].ID])) || ((!(Cats[Cx].ID in Locs[CurLoc].CF)) && (x == 4))) HTML += ' CHECKED';
        HTML += '></DIV>';
      }
      HTML += '</DIV><DIV ID="DC'+Cats[Cx].ID+'" CLASS="ttlset">'+Cats[Cx].N+'</DIV><DIV CLASS="clr"></DIV></DIV>';
      for (Tx in Cats[Cx].T) {
        Back = (Tx % 2 == 0)?' row':'';
        FT = (Cats[Cx].T[Tx].ID in Locs[CurLoc].TF); 
        FC = (Locs[CurLoc].CF[Cats[Cx].ID] == 0);
        
        HTML += '<DIV CLASS="mrgl'+Back+'"><DIV CLASS="flr iesux" ID="SPCBT'+Cats[Cx].T[Tx].ID+'">&nbsp;</DIV><DIV CLASS="flr opt"><INPUT ID="CBT'+Cats[Cx].T[Tx].ID+'" TYPE="Checkbox" VALUE="OK" onClick="Tog(this);"';
        if (!FT && !FC) HTML += ' CHECKED';
        if (FC) HTML += ' DISABLED';
        HTML += '></DIV><DIV ID="DT'+Cats[Cx].T[Tx].ID+'"';
        if (FT || FC) HTML += ' CLASS="s gray"';
        HTML += '>'+Cats[Cx].T[Tx].N+'</DIV></DIV>';
      }
    
    }
    
  }
  HTML += '</DIV>';
  PopUp('big');
  UpDIV('PUC', HTML);
  SetAW();
  
  if (ID !== undefined) {
  
    LDIV = getI('PUCL');
    TDIV = getI('DT'+ID);
    
    LDIV.scrollTop = TDIV.offsetTop;
  
  }
  
}

function TogAll(X) {

  for (Cx in Cats) {
    SetCur('cwt');
    CatIpt = getI('RC'+Cats[Cx].ID+'-'+X);
    CatIpt.checked = true;
    TogCat(CatIpt);
  }
  SetAW();  
  SetCur('cn');

}

function TogCat(X) {
  
  CID = parseInt(X.name.substr(2));
  if (CID > 0) {

    if (CID in Locs[CurLoc].CF) {
      if (Locs[CurLoc].CF[CID] == 0 || parseInt(X.value) == 0) TogTyps(CID, (parseInt(X.value) == 0));
      if (parseInt(X.value) == 100) {
        delete Locs[CurLoc].CF[CID];
      } else {
        Locs[CurLoc].CF[CID] = parseInt(X.value);
      }
    } else {
      if (parseInt(X.value) < 100) {
        if (parseInt(X.value) == 0) TogTyps(CID, true);
        Locs[CurLoc].CF[CID] = parseInt(X.value);
      }
    }
    
    if (IsR()) Req('POST', '/index.php?Filter', null, null, 'L='+Enc(Locs[CurLoc].ID)+'&C='+Enc(CID)+'&V='+Enc(parseInt(X.value)));

  }
  
  Sort(1);
  
}

function TogTyps(I,D) {

  CIdx = FndIdxID(Cats, I);
  if (CIdx !== false) {
    for (Tx in Cats[CIdx].T) {
      T = getI('CBT'+Cats[CIdx].T[Tx].ID);
      if (!D && (Cats[CIdx].T[Tx].ID in Locs[CurLoc].TF)) {
        T.checked = false;
        T.disabled = false;
      } else {
        T.checked = !D;
        T.disabled = D;
      }
      Tog(T, 0);
    }
  }
}

function Tog(X,S) {

  D = getI(X.id.replace('CB','D'));
  D.className = (X.checked)? '':'s gray';
  
  if (S === undefined) {
    TID = parseInt(X.id.substr(3));
    if (TID > 0) {
      if (X.checked && (TID in Locs[CurLoc].TF)) {
        delete Locs[CurLoc].TF[TID];
      } else if (X.checked === false && !(TID in Locs[CurLoc].TF)) {
        Locs[CurLoc].TF[TID] = 0;
      }
      Sort(1);
      if (IsR()) Req('POST', '/index.php?Filter', null, null, 'L='+Enc(Locs[CurLoc].ID)+'&T='+Enc(TID)+'&V='+Enc(parseInt(+X.checked)));
    }
  }
  
}

function SetTW(ID) {

  CIdx = FndIdxID(Cats, ID);
  if (CIdx !== false) {
  
    if (!(Cats[CIdx].ID in Locs[CurLoc].CF)) {
      TheW = 0;
    } else {
      for (x=0;x<Opts.length;x++) {
        if (Opts[x] == Locs[CurLoc].CF[Cats[CIdx].ID]) {
          TheW = (4 - x);
          break;
        }
      }
    }
  
    for (Tx in Cats[CIdx].T) {
      Spc = getI('SPCBT'+Cats[CIdx].T[Tx].ID);
      if (Spc) Spc.style.width = (TheW * 30)+'px';
    }
  }    

}

function SetAW() {

  for (Cx in Cats) {
    SetTW(Cats[Cx].ID);
  }
  
}

function KlD(I) {

  DP = getI('PanLC');
  DC = getI('D'+I);
  DP.removeChild(DC);

  DIdx = FndIdxID(Locs[CurLoc].D, I);
  if (DIdx !== false) {
    xL = Locs[CurLoc];
    xD = xL.D[DIdx];
    SetLoc(xD.L, null);
    xL.DTC--;
    if (!(xD.ID in xL.DF)) xL.DNC--;
  }
  
  Locs[CurLoc].DF[I] = 1;
  
  UpTot();
  if (Locs[CurLoc].DTC == 0) {
    UpLst();
  } else {
    ChkFe();
  }
  
  if (IsR()) Req('POST', '/index.php?Filter', null, null, 'L='+Enc(Locs[CurLoc].ID)+'&D='+Enc(I)+'&V=1');
  
}

function RmNew(DID) {

  if (!(DID in Locs[CurLoc].DF)) {
    if (getI('N'+DID)) {
    
      DD = getI('D'+DID);
      NS = getI('N'+DID);
      DD.removeChild(NS);

      Locs[CurLoc].DF[DID] = 0;
      if (CurTab==1) Locs[CurLoc].DNC--;
      if (CurTab==3) Locs[CurLoc].WNC--;
      UpTot();
      
    }
    
  }
  
}

function GetDet(DID,SM) {

  if (SM === undefined) {

    if (!AcWin) {
      if (DID in Dets) {
        DoPanR(Dets[DID]);
      } else {
        ClrPanR();
        Req('POST', '/index.php?Details', 'SetDet', 'PopErr', 'DID='+Enc(DID)+'&ULID='+Enc(Locs[CurLoc].ID));
        RmNew(DID);
      }
      
    }

  } else {
  
    Req('POST', '/index.php?Details', 'SetDet', 'PopErr', 'DID='+Enc(DID)+'&SM=1');
  
  }
  
}

function SetDet(R) {

  if (R['S']) {
    Dets[R['C']] = Array(R['R'][0], R['D'], R['R'][1], R['R'][2], R['R'][3]);
    DoPanR(Dets[R['C']]);
  } else {
    PopErr();
  }

}

function RstDet(DID) {

  if (DID in Dets) delete Dets[DID];
  GetDet(DID);

}

function ChkGSV(A,O) {

  if (getI('GSVDIV')) {
    LL = new google.maps.LatLng(A,O);
    
    var PO = {
      position: LL,
      pov: {
        heading: 0,
        pitch: 10,
        zoom: 0
      },
      addressControl: false,
      panControl: false,
      zoomControl: false,
      visible: true
    };

    P = new google.maps.StreetViewPanorama(getI('GSVDIV'), PO);
  }

}

function TogSav(O,DID,DT) { 

  if (!AcWin && IsR()) {
  
    if (DID < 0) {
      FIdx = FndIdxID(Locs[CurLoc].F, DID);
    } else {
      FIdx = FndIdxDID(Locs[CurLoc].F, DID);
    }
    
    if ( ( (O == 1) && (DID > 0) && (FIdx === false) ) || ( (O == 0) && (FIdx !== false) ) ) {
      if (DID > 0) RmNew(DID);
      Req('POST', '/index.php?SaveDeal', 'ProSav', 'PopErr', 'O='+Enc(O)+'&DID='+Enc(DID)+'&ULID='+Enc(Locs[CurLoc].ID)+'&DT='+Enc(DT));
    } 
    
  }
  
}

function ProSav(R) {

  if (R['S']) {
  
    FIdx = FndIdxDID(Locs[CurLoc].F, R['C']);
  
    if (R['R']) {
    
      if (FIdx === false) {
        F = R['C'];
        aF = new FSav(F['FID'], F['Desc'], ((F['EDate']==0)?0:(new Date(F['EDate'] * 1000))), F['Value'], F['DID'], F['Web']);
        if (!F['Web']) {
          for (Ly in F['Locs']) {
            FL = F['Locs'][Ly];
            aFL = new DLoc(F['FID'], Ly, F['Desc'], FL['Lat'], FL['Lng'], F['Icon'], FL['Dist']);
            aF.L.push(aFL);
          }
        }
        Locs[CurLoc].F.push(aF);
      }
      if (getI('SN'+F['DID'])) getI('SN'+F['DID']).className = 'fll din';
      Sort(2,CurTab==2);
      if (CurTab!=2) UpTot();
      
    } else {
    
      if (FIdx !== false) {
        SetLoc(Locs[CurLoc].F[FIdx].L, null);
        Locs[CurLoc].F.splice(FIdx, 1);
      }
      
      if (getI('SN'+R['C'])) getI('SN'+R['C']).className = 'fll dno';
      if (CurTab==2) {
        DP = getI('PanLC');
        DC = getI('F'+R['D']);
        UHighL(DC, R['D']);
        DP.removeChild(DC);
        if (Locs[CurLoc].F.length == 0) {
          UpLst();
        } else {
          UpTot();
        }
      } else {
        UpTot();
      }
      
    }
    
    Run(R['J']);
    
  } else {
    
    PopErr();
    
  }

}

function ChkSav(DID) {

  if (IsR()) {
    DIdx = FndIdxID(Locs[CurLoc].D, DID);
    if (DIdx !== false) {    
      FIdx = FndIdxDID(Locs[CurLoc].F, DID);
      if (FIdx === false) {
        PopQ(GS(1338), 'ClWin(); TogSav(1,'+DID+',0);');
      }
    }  
  }
  
}

function RstFlt(CONF) {

  if (CONF === undefined) {
    PopQ(GS(1355), 'ClWin(); RstFlt(1);');
  } else {
    if (IsR()) {
      Req('POST', '/index.php?ResetFilter', Array('', 3), 'PopErr', 'L='+Enc(Locs[CurLoc].ID));
    } else {
      Locs[CurLoc].CF.length = 0
      Locs[CurLoc].TF.length = 0;
      Locs[CurLoc].DF.length = 0;
      UpLst();
    }
  }

}

function DoFB() { 
  if (FBLoad) {
    PopUp('med');
    UpDIV('PUC', '<DIV CLASS="ttlw"><DIV>Facebook<HR></DIV></DIV><DIV CLASS="abs fulls flwa"><DIV CLASS="mgrx"><fb:like-box href="http://www.facebook.com/pages/Dealplotter/186105188102951" width="250" height="230" show_faces="true" stream="false" header="false"></fb:like-box></DIV></DIV>');
    FB.init({status: true, cookie: true, xfbml: true});
  } else {
    PWin('http://www.facebook.com/share.php?u=http://www.dealplotter.com');
  }
}

//////////////////////////////////////////////////////////////////////////
// RECAPTCHA FUNCTIONS
//////////////////////////////////////////////////////////////////////////

function NewCap(Msg) {

  Recaptcha.reload();
  Recaptcha.focus_response_field();

}

//////////////////////////////////////////////////////////////////////////
// STRING FUNCTIONS
//////////////////////////////////////////////////////////////////////////

function trim(S, C) { return ltrim(rtrim(S, C), C); }
function ltrim(S, C) { C = C || "\\s"; return S.replace(new RegExp("^[" + C + "]+", "g"), ""); }
function rtrim(S, C) { C = C || "\\s"; return S.replace(new RegExp("[" + C + "]+$", "g"), ""); }
function pad(N) { return ((N+'').length < 2)?'0'+N:N; }
function addC(N)
{
	N += '';
	x = N.split('.');
	x1 = x[0];
	x2 = x.length > 1 ? '.' + x[1] : '';
	var rgx = /(\d+)(\d{3})/;
	while (rgx.test(x1)) {
		x1 = x1.replace(rgx, '$1' + ',' + '$2');
	}
	return x1 + x2;
}

//////////////////////////////////////////////////////////////////////////
// DEBUG FUNCTIONS
//////////////////////////////////////////////////////////////////////////

/**
 * Concatenates the values of a variable into an easily readable string
 * by Matt Hackett [scriptnode.com]
 * @param {Object} x The variable to debug
 * @param {Number} max The maximum number of recursions allowed (keep low, around 5 for HTML elements to prevent errors) [default: 10]
 * @param {String} sep The separator to use between [default: a single space ' ']
 * @param {Number} l The current level deep (amount of recursion). Do not use this parameter: it's for the function's own use
 */
function print_r(x, max, sep, l) {

	l = l || 0;
	max = max || 10;
	sep = sep || ' ';

	if (l > max) {
		return "[WARNING: Too much recursion]\n";
	}

	var
		i,
		r = '',
		t = typeof x,
		tab = '';

	if (x === null) {
		r += "(null)\n";
	} else if (t == 'object') {

		l++;

		for (i = 0; i < l; i++) {
			tab += sep;
		}

		if (x && x.length) {
			t = 'array';
		}

		r += '(' + t + ") :\n";

		for (i in x) {
			try {
				r += tab + '[' + i + '] : ' + print_r(x[i], max, sep, (l + 1));
			} catch(e) {
				return "[ERROR: " + e + "]\n";
			}
		}

	} else {

		if (t == 'string') {
			if (x == '') {
				x = '(empty)';
			}
		}

		r += '(' + t + ') ' + x + "\n";

	}

	return r;

};



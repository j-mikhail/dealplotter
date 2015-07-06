//////////////////////////////////////////////////////////////////////////
// MAP FUNCTIONS
//////////////////////////////////////////////////////////////////////////

var NewStr = 0;
var NewCat = 0;
var NewTyp = 0;
var NewSrc = 0;
var NewDiv = 0;

function Mark(I) {
  I.className += ' ismod';
}

function AddStr(L) {

  T = getI('StrTbl');
  R = T.insertRow(-1);
  
  NewStr--;
  R.innerHTML = '<TD><INPUT CLASS="w80 ismod" TYPE="input" ID="S'+NewStr+'" VALUE="0"></TD>'+
                '<TD><INPUT CLASS="w150 ismod" TYPE="input" ID="D'+NewStr+'" VALUE=""></TD>';
                
  for (x=1;x<=L;x++) {
    R.innerHTML += '<TD><INPUT CLASS="w300 ismod" TYPE="input" ID="X'+NewStr+'-'+x+'"></TD>';
  }
  
  if (NewStr < -1) {
    getI('S'+NewStr).value = parseInt(getI('S'+(NewStr+1)).value)+1;
    getI('D'+NewStr).value = getI('D'+(NewStr+1)).value;
  }
  
  Foc('S'+NewStr);
  
}

function SavStr() {

  ClDIV('StrMsg');

  T = getI('StrTbl');
  I = T.getElementsByTagName('input');
  
  //Check errors
  for (x=0;x<I.length;x++) {
  
    switch (I[x].id.substr(0,1).toLowerCase()) {
    
      case 's':
        if (parseInt(I[x].value) == 0) {
          Foc(I[x].id);
          UpDIV('StrMsg', 'Valid number required.', 'red');
          return;
        }
        break;
        
      case 'd':
        if (IsE(I[x].id)) {
          Foc(I[x].id);
          UpDIV('StrMsg', 'Field can not be blank.', 'red');
          return;
        }
        break;
    }

  }
  
  S = 'Key='+Enc(getI('Key').value)+'&';
  for (x=0;x<I.length;x++) {
    if (I[x].className.indexOf('ismod') > -1) S += I[x].id + '=' + Enc(I[x].value) + '&';
  }
  S = rtrim(S, '&');

  Req('POST', '/index.php?Strings', 'ProSavStr', 'PopErr', S);
  
}

function ProSavStr(R) {

  if (R['S']) {
    Req('GET', '/index.php?Strings', Array('PUC', 19), 'PopErr');
  } else {
    UpDIV('StrMsg', R['R'], 'red');
  }
  eval(R['J']);

}

function AddCat() {
  
  NewCat--;
  
  var T = getI('MchTbl');
  var TR = T.insertRow(-1);
  var TC = TR.insertCell(-1);
  TC.innerHTML = '<INPUT CLASS="w150 ismod" ID="C'+NewCat+'" VALUE=""><INPUT CLASS="w300 mrgl ismod" ID="X'+NewCat+'" VALUE=""><INPUT CLASS="w80 ismod mrgl" ID="J'+NewCat+'" VALUE=""><INPUT TYPE="Button" CLASS="butt mrgl" VALUE="New Type" onClick="AddTyp(this,'+NewCat+')">';
  Foc('C'+NewCat);
  
}

function AddTyp(O, C) {

  NewTyp++;

  var T = getI('MchTbl');
  var TR = T.insertRow(O.parentNode.parentNode.rowIndex+1);
  var TC = TR.insertCell(-1);
  
  TC.innerHTML = '<INPUT CLASS="w150 mrgl ismod" ID="T'+C+'-'+NewTyp+'" VALUE=""><INPUT CLASS="w70p mrgl ismod" ID="K'+C+'-'+NewTyp+'" VALUE=""><INPUT CLASS="w80 mrgl ismod" ID="I'+C+'-'+NewTyp+'" VALUE="">';
  
}

function SavTag() {

  ClDIV('StrMsg');

  T = getI('MchTbl');
  I = T.getElementsByTagName('input');
  
  //Check errors
  for (x=0;x<I.length;x++) {
  
    switch (I[x].id.substr(0,1).toLowerCase()) {
    
      case 'c':
      case 't':
      case 'k':
        if (IsE(I[x].id)) {
          Foc(I[x].id);
          UpDIV('StrMsg', 'Field can not be blank.', 'red');
          return;
        }
        break;
    }

  }
  
  S = 'Key='+Enc(getI('Key').value)+'&';
  for (x=0;x<I.length;x++) {
    if (I[x].className.indexOf('ismod') > -1) S += I[x].id + '=' + Enc(I[x].value) + '&';
  }
  S = rtrim(S, '&');

  Req('POST', '/index.php?Tags', 'ProSavTag', 'PopErr', S);
  
}

function ProSavTag(R) {

  if (R['S']) {
    Req('GET', '/index.php?Tags', Array('PUC', 19), 'PopErr');
  } else {
    UpDIV('StrMsg', R['R'], 'red');
  }
  eval(R['J']);

}

function SavTyp() {

  T = getI('TypTbl');
  I = T.getElementsByTagName('select');

  S = 'Key='+Enc(getI('Key').value)+'&';
  for (x=0;x<I.length;x++) {
    if (I[x].className.indexOf('ismod') > -1) S += I[x].id + '=' + Enc(I[x].value) + '&';
  }
  S = rtrim(S, '&');
  
  Req('POST', '/index.php?Types', 'ProSavTyp', 'PopErr', S);

}

function ProSavTyp(R) {

  if (R['S']) Req('GET', '/index.php?Types', Array('PUC', 19), 'PopErr');
  eval(R['J']);

}

function AddSrc() {
  
  NewSrc--;
  
  var T = getI('SrcTbl');
  var TR = T.insertRow(-1);
  
  var TC = TR.insertCell(-1);
  TC.innerHTML = '<INPUT CLASS="w100 ismod" ID="D'+NewSrc+'" VALUE="New" onChange="Mark(this);">';
  TC = TR.insertCell(-1);
  TC.innerHTML = '<INPUT CLASS="w300 ismod" ID="H'+NewSrc+'" VALUE="" onChange="Mark(this);">';
  TC = TR.insertCell(-1);
  TC.innerHTML = '<INPUT CLASS="w100 ismod" ID="F'+NewSrc+'" VALUE="" onChange="Mark(this);">';
  TC = TR.insertCell(-1);
  TC.innerHTML = '<INPUT CLASS="w150 ismod" ID="R'+NewSrc+'" VALUE="" onChange="Mark(this);">';
  TC = TR.insertCell(-1);
  TC.innerHTML = '<SELECT ID="S'+NewSrc+'" CLASS="w100 mrgls ismod" onChange="Mark(this);">'+
                   '<OPTION VALUE="0" SELECTED>Inactive</OPTION>'+
                   '<OPTION VALUE="1">Active</OPTION>'+
                 '</SELECT>';
  TC = TR.insertCell(-1);
  TC.innerHTML = '<INPUT CLASS="w80 ismod" ID="E'+NewSrc+'" VALUE="" onChange="Mark(this);">';
  Foc('D'+NewSrc);
  
}

function SavSrc() {

  T = getI('SrcTbl');
  S = 'Key='+Enc(getI('Key').value)+'&';
  
  I = T.getElementsByTagName('input');
  for (x=0;x<I.length;x++) {
    if (I[x].className.indexOf('ismod') > -1) S += I[x].id + '=' + Enc(I[x].value) + '&';
  }
  I = T.getElementsByTagName('select');
  for (x=0;x<I.length;x++) {
    if (I[x].className.indexOf('ismod') > -1) S += I[x].id + '=' + Enc(I[x].value) + '&';
  }

  S = rtrim(S, '&');
  
  //opera.postError(S);
  
  Req('POST', '/index.php?Sources', 'ProSavSrc', 'PopErr', S);

}

function ProSavSrc(R) {

  if (R['S']) Req('GET', '/index.php?Sources', Array('PUC', 19), 'PopErr');
  eval(R['J']);

}

function AddDiv() {
  
  NewDiv--;
  
  var T = getI('DivTbl');
  var TR = T.insertRow(-1);
  
  var TC = TR.insertCell(-1);
  TC.innerHTML = '<INPUT CLASS="w30 ismod" ID="D'+NewDiv+'" VALUE="0" onChange="Mark(this);">';
  TC = TR.insertCell(-1);
  TC.innerHTML = '<INPUT CLASS="w30 ismod" ID="L'+NewDiv+'" VALUE="1" onChange="Mark(this);">';
  TC = TR.insertCell(-1);
  TC.innerHTML = '<INPUT CLASS="w30 ismod" ID="C'+NewDiv+'" VALUE="0" onChange="Mark(this);">';
  TC = TR.insertCell(-1);
  TC.innerHTML = '<INPUT CLASS="w150 ismod" ID="R'+NewDiv+'" VALUE="0" onChange="Mark(this);">';
  TC = TR.insertCell(-1);
  TC.innerHTML = '<INPUT CLASS="w150 ismod" ID="U'+NewDiv+'" VALUE="" onChange="Mark(this);">';
  TC = TR.insertCell(-1);
  TC.innerHTML = '<INPUT CLASS="w30 ismod" ID="T'+NewDiv+'" VALUE="0" onChange="Mark(this);">';
  TC = TR.insertCell(-1);
  TC.innerHTML = '<INPUT CLASS="w100 ismod" ID="A'+NewDiv+'" VALUE="0" onChange="Mark(this);">';
  TC = TR.insertCell(-1);
  TC.innerHTML = '<INPUT CLASS="w100 ismod" ID="O'+NewDiv+'" VALUE="0" onChange="Mark(this);">';
  TC = TR.insertCell(-1);
  TC.innerHTML = '<INPUT CLASS="w100 ismod" ID="N'+NewDiv+'" VALUE="0" onChange="Mark(this);">';
  TC = TR.insertCell(-1);
  TC.innerHTML = '<INPUT TYPE="Button" CLASS="butt pnt" onClick="FndLL('+NewDiv+');" VALUE="LL">';

  Foc('D'+NewDiv);
  
}

function SavDiv() {

  T = getI('DivTbl');
  S = 'Key='+Enc(getI('Key').value)+'&';
  
  I = T.getElementsByTagName('input');
  for (x=0;x<I.length;x++) {
    if (I[x].className.indexOf('ismod') > -1) S += I[x].id + '=' + Enc(I[x].value) + '&';
  }
  I = T.getElementsByTagName('select');
  for (x=0;x<I.length;x++) {
    if (I[x].className.indexOf('ismod') > -1) S += I[x].id + '=' + Enc(I[x].value) + '&';
  }

  S = rtrim(S, '&');
  
  //opera.postError(S);
  
  Req('POST', '/index.php?Divisions', 'ProSavDiv', 'PopErr', S);

}

function ProSavDiv(R) {

  if (R['S']) Req('GET', '/index.php?Divisions', Array('PUC', 19), 'PopErr');
  eval(R['J']);

}

function FndLL(ID) {

  RID = getI('R'+ID);
  Lat = getI('A'+ID);
  Lng = getI('O'+ID);
  
  Qry = prompt('City name:', RID.value);

  GeoC.geocode( {'address': Qry}, function(results, status) {
    if (status == google.maps.GeocoderStatus.OK) {
      alert('Matched to '+results[0].formatted_address);
      Lat.value = results[0].geometry.location.lat();
      Mark(Lat);
      Lng.value = results[0].geometry.location.lng();
      Mark(Lng);
    } else {
      alert('Error or no results');
    }
  });
  
}

function FndTZ() {

  Req('POST', '/index.php?Timezones', 'ProSavDiv', 'PopErr', 'Key='+Enc(getI('Key').value));
  
}

function GetRec() {

  Req('POST', '/index.php?Log', 'ProGetRec', 'PopErr', 'Key='+Enc(getI('Key').value)+'&O='+Enc(NxtSet));

}

function ProGetRec(R) {

  if (R['S']) {
  
    var T = getI('LogTbl');
    T.innerHTML += R['R'];
  
  }
  
  eval(R['J']);

}

function GetGrp(CELL, GID) {

  Req('POST', '/index.php?Log', 'ProGetGrp', 'PopErr', 'Key='+Enc(getI('Key').value)+'&G='+Enc(GID));
  CELL.innerHTML = '';

}

function ProGetGrp(R) {

  if (R['S']) {
  
    GrpRow = getI('LOGG' + R['D']);
    GrpRow.innerHTML = '<DIV>'+R['R']+'</DIV>';
    
  } else {
  
    eval(R['J']);
    
  }

}
var nextId =1000;

// A button that runs window[call](args...) through js/dp-actions.js (no inline handler).
function tpActionButton(value, call, args) {
	var button = document.createElement("input");
	button.type = "button";
	button.className = "button";
	button.value = value;
	button.setAttribute("data-dp-call", call);
	button.setAttribute("data-dp-args", JSON.stringify(args));
	return button;
}

/*
Array.prototype.remove = function(start, end) {
    this.splice(start, end);
    return this;
}

Array.prototype.insert = function(pos, item) {
    this.splice(pos, 0, item);
    return this;
}

EAPItem = function(){
	this.subItems = Array();
	this.number="";
	this.id=-1;
	this.parent=null;
}

EAPItem.addSubItem = function(item){
	this.subItems.insert(this.subItems.length,item);
}

EAPItem.removeSubItem = function(pos){
	this.subItems.remove(pos+1,pos-1);
}
*/

function addItem(id,itemName,index,identation){
	nextId++;
	if(id==''){
		id=nextId;
	}
	
	var table = document.getElementById("tb_eap");
	var lastRow= table.rows.length;
	
	var row = table.insertRow(lastRow);
	row.id="row_"+id;
	
	//numbering
	var td = row.insertCell(0);
	td.noWrap=true;
	var div=document.createElement("div");
	div.id="div_numbering_"+id;
	div.innerHTML="";
	td.appendChild(div);
	
	//add field to store numbering
	var field=document.createElement("input");
	field.id="number_field_"+id;
	field.name="number_field_"+id;
	field.type="hidden";
	td.appendChild(field);
	
	//add field to store leaf info
	var field=document.createElement("input");
	field.id="leaf_field_"+id;
	field.name="leaf_field_"+id;
	field.type="hidden";
	td.appendChild(field);
	
	//sorter
	var td = row.insertCell(1);
	td.noWrap=true;
	var div=document.createElement("div");
	div.appendChild(tpActionButton("\u2191", "moveRow", [-1, row.id]));
	div.appendChild(tpActionButton("\u2193", "moveRow", [1, row.id]));
	td.appendChild(div);
	//Identation
	var td = row.insertCell(2);
	td.noWrap=true;
	var div=document.createElement("div");
	div.appendChild(tpActionButton("\u2190", "identation", [String(id), -1]));
	div.appendChild(tpActionButton("\u2192", "identation", [id, 1]));
	td.appendChild(div);
	//Identation inputs
	var td = row.insertCell(3);
	td.noWrap=true;
	var span=document.createElement("span");
	span.id="identation_"+id;
	span.innerHTML=identation;
	td.appendChild(span);
	var identation_field=document.createElement("input");
	identation_field.id="identation_field_"+id;
	identation_field.name="identation_field_"+id;
	identation_field.type="hidden";
	identation_field.value=identation;
	td.appendChild(identation_field);
	//Description
	var field=document.createElement("input");
	field.type="text";
	field.className="text";
	field.id="description_"+id;
	field.name="description_"+id;
	field.value=itemName;
	field.style.width="80%";
	td.appendChild(field);
	
	//add a exclude button
	td = row.insertCell(4);
	td.noWrap=true;
	div=field=document.createElement("div");
	div.appendChild(tpActionButton("X", "deleteRole", [row.id]));
	td.appendChild(div);
	
	updateEAPItemsNumbers();
}

function identation(i,type){
	var div=document.getElementById("identation_"+i);
	var field = document.getElementById("identation_field_"+i);
	var row=document.getElementById("row_"+i);
	var row_index=row.rowIndex;
	
	if(type==1){
		div.innerHTML+="&nbsp;&nbsp;&nbsp;";
		field.value+= "&nbsp;&nbsp;&nbsp;";
	}else{
		div.innerHTML=div.innerHTML.replace("&nbsp;&nbsp;&nbsp;","");
		field.value = div.innerHTML.replace("&nbsp;&nbsp;&nbsp;","");;
	}
	updateEAPItemsNumbers();
}

function deleteRole(rowId){
	var id=rowId.substring(4,rowId.length);
	var field=document.getElementById("items_ids_to_delete");
	field.value+=field.value==""?id:","+_id;
	var i=document.getElementById(rowId).rowIndex;
	document.getElementById('tb_eap').deleteRow(i);
	updateEAPItemsNumbers();
}

function moveRow(direction,rowId){
	var oTable = document.getElementById('tb_eap');
	var trs = oTable.tBodies[0].getElementsByTagName("tr");
	var i = document.getElementById(rowId).rowIndex;
	var j = i+direction;
	if(j==0){
		return false;
	}
	if(i >= 0 && j >= 0 && i < trs.length && j < trs.length){
		if(i == j+1) {
			oTable.tBodies[0].insertBefore(trs[i], trs[j]);
		} else if(j == i+1) {
			oTable.tBodies[0].insertBefore(trs[j], trs[i]);
		} else {
			var tmpNode = oTable.tBodies[0].replaceChild(trs[i], trs[j]);
			if(typeof(trs[i]) != "undefined") {
				oTable.tBodies[0].insertBefore(tmpNode, trs[i]);
			} else {
				oTable.appendChild(tmpNode);
			}
		}		
	}else{
		//alert("Invalid Values!");
		return false;
	}
	updateEAPItemsNumbers();
}

function getCurrentSubLevelIndex(sublevelString){
	if(sublevelString.indexOf(".")==-1){
		return 0;
	}
	var sublevels=sublevelString.split(".");
	return sublevels.length-1;
}

function getFormatedNumberOnLevel(sublevelString,level){
	var sublevels=sublevelString.split(".");
	var out="";
	for(k=0;k<=level;k++){
		out=out+sublevels[k]+".";
	}
	return out;
}

function resetSubNumbersAfterLevel(list,level){
	for(k=level+1;k<list.length;k++){
		list[k]=0;
	}
	return list;
}


function updateEAPItemsNumbers(){
	var table = document.getElementById("tb_eap");
	// Only the WBS item rows: the Planning and monitoring tab puts header, form and
	// activity rows in the same table, and has no number or leaf field for an item.
	var rows=[];
	for(i=0;i<table.rows.length;i++){
		var row_id=table.rows[i].id;
		if(row_id.indexOf("row_")==0 && document.getElementById("identation_"+row_id.substring(4))){
			rows.push(row_id.substring(4));
		}
	}
	var lastRow=rows.length;
	var currentNumber=0;
	var currentFormatedNumber="";//String,used to concat sublevels
	var currentSubNumbers=[0,0,0,0,0,0,0,0,0,0];
	var previousIdentation="";
	
	for(i=0;i<lastRow;i++){
		var id=rows[i];
		
		var identation =document.getElementById("identation_"+id).innerHTML;
		var number_div=document.getElementById("div_numbering_"+id) || {};
		var number_field=document.getElementById("number_field_"+id) || {};
		var is_leaf_field=document.getElementById("leaf_field_"+id) || {};
		
		//set is leaf info
		if((i+1)<lastRow){
			var next_identation =document.getElementById("identation_"+rows[i+1]).innerHTML;
			if(identation.length >= next_identation.length){
				is_leaf_field.value="1";
			}else{
				is_leaf_field.value="0";
			}
		}else{
			is_leaf_field.value="1";
		}
		var is_leaf_icon = is_leaf_field.value == "1" ? " * " : "" ;
		
		if(identation.length==0){
			currentNumber++;
			currentFormatedNumber=currentNumber+"";
			number_div.innerHTML=currentNumber+is_leaf_icon;
			number_field.value=currentNumber;		
			for(j=0;j<currentSubNumbers.length;j++){
				currentSubNumbers[j]=0;
			}
		}else{
			if(identation.length > previousIdentation.length){
				var subIndex=getCurrentSubLevelIndex(currentFormatedNumber);
				currentSubNumbers[subIndex+1]++;
				if(currentFormatedNumber.indexOf(".")==-1){
					currentFormatedNumber+=".";
				}else{
					currentFormatedNumber=getFormatedNumberOnLevel(currentFormatedNumber,subIndex-1)+currentSubNumbers[subIndex]+".";
				}
				number_div.innerHTML=currentFormatedNumber+currentSubNumbers[subIndex+1]+is_leaf_icon;
				number_field.value=currentFormatedNumber+currentSubNumbers[subIndex+1];
			}else{
				if(identation.length < previousIdentation.length){
					var subIndex=getCurrentSubLevelIndex(currentFormatedNumber);
					var levels_to_return=1;
					if(subIndex>1){
						levels_to_return=2;
					}
					
					currentFormatedNumber=getFormatedNumberOnLevel(currentFormatedNumber,subIndex - levels_to_return);
					currentSubNumbers[subIndex-1]++;
					number_div.innerHTML=currentFormatedNumber+currentSubNumbers[subIndex-1]+is_leaf_icon;
					number_field.value=currentFormatedNumber+currentSubNumbers[subIndex-1];
					currentSubNumbers=resetSubNumbersAfterLevel(currentSubNumbers, subIndex-1);
				}else{
					if(identation.length == previousIdentation.length){
						var subIndex=getCurrentSubLevelIndex(currentFormatedNumber);
						currentSubNumbers[subIndex]++;
						number_div.innerHTML=currentFormatedNumber+currentSubNumbers[subIndex]+is_leaf_icon;
						number_field.value=currentFormatedNumber+currentSubNumbers[subIndex];
					}
				}
			}
		}
		previousIdentation=identation;		
	}
}

function saveEAP(){
	var idsField=document.getElementById("eap_items_ids");
	idsField.value="";
	var table = document.getElementById("tb_eap");
	var lastRow= table.rows.length;
	for(i=1;i<lastRow;i++){
		var row_id=table.rows[i].id;
		var id=row_id.substring(4,row_id.length);
		if(idsField.value==""){
			idsField.value=id;
		}else{
			idsField.value +=","+id;
		}
	}

	document.getElementById("form_eap").submit();
}
var pre = new Array();
var pos = new Array();
var divs = new Array();
//constants
var width=100;
var height=45;

function save_mdp(){
	var tasks_dependencies_ids_field=document.getElementById("tasks_dependencies_ids");
	var tasks_positions_field=document.getElementById("tasks_positions");
	tasks_dependencies_ids_field.value="";
	tasks_positions_field.value="";
	var out="";
	out=out+"\n";
	var prefix="block_";
	for (j=0;j<pre.length;j++){
		var task_id = pre[j].parentEl.id;
		tasks_dependencies_ids_field.value += "#"+ task_id.substring(prefix.length,task_id.length) + ":" ;
		var xy=pre[j].getXY();
	    tasks_positions_field.value += "#"+ task_id.substring(prefix.length,task_id.length) + ":" +(xy[0]+7) + "," + (xy[1]-(height*0.65));
		var relatedNodes="";
		//out=out+"\ntask id:"+task_id+"\n";
		 var relatedNodes="";
		 for(i=0;i<pre[j].wires.length;i++){
			var pre_id=pre[j].wires[i].terminal1.parentEl.id; 
			if(relatedNodes==""){
				relatedNodes+= pre_id.substring(prefix.length,pre_id.length);	
			}else{
				relatedNodes+= "," + pre_id.substring(prefix.length,pre_id.length);	
			}
		 }
		 tasks_dependencies_ids_field.value+=relatedNodes;
		 //out+="\nPre Tasks: "+ relatedNodes+"\n";
	}
	//alert(out);

	document.getElementById("form_mdp").submit();
	
}

function addNew(name,id,posX,posY){
	var container=document.getElementById("graph_panel");
	/*
	if(posY==-1){
		
		var top=container.style.top; 
		top=top.replace("px","");
		top=parseInt(top);
		posY = (top+70)+"px";
	}
	*/
	var i=pre.length;
	var div_name="block_"+id;
	var div=document.createElement("div");
	div.name=div_name;
	div.id=div_name;
	div.className="blockBox";
	//div.style.width=150+"px";
	//div.style.height=70+"px";
	div.style.width=width+"px";
	div.style.height=height+"px";
	divs[divs.length]=div;
	if(posX!=-1){
		div.style.left=posX+"px";
	}else{
		//div.style.left=container.style.left;
	}
	if(posY!=-1){
		try{
			div.style.top=posY+"px";
		}catch(e){}
	}else{
		//div.style.top=container.style.top;
	}
	//&nbsp;
	// The task name is text, never HTML.
	div.textContent="";
	div.appendChild(document.createElement("br"));
	div.appendChild(document.createTextNode(name));
	div.style.display="block";
	document.getElementById("graph_panel").appendChild(div);
	var block = YAHOO.util.Dom.get(div_name);
	//pre[i]=new WireIt.Terminal(block, {direction: [-1,0], wireConfig:{drawingMethod: "arrows"}, offsetPosition: [-18,35]});
	//pos[i]=new WireIt.Terminal(block, {direction: [1,0], wireConfig:{drawingMethod: "arrows"} ,offsetPosition: [140,35]});
	pre[i]=new WireIt.Terminal(block, {direction: [-1,0], wireConfig:{drawingMethod: "bezierArrows"}, offsetPosition: [-22,(height/3)-1]});
	pos[i]=new WireIt.Terminal(block, {direction: [1,0], wireConfig:{drawingMethod: "bezierArrows"} ,offsetPosition: [width-8,(height/3)-1]});
	var terminals1 = [pre[i],pos[i]];
	new WireIt.util.DD(terminals1,block);
}

function zoom(signal){
	for (i=0;i<divs.length;i++){
		
		var div=divs[i];
		var width=div.style.width;
		var height=div.style.height;
		var fontSize=div.style.fontSize;
		width=width.replace("px","");
		height=height.replace("px","");
		fontSize=fontSize.replace("pt","");
		width=parseInt(width);
		height=parseInt(height);
		fontSize=parseInt(fontSize);
		div.style.width=(width+(width/10)*signal)+"px";
        div.style.height=(height+(height/10)*signal)+"px";
        div.style.fontSize=(fontSize+(1*signal))+"pt";
		// this.el.style.left = pos[0]+"px";
        //  this.el.style.top = pos[1]+"px";
		//pre[i].el.style.left = width+(width/10)*signal;
	
		var left=pos[i].el.style.left; 
		left=left.replace("px","");
		left=parseInt(left);
		pos[i].el.style.left = (left+(left/10)*signal)+"px";
		
		var top=pos[i].el.style.top; 
		top=top.replace("px","");
		top=parseInt(top);
		pos[i].el.style.top = (top+(top/10)*signal)+"px";
		
		var top=pre[i].el.style.top; 
		top=top.replace("px","");
		top=parseInt(top);
		pre[i].el.style.top = (top+(top/10)*signal)+"px";
		}
		
		wiresUpdate();
	}
	
	function addDependency(task_id_A,task_id_B){
		//find task A
		var terminalA= null;
		var terminalB= null;
		for (j=0;j<pre.length;j++){
			var task_id = pre[j].parentEl.id;
			if(task_id=="block_"+task_id_A){
				terminalA=pre[j];
				break;
			}
		}
		for (j=0;j<pos.length;j++){
			var task_id = pos[j].parentEl.id;
			if(task_id=="block_"+task_id_B){
				terminalB=pos[j];
				break;
			}
		}
		// A dependency on a task that is not in the diagram (another project, a task group) has no box.
		if (!terminalA || !terminalB) {
			return;
		}
		var w1 = new WireIt.Wire(terminalB,terminalA ,document.body);
		//w1.options.drawingMethod ="arrows";
		w1.options.drawingMethod ="bezierArrows";
		
		w1.redraw();
	}

	function wiresUpdate(){
		for (j=0;j<pre.length;j++){
			for(i=0;i<pre[j].wires.length;i++){
				pre[j].wires[i].redraw();
			}
		}
	}
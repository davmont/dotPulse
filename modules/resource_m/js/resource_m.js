/* RESOURCE_M resource_m.js, v 0.2.0 2012/07/19 */
/*
* Copyright (c) 2011-2012 Region Poitou-Charentes (France)
*
* Description:	Js function page of the Resource Management module.
*
* Author:		Simon BENUREAU, <simon.benureau@gmail.com>
*
* License:		GNU/GPL
*
* CHANGE LOG
*
* version 0.1.0
* 	Creation.
* version 0.1.2
* 	Optimize the getAssignment function.
* version 0.1.2.2
*	Fix the 1 hour tasks bug.
* version 0.2.0
*	Fix multi-level dynamic tasks
*	Re-Expand lines at any time
*	Merge completeTds scripts
*	Add mergeTd function which change the display way
*/

/* Set the <td></td> values for all lines.
** @param	int		scale
** @param	string	title_string
*/ 
function completeTd(scale, title_string)
{
	var end = true;
	var sum;
	var wait;
	var buffR;
	var buffG;
	var buffB;
	// reverse in order to begin with children
	$($(".todo").get().reverse()).each(
		function(i){
			sum = 0;
			wait = false;
			$(".child-of-"+this.parentNode.id+this.getAttribute('rel')).each(
				function (j){
					if($(this).hasClass("todo")){ wait = true; return;}
					else if (!isNaN(parseFloat(this.innerHTML)))
						sum += parseFloat(this.innerHTML);
				}
			);
			if(wait)
				end = false;
			else{
				buffR = Math.round(230 + (sum/scale)*(170-230));
				buffG = Math.round(238 + (sum/scale)*(221-238));
				buffB = Math.round(221 + (sum/scale)*(170-221));
			
				if (sum>0){
					this.innerHTML = Math.round(sum*4)/4;
					$('#convert').html(title_string);
					title_string = $('#convert').text();
					this.title = title_string;
					if (sum>scale)
						this.style.backgroundColor = dPthemeColor('#CC6666', 'bg');
					else
						this.style.backgroundColor = dPthemeColor('rgb('+buffR+','+buffG+','+buffB+')', 'bg');
				}else{
					this.innerHTML = '';
					this.style.backgroundColor = dPthemeColor('#ECECEC', 'bg');
				}
				
				$(this).removeClass("todo");
			}
		}
	);
	if(!end) completeTd(scale, title_string);
}

/* Merge <td></td> of each line when they have same value.
*/ 
function mergeTd()
{
	var remove=0;
	$("table.treeTable > tbody > tr").each(
		function(i){
			$(this).children().each(
				function(j){
					if(remove)
						remove--;
					else{
						while(($(this).text() == $(this).next().text()) && $(this).next().length != 0){
							$(this).next().remove();
							remove++;
							$(this).attr('colspan', ($(this).attr('colspan')+1));
						}
					}
				}
			);
		}
	);
}

/* Submit the button's <form> with an additional input 
** that hold what <tr></tr> are currently expanded in order to re-expand them on next page.
** @param	Object	button
*/ 
function submitWithExpandList(button)
{
	var element = document.createElement('input');
	element.setAttribute('type', 'hidden');
	element.setAttribute('value', getExpanded());
	element.setAttribute('name', 'expandedList');
	button.form.appendChild(element);
	button.form.submit();
}

/* Set the timeout for calling the function that will display the details : dispUserDetails().
** @param	event	ev
** @param	string	company
** @param	string	department
*/ 
function displayUserDetails(ev, company, department)
{
	return setTimeout(function() {dispUserDetails(ev, company, department);}, 1500);
}

/* Set correct values and display the details for a user.
** @param	event	ev
** @param	string	company
** @param	string	department
*/ 
function dispUserDetails(ev, company, department)
{
	var div = document.getElementById("userDetails");
	var xPos; 
	var yPos;
	if(ev.pageX || ev.pageY){
		xPos=ev.pageX;
		yPos=ev.pageY;
	}else{
		xPos=ev.clientX + document.body.scrollLeft - document.body.clientLeft;
		yPos=ev.clientY + document.body.scrollTop  - document.body.clientTop;
	}
	div.style.left=xPos+9+"px";
	div.style.top=yPos+15+"px";
	document.getElementById("uCompany").textContent=company;
	document.getElementById("uDepartment").textContent=department;
	div.style.display = "block";
	
}

/* Clear timeout and hide user details.
** @param	timer	t
*/ 
function hideUserDetails(t)
{
	clearTimeout(t);
	var div = document.getElementById("userDetails");
	div.style.display = "none";
}

/* Set the timeout for calling the function that will display the details : dispProjectDetails().
** @param	event	ev
** @param	string	owner
** @param	string	sdate
** @param	string	edate
** @param	string	company
** @param	string	department
*/ 
function displayProjectDetails(ev, owner, sdate, edate, company, department)
{
	return setTimeout(function() {dispProjectDetails(ev, owner, sdate, edate, company, department);}, 1500);
}

/* Set correct values and display the details for a project.
** @param	event	ev
** @param	string	owner
** @param	string	sdate
** @param	string	edate
** @param	string	company
** @param	string	department
*/ 
function dispProjectDetails(ev, owner, sdate, edate, company, department)
{
	var div = document.getElementById("projectDetails");
	var xPos; 
	var yPos;
	if(ev.pageX || ev.pageY){
		xPos=ev.pageX;
		yPos=ev.pageY;
	}else{
		xPos=ev.clientX + document.body.scrollLeft - document.body.clientLeft;
		yPos=ev.clientY + document.body.scrollTop  - document.body.clientTop;
	}
	div.style.left=xPos+9+"px";
	div.style.top=yPos+15+"px";
	document.getElementById("pOwner").textContent=owner;
	document.getElementById("pSDate").textContent=sdate;
	document.getElementById("pEDate").textContent=edate;
	document.getElementById("pCompany").textContent=company;
	document.getElementById("pDepartment").textContent=department;
	div.style.display = "block";
	
}

/* Clear timeout and hide project details.
** @param	timer	t
*/ 
function hideProjectDetails(t)
{
	clearTimeout(t);
	var div = document.getElementById("projectDetails");
	div.style.display = "none";
}

/* Set the timeout for calling the function that will display the details : dispTaskDetails().
** @param	event	ev
** @param	string	owner
** @param	string	percent
** @param	string	sdate
** @param	string	edate
** @param	string	dyna
*/
function displayTaskDetails(ev, owner, percent, sdate, edate, dyna)
{
	return setTimeout(function() {dispTaskDetails(ev, owner, percent, sdate, edate, dyna);}, 1500);
}

/* Set correct values and display the details for a task.
** @param	event	ev
** @param	string	owner
** @param	string	percent
** @param	string	sdate
** @param	string	edate
** @param	string	dyna
*/ 
function dispTaskDetails(ev, owner, percent, sdate, edate, disp)
{
	var div = document.getElementById("taskDetails");
	var xPos; 
	var yPos;
	if(ev.pageX || ev.pageY){
		xPos=ev.pageX;
		yPos=ev.pageY;
	}else{
		xPos=ev.clientX + document.body.scrollLeft - document.body.clientLeft;
		yPos=ev.clientY + document.body.scrollTop  - document.body.clientTop;
	}
	div.style.left=xPos+9+"px";
	div.style.top=yPos+15+"px";
	if(disp)
		document.getElementById("trDyna").style.visibility="visible";
	else
		document.getElementById("trDyna").style.visibility="hidden";
	document.getElementById("tOwner").textContent=owner;
	document.getElementById("tPercent").textContent=percent;
	document.getElementById("tSDate").textContent=sdate;
	document.getElementById("tEDate").textContent=edate;
	div.style.display = "block";
	
}

/* Clear timeout and hide task details.
** @param	timer	t
*/ 
function hideTaskDetails(t)
{
	clearTimeout(t);
	var div = document.getElementById("taskDetails");
	div.style.display = "none";
}

/* Set correct values and display the edition of the assignment of a user to a task.
** @param	event	ev
** @param	string	user
** @param	string	task
** @param	string	project
** @param	int		taskid
** @param	int		userid
** @param	float	complete
** @param	string	sdate
** @param	string	edate
** @param	dloat	aPercent
** @param	timer	t
*/ 
function displayAssignEdit(ev, user, task, project, taskid, userid, complete, sdate, edate, aPercent, t)
{
	var div = document.getElementById("assignEdit");
	var xPos; 
	var yPos;
	if(ev.pageX || ev.pageY){
		xPos=ev.pageX;
		yPos=ev.pageY;
	}else{
		xPos=ev.clientX + document.body.scrollLeft - document.body.clientLeft;
		yPos=ev.clientY + document.body.scrollTop  - document.body.clientTop;
	}
	div.style.left=xPos+9+"px";
	div.style.top=yPos+15+"px";
	document.getElementById("aUser").textContent		= user;
	document.getElementById("aTask").textContent		= task;
	document.getElementById("aProject").textContent	= project;
	document.getElementById("aTId").value			= taskid;
	document.getElementById("aUId").value			= userid;
	document.getElementById("aComplete").textContent	= complete;
	document.getElementById("show_aSDate").value	= sdate;
	document.getElementById("show_aEDate").value	= edate;
	var start 										= sdate.substr(6)+sdate.substr(3,2)+sdate.substr(0,2);
	var end 										= edate.substr(6)+edate.substr(3,2)+edate.substr(0,2);
	document.getElementById("aSDate").value			= start;
	document.getElementById("aEDate").value			= end;
	document.getElementById("aPercent").value		= aPercent;
	div.style.display 								= "block";	
	document.getElementById('aSCal').style.display	= 'table-cell';
	document.getElementById('aECal').style.display	= 'table-cell';
	document.getElementById("aPercent").disabled	= false;
	document.getElementById('ad').style.display		= 'none';
	document.getElementById('aa').style.display		= 'none';
	hideTaskDetails(t);
}

/* Return a list of the expanded tr separate by ','
** @return	string	list
*/ 
function getExpanded() {
	var tmp											= document.getElementsByTagName('tr');
	var pattern 									= new RegExp("(^|\\s)expanded(\\s|$)");
	var list										= ""
	for(var i=0; i<tmp.length; i++) {
		if(pattern.test(tmp[i].className))
			list = list + tmp[i].id + ",";
	}
	return list;
}

/* Switch the display mode of edit Form
*/ 
function switchTypeAA()
{
	document.getElementById('aPercent').disabled=true;
	document.getElementById('aa').style.display='table-row';
	document.getElementById('editType').value='aa';
}

/* Switch the display mode of edit Form
*/ 
function switchTypeAD()
{
	document.getElementById('aSCal').style.display='none';
	document.getElementById('aECal').style.display='none';
	document.getElementById('ad').style.display='table-row';
	document.getElementById('editType').value='ad';
}

/* Switch the display mode of edit Form
*/ 
function switchTypeNull()
{
	document.getElementById('aPercent').disabled=false;
	document.getElementById('aa').style.display='none';
	document.getElementById('aSCal').style.display='table-cell';
	document.getElementById('aECal').style.display='table-cell';
	document.getElementById('ad').style.display='none';
	document.getElementById('editType').value='';
}

/**************************** UTILITY FUNCTIONS FOR DATE MANAGEMENT **********************************/
/*
*	Calendar functions and date setting
*/
var calendarField = "";
function popCalendar(field) {
	calendarField = field;
	idate = field.value;
	window.open("index.php?m=public&a=calendar&dialog=1&callback=setCalendar&date=" + idate, 
				"calwin", "width=250, height=220, scrollbars=no, status=no");
}

function setCalendar(idate, fdate) {
	calendarField.value = idate;
	document.getElementById( "show_"+calendarField.name ).value = fdate;
}

/**************************** HANDLERS CALLED THROUGH js/dp-actions.js **********************************/
/* They replace the inline onclick/onmouseover/... attributes of index.php. The
** global "timer" (declared in index.php) holds the pending details timeout.
*/

/* Calendar link of a filter field of mainFrm: start_date or end_date.
** @param	string	name
*/
function rmPopCalendar(name)
{
	popCalendar(document.mainFrm.elements[name]);
}

/* Calendar link of the assignment edit form (aSDate or aEDate): switch the form
** to "adapt assignment" mode, then open the calendar.
** @param	string	name
*/
function rmAssignCalendar(name)
{
	switchTypeAA();
	popCalendar(document.mainFrm.elements[name]);
}

/* Focus on a calendar link of the assignment edit form.
** @param	string	id		aSDate or aEDate
** @param	string	global	name of the global holding the original date ("start" or "end")
*/
function rmAssignCalendarFocus(id, global)
{
	if (document.getElementById(id).value == window[global])
		switchTypeNull();
}

/* Change of the (hidden) assignment start date.
*/
function rmAssignStartChanged()
{
	document.getElementById('aPercent').disabled = 'disabled';
	document.getElementById('aa').style.display = 'table-row';
}

/* Focus, blur and change of the assignment field. After a change, blur no longer
** resets the form (the inline handler cleared its own onblur).
*/
function rmPercentFocus()
{
	switchTypeAD();
	this.select();
}

function rmPercentBlur()
{
	switchTypeNull();
}

function rmPercentChange()
{
	this.removeAttribute('data-dp-call-blur');
	switchTypeAD();
}

/* Close the assignment edit form.
*/
function rmHideAssignEdit()
{
	document.getElementById('assignEdit').style.display = 'none';
}

/* Date range links of the calendar header: set the range and submit.
** @param	string	sdate	YYYYMMDD
** @param	string	edate	YYYYMMDD
*/
function rmRange(sdate, edate)
{
	$('input#start_date').val(sdate);
	$('input#end_date').val(edate);
	$('#submitButton').click();
}

/* Hover details of a user, project or task line.
*/
function rmUserOver(ev, company, department)
{
	window.timer = displayUserDetails(ev, company, department);
}

function rmUserOut()
{
	hideUserDetails(window.timer);
}

function rmProjectOver(ev, owner, sdate, edate, company, department)
{
	window.timer = displayProjectDetails(ev, owner, sdate, edate, company, department);
}

function rmProjectOut()
{
	hideProjectDetails(window.timer);
}

/* @param	bool	editable	the task assignment can be edited: show the pencil icon
*/
function rmTaskOver(ev, editable, owner, percent, sdate, edate, disp)
{
	if (editable) {
		$(this).append($("<img style='float:right;' src='./images/icons/pencil.gif' />"));
		$(this).css('cursor', 'pointer');
	}
	window.timer = displayTaskDetails(ev, owner, percent, sdate, edate, disp);
}

function rmTaskOut(editable)
{
	if (editable) {
		$(this).find('img:last').remove();
		$(this).css('cursor', 'default');
	}
	hideTaskDetails(window.timer);
}

function rmAssignEdit(ev, user, task, project, taskid, userid, complete, sdate, edate, aPercent)
{
	displayAssignEdit(ev, user, task, project, taskid, userid, complete, sdate, edate, aPercent, window.timer);
}

/* Filter links (generateFilterLink(), class rmFilterLink): a click filters the page
** on that user or project; hovering shows the filter icon. Bound with jQuery rather
** than data-dp-* attributes so the hover details of the cell around the link
** (rmUserOver/rmProjectOver) keep working: dp-actions only runs the nearest element.
*/
function rmFilter(name, id)
{
	$('select[name=' + name + '_id]').val(id);
	$('#submitButton').click();
}

$(document).on('click', 'a.rmFilterLink', function () {
	rmFilter($(this).attr('data-rm-filter'), $(this).attr('data-rm-id'));
}).on('mouseenter', 'a.rmFilterLink', function () {
	$(this).append($("<img style='float:right;' src='./modules/resource_m/images/filter.png' height='12' width='12' />"));
}).on('mouseout', 'a.rmFilterLink', function () {
	$(this).find('img:last').remove();
});

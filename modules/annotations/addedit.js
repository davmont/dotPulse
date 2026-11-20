/* Annotations add/edit page (modules/annotations/addedit.php). */

// return the value of the radio button that is checked
// return an empty string if none are checked, or
// there are no radio buttons
function getCheckedValue(radioObj) {
	if (!radioObj) {
		return '';
	}
	var radioLength = radioObj.length;
	if (radioLength == undefined) {
		return radioObj.checked ? radioObj.value : '';
	}
	for (var i = 0; i < radioLength; i++) {
		if (radioObj[i].checked) {
			return radioObj[i].value;
		}
	}
	return '';
}

// to popup the calendar for choosing dates
function popCalendar(field) {
	var idate = document.chooseProject.annotation_show_date.value;
	window.open('index.php?m=public&a=calendar&dialog=1&callback=setCalendar&date=' + encodeURIComponent(idate),
		'calwin', 'width=280, height=250, scrollbars=no, status=no');
}

/**
*	@param string Input date in the format YYYYMMDD
*	@param string Formatted date
*/
function setCalendar(idate, fdate) {
	document.chooseProject.annotation_show_date.value = idate;
	document.chooseProject.show_date.value = fdate;
	document.chooseProject.submit();
}

// Delete button (after its data-dp-confirm).
function delIt() {
	document.frmDelete.submit();
}

// Check if all the necessary information was entered.
// msgs: the translated alerts {scope, resources, time}.
function SubmitIt(msgs) {
	var f = document.editFrm;
	var str = '';
	var err = 0;
	msgs = msgs || {};
	// Check for rationale for must do
	str = str + f.annotation_must_rationale.value;
	if (f.annotation_must.checked == true && str.replace(/^\s+|\s+$/g, '') == '') {
		alert('Please enter a rationale for a "Must Do"');
		err = 1;
	}
	// Check for rationale for ratings ( if it's yellow / red )
	str = '' + f.annotation_scope_desc.value;
	if ((getCheckedValue(f.annotation_scope) == 1 || getCheckedValue(f.annotation_scope) == 2) && str.replace(/^\s+|\s+$/g, '') == '') {
		err = 1;
		alert(msgs.scope);
	}

	str = '' + f.annotation_resources_desc.value;
	if ((getCheckedValue(f.annotation_resources) == 1 || getCheckedValue(f.annotation_resources) == 2) && str.replace(/^\s+|\s+$/g, '') == '') {
		err = 1;
		alert(msgs.resources);
	}

	str = '' + f.annotation_time_desc.value;
	if ((getCheckedValue(f.annotation_time) == 1 || getCheckedValue(f.annotation_time) == 2) && str.replace(/^\s+|\s+$/g, '') == '') {
		err = 1;
		alert(msgs.time);
	}
	str = '' + f.show_time.value;
	if (str.substr(1, 1) == ':') {
		f.show_time.value = '0' + f.show_time.value;
		str = '' + f.show_time.value;
	}
	if (str.substr(2, 1) != ':' || str.length != 5) {
		err = 1;
		alert('Please enter valid Time (HH:MM)');
	}
	if (err == 0) {
		f.submit();
	}
}

// popup for member selection: names is the list of contacts ("Last, F."),
// labels the translated {title, hint, close}. The popup is about:blank and
// inherits this page's CSP, so it is built with DOM calls and its clicks are
// handled by a listener added from here.
function selectTeam(names, labels) {
	var win = window.open('', '', 'width=300, height=600,resizable,scrollbars=no');
	if (!win) {
		return;
	}
	var doc = win.document;
	labels = labels || {};
	names = names || [];

	function el(tag, text, attrs) {
		var e = doc.createElement(tag);
		if (text !== null && text !== undefined) {
			e.appendChild(doc.createTextNode(text));
		}
		for (var k in attrs || {}) {
			if (Object.prototype.hasOwnProperty.call(attrs, k)) {
				e.setAttribute(k, attrs[k]);
			}
		}
		return e;
	}

	doc.title = labels.title || '';
	var body = doc.body || doc.documentElement.appendChild(doc.createElement('body'));
	body.style.backgroundColor = dPthemeColor('#ffebcd', 'bg');
	if (dPthemeIsDark()) {
		// Light default text, links and buttons on the dark background.
		doc.documentElement.style.colorScheme = 'dark';
	}
	body.appendChild(el('center', labels.hint));
	var form = body.appendChild(doc.createElement('form'));
	var index = form.appendChild(doc.createElement('center'));
	for (var i = 65; i <= 90; i++) {
		index.appendChild(el('a', String.fromCharCode(i) + ' ', { href: '#' + String.fromCharCode(i) }));
	}
	var list = form.appendChild(el('div', null, { style: 'overflow: auto; height: 430px; width: 290px;' }));
	var lastLetter = '';
	for (var j = 0; j < names.length; j++) {
		var letter = String(names[j]).substr(0, 1).toUpperCase();
		if (letter !== lastLetter) {
			lastLetter = letter;
			list.appendChild(doc.createElement('br'));
			list.appendChild(el('center')).appendChild(el('a', letter, { name: letter }));
		}
		list.appendChild(el('center')).appendChild(el('input', null, {
			name: 'neu', value: names[j], type: 'button', style: 'width: 200px;', size: '20', 'data-name': names[j]
		}));
	}
	form.appendChild(doc.createElement('br'));
	form.appendChild(el('center')).appendChild(el('a', labels.close, { href: '#', 'data-close': '1' }));

	doc.addEventListener('click', function (ev) {
		var t = ev.target;
		if (!t || !t.getAttribute) {
			return;
		}
		if (t.hasAttribute('data-name')) {
			Hinzufuegen(t.getAttribute('data-name') + '\n');
		} else if (t.hasAttribute('data-close')) {
			ev.preventDefault();
			win.close();
		}
	}, false);
}

function Hinzufuegen(txt) {
	document.editFrm.annotation_team.value += txt;
}

// array holding translation strings for used in javascript code
// translated key/value pairs are generated by Smarty templates
_translations = Array();

// $.ajaxSetup({cache: false});

$(document).bind('mobileinit', function() {
	$.mobile.selectmenu.prototype.options.nativeMenu = false;
});

$('[data-role="page"]').live('pageshow', function() {
	var url = location.hash;
	if (url.length > 0) {
		url = url.substr(1);
	} else {
		url = location.href;
	}
	// update the language form action URL
	$('#langForm').attr('action', url);

	// update the "Go to Standard View" href
	var match = url.match(/([&?]?ui=[^&]+)/);
	if (match) {
		var replace = ((match[1].indexOf('?') != -1) ? '?' : '&') + 'ui=standard';
		url = url.replace(match[1], replace);
	} else {
		url += ((url.indexOf('?') == -1) ? '?' : '&') + 'ui=standard';
	}
	url = url.replace('&ui-state=dialog', '');
	$('a.standard-view').each(function() {
		$(this).attr('href', url);
	});
});

function pwdToText(fieldId){
	var elem = document.getElementById(fieldId);
	var input = document.createElement('input');
	input.id = elem.id;
	input.name = elem.name;
	input.value = elem.value;
	input.size = elem.size;
	input.onfocus = elem.onfocus;
	input.onblur = elem.onblur;
	input.className = elem.className;
	if (elem.type == 'text' ){
		input.type = 'password';
	} else {
		input.type = 'text'; 
	}

	elem.parentNode.replaceChild(input, elem);
	return input;
}

function moreFacets(name){
	$("#more" + name).hide();
	$(".narrowGroupHidden_" + name).show();
}
function lessFacets(name){
	$("#more" + name).show();
	$(".narrowGroupHidden_" + name).hide();
}

var ajaxCallback = null;
function ajaxLogin(callback){
	ajaxCallback = callback;
	ajaxLightbox(path + '/MyResearch/AJAX?method=LoginForm');
}

function ajaxLightbox(urlToLoad, parentId, left, width, top, height){
	$.mobile.changePage( urlToLoad, {
		type: "get",
		role  : "dialog",
		showLoadMsg: true
	});
}

function hideLightbox(){
	$('.ui-dialog').dialog('close');
}

function processAjaxLogin(){
	var username = $("#username").val();
	var password = $("#password").val();
	if (!username || !password){
		alert("Please enter both the username and password");
		return false;
	}
	var url = path + "/AJAX/JSON?method=loginUser"
	$.ajax({url: url,
			data: {username: username, password: password},
			success: function(response){
				if (response.result.success == true){
					loggedIn = true;
					// Hide "log in" options and show "log out" options:
					$('.loginOptions').hide();
					$('.logoutOptions').show();
					$('#loginOptions').hide();
					$('#logoutOptions').show();
					$('#myAccountNameLink').html(response.result.name);
					
					if (ajaxCallback  && typeof(ajaxCallback) === "function"){
						ajaxCallback();
					}else{
						hideLightbox();
					}
				}else{
					alert("That login information was not recognized.  Please try again.");
				}
			},
			error: function(){
				alert("There was an error processing your login, please try again.");
			},
			dataType: 'json',
			type: 'post' 
	});
	
	return false;
}

function showProcessingIndicator(message){
	$.mobile.loading('show', {
		text: message,
		textVisible: true
	});
}

function getQuerystringParameters(){
	var vars = [];
	var q = document.URL.split('?')[1];
	if(q != undefined){
		q = q.split('&');
		for(var i = 0; i < q.length; i++){
			var hash = q[i].split('=');
			vars[hash[0]] = hash[1];
		}
	}
	return vars;
}

function returnEpub(returnUrl){
	$.getJSON(returnUrl, function (data){
		if (data.success == false){
			alert("Error returning EPUB file\r\n" + data.message);
		}else{
			alert("The file was returned successfully.");
			window.location.reload();
		}
		
	});
}

function cancelEContentHold(cancelUrl){
	$.getJSON(cancelUrl, function (data){
		if (data.result == false){
			alert("Error cancelling hold.\r\n" + data.message);
		}else{
			alert(data.message);
			window.location.reload();
		}
		
	});
}

function reactivateEContentHold(reactivateUrl){
	$.getJSON(reactivateUrl, function (data){
		if (data.error){
			alert("Error reactivating hold.\r\n" + data.error);
		}else{
			alert("The hold was activated successfully.");
			window.location.reload();
		}
		
	});
}

function getOverDriveSummary(){
	$.getJSON(path + '/MyResearch/AJAX?method=getOverDriveSummary', function (data){
		if (data.error){
			// Unable to load overdrive summary
		}else{
			// Load checked out items
			$("#checkedOutItemsOverDrivePlaceholder").html(data.numCheckedOut);
			// Load available holds
			$("#availableHoldsOverDrivePlaceholder").html(data.numAvailableHolds);
			// Load unavailable holds
			$("#unavailableHoldsOverDrivePlaceholder").html(data.numUnavailableHolds);
			// Load wishlist
			$("#wishlistOverDrivePlaceholder").html(data.numWishlistItems);
		}
	});
}
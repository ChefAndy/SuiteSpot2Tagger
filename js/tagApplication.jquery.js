$("#navcontainer").height($(window).height());
$("#frameContainer").height($(window).height());
$("#mainframe").height($(window).height());
var navmenuposition=1;
var currentnavitem=null;
var currentnavid=null;
var currentpageid=null;

function mainMenu() {
	$.getJSON( "./objbrowse.php", function( data ) {
		$('nav.leftpanel').empty();
		
		$('nav.leftpanel').addClass('menuloading');

		var items = [];
		$('nav.leftpanel').append('<div class="goback"></div> <div class="toctitle">Top Level</div>');
		$.each( data, function( key, val ) {
			items.push( "<li id='entry_'" + val._id.$id + "' class='navlink'> <span id='" + val._id.$id + "' class='naventry'>" + val.label + "</span>(<a target='_blank' href='" + val.urn + "'>PDS</a>)</li>" );
		});
		$( "<ul/>", {
			"class": "navlist",
			html: items.join( "" )
		}).appendTo( "nav.leftpanel" );
		$('nav.leftpanel').removeClass('menuloading');
	} );
}


function makeLinksClickable() {

	$(document).on('click', ".goback" , function() {
		$('nav.leftpanel').empty(); 
		mainMenu();
	});

	$(document).on('click', ".naventry" , function() {
		$('nav.leftpanel').empty(); 
		currentnavitem=$(this).html();
		currentnavid=$(this).attr('id');
		$('nav.leftpanel').append('<div class="goback">&lt;&lt;Go Back</div> <div class="toctitle">' + currentnavitem + ' </div>');
		$.getJSON( "./objbrowse.php?docID="+$(this).attr('id'), function( data ) {
			addPages(data);
		}, console.log('getJSON callback called'));
			//console.log($( this ).parent().attr("class"));
		});

	$(document).on('click', ".pagelink" , function() {
		$('.pagelink').css("background-color", "transparent");
		$(this).css("background-color", "#B2C8E2");
		var itemval=$(this).html();
		currentpageid=$(this).attr('id');
		$.getJSON( "./getTags.php?pageID="+$(this).attr('id'), function( tagdata ) {
			populateBottom(tagdata, itemval);
		}, console.log('getJSON callback called for tagging'));
			//console.log($( this ).parent().attr("class"));
		});

	$(function() {
		$( "#slider-vertical" ).slider({
			orientation: "vertical",
			range: "min",
			min: 0,
			max: 300,
			value: 100,
			slide: function( event, ui ) {
				$(".bottom").css({height: ui.value * 2 });
			}
		});
	});

}


function populateBottom(tagdata, itemval) {
	$('div.pageinfo').empty(); 
	$('div.pageinfo').append('<span class="pagetitle">'+ currentnavitem + ': ' + itemval +'</span>');

	$('div.tagaddcontainer').empty(); 
	$('div.tagaddcontainer').append('<div class="ui-widget"> <label for="taginput">Tag</label> <input name="tag" id="taginput" type="text"><br> <label for="tagcategory">Type</label> <input name="tag" id="tagcategory" type="text"> </div> <button id="addbutton">Addtag</button> </div>');

	$('div.taglistcontainer').empty(); 
	$.each( tagdata, function( key, val ) {
		addTag(val.tag, val.type, val._id.$id, null);
	});
	taggingActivate();


}

function addTag(tag, type, idstring, newornot) {
	if (newornot === null) {
		$('div.taglistcontainer').append('<div id="instance_' + idstring + '" class="taginstance"><span class="tagtype">'+ type +'</span><span class="tagtext">'+ tag +'</span><span id="remtag_'+ idstring +'" class="tagaction"><img src="./css/icons/tag_delete.png"></span><span id="edittag_'+ idstring +'" class="tagaction"><img src="./css/icons/tag_edit.png"></span></div>');
	} else {
		$('div.taglistcontainer').append('<div id="instance_' + idstring + '" class="taginstancenew"><span class="tagtype">'+ type +'</span><span class="tagtext">'+ tag +'</span><span id="remtag_'+ idstring +'" class="tagaction"><img src="./css/icons/tag_delete.png"></span><span id="edittag_'+ idstring +'" class="tagaction"><img src="./css/icons/tag_edit.png"></span></div>');
	}

	$('#remtag_'+ idstring).click(function(event) {
		removeTagDialog(idstring);
	});

	$('#edittag_'+ idstring).click(function(event) {
		editTagDialog(idstring, tag, type);
	});
}

function taggingActivate() {


	$.widget( "custom.catcomplete", $.ui.autocomplete, {
		_create: function() {
			this._super();
			this.widget().menu( "option", "items", "> :not(.ui-autocomplete-category)" );
		},
		_renderMenu: function( ul, items ) {
			var that = this,
			currentCategory = "";
			$.each( items, function( index, item ) {
				var li;
				if ( item.category != currentCategory ) {
					ul.append( "<li class='ui-autocomplete-category'>" + item.category + "</li>" );
					currentCategory = item.category;
				}
				li = that._renderItemData( ul, item );
				if ( item.category ) {
					li.attr( "data-tagtype", item.category);
					li.attr( "data-tagtext", item.label );
				}
			});
		}
	});

	$(function() {
		$( "#taginput" ).catcomplete({
			source: "getTags.php",
			delay: 0,

			select: function(event, ui) {
				var I = ui.item;
				$("#taginput" ).catcomplete("close");

				$('#taginput').val(I.label);
				$('#tagcategory').val(I.category);
	    //window.location = '/podjetje/'+I.value+'.html';
	    //$('#frm_company_id').val(I.value);
	    return false;
	}
});
	});

	$(function() {
		$( "#tagcategory" ).autocomplete({
			source: "getTags.php?tagtypes",
			minLength: 1,
			select: function( event, ui ) {
				console.log(ui);
			}
		});
	});


	$("#addbutton").click(function(event) {


		newTag(event);
	});

}

function newTag(event) {
	var category = $("#tagcategory").val();
	var tag = $("#taginput").val();
	var tagid = null;

	$.getJSON( "./modTag.php?pageID="+currentpageid+ "&objID=" + currentnavid+ "&tag=" + tag+ "&type=" + category, function( returndata ) {
		if (returndata.exception) {
			var exceptions = [];
			$.each(returndata.exception, function(index, val) {
				if (val === "tag") {
					exceptions= exceptions + 'Tags may contain only letters, numbers, periods, apostrophes, spaces, and commas. ';
				}
				if (val === "type") {
					exceptions= exceptions + 'Types may contain only letters and numbers. ';
				}
				if (val === "insert") {
					exceptions= exceptions + 'Could not insert tag into the database. Please contact asilva@law.harvard.edu for support. ';
				}
				if (val === "pageID") {
					exceptions= exceptions + 'Bad pageID. Please contact asilva@law.harvard.edu for support. ';
				}
				if (val === "objID") {
					exceptions= exceptions + 'Bad objID. Please contact asilva@law.harvard.edu for support. ';
				}
			});
			alert("Tag Not Inserted: " + exceptions);
		} else {
			console.log(returndata);
			addTag(tag, category, returndata.success, 'new');
		}
	}, console.log('json for adding tag'));
}

function removeTagDialog(tagid) {
	$(function() {
		$( "#dialog-confirm" ).dialog({
			resizable: false,
			height:140,
			modal: true,
			buttons: {
				"Delete": function() {
					removeTag(tagid);

					$( this ).dialog( "close" );
				},
				Cancel: function() {
					$( this ).dialog( "close" );
				}
			}
		});
	});
}



function editTag(tagid, newtag, newtype) {
	console.log("./modTag.php?edit="+tagid+"&tag="+newtag+"&type="+newtype);
	$.getJSON( "./modTag.php?edit="+tagid+"&tag="+newtag+"&type="+newtype, function( returndata ) {
		if (returndata.exception) {
			alert("Could not modify tag. Please contact asilva@law.harvard.edu for support.");
		} else {
			addTag(newtag, newtype, tagid, 'new');
			$('#instance_'+tagid).remove();
		}
	}, console.log('json for deleting tag'));

}

function removeTag(tagid) {
	console.log("./modTag.php?remove="+tagid);
	$.getJSON( "./modTag.php?remove="+tagid, function( returndata ) {
		if (returndata.exception) {
			alert("Could not delete tag. Please contact asilva@law.harvard.edu for support.");
		} else {
			$('#instance_'+tagid).remove();
		}
	}, console.log('json for deleting tag'));
}

function editTagDialog(tagid, existingtag, existingtype) {

	$(function() {
		var dialog, form,
		// From http://www.whatwg.org/specs/web-apps/current-work/multipage/states-of-the-type-attribute.html#e-mail-state-%28type=email%29
		tag = $( "#tag" ),
		tagtype = $( "#tagtype" ),
		allFields = $( [] ).add( tagtype ).add( tag ),
		tips = $( ".validateTips" );
		tag.val(existingtag);
		tagtype.val(existingtype);
		function updateTips( t ) {
			tips
			.text( t )
			.addClass( "ui-state-highlight" );
			setTimeout(function() {
				tips.removeClass( "ui-state-highlight", 1500 );
			}, 500 );
		}
		function checkLength( o, n, min, max ) {
			if ( o.val().length > max || o.val().length < min ) {
				o.addClass( "ui-state-error" );
				updateTips( "Length of " + n + " must be between " +
					min + " and " + max + "." );
				return false;
			} else {
				return true;
			}
		}
		function checkRegexp( o, regexp, n ) {
			if ( !( regexp.test( o.val() ) ) ) {
				o.addClass( "ui-state-error" );
				updateTips( n );
				return false;
			} else {
				return true;
			}
		}
		function submitEditTag() {
			var valid = true;
			allFields.removeClass( "ui-state-error" );
			valid = valid && checkLength( tag, "tag", 1, 200 );
			valid = valid && checkLength( tagtype, "tagtype", 1, 200 );
			if ( valid ) {
				editTag(tagid, tag.val(), tagtype.val());
				dialog.dialog( "close" );
			}
			return valid;
		}
		dialog = $( "#dialog-form" ).dialog({
			autoOpen: false,
			height: 300,
			width: 350,
			modal: true,
			buttons: {
				"Modify Tag": submitEditTag,
				Cancel: function() {
					dialog.dialog( "close" );
				}
			},
			close: function() {
				form[ 0 ].reset();
				allFields.removeClass( "ui-state-error" );
			}
		});
		form = dialog.find( "form" ).on( "submit", function( event ) {
			event.preventDefault();
			submitEditTag();
		});
		dialog.dialog( "open" );
	});	
}

function addPages(data) {
	var items = [];
	var oldsection;
	$.each( data, function( pagekey, pageval ) {

		if (pageval.sectionLabel) {
			console.log(pageval.sectionLabel);
		}
		if (pageval.sectionLabel != oldsection) {
			oldsection = pageval.sectionLabel;
			if (pageval.sectionLabel === null) {
				items.push( "<li class='pagesection'> No Section </li>" );
			}
			else {
				items.push( "<li class='pagesection'> " + pageval.sectionLabel+ "</li>" );
				console.log(pageval.sectionLabel);
			}
		}
		items.push( "<li class='pageitem'> <a class='pagelink' target='idsscreen' id='" + pageval._id+ "'' href='" + pageval.image + "?buttons=y'>" + pageval.label + "</a></li>" );
	});
	$( "<ul/>", {
		"class": "navlist",
		html: items.join( "" )
	}).appendTo( "nav.leftpanel" );	
}


function addIngestEntry(data) {
	var items = [];
	$.each( data, function( ingestkey, ingestval ) {
		items.push( "<tr class='ingestitem'><td><img src='" + ingestval.thumbnail + "'></td><td>" + ingestval.label+ "</td><td>" + ingestval.imgct + "</td></tr>" );
	});
	$( "<table/>", {
		"class": "my-new-ingesttable",
		html: items.join( "" )
	}).appendTo( "table.ingestlist" );	
}

function preIngestHandler() {
	$(document).on('click', "#ingestbutton" , function() {
		ingesturns=$("#unconfirmedurns").val();
		$.getJSON( "./ingest.php?unconfirmedurns="+ingesturns, function( infodata ) {
			jsoninput=infodata;
			addIngestEntry(infodata);
			ingestConfrimDialog(infodata)
		}, console.log("ingest callback"));
			//console.log($( this ).parent().attr("class"));
		});

}


function ingestConfrimDialog(jsoninput) {
	$(function() {
		$( "#ingest-confirm" ).dialog({
			resizable: false,
			height:640,
			width:768,
			modal: true,
			buttons: {
				"Ingest": function() {
					$("#mainframe").attr("src", "ingesting.html");
					$('nav.leftpanel').empty();
					$('nav.leftpanel').addClass('menuloading');
					$( this ).dialog( "close" );
					executeIngest(jsoninput);
				},
				Cancel: function() {
					$( this ).dialog( "close" );
				}
			}
		});
	});
}

function executeIngest(jsoninput) {

	$.post( "ingest.php", { urns: JSON.stringify(jsoninput) }, function( data ) {
		$("#mainframe").attr("src", "ingestcompleted.html");
		mainMenu();
	}, "json");

}

$( document ).ready(function() {
	mainMenu();
	makeLinksClickable();
	$("#switchstrip").click(function(){
		if (navmenuposition === 1 ) {
			$("#navcontainer").animate({marginLeft:'-18%'});
			$(".goback").animate({left:'-18%'});
			$(".toctitle").animate({left:'-18%'});
			$(".frameContainer").animate({width:'98%'});
			$(".bottom").animate({width:'98%', left: '2%'});
			navmenuposition = 0;
		} else {
			$("#navcontainer").animate({marginLeft:'0'});
			$(".goback").animate({left:'2%'});
			$(".toctitle").animate({left:'2%'});
			$(".frameContainer").animate({width:'80%'});
			$(".bottom").animate({width:'80%', left: '20%'});
			navmenuposition = 1;
		}
	});
	preIngestHandler();
});
//$("#myiframe").attr("src", "newwebpage.html");
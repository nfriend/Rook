function init() {
	
	//in case the browser doesn't natively support the array.forEach method
	if ( !Array.prototype.forEach ) {
	  Array.prototype.forEach = function(fn, scope) {
	    for(var i = 0, len = this.length; i < len; ++i) {
	      fn.call(scope || this, this[i], i, this);
	    }
	  }
	}
	
	Server = new FancyWebSocket('wss://nathanfriend.io:9300');

	//Let the user know we're connected
	Server.bind('open', function() {
		var response = {};
			response.action = "changename"
			response.data = playername; 
			message = JSON.stringify(response);
			send( message );		
		
		$("#connectingpage").css("display", "none");
		$("#lobby").css("display", "");
	});

	//OH NOES! Disconnection occurred.
	Server.bind('close', function( data ) {
		$("#serverdisconnectdialog").dialog("open");
	});

	//Log any messages sent from server
	Server.bind('message', function (payload) {
		interpretServerMessage(payload);		
	});
	
	$("#playername_button").button().click(function()
	{
		if($("#playername").val() === "")
		{
			createAlert("Please enter a name");
		}
		else
		{
			playername = $("#playername").val();
			$("#startpage").css("display", "none");
			$("#connectingpage").css("display", "");
			Server.connect();
		}
	})
	
	$("#playername").keydown(function(e)
	{		
		if(e.keyCode === 13)
			$("#playername_button").click();		
	})	
	
	$("#opengamesaccordian").accordion({
		collapsible: true,
		autoHeight: false
	});		
	
	$("#creategamebutton").button().click(function()
	{
		$("#creategamedialog").dialog("open");
	});
	
	$("#leavegamebutton").button().click(function()
	{
		$("#leavegameconfirmationdialog").dialog("open");	
	});
	
	$("#creategamedialog").dialog({
		autoOpen: false,
		modal: true,	
		width: 700,
		open: function()
		{
			$("#gamenameinput").css("background-color", "white");
		},				
		buttons: {
			"Create game": function()						
			{
				if( $("#gamenameinput").val() === "")
				{
					$("#gamenameinput").css("background-color", "#FFD3D3");
				}
				else
				{
					var response = {};
						response.action = "new";
						response.data = {
							name: $("#gamenameinput").val(),
							rookvalue: $("#rookcardvalueinput option:selected").val(),
							rookfirsttrick: $("#rookplayfirsttrickinput option:selected").val(),
							trumpbeforekitty: $("#trumpbeforekittyinput option:selected").val(),
							playto: $("#playtoinput option:selected").val()
						}; 
						message = JSON.stringify(response);		
						send( message );
						}						
			},
			"Cancel": function()
			{
				$("#creategamedialog").dialog("close");
			}
		}
	})
	
	$("#leavegameconfirmationdialog").dialog({
		autoOpen: false,
		modal: true,
		buttons: {
			"Yes, leave game": function()
			{
				$("#confirmbegingamedialog").dialog("close");
				
				var response = {};
					response.action = "leave";
					response.data = "";
					message = JSON.stringify(response);				
				send( message );								
			},
			"No": function()
			{
				$("#leavegameconfirmationdialog").dialog("close");
			}
		}
	});
	
	$("#lobbychatinput").keydown( function(e)
	{
		if (e.keyCode === 13)
		{
			var response = {};
			response.action = "chat"
			response.data = $('#lobbychatinput').val(); 
			message = JSON.stringify(response);
			send( message );
			log( 'You: ' + response.data, "blue");
			$("#lobbychatinput").val("");
		}
	});
	
	$("#joingamedialog").dialog({
		autoOpen: false,		
		modal: true,
		width: 440,
		open: function () {
			thisDialog = $("#joingamedialog");
			
			thisDialog.find("#team1players").html("");
			thisDialog.find("#team2players").html("");
			
			team1 = 0;
			team2 = 0;
			
			thisGameId = thisDialog.data("gameid");
						
			for (i = 0; i < allOpenGames.length; i++)
			{
				if (allOpenGames[i].id === thisGameId)
				{
					game = allOpenGames[i];
					break;				
				}						
			}
			
			
			thisDialog.dialog("option", "title", "Join game " + game.name + "?");
			
			if (game.team1player1)
			{
				thisDialog.find("#team1players").append(game.team1player1 + "<br />")
				team1++;
			}
			else
			{
				thisDialog.find("#team1players").append("(empty)" + "<br />")
			}
			
			if (game.team1player2)
			{
				thisDialog.find("#team1players").append(game.team1player2 + "<br />")
				team1++;
			}
			else
			{
				thisDialog.find("#team1players").append("(empty)" + "<br />")
			}
			
			if (game.team2player1)
			{
				thisDialog.find("#team2players").append(game.team2player1 + "<br />")
				team2++;
			}
			else
			{
				thisDialog.find("#team2players").append("(empty)" + "<br />")
			}
			
			if (game.team2player2)
			{
				thisDialog.find("#team2players").append(game.team2player2 + "<br />")
				team2++;
			}
			else
			{
				thisDialog.find("#team2players").append("(empty)" + "<br />")
			}
			
			if (team1 === 2)
			{
				$("#jointeam1button").button("disable");
			}
			else
			{
				$("#jointeam1button").button("enable");
			}
			
			if (team2 === 2)
			{
				$("#jointeam2button").button("disable");
			}
			else
			{
				$("#jointeam2button").button("enable");
			}
		},
		buttons: {
			"Cancel": function()
			{
				$("#joingamedialog").dialog("close");
			}
		}	
	});
	
	$("#jointeam1button").button().click( function()
	{
		gameid = $("#joingamedialog").data("gameid");
		
		var response = {};
			response.action = "join"
			response.data = {
				game: gameid,
				team: 1
			} 
			message = JSON.stringify(response);			
			send( message );
	});
	
	$("#jointeam2button").button().click( function()
	{
		gameid = $("#joingamedialog").data("gameid");
		
		var response = {};
			response.action = "join"
			response.data = {
				game: gameid,
				team: 2
			} 
			message = JSON.stringify(response);			
			send( message );
	});
	
	$("#confirmbegingamedialog").dialog({
		autoOpen: false,
		modal: true,
		open: function() {
			$("#confirmbegingamedialog").html("<p>This game now has four players.  Click \"Begin game\" to continue.</p>");						
		},
		buttons: {
			"Begin game": function () {
				$("#confirmbegingamedialog").html("<p>Waiting on other players to confirm...</p>");				
				var response = {};
					response.action = "confirm";
					response.data = "";
					message = JSON.stringify(response);			
					send( message );				
			},
			"Leave game": function () {
				$("#leavegameconfirmationdialog").dialog("open");
			}
		}
		
	})	
    
    $('#target').droppable({
        drop: function (event, ui)
        {
            var legalCard = false
            
            for(i = 0; i < allowedSuits.length; i++)
            {   
            	if ($(ui.draggable).data("suit") == allowedSuits[i])
            	{
            		legalCard = true;
            		break;
            	} 
                	
            }
            
            if (!legalCard)
            	return;
            	
        	$(ui.draggable).addClass("played");	

			var response = {};
				response.action = "game"
				response.data = {
					"command": "lay",
					"arguments": {
						"suit": $(ui.draggable).data("suit"),
						"number": $(ui.draggable).data("number")
					}					
				} 
				message = JSON.stringify(response);
				send( message );

			allowedSuits = [];

            $(ui.draggable).attr("dropped", "true")
            
            $("#faketarget").css("border-style", "none").css("background-color", "transparent").css("z-index", "49").children("p").css("display", "none");
			
			$(ui.draggable).appendTo("#target");

			spaceCards();
            
            $(ui.draggable).css({
                position: "absolute",
                left: "13px",
                top: "7px"
            }, 100);
        }
    });
    
    $('#passbutton').button().click(function()
    {
    	var response = {};
			response.action = "game";
			response.data = {
				"command": "bid",
				"arguments": "pass"
			}; 
			message = JSON.stringify(response);
			send( message );
    });
    
    $('#bidbutton').button().click(function()
    {
    	var response = {};
			response.action = "game";
			response.data = {
				"command": "bid",
				"arguments": $('#yourbid').val()
			}; 
			message = JSON.stringify(response);
			send( message );
    })
    
    $("#submitkitty").button({
    	disabled: true
    }).click( function() 
    {
    	chosenCards = [];
    	
    	chosenCardsForResponse = [];
    	
    	$(".card").each( function()
    	{
    		if ($(this).data("chosenforkitty") === "true")
    			chosenCards.push($(this));
    	})
    	
    	for(i = 0; i < chosenCards.length; i++)
    	{
    		chosenCardsForResponse.push({
    			"suit": chosenCards[i].data("suit"),
    			"number": chosenCards[i].data("number")
    		})
    	}
    	
    	$(".trumpoption").each( function() {
    		thisOption = $(this);
    		if (thisOption.data("chosentrump") === "true")
    			trumpColor = thisOption.attr("trumpcolor");
    	});
    	
    	var response = {};
		response.action = "game";
		response.data = {
			"command": "kitty",
			"arguments": chosenCardsForResponse,			
			"trumpcolor": trumpColor					
		}; 
		message = JSON.stringify(response);
		send( message );	
    })
	
	$(".trumpoption").click( function(event)
	{
		$(".trumpoption").each( function()
		{			
			$(this).css("background-image", "").data("chosentrump", "false");
		})
		
		$(event.target).css("background-image", "url('Images/checkmark.png')").data("chosentrump", "true");
		//$(event.target).html("S");		
	}).hover( function()
	{
		$(this).css("border-color", "white");
	}, function() {
		$(this).css("border-color", "black");
	}).filter(":first").data("chosentrump", "true").css("background-image", "url('Images/checkmark.png')");
	
	initializeOtherCards();	
	
	$(".namecontainer").each(function()
	{
		blinkDiv($(this), false);
	});
	
	$("#endrounddialog").dialog({
		autoOpen: false,
		modal: true,
		width: 550,
		height: 600,
		resizable: false,
		open: function ()
		{	
			data = $("#endrounddialog").data("endofgamedata");
			
			if((data.teamBidTaker == "2" && data.bidderMadeBid) || (data.teamBidTaker == "1" && !(data.bidderMadeBid)))
				teamWinner = 2;
			else
				teamWinner = 1;
			
			dialog = $("#endrounddialog");
			dialog.html("<p style='font-size: 1.2em'>Team " + teamWinner + " has won the round!</p>");
			dialog.append("<p style='font-size: 1.0em'>Round bid: " + data.bid + "</p>");
			dialog.append("<p style='float:left; text-align: center; width: 250px'>Team 1's round score: <strong>" + data.team1RoundScore + "</strong></p>");
			dialog.append("<p style='float:right; text-align: center; width: 250px'>Team 2's round score: <strong>" + data.team2RoundScore + "</strong></p>");
			dialog.append("<div style='clear:both'></div>");
			dialog.append("<p style='float:left; text-align: center; width: 250px'>Team 1's total score: <strong>" + data.team1TotalScore + "</strong></p>");
			dialog.append("<p style='float:right; text-align: center; width: 250px'>Team 2's total score: <strong>" + data.team2TotalScore + "</strong></p>");
			dialog.append("<div style='clear:both'></div>");
			dialog.append("<p>Cards in the kitty:</p>");
			dialog.append("<div id='endroundcardcontainer' style='position: absolute; top: 250px; width: 80%; background-color: red; left: 80px'></div>");
			for(i = 0; i < 5; i++)
			{
				if (data.kittyCards[i].suit === "rook")
					cardname = "rook.PNG";
				else
					cardname = data.kittyCards[i].suit + data.kittyCards[i].number + ".PNG";
				
				$("#endroundcardcontainer").append("<img src='Images/cards/" + cardname + "' style='position: absolute; margin-left:-136px; left: " + ((100/5) * (i+1)) + "%;' />");
			}
			dialog.append("<em id='roundstatus' style='visibility: hidden; position:absolute; bottom: 10px; width:270px'>Waiting on other players...</em>");
		},
		buttons: {
			"Ready for next round": function ()
			{	
				$("#roundstatus").css("visibility", "visible");
				var response = {};
				response.action = "game";
				response.data = {
					"command": "nextgame",
					"arguments": ""				
				}; 
				message = JSON.stringify(response);
				send( message );				
			}
		}		
	});
	
	$("#endgamedialog").dialog({
		autoOpen: false,
		modal: true,
		width: 550,
		height: 600,
		resizable: false,
		open: function()
		{
			data = $("#endgamedialog").data("endofgamedata");
						
			if(parseInt(data.team1TotalScore, 10) < parseInt(data.team2TotalScore, 10))
				teamWinner = 2;
			else
				teamWinner = 1;
			
			dialog = $("#endgamedialog");
			dialog.html("<p style='font-size: 1.2em'>Team " + teamWinner + " has won the game!</p>");
			dialog.append("<p style='font-size: 1.0em'>Round bid: " + data.bid + "</p>");
			dialog.append("<p style='float:left; text-align: center; width: 250px'>Team 1's round score: <strong>" + data.team1RoundScore + "</strong></p>");
			dialog.append("<p style='float:right; text-align: center; width: 250px'>Team 2's round score: <strong>" + data.team2RoundScore + "</strong></p>");
			dialog.append("<div style='clear:both'></div>");
			dialog.append("<p style='float:left; text-align: center; width: 250px'>Team 1's total score: <strong>" + data.team1TotalScore + "</strong></p>");
			dialog.append("<p style='float:right; text-align: center; width: 250px'>Team 2's total score: <strong>" + data.team2TotalScore + "</strong></p>");
			dialog.append("<div style='clear:both'></div>");
			dialog.append("<p>Cards in the kitty:</p>");
			dialog.append("<div id='endgamecardcontainer' style='position: absolute; top: 250px; width: 80%; background-color: red; left: 80px'></div>");
			for(i = 0; i < 5; i++)
			{
				if (data.kittyCards[i].suit === "rook")
					cardname = "rook.PNG";
				else
					cardname = data.kittyCards[i].suit + data.kittyCards[i].number + ".PNG";
				
				$("#endgamecardcontainer").append("<img src='Images/cards/" + cardname + "' style='position: absolute; margin-left:-136px; left: " + ((100/5) * (i+1)) + "%;' />");
			}
			
		},
		buttons: {
			"Back to lobby": function ()
			{
				$("#endgamedialog").dialog("close");
				$("#gametable").css("display", "none");
				$("#faketarget").css("border-style", "none").css("background-color", "transparent").css("z-index", "49").children("p").css("display", "none");				
				$("#gameaccordiancontainer").css("display", "");
				$("#ingamecontainer").css("display", "none");
				$("#lobby").css("display", "");
				initializeOtherCards();
				currentGameId = -1;
				hand = [];
				myPlayerNumber = 0;
				allowedSuits = [];
				numberOfCardsInTrick = 0;
				thisGame = null;
			}
		}		
	});
	
	$("#abortdialog").dialog({
		autoOpen: false,
		modal: true,
		resizable: false,
		width: 500,
		buttons:
		{
			"Back to lobby": function ()
			{
				$(".ui-dialog-content").dialog("close");
				$("#gametable").css("display", "none");
				$("#faketarget").css("border-style", "none").css("background-color", "transparent").css("z-index", "0").children("p").css("display", "none");
				$(".played").remove();				
				$("#gameaccordiancontainer").css("display", "");
				$("#ingamecontainer").css("display", "none");
				$("#lobby").css("display", "");
				initializeOtherCards();
				currentGameId = -1;
				hand = [];
				myPlayerNumber = 0;
				allowedSuits = [];
				numberOfCardsInTrick = 0;
				thisGame = null;
			}
		}
	});
	
	$("#serverdisconnectdialog").dialog({
		autoOpen: false,
		modal: true,
		resizable: false,
		width: 500
	});
	
	$("#nowsdialog").dialog({
		autoOpen: false,
		modal: true,
		resizable: false,
		width: 500	
	})
	
	$(".ui-dialog-titlebar-close").remove();
	
	if (!("WebSocket" in window))
	{
		$("#nowsdialog").dialog("open");		
	}
	
	// Added on 2018/09/18 to auto-initialize the web socket server
	// if it's not already running.
	$.get('server.php');
}
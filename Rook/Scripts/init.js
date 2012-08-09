function init() {
	Server = new FancyWebSocket('ws://127.0.0.1:9300');

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
		log( "Disconnected." );
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
						log(printObject(response));					
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
			log( 'You: ' + response.data, "blue");
			send( message );
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
	
		
	for(i = 0; i < 10; i++)
	{			
		$("#topcardscontainer").append("<img src='Images/cards/CardBackTop.PNG' style='position: absolute; margin-left: -140px; left: " + (i + 1) * (700/10) + "px' />");
	}
	
	for(i = 0; i < 10; i++)
	{			
		$("#leftcardscontainer").append("<img src='Images/cards/CardBackLeft.PNG' style='position: absolute; margin-top: -140px; top: " + (i + 1) * (400/10) + "px' />");
	}
	
	for(i = 0; i < 10; i++)
	{			
		$("#rightcardscontainer").append("<img src='Images/cards/CardBackRight.PNG' style='position: absolute; margin-top: -140px; top: " + (i + 1) * (400/10) + "px' />");
	}
	
	$(".namecontainer").each(function()
	{
		blinkDiv($(this), false);
	})
}
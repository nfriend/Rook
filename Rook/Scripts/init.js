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
	
	$("#readybutton").button({
		disabled: true
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
					$("#creategamedialog").dialog("close");
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
				$("#leavegameconfirmationdialog").dialog("close");
				$("#gameaccordiancontainer").css("display", "");
				$("#ingamecontainer").css("display", "none");
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
	
}
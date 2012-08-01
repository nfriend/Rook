function init() {
	Server = new FancyWebSocket('ws://127.0.0.1:9300');

	//Let the user know we're connected
	Server.bind('open', function() {
		$("#connectingpage").css("display", "none");
		$("#lobby").css("display", "");
	});

	//OH NOES! Disconnection occurred.
	Server.bind('close', function( data ) {
		log( "Disconnected." );
	});

	//Log any messages sent from server
	Server.bind('message', function( payload ) {
		var message = JSON.parse(payload);
		switch(message.action)
		{
			case "log":
				log("Log: " + message.message);
				break;
				
			case "chat":
				log(message.message, "black");
				break;
				
			case "command":
				command = message.message;
				switch(command)
				{
					case "losepermission":
						window.permission = false;
						log("Lost permission");
						break;
					case "gainpermission":
						window.permission = true;
						log("Gained permission");
						break;
				}
				break;
		}
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
	
}
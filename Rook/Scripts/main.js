var Server;
window.permission = true;
var instanceQueue = [];
var playername = "Player";
var allOpenGames = {};

$(init);

function log(text, color) {
						
	text = text.replace(/:D/g, '<img style="vertical-align:middle;" src="http://www.animated-gifs.eu/anisigns/signer/laughing/laughing.gif" />');
	
	if(!color)
	{
		color = "red"
	}
	
	$log = $('#lobbychatarea');
	//Add text to log
	$log.append("<div style='color:" + color + ";'>"+ text + "</div>");
	//Autoscroll
	$log[0].scrollTop = $log[0].scrollHeight - $log[0].clientHeight;
}

function send( text ) {
	Server.send( 'message', text );
}

function createAlert(message, color, textcolor)
{				
	if(!color)
	{
		color = "red";
	}
	
	if(!textcolor)
	{
		textcolor = "white";
	}
	
	instanceQueue.push({message: message, color: color, textcolor: textcolor});
	
	if (instanceQueue.length > 1)
	{
		return;
	}
	else
	{					
		startAlert();
	}
	
	function startAlert()
	{				
		thisMessage = instanceQueue[0].message;
		thisColor = instanceQueue[0].color;
		thisTextColor = instanceQueue[0].textcolor;
		
		$("#alertbox").html(thisMessage).css({
			marginLeft: (function() {
				current_width = $("#alertbox").css("width");
				current_width = current_width.substring(0, current_width.length - 2);
				current_width = parseInt(current_width, 10);
				current_width = -1 * ((current_width + 40) / 2);
				return current_width;
			}),
			backgroundColor: thisColor,
			color: thisTextColor
		}).animate({
			top: "+=42"
		}, 1000, function()
		{
			setTimeout(function()
			{
				$("#alertbox").animate({
					top: "-=42"
				}, 1000, function()
				{
					instanceQueue.shift();
					if(instanceQueue.length > 0)
					{	
						startAlert();
					}
				});
			}, 3000)
		})
	}
}		

function interpretServerMessage( payload )
{
	var message = JSON.parse(payload);
	switch(message.action)
	{
		case "log":
			log("Log: " + message.message);
			break;
			
		case "chat":
			log(message.message, "black");
			break;
			
		case "alert":
			createAlert(message.message);
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
					
				case "allgamedetails":
					allOpenGames = message.data;
					log(printObject(allOpenGames), "green");
					
					for (var g in allOpenGames)
					{	
						game = allOpenGames[g];
												
						var newHtml = $(".gamedetailstemplate").clone();						
						$(newHtml).children(".gamenamecontainer").html("Name: " + game.name);
						$(newHtml).children(".gamestatuscontainer").html("Status: " + "changeme");
						
						if(game.team1player1)
						{
							$(newHtml).children(".playerlist").append("<li>" + game.team1player1 + "</li>");
						}
						
						if(game.team1player2)
						{
							$(newHtml).children(".playerlist").append("<li>" + game.team1player2 + "</li>");
						}
						
						if(game.team2player1)
						{
							$(newHtml).children(".playerlist").append("<li>" + game.team2player1 + "</li>");
						}
						
						if(game.team2player2)
						{
							$(newHtml).children(".playerlist").append("<li>" + game.team2player2 + "</li>");
						}
						
						if(game.rookvalue === "10.5")
						{
							$(newHtml).children(".rulelist").append("<li>" + "The Rook's card value is 10.5" + "</li>");
						}
						else if(game.rookvalue === "4")
						{
							$(newHtml).children(".rulelist").append("<li>" + "The Rook is low" + "</li>");
						}
						else if(game.rookvalue === "16")
						{
							$(newHtml).children(".rulelist").append("<li>" + "The Rook is high" + "</li>");
						}
						
						if(game.norookonfirsttrick === "true")
						{
							$(newHtml).children(".rulelist").append("<li>" + "The Rook cannot be played in the first trick" + "</li>");
						}
						else
						{
							$(newHtml).children(".rulelist").append("<li>" + "The Rook can be played in the first trick" + "</li>");
						}
						
						if(game.trumpbeforekitty === "true")
						{
							$(newHtml).children(".rulelist").append("<li>" + "Trump is called before the kitty is viewed" + "</li>");
						}
						else
						{
							$(newHtml).children(".rulelist").append("<li>" + "Trump is called after the kitty is viewed" + "</li>");
						}
						
						if(game.playto)
						{
							$(newHtml).children(".rulelist").append("<li>" + "The game is played to " + game.playto + " points"+ "</li>");
						}
						
						$(newHtml).attr("id", game.id);
						
						$(newHtml).css("display", "");
						
						newJoinButton = $("<div>Join this game</div>");
						$(newJoinButton).attr( "onclick", "$('#joingamedialog').data('gameid', '" + game.id +"').dialog('open')").css("font-size", ".8em").button();						
						
						$(newHtml).append(newJoinButton);
						$(newHtml).append("<hr />");
						
						$(newHtml).removeClass("gamedetailstemplate");
						
						$("#gamedescription").append(newHtml);
					}
					
					break;
				case "joinsuccess":
					$("#joingamedialog").dialog("close");
					$("#gameaccordiancontainer").css("display", "none");
					$("#ingamecontainer").css("display", "");
			}
			break;
			
	}	
}

function printObject(o) {
  var out = '';
  for (var p in o) {
  	if (typeof o[p] === "object")
  	{
  		out += p + ': { ' + printObject(o[p]) + ' }, \n';
  	}
  	else
  	{
  		out += p + ': ' + o[p] + '\n';	
  	}    
  }
  return(out);
}


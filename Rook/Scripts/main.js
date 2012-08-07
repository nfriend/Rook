var Server;
window.permission = true;
var instanceQueue = [];
var playername = "Player";
var allOpenGames = {};
var currentGameId = -1;
var hand = [];
var myPlayerNumber = 0;
var allowedSuits = [];
var numberOfCardsInTrick = 0;

$(init);

String.prototype.getSuitOrdinal = function() {
    if (this == "black") return 0;
    if (this == "yellow") return 1;
    if (this == "red") return 2;
    if (this == "green") return 3;
    if (this == "rook") return 4;
}

var sortMethod = function compare(a, b) {
    a_suit = a.suit.getSuitOrdinal();
    b_suit = b.suit.getSuitOrdinal();

    if (a_suit === b_suit) {
        if (a.number == "1")
        	return 1;
    	if (b.number == "1")
        	return -1;
        
        return a.number - b.number;
    }
    else {
        return a_suit - b_suit;
    }
}

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
		}, 500, function()
		{
			setTimeout(function()
			{
				$("#alertbox").animate({
					top: "-=42"
				}, 500, function()
				{
					instanceQueue.shift();
					if(instanceQueue.length > 0)
					{	
						startAlert();
					}
				});
			}, 2500)
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
			
			log(printObject(message), "green");
			
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
						addGame(g);
					}
					
					break;
					
				case "joinsuccess":
					$("#joingamedialog").dialog("close");
					
					var thisGame = allOpenGames[$("#joingamedialog").data("gameid")];
					
					currentGameId = thisGame.id;
					
					$("#gameaccordiancontainer").css("display", "none");
										
					changeInGameDetails(thisGame);
					
					$("#ingamecontainer").css("display", "");
					break;
					
				case "leavesuccess":
					currentGameId = -1;
					$("#leavegameconfirmationdialog").dialog("close");
					$("#ingamecontainer").css("display", "none");
					$("#gameaccordiancontainer").css("display", "");
					break;			
					
				case "createsuccess":					
					$("#creategamedialog").dialog("close");
					
					var thisGame;
					
					for(i = 0; i < allOpenGames.length; i++)
					{
						if(allOpenGames[i].id === message.data)
						{
							thisGame = allOpenGames[i];
							break;
						}
					}				
					
					currentGameId = thisGame.id;	
					
					$("#gameaccordiancontainer").css("display", "none");
					
					changeInGameDetails(thisGame);
					
					$("#ingamecontainer").css("display", "");
					
					break;
				
				case "addgame":
					addGame(null, message.data);					
					allOpenGames.push(message.data);
					break;
				
				case "deletegame":					
					var thisGame;					
					for(i = 0; i < allOpenGames.length; i++)
					{
						if(allOpenGames[i].id === message.data)
						{
							thisGame = allOpenGames[i];
							break;
						}
					}
					
					$("#gamenumber" + thisGame.id).remove();
					
					delete(thisGame);
					
					break;
					
				case "updategame":
					
					var thisGame;
					for(i = 0; i < allOpenGames.length; i++)
					{
						if(allOpenGames[i].id === message.data.gameid)
						{
							thisGame = allOpenGames[i];
							break;
						}
					}
					
					thisDiv = $("#gamenumber" + thisGame.id).children(".playerlist");
					thisDiv.html("");					
					
					if(message.data.team1player1)
					{
						thisDiv.append("<li>" + message.data.team1player1 + "</li>");
						thisGame.team1player1 = message.data.team1player1;						
					} 
					else
					{
						thisGame.team1player1 = null;
					}
					
					if(message.data.team1player2)
					{
						thisDiv.append("<li>" + message.data.team1player2 + "</li>");
						thisGame.team1player2 = message.data.team1player2;						
					}
					else
					{
						thisGame.team1player2 = null;	
					}
					
					if(message.data.team2player1)
					{
						thisDiv.append("<li>" + message.data.team2player1 + "</li>");
						thisGame.team2player1 = message.data.team2player1;						
					}
					else
					{
						thisGame.team2player1 = null;	
					}
					
					if(message.data.team2player2)
					{
						thisDiv.append("<li>" + message.data.team2player2 + "</li>");
						thisGame.team2player2 = message.data.team2player2;						
					}
					else
					{
						thisGame.team2player2 = null;	
					}
					
					if(thisGame.id === currentGameId)
					{
						thisGame.status = "Waiting for 4 players";
						changeInGameDetails(thisGame);
						
						$("#confirmbegingamedialog").dialog("close");						
					}
					
					break;
					
				case "gamefull":					
					
					allOpenGames[currentGameId].status = "Waiting for all players to confirm";
					
					$("#ingamecontainer .gamestatuscontainer").html(allOpenGames[currentGameId].status);
					
					if (currentGameId !== -1)
					{
						$("#confirmbegingamedialog").dialog("open");
					}
					
					break;
					
				case "begingame":					
					$(".ui-dialog-content").dialog("close");
					$("#lobby").css("display", "none");
					$("#gametable").css("display", "");
					
					$("#bottomnamecontainer").html(message.data[0]);
					$("#leftnamecontainer").html(message.data[1]);
					
					$("#leftnamecontainer").css({
						marginLeft: (($("#leftnamecontainer").width() * -1) / 2) + 100
					});
					
					$("#topnamecontainer").html(message.data[2]);
					$("#rightnamecontainer").html(message.data[3]);
					
					$("#rightnamecontainer").css({
						marginRight: (($("#rightnamecontainer").width() * -1) / 2) + 30
					});
					
					break;
					
				case "initializecards":
					initializeCards(message);
					$("#newsfeed").css("display", "").html("Waiting for the first player to begin bidding...");															
					break;
					
				case "yourbid":
					$("#bidcontainer").css("display", "");					
					$("#newsfeed").css("display", "none");
					$("#currentbidcontainer").html(message.data.highestbidder + " currently has the bid at " + message.data.bid);				
					break;
					
				case "notyourbid":
					$("#bidcontainer").css("display", "none");
					$("#currentbidcontainer").html(message.data.highestbidder + " currently has the bid at " + message.data.bid);
					break;
					
				case "newsfeed":
					$("#newsfeed").css("display", "").html(message.data);
					$("#bidcontainer").css("display", "none");
					break;
					
				case "waitforkitty":
					$("#currentbidcontainer").css("display", "none");
					$("#bidcontainer").css("display", "none");
					$("#newsfeed").css("display", "").html(message.data.bidwinner + " has won the bid at " + message.data.bid + ".  The game will begin when " + message.data.bidwinner + " has finished with the kitty.");
					break;
				
				case "kitty":
					$("#currentbidcontainer").css("display", "none");
					$("#bidcontainer").css("display", "none");
					$("#newsfeed").css("display", "").html("You won the bid! The kitty has been added to your hand.  Select five cards to place back into the kitty.");
					$("#submitkitty").css("display", "");
					$("#trumpselector").css("display", "");
					
					initializeCards(message);
					
					$(".card").click( function(event) 
					{	
						chosenCount = 0;
						
						$(".card").each( function()
						{	
							if ($(this).data("chosenforkitty") === "true")
							{
								chosenCount++;
							}						
						})
							
						if($(event.target).data("chosenforkitty") !== "true")
						{
							if (chosenCount < 5)
							{
								$(event.target).data("chosenforkitty", "true");
								$(event.target).css("bottom", "-=30px");
								
								if (chosenCount === 4)
								{
									$("#submitkitty").button("enable");
								}
								else
								{
									$("#submitkitty").button("disable");
								}	
							}							 
							else
							{
								createAlert("You can only place 5 cards in the kitty");
								
							}						
						}
						else
						{
							$(event.target).data("chosenforkitty", "false");
							$(event.target).css("bottom", "+=30px")
							
							$("#submitkitty").button("disable");
						}						
					});
					
					break;
					
				case "setplayernumber":
					myPlayerNumber = parseInt(message.data, 10);
					break;
					
				case "waitingon":
					moveFocus(parseInt(message.data, 10));
					break;
					
				case "beginlay":
					if (myPlayerNumber == message.playernumber)
					{
						$("#faketarget").css("display", "");
						hand = [];
						allowedSuits = ["black", "yellow", "green", "red", "rook"];
						initializeCards(message)
					}
					
					$("#trumpselector").add("#submitkitty").css("display", "none");
					
					$(".card").unbind('click');
					makeCardsDraggable();
					break;
					
				case "cardlaid":
					ordinal = parseInt(message.data.player, 10) - myPlayerNumber;
					
					if (ordinal < 0)
					{
						ordinal = 4 + ordinal;	
					}
					
					if (ordinal === 1)
						animateP1CardPlay(message.data.suit, message.data.number, parseInt(message.data.numberofcardsintrick, 10));
					if (ordinal === 2)
						animateP2CardPlay(message.data.suit, message.data.number, parseInt(message.data.numberofcardsintrick, 10));
					if (ordinal === 3)
						animateP3CardPlay(message.data.suit, message.data.number, parseInt(message.data.numberofcardsintrick, 10));
					
					break;
					
				// also signals that it's this player's turn
				case "setallowedsuits":
					allowedSuits = [];
					for(var p in message.data)
					{
						suit = message.data[p]; 
						
						allowedSuits.push(suit);						
					}
					
					numberOfCardsInTrick = message.numberofcardsintrick
					
					setTimeout(function() 
					{
						$("#faketarget").css('display', '').css("z-index", parseInt(message.numberofcardsintrick, 10)*2 + 51);
					}, 800);
					break;
				case "trickdone":
					numberOfCardsInTrick = 0;
					moveFocus(parseInt(message.data, 10));
					
					ordinal = parseInt(message.data, 10) - myPlayerNumber;
					
					if (ordinal < 0)
					{
						ordinal = 4 + ordinal;	
					}
					
					if (ordinal === 0)
						setTimeout(animateP0TrickWin, 2000)
					if (ordinal === 1)
						setTimeout(animateP1TrickWin, 2000)
					if (ordinal === 2)
						setTimeout(animateP2TrickWin, 2000)
					if (ordinal === 3)
						setTimeout(animateP3TrickWin, 2000)
					
					break;
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
  		out += p + ': ' + o[p] + ', \n';	
  	}    
  }
  return(out);
}

function addGame(g, details)
{
	if(details)
	{
		game = details;
	}
	else
	{
		game = allOpenGames[g];	
	}
												
	var newHtml = $(".gamedetailstemplate").clone();						
	$(newHtml).children(".gamenamecontainer").html("Name: " + game.name);
	$(newHtml).children(".gamestatuscontainer").html("Status: " + game.status);
	
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
	
	$(newHtml).attr("id", "gamenumber" + game.id);
	
	$(newHtml).css("display", "");
	
	newJoinButton = $("<div>Join this game</div>");
	$(newJoinButton).attr( "onclick", "$('#joingamedialog').data('gameid', '" + game.id +"').dialog('open')").css("font-size", ".8em").button();						
	
	$(newHtml).append(newJoinButton);
	$(newHtml).append("<hr />");
	
	$(newHtml).removeClass("gamedetailstemplate");
	
	$("#gamedescription").append(newHtml);
}

function changeInGameDetails(thisGame)
{
	$("#gametitlediv").html("You are in game '" + thisGame.name + "'");
	$("#gamedetails .gamestatuscontainer").html("Status: " + thisGame.status)
	
	thisDiv = $("#gamedetails .playerlist");
	
	thisDiv.html("");
	
	if(thisGame.team1player1)
	{
		thisDiv.append("<li>" + thisGame.team1player1 + "</li>");						
	}
	
	if(thisGame.team1player2)
	{
		thisDiv.append("<li>" + thisGame.team1player2 + "</li>");						
	}
	
	if(thisGame.team2player1)
	{
		thisDiv.append("<li>" + thisGame.team2player1 + "</li>");						
	}
	
	if(thisGame.team2player2)
	{
		thisDiv.append("<li>" + thisGame.team2player2 + "</li>");						
	}
	
	ruleUl = $("#gamedetails .rulelist");
	ruleUl.html("");
	
	if(thisGame.rookvalue === "10.5")
	{
		ruleUl.append("<li>" + "The Rook's card value is 10.5" + "</li>");
	}
	else if(thisGame.rookvalue === "4")
	{
		ruleUl.append("<li>" + "The Rook is low" + "</li>");
	}
	else if(thisGame.rookvalue === "16")
	{
		ruleUl.append("<li>" + "The Rook is high" + "</li>");
	}
	
	if(thisGame.norookonfirsttrick === "true")
	{
		ruleUl.append("<li>" + "The Rook cannot be played in the first trick" + "</li>");
	}
	else
	{
		ruleUl.append("<li>" + "The Rook can be played in the first trick" + "</li>");
	}
	
	if(thisGame.trumpbeforekitty === "true")
	{
		ruleUl.append("<li>" + "Trump is called before the kitty is viewed" + "</li>");
	}
	else
	{
		ruleUl.append("<li>" + "Trump is called after the kitty is viewed" + "</li>");
	}
	
	if(thisGame.playto)
	{
		ruleUl.append("<li>" + "The game is played to " + thisGame.playto + " points"+ "</li>");
	}
}

function spaceCards()
{
	var cards = $("#cardscontainer img");
	var notDroppedArray = [];
	
	cards.each(function() {
		if(!($(this).attr("dropped") === "true"))
		{
			notDroppedArray.push($(this));			
		}		
	});
	count = notDroppedArray.length;
	
	for(i = 0; i < count; i++)
	{
		notDroppedArray[i].animate({
			left: (i+1)*(750/(count + 1)) + "px"
		});		
	}
}

function initializeCards(message)
{
	$("#cardscontainer").html("");
	
	for(var p in message.data)
	{
		card = message.data[p]; 
		
		hand.push({
			suit: card.suit,
			number: card.number
		});						
	}
	
	hand.sort(sortMethod);
	
	for(i = 0; i < hand.length; i++)
	{						
		card = hand[i];
		
		if(card.suit == "rook")
		{
			var cardToAdd = $('<img class="card" dropped="false" src="Images/cards/rook.jpg" />');
			cardToAdd.data('suit', 'rook').data('number', 10.5);
		}
		else
		{
			var cardToAdd = $('<img class="card" dropped="false" src="Images/cards/' + card.suit + card.number + '.jpg" />');
			cardToAdd.data('suit', card.suit).data('number', card.number);
		}
		
		$("#cardscontainer").append(cardToAdd);
	}	
    
    spaceCards();
}

function makeCardsDraggable()
{
	$("#cardscontainer img").draggable({
        revert: function (valid)
        {
            if (!valid)
            // if the card is dropped in a non-valid location
            {
                $(this).attr("z-index", "10");
                return true;                
            }
            else
            {
                if ($(this).attr("dropped") === 'false')
                {                            
                    $(this).attr("z-index", "10");
                    createAlert("You must follow suit");
                    return true;
                }
                return false;
            }
        },
        start: function(event, ui)
        {
        	$(this).css("z-index", (numberOfCardsInTrick + 1)*2 + 50)
        }        
    }).attr("dropped", "false").css("zIndex", 10);
}

function moveFocus(number)
{
	ordinal = number - myPlayerNumber;
	
	if (ordinal < 0)
	{
		ordinal = 4 + ordinal;	
	}

	if(ordinal === 0)
	{
		$("#bottompulsatinggreen").css("display", "");
		$("#leftpulsatinggreen").css("display", "none");
		$("#toppulsatinggreen").css("display", "none");
		$("#rightpulsatinggreen").css("display", "none");
		return
	}
	
	if(ordinal === 1)
	{
		$("#bottompulsatinggreen").css("display", "none");
		$("#leftpulsatinggreen").css("display", "");
		$("#toppulsatinggreen").css("display", "none");
		$("#rightpulsatinggreen").css("display", "none");
		return
	}
	
	if(ordinal === 2)
	{
		$("#bottompulsatinggreen").css("display", "none");
		$("#leftpulsatinggreen").css("display", "none");
		$("#toppulsatinggreen").css("display", "");
		$("#rightpulsatinggreen").css("display", "none");
		return
	}
	
	if(ordinal === 3)
	{
		$("#bottompulsatinggreen").css("display", "none");
		$("#leftpulsatinggreen").css("display", "none");
		$("#toppulsatinggreen").css("display", "none");
		$("#rightpulsatinggreen").css("display", "");
		return
	}
} 

function animateP1CardPlay(suit, number, zindex)
{
	if (suit !== "rook")
	{
		cardPath = suit + number;
	}
	else
	{
		cardPath = "rook";
	}
	
	newCard = $("<img class='played' src='Images/cards/" + cardPath + ".jpg' style='position: absolute; left: -200px; top: 50%; z-index:" + ((zindex*2) + 50) + "'/>");
	$("#gametable").append(newCard);
	
	offset = $('#target').offset();
	
	$("#leftcardscontainer :last").remove();
	
	i = 0;
	count = $("#leftcardscontainer").children().size();
	
	$("#leftcardscontainer").children().each( function() {
		i++;
		$(this).animate({
			top: i * (400/count)
		})		
	})
	
	
	$(newCard).animate({
		left: offset.left - 80,
		top: offset.top - 50
	});
	
}

function animateP2CardPlay(suit, number, zindex)
{
	if (suit !== "rook")
	{
		cardPath = suit + number;
	}
	else
	{
		cardPath = "rook";
	}
	
	newCard = $("<img class='played' src='Images/cards/" + cardPath + ".jpg' style='position: absolute; top: -200px; z-index:" + ((zindex*2) + 50) + "'/>");
	$("#gametable").append(newCard);
	
	offset = $('#target').offset();
	
	$("#topcardscontainer :first").remove();
	
	i = 0;
	count = $("#topcardscontainer").children().size();
	
	$("#topcardscontainer").children().each( function() {
		i++;
		$(this).animate({
			left: i * (700/count)
		})		
	})
	
	$(newCard).animate({
		left: offset.left + 10,
		top: offset.top - 100
	});
}


function animateP3CardPlay(suit, number, zindex)
{
	if (suit !== "rook")
	{
		cardPath = suit + number;
	}
	else
	{
		cardPath = "rook";
	}
	
	newCard = $("<img class='played' src='Images/cards/" + cardPath + ".jpg' style='position: absolute; right: -200px; top: 50%; z-index:" + ((zindex*2) + 50) + "'/>");
	$("#gametable").append(newCard);
	
	offset = $('#target').offset();
	
	$("#rightcardscontainer :last").remove();
	
	i = 0;
	count = $("#rightcardscontainer").children().size();
	
	$("#rightcardscontainer").children().each( function() {
		i++;
		$(this).animate({
			top: i * (400/count)
		})		
	})
	
	
	$(newCard).animate({
		left: offset.left + 80,
		top: offset.top - 50
	});
	
}

function animateP0TrickWin()
{
	$(".played").each( function()
	{
		height = $(window).height();
		offset = $('#target').offset();
		
		$(this).animate({
			top: height + "px",
			left: offset.left
		}, 1000, function ()
		{
			$(".played").each( function()
			{
				$(this).remove();
			})
		});
	});
}

function animateP1TrickWin()
{
	$(".played").each( function()
	{
		offset = $('#target').offset();
		
		$(this).animate({
			top: offset.top,
			left: "-140px"
		}, 1000, function ()
		{
			$(".played").each( function()
			{
				$(this).remove();
			})
		});
	});
}

function animateP2TrickWin()
{
	$(".played").each( function()
	{
		offset = $('#target').offset();
		
		$(this).animate({
			top: "-180px",
			left: offset.left
		}, 1000, function ()
		{
			$(".played").each( function()
			{
				$(this).remove();
			})
		});
	});
}

function animateP3TrickWin()
{
	$(".played").each( function()
	{
		width = $(window).width();
		offset = $('#target').offset();
		
		$(this).animate({
			top: offset.top,
			left: width + "px"
		}, 1000, function ()
		{
			$(".played").each( function()
			{
				$(this).remove();
			})
		});
	});
}

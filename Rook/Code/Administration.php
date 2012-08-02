<?php

function getClientGame($clientID)
{
	global $gameArray;
	
	$clientGameId = -1;
	$clientTeamNumber = -1;
	$clientPlayerNumber = -1;
	$clientName = "";
	$game = null;
	$team = null;
	$player = null;
	
	foreach($gameArray as $g)
	{
		if ($g && $g->Team1 && $g->Team1->Player1 && $g->Team1->Player1->ClientId === $clientID)
		{
			$clientGameId = $g->Id;
			$clientTeamNumber = 1;
			$clientPlayerNumber = 1;
			$clientName = $g->Team1->Player1->Name;	
			$game = $g;
			$team = $game->Team1;
			$player = $game->Team1->Player1;			
			break;
		}
		
		if ($g && $g->Team1 && $g->Team1->Player2 && $g->Team1->Player2->ClientId === $clientID)
		{
			$clientGameId = $g->Id;
			$clientTeamNumber = 1;
			$clientPlayerNumber = 2;
			$clientName = $g->Team1->Player2->Name;
			$game = $g;
			$team = $game->Team1;
			$player = $game->Team1->Player2;
			break;
		}
		
		if ($g && $g->Team2 && $g->Team2->Player1 && $g->Team2->Player1->ClientId === $clientID)
		{
			$clientGameId = $g->Id;
			$clientTeamNumber = 2;
			$clientPlayerNumber = 1;
			$clientName = $g->Team2->Player1->Name;
			$game = $g;
			$team = $game->Team2;
			$player = $game->Team2->Player1;
			break;
		}
		
		if ($g && $g->Team2 && $g->Team2->Player2 && $g->Team2->Player2->ClientId === $clientID)
		{
			$clientGameId = $g->Id;
			$clientTeamNumber = 2;
			$clientPlayerNumber = 2;
			$clientName = $g->Team2->Player2->Name;
			$game = $g;
			$team = $game->Team2;
			$player = $game->Team2->Player2;
			break;
		}		
	}
	
	$responseObject = array(
		"gameId" => $clientGameId,
		"clientId" => $clientID,
		"teamNumber" => $clientTeamNumber,
		"playerNumber" => $clientPlayerNumber,
		"playerName" => $clientName,
		"game" => $game,
		"team" => $team,
		"player" => $player		
	);
		
	return $responseObject;
}

function addGame($clientID, $gameDetails)
{
	global $Server, $gameArray;
	
	$clientGameInfo = getClientGame($clientID);
	$clientGameId = $clientGameInfo["gameId"];
	$clientTeamNumber = $clientGameInfo["teamNumber"];
	$clientPlayerNumber = $clientGameInfo["playerNumber"];
	
	if ($clientGameId === -1)
	{
		$newGame = new Game();					
		$newGame->Team1->AddPlayer($clientID);	
		
		if ($gameDetails["rookvalue"] === "10.5")
		{
			$newGame->Rules->RookValue = 10.5;
		} 
		else if ($gameDetails["rookvalue"] === "low")
		{
			$newGame->Rules->RookValue = 4;
		} 
		else if ($gameDetails["rookvalue"] === "high")
		{
			$newGame->Rules->RookValue = 16;
		}
		
		if ($gameDetails["rookfirsttrick"] === "yes")
		{
			$newGame->Rules->NoRookOnFirstTrick = false;
		}
		else if ($gameDetails["rookfirsttrick"] === "no")
		{
			$newGame->Rules->NoRookOnFirstTrick = true;
		}
		
		if ($gameDetails["trumpbeforekitty"] === "yes")
		{
			$newGame->Rules->TrumpBeforeKitty = true;
		}
		else if ($gameDetails["trumpbeforekitty"] === "no")
		{
			$newGame->Rules->TrumpBeforeKitty = false;
		}
		
		if ($gameDetails["playto"] === "single")
		{
			$newGame->Rules->PlayTo = "single";
		}
		else if ($gameDetails["playto"] === "100")
		{
			$newGame->Rules->PlayTo = 100;
		}
		else if ($gameDetails["playto"] === "300")
		{
			$newGame->Rules->PlayTo = 300;
		}
		else if ($gameDetails["playto"] === "500")
		{
			$newGame->Rules->PlayTo = 500;
		}
		
		
		$gameArray[] = $newGame;
		$ordinal = array_search($newGame, $gameArray);
		$gameArray[$ordinal]->Id = $ordinal;
		
		if(is_null($gameDetails["name"]) || $gameDetails["name"] === "")
		{
			$gameArray[$ordinal]->Name = "Game " . (string) $ordinal; 
		}
		else
		{
			$gameArray[$ordinal]->Name = $gameDetails["name"];
		}
				
		$rules = $gameArray[$ordinal]->Rules;
		$TrumpBeforeKitty = $rules->TrumpBeforeKitty ? "true" : "false";
		$NoRookOnFirstTrick = $rules->NoRookOnFirstTrick ? "true" : "false";
		
		$team1player1name = "";
		$team1player2name = "";
		$team2player1name = "";
		$team2player2name = "";		
		
		if($gameArray[$ordinal] && $gameArray[$ordinal]->Team1 && $gameArray[$ordinal]->Team1->Player1) 
			$team1player1name = $gameArray[$ordinal]->Team1->Player1->Name;
		
		if($gameArray[$ordinal] && $gameArray[$ordinal]->Team1 && $gameArray[$ordinal]->Team1->Player2) 
			$team1player1name = $gameArray[$ordinal]->Team1->Player2->Name;
		
		if($gameArray[$ordinal] && $gameArray[$ordinal]->Team2 && $gameArray[$ordinal]->Team2->Player1) 
			$team1player1name = $gameArray[$ordinal]->Team2->Player1->Name;
			
		if($gameArray[$ordinal] && $gameArray[$ordinal]->Team2 && $gameArray[$ordinal]->Team2->Player2) 
			$team1player1name = $gameArray[$ordinal]->Team2->Player2->Name;
		
			
		$gameDetails = array(
			"name"=>(string)($gameArray[$ordinal]->Name),
			"id"=>(string)($gameArray[$ordinal]->Id),
			"status"=>"Waiting for 4 players",
			"team1player1"=>(string)($team1player1name),
			"team1player2"=>(string)($team1player2name),
			"team2player1"=>(string)($team2player1name),
			"team2player2"=>(string)($team2player2name),
			"rookvalue"=>(string)($rules->RookValue),
			"playto"=>(string)($rules->PlayTo),
			"trumpbeforekitty"=>$TrumpBeforeKitty,
			"norookonfirsttrick"=>$NoRookOnFirstTrick
		);
		
		foreach ( $Server->wsClients as $id => $client )
		{							
			$response = array(
				"action"=>"command", 
				"message"=> "addgame",
				"data"=>$gameDetails					
			);	
				
			sendJson($id, $response);			
		}
		
		$response = array(
			"action"=>"command", 
			"message"=> "createsuccess",
			"data"=>$gameDetails["id"]
		);		
		
		sendJson($clientID, $response);
	}
	else 
	{
		$response = array(
			"action"=>"log", 
			"message"=> "Player " . (string) $clientID . " is already in game " . (string) $clientGameId
		);
		
		sendJson($clientID, $response);
	}
}

function leaveGame($clientID)
{
	global $gameArray, $Server;	
		
	$clientGameInfo = getClientGame($clientID);
	$clientGameId = $clientGameInfo["gameId"];
	$clientTeamNumber = $clientGameInfo["teamNumber"];
	$clientPlayerNumber = $clientGameInfo["playerNumber"];
	
	$gameObject;
	
	if ($clientGameId === -1)
	{
		$response = array(
			"action"=>"alert", 
			"message"=> "You are not currently in a game"
		);
		
		sendJson($clientID, $response);
	}		
	else 
	{
		
		foreach($gameArray as $g)
		{
			if($g->Id === $clientGameId)
			{
				if($clientTeamNumber === 1)
				{
					if($clientPlayerNumber === 1)
					{
						$g->Team1->Player1 = null;
						$gameObject = $g;
						break;
					}
					else
					{
						$g->Team1->Player2 = null;
						$gameObject = $g;
						break;
					}
				}
				else
				{
					if($clientPlayerNumber === 1)
					{
						$g->Team2->Player1 = null;
						$gameObject = $g;
						break;
					}
					else
					{
						$g->Team2->Player2 = null;
						$gameObject = $g;
						break;
					}
				}
			}
		}	
						
		$response = array(
			"action"=>"command", 
			"message"=> "leavesuccess"
		);
		
		sendJson($clientID, $response);
		
		if (is_null($g->Team1->Player1) && is_null($g->Team1->Player2) && is_null($g->Team2->Player1) && is_null($g->Team2->Player2))
		{
			$ordinal = array_search($gameObject, $gameArray);		
			unset($gameArray[$ordinal]);
			
			foreach ( $Server->wsClients as $id => $client )
			{
				$response = array(
					"action"=>"command", 
					"message"=> "deletegame",
					"data"=>(string)($g->Id)
				);	
					
				sendJson($id, $response);				
			}
		} else {
				
			$team1player1name = "";
			$team1player2name = "";
			$team2player1name = "";
			$team2player2name = "";		
			
			if($g && $g->Team1 && $g->Team1->Player1) 
				$team1player1name = $g->Team1->Player1->Name;
			
			if($g && $g->Team1 && $g->Team1->Player2) 
				$team1player2name = $g->Team1->Player2->Name;
			
			if($g && $g->Team2 && $g->Team2->Player1) 
				$team2player1name = $g->Team2->Player1->Name;
				
			if($g && $g->Team2 && $g->Team2->Player2) 
				$team2player2name = $g->Team2->Player2->Name;	
				
			foreach ( $Server->wsClients as $id => $client )
			{
				$response = array(
					"action"=>"command", 
					"message"=> "updategame",
					"data"=>array(
						"gameid"=>(string)($g->Id),
						"team1player1"=> (string)($team1player1name),
						"team1player2"=> (string)($team1player2name),
						"team2player1"=> (string)($team2player1name),
						"team2player2"=> (string)($team2player2name)						
					)
				);	
					
				sendJson($id, $response);			
			}
			
			if(!is_null($g->Team1->Player1))
			{
				$g->Team1->Player1->Confirmed = false;
			}
			
			if(!is_null($g->Team1->Player2))
			{
				$g->Team1->Player2->Confirmed = false;
			}
			
			if(!is_null($g->Team2->Player1))
			{
				$g->Team2->Player1->Confirmed = false;
			}
			
			if(!is_null($g->Team2->Player2))
			{
				$g->Team2->Player2->Confirmed = false;
			}
			
		}
	}
}


function joinGame($clientID, $gameNumber, $teamNumber)
{
	global $gameArray, $Server;
		
	$clientGameInfo = getClientGame($clientID);
	$clientGameId = $clientGameInfo["gameId"];
	$clientTeamNumber = $clientGameInfo["teamNumber"];
	$clientPlayerNumber = $clientGameInfo["playerNumber"];
	
	if($clientGameId === -1)
	{
		$requestedGameDoesExist = true;
			
		foreach($gameArray as $g)
		{				
			if($gameNumber === $g->Id)
			{					
				$requestedGameDoesExist = false;
					
				if($teamNumber === 1)
				{
					$success = $g->Team1->AddPlayer($clientID);
					if(!$success)
					{
						$response = array(
							"action"=>"alert", 
							"message"=> "Player " . (string) $clientID . " is unable to join Game " . (string) $gameNumber . " on Team " . (string) $teamNumber . " because the team is full."
						);
						
						sendJson($clientID, $response);
						return;			
					}
					break;
				}
				elseif($teamNumber === 2) 
				{
					$success = $g->Team2->AddPlayer($clientID);
					if(!$success)
					{
						$response = array(
							"action"=>"alert", 
							"message"=> "Player " . (string) $clientID . " is unable to join Game " . (string) $gameNumber . " on Team " . (string) $teamNumber . " because the team is full."
						);
						
						sendJson($clientID, $response);
						return;			
					}
					break;
				}
			}
		}
		
		if($requestedGameDoesExist)
		{
			$response = array(
				"action"=>"alert", 
				"message"=> "Game " . (string) $gameNumber . " does not exist"
			);
			
			sendJson($clientID, $response);	
			return;
		}
		
		foreach ( $Server->wsClients as $id => $client )
		{
			
			$team1player1name = "";
			$team1player2name = "";
			$team2player1name = "";
			$team2player2name = "";		
			
			if($g && $g->Team1 && $g->Team1->Player1) 
				$team1player1name = $g->Team1->Player1->Name;
			
			if($g && $g->Team1 && $g->Team1->Player2) 
				$team1player2name = $g->Team1->Player2->Name;
			
			if($g && $g->Team2 && $g->Team2->Player1) 
				$team2player1name = $g->Team2->Player1->Name;
				
			if($g && $g->Team2 && $g->Team2->Player2) 
				$team2player2name = $g->Team2->Player2->Name;	
				
			foreach ( $Server->wsClients as $id => $client )	
											
			$response = array(
				"action"=>"command", 
				"message"=> "updategame",
				"data"=>array(
					"gameid"=>(string)($g->Id),
					"team1player1"=> (string)($team1player1name),
					"team1player2"=> (string)($team1player2name),
					"team2player1"=> (string)($team2player1name),
					"team2player2"=> (string)($team2player2name)						
				)
			);	
					
			sendJson($id, $response);
		}
		
		$response = array(
			"action"=>"command", 
			"message"=> "joinsuccess"
		);
		
		sendJson($clientID, $response);
		
		checkForFullGame($gameNumber, $clientID);
		
		return;
	}
	else 
	{		
		$response = array(
			"action"=>"alert", 
			"message"=> "You are already in game " . (string) $clientGameId . " on Team " . (string) $clientTeamNumber
		);
		
		sendJson($clientID, $response);
	}
}

function chat($clientID, $message)
{
	global $Server, $wsClientNames;
	
	$ip = long2ip( $Server->wsClients[$clientID][6] );	
		
	$clientGameInfo = getClientGame($clientID);
	$clientGameId = $clientGameInfo["gameId"];
	$clientTeamNumber = $clientGameInfo["teamNumber"];
	$clientPlayerNumber = $clientGameInfo["playerNumber"];
	
	if (is_null($wsClientNames[$clientID]))
	{
		$clientName = "Player " . (string)$clientID;
	}
	else
	{
		$clientName = $wsClientNames[$clientID];
	}
	
	foreach ( $Server->wsClients as $id => $client )
		if ($id !== $clientID)
		{
			
			$response = array(
				"action"=>"chat", 
				"message"=> $clientName . ": " . $message
			);	
				
			sendJson($id, $response);
		}
}

function beginGame($thisGame)
{
	$thisGame->State->NextAction = "Team1Player1Bid";	
	
	array_push($thisGame->Rounds, new Round());
				
	$gamePlayers = array(
		"p1"=>$thisGame->Team1->Player1->ClientId,
		"p2"=>$thisGame->Team1->Player2->ClientId,
		"p3"=>$thisGame->Team2->Player1->ClientId,
		"p4"=>$thisGame->Team2->Player2->ClientId		
	);
	
	foreach($gamePlayers as $id)
	{
		$response = array(
			"action"=>"log", 
			"message"=> "Beginning game " . (string)$thisGame->Id
		);	
			
		sendJson($id, $response);
		
		$response = array(
			"action"=>"command", 
			"message"=> "losepermission"
		);	
			
		sendJson($id, $response);	
	}
	
	deal($thisGame);
	
	$p1Cards = "";
	$p2Cards = "";
	$p3Cards = "";
	$p4Cards = "";
	$kittyCards = "";
	
	foreach($thisGame->Team1->Player1->Hand as $card)
	{
		$p1Cards = $p1Cards . $card->toString() . ", ";
	}
	foreach($thisGame->Team1->Player2->Hand as $card)
	{
		$p2Cards = $p2Cards . $card->toString() . ", ";
	}
	foreach($thisGame->Team2->Player1->Hand as $card)
	{
		$p3Cards = $p3Cards . $card->toString() . ", ";
	}
	foreach($thisGame->Team2->Player2->Hand as $card)
	{
		$p4Cards = $p4Cards . $card->toString() . ", ";
	}
	
	$round = end($thisGame->Rounds);
	foreach($round->Kitty as $card)
	{
		$kittyCards = $kittyCards . $card->toString() . ", ";
	}
	
	$response = array(
		"action"=> "log",
		"message"=> "Player 1's cards: " . $p1Cards
	);
	
	sendJson($thisGame->Team1->Player1->ClientId, $response);
	
	$response = array(
		"action"=> "log",
		"message"=> "Player 2's cards: " . $p2Cards
	);
	
	sendJson($thisGame->Team1->Player2->ClientId, $response);
	
	$response = array(
		"action"=> "log",
		"message"=> "Player 1's cards: " . $p3Cards
	);
	
	sendJson($thisGame->Team2->Player1->ClientId, $response);
	
	$response = array(
		"action"=> "log",
		"message"=> "Player 2's cards: " . $p4Cards
	);
	
	sendJson($thisGame->Team2->Player2->ClientId, $response);
	
	foreach($gamePlayers as $id)
	{
		$response = array(
			"action"=>"log", 
			"message"=> "Cards in the Kitty: " . $kittyCards
		);	
			
		sendJson($id, $response);	
	}
	
	$response = array(
		"action"=>"command",
		"message"=>"gainpermission"
	);
	
	sendJson($gamePlayers["p1"], $response);
	
}

function confirmClient($clientID)
{
	$gameDetails = getClientGame($clientID);
	
	$gameDetails["player"]->Confirmed = true;
	
	checkForFullGame($gameDetails["gameId"], $clientID, false);
}

function checkForFullGame($gameId, $clientID, $sendGameFull = true)
{
	global $gameArray, $Server;
	
	$thisGame;	

	foreach($gameArray as $g)
	{
		if($g->Id === $gameId)
		{
			$thisGame = $g;
			break;
		}
	}
	
	if(!is_null($thisGame->Team1->Player1) && !is_null($thisGame->Team1->Player2) && !is_null($thisGame->Team2->Player1) && !is_null($thisGame->Team2->Player2))
	{
		$gamePlayers = array(
			$thisGame->Team1->Player1->ClientId,
			$thisGame->Team1->Player2->ClientId,
			$thisGame->Team2->Player1->ClientId,
			$thisGame->Team2->Player2->ClientId		
		);
			
		if($thisGame->Team1->Player1->Confirmed === true && $thisGame->Team1->Player2->Confirmed === true && $thisGame->Team2->Player1->Confirmed === true && $thisGame->Team2->Player2->Confirmed === true)
		{	
			if($thisGame->State->Location === "lobby")
			{
				$thisGame->State->Location === "table";	
			}		
			
			foreach($gamePlayers as $id)
			{
				$response = array(
					"action"=>"command", 
					"message"=> "begingame"
				);	
					
				sendJson($id, $response);	
			}
			
			foreach ( $Server->wsClients as $id => $client )
			{				
				$response = array(
					"action"=>"command", 
					"message"=> "deletegame",
					"data"=>(string)($g->Id)
				);	
						
				sendJson($id, $response);			
			}
			
			beginGame($thisGame);
		}
		else if($sendGameFull)
		{
			foreach($gamePlayers as $id)
			{
				$response = array(
					"action"=>"command", 
					"message"=> "gamefull"
				);	
					
				sendJson($id, $response);	
			}
		}
	}
	
}

function forwardCommand($clientID, $data)
{
	$clientGameInfo = getClientGame($clientID);
	$thisGame = $clientGameInfo["game"];
	
	$thisGame->processCommand($clientGameInfo, $data);
		
}

function sendAllOpenGames($clientID)
{
	global $gameArray;
	
	$openGames = array();
	$responseArray = array();
	
	foreach($gameArray as $game)
	{
		if(is_null($game->Team1->Player1) || is_null($game->Team1->Player2) || is_null($game->Team2->Player1) || is_null($game->Team2->Player2))
		{
			array_push($openGames, $game);
		}
	}
	
	foreach($openGames as $game)
	{
		$rules = $game->Rules;
		$TrumpBeforeKitty = $rules->TrumpBeforeKitty ? "true" : "false";
		$NoRookOnFirstTrick = $rules->NoRookOnFirstTrick ? "true" : "false";
			
		$gameDetails = array(
			"name"=>(string)($game->Name),
			"id"=>(string)($game->Id),
			"status"=>"Waiting for 4 players",
			"team1player1"=>(string)($game->Team1->Player1->Name),
			"team1player2"=>(string)($game->Team1->Player2->Name),
			"team2player1"=>(string)($game->Team2->Player1->Name),
			"team2player2"=>(string)($game->Team2->Player2->Name),
			"rookvalue"=>(string)($rules->RookValue),
			"playto"=>(string)($rules->PlayTo),
			"trumpbeforekitty"=>$TrumpBeforeKitty,
			"norookonfirsttrick"=>$NoRookOnFirstTrick
		);
		
		array_push($responseArray, $gameDetails);
	}
	
	$response = array(
		"action"=>"command", 
		"message"=>"allgamedetails",
		"data"=>$responseArray
	);
	
	sendJson($clientID, $response);
	
}

?>
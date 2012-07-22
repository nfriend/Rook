<?php

function sendJson($clientID, $object)
{
	global $Server;	
	$Server->wsSend($clientID, json_encode($object));
}

function getNextClientId($clientInfo)
{
	$teamNumber = $clientInfo["teamNumber"];
	$playerNumber = $clientInfo["playerNumber"];
	$thisGame = $clientInfo["game"];
	
	if($teamNumber === 1)
	{
		if($playerNumber === 1)
		{
			return $thisGame->Team2->Player1->ClientId;
		}
		else
		{
			return $thisGame->Team2->Player2->ClientId;
		}	
		
	}
	else
	{
		if($playerNumber === 1)
		{
			return $thisGame->Team1->Player2->ClientId;
		}
		else
		{
			return $thisGame->Team1->Player1->ClientId;
		}
	}
	
}

function getAllClientIdsInGame($game)
{
	$allClients = array(
		$game->Team1->Player1->ClientId,
		$game->Team1->Player2->ClientId,
		$game->Team2->Player1->ClientId,
		$game->Team2->Player2->ClientId,
	);
	
	return $allClients;
}

function getNextBidder($clientInfo)
{
	$game = $clientInfo["game"];	
		
	$nextPlayer;
	
	for($i = 0; $i < 4; $i++)
	{
		$nextPlayer = getNextPlayer($clientInfo);
		if(!$nextPlayer->HasPassed)
			break;		
	}
	
	if($nextPlayer === $game->Team1->Player1)
		$game->State->NextAction = "Team1Player1Bid";
	if($nextPlayer === $game->Team1->Player2)
		$game->State->NextAction = "Team1Player2Bid";
	if($nextPlayer === $game->Team2->Player1)
		$game->State->NextAction = "Team2Player1Bid";
	if($nextPlayer === $game->Team2->Player2)
		$game->State->NextAction = "Team2Player2Bid";
	
	return $nextPlayer->ClientId;
}

function getNextPlayer($clientInfo)
{	
	$teamNumber = $clientInfo["teamNumber"];
	$playerNumber = $clientInfo["playerNumber"];
	$thisGame = $clientInfo["game"];
	
	if($teamNumber === 1)
	{
		if($playerNumber === 1)
		{
			return $thisGame->Team2->Player1;
		}
		else
		{
			return $thisGame->Team2->Player2;
		}	
		
	}
	else
	{
		if($playerNumber === 1)
		{
			return $thisGame->Team1->Player2;
		}
		else
		{
			return $thisGame->Team1->Player1;
		}
	}	
}

function getNumberOfPassedPlayers($game)
{
	$count = 0;

	if($game->Team1->Player1->HasPassed)
		$count++;
	if($game->Team1->Player2->HasPassed)
		$count++;
	if($game->Team2->Player1->HasPassed)
		$count++;
	if($game->Team2->Player2->HasPassed)
		$count++;	

	
	return $count;
}

function playerHasCard($clientInfo, $data)
{
	$game = $clientInfo["game"];
	$player;
	
	if($clientInfo["teamNumber"] === 1)
	{
		if($clientInfo["playerNumber"] === 1)
		{
			$player = $game->Team1->Player1;	
		}
		else
		{
			$player = $game->Team1->Player2;
		}
	}
	else
	{
		if($clientInfo["playerNumber"] === 1)
		{
			$player = $game->Team2->Player1;
		}
		else
		{
			$player = $game->Team2->Player2;
		}
	}
	
	sendJson($clientInfo["clientId"], "all the cards in your hand:");
	foreach($player->Hand as $card)
	{			
		sendJson($clientInfo["clientId"], $card->toString());
	}
	
	foreach($player->Hand as $card)
	{			
		$cardSuit = $card->getSuitAsString();
		if($cardSuit === $data["suit"] && ($card->Number === (int)$data["number"] || $cardSuit === "rook"))
			return $card;
	}
	
	return null;
}

function isLegalMove($clientInfo, $trick, $card)
{
	if(count($trick->CardSet) === 0)
		return true;
	
	if($trick->CardSet[0]->Suit === $card->Suit)
		return true;	
		
	$game = $clientInfo["game"];
	$player;
	
	if($clientInfo["teamNumber"] === 1)
	{
		if($clientInfo["playerNumber"] === 1)
		{
			$player = $game->Team1->Player1;	
		}
		else
		{
			$player = $game->Team1->Player2;
		}
	}
	else
	{
		if($clientInfo["playerNumber"] === 1)
		{
			$player = $game->Team2->Player1;
		}
		else
		{
			$player = $game->Team2->Player2;
		}
	}	
	
	foreach($player->Hand as $card)
	{			
		if($trick->CardSet[0]->Suit === $card->Suit)
			return false;
	}
	
	return true;
}

function removeCardFromHand($clientId, $player, $card)
{
	$hand = $player->Hand;			
	$key = array_search($card, $hand);	
	unset($hand[$key]);
	$player->Hand = $hand;			
}

?>

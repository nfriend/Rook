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
		
	$nextPlayer = getNextPlayer($clientInfo);
	
	$teamAndPlayer = null;
	
	for($i = 0; $i < 4; $i++)
	{
		$nextPlayer = getNextPlayer($clientInfo, $teamAndPlayer);
		if(!$nextPlayer->HasPassed)
			break;
		
		if($nextPlayer === $game->Team1->Player1)
		{
			$teamNumber = 1;
			$playerNumber = 1;
		} 
		else if($nextPlayer === $game->Team1->Player2) 
		{
			$teamNumber = 1;
			$playerNumber = 2;
		}
		else if($nextPlayer === $game->Team2->Player1)
		{
			$teamNumber = 2;
			$playerNumber = 1;
		}
		else if($nextPlayer === $game->Team2->Player2)
		{
			$teamNumber = 2;
			$playerNumber = 2;
		}		
			
		$teamAndPlayer = array(
			"teamNumber"=> $teamNumber,
			"playerNumber"=> $playerNumber
		);		
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

// two options: pass in the clientInfo to get the next player after the referenced player OR
//				pass in the team number and player number manually
// method will still need $clientInfo if the second option is chosen, just to return the correct value
// $teamAndPlayer is an array with keys "teamNumber" and "playerNumber"
function getNextPlayer($clientInfo, $teamAndPlayer = null)
{
	if (!is_null($teamAndPlayer))
	{
		$teamNumber = $teamAndPlayer["teamNumber"];
		$playerNumber = $teamAndPlayer["playerNumber"];		
	}
	else
	{
		$teamNumber = $clientInfo["teamNumber"];
		$playerNumber = $clientInfo["playerNumber"];		
	}
	
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
	
	foreach($player->Hand as $card)
	{			
		$cardSuit = $card->getSuitAsString();
		if($cardSuit === $data["suit"] && ($card->Number === (int)$data["number"] || $cardSuit === "rook"))
			return $card;
	}
	
	return null;
}

function kittyHasCard($clientInfo, $data)
{
	$game = $clientInfo["game"];	
	$round = end($game->Rounds);
	$kitty = $round->Kitty;
	
	foreach($kitty as $card)
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
		
	$game = $clientInfo["game"];
	$round = end($game->Rounds);		
		
	$suitLead = $trick->CardSet[0]->Suit;
	if ($suitLead === Suit::Rook)
		$suitLead = $round->Trump;
		
	if($suitLead === $card->Suit)
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
		if($suitLead === $card->Suit)
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

function removeCardFromKitty($game, $card)
{
	$round = end($game->Rounds);
	$kitty = $round->Kitty;	
	$key = array_search($card, $kitty);	
	unset($kitty[$key]);
	$round->Kitty = $kitty;
}

function setNextGameState($clientInfo)
{
	$clientTeam = $clientInfo["teamNumber"];
	$clientPlayer = $clientInfo["playerNumber"];
	$game = $clientInfo["game"];
	
	if($clientTeam === 1)
	{
		if($clientPlayer === 1)
		{
			$game->State->NextAction = "Team2Player1Lay";
		}
		else
		{
			$game->State->NextAction = "Team2Player2Lay";
		}
	}
	else
	{
		if($clientPlayer === 1)
		{
			$game->State->NextAction = "Team1Player2Lay";
		}
		else
		{
			$game->State->NextAction = "Team1Player1Lay";
		}
	}
}

function moveCardsToKitty($clientInfo, $chosenHandCards, $chosenKittyCards)
{
	$game = $clientInfo["game"];
	$round = end($game->Rounds);
	$kitty = $round->Kitty;
	$player = $clientInfo["player"];
	$hand = $clientInfo["player"]->Hand;
	$currentHand = array_merge($kitty, $hand);
	$newKitty = array_merge($chosenHandCards, $chosenKittyCards);
		
	foreach($newKitty as $card)
	{
		$key = array_search($card, $currentHand);
		unset($currentHand[$key]);
	}
	
	$player->Hand = $currentHand;
	$round->Kitty = $newKitty;
	
}

function getSuitAsString($suit, $caps=false)
{
	if($suit === Suit::Black)
	{
		if ($caps)
		{
			return "Black";	
		}
		else 
		{
			return "black";
		}
	}
	
	if($suit === Suit::Red)
	{
		if ($caps)
		{
			return "Red";	
		}
		else 
		{
			return "red";
		}
	}
	
	if($suit === Suit::Yellow)
	{
		if ($caps)
		{
			return "Yellow";	
		}
		else 
		{
			return "yellow";
		}
	}
	
	if($suit === Suit::Green)
	{
		if ($caps)
		{
			return "green";	
		}
		else 
		{
			return "green";
		}
	}
}

//function setRookCardSuit($game)
//{
//	$round = end($game->Rounds);
//	$rookCard;
//	
//	foreach($round->Deck as $card)
//	{
//		if ($card->Suit = Suit::Rook)
//		{
//			$rookCard = $card;
//			break;
//		}
//	}
//	
//	$rookCard->Suit = $round->Trump;
//}

function tellClientsWhatCardsTheyHave($game)
{
	$id = $game->Team1->Player1->ClientId;
	$hand = $game->Team1->Player1->Hand;
	$allCards = "Your cards: ";
	
	foreach($hand as $card)
	{
		$allCards = $allCards . $card->toString() . ", ";
	}
		
	$response = array(
		"action"=>"log",
		"message"=>$allCards
	);

	sendJson($id, $response);
	
	$id = $game->Team1->Player2->ClientId;
	$hand = $game->Team1->Player2->Hand;
	$allCards = "Your cards: ";
	
	foreach($hand as $card)
	{
		$allCards = $allCards . $card->toString() . ", ";
	}
		
	$response = array(
		"action"=>"log",
		"message"=>$allCards
	);

	sendJson($id, $response);
	
	$id = $game->Team2->Player1->ClientId;
	$hand = $game->Team2->Player1->Hand;
	$allCards = "Your cards: ";
	
	foreach($hand as $card)
	{
		$allCards = $allCards . $card->toString() . ", ";
	}
		
	$response = array(
		"action"=>"log",
		"message"=>$allCards
	);

	sendJson($id, $response);
	
	$id = $game->Team2->Player2->ClientId;
	$hand = $game->Team2->Player2->Hand;
	$allCards = "Your cards: ";
	
	foreach($hand as $card)
	{
		$allCards = $allCards . $card->toString() . ", ";
	}
		
	$response = array(
		"action"=>"log",
		"message"=>$allCards
	);

	sendJson($id, $response);
}

function checkIfPointsInKitty($clientInfo)
{
	$game = $clientInfo["game"];
	$round = end($game->Rounds);
	$kitty = $round->Kitty;
	
	foreach($kitty as $card)
	{
		if($card->Value !== 0)
		{
			return true;
		}
	}
	
	return false;
}

?>

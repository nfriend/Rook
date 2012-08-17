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
	{
		$waitingOn = 0;		
		$game->State->NextAction = "Team1Player1Bid";
	}
	if($nextPlayer === $game->Team1->Player2)
	{
		$waitingOn = 2;
		$game->State->NextAction = "Team1Player2Bid";
	}
	if($nextPlayer === $game->Team2->Player1)
	{
		$waitingOn = 1;	
		$game->State->NextAction = "Team2Player1Bid";
	}
	if($nextPlayer === $game->Team2->Player2)
	{
		$waitingOn = 3;
		$game->State->NextAction = "Team2Player2Bid";
	}
	
	$allClientIds = getAllClientIdsInGame($game);
	
	for($i = 0; $i < 4; $i++)
	{	
		$response = array(
			"action"=>"command",
			"message"=>"waitingon",
			"data"=>$waitingOn											
		);
			
		sendJson($allClientIds[$i], $response);
	}
	
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
	$trumpWasLed = false;
	
	if(count($trick->CardSet) === 1 && $game->Rules->NoRookOnFirstTrick && $card->Suit === Suit::Rook)
		return false;
		
	$suitLead = $trick->CardSet[0]->Suit;
	if ($suitLead === Suit::Rook || $suitLead === $round->Trump)
	{
		$suitLead = $round->Trump;
		$trumpWasLed = true;
	}
		
	if($suitLead === $card->Suit)
		return true;
	
	if($trumpWasLed && $card->Suit === Suit::Rook)
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
		if($game->Rules->NoRookOnFirstTrick && count($round->Tricks) === 1 && $card->Suit === Suit:: Rook)
		{
			if($suitLead === $card->Suit)
				return false;
		}	
		else
		{
			if($suitLead === $card->Suit || ($trumpWasLed && $card->Suit === Suit::Rook))
				return false;
		}
		
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
			$waitingOn = 1;
		}
		else
		{
			$game->State->NextAction = "Team2Player2Lay";
			$waitingOn = 3;
		}
	}
	else
	{
		if($clientPlayer === 1)
		{
			$game->State->NextAction = "Team1Player2Lay";
			$waitingOn = 2;
		}
		else
		{
			$game->State->NextAction = "Team1Player1Lay";
			$waitingOn = 0;
		}
	}
	
	$allClientIds = getAllClientIdsInGame($game);
	
	foreach($allClientIds as $id)
	{
		$response = array(
			"action"=>"command",
			"message"=>"waitingon",
			"data"=>$waitingOn
		);
		
		sendJson($id, $response);
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

function cardsToJsonArray($cardArray)
{
	$returnArray = array();	
		
	foreach($cardArray as $card)
	{
		array_push($returnArray, array(
			"suit"=>$card->getSuitAsString(),
			"number"=>$card->Number
		));
	}
	
	return $returnArray;
}

function getAbsolutePlayerNumber($clientInfo)
{
	if ($clientInfo["teamNumber"] === 1)
	{
		if($clientInfo["playerNumber"] === 1)
		{
			return 0;
		}
		else 
		{
			return 2;
		}	
	}
	else
	{
		if($clientInfo["playerNumber"] === 1)
		{
			return 1;
		}
		else 
		{
			return 3;
		}
	}
}

// this method will either figure out the next hand based on the player referenced in $clientInfo,
// or it will use the teamNumber and playerNumber if provided - in this case it will NOT
// progress to the next player, but use the hand of the player provided
function getAllowedSuitsForNextPlayer($clientInfo, $teamNumber = null, $playerNumber = null)
{
	$game = $clientInfo["game"];			
	$hand;
	
	if(is_null($teamNumber) || is_null($playerNumber))
	{
		if($clientInfo["teamNumber"] === 1)
		{
			if($clientInfo["playerNumber"] === 1)
			{
				$hand = $game->Team2->Player1->Hand;	
			}
			else
			{
				$hand = $game->Team2->Player2->Hand;
			}
		}
		else
		{
			if($clientInfo["playerNumber"] === 1)
			{
				$hand = $game->Team1->Player2->Hand;	
			}
			else
			{
				$hand = $game->Team1->Player1->Hand;
			}
		}
	}
	else
	{
		if($teamNumber === 1)
		{
			if($playerNumber === 1)
			{
				$hand = $game->Team1->Player1->Hand;	
			}
			else
			{
				$hand = $game->Team1->Player2->Hand;
			}
		}
		else
		{
			if($playerNumber === 1)
			{
				$hand = $game->Team2->Player1->Hand;	
			}
			else
			{
				$hand = $game->Team2->Player2->Hand;
			}
		}	
	}	
		
	$trumpWasLed = false;
	$round = end($game->Rounds);
	$trick = end($round->Tricks);
	$trickCount = count($round->Tricks);
	
	$allowedSuits = array();
	
	$leadSuit = $trick->CardSet[0]->Suit;
	
	if($leadSuit === Suit::Rook)
		$leadSuit = $round->Trump;
	
	if($leadSuit === $round->Trump)
		$trumpWasLed = true;
	
	$handContainsSuit = false;
	
	foreach($hand as $card)
	{
		if($trumpWasLed)
		{
			if ($trickCount === 1 && $game->Rules->NoRookOnFirstTrick)			
			{
				if ($card->Suit === $leadSuit)
				{
					array_push($allowedSuits, $card->getSuitAsString());
					$handContainsSuit = true;			
					break;
				}
			}
			else
			{
				if ($card->Suit === $leadSuit || $card->Suit === Suit::Rook)
				{
					array_push($allowedSuits, $card->getSuitAsString());
					$handContainsSuit = true;			
					break;
				}
			}
		}
		else
		{
			if ($card->Suit === $leadSuit)
			{
				array_push($allowedSuits, $card->getSuitAsString());
				$handContainsSuit = true;			
				break;
			}
		}		 
	}
	
	if ($handContainsSuit)
	{
		if($trumpWasLed)
		{
			if ($trickCount === 1 && $game->Rules->NoRookOnFirstTrick)
			{
				return array(getSuitAsString($round->Trump));
			}
			else
			{
				return array("rook", getSuitAsString($round->Trump));	
			}	
		}	
		else
		{
			return $allowedSuits;	
		}		
	}
	else
	{
		if ($trickCount === 1 && $game->Rules->NoRookOnFirstTrick)
		{	
			return array("black", "yellow", "red", "green");
		}
		else
		{
			return array("black", "yellow", "red", "green", "rook");
		}
	}
	
}

function computeEndOfGameInfo($clientInfo, $winnerInfo)
{
	$game = $clientInfo["game"];		
	$round = end($game->Rounds);
	$trick = end($round->Tricks);
	
	$bid = $round->Bid;
	$team1RoundScore = 0;
	$team2RoundScore = 0;
	$kittyValue = 0;
	
	foreach($round->Tricks as $trick)
	{
		$trickValue = 0;	
			
		foreach($trick->CardSet as $card)
		{
			$trickValue += $card->Value;
		}
		
		if($trick->WinningTeam === 1)
		{
			$team1RoundScore += $trickValue;
		}
		else
		{
			$team2RoundScore += $trickValue;
		}
	}
	
	foreach($round->Kitty as $card)
	{
		$kittyValue += $card->Value;
	}
	
	if($winnerInfo["team"] === $game->Team1)
	{
		$team1RoundScore += $kittyValue;
	}
	else
	{
		$team2RoundScore += $kittyValue;	
	}
	
	if ($round->TeamBidWinner === $game->Team1)
	{
		$teamBidTaker = 1;	
						
		if($team1RoundScore < $bid)
		{
			$bidderMadeBid = false;
			array_push($game->Team1->ScoreCard->Rounds, -1 * $bid);
			array_push($game->Team2->ScoreCard->Rounds, $team2RoundScore);
		}
		else
		{
			$bidderMadeBid = true;	
			array_push($game->Team1->ScoreCard->Rounds, $team1RoundScore);
			array_push($game->Team2->ScoreCard->Rounds, $team2RoundScore);
		}
	}
	else
	{
		$teamBidTaker = 2;
						
		if($team2RoundScore < $bid)
		{
			$bidderMadeBid = false;
			array_push($game->Team2->ScoreCard->Rounds, -1 * $bid);
			array_push($game->Team1->ScoreCard->Rounds, $team2RoundScore);
		}
		else
		{
			$bidderMadeBid = true;	
			array_push($game->Team1->ScoreCard->Rounds, $team1RoundScore);
			array_push($game->Team2->ScoreCard->Rounds, $team2RoundScore);
		}
	}
	
	$team1TotalScore = 0;
	$team2TotalScore = 0;
	
	foreach($game->Team1->ScoreCard->Rounds as $score)
	{
		$team1TotalScore += $score;
	}
	
	foreach($game->Team2->ScoreCard->Rounds as $score)
	{
		$team2TotalScore += $score;
	}
	
	$teamGameWinner = 0;
	$gameIsDone = "no";
	
	if($game->Rules->PlayTo === "single" || $team1TotalScore > $game->Rules->PlayTo)
	{
		$teamGameWinner = 1;
		$gameIsDone = "yes";
	}
	
	if($team1TotalScore > $game->Rules->PlayTo)
	{
		$gameIsDone = true;	
		if ($teamGameWinner === 1)
		{
			$teamGameWinner = 3;
		}
		else
		{
			$teamGameWinner = 2;	
		}		
	}
		
	return array(
		"bid"=> $bid,
		"teamBidTaker"=> $teamBidTaker,
		"team1RoundScore"=> $team1RoundScore,
		"team1TotalScore"=> $team1TotalScore,
		"team2RoundScore"=> $team2RoundScore,
		"team2TotalScore"=> $team2TotalScore,
		"bidderMadeBid"=> $bidderMadeBid,
		"kittyCards"=> cardsToJsonArray($round->Kitty),
		"kittyValue"=> $kittyValue,
		"gameIsDone"=> $gameIsDone,
		"teamGameWinner"=> $teamGameWinner		
	);
}

?>

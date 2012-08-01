<?php

class Game
{
	public $Id;
	
	public $Name;	
		
	public $Team1;
	public $Team2;

	public $State;
	
	public $Rounds;
	
	public $Rules;	

	function Game()
	{
		$this->Id = -1;
						
		$this->Team1 = new Team();		
		$this->Team2 = new Team();

		$this->State = new GameState();
		
		$this->Rounds = array();
		
		$this->Rules = new Rules();
	}
	
	function processCommand($clientInfo, $data)
	{
		if($this->State != "lobby")
		{
			switch($data["command"])
			{
				case "bid":
					if (($this->State->NextAction === "Team1Player1Bid" && $clientInfo["teamNumber"] === 1 && $clientInfo["playerNumber"] === 1) ||
						($this->State->NextAction === "Team1Player2Bid" && $clientInfo["teamNumber"] === 1 && $clientInfo["playerNumber"] === 2) ||
						($this->State->NextAction === "Team2Player1Bid" && $clientInfo["teamNumber"] === 2 && $clientInfo["playerNumber"] === 1) ||
						($this->State->NextAction === "Team2Player2Bid" && $clientInfo["teamNumber"] === 2 && $clientInfo["playerNumber"] === 2))
					// the player has given an appropriate command
					{						
						if ((string)$data["arguments"] === "pass")
						// the player passes instead of bids
						{
							if($clientInfo["teamNumber"] === 1)
							{
								if($clientInfo["playerNumber"] === 1)
								{
									$this->Team1->Player1->HasPassed = true;
								}	
								else
								{
									$this->Team1->Player2->HasPassed = true;
								}									
							}
							else
							{
								if($clientInfo["playerNumber"] === 1)
								{
									$this->Team2->Player1->HasPassed = true;
								}	
								else
								{
									$this->Team2->Player2->HasPassed = true;
								}
							}
							
							if (getNumberOfPassedPlayers($clientInfo["game"]) == 3)
							// check to see if everyone has passed, and if so, decide who won
							{
								$round = end($this->Rounds);
								array_push($round->Tricks, new Trick());
								
								if($this->Team1->Player1->HasPassed === false)
								{
									$round->TeamBidWinner = $this->Team1;
									$round->PlayerBidWinner = $this->Team1->Player1;
									$this->State->NextAction = "Team1Player1Kitty";
								}
								if($this->Team1->Player2->HasPassed === false)
								{
									$round->TeamBidWinner = $this->Team1;
									$round->PlayerBidWinner = $this->Team1->Player2;
									$this->State->NextAction = "Team1Player2Kitty";
								}
								if($this->Team2->Player1->HasPassed === false)
								{
									$round->TeamBidWinner = $this->Team2;
									$round->PlayerBidWinner = $this->Team2->Player1;
									$this->State->NextAction = "Team2Player1Kitty";
								}
								if($this->Team2->Player2->HasPassed === false)
								{
									$round->TeamBidWinner = $this->Team2;
									$round->PlayerBidWinner = $this->Team2->Player2;
									$this->State->NextAction = "Team2Player2Kitty";
								}
								
									
								$allClients = getAllClientIdsInGame($clientInfo["game"]);
								
								foreach($allClients as $id)
								{
									$response = array(
										"action"=>"log",
										"message"=> "Player " . (string)$round->PlayerBidWinner->ClientId . " has won the bid with a bid of " . (string)$round->Bid
									);
									
									sendJson($id, $response);
									
									if($id != $round->PlayerBidWinner->ClientId)
									{
										$response = array(
											"action"=>"command",
											"message"=>"losepermission"											
										);
									}
									else 
									{
										$response = array(
											"action"=>"command",
											"message"=>"gainpermission"											
										);
									}
									
									sendJson($id, $response);
								}
								
							}
							else 
							{
								$response = array(
									"action"=>"command",
									"message"=>"losepermission"
								);
								
								sendJson($clientInfo["clientId"], $response);
								
								$response = array(
									"action"=>"command",
									"message"=>"gainpermission"
								);
									
								sendJson(getNextBidder($clientInfo), $response);	
							}							
						}
						elseif ($data["arguments"] % 5 === 0)
						// the player did not pass, and the bid is divisible by 5
						{
							$round = end($this->Rounds);
							
							if((int)$data["arguments"] > $round->Bid && (int)$data["arguments"] < 180)
							// make sure the bid is within the correct bounds
							{
								$round->Bid = (int)$data["arguments"];
								
								$allClients = getAllClientIdsInGame($clientInfo["game"]);
								
								foreach($allClients as $id)
								{
									$response = array(
										"action"=>"log",
										"message"=>"The bid is now at " . (string)$round->Bid
									);
									
									sendJson($id, $response);
								}
									
								$response = array(
									"action"=>"command",
									"message"=>"losepermission"
								);
								
								sendJson($clientInfo["clientId"], $response);
								
								$response = array(
									"action"=>"command",
									"message"=>"gainpermission"
								);
									
								sendJson(getNextClientId($clientInfo), $response);
								
								if($clientInfo["teamNumber"] === 1)
								{
									if($clientInfo["playerNumber"] === 1)
									{
										$this->State->NextAction = "Team2Player1Bid";	
									}	
									else
									{
										$this->State->NextAction = "Team2Player2Bid";	
									}									
								}
								else
								{
									if($clientInfo["playerNumber"] === 1)
									{
										$this->State->NextAction = "Team1Player2Bid";	
									}	
									else
									{
										$this->State->NextAction = "Team1Player1Bid";	
									}
								}									
							}
							elseif((int)$data["arguments"] === 180)
							// check to see if the player bid 180 exactly
							{
								$round = end($this->Rounds);
								array_push($round->Tricks, new Trick());
								$round->Bid = 180;
								
								if($this->Team1->Player1->ClientId === $clientInfo["clientId"])
								{
									$round->TeamBidWinner = $this->Team1;
									$round->PlayerBidWinner = $this->Team1->Player1;
									$this->State->NextAction = "Team1Player1Kitty";
								}
								if($this->Team1->Player2->ClientId === $clientInfo["clientId"])
								{
									$round->TeamBidWinner = $this->Team1;
									$round->PlayerBidWinner = $this->Team1->Player2;
									$this->State->NextAction = "Team1Player2Kitty";
								}
								if($this->Team2->Player1->ClientId === $clientInfo["clientId"])
								{
									$round->TeamBidWinner = $this->Team2;
									$round->PlayerBidWinner = $this->Team2->Player1;
									$this->State->NextAction = "Team2Player1Kitty";
								}
								if($this->Team2->Player2->ClientId === $clientInfo["clientId"])
								{
									$round->TeamBidWinner = $this->Team2;
									$round->PlayerBidWinner = $this->Team2->Player2;
									$this->State->NextAction = "Team2Player2Kitty";
								}
								
								$allClients = getAllClientIdsInGame($clientInfo["game"]);
								
								foreach($allClients as $id)
								{
									$response = array(
										"action"=>"log",
										"message"=> "Player " . (string)$round->PlayerBidWinner->ClientId . " has won the bid with a bid of " . (string)$round->Bid
									);
									
									sendJson($id, $response);
									
									if($id != $round->PlayerBidWinner->ClientId)
									{
										$response = array(
											"action"=>"command",
											"message"=>"losepermission"											
										);
									}
									else 
									{
										$response = array(
											"action"=>"command",
											"message"=>"gainpermission"											
										);
									}
									
									sendJson($id, $response);
								}
							}
							else
							// the player has submitted a bid outside of the allowable bounds
							{
								$response = array(
									"action"=>"alert",
									"message"=>"Sorry, your bid must be higher than the current bid of " . (string)$round->Bid . " and no greater than 180."
								);
								
								sendJson($clientInfo["clientId"], $response);	
							}
						}
						else 
						// the player has submitted a bid that is not divisble by 5
						{
							$response = array(
								"action"=>"alert",
								"message"=>"Sorry, your bid must be a multiple of 5."
							);
							
							sendJson($clientInfo["clientId"], $response);	
						}
					}
					break;
				case "kitty":
					if (($this->State->NextAction === "Team1Player1Kitty" && $clientInfo["teamNumber"] === 1 && $clientInfo["playerNumber"] === 1) ||
						($this->State->NextAction === "Team1Player2Kitty" && $clientInfo["teamNumber"] === 1 && $clientInfo["playerNumber"] === 2) ||
						($this->State->NextAction === "Team2Player1Kitty" && $clientInfo["teamNumber"] === 2 && $clientInfo["playerNumber"] === 1) ||
						($this->State->NextAction === "Team2Player2Kitty" && $clientInfo["teamNumber"] === 2 && $clientInfo["playerNumber"] === 2))
					// check to make sure the player has made an appropriate command
					{
						$allCardsAreValid = true;
						
						$chosenHandCards = array();
						$chosenKittyCards = array();
						
						foreach($data["arguments"] as $card)
						// check each card, make sure it exists in either the player's hand or the kitty
						{
							$round = end($this->Rounds);
							$kitty = $round->Kitty;	
								
							$handCard = playerHasCard($clientInfo, $card);
							$kittyCard = kittyHasCard($clientInfo, $card); 	
								
							if(!$handCard && !$kittyCard)
							{
								$allCardsAreValid = false;
								break;
							}
							
							if($handCard)
							{
								array_push($chosenHandCards, $handCard);								
							} 
							elseif ($kittyCard)
							{
								array_push($chosenKittyCards, $kittyCard);
							}
						}
						
						$round = end($this->Rounds);
						
						if($allCardsAreValid)
						// all the cards submitted were found in the kitty or the player's hand
						{
							//set the trump for the round
							if((string)$data["trumpcolor"] === "green")
							{								
								$round->Trump = Suit::Green;
							} elseif ((string)$data["trumpcolor"] === "red")
							{
								$round->Trump = Suit::Red;
							} elseif ((string)$data["trumpcolor"] === "black")
							{
								$round->Trump = Suit::Black;
							} elseif ((string)$data["trumpcolor"] === "yellow")
							{
								$round->Trump = Suit::Yellow;
							} else {
								$response = array(
									"action"=>"alert",
									"message"=>"Invalid color choice"
								);
								
								sendJson($clientInfo["clientId"], $response);
								
								break;								
							}
							
							moveCardsToKitty($clientInfo, $chosenHandCards, $chosenKittyCards);
							
							$allClients = getAllClientIdsInGame($clientInfo["game"]);
							
							$round = end($this->Rounds);
							$trumpColor = getSuitAsString($round->Trump, true);
							
							foreach($allClients as $id)
							{											
								$response = array(
									"action"=>"log",
									"message"=>"Player " . (string)$clientInfo["clientId"] . " is finished with the kitty, and trump is " . $trumpColor
								);
								
								sendJson($id, $response);							
							}
							
							$teamNumber = $clientInfo["teamNumber"];
							$playerNumber = $clientInfo["playerNumber"];
							
							if($teamNumber === 1 && $playerNumber === 1)
								$this->State->NextAction = "Team1Player1Lay";
							if($teamNumber === 1 && $playerNumber === 2)
								$this->State->NextAction = "Team1Player2Lay";
							if($teamNumber === 2 && $playerNumber === 1)
								$this->State->NextAction = "Team2Player1Lay";
							if($teamNumber === 2 && $playerNumber === 2)
								$this->State->NextAction = "Team2Player2Lay";							
						}
						else 
						// the cards submitted were not found in the player's hand or the kitty
						{
							$response = array(
								"action"=>"alert",
								"message"=>"Invalid card selection."
							);
							
							sendJson($clientInfo["clientId"], $response);
						}
					}
						
					break;
				case "lay":
					if (($this->State->NextAction === "Team1Player1Lay" && $clientInfo["teamNumber"] === 1 && $clientInfo["playerNumber"] === 1) ||
						($this->State->NextAction === "Team1Player2Lay" && $clientInfo["teamNumber"] === 1 && $clientInfo["playerNumber"] === 2) ||
						($this->State->NextAction === "Team2Player1Lay" && $clientInfo["teamNumber"] === 2 && $clientInfo["playerNumber"] === 1) ||
						($this->State->NextAction === "Team2Player2Lay" && $clientInfo["teamNumber"] === 2 && $clientInfo["playerNumber"] === 2))
					// make sure the player has made an appropriate command
					{
						$card = playerHasCard($clientInfo, $data["arguments"]);	
													
						if($card !== null)
						//continue if the card was found in the player's hand
						{
							$round = end($this->Rounds);	
							$trick = end($round->Tricks);
							
							if(isLegalMove($clientInfo, $trick, $card))
							// make sure the player is properly following suit
							{
								array_push($trick->CardSet, $card);
								array_push($trick->PlayerOrder, $clientInfo);
								
								removeCardFromHand($clientInfo["clientId"], $clientInfo["player"], $card);
								
								$response = array(
									"action"=>"log",
									"message"=>"Card laid."
								);
								
								sendJson($clientInfo["clientId"], $response);
								
								if(count($trick->CardSet) === 4)
								// if the trick is done, figure out who won
								{										
									$leadSuit = $trick->CardSet[0]->Suit;
									if($leadSuit === Suit::Rook)
										$leadSuit === $round->Trump;
									
									$highestValue = 0;
									$highestCard;
									
									$trumpIsInRound = false;
									
									foreach($trick->CardSet as $card)
									{
										$cardSuit = $card->Suit;
										if($cardSuit === Suit::Rook)
											$cardSuit = $round->Trump;
										
										if($cardSuit === $round->Trump && !$trumpIsInRound)
										{
											$highestValue = 0;	
											$trumpIsInRound = true;
										}
											
										if($cardSuit === $leadSuit && !$trumpIsInRound)
										// if the card matches the suit of the card lead, and so far no trump has been found in the trick
										{
											$cardNumber = $card->Number;
											if($cardNumber === 1)
											{
												$cardNumber = 15;
											}
												
											if($cardNumber > $highestValue)
											{
												$highestValue = $cardNumber;	
												$highestCard = $card;
											}
										}
										elseif($cardSuit === $round->Trump && $trumpIsInRound)
										// if the card is trump, basically ignore anything that isn't trump
										{
											$cardNumber = $card->Number;
											if($cardNumber === 1)
											{
												$cardNumber = 15;
											}
												
											if($cardNumber > $highestValue)
											{
												$highestValue = $cardNumber;	
												$highestCard = $card;
											}											
										}		
									}
									
									$key = array_search($highestCard, $trick->CardSet);
									$winnerInfo = $trick->PlayerOrder[$key];
									
									if($winnerInfo["teamNumber"] === 1 && $winnerInfo["playerNumber"] === 1)
										$this->State->NextAction = "Team1Player1Lay";
									if($winnerInfo["teamNumber"] === 1 && $winnerInfo["playerNumber"] === 2)
										$this->State->NextAction = "Team1Player2Lay";
									if($winnerInfo["teamNumber"] === 2 && $winnerInfo["playerNumber"] === 1)
										$this->State->NextAction = "Team2Player1Lay";
									if($winnerInfo["teamNumber"] === 2 && $winnerInfo["playerNumber"] === 2)
										$this->State->NextAction = "Team2Player2Lay";
									
									$isLastRound = false;
									if(count($round->Tricks) === 10)
									{
										$isLastRound = true;
									}
									else
									{
										array_push($round->Tricks, new Trick());
									}									
									
									$allClients = getAllClientIdsInGame($clientInfo["game"]);
									
									foreach($allClients as $id)
									{
										if($id === $winnerInfo["clientId"])
										{
											$response = array(
												"action"=>"command",
												"message"=>"gainpermission"
											);
											sendJson($id, $response);	
										}		
										else
										{
											$response = array(
												"action"=>"command",
												"message"=>"losepermission"
											);
											sendJson($id, $response);
										}		
											
										$response = array(
											"action"=>"log",
											"message"=>"The trick was won by Player " . $winnerInfo["clientId"] . " with the " . $highestCard->toString()
										);
										
										sendJson($id, $response);
										
										if($isLastRound)
										{
											$response = array(
											"action"=>"log",
											"message"=>"The game is over!  The kitty goes to team " . (string)$winnerInfo["teamNumber"]
										);
										
										sendJson($id, $response);
										}
									}

									tellClientsWhatCardsTheyHave($clientInfo["game"]);

								}
								else
								//other players still need to lay
								{
									setNextGameState($clientInfo);
									$allClients = getAllClientIdsInGame($clientInfo["game"]);
									
									foreach($allClients as $id)
									{
										if($id === getNextClientId($clientInfo))
										{
											$response = array(
												"action"=>"command",
												"message"=>"gainpermission"
											);
											sendJson($id, $response);	
										}		
										else
										{
											$response = array(
												"action"=>"command",
												"message"=>"losepermission"
											);
											sendJson($id, $response);
										}
									}									
								}									
							}
							else 
							// the card was not a legal move
							{
								$response = array(
									"action"=>"alert",
									"message"=>"Sorry, you must follow suit."
								);
								
								sendJson($clientInfo["clientId"], $response);	
							}							
						}
						else
						// the player tried to lay a card that wasn't in his/her hand
						{
							$response = array(
								"action"=>"alert",
								"message"=>"Sorry, you do not have that card in your hand."
							);
							
							sendJson($clientInfo["clientId"], $response);							
						}
					}
					
					break;
			}
		}
	}
}

class Team
{
	public $Player1;
	public $Player2;
	
	public $ScoreCard;

	function Team()
	{			
		$this->Player1 = null;
		$this->Player2 = null;
			
		$this->Score = new ScoreCard();		
	}
	
	function AddPlayer($clientId)
	{
		global $Server, $wsClientNames;	
		
		$playerNumber;
			
		if (is_null($this->Player1))
		{
			$this->Player1 = new Player($clientId);
			$playerNumber = 1;
			
			if(is_null($wsClientNames[$clientId]))
			{
				$this->Player1->Name = "Player " . (string)$clientId;			
			}
			else
			{
				$this->Player1->Name = $wsClientNames[$clientId];	
			}
			
		} 
		elseif (is_null($this->Player2)) 
		{
			$this->Player2 = new Player($clientId);
			$playerNumber = 2;
			
			if(is_null($wsClientNames[$clientId]))
			{
				$this->Player2->Name = "Player " . (string)$clientId;			
			}
			else
			{
				$this->Player2->Name = $wsClientNames[$clientId];	
			}
		} 
		else 
		{
			return false;	
		}
		
		$response = array(
			"action"=>"log",
			"message"=>"Player " . (string) $clientId . " successfully added."
		);
		
		sendJson($clientId, $response);
		
		return $playerNumber;
	}
}

class Player
{
	public $ClientID;
	public $Name;
	public $Hand;
	public $HasPassed;
	
	function Player($id)
	{
		$this->ClientId = $id;
		$this->HasPassed = false;
		$this->Hand = array();		
	}

}

class ScoreCard
{
	public $Rounds;

	function ScoreCard()
	{
		$this->Rounds = array();
	}
}

class Round
{
	public $Bid;
	public $Score;
	
	public $TeamBidWinner;
	public $PlayerBidWinner;
	
	public $Tricks;
	
	public $Trump;	
	
	public $Kitty;
	
	public $Deck;
	
	function Round()
	{
		$this->Score = 0;
		$this->Bid = 75;
		$this->Tricks = array();
		$this->Kitty = array();
		$this->Deck = array();
	}	
}

class Trick
{
	public $CardSet;
	public $PlayerOrder;
	
	public $WinningTeam;
	
	function Trick()
	{
		$this->CardSet = array();
		$this->PlayerOrder = array();
	}
}

class GameState
{
	public $Location;
	
	public $NextAction;
	
	function GameState()
	{
		$this->Location = "lobby";
		$this->NextAction = "begingame";
	}
}

class Rules
{
	public $RookValue;
	public $PlayTo;
	public $TrumpBeforeKitty;
	public $NoRookOnFirstTrick;
	
	function Rules()
	{
		$this->RookValue = 10.5;
		$this->PlayTo = 500;
		$this->TrumpBeforeKitty = false;
		$this->NoRookOnFirstTrick = false;		
	}
	
}

final class Suit
{
	 const Red = 0;
	 const Yellow = 1;
	 const Green = 2;
	 const Black = 3;
	 const Rook = 4;
}

class Card
{
	public $Suit;
	public $Number;
	public $Value;
	
	function Card($s, $n, $v)
	{
		$this->Suit = $s;
		$this->Number = $n;
		$this->Value = $v;
	}
	
	function toString()
	{
		if($this->Suit === Suit::Black)
		{
			return "Black " . (string)$this->Number;
		}
		elseif($this->Suit === Suit::Red)
		{
			return "Red " . (string)$this->Number;
		}
		elseif($this->Suit === Suit::Green)
		{
			return "Green " . (string)$this->Number;
		}
		elseif($this->Suit === Suit::Yellow)
		{
			return "Yellow " . (string)$this->Number;
		}
		else
		{
			return "Rook";	
		}
	}
	
	function getSuitAsString()
	{
		if($this->Suit === Suit::Black)
		{
			return "black";	
		}
		if($this->Suit === Suit::Green)
		{
			return "green";	
		}
		if($this->Suit === Suit::Yellow)
		{
			return "yellow";	
		}
		if($this->Suit === Suit::Red)
		{
			return "red";	
		}
		if($this->Suit === Suit::Rook)
		{
			return "rook";	
		}
	}
	
}


?>
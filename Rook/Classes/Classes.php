<?php

/*
	Represents a single instance of a Rook game
*/

class Game
{
	public $Id;	
		
	public $Team1;
	public $Team2;

	public $State;
	
	public $Kitty;
	
	public $Rounds;	

	function Game()
	{
		$this->Id = -1;
						
		$this->Team1 = new Team();		
		$this->Team2 = new Team();

		$this->State = new GameState();
		
		$this->Rounds = array();
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
					{
						if ((string)$data["arguments"] === "pass")
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
							{
								$round = end($this->Rounds);
								array_push($round->Tricks, new Trick());
								
								if($this->Team1->Player1->HasPassed === false)
								{
									$round->TeamBidWinner = $this->Team1;
									$round->PlayerBidWinner = $this->Team1->Player1;
									$this->State->NextAction = "Team1Player1Lay";
								}
								if($this->Team1->Player2->HasPassed === false)
								{
									$round->TeamBidWinner = $this->Team1;
									$round->PlayerBidWinner = $this->Team1->Player2;
									$this->State->NextAction = "Team1Player2Lay";
								}
								if($this->Team2->Player1->HasPassed === false)
								{
									$round->TeamBidWinner = $this->Team2;
									$round->PlayerBidWinner = $this->Team2->Player1;
									$this->State->NextAction = "Team2Player1Lay";
								}
								if($this->Team2->Player2->HasPassed === false)
								{
									$round->TeamBidWinner = $this->Team2;
									$round->PlayerBidWinner = $this->Team2->Player2;
									$this->State->NextAction = "Team2Player2Lay";
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
						{
							$round = end($this->Rounds);
							
							if((int)$data["arguments"] > $round->Bid && (int)$data["arguments"] < 180)
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
							{
								$round = end($this->Rounds);
								$round->Bid = 180;
								
								if($this->Team1->Player1->ClientId === $clientInfo["clientId"])
								{
									$round->TeamBidWinner = $this->Team1;
									$round->PlayerBidWinner = $this->Team1->Player1;
									$this->State->NextAction = "Team1Player1Lay";
								}
								if($this->Team1->Player2->ClientId === $clientInfo["clientId"])
								{
									$round->TeamBidWinner = $this->Team1;
									$round->PlayerBidWinner = $this->Team1->Player2;
									$this->State->NextAction = "Team1Player2Lay";
								}
								if($this->Team2->Player1->ClientId === $clientInfo["clientId"])
								{
									$round->TeamBidWinner = $this->Team2;
									$round->PlayerBidWinner = $this->Team2->Player1;
									$this->State->NextAction = "Team2Player1Lay";
								}
								if($this->Team2->Player2->ClientId === $clientInfo["clientId"])
								{
									$round->TeamBidWinner = $this->Team2;
									$round->PlayerBidWinner = $this->Team2->Player2;
									$this->State->NextAction = "Team2Player2Lay";
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
									"action"=>"alert",
									"message"=>"Sorry, your bid must be higher than the current bid of " . (string)$round->Bid . " and no greater than 180."
								);
								
								sendJson($clientInfo["clientId"], $response);	
							}
						}
						else 
						{
							$response = array(
								"action"=>"alert",
								"message"=>"Sorry, your bid must be a multiple of 5."
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
					{
						$card = playerHasCard($clientInfo, $data["arguments"]);	
													
						if($card !== null)
						{
							$round = end($this->Rounds);	
							$trick = end($round->Tricks);
							
							if(isLegalMove($clientInfo, $trick, $card))
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
								{
									$leadSuit = $trick->CardSet[0]->Suit;
									$highestValue = 0;
									$highestCard;
									
									foreach($trick->CardSet as $card)
									{
										if($card->Suit === $leadSuit)
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
									
									$allClients = getAllClientIdsInGame($clientInfo["game"]);
									
									foreach($allClients as $id)
									{
										$response = array(
											"action"=>"log",
											"message"=>"The trick was won by Player " . $winnerInfo["clientId"] . " with the " . $highestCard->toString()
										);
										
										sendJson($id, $response);
									}
								}									
							}
							else 
							{
								$response = array(
									"action"=>"alert",
									"message"=>"Sorry, you must follow suit."
								);
								
								sendJson($clientInfo["clientId"], $response);	
							}							
						}
						else
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
		global $Server;	
			
		if (is_null($this->Player1))
		{
			$this->Player1 = new Player($clientId);
		} 
		elseif (is_null($this->Player2)) 
		{
			$this->Player2 = new Player($clientId);
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
		
		return true;
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
	
	function Round()
	{
		$this->Score = 0;
		$this->Bid = 75;
		$this->Tricks = array();
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
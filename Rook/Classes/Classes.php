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
	
	public $BidStarter;	
	
	public $DeleteMe;

	function Game()
	{
		$this->Id = -1;
						
		$this->Team1 = new Team();		
		$this->Team2 = new Team();

		$this->State = new GameState();
		
		$this->Rounds = array();
		
		$this->Rules = new Rules();
		
		$this->BidStarter = 1;
		
		$this->DeleteMe = false;
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
									$waitingOn = 0;
								}
								if($this->Team1->Player2->HasPassed === false)
								{
									$round->TeamBidWinner = $this->Team1;
									$round->PlayerBidWinner = $this->Team1->Player2;
									$this->State->NextAction = "Team1Player2Kitty";
									$waitingOn = 2;
								}
								if($this->Team2->Player1->HasPassed === false)
								{
									$round->TeamBidWinner = $this->Team2;
									$round->PlayerBidWinner = $this->Team2->Player1;
									$this->State->NextAction = "Team2Player1Kitty";
									$waitingOn = 1;
								}
								if($this->Team2->Player2->HasPassed === false)
								{
									$round->TeamBidWinner = $this->Team2;
									$round->PlayerBidWinner = $this->Team2->Player2;
									$this->State->NextAction = "Team2Player2Kitty";
									$waitingOn = 3;
								}
								
									
								$allClients = getAllClientIdsInGame($clientInfo["game"]);
								
								foreach($allClients as $id)
								{	
									if($id != $round->PlayerBidWinner->ClientId)
									{
										$response = array(
											"action"=>"command",
											"message"=>"waitforkitty",
											"data"=>array(
												"bid"=> $round->Bid,
												"bidwinner"=> $round->PlayerBidWinner->Name
											)											
										);
										
										sendJson($id, $response);										
										
										$response = array(
											"action"=>"command",
											"message"=>"losepermission"
										);									
											
										sendJson($id, $response);	
									}
									else 
									{
										$kittyCards = array();
											
										foreach($round->Kitty as $card)
										{
											array_push($kittyCards, array(
												"suit"=>$card->getSuitAsString(),
												"number"=>$card->Number
											));
										}
										
										$response = array(
											"action"=>"command",
											"message"=>"kitty",
											"data"=>$kittyCards											
										);
										
										sendJson($id, $response);
										
										$response = array(
											"action"=>"command",
											"message"=>"gainpermission"
										);
										
										sendJson($id, $response);
									}
									
									$response = array(
											"action"=>"command",
											"message"=>"waitingon",
											"data"=>$waitingOn											
										);
										
									sendJson($id, $response);
								}
								
							}
							else
							// not everyone has passed 
							{
								$round = end($this->Rounds);	
								
								$allClients = getAllClientIdsInGame($clientInfo["game"]);
								$nextClientId = getNextBidder($clientInfo); 
									
								foreach($allClients as $thisClient)
								{
									$response = array(
										"action"=>"command",
										"message"=>"newsfeed", 
										"data" => $clientInfo["player"]->Name . " has passed"
									);
									
									sendJson($thisClient, $response);	
										
									if($thisClient === $nextClientId)
									{
										$response = array(
											"action"=>"command",
											"message"=>"gainpermission"
										);									
											
										sendJson($nextClientId, $response);	
										
										$response = array(
											"action"=>"command",
											"message"=>"yourbid",
											"data"=> array(
												"bid"=> $round->Bid,
												"highestbidder"=> $round->CurrentHighestBidder->Name
											)
										);
										
										sendJson($nextClientId, $response);
									
									}	
									else
									{
										$response = array(
											"action"=>"command",
											"message"=>"losepermission"
										);
										
										sendJson($thisClient, $response);
										
										$response = array(
											"action"=>"command",
											"message"=>"notyourbid",
											"data"=> array(
												"bid"=> $round->Bid,
												"highestbidder"=> $round->CurrentHighestBidder->Name
											)
										);
										
										sendJson($thisClient, $response);
									}												
								}
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
								$round->CurrentHighestBidder = $clientInfo["player"];
								
								$allClients = getAllClientIdsInGame($clientInfo["game"]);
								$nextClientId = getNextBidder($clientInfo);
								
								foreach($allClients as $id)
								{
									$response = array(
										"action"=>"command",
										"message"=>"newsfeed", 
										"data"=>$clientInfo["player"]->Name . " just bid " . (string)$round->Bid
										);
										
									sendJson($id, $response);	
										
									if ($nextClientId === $id)
									{
										$response = array(
											"action"=>"command",
											"message"=>"gainpermission"
										);
											
										sendJson($id, $response);
										
										$response = array(
											"action"=>"command",
											"message"=>"yourbid",
											"data"=> array(
												"bid"=> $round->Bid,
												"highestbidder"=> $round->CurrentHighestBidder->Name
											)
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
										
										$response = array(
											"action"=>"command",
											"message"=>"notyourbid",
											"data"=> array(
												"bid"=> $round->Bid,
												"highestbidder"=> $round->CurrentHighestBidder->Name
											)
										);
										
										sendJson($id, $response);
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
									$waitingOn = 0;
								}
								if($this->Team1->Player2->ClientId === $clientInfo["clientId"])
								{
									$round->TeamBidWinner = $this->Team1;
									$round->PlayerBidWinner = $this->Team1->Player2;
									$this->State->NextAction = "Team1Player2Kitty";
									$waitingOn = 2;
								}
								if($this->Team2->Player1->ClientId === $clientInfo["clientId"])
								{
									$round->TeamBidWinner = $this->Team2;
									$round->PlayerBidWinner = $this->Team2->Player1;
									$this->State->NextAction = "Team2Player1Kitty";
									$waitingOn = 1;
								}
								if($this->Team2->Player2->ClientId === $clientInfo["clientId"])
								{
									$round->TeamBidWinner = $this->Team2;
									$round->PlayerBidWinner = $this->Team2->Player2;
									$this->State->NextAction = "Team2Player2Kitty";
									$waitingOn = 3;
								}
								
								$allClients = getAllClientIdsInGame($clientInfo["game"]);
								
								foreach($allClients as $id)
								{	
									if($id != $round->PlayerBidWinner->ClientId)
									{
										$response = array(
											"action"=>"command",
											"message"=>"waitforkitty",
											"data"=>array(
												"bid"=> $round->Bid,
												"bidwinner"=> $round->PlayerBidWinner->Name
											)											
										);
										
										sendJson($id, $response);										
										
										$response = array(
											"action"=>"command",
											"message"=>"losepermission"
										);									
											
										sendJson($id, $response);	
									}
									else 
									{
										$kittyCards = array();
											
										foreach($round->Kitty as $card)
										{
											array_push($kittyCards, array(
												"suit"=>$card->getSuitAsString(),
												"number"=>$card->Number
											));
										}
										
										$response = array(
											"action"=>"command",
											"message"=>"kitty",
											"data"=>$kittyCards											
										);
										
										sendJson($id, $response);
										
										$response = array(
											"action"=>"command",
											"message"=>"gainpermission"
										);
										
										sendJson($id, $response);
									}
									
									$response = array(
										"action"=>"command",
										"message"=>"waitingon",
										"data"=>$waitingOn											
									);
										
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
							
							if(checkIfPointsInKitty($clientInfo))
							{
								$messageString = "There are points in the kitty.";
							}
							else
							{
								$messageString = "There are no points in the kitty.";
							}
							
							$firstlayplayer = getAbsolutePlayerNumber($clientInfo);
							
							foreach($allClients as $id)
							{											
								$response = array(
									"action"=>"command",
									"message"=>"newsfeed",
									"data"=> (string)$clientInfo["player"]->Name . " is finished with the kitty, and trump is " . $trumpColor . ". " . $messageString
								);
								
								sendJson($id, $response);
								
								$response = array(
									"action"=>"command",
									"message"=>"beginlay",
									"playernumber"=> (string)$firstlayplayer,
									"data"=>cardsToJsonArray($clientInfo["player"]->Hand)							
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
								if ($card->Suit === Suit::Rook && $this->Rules->NoRookOnFirstTrick && count($round->Tricks) === 1)
								{
									$response = array(
										"action"=>"alert",
										"message"=>"You cannot play the Rook on the first trick"
									);
								
									sendJson($clientInfo["clientId"], $response);
									
									return;
								}	
									
								array_push($trick->CardSet, $card);
								array_push($trick->PlayerOrder, $clientInfo);
								
								removeCardFromHand($clientInfo["clientId"], $clientInfo["player"], $card);
								
								$allClients = getAllClientIdsInGame($clientInfo["game"]);
								$thisPlayerNumber = getAbsolutePlayerNumber($clientInfo);
								
								foreach($allClients as $id)
								{
									$response = array(
										"action"=>"command",
										"message"=>"cardlaid",
										"data"=> array(
											"player"=>$thisPlayerNumber,
											"suit"=>$card->getSuitAsString(),
											"number"=>$card->Number,
											"numberofcardsintrick"=>count($trick->CardSet)
										)
									);
								
									sendJson($id, $response);	
								}
								
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
									$trick->WinningTeam = $winnerInfo["teamNumber"];
									$waitingOn;
									
									if($winnerInfo["teamNumber"] === 1 && $winnerInfo["playerNumber"] === 1)
									{
										$this->State->NextAction = "Team1Player1Lay";
										$waitingOn = 0;
									}
									if($winnerInfo["teamNumber"] === 1 && $winnerInfo["playerNumber"] === 2)
									{
										$this->State->NextAction = "Team1Player2Lay";
										$waitingOn = 2;
									}
									if($winnerInfo["teamNumber"] === 2 && $winnerInfo["playerNumber"] === 1)
									{	
										$this->State->NextAction = "Team2Player1Lay";
										$waitingOn = 1;
									}
									if($winnerInfo["teamNumber"] === 2 && $winnerInfo["playerNumber"] === 2)
									{	
										$this->State->NextAction = "Team2Player2Lay";
										$waitingOn = 3;
									}
									
									$endOfGameInfo = null;
									
									$isLastRound = false;
									if(count($round->Tricks) === 10)
									{
										$isLastRound = true;
										$endOfGameInfo = computeEndOfGameInfo($clientInfo, $winnerInfo);		
										$this->State->NextAction = "ConfirmNextGame";								
									}
									else
									{
										array_push($round->Tricks, new Trick());
									}									
									
									$allClients = getAllClientIdsInGame($clientInfo["game"]);
									
									if(!$isLastRound)
									{
										foreach($allClients as $id)
										{
											if($id === $winnerInfo["clientId"])
											{
												$response = array(
													"action"=>"command",
													"message"=>"gainpermission"
												);
												sendJson($id, $response);
												
												$response = array(
													"action"=>"command",
													"message"=>"setallowedsuits",
													"numberofcardsintrick"=>"0",
													"data"=> array("black", "yellow", "red", "green", "rook")
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
												"action"=>"command",
												"message"=>"newsfeed",
												"data"=>"The trick was won by " . $winnerInfo["player"]->Name . " with the " . $highestCard->toString()
											);
											
											sendJson($id, $response);
											
											$response = array(
												"action"=>"command",
												"message"=>"trickdone",
												"data"=>$waitingOn
											);
											
											sendJson($id, $response);											
										}
									}
									else
									// this was the last trick, the round is over 
									{
										foreach($allClients as $id)
										{											
											$response = array(
												"action"=>"command",
												"message"=>"gainpermission"
											);
											sendJson($id, $response);
												
											$response = array(
												"action"=>"command",
												"message"=>"newsfeed",
												"data"=>"The trick was won by " . $winnerInfo["player"]->Name . " with the " . $highestCard->toString()
											);
											
											sendJson($id, $response);
											
											$response = array(
												"action"=>"command",
												"message"=>"trickdone",
												"data"=>$waitingOn
											);
											
											sendJson($id, $response);
											
											
											$response = array(
												"action"=>"command",
												"message"=>"endgame",
												"data"=>$endOfGameInfo
											);
											
											sendJson($id, $response);
										}
										
										if(!(is_null($endOfGameInfo)) && $endOfGameInfo["gameIsDone"] === "yes")
										{
											$this->DeleteMe = true;											
										}	
									}

								}
								else
								//other players still need to lay
								{
									setNextGameState($clientInfo);
									$allClients = getAllClientIdsInGame($clientInfo["game"]);
									
									$allowedSuits = getAllowedSuitsForNextPlayer($clientInfo);
									
									foreach($allClients as $id)
									{
										if($id === getNextClientId($clientInfo))
										{
											$response = array(
												"action"=>"command",
												"message"=>"gainpermission"
											);
											sendJson($id, $response);	
											
											$response = array(
												"action"=>"command",
												"message"=>"setallowedsuits",
												"data"=>$allowedSuits,
												"numberofcardsintrick"=>count($trick->CardSet)
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
				case "nextgame":
					if ($this->State->NextAction === "ConfirmNextGame")
					{
						$thisGame = $clientInfo["game"];	
						$clientInfo["player"]->NextGameConfirmed = true;
						
						if ($thisGame->Team1->Player1->NextGameConfirmed &&
							$thisGame->Team1->Player2->NextGameConfirmed &&
							$thisGame->Team2->Player1->NextGameConfirmed &&
							$thisGame->Team2->Player2->NextGameConfirmed)
						{
							
							$thisGame->Team1->Player1->HasPassed = false;
							$thisGame->Team1->Player2->HasPassed = false;
							$thisGame->Team2->Player1->HasPassed = false;
							$thisGame->Team2->Player2->HasPassed = false;
							
							$thisGame->Team1->Player1->NextGameConfirmed = false;
							$thisGame->Team1->Player2->NextGameConfirmed = false;
							$thisGame->Team2->Player1->NextGameConfirmed = false;
							$thisGame->Team2->Player2->NextGameConfirmed = false;	
									
							$bidStarter = $thisGame->BidStarter + 1;
							if ($bidStarter === 5)
								$bidStarter = 1;
							
							if ($bidStarter === 1)
								$thisGame->State->NextAction = "Team1Player1Bid";
							else if ($bidStarter === 2)
								$thisGame->State->NextAction = "Team2Player1Bid";
							else if ($bidStarter === 3)
								$thisGame->State->NextAction = "Team1Player2Bid";
							else if ($bidStarter === 4)
								$thisGame->State->NextAction = "Team2Player2Bid";
							
							$thisGame->BidStarter = $bidStarter;	
							
							array_push($thisGame->Rounds, new Round());
										
							$gamePlayers = array(
								$thisGame->Team1->Player1->ClientId,
								$thisGame->Team2->Player1->ClientId,
								$thisGame->Team1->Player2->ClientId,
								$thisGame->Team2->Player2->ClientId		
							);
							
							for($i = 0; $i < 4; $i++)
							{
								$response = array(
									"action"=>"command", 
									"message"=> "resetfornextgame",
								);
								
								sendJson($gamePlayers[$i], $response);	
																	
								$response = array(
									"action"=>"command", 
									"message"=> "losepermission"
								);	
									
								sendJson($gamePlayers[$i], $response);
								
								$response = array(
									"action"=>"command", 
									"message"=> "waitingon",
									"data"=>($bidStarter - 1) 
								);
								
								sendJson($gamePlayers[$i], $response);		
							}
							
							deal($thisGame);
							
							$p1Cards = array();
							$p2Cards = array();
							$p3Cards = array();
							$p4Cards = array();
							$kittyCards = "";
							
							$round = end($thisGame->Rounds);
							
							if ($bidStarter === 1)
								$round->CurrentHighestBidder = $thisGame->Team2->Player2;
							if ($bidStarter === 2)
								$round->CurrentHighestBidder = $thisGame->Team1->Player1;
							if ($bidStarter === 3)
								$round->CurrentHighestBidder = $thisGame->Team2->Player1;
							if ($bidStarter === 4)
								$round->CurrentHighestBidder = $thisGame->Team1->Player2;
							
							foreach($thisGame->Team1->Player1->Hand as $card)
							{
								array_push($p1Cards, array(
									"suit"=>$card->getSuitAsString(),
									"number"=>$card->Number
								));						
							}
							foreach($thisGame->Team1->Player2->Hand as $card)
							{		
								array_push($p2Cards, array(
									"suit"=>$card->getSuitAsString(),
									"number"=>$card->Number
								));	
							
							}
							foreach($thisGame->Team2->Player1->Hand as $card)
							{
								array_push($p3Cards, array(
									"suit"=>$card->getSuitAsString(),
									"number"=>$card->Number
								));
							}
							foreach($thisGame->Team2->Player2->Hand as $card)
							{		
								array_push($p4Cards, array(
									"suit"=>$card->getSuitAsString(),
									"number"=>$card->Number
								));
							}
						
							foreach($round->Kitty as $card)
							{
								$kittyCards = $kittyCards . $card->toString() . ", ";
							}
							
							$response = array(
								"action"=> "command",
								"message"=> "initializecards",
								"data"=>$p1Cards
							);
							
							sendJson($thisGame->Team1->Player1->ClientId, $response);
							
							$response = array(
								"action"=> "command",
								"message"=> "notyourbid",
								"data"=> array(
									"bid"=> $round->Bid,
									"highestbidder"=> $round->CurrentHighestBidder->Name
								)
							);
							
							sendJson($thisGame->Team1->Player1->ClientId, $response);
							
							$response = array(
								"action"=> "command",
								"message"=> "initializecards",
								"data"=>$p2Cards
							);
							
							sendJson($thisGame->Team1->Player2->ClientId, $response);
							
							$response = array(
								"action"=> "command",
								"message"=> "notyourbid",
								"data"=> array(
									"bid"=> $round->Bid,
									"highestbidder"=> $round->CurrentHighestBidder->Name
								)
							);
							
							sendJson($thisGame->Team1->Player2->ClientId, $response);
							
							$response = array(
								"action"=> "command",
								"message"=> "initializecards",
								"data"=>$p3Cards
							);
							
							sendJson($thisGame->Team2->Player1->ClientId, $response);
							
							$response = array(
								"action"=> "command",
								"message"=> "notyourbid",
								"data"=> array(
									"bid"=> $round->Bid,
									"highestbidder"=> $round->CurrentHighestBidder->Name
								)
							);
							
							sendJson($thisGame->Team2->Player1->ClientId, $response);
							
							$response = array(
								"action"=> "command",
								"message"=> "initializecards",
								"data"=>$p4Cards
							);
							
							sendJson($thisGame->Team2->Player2->ClientId, $response);
							
							$response = array(
								"action"=> "command",
								"message"=> "notyourbid",
								"data"=> array(
									"bid"=> $round->Bid,
									"highestbidder"=> $round->CurrentHighestBidder->Name
								)
							);
							
							sendJson($thisGame->Team2->Player2->ClientId, $response);
							
							$response = array(
								"action"=>"command",
								"message"=>"gainpermission"
							);
							
							sendJson($gamePlayers[$bidStarter - 1], $response);
							
							$response = array(
								"action"=>"command",
								"message"=>"yourbid",
								"data"=> array(
									"bid"=> $round->Bid,
									"highestbidder"=> $round->CurrentHighestBidder->Name
								)
							);
							
							sendJson($gamePlayers[$bidStarter - 1], $response);
						}						
					}
			}
		}
	}

	function PrematureEnd($quitId)
	{
		global $gameArray;
							
		if ($this->State->Location === "table")
		{		
			$playerArray = array();		
					
			if($this->Team1 && $this->Team1->Player1)
			{
				array_push($playerArray, $this->Team1->Player1->ClientId);				
			}
			if($this->Team1 && $this->Team1->Player2)
			{
				array_push($playerArray, $this->Team1->Player2->ClientId);				
			}
			if($this->Team2 && $this->Team2->Player1)
			{
				array_push($playerArray, $this->Team2->Player1->ClientId);				
			}
			if($this->Team2 && $this->Team2->Player2)
			{
				array_push($playerArray, $this->Team2->Player2->ClientId);				
			}					
									
			foreach($playerArray as $id)
			{					
				$response = array(
					"action"=>"command",
					"message"=>"abortgame",
					"data"=>""
				);
		
				if ($quitId !== $id && !is_null($id) && $id != "")
				{
					sendJson($id, $response);
				}
				
				$response = array(
					"action"=>"command",
					"message"=>"gainpermission"
				);									
											
				if ($quitId !== $id && !is_null($id) && $id != "")
				{
					sendJson($id, $response);
				}	
			}
			
			if($this->Team1 && $this->Team1->Player1)
			{					
				$this->Team1->Player1 = null;			
			}
			if($this->Team1 && $this->Team1->Player2)
			{				
				$this->Team1->Player2 = null;
			}
			if($this->Team2 && $this->Team2->Player1)
			{
				$this->Team2->Player1 = null;
			}
			if($this->Team2 && $this->Team2->Player2)
			{	
				$this->Team2->Player2 = null;
			}
			
			$this->DeleteMe = true;
			
			$index = array_search($this, $gameArray);
			unset($gameArray[$index]);
		}
		else
		{
			leaveGame($quitId);	
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
			
		$this->ScoreCard = new ScoreCard();		
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
	public $ClientId;
	
	public $Name;
	public $Hand;
	public $HasPassed;
	public $Confirmed;
	public $NextGameConfirmed;
	
	function Player($id)
	{
		$this->ClientId = $id;
		$this->ClientID = $id;
		
		$this->HasPassed = false;
		$this->Hand = array();
		$this->Confirmed = false;
		$this->NextGameConfirmed = false;
		$this->Name = "Player";
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
	public $Team1Score;
	public $Team2Score;
	
	public $TeamBidWinner;
	public $PlayerBidWinner;
	
	// a player object
	public $CurrentHighestBidder;
	
	public $Tricks;
	
	public $Trump;	
	
	public $Kitty;
	
	public $Deck;
	
	function Round()
	{
		$this->Team1Score = 0;
		$this->Team2Score = 0;
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
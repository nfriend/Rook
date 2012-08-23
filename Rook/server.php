<?php
// prevent the server from timing out
set_time_limit(0);

// include the web sockets server script (the server is started at the far bottom of this file)
require 'References.php';

$wsClientNames = array();

// when a client sends data to the server
function wsOnMessage($clientID, $message, $messageLength, $binary) {
	global $Server, $gameArray, $wsClientNames;
	$ip = long2ip( $Server->wsClients[$clientID][6] );
	
	$jsonMessage = json_decode($message, true);
	
	$action = (string) $jsonMessage["action"];
	$data   = $jsonMessage["data"];
	
	switch ($action)
	{
		case "new":			
			
			$response = array(
				"action"=>"log",
				"message"=>"Creating new game..."
			);
			
			sendJson($clientID, $response);
			
			addGame($clientID, $data);
			break;
		case "join":			
			$response = array(
				"action"=>"log",
				"message"=>"Joining a game"
			);
			
			$gameNumber = (int) $data["game"];
			$teamNumber = (int) $data["team"];
			
			sendJson($clientID, $response);
			
			joinGame($clientID, $gameNumber, $teamNumber);
			break;
		case "leave":
			$response = array(
				"action"=>"log",
				"message"=>"Attempting to leave current game."
			);
			
			sendJson($clientID, $response);
			
			leaveGame($clientID);
			
			break;
		case "changename":
			$wsClientNames[$clientID] = (string)$data;	
			
			foreach ( $Server->wsClients as $id => $client )
			{
				if ( $id != $clientID )
				{
					$response = array(
						"action"=>"chat",
						"message"=>(string)$data . " has entered the lobby."
					);
			
					sendJson($id, $response);					
				}
			}
								
			break;
			
		case "confirm":
			confirmClient($clientID);
			break;
			
		case "game":
			
			forwardCommand($clientID, $data);
			
			break;
		case "chat":
			
			chat($clientID, (string)$data);
			break;			
		default:			
			$response = array(
				"action"=>"log",
				"message"=>"Didn't recognize that command."
			);
			
			sendJson($clientID, $response);					
	}

	// check if message length is 0
	if ($messageLength == 0) {
		$Server->wsClose($clientID);
		return;
	}

	//foreach ( $Server->wsClients as $id => $client )
		//$Server->wsSend($id, "Visitor $clientID ($ip) said \"$message\"");
		//$Server->wsSend($id, "Server response: ($responseMessage)");
		//$arrayCount = (string)count($gameArray);
		//$Server->wsSend($id, "number of open games: ($arrayCount)");
}

// when a client connects
function wsOnOpen($clientID)
{
	global $Server;
	$ip = long2ip( $Server->wsClients[$clientID][6] );

	$Server->log( "$ip ($clientID) has connected." );
	
	sendAllOpenGames($clientID);
		
}

// when a client closes or lost connection
function wsOnClose($clientID, $status) {
	global $Server;
	$ip = long2ip( $Server->wsClients[$clientID][6] );
	
	$Server->log( "$ip ($clientID) has disconnected." );
	
	clientDisconnect($clientID);
	
}


// start the server
$gameArray = array();

$Server = new PHPWebSocket();
$Server->bind('message', 'wsOnMessage');
$Server->bind('open', 'wsOnOpen');
$Server->bind('close', 'wsOnClose');
// for other computers to connect, you will probably need to change this to your LAN IP or external IP,
// alternatively use: gethostbyaddr(gethostbyname($_SERVER['SERVER_NAME']))
$Server->wsStartServer('0.0.0.0', 9300);

?>
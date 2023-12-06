<?php 
header("Content-type: application/json; charset=utf-8");
// Scanning tests
// -initial-                                    : {"MAC_ID":"08:3A:8D:15:12:BC", "Status":"scanning"}
// -accepted & entering activity check-         : {"MAC_ID":"08:3A:8D:15:12:BC", "Status":"online"}

// -initial-                                    : {"MAC_ID":"09:3B:8D:15:12:BC", "Status":"scanning"}
// -accepted-                                   : {"MAC_ID":"09:3B:8D:15:12:BC", "Status":"online"}

// Transaction tests
// -init-  with product                         : {"MAC_ID":"08:3A:8D:15:12:BC", "Timestamp":"123456", "Status":"init", "CARD_ID":"511572075"}
// -init-  no product recieve fail when no product , on init 
// -processing-                                 : {"MAC_ID":"08:3A:8D:15:12:BC", "Timestamp":"123456", "Status":"processing"}
// -success-                                    : {"MAC_ID":"08:3A:8D:15:12:BC", "Timestamp":"123456", "Status":"success"}
// -fail-                                       : {"MAC_ID":"08:3A:8D:15:12:BC", "Timestamp":"123456", "Status":"fail"}

// -init-                                       : {"MAC_ID":"09:3B:8D:15:12:BC", "Timestamp":"123456", "Status":"init", "CARD_ID":"511572075"}
// -processing-                                 : {"MAC_ID":"09:3B:8D:15:12:BC", "Timestamp":"123456", "Status":"processing"}
// -success-                                    : {"MAC_ID":"09:3B:8D:15:12:BC", "Timestamp":"123456", "Status":"success"}
// -fail-                                       : {"MAC_ID":"09:3B:8D:15:12:BC", "Timestamp":"123456", "Status":"fail"}

// SOCKETS LOGIC
$host                   = "10.109.254.38";
$port                   = 2222;
$null                   = NULL;

if(($sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) === false) {
    echo "Couldn't create socket " . socket_strerror(socket_last_error()). "\n";
}
if(socket_bind($sock, $host, $port) === false) {
    echo "Bind error " . socket_strerror(socket_last_error($sock)) ."\n";
}

if(socket_listen($sock) === false) {
    echo "Listen Failed ".socket_strerror(socket_last_error($socket)) . "\n";
}

echo "Listening for new connections on port {$port} \n"; 

$members                    = [];
$connections                = [];
$connections[]              = $sock;
set_time_limit(0);
ob_implicit_flush();

// PROJECT LOGIC
// global properties
$username                   = "app";
$password                   = "sau03magen";
$database                   = "";
$master_host                = "";

$mac_verification           = false;
$command                    = false;
$customerId                 = false;
$currentLocationId          = false;
$priceAndStock              = false;
$productPrice               = false;
$productStock               = false;
$slaveOrMaster              = false;
$masterConnection           = false;

$dataForClient              = [];
$storedTransactions         = [];
$transactionId              = false;
$transaction_mac            = false;
$transaction_operation_id   = false;
$transaction_status         = false;
$transaction_card_id        = false;

function Q(&$con,$q)
{
	try
	{
		$res = mysqli_query($con,$q);
	}
	catch(Exception $e)
	{
		$SQL_ERR=$e->getMessage();
		echo "QUERIUL $q a DAT: $SQL_ERR<br>\n";
        $res = "Ok";
	}
	return $res;
}

$localConnection    = mysqli_connect('127.0.0.1', $username, $password, $database) or die ("Cannot connect to the database");
$checkIfSlave       = Q($localConnection,"SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = 'MasterOnlyDB'");
$checkIfSlave       = mysqli_fetch_assoc($checkIfSlave);
$slaveOrMaster      = $checkIfSlave['SCHEMA_NAME'];

if($slaveOrMaster) {
    $isSlave = true;
    $masterConnection = $localConnection;
    // echo "master"; 
} else {
    $isSlave = false;
    echo "slave";
    $get_master_ip = Q($localConnection,"SELECT * FROM Mystery.MasterIP WHERE ServerType='Master';");
    $get_master_ip = mysqli_fetch_assoc($get_master_ip);
    $master_host = $get_master_ip['IP'];
    $masterConnection = mysqli_connect($master_host, $username, $password, $database) or die ("Cannot connect to the database"); 
}

class Transaction {
    public $mac;
    public $operationId;
    public $status;
    public $cardId;

    function __construct($mac, $operationId, $status, $cardId) {
        $this->mac          = $mac;
        $this->operationId  = $operationId;
        $this->status       = $status;
        $this->cardId       = $cardId;
    }
}

while(true) { 
      $reads = $connections;
      $writes = $exceptions = $null;

      socket_select($reads, $writes, $exceptions, 0);

      if(in_array($sock, $reads)) {
        if(($new_connection = socket_accept($sock)) === false){
            echo "Error: socket_accept: " . socket_strerror(socket_last_error($sock)) . "\n";
            break;
        }

        $connections[] = $new_connection;
        $reply = "connected to the socket server \n";
        socket_write($new_connection, $reply, strlen($reply));
        $sock_index = array_search($sock, $reads);
        unset($reads[$sock_index]);
        
      }
      foreach($reads as $deviceOrder => $device) {
        if(($data = socket_read($device, 4096)) === false) {
            echo "Socket read error: ".socket_strerror(socket_last_error($msgsock)) . "\n";
            break;
        }

        if(!empty($data)) {
            var_dump($data);
            echo "message printed";
            //data contains some message                                            // Ignore - functionality to write to all connected clients at once for future reference
                                                                                    // foreach($connections as $ckey => $cvalue) {
                                                                                    //  if($ckey === 0) continue;
                                                                                    //  socket_write($cvalue, $data, strlen($data));
                                                                                    // }
            //respond back to the same person who sent message to the server
            // if(str_contains($data, "{")){ 
                
            //     $jsToArray = json_decode($data, true);
            //     echo "\n\n-----------------------------------------------------------------------";
            //     echo "\n-------------------------------- START --------------------------------\n";
            //     echo "                                " . $jsToArray['Status'];
            //     echo "\n-----------------------------MESSAGE BEGIN-----------------------------\n";
            //     var_dump($jsToArray);
            //     echo "-----------------------------MESSAGE ENDED-----------------------------\n";

            //     if(isset($jsToArray["MAC_ID"])) {
            //         //If-ul asta atat de mare implica sa primesc mac-ul de fiecare data inainte sa fac ceva
            //         $mac = $jsToArray["MAC_ID"];
            //                                                                         echo "Mac: " . $mac . "\n";
            //         $mac_verification = Q($localConnection,"SELECT * FROM WineDispenser.cigarette_dispensers WHERE Mac='$mac';");
            //         $mac_verification = mysqli_fetch_assoc($mac_verification);
            //         if(empty($mac_verification)) {
            //             //insert mac and no reply | mac already inserted
            //                                                                         echo "it wasn't inserted already \n";
            //             $insert_log = Q($localConnection,"INSERT INTO WineDispenser.`cigarette_dispensers`(`Mac`, `Command`, `Status`, `Assigned`) 
            //                                                 VALUES ('$mac', 'unlock', 'unavailable', 'false');");
            //         } else {
            //                                                                         echo "it was inserted already \n";
            //             $command   = $mac_verification['Command'];
            //             $status    = $mac_verification['Status'];
            //             $assigned  = $mac_verification['Assigned'];
            //             $productId = $mac_verification['ProductId'];

            //             // trimit mesaj catre byulent ( pregatesc array-ul pentru a trimite mesaaj catre byulent)
            //             // sending standard message with status depending on the customer card points
            //             $dataForClient['Mac']       = $mac;

            //             if(isset($jsToArray['Timestamp'])) {
            //                 $transactionId = $jsToArray['Timestamp'];
            //                 $dataForClient['Timestamp'] = $transactionId;
            //             }

            //             if($assigned == "true" && ( isset($jsToArray["Status"]) && ( $jsToArray["Status"] == 'scanning') || $jsToArray["Status"] == 'online' ) ) {
            //                 // TODO2
            //                 // write accept, if assigned and message contains scanning, turn the device to available and than find a way to exit the following execution
            //                 // there's not real need to exit execution as there's nothing to be executed after this block due to the conditions matching
                            
            //                 if($jsToArray["Status"] == 'scanning') {
            //                     $dataForClient['Status'] = 'accept';
            //                     $update_dispenser_details = Q($localConnection," UPDATE WineDispenser.`cigarette_dispensers` SET `Status`='available'  WHERE `Mac` = '$mac';");
            //                 } else {
            //                     $dataForClient['Status'] = 'continue';
            //                 }

            //                 $dataForClient = json_encode($dataForClient);
            //                 socket_write($device, $dataForClient, strlen($dataForClient));
            //             }
            //                                                                         echo("Command: " . $command  . "\n");
            //                                                                         echo('Status: '  . $status   . "\n");
            //                                                                         echo('Assigned: '. $assigned . "\n");
            //             if(( isset($assigned) && $assigned == "true") && (isset($status) && $status == "available") && (isset($command) && $command = "unlock") && isset($productId)) {
            //                 if( isset($jsToArray["Status"]) && ($jsToArray["Status"] == 'init')) {
            //                     if( isset($jsToArray["CARD_ID"]) ) {
            //                         $cardId = $jsToArray["CARD_ID"];
            //                         $card_id_verification = Q($localConnection,"SELECT `CustomerID` FROM `PlayerTracking`.`CustomerCards` WHERE `CardID`=$cardId;");
            //                         $card_id_verification = mysqli_fetch_assoc($card_id_verification);
            //                         if(isset($card_id_verification['CustomerID'])) {
            //                             //If the card has a customerID attached to it we function, otherwise we don't
            //                             $customerId = $card_id_verification['CustomerID'];
            //                                                                         echo "customer id is: " . $customerId . "\n";
            //                             $priceAndStock = Q($localConnection,"SELECT `CostPoints`,`Stock` FROM WineDispenser.products_list WHERE Id=$productId;");
            //                             $priceAndStock = mysqli_fetch_assoc($priceAndStock);
            //                             $productStock = $priceAndStock['Stock'];
            //                             $productPrice = $priceAndStock['CostPoints'];
            //                                                                         echo('productStock is: ' . $productStock) . "\n";
            //                                                                         echo('productPrice is: ' . $productPrice) . "\n";
            //                             // se aplica daca punctele de pe local sunt > decat product CostPoints si daca produsul are stock
            //                             // pot verifica daca mai intai are stoc, apoi iau punctele de pe serverul local, si verific daca sunt mai mari decat pretul, apoi fac callul
            //                             if( $productStock > 0 ) {
            //                                 //LOCAL POINTS
            //                                 //TODO4 , add write messages on all cases, and correct transaction logs, we need many transactions per mac!
            //                                 $local_server_points = Q($localConnection,"SELECT `b1` FROM PlayerTracking.benefits WHERE player_id=$customerId;");
            //                                 $local_server_points = mysqli_fetch_assoc($local_server_points);
            //                                                                         echo "local server points are: " . $local_server_points['b1'];
            //                                 //PANA Nu stiu ca e cardu bun, nu fac operatiuni, blocul cu locationid a fost mutat pentru a oprii functionalitatea cat timp nu e cardu bun
            //                                 //Get current location ID, to be used FOR SUM of points of all servers except the local server
            //                                 $get_current_location_id = Q($localConnection,"SELECT * FROM `PlayerTracking`.`location` LIMIT 1;");
            //                                 $get_current_location_id = mysqli_fetch_assoc($get_current_location_id);
            //                                 $currentLocationId = $get_current_location_id['id'];
            //                                                                         echo "\ncurrent location id is: " . $currentLocationId . "\n";
            //                                 // SIBLING SERVERS POINTS
            //                                 $sibling_servers_points = Q($masterConnection,"SELECT SUM(`b1`) as `b1` FROM `MasterOnlyDB`.`GlobalBenefits` WHERE `player_id` = '$customerId' AND `location_id` != '$currentLocationId';");
            //                                 $sibling_servers_points = mysqli_fetch_assoc($sibling_servers_points);
            //                                                                         echo "sibling servers points are: " . $sibling_servers_points['b1'];
            //                                 $totalPoints = $local_server_points['b1'] + $sibling_servers_points['b1'];
            //                                                                         echo "\ntotal servers points are: " . $totalPoints;
            //                                 if($totalPoints >= $productPrice) {
            //                                     //trimit ok, updatez tranzactia, si mai tarziu extrag
            //                                     $dataForClient['Status']    = 'start';
            //                                     //Opening transaction
            //                                     $storedTransactions[$deviceOrder] = new Transaction($mac, $transactionId, 'initialized', $cardId);
            //                                                                         echo "\nTransaction status: " . $storedTransactions[$deviceOrder]->status . "\n";
            //                                                                         echo "customer can buy a cigarette! \n";
            //                                     //apoi inserez in log-uri
            //                                     $insert_log = Q($localConnection,"INSERT IGNORE INTO WineDispenser.`cigarette_dispensers_transactions`(`Mac`, `OperationId`, `Status`, `CardId`) 
            //                                                                     VALUES ('$mac', '$transactionId', 'initialized', '$cardId');");
            //                                 } else {
            //                                     $dataForClient['Status']    = 'fail';
            //                                 }
            //                             } else {
            //                                 $dataForClient['Status']    = 'fail';
            //                             }
            //                         } else {
            //                             //Card Id Invalid - No customerID on this CardID
            //                             $dataForClient['Status'] = 'fail';
            //                                                                         echo "invalid card id!" . "\n";
            //                         }

            //                         $dataForClient = json_encode($dataForClient);
            //                         socket_write($device, $dataForClient, strlen($dataForClient));
            //                     } else {
            //                         //nu am primit cardID
            //                         $dataForClient['Status']    = 'fail';
            //                         $dataForClient = json_encode($dataForClient);
            //                         socket_write($device, $dataForClient, strlen($dataForClient));
            //                     }
            //                 } elseif( isset($jsToArray["Status"]) && ($jsToArray["Status"] == 'processing') ) {
            //                     //processing status is always replied with continue by the server
            //                     $dataForClient['Status'] = 'continue';
            //                     $dataForClient = json_encode($dataForClient);
            //                     socket_write($device, $dataForClient, strlen($dataForClient));
            //                     if(isset($storedTransactions[$deviceOrder])) {
            //                         $storedTransactions[$deviceOrder]->status = "processing";



            //                         $transaction_mac            = $storedTransactions[$deviceOrder]->mac;
            //                         $transaction_operation_id   = $storedTransactions[$deviceOrder]->operationId;
            //                         $transaction_status         = $storedTransactions[$deviceOrder]->status;
            //                         $transaction_card_id        = $storedTransactions[$deviceOrder]->cardId;



            //                         $insert_log = Q($localConnection,"INSERT IGNORE INTO WineDispenser.`cigarette_dispensers_transactions`(`Mac`, `OperationId`, `Status`, `CardId`) 
            //                         VALUES ('$transaction_mac', '$transaction_operation_id', '$transaction_status', '$transaction_card_id');"); 
            //                     }
            //                 } elseif( isset($jsToArray["Status"]) && ($jsToArray["Status"] == 'success') ) {
            //                     // fac log de success, nu intorc nimic
            //                     if(isset($storedTransactions[$deviceOrder])) {
            //                         $storedTransactions[$deviceOrder]->status = "success";
            //                         $transaction_mac            = $storedTransactions[$deviceOrder]->mac;
            //                         $transaction_operation_id   = $storedTransactions[$deviceOrder]->operationId;
            //                         $transaction_status         = $storedTransactions[$deviceOrder]->status;
            //                         $transaction_card_id        = $storedTransactions[$deviceOrder]->cardId;

            //                         $insert_log = Q($localConnection,"INSERT IGNORE INTO WineDispenser.`cigarette_dispensers_transactions`(`Mac`, `OperationId`, `Status`, `CardId`) 
            //                         VALUES ('$transaction_mac', '$transaction_operation_id', '$transaction_status', '$transaction_card_id');");
            //                         $extract_points = Q($localConnection,"CALL `PlayerTracking`.`AddRemovePlayerPoints`('$productPrice', '$customerId', 'Remove','');");
            //                                                                         echo "customer has bought a cigarette! \n";
            //                     }
            //                 } elseif( isset($jsToArray["Status"]) && ($jsToArray["Status"] == 'fail') ) {
            //                     // fac log de fail, nu intorc nimic
            //                     if(isset($storedTransactions[$deviceOrder])) {
            //                         $storedTransactions[$deviceOrder]->status = "fail";
            //                         $transaction_mac            = $storedTransactions[$deviceOrder]->mac;
            //                         $transaction_operation_id   = $storedTransactions[$deviceOrder]->operationId;
            //                         $transaction_status         = $storedTransactions[$deviceOrder]->status;
            //                         $transaction_card_id        = $storedTransactions[$deviceOrder]->cardId;
            //                         $insert_log = Q($localConnection,"INSERT IGNORE INTO WineDispenser.`cigarette_dispensers_transactions`(`Mac`, `OperationId`, `Status`, `CardId`) 
            //                         VALUES ('$transaction_mac', '$transaction_operation_id', '$transaction_status', '$transaction_card_id');");                
            //                     }
            //                 }
            //             } else {
            //                 //device can give cigarette as it is available , unlocked, assigned has product
            //                 //sending standard message + start status 
            //                 echo "Commented write";
            //                 // $dataForClient['Status'] = 'fail';
            //                 // $dataForClient = json_encode($dataForClient);
            //                 // socket_write($device, $dataForClient, strlen($dataForClient));
            //             }
            //         }    
            //     } else {
            //         //NO MAC
            //         $dataForClient['Status']    = 'fail';
            //         $dataForClient = json_encode($dataForClient);
            //         socket_write($device, $dataForClient, strlen($dataForClient));
            //     }
            // } else {
            //     //Strings that are not json will enter here
            //     echo $data;
            // }
            if(isset($dataForClient)) {
                unset($dataForClient);
            }
            sleep(2);
            echo "--------------------------------- END ---------------------------------\n";
            echo "-----------------------------------------------------------------------\n\n";
            // socket_write($device, $data, strlen($data));
        }
        // } else if($data === '') {
        //     //it's a connection close request, so data is empty
        //     echo "disconnecting client $deviceOrder \n";
        //     unset($connections[$deviceOrder]);
        //     socket_close($device);
        // }
      }
}
socket_close($sock);
?>
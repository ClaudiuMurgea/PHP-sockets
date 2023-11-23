<?php 
header("Content-type: application/json; charset=utf-8");
// forTesting : {"MAC_ID":"08:3A:8D:15:12:BC","CARD_ID":"1925130166"}

//TO DO, to send a json and recieve the cost points, than send a different json and recieve a different cost points

// SOCKETS LOGIC
$host = "127.0.0.1";
$port = 3333;
$null = NULL;

if(($sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) === false) {
    echo "Couldn't create socket ".socket_strerror(socket_last_error()). "\n";
}
if(socket_bind($sock, $host, $port) === false) {
    echo "Bind error ".socket_strerror(socket_last_error($sock)) ."\n";
}

if(socket_listen($sock) === false) {
    echo "Listen Failed ".socket_strerror(socket_last_error($socket)) . "\n";
}

echo "Listening for new connections on port {$port} \n"; 

$members            = [];
$connections        = [];
$connections[]      = $sock;
set_time_limit(0);
ob_implicit_flush();

// PROJECT LOGIC
// global properties
$username       = "app";
$password       = "sau03magen";
$database       = "";
$master_host    = "10.109.254.38";

$mac_verification   = false;
$response           = false;
$command            = false;
$customerId         = false;
$currentLocationId  = false;
$productPrice       = false;
$masterConnection   = ""; 
$slaveOrMaster      = false;

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

class Transaction {
    public $mac;
    public $operationId;
    public $status;
    public $cardId;

    function __construct($mac, $operationId, $status, $cardId) {
        $this->operationId = $operationId;
        $this->status = $status;
        $this->cardId = $cardId;
    }

    function logger() {
        //
    }
}

$localConnection    = mysqli_connect('10.109.254.38', $username, $password, $database) or die ("Cannot connect to the database");
$checkIfSlave       = Q($localConnection,"SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = 'MasterOnlyDB'");
$checkIfSlave       = mysqli_fetch_assoc($checkIfSlave);
$slaveOrMaster      = $checkIfSlave['SCHEMA_NAME'];

if($slaveOrMaster) {
    $isSlave = true;
    $masterConnection = $localConnection;
    echo "master"; 
} else {
    $isSlave = false;
    echo "slave";
    $get_master_ip = Q($localConnection,"SELECT * FROM Mystery.MasterIP WHERE ServerType='Master';");
    $get_master_ip = mysqli_fetch_assoc($get_master_ip);
    $master_host = $get_master_ip['IP'];
    $masterConnection = mysqli_connect($master_host, $username, $password, $database) or die ("Cannot connect to the database"); 
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
            //data contains some message
            //write to all connected clients
            // foreach($connections as $ckey => $cvalue) {
            //     if($ckey === 0) continue;
                // socket_write($cvalue, $data, strlen($data));
            // }

            
            //prima oara verific mesajul sa aibe toate datele, dupa care instantiez intr-un singur array tranzactiile per $device, de ex: $transactions[$device] = new Transaction($mac, $operationId, $status, $cardId)

            //respond back to the same person who sent message to the server
            if(str_contains($data, "{")){ 
                // CORRECT JSON '{"Card_ID":"EB A2 EA 56","MAC_ID":"08:3A:8D:15:12:BC","OPER_ID":"no"}'
                $jsToArray = json_decode($data, true);
                echo "here we go";
                var_dump($jsToArray);
                if(isset($jsToArray["MAC_ID"])) {
                    //If-ul asta atat de mare implica sa primesc mac-ul de fiecare data inainte sa fac ceva
    
                    $mac = $jsToArray["MAC_ID"];
                    //verific daca mac-ul are status available sau command is locked
                    echo "before checking given mac: ";
                    
                    // $getMasterIp = Q($localConnection,"SELECT * FROM Mystery.MasterIP WHERE ServerType='Master';");
                    echo $mac;
                    //TODO, GET ON LOCAL CONNECTION THE MAC
                    $mac_verification = Q($localConnection,"SELECT * FROM WineDispenser.cigarette_dispensers WHERE Mac='$mac';");
                    echo "after";
                    $mac_verification = mysqli_fetch_assoc($mac_verification);
                    $command   = $mac_verification['Command'];
                    $status    = $mac_verification['Status'];
                    $assigned  = $mac_verification['Assigned'];
                    $productId = $mac_verification['ProductId'];
    
                    echo($command);
                    echo($status);
                    echo($assigned);
    
                    if((isset($assigned) && $assigned == "True") && (isset($status) && $status == "Available")) {
                        socket_write($device, 'Accepted', strlen('Accepted'));
                        //If it is assigned true, I should write to the device
    
                        if(isset($command) && $command = "Unlock") {
                            echo "tuto bene";
                      
                            if(isset($jsToArray["CARD_ID"]) && isset($productId)) {
                    
                                $productPrice = Q($localConnection,"SELECT `CostPoints` FROM WineDispenser.products_list WHERE Id=$productId;");
                                $productPrice = mysqli_fetch_assoc($productPrice);
                                $productPrice = $productPrice['CostPoints'];
                                //atunci exista produs, altfel e null
                                echo('productPrice is :' . $productPrice);
                                $cardId = $jsToArray["CARD_ID"];
                                
                                $card_id_verification = Q($localConnection,"SELECT * FROM `PlayerTracking`.`CustomerCards` WHERE `CardID`=$cardId;");
                                $card_id_verification = mysqli_fetch_assoc($card_id_verification);
                                $customerId = $card_id_verification['CustomerID'];
                       
                                echo "customer id is: ";
                                echo $customerId;
    
                                $get_current_location_id = Q($localConnection,"SELECT * FROM `PlayerTracking`.`location` LIMIT 1;");
                                $get_current_location_id = mysqli_fetch_assoc($get_current_location_id);
                                $currentLocationId = $get_current_location_id['id'];
                                
                                echo "current location id is: ";
                                echo $currentLocationId;
                                 
    
                                // de aici verific pe toate punctele pe toate loc
                                // apelez daca are sum-ul de puncte suficiente pcte, 
                                // trimit mesaj catre byulent
    
    
    
                                // SELECT ( SUM(`b1`)+SUM(`b2`)+SUM(`b3`)+SUM(`b4`) ) FROM GlobalBenefits WHERE `player_id` = 2;
    
    
                                // dupa ce iau suma tuturor punctelor, daca suma este mai mare decat costul unei tigari, scad punctele. sau scad direct si trimit ocheiul
                                // punctele se scad de pe slave
                                // direct pe slave scad pretul tigarii
    
                                
    
                                // masterConnection, get sum of points per all locations
                            } else {
                                //no card id
                            }
                        } else {
                            //locked 
                        }
                        //extract points
    
                    } else {
                        //not available
                    }
                    
    
                } else {
                    //problem
                }
    
                if(isset($jsToArray['Card_ID'])) {
                    $cardId = $jsToArray['Card_ID'];
                }
    
                // echo $jsToArray['Card_ID'];
            } else {
                //Strings that are not json will enter here
                echo $data;
            }
            socket_write($device, $data, strlen($data));
        } else if($data === '') {
            //it's a connection close request, so data is empty

            echo "disconnecting client $deviceOrder \n";
            unset($connections[$deviceOrder]);
            socket_close($device);
        }
      }
}

socket_close($sock);
?>
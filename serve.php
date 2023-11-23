<?php 
// forTesting : {"MAC_ID":"08:3A:8D:15:12:BC","CARD_ID":"1925130166"}
// Sockets Logic -----------------------------------------------------------
header("Content-type: application/json; charset=utf-8");
$host         = "127.0.0.1"; //10.109.254.38 ar trebui sa fie pe server
$master_host  = "10.109.254.38";
$port         = 2222;

//Allow the script to hang around waiting for connections
set_time_limit(0);

//Turn on implicit output flushing so e see what we're getting as it comes in
ob_implicit_flush(); 

echo "Listening for connections";
// --------------------------------------------------------------------------

// Database connection ------------------------------------------------------
$username = "app";
$password = "sau03magen";
$database = "";
//We assume that we are located on a Slave server.
$isSlave            = true;
$masterConnection   = ""; 
$slaveOrMaster      = false;
$localConnection    = mysqli_connect('10.109.254.38', $username, $password, $database) or die ("Cannot connect to the database");

// Communication attributes
$cardId             = false;
$mac                = false;
$mac_verification   = false;
$response           = false;
$status             = false;
$command            = false;
$customerId         = false;
$currentLocationId  = false;
$productPrice       = false;

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

$checkIfSlave       = Q($localConnection,"SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = 'MasterOnlyDB'");

foreach($checkIfSlave as $index) {
    foreach($index as $columnName => $columnValue) {
        $slaveOrMaster = $columnValue;
    }
}

if($slaveOrMaster) {
    $isSlave = true;
    $masterConnection = $localConnection;
    // echo "master"; 
} else {
    $isSlave = false;
    // echo "slave";
    $get_master_ip = Q($localConnection,"SELECT * FROM Mystery.MasterIP WHERE ServerType='Master';");
    $get_master_ip = mysqli_fetch_assoc($get_master_ip);
    $master_host = $get_master_ip['IP'];
    $masterConnection = mysqli_connect($master_host, $username, $password, $database) or die ("Cannot connect to the database"); 
}
// --------------------------------------------------------------------------

    //Planification

    //Part 1 - Card and mac recognition + points subtraction per ciggarette
    //Check if mac is locked or not
    //Check the price of the cigarette
    //Check if server is slave or master 
    //Get the points based on cardID from b1 benefits
    // Aici o sa trebuiasca sa fac sum cu toate punctele de pe toate locatiile, si daca are puncte
    //scad de pe slave, chiar daca apare pe minus
    //Send yes if it has points
    //Send no if it has no points

    //Part 2 - Commands + User interface on website
    //Un meniu separat pentru Ciggarette Dispenser

    // $jsToArray = '{"Card_ID":"EB A2 EA 56","MAC_ID":"08:3A:8D:15:12:BC","OPER_ID":"no"}';


    //Planification rmk
    //mac is registered locally when server is found during scanning                                                 
    //mac will continue scanning but i'm not adding (registering it) anymore if it was already registered
    //if the user accepts the device online on the website
    //the server on that location will tell the mac to stop scanning based on the mac and accepted columns.
    //if a user scans the card reader, i will check if there's a product linked to the mac with stock higher than 0
        //cases 
            // case THERE IS NOT
            // in this case we will tell the mac that he shouldnt give cigarette

            //case THERE IS
            // in this case we get the price, and we tell the mac that we can give cigarette

    //when the mac says that the cigarette has been given, than we check again the price in order to extract the points of the user by the cost "price" of a cigarette
    //blockers 1. = can i give the price to byulent after i check if the mac is ok and product linked to it, so he can tell me that he printed and give me the price back
    //         2. = procedura care extrage un anumit numar de puncte de la un anumit player  

try {
    // nu am urcat pe server inca setarea asta
    // socket_set_nonblock ($sock);

    $sock   = socket_create(AF_INET, SOCK_STREAM, 0);
    $result = socket_bind($sock, $host, $port); 
    $result = socket_listen($sock, 3);
    $client = socket_accept($sock);

    while(true) {
        $msg = socket_read($client,4096) or die("Cannot read from socket");
        $msg = trim($msg);

        if(str_contains($msg, "{")){ 
            // CORRECT JSON '{"Card_ID":"EB A2 EA 56","MAC_ID":"08:3A:8D:15:12:BC","OPER_ID":"no"}'
            $jsToArray = json_decode($msg, true);
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
                    socket_write($client, 'Accepted', strlen('Accepted'));
                    //If it is assigned true, I should write to the device

                    if(isset($command) && $command = "Unlock") {
                        echo "tuto bene";
                  
                        echo('productPrice is :' . $productPrice);
                        if(isset($jsToArray["CARD_ID"]) && isset($productId)) {
                
                            $productPrice = Q($localConnection,"SELECT `CostPoints` FROM WineDispenser.products_list WHERE Id=$productId;");
                            $productPrice = mysqli_fetch_assoc($productPrice);
                            $productPrice = $productPrice['CostPoints'];
                            //atunci exista produs, altfel e null

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
            echo $msg;
        }
        
        unset($msg);
        // socket_write($client, $msg, strlen($msg));
        // socket_write($accept, $msg);
    } 

} catch (Exception $e) {
    echo "error $e";
}

socket_close($client);

?>

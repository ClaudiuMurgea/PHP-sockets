<?php
// =========================================================================================================
// DATABASE DEPENDENCIES
// =========================================================================================================
header('Access-Control-Allow-Origin: *');
$dHost = "127.0.0.1";
$dUsername = "app";
$dPassword = "sau03magen";
$dDatabase = "";

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
	}
	return $res;
}

while(true)
{
	sleep(2);
	echo('Ok');
	
	$dyanmicConnection = mysqli_connect($dHost, $dUsername, $dPassword, $dDatabase) or die ("Cannot connect to the database"); 
	$slaveConnection = mysqli_connect($dHost, $dUsername, $dPassword, $dDatabase) or die ("Cannot connect to the database"); 


    $slaveOrMaster = false;
    $checkIfSlave = Q($dyanmicConnection,"SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = 'MasterOnlyDB'");
    	
    foreach($checkIfSlave as $index) {
        foreach($index as $columnName => $columnValue) {
            $slaveOrMaster = $columnValue;
        }
    }

    // Getting all bets from MultigroupBonusLocal.deviceLastBet
	$allLocalBets = Q($dyanmicConnection,"SELECT * FROM MultigroupBonusLocal.deviceLastBet");
    $betRowsCount = $allLocalBets->num_rows;

    if($slaveOrMaster) {
        echo "master"; 
    } else {
        $getMasterIp = Q($dyanmicConnection,"SELECT * FROM Mystery.MasterIP WHERE ServerType='Master';");
        foreach($getMasterIp as $index => $value) {
            $masterIp = $value['IP'];
        }
        
        $dyanmicConnection = mysqli_connect($masterIp, $dUsername, $dPassword, $dDatabase) or die ("Cannot connect to the database"); 
    }

    if($betRowsCount) {
        foreach($allLocalBets as $index => $value) {
            $lastBetDeviceId        = $value['DeviceId'];
            $lastBetLocationId      = $value['locationId'];
            $lastBetEventId         = $value['eventId'];
            $lastBetLastBetValue    = $value['LastBetValue'];
            $lastBetLastBetTime     = $value['LastBetTime'];
            $game     	 	    = $value['LastBetGame'];
            $lastBetPlayerId        = $value['playerId'];
            $lastBetCardColor       = $value['cardColor'];
    
            $pushBets = Q($dyanmicConnection,
            "INSERT INTO MultigroupBonusGlobal.deviceBet(DeviceId, locationId, eventId, LastBetValue, LastBetTime, LastBetGame, playerId, cardColor)
                VALUES('$lastBetDeviceId','$lastBetLocationId','$lastBetEventId','$lastBetLastBetValue', '$lastBetLastBetTime', '$game', '$lastBetPlayerId', '$lastBetCardColor')");
    
            if($index == $betRowsCount - 1) {
                $lastRowId = $value['id'];
                
                if($slaveOrMaster) {
                    $deleteSentBets = Q($dyanmicConnection, "DELETE FROM MultigroupBonusLocal.`deviceLastBet` WHERE id<=$lastRowId;");
                } else {
                    $deleteSentBets = Q($slaveConnection, "DELETE FROM MultigroupBonusLocal.`deviceLastBet` WHERE id<=$lastRowId;");
                }
            }
        }
    }

    $endTime = false;
    $getEndTime = Q($dyanmicConnection,"SELECT `eventEnd` FROM MultigroupBonusLocal.eventSettings LIMIT 1;");
    foreach($getEndTime as $index => $value) {
        $endTime = $value['eventEnd'];
    }

    $now = date("Y-m-d h:i:s");

    if($now > $endTime) {
        $stopEvent = Q($slaveConnection, "DELETE FROM MultigroupBonusLocal.`eventSettings` WHERE eventEnd < '$now';");
    }

    $getTransfer = Q($slaveConnection,"SELECT `hitId`, `status`, `Info` FROM MultigroupBonusLocal.transferStatusRequest;");
    
    foreach($getTransfer as $index => $value) {
        $transferHitId = $value['hitId'];
        $transferStatus = $value['status'];
        $transferInfo = $value['Info'];
        echo($transferInfo);

        $pushHitHistory = Q($dyanmicConnection,
        "UPDATE MultigroupBonusGlobal.HistoryHits SET `hitStatus` = '$transferStatus', `hitInfo` = '$transferInfo' WHERE `id` = $transferHitId");
    }
}
?>



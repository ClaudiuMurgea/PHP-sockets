<?php
// =========================================================================================================
// DATABASE DEPENDENCIES
// =========================================================================================================
header('Access-Control-Allow-Origin: *');
$masterHost = "127.0.0.1";
$masterUsername = "app";
$masterPassword = "sau03magen";
$masterDatabase = "MultigroupBonusGlobal";
$dynamicDatabase = "";

$futureSettingsParticipation     = false;
$futureSettingsEventId		 = false;
$futureSettingsEventName 	 = false;
$futureSettingsEventStart	 = false;
$futureSettingsEventEnd 	 = false;

$jpLocationId	= false;
$jpLocationName	= false;
$jpIp 		= false;
$jpHitId	= false;
$jpDeviceId 	= false;
$jpValue 	= false;
$jpTransferType = false;
$jpLock 	= false;
$jpId 		= false;


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
	
	$con1 = mysqli_connect($masterHost, $masterUsername, $masterPassword, $masterDatabase) or die ("Cannot connect to the database"); 
	$con2 = mysqli_connect($masterHost, $masterUsername, $masterPassword, $dynamicDatabase) or die ("Cannot connect to the database"); 

	//Getting only one row from MultigroupBonusGlobal.FutureSettingsEvent
	$futureSettingsEvent = Q($con1,"SELECT `eventId`,`eventName`,`eventStart`,
	`eventEnd`, `stopMystery` FROM FutureSettingsEvent 
	WHERE NOW() >=`eventStart` AND NOW()<=`eventEnd` ORDER BY `eventStart`");

	if($futureSettingsEvent->num_rows) {
	
		foreach($futureSettingsEvent as $index => $value) {
			$futureSettingsEventId			 = $value['eventId'];
			$futureSettingsEventName 		 = $value['eventName'];
			$futureSettingsEventStart		 = $value['eventStart'];
			$futureSettingsEventEnd 		 = $value['eventEnd'];
			$futureSettingsMystery		 	 = $value['stopMystery'];
		

		$futureSettingsParticipation = Q($con1,"SELECT * FROM FutureSettingsParticipation WHERE eventId='$futureSettingsEventId';");
		$masterIpList = Q($con2,"SELECT * FROM Mystery.MasterIP;");


			if($futureSettingsParticipation && $futureSettingsEventId) {
				foreach($futureSettingsParticipation as $index => $value) {
					if($futureSettingsEventId == $value['eventId']) {
						foreach($masterIpList as $index2 => $value2) {
							if($value['locationId'] == $value2['LocationID']) {
								$dynamicConnection = mysqli_connect($value2['IP'], $masterUsername, $masterPassword, $dynamicDatabase) or die("Cannot"); 
								
								try
									{
										$locationId 		= $value['locationId'];
										$locationName		= $value['locationName'];
										$gamingMachineList 	= $value['gamingMachineList'];

										$pushNotifications = Q($dynamicConnection,
										"INSERT INTO MultigroupBonusLocal.eventSettings(eventId, eventName, eventStart, eventEnd, locationId, locationName, gamingMachineList, stopMystery)
											VALUES($futureSettingsEventId,'$futureSettingsEventName','$futureSettingsEventStart', '$futureSettingsEventEnd',  '$locationId',
											'$locationName', '$gamingMachineList', '$futureSettingsMystery') ON DUPLICATE KEY UPDATE eventId=$futureSettingsEventId");


									}
									catch(Exception $e)
									{
										echo 'Fail';
									}

							}
						}
					}
				}
			}
		}

		$getJackpotCmds = Q($con1,"SELECT * FROM `JackpotCmds`");

		if($getJackpotCmds->num_rows) {
			foreach($getJackpotCmds as $index => $value) {
				$jpId 				= $value['id'];
				$jpLocationId		= $value['locationId'];
				$jpLocationName 	= $value['locationName'];
				$jpIp		 		= $value['ip'];
				$jpHitId 		 	= $value['hitId'];
				$jpDeviceId		 	= $value['deviceId'];
				$jpValue		 	= $value['value'];
				$jpTransferType		= $value['transferType'];
				$jpLock 			= $value['lock'];

				$dynamicConnection = mysqli_connect($value['ip'], $masterUsername, $masterPassword, $dynamicDatabase) or die("Cannot"); 

				try
				{
					$pushJpCommands = Q($dynamicConnection,
					"CALL MultigroupBonusLocal.`INSERT_CMDS`('".$jpHitId."', '".$jpDeviceId."', '".$jpValue."', '".$jpTransferType."', '".$jpLock."');");
					
					$deletePrevJp   = Q($con1,
					"DELETE FROM `JackpotCmds` WHERE id='$jpId'");
				}
				catch(Exception $e)
				{
					echo 'Fail';
				}
			}
		}
	}
}
?>

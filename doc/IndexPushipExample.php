<!DOCTYPE html>

<html>
	<meta http-equiv="content-type" content="text/html; charset=utf-8" />
<head>
  <title>Puship API example page</title>
  <style>
    form {
        margin:5px 5px 0px 0px;
    }
    input {
        width: 200px;
    }
  </style>
</head>

<body>

<h1>Puship Api example 1.2.2</h1>

<form name="GetPushMessagesForm" action="" method="GET" >
    <input type="submit" name="GetPushMessages" value="Get push messages" />
</form>

<form name="GetPushByDevice" action="" method="GET">
    <input type="submit" name="GetPushByDevice" value="Get push messages by device" />
</form>

<form name="DeletePushNotification" action="" method="GET">
    <input type="submit" name="DeletePushNotification" value="Delete Push" />
</form>

<form name="GetDeviceList" action="" method="GET">
    <input type="submit" name="GetDeviceList" value="Get devices"  />
</form>

<form name="SendPushByDevice" action="" method="GET">
    <input type="submit" name="SendPushByDevice" value="Send push by device" />
</form>

<form name="SendByTagOrType" action="" method="GET">
    <input type="submit" name="SendByTagOrType" value="Send push by tag or type" />
</form>

<form name="GetAppTags" action="" method="GET">
    <input type="submit" name="GetAppTags" value="Get Tags" />
</form>

<br><br><br><br>

<?php

/////////////////////////////////////// API CONFIGURATION ////////////////////////////////////////////////////////////////////////////

//setting your desidered location for date format
date_default_timezone_set('Europe/Rome');

require_once('../src/PushipApi.php');

$config = array();
$config['AppId'] = 'jS0oE8PtHcVerde';
$config['Username'] = '<username>';
$config['Password'] = '<password>';

$puship = new PushipApi($config);

//$puship -> EnableDebug = true; //Shows URL, URL Parameters, and the Response from the Server

//////////////////////////////////////// GET PUSH MESSAGES //////////////////////////////////////////////////////////////////////////


if (isset($_GET['GetPushMessages']))
{
  echo "<h3>GetPushMessages result</h3>";

  $param = array();
  $param['DeviceType'] = '3';
  $param['Limit'] = '15';
  $param['Offset'] = '0';

  $param['Latitude'] = '45.44085';
  $param['Longitude'] = '12.31552';

  $tagstosend = array();
  $tagstosend[0]='Virgo'; //Tag number 1
  $tagstosend[1]='Libra'; //Tag number 2

  $tagstosend['Tags'] = $tagstosend;

  //$param['DeviceId'] = 'jS0oE8PtHcVerde_7a6ff0f023039299';

  $resultest = $puship->GetPushMessages($param);
  if (is_array($resultest)) {
    if(sizeof($resultest)>0) {
      foreach ($resultest as $push)
      {
          $date = $push['Date'];
          preg_match('/[0-9]+/', $date, $matches);
          $resdate= date('Y-m-d H:i:s', $matches[0]/1000);

          echo "<b>PushMessageId:</b> ". $push['PushMessageId'] . " <b>Date:</b> ".  $resdate . " <b>Message:</b> ". $push['Message'] . "<br>";
      };
    }
    else {
       echo('0 messages found using this filter');
    }
  } else {
      echo('error read');
  }
}

/////////////////////////////////////// GET PUSH MESSAGE BY DEVICE ///////////////////////////////////////////////////////////////////////////

if (isset($_GET['GetPushByDevice']))
{
  echo "<h3>GetPushMessagesByDevice result</h3>";

  $param = array();
  $param['DeviceId'] = 'jS0oE8PtHcVerde_7a6ff0f023039299';
  $param['Limit'] = '10';
  $param['Offset'] = '0';

  $resultest = $puship->GetPushMessagesByDevice($param);
  if (is_array($resultest)){
    if(sizeof($resultest)>0) {
      foreach ($resultest as $push)
      {

          $date = $push['Date'];
          preg_match('/[0-9]+/', $date, $matches);
          $resdate= date('Y-m-d H:i:s', $matches[0]/1000);

          echo "<b>PushMessageId:</b> ". $push['PushMessageId'] . " <b>Date:</b> ".  $resdate . " <b>Message:</b> ". $push['Message'] . "<br>";
      };
    }
    else {
       echo('0 messages found using this filter');
    }
  }else{
      echo('error read');
  }
}

//////////////////////////////////////// DELETE PUSH NOTIFICATION //////////////////////////////////////////////////////////////////////////

if (isset($_GET['DeletePushNotification']))
{
  echo "<h3>DeletePushMessage result</h3>";

  $pushparam = array();

  $pushparam['PushMessageId'] = 'jS0oE8PtHcVerde_635192605191400000'; //The ID of Push to delete

  $pushresult = $puship->DeletePushMessage($pushparam);

  echo "<b>Error:</b> " . ($pushresult["Error"] ? '1' : '0') . " <b>Message:</b> " . $pushresult["Message"];
}

//////////////////////////////////// GET THE DEVICE LIST //////////////////////////////////////////////////////////////////////////////

if (isset($_GET['GetDeviceList']))
{
  echo "<h3>GetDevices result</h3>";

  $param = array();
  $param['DeviceType'] = '2';  //this is optional
  $param['Limit'] = '15';
  $param['Offset'] = '0';
  $param['Expired'] = 'True';


  $param['P1Latitude'] = '40.42163694648697';
  $param['P1Longitude'] = '10.299156188964844';
  $param['P2Latitude'] = '47.45030563100575';
  $param['P2Longitude'] = '15.368927001953125';

  $param['LastPositionNumber'] = '5';
  $param['LastPositionDate'] = strtotime('-10 days UTC');

  $tagstosend = array();
  $tagstosend[0]='Virgo'; //Tag number 1
  $tagstosend[1]='Libra'; //Tag number 2

  $param['Tags'] = $tagstosend;

  $resultest = $puship->GetDevices($param);
  if (is_array($resultest)){
    if(sizeof($resultest)>0) {
      foreach ($resultest as $dev)
      {
          $datecreated = $dev['Created'];
          preg_match('/[0-9]+/', $datecreated, $matchescreated);
          $cdate= date('Y-m-d H:i:s', $matchescreated[0]/1000);

          $dateupdated = $dev['Updated'];
          preg_match('/[0-9]+/', $dateupdated, $matchesupdated);
          $udate= date('Y-m-d H:i:s', $matchesupdated[0]/1000);

          echo "<b>Expired:</b> ". ($dev['Expired'] ? '1' : '0') . " <b>DeviceType:</b> ".  $dev['DeviceType'] . " <b>Created:</b> ".  $cdate . " <b>Updated:</b> ".  $udate . " <b>DeviceId:</b> ". $dev['DeviceId'] . "<br>";
      };
    } else {
       echo('0 devices found using this filter');
    }
  }else{
      echo('error read');
  }
}


//////////////////////////////////////// SEND PUSH NOTIFICATION BY DEVICE //////////////////////////////////////////////////////////////////////////

if (isset($_GET['SendPushByDevice']))
{
  echo "<h3>SendPushMessageByDevice result</h3>";

  $pushparam = array();

  $devicestosend = array();
  $devicestosend[0] = 'jS0oE8PtHcVerde_b87389c9-5e86-45cd-88e0-9cc239643faf'; //Device number 1 (microsoft device)
  $devicestosend[1] = 'jS0oE8PtHcVerde_7a6ff0f023039299'; //Device number 2 (android device)
  //$devicestosend[2] = 'jS0oE8PtHcVerde_26540AD9-6BE3-4136-B81D-C600A11EBCDB'; // Device number 3 (apple device)

  $pushparam['Devices'] = $devicestosend;
  $pushparam['Message'] = 'last push to device'; //'Hi Marco with wp and David with android and john with apple!';
  $pushparam['Badge'] = '1';
  $pushparam['Push'] = 'True';
  $pushparam['Sound'] = 'Default';

  $pushresult = $puship->SendPushMessageByDevice($pushparam);

  echo "<b>Error:</b> " .  ($pushresult["Error"] ? '1' : '0') . " <b>Message:</b> " . $pushresult["Message"];

}

/////////////////////////////////////// SEND PUSH NOTIFICATION BY TAG OR DEVICE TYPE ///////////////////////////////////////////////////////////////////////////

if (isset($_GET['SendByTagOrType']))
{
  echo "<h3>SendPushMessage result</h3>";

  $param = array();

  $tagstosend = array();
  $tagstosend[0]='Virgo'; //Tag number 1
  //$tagstosend[1]='Libra'; //Tag number 2

  $param['Tags'] = $tagstosend; //this parameter is optional
  $param['Message'] = 'Push to Venice Area!';
  $param['Badge'] = '3';
  $param['Push'] = 'True';
  $param['Sound'] = 'Default';
  $param['SendIOS'] = 'True';
  $param['SendAndroid'] = 'False';
  $param['SendBB'] = 'False';
  $param['SendWP'] = 'True';


  $param['P1Latitude'] = '40.42163694648697';
  $param['P1Longitude'] = '10.299156188964844';
  $param['P2Latitude'] = '47.45030563100575';
  $param['P2Longitude'] = '15.368927001953125';

  $param['LastPositionNumber'] = '5';
  $param['LastPositionDate'] = strtotime('-10 days UTC');

  $pushtagresult = $puship->SendPushMessage($param);

  echo "<b>Error:</b> " .  ($pushtagresult["Error"] ? '1' : '0') . " <b>Message:</b> " . $pushtagresult["Message"];

}

//////////////////////////////////// GET TAG FILTERS //////////////////////////////////////////////////////////////////////////////

if (isset($_GET['GetAppTags']))
{
  echo "<h3>GetAppTagFilters result</h3>";

  $tagparam = array();
  $tagparam['ReturnHystory']='True';

  $resultags = $puship->GetAppTagFilters($tagparam);

  if (is_array($resultags)) {
    if(sizeof($resultags)>0) {
      foreach ($resultags as $item)
      {
          echo "<b>Tag:</b> " .  $item . "<br>";
      };
    } else {
       echo('0 tags found using this filter');
    }
  } else {
      echo('error read');
  }
}

?>

</body>
</html>
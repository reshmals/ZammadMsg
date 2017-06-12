<?php
$isSuccess = false;
$isMediaPresent = false;
$shouldCreateNewTicket = false;
$openTicketId = 0;
// Commented below line to test if($_SERVER['REQUEST_METHOD'] == "POST"){
if($_SERVER['REQUEST_METHOD'] == "POST"){
	/*if(isset($_FILES['file']['name'])) {
		$isMediaPresent = true;
		//comment Echos echo "FileName: " . $_FILES['file']['name'];
		//comment Echos echo "File: " . $_FILES['file']['tmp_name'];
	}
*/
 
    $PhoneNumber = -1;
	$Message = "";
	$SubDomain = "precitech";
	//$ApiKey = "kI22V6GYFBritLNU471l";
	$username = "sodanwar@gmail.com";
	$password = "shriya9009";
	$ApiKey = $username.":".$password;
	$Conversation = "";
	$AgentNumber = 1;
	$Source = "";
	//if( ($isMediaPresent == true && isset($_POST['json'])) || $_SERVER["CONTENT_TYPE"] == "application/json") {
	if( ($isMediaPresent == true && isset($_POST['json'])) || strpos($_SERVER["CONTENT_TYPE"], 'application/json') !== FALSE) {
		$JsonBdy = json_decode("{}");
		//comment Echos echo file_get_contents('php://input') . "\n";
		if($isMediaPresent == true && isset($_POST['json'])) {
			$JsonBdy = json_decode($_POST['json']);
		} else {
			//comment Echos echo file_get_contents('php://input') . "\n";
			$replacedNewLineCharStr = str_replace(chr(10), "\\n", file_get_contents('php://input'));
		    $replacedNewLineCharStr = str_replace("\r\\n", "\r\n", $replacedNewLineCharStr);
			//echo $replacedNewLineCharStr . "\n";
			//echo json_encode($replacedNewLineCharStr) . "\n";
			//$JsonBdy = json_decode(file_get_contents('php://input'));
			$JsonBdy = json_decode($replacedNewLineCharStr);
		}
		//comment Echos echo json_encode($JsonBdy, JSON_PRETTY_PRINT);
		$PhoneNumber = $JsonBdy->phone;
		$Message = $JsonBdy->message;

		//Convert $Message for HTML
		//Now Doing in function only for description & not for Subject
		//$Mesage = htmlspecialchars($Message);
		//$Message = nl2br($Message);

		$SubDomain = $JsonBdy->subdomain;
		$ApiKey = $JsonBdy->apikey;
		if(isset($JsonBdy->agentnumber))
			$AgentNumber = $JsonBdy->agentnumber;
		if(isset($JsonBdy->source))
			$Source = $JsonBdy->source;

		$Conversation = empty($JsonBdy->conversation) ? "" : $JsonBdy->conversation;

	} else {
		$PhoneNumber = isset($_POST['phone']) ? $_POST['phone'] : "";
		$Message = isset($_POST['message']) ? $_POST['message'] : "";
		$Conversation = isset($_POST['conversation']) ? $_POST['conversation'] : "";
		

		$SubDomain = isset($_POST['subdomain']) ? $_POST['subdomain'] : "";
		$ApiKey = isset($_POST['apikey']) ? $_POST['apikey'] : "";
		$AgentNumber = isset($_POST['agentnumber']) ? $_POST['agentnumber'] : "";
		$Source = isset($_POST['source']) ? $_POST['source'] : "";
		//comment Echos echo "SubDomain:" . $SubDomain . ", ApiKey:" . $ApiKey . ", AgentNumber:" . $AgentNumber . ", Source:" . $Source . "\n";
	}

	if($PhoneNumber == "") {
		//comment Echos echo "No PhoneNumber Present" . "\n";
		return;
	}

	
	//comment Echos echo "PhoneNumber:" . $PhoneNumber . ", Message:" . $Message . "\n";
	//comment Echos echo "SubDomain:" . $SubDomain . ", ApiKey:" . $ApiKey . "\n";
	//comment Echos echo "AgentNumber:" . $AgentNumber . ", Source:" . $Source . "\n";

	//Replace Emoji
	$Message = remove_emoji($Message);
	//comment Echos echo "Message After Removing Emoji:" . $Message . "\n";

	$CustomerId = getCustomerId($PhoneNumber, $SubDomain, $ApiKey);
	//comment Echos echo "CustomerId:" . $CustomerId . "\n";

	if($CustomerId<=0) {
		$shouldCreateNewTicket = true;
	} else {
		$OpenTicketIdRet = getOpenTickets($CustomerId, $SubDomain, $ApiKey);
		//comment Echos echo "OpenTicketIdRet:" . $OpenTicketIdRet . "\n";
		if($OpenTicketIdRet<=0) { //TODO
			$shouldCreateNewTicket = true;
		} else {
			$openTicketId = $OpenTicketIdRet; //TODO
		}
	}
	//comment Echos echo "shouldCreateNewTicket:" . $shouldCreateNewTicket . "\n";

	if($shouldCreateNewTicket == false) {
		if($isMediaPresent == true) {
			echo "addArticleWithAttachement" . "\n";
			addArticleWithAttachement($openTicketId, $CustomerId, $Message, $Conversation, $_FILES, $SubDomain, $ApiKey); //TODO
		} else {
			echo "addArticleWithoutAttachement" . "\n";
			addArticleWithoutAttachement($openTicketId, $CustomerId, $Message, $Conversation, $SubDomain, $ApiKey); //TODO
		}
	} else {
		if($isMediaPresent == true) {
			echo "createTicketWithAttachement" . "\n";
			createTicketWithAttachement($Message, $PhoneNumber, $Conversation, $_FILES, $SubDomain, $ApiKey, $AgentNumber, $CustomerId); //TODO
		} else {
			echo "createTicketWithoutAttachement" . "\n";
			createTicketWithoutAttachement($Message, $PhoneNumber, $Conversation, $SubDomain, $ApiKey, $AgentNumber, $CustomerId); //TODO
		}
	}


} 
else {
	echo "Here I am Some Error\n";
} //if($_SERVER['REQUEST_METHOD'] == "POST")---Closing bracket remove comment


//---------------------ADD FUNCTIONS FROM BELOW
function createTicketWithoutAttachement($Message, $PhoneNumber, $Conversation, $SubDomain, $ApiKey, $AgentNumber, $CustomerId) {
$yourdomain = $SubDomain;
	$ticket_data = json_encode(array(
	   "title"=>$Message,
       "group"=>"Users",
       "customer_id"=> $CustomerId,
       "article"=>array("subject"=> $Message,
		   "body"=> "BODY --".$Message,
		   "type"=>"note",
		   "internal"=> false
         ),
		"conversation" => $Conversation,
        "note"=>"some note"
));	
	/*
	$ticket_data = json_encode(array(
	  "description" => getFDSpecificString($Message),
	  "subject" => $Message,
	  "name" => $PhoneNumber,
	  "phone" => $PhoneNumber,
	  "priority" => 2,
	  "status" => 2,
	  "custom_fields" => array("conversation" => $Conversation),
	  "tags" => array("BagAChat", $AgentNumber, $Source)
	));
*/
	$url = "https://$yourdomain.zammad.com/api/v1/tickets";
	//echo "url:" . $url . "\n";
	$ch = curl_init($url);
	$header[] = "Content-type: application/json";
	/*
	$headers = array();
	$headers[] = 'Content-Type:application/json';
	$headers[] = 'Authorization: Token token=eMi9ECBcu6-Mcc14ZDDDrHYd1T6PwUHqmeFSRf_52mcONsG5EDOXM_CcIWg9sfUC';
*/

/* FvMdW_owA8XNrOwgMovIDFKeLp8QRM3uux4kjjf9rwhPoIlQPa7rP_JAoxKXKcFU
	$headers = array(
    'Content-Type:application/json',
    'Authorization: Basic '. base64_encode("user:password") // <---
);*/
//curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
	curl_setopt($ch, CURLOPT_HEADER, true);
	curl_setopt($ch, CURLOPT_USERPWD, $ApiKey);
	//-curl_setopt($ch, CURLOPT_USERPWD, "$api_token");
	curl_setopt($ch, CURLOPT_POSTFIELDS, $ticket_data);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
	$server_output = curl_exec($ch);
	$info = curl_getinfo($ch);
	//echo "server_output:" . $server_output . "\n";
	//print_r($info);
	//print_r($server_output);
	$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
	$headers = substr($server_output, 0, $header_size);
	$response = substr($server_output, $header_size);
	if($info['http_code'] == 201 || $info['http_code'] == 200) {
	  //comment Echos echo "Ticket created successfully, the response is given below \n";
	  //comment Echos echo "Response Headers are \n";
	  //comment Echos echo $headers."\n";
	  //comment Echos echo "Response Body \n";
	  //comment Echos echo "$response \n";
	} else {
	  if($info['http_code'] == 404) {
		echo "Error, Please check the end point 404 \n";
	  } else {
		echo "Error, HTTP Status Code Here inside: " . $info['http_code'] . "\n";
		echo "Headers are ".$headers;
		echo "Response are ".$response;
	  }
	}
	curl_close($ch);
	
}
// $Message, $PhoneNumber, $Conversation, $_FILES, $SubDomain, $ApiKey, $AgentNumber, $CustomerId
function createTicketWithAttachement($Message, $PhoneNumber, $Conversation, $Filesvar, $SubDomain, $ApiKey,$AgentNumber, $CustomerId) {}
function addArticleWithoutAttachement($OpenTicketId, $CustomerId, $Message, $Conversation, $SubDomain, $ApiKey){
	$api_key = $ApiKey;
	//$password = "x";
	$yourdomain = $SubDomain;
	// Reply will be added to the ticket with the following id
	$ticket_id = $OpenTicketId;
	/*
	{
   "ticket_id": 23,
   
   "to": "",
   "cc": "",
   "subject": "adding thru main script",
   "body": "bbbb",
   "content_type": "text/html",
   "type": "note",
   "internal": false,
   "time_unit": "12"
}
	*/
	$article =json_encode(array(
		"ticket_id" => $ticket_id,
    	"to"=> "",
	    "cc"=> "",
        "subject" => "Subject by --",
	    "body" => getFDSpecificString($Message),
	    "content_type" => "text/html",
        "type" => "note",
        "internal" => false,
        "time_unit" => "12"
	));
	$url = "https://$yourdomain.zammad.com/api/v1/ticket_articles";
	$ch = curl_init($url);
	$header[] = "Content-type: application/json";
//echo $url;
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_HEADER, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
	curl_setopt($ch, CURLOPT_USERPWD, $ApiKey);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $article);
	//curl_setopt($ch, CURLO);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
	$server_output = curl_exec($ch);
	//echo "SERVER OP".$server_output."--<br/>";
	$info = curl_getinfo($ch);
	$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
	$headers = substr($server_output, 0, $header_size);
	$response = substr($server_output, $header_size);
	if($info['http_code'] == 201 || $info['http_code'] == 200) {
	  //comment Echos echo "Note added to the ticket, the response is given below \n";
	  //comment Echos echo "Response Headers are \n";
	  //comment Echos echo $headers."\n";
	  //comment Echos echo "Response Body \n";
	  //comment Echos echo "$response \n";
	  echo "ARTICLE ADDED";
	} else {
		if($info['http_code'] == 404) {
		echo "Error, Please check the end point \n";
	  } else {
		echo "Error, HTTP Status Code : " . $info['http_code'] . "\n";
		echo "Headers are ".$headers."\n";
		echo "Response is ".$response;
	  }
	}
	curl_close($ch);
}
function addArticleWithAttachement($OpenTicketId, $CustomerId, $Message, $Conversation, $Filesvar, $SubDomain, $ApiKey) {}
function getCustomerId($PhoneNumber, $SubDomain, $ApiKey){
	echo "<br/>PhoneNumber:--" . $PhoneNumber . "\n";
	$CustomerId = 0;
	//$api_key = $ApiKey;
//	$password = "x";
	$yourdomain = $SubDomain;
	$header[] = "Content-type: application/json";
//curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	$url = "https://$yourdomain.zammad.com/api/v1/users";
	$ch = curl_init($url);
	//echo "url:" . $url . "\n";
	//curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
	curl_setopt($ch, CURLOPT_HEADER, true);
	curl_setopt($ch, CURLOPT_USERPWD, $ApiKey);
	//curl_setopt($ch, CURLOPT_USERPWD, "$api_key");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
	//print_r("hi\n");
	//print_r($ch);
	$server_output = curl_exec($ch);
	$info = curl_getinfo($ch);
	//print_r($info);
	//print_r($server_output);
	$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
	$headers = substr($server_output, 0, $header_size);
	$response = substr($server_output, $header_size);
	if($info['http_code'] == 200 || $info['http_code'] == 201) {
	  //comment Echos echo "Contacts fetched successfully, the response is given below \n";
	  //comment Echos echo "Response Headers are \n";
	  //comment Echos echo $headers."\n";
	  //comment Echos echo "Response Body \n";
	  //comment Echos echo "$response \n";

	  //echo json_encode($ContactJson, JSON_PRETTY_PRINT);
	  $ContactJson = json_decode($response, true);
	  //$i = 0;
	  foreach ($ContactJson as $Contact) {
	          //print $Contact['id']."-MOBILE-".$Contact['mobile']."-EMAIL-". $Contact['email'].'---<br>';
			   if($Contact['mobile']==$PhoneNumber){
				   $CustomerId = $Contact['id'];
				   return $CustomerId;
			   }
            }
			if($CustomerId == 0){
				//$PhoneNumber = 9930943030;
				$CustomerId = createCustomerId($PhoneNumber ,$SubDomain, $ApiKey);
			}
	} else {
	  if($info['http_code'] == 404) {
		echo "Error, Please check the end point \n";
	  } else {
		echo "Error, HTTP Status Code : " . $info['http_code'] . "\n";
		echo "Headers are ".$headers;
		echo "Response are ".$response;
	  }
	}
	/*
	  $ContactJson = json_decode($response, true);
	  //$i = 0;
	  foreach ($ContactJson as $Contact) {
	          print $Contact['id']."-MOBILE-".$Contact['mobile']."-EMAIL-". $Contact['email'].'---<br>';
			   if($Contact['mobile']==$PhoneNumber){
				   $CustomerId = $Contact['id'];
				   return $CustomerId;
			   }
            }
			if($CustomerId == 0){
				//$PhoneNumber = 9930943030;
				$CustomerId = createCustomerId($PhoneNumber ,$SubDomain, $ApiKey);
			}
		*/
	curl_close($ch);
    //echo '<br/> END --CustomerId IN GET CUSTOMER ID--'.$CustomerId."<br/>";
	return $CustomerId;
}
function createCustomerId($PhoneNumber, $SubDomain, $ApiKey) {
$CustomerId = 0;
//echo "<br/>PHON--".$PhoneNumber."--DOM--".$SubDomain."--USER--".$username."--PASS--".$password."<br/>";
	$yourdomain = $SubDomain;

	$user_data = json_encode(array(
	   "firstname"=>"--".$PhoneNumber,
       "lastname"=>"--".$PhoneNumber,
       "phone"=> $PhoneNumber,
       "mobile"=> $PhoneNumber
	));	
/*
{
 "firstname": "Shriya",
 "lastname": "Sodanwar",
 "email": "precitech.lax@gmail.com",
 "phone":"02119224511",
 "mobile":"9923110011",
 "updated_by_id": 1,
  "created_by_id": 1
}
*/
$header[] = "Content-type: application/json";
//curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	$url = "https://$yourdomain.zammad.com/api/v1/users";
	$ch = curl_init($url);
	//echo "url:" . $url . "\n";
	curl_setopt($ch, CURLOPT_POST, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
	curl_setopt($ch, CURLOPT_HEADER, true);
	curl_setopt($ch, CURLOPT_USERPWD, $ApiKey);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $user_data);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
	//print_r("hi\n");
	//print_r($ch);
	$server_output = curl_exec($ch);
	$info = curl_getinfo($ch);
	//print_r($info);
	//print_r($server_output);
	$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
	$headers = substr($server_output, 0, $header_size);
	$response = substr($server_output, $header_size);
	if($info['http_code'] == 200 || $info['http_code'] == 201) {
	  //comment Echos echo "Contacts fetched successfully, the response is given below \n";
	  //comment Echos echo "Response Headers are \n";
	  //comment Echos echo $headers."\n";
	  //comment Echos echo "Response Body \n";
	  //comment Echos echo "$response \n";

	  //echo json_encode($ContactJson, JSON_PRETTY_PRINT);
	  $ContactJson = json_decode($response, true);
	  //echo json_encode($ContactJson, JSON_PRETTY_PRINT);
	  /*foreach ($ContactJson as $Contact) {
	          // print $Contact['id']."-MOBILE-".$Contact['mobile']."-EMAIL-". $Contact['email'].'---<br>';
			   $CustomerId = $Contact['id'];
			   print "INSIDE CREATE CUSTOMER ID Is -->".$CustomerId;
	  }*/
	  $CustomerId = $ContactJson['id'];
	  
	} else {
	  if($info['http_code'] == 404) {
		echo "<br/>Error, Please check the end point \n";
	  } else {
		echo "<br/>Error, HTTP Status Code : " . $info['http_code'] . "\n";
		echo "<br/>Headers are ".$headers;
		echo "<br/>Response are ".$response;
	  }
	}
	  
	  	  
	curl_close($ch);
    echo '<br/>CustomerId IN CREATE CUSTOMER ID--'.$CustomerId."<br/>";
	return $CustomerId;
}

function getOpenTickets($CustomerId, $SubDomain, $ApiKey) {
	//echo "<br/>INSIDE GET TICKET CustomerId:" . $CustomerId . "\n";

	$OpenTicketId = 0;

	//$api_key = $username.":".$password;
	//$password = "x";
	$yourdomain = $SubDomain;
	// Return the tickets that are new or opend & assigned to you
	// If you want to fetch all tickets remove the filter query param
	$url = "https://$yourdomain.zammad.com/api/v1/tickets";
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_HEADER, true);
	curl_setopt($ch, CURLOPT_USERPWD, $ApiKey);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
	$server_output = curl_exec($ch);
	$info = curl_getinfo($ch);
	$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
	$headers = substr($server_output, 0, $header_size);
	$response = substr($server_output, $header_size);
	if($info['http_code'] == 200) {
	  //comment Echos echo "Tickets fetched successfully, the response is given below \n";
	  //comment Echos echo "Response Headers are \n";
	  //comment Echos echo $headers."\n";
	  //comment Echos echo "Response Body \n";
	  //comment Echos echo "$response \n";

	  $Tickets = json_decode($response);
	  //echo json_encode($Tickets, JSON_PRETTY_PRINT);
	  if(empty($Tickets)) {
		  //Nothing here
	  } else {
			foreach($Tickets as $SingleTicket) { //foreach element in $arr
				$TicketStatus = $SingleTicket->close_at; //etc
				$Customer_ID_Ticket = $SingleTicket->customer_id;
				//echo "TicketStatus:" . $TicketStatus . "\n ----CUST ID --".$Customer_ID_Ticket."<br/>";
				
				if(($TicketStatus == null) && ($CustomerId==$Customer_ID_Ticket)) {
					//echo "INSIDE IF LOOP";
					$OpenTicketId = $SingleTicket->id;
					//echo "<br/>OpenTicketId------:" . $OpenTicketId;
					break;
				}
				
			}
	  }
	} else {
	  if($info['http_code'] == 404) {
		echo "Error, Please check the end point GET TICKET ID \n";
	  } else {
		echo "Error, HTTP Status Code : " . $info['http_code'] . "\n";
		echo "Headers are ".$headers;
		echo "Response are ".$response;
	  }
	}
	curl_close($ch);

	return $OpenTicketId;
}
function getFDSpecificString($str) {
	$str = nl2br(htmlspecialchars($str));
	$str = str_replace("\0", "", $str);
	//return nl2br(htmlspecialchars($str))
	return $str;
}

function remove_emoji($text){
  return preg_replace('/([0-9#][\x{20E3}])|[\x{00ae}\x{00a9}\x{203C}\x{2047}\x{2048}\x{2049}\x{3030}\x{303D}\x{2139}\x{2122}\x{3297}\x{3299}][\x{FE00}-\x{FEFF}]?|[\x{2190}-\x{21FF}][\x{FE00}-\x{FEFF}]?|[\x{2300}-\x{23FF}][\x{FE00}-\x{FEFF}]?|[\x{2460}-\x{24FF}][\x{FE00}-\x{FEFF}]?|[\x{25A0}-\x{25FF}][\x{FE00}-\x{FEFF}]?|[\x{2600}-\x{27BF}][\x{FE00}-\x{FEFF}]?|[\x{2900}-\x{297F}][\x{FE00}-\x{FEFF}]?|[\x{2B00}-\x{2BF0}][\x{FE00}-\x{FEFF}]?|[\x{1F000}-\x{1F6FF}][\x{FE00}-\x{FEFF}]?/u', '?', $text);
}



?>
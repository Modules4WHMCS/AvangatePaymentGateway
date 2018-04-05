<?php
# Required File Includes



// FOR 6.x version
$ROOTDIR= __DIR__.'/../../../';


include_once $ROOTDIR.'init.php';
include_once $ROOTDIR.'includes/functions.php';
include_once $ROOTDIR.'includes/gatewayfunctions.php';
include_once $ROOTDIR.'includes/invoicefunctions.php';


use WHMCS\Database\Capsule;


function buildHashStr($arr)
{
	$retData='';
	if(empty($arr)) return 0;
	foreach ($arr as $key=>$val){
		if(is_array($val)){
			$retData .= buildHashStr($val);
		}
		else{
			if($key && $key == 'HASH') continue;
			if($val){
				$retData .= strlen($val).$val;
			}
			else{
				$retData .= 0;
			}
		}
	}
	return $retData;
}


	$GATEWAY = getGatewayVariables('avangate');

if($_REQUEST['success']){
	$invoiceid=$_REQUEST['success'];
	checkCbInvoiceID($invoiceid,'avangate');

    $orderStatus = Capsule::table('tblorders')->where('invoiceid', $invoiceid)->value('status');
	if($orderStatus != 'Active'){
		$url = $_SERVER['PHP_SELF'].'?success='.$invoiceid;
		header("Refresh: 20; url=$url");
		echo('Your order is being processed.<br>
Please wait .....<br>
if your browser does not support auto refresh click this <a href="'.$url.'">link</a>.');
	}
	else{
		header('Location: ../../../viewinvoice.php?id='.$_REQUEST['success'].'&paymentsuccess=true');
	}
	exit();
}
else{
	$ipnStr = strlen($_REQUEST['IPN_PID'][0]).$_REQUEST['IPN_PID'][0].
				 strlen($_REQUEST['IPN_PNAME'][0]).$_REQUEST['IPN_PNAME'][0].
				 strlen($_REQUEST['IPN_DATE']).$_REQUEST['IPN_DATE'].
				 strlen($_REQUEST['IPN_DATE']).$_REQUEST['IPN_DATE'];
	$replyHash = hash_hmac('md5', $ipnStr, $GATEWAY['AVG_SECRET_KEY']);
	echo '<EPAYMENT>'.$_REQUEST['IPN_DATE'].'|'.$replyHash.'</EPAYMENT>';
	$invoiceid = $_REQUEST['REFNOEXT'];
	$transactionid = $_REQUEST['REFNO'];
	checkCbInvoiceID($invoiceid,$GATEWAY["name"]);
	checkCbTransID($transactionid);
	
	if($_REQUEST['ORDERSTATUS'] == 'COMPLETE' || $_REQUEST['ORDERSTATUS'] == 'TEST'){
		$ipnStr = buildHashStr($_REQUEST);

		$gateHash = hash_hmac('md5', $ipnStr, $GATEWAY['AVG_SECRET_KEY']);

		if($gateHash !== $_REQUEST['HASH']){
			logTransaction($GATEWAY["name"],$_REQUEST,'ERROR:HASH mismatch::'.$gateHash);
			throw new Exception('HASH mismatch');
		}
		
		$values['invoiceid'] = $invoiceid;
		$invoice = localAPI("getinvoice",$values,$GATEWAY['whmcs_admin']);
		$inv_amount = $invoice['total'];
		$paid_amount = $_REQUEST['IPN_TOTALGENERAL'];
		$paid_currency=$_REQUEST['CURRENCY'];
		$commission=$_REQUEST['IPN_COMMISSION'];

        addInvoicePayment($invoiceid,$transactionid,$inv_amount,$commission,$GATEWAY["name"]);

        $values2["orderid"] = Capsule::table('tblorders')->where('invoiceid', $invoiceid)->value('id');
		$values2["autosetup"] = true;
		$values2["sendemail"] = true;
		$results = localAPI("acceptorder",$values2,$GATEWAY['whmcs_admin']);

		logTransaction($GATEWAY["name"],$_REQUEST,'Payment recieved');
	
		exit();
	}
}



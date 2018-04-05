<?php

// Define module version for release build tool
//MODULE_VERSION,'1.0';




use WHMCS\Database\Capsule;

function avangate_config() 
{
    $configarray = array(
			"FriendlyName" => array("Type" => "System", "Value"=>"AvanGate"),
			"AVG_MERCHANT_NAME" => array("FriendlyName" => "Merchant Name","Type" => "text","Rows"=>20),
			"AVG_SECRET_KEY" => array("FriendlyName" => "Shop Secret Key","Type" => "text","Rows"=>20),
			"whmcs_admin" => array("FriendlyName" => "WHMCS Admin Login", "Type" => "text", 'Rows'=>20),   
			"test" => array("FriendlyName" => "Test Mode", "Type" => "yesno"),
            "licensekey" => array("FriendlyName" => "License Key", "Type" => "text", "Size" => "30")
		);
		return $configarray;
}

function avangate_link($params) 
{
	$checkoutUri= 'PLNKEXP='.(time()+DAY_1).'&PLNKID='.mt_rand().'&PRODS=4621206&QTY=1&PRICES4621206['.$params['currency'].']='.$params['amount'];
	$PHASH = hash_hmac('md5', strlen($checkoutUri).$checkoutUri, $params['AVG_SECRET_KEY']);
	if($params['test']){
		$checkoutUri.='&DOTEST=1';
	}

    $descArr = Capsule::table('tblinvoiceitems')->where('invoiceid', $params['invoiceid'])->get();
    $prodDesc="";
	foreach($descArr as $row){
		$prodDesc.=$row['description'].' | ';
	}
	
	$checkoutUri.='&INFO4621206='.urlencode($prodDesc.$params["description"]);
	$checkoutUri.='&CART=1&CARD=2&PHASH='.$PHASH.'&REF='.$params['invoiceid'];
	$checkoutUri.='&BACK_REF='.'https://'.$_SERVER['HTTP_HOST'].'/modules/gateways/callback/avangate.php?success='.$params['invoiceid'];
	
	$checkoutUrl='https://secure.avangate.com/order/checkout.php?'.$checkoutUri;
	
	$code = '<form method="GET" action="https://secure.avangate.com/order/pf.php">
                <input type="hidden" name="BILL_FNAME" value="'.$params['clientdetails']['firstname'].'" />
                <input type="hidden" name="BILL_LNAME" value="'.$params['clientdetails']['lastname'].'"/>
                <input type="hidden" name="BILL_COMPANY" value="'.$params['clientdetails']['companyname'].'" />
			    <input type="hidden" name="BILL_EMAIL" value="'.$params['clientdetails']['email'].'" />			    
                <input type="hidden" name="BILL_PHONE" value="'.$params['clientdetails']['phonecc'].$params['clientdetails']['phonenumber'].'" />
                <input type="hidden" name="BILL_ADDRESS" value="'.$params['clientdetails']['address1'].'" />
			    <input type="hidden" name="BILL_ADDRESS2" value="'.$params['clientdetails']['address2'].'" />
                <input type="hidden" name="BILL_ZIPCODE" value="'.$params['clientdetails']['postcode'].'" />
				<input type="hidden" name="BILL_CITY" value="'.$params['clientdetails']['city'].'" />
				<input type="hidden" name="BILL_STATE" value="'.$params['clientdetails']['state'].'" />
 				<input type="hidden" name="BILL_COUNTRYCODE" value="'.$params['clientdetails']['countrycode'].'" />
 				<input type="hidden" name="BILL_CITY" value="'.$params['clientdetails']['city'].'" />               		
 				<input type="hidden" name="URL" value="'.$checkoutUrl.'" /> 
 				<input type="hidden" name="MERCHANT" value="'.$params['AVG_MERCHANT_NAME'].'" /> 
 					
 						
                '					
	
	
	;


    $code .= '<input type="submit" value="'.$params['langpaynow'].'" /></form>';

		return $code;	
	
	
}


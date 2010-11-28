<?php
require '2performant.php';

$session = new TPerformant("simple", array("user" => 'uradmin', "pass" => '2paraletest'), 'http://localhost:3000');

#$opts = array( "url" => "http://localhost/action-callback.php", "url_type" => "iframe" );

#$hook = array( "name" => "actions", "value" => json_encode($opts) );

#var_dump($session->hook_create($hook, "QK21M2zXlgIl4giS9hsR"));

//var_dump($session->product_store_products_search('approved', 'a'));
//var_dump($session->campaigns_listforowner());
//var_dump($session->user_loggedin());


//$taxes =  array("type" => array("percent", "percent"), "value" => array(20, 20), "description" => array("description 1", "description 2"));
//$commissions = array("1608", "1685", "1991", "2228");
//$affiliate_invoice = array("user_id" => 621, "completed" => "false", "comments" => "coments");
//var_dump($session->admin_affiliate_invoice_create(621, $affiliate_invoice, $commissions, $taxes));


//$affiliate_invoice = array("completed" => "true");
//var_dump($session->admin_affiliate_invoice_update(621, 45, $affiliate_invoice));

var_dump($session->admin_affiliate_invoice_destroy(621, 44));

?>

<?php

require '2performant.php';

$session = new TPerformant("simple", array("user" => 'tarafashion', "pass" => '2paraletest'), 'http://localhost:3000');

$options = array("search_transaction_id" => "abscsead");
$commission = $session->commissions_search($options);

$commission->{'status'} = 'accepted';
$session->commission_update($commission->{'id'}, $commission)
?>

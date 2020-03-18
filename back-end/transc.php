<?php

include('sql.php');

$data = new DataProcess($dsn, $user, $pass);

if ($_POST['action'] == 'buy') {
	$cid = htmlspecialchars($_POST['cid']);
	$total_amount = htmlspecialchars($_POST['total_amount']);
	$addressid = htmlspecialchars($_POST['addressid']);
	$pros = $_POST['pros'];
	$cartid = htmlspecialchars($_POST['cartid']);
	$type = htmlspecialchars($_POST['payment']);

	echo $data->make_payment($cid, $total_amount, $type, $pros, $addressid, $cartid);
} else if ($_GET['action'] == 'view') {
	$cid = htmlspecialchars($_GET['cid']);

	echo $data->view_transac($cid);
} else if ($_GET['action'] == 'view_each') {
	$tid = htmlspecialchars($_GET['tid']);

	echo $data->view_product_in_transac($tid);
}

?>
<?php

include('sql.php');

$data = new DataProcess($dsn, $user, $pass);

if (isset($_GET['cartid']) && !isset($_GET['action'])) {
	$cartid = htmlspecialchars($_GET['cartid']);

	echo $data->get_products_in_cart($cartid);
} else if ($_POST['action'] == 'delete') {
	$cartid = htmlspecialchars($_POST['cartid']);
	$pid = htmlspecialchars($_POST['pid']);

	echo $data->remove_from_cart($cartid, $pid);
} else if ($_POST['action'] == 'update') {
	$cartid = htmlspecialchars($_POST['cartid']);
	$pid = htmlspecialchars($_POST['pid']);
	$num = htmlspecialchars($_POST['num']);

	echo $data->update_from_cart($num, $cartid, $pid);
} else if ($_POST['action'] == 'add') {
	$cartid = htmlspecialchars($_POST['cartid']);
	$pid = htmlspecialchars($_POST['pid']);

	echo $data->add_to_cart($cartid, $pid);
}


?>
<?php

include('sql.php');

$data = new DataProcess($dsn, $user, $pass);

if (isset($_POST['username']) && isset($_POST['password']) && $_POST['action'] == 'login') {
	$username = htmlspecialchars($_POST['username']);
	$password = htmlspecialchars($_POST['password']);

	echo $data->cust_login($username, $password, $_SERVER['REQUEST_TIME'], $_SERVER['REQUEST_TIME'] + 7200);
} else if ($_POST['action'] == 'test_jwt') {
	$jwt = $_SERVER['HTTP_AUTHORIZATION'];

	echo json_encode($data->protect($jwt, '123456', $_SERVER['REQUEST_TIME']));
} else if ($_POST['action'] == 'register') {
	$name = htmlspecialchars($_POST['username']);
	$password = htmlspecialchars($_POST['password']);
	$email = htmlspecialchars($_POST['email']);
	$gai = htmlspecialchars($_POST['income']);
	$kind = htmlspecialchars($_POST['kind']);
	$street = htmlspecialchars($_POST['address']);
	$city = htmlspecialchars($_POST['city']);
	$zipcode = htmlspecialchars($_POST['zipcode']);
	$stateid = htmlspecialchars($_POST['state']);

	if ($kind == 0) {
		$age = htmlspecialchars($_POST['age']);
		$gender = htmlspecialchars($_POST['gender']);
		$married = htmlspecialchars($_POST['married']);

		echo $data->insert_home_customer($name, $email, $gai, $password, $age, $gender, $married, $kind, $street, $city, $zipcode, $stateid);
	} else {
		$category = htmlspecialchars($_POST['bc']);

		echo $data->insert_business_customer($name, $email, $gai, $password, $category, $kind, $street, $city, $zipcode, $stateid);
	}
} else if (isset($_GET['cid']) && isset($_GET['kind'])) {
	$cid = $_GET['cid'];
	$kind = $_GET['kind'];

	echo $data->get_customer_detail($cid, $kind);
} else if ($_POST['action'] == 'edit') {
	$cid = htmlspecialchars($_POST['cid']);
	$name = htmlspecialchars($_POST['username']);
	$gai = htmlspecialchars($_POST['income']);
	$kind = htmlspecialchars($_POST['kind']);
	$street = htmlspecialchars($_POST['street']);
	$city = htmlspecialchars($_POST['city']);
	$zipcode = htmlspecialchars($_POST['zipcode']);
	$stateid = htmlspecialchars($_POST['stateid']);
	$addressid = htmlspecialchars($_POST['addressid']);

	$data->update_customer($name, $gai, $cid);
	$data->update_address($street, $city, $zipcode, $stateid, $addressid);

	if ($kind == 0) {
		$age = htmlspecialchars($_POST['age']);
		$gender = htmlspecialchars($_POST['gender']);
		$married = htmlspecialchars($_POST['married']);

		$data->update_home_cust($age, $gender, $married, $cid);

		echo json_encode(1);
	} else {
		$category = htmlspecialchars($_POST['bc']);

		$data->update_busi_cust($category, $cid);

		echo json_encode(1);
	}

} else if ($_GET['action'] == 'get_states') {
	echo $data->get_states();
} else if ($_POST['action'] == 'quit') {
	$cid = htmlspecialchars($_POST['cid']);
	$addressid = htmlspecialchars($_POST['addressid']);

	echo $data->remove_customer($cid, $addressid);
}



?>
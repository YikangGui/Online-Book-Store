<?php

include('sql.php');

$data = new DataProcess($dsn, $user, $pass);

if ($_GET['action'] == 'view_product') {
	echo $data->view_product();
} else if ($_POST['action'] == 'update_product') {
	$pid = htmlspecialchars($_POST['pid']);
	$name = htmlspecialchars($_POST['name']);
	$description = htmlspecialchars($_POST['desc']);
	$year = htmlspecialchars($_POST['year']);
	$vendorprice = htmlspecialchars($_POST['vp']);
	$retailprice = htmlspecialchars($_POST['rp']);
	$stock = htmlspecialchars($_POST['stock']);
	$sold = htmlspecialchars($_POST['sold']);
	$picture = htmlspecialchars($_POST['picture']);

	echo $data->update_product($name, $description, $year, $vendorprice, $retailprice, $stock, $sold, $picture, $pid);
} else if ($_POST['action'] == 'delete_product') {
	$pid = htmlspecialchars($_POST['pid']);

	echo $data->remove_product($pid);
} else if ($_POST['action'] == 'add_product') {
	$name = htmlspecialchars($_POST['name']);
	$description = htmlspecialchars($_POST['desc']);
	$year = htmlspecialchars($_POST['year']);
	$vendorprice = htmlspecialchars($_POST['vp']);
	$retailprice = htmlspecialchars($_POST['rp']);
	$stock = htmlspecialchars($_POST['stock']);
	$sold = 0;
	$picture = htmlspecialchars($_POST['picture']);
	$cateid = htmlspecialchars($_POST['cate']);
	$vendorid = htmlspecialchars($_POST['pcate']);

	echo $data->add_product($name, $description, $year, $vendorprice, $retailprice, $stock, $sold, $picture, $cateid, $vendorid);

} else if ($_GET['action'] == 'view_sales_profit') {
	echo $data->sale_profit();
} else if ($_GET['action'] == 'view_top_category') {
	echo $data->top_selling_cate_by_sales();
} else if ($_GET['action'] == 'view_top_region') {
	echo $data->top_region();
} else if ($_GET['action'] == 'view_top_business') {
	echo $data->top_business_buyer($_GET['pid']);
} else if ($_POST['action'] == 'staff_login') {
	$email = htmlspecialchars($_POST['email']);
	$password = htmlspecialchars($_POST['password']);

	echo $data->staff_login($email, $password, $_SERVER['REQUEST_TIME'], $_SERVER['REQUEST_TIME'] + 7200);
} else if ($_GET['action'] == 'get_staff_detail') {
	$sid = htmlspecialchars($_GET['sid']);
	echo $data->get_staff_detail($sid);
} else if ($_GET['action'] == 'view_store') {
	echo $data->view_store();
} else if ($_POST['action'] == 'delete_store') {
	$storeid = htmlspecialchars($_POST['storeid']);
	echo $data->remove_store($storeid);
} else if ($_GET['action'] == 'get_regionid') {
	$mgrid = htmlspecialchars($_GET['mgrid']);
	echo $data->get_regionid($mgrid);
} else if ($_GET['action'] == 'get_staff') {
	echo $data->select_all_available_staff();
} else if ($_POST['action'] == 'add_store') {
	$name = htmlspecialchars($_POST['name']);
	$mgrid = htmlspecialchars($_POST['manager']);
	$stateid = htmlspecialchars($_POST['state']);
	$city = htmlspecialchars($_POST['city']);
	$street = htmlspecialchars($_POST['street']);
	$zipcode = htmlspecialchars($_POST['zip']);
	$regionid = htmlspecialchars($_POST['regionid']);

	echo $data->add_store($name, $regionid, $mgrid, $street, $city, $zipcode, $stateid);
} else if ($_POST['action'] == 'edit_store') {
	$storeid = htmlspecialchars($_POST['id']);
	$name = htmlspecialchars($_POST['name']);

	echo $data->update_store($storeid, $name);
}



?>
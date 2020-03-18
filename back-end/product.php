<?php

include('sql.php');

$data = new DataProcess($dsn, $user, $pass);

if (isset($_GET['pid'])) {
	$pid = htmlspecialchars($_GET['pid']);

	echo $data->product_detail($pid);
} else if (isset($_GET['index'])) {
	$index = htmlspecialchars($_GET['index']);

	echo $data->list_product_by_index($index);
} else if (isset($_GET['pcat']) && isset($_GET['ccat']) && !isset($_GET['lo']) && !isset($_GET['hi'])) {
	if ($_GET['ccat'] == '') {
		$pcat = htmlspecialchars($_GET['pcat']);
		
		echo $data->list_product_parent_category($pcat);
	} else {
		$ccat = htmlspecialchars($_GET['ccat']);
		
		echo $data->list_product_category($ccat);
	}
} else if (isset($_GET['name'])) {
	$name = htmlspecialchars($_GET['name']);

	echo $data->search_product_by_name($name);
} else if (isset($_GET['pcat']) && isset($_GET['ccat']) && isset($_GET['lo']) && isset($_GET['hi'])) {

	$lo = htmlspecialchars($_GET['lo']);
	$hi = htmlspecialchars($_GET['hi']);

	if ($_GET['ccat'] == '') {
		$pcat = htmlspecialchars($_GET['pcat']);
		
		echo $data->product_parent_cate_price_range($pcat, $lo, $hi);
	} else {
		$ccat = htmlspecialchars($_GET['ccat']);
		
		echo $data->product_cate_price_range($ccat, $lo, $hi);
	}
} else if ($_GET['action'] == 'get_parent_category') {
	echo $data->get_parent_category();
} else if ($_GET['action'] == 'get_child_category') {
	echo $data->get_child_category($_GET['parent_category']);
}






?>
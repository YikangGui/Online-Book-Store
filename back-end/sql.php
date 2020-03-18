<?php

$dbms='mysql';     //数据库类型
$host='localhost:8889'; //数据库主机名
$dbName='unaffordables_db';    //使用的数据库
$user='root';      //数据库连接用户名
$pass='root';          //对应的密码
$dsn="$dbms:host=$host;dbname=$dbName";

class DataProcess {
    
    protected $pdo;

    public function __construct($dsn, $user, $pass) {
        date_default_timezone_set('America/Detroit');
        try {
            $this->$pdo = new PDO($dsn, $user, $pass);
        } catch(PDOException $e) {
            die("Unable to select database");
        }
    }

    public function get_states() {
        $sql = "select * from state";
        $stmt = $this->$pdo->prepare($sql);
        $result = $stmt->execute(array($cid));
        if (!$result) {
            return json_encode(0);
        } else {
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return json_encode($rows);
        }
    }

    public function insert_address($street, $city, $zipcode, $stateid) {
        $stmt = $this->$pdo->prepare("insert into address (street, city, zipcode, stateid) values (?, ?, ?, ?)");
        $stmt->execute(array($street, $city, $zipcode, $stateid));
    }

    public function insert_home_customer($name, $email, $gai, $password, $age, $gender, $married, $kind, $street, $city, $zipcode, $stateid) {
        $this->insert_address($street, $city, $zipcode, $stateid);
        $addr_id = $this->$pdo->lastInsertId();
        $in_cust = $this->$pdo->prepare("insert into customer (name, email, gai, password, salt, kind, addressid) values (?, ?, ?, ?, ?, ?, ?)");
        $salt = $this->random_salt();
        $salted_password = $this->hash_password($password, $salt);
        // $addr_id = $this->get_last_id();
        $in_cust->execute(array($name, $email, $gai, $salted_password, $salt, $kind, $addr_id));
        $home_cust = $this->$pdo->prepare("INSERT INTO home_cust (cid, age, gender, married) VALUES (?, ?, ?, ?)");
        // $cust_id = $this->get_last_id();
        $cust_id = $this->$pdo->lastInsertId();
        $home_cust->execute(array($cust_id, $age, $gender, $married));
        $this->create_cart($cust_id); //create a cart for new customer
        return json_encode(1);
    }

    public function insert_business_customer($name, $email, $gai, $password, $category, $kind, $street, $city, $zipcode, $stateid) {
        $this->insert_address($street, $city, $zipcode, $stateid);
        $addr_id = $this->$pdo->lastInsertId();
        $in_cust = $this->$pdo->prepare("insert into customer (name, email, gai, password, salt, kind, addressid) values (?, ?, ?, ?, ?, ?, ?)");
        $salt = $this->random_salt();
        $salted_password = $this->hash_password($password, $salt);
        // $addr_id = $this->get_last_id();
        $in_cust->execute(array($name, $email, $gai, $salted_password, $salt, $kind, $addr_id));
        $busi_cust = $this->$pdo->prepare("INSERT INTO busi_cust (cid, category) VALUES (?, ?)");
        // $cust_id = $this->get_last_id();
        $cust_id = $this->$pdo->lastInsertId();
        $busi_cust->execute(array($cust_id, $category));
        $this->create_cart($cust_id); //create a cart for new customer
        return json_encode(1);
    }


    public function create_cart($cid) {
        $sql = "insert into cart (cid) values(?)";
        $stmt = $this->$pdo->prepare($sql);
        $stmt->execute(array($cid));
    }

    // Determine if a customer exists in the system.
    // return true if exists
    public function exists_customer ($email) {
        $sql = "select * from customer where email = ?";
        $stmt = $this->$pdo->prepare($sql);
        $stmt->execute(array($email));
        if($stmt->rowCount() == 0) {
            return false;
        }
        return true;
    }

    // Determine if a staff exists in the system.
    // return true if exists
    public function exists_staff ($email) {
        $sql = "select * from staff where email = ?";
        $stmt = $this->$pdo->prepare($sql);
        $stmt->execute(array($email));
        if($stmt->rowCount() == 0) {
            return false;
        }
        return true;
    }


    public function hash_password($password, $salt) {
        $salted_password = hash('sha256', $password. $salt);
        return $salted_password;
    }

    public function random_salt($len = 16) {
        $bytes = openssl_random_pseudo_bytes($len / 2);
        return bin2hex($bytes);
    }

//     public function get_last_id() {
//         $last_id = $this->$pdo->insert_id;
//         return $last_id;
//     }

    // returns true if credentials correct
    // fills member variable info if true
    public function cust_login($email, $password, $iat, $exp) {
        $sql = "select * from customer where email=?";
        $stmt = $this->$pdo->prepare($sql);
        $stmt->execute(array($email));
        if($stmt->rowCount() === 0) {
            return json_encode(0);
        }

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $hashed_password = $row['password'];
        $salt = $row['salt'];

        if($hashed_password !== hash("sha256", $password . $salt)) {
            return json_encode(1);
        }

        $payload=[
            'iss' => 'pitt', //签发者
            'iat' => $iat, //什么时候签发的
            'exp' => $exp, //过期时间
            'cid' => $row['id'],
            'is_admin' => false,
            'cartid' => $this->get_cart($row['id']),
            'kind' => $row['kind'],
            'addressid' => $row['addressid']
        ];

        $key = '123456';

        return json_encode(array('cid'=>$row['id'], 'cartid'=>$this->get_cart($row['id']), 'kind'=>$row['kind'], 'addressid'=>$row['addressid'], 'access_token'=>$this->jwt_encode($payload, $key, 'SHA256')));
    }

    public function jwt_encode(array $payload, string $key, string $alg) {

        $key = md5($key);
        $jwt = $this->urlsafeB64Encode(json_encode(['typ' => 'JWT', 'alg' => $alg])) . '.' . $this->urlsafeB64Encode(json_encode($payload));
        return $jwt . '.' . $this->signature($jwt, $key, $alg);
    }

    public function signature(string $input, string $key, string $alg) {
        return hash_hmac($alg, $input, $key);
    }

    public function urlsafeB64Encode(string $input) {
        return str_replace('=', '', strtr(base64_encode($input), '+/', '-_'));
    }

    public function protect(string $jwt, string $key, string $server_time) {
        return $this->decode($jwt, $key, $server_time);
    }

    public function decode(string $jwt, string $key, string $server_time) {
        $tokens = explode('.', $jwt);
        $key    = md5($key);

        if (count($tokens) != 3)
            return false;

        list($header64, $payload64, $sign) = $tokens;

        $header = json_decode($this->urlsafeB64Decode($header64), JSON_OBJECT_AS_ARRAY);
        if (empty($header['alg']))
            return false;

        if ($this->signature($header64 . '.' . $payload64, $key, $header['alg']) !== $sign)
            return false;

        $payload = json_decode($this->urlsafeB64Decode($payload64), JSON_OBJECT_AS_ARRAY);

        $time = $server_time;
        if (isset($payload['iat']) && $payload['iat'] > $time)
            return false;

        if (isset($payload['exp']) && $payload['exp'] < $time)
            return false;

        return true;
    }

    public function urlsafeB64Decode(string $input) {
        $remainder = strlen($input) % 4;

        if ($remainder)
        {
            $padlen = 4 - $remainder;
            $input .= str_repeat('=', $padlen);
        }

        return base64_decode(strtr($input, '-_', '+/'));
    }

    public function get_home_cust_info($id) {
        $sql = "select * from busi_cust where id=?";
        $stmt = $this->$pdo->prepare($sql);
        $stmt->execute(array($id));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->gender = $row['gender'];
        $this->age = $row['age'];
        $this->married = $row['married'];
    }

    public function get_business_cust_info($id) {
        $sql = "select * from home_cust where id=?";
        $stmt = $this->$pdo->prepare($sql);
        $stmt->execute(array($id));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->$business_category = $row['category'];
    }

    // returns true if credentials correct
    // fills member variable info if true
    public function staff_login($email, $password, $iat, $exp) {
        $sql = "select * from staff where email = ?";
        $stmt = $this->$pdo->prepare($sql);
        $stmt->execute(array($email));
        if($stmt->rowCount() === 0) {
            return json_encode(0);
        }

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $hashed_password = $row['password'];
        $salt = $row['salt'];

        if($hashed_password !== hash("sha256", $password . $salt)) {
            return json_encode(1);
        }
        
        $payload=[
            'iss' => 'pitt', //签发者
            'iat' => $iat, //什么时候签发的
            'exp' => $exp, //过期时间
            'sid' => $row['id'],
            'is_admin' => true,
            'level' => $row['level'],
            'addressid' =>$row['addressid']
        ];

        $key = '123456';

        return json_encode(array('sid'=>$row['id'], 'level'=>$row['level'], 'addressid'=>$row['addressid'], 'access_token'=>$this->jwt_encode($payload, $key, 'SHA256')));
    }

    public function get_staff_detail($sid) {
        $sql = "select s.name sname, s.email, s.salary, s.position, s.password, a.street, a.city, a.zipcode, st.name as stname from staff s, address a, state st where s.id = ? and s.addressid = a.id and a.stateid = st.id";
        $stmt = $this->$pdo->prepare($sql);
        $result = $stmt->execute(array($sid));
        if (!$result) {
            return json_encode(0);
        } else {
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return json_encode($rows);
        }
    }

    // add product to cart
    // insert new row if product does not exist
    // else add product count by 1
    public function add_to_cart($cartid, $pid) {
        // $cartid = $this->get_cart($this->id);
        if ($this->product_exist_in_cart($cartid, $pid) == false) {
            $add_new = "insert into product_in_cart (cartid, pid, quantity) values (?, ?, ?)";
            $stmt = $this->$pdo->prepare($add_new);
            $result = $stmt->execute(array($cartid, $pid, 1));
            if (!$result) {
                return json_encode(0);
            } else {
                return json_encode(1);
            }
        } else {
            $add_one = "update product_in_cart set quantity = quantity + 1 where cartid = ? and pid = ?";
            $stmt = $this->$pdo->prepare($add_one);
            $result = $stmt->execute(array($cartid, $pid));
            if (!$result) {
                return json_encode(0);
            } else {
                return json_encode(2);
            }
        }
    }

    // return rows including all products in a cart
    // along with remaining stock of each product
    public function get_products_in_cart($cartid) {
        $sql = "select pc.pid, pc.quantity, p.stock, p.name, p.retailprice, p.picture from product_in_cart pc inner join product p on pc.pid = p.id where pc.cartid = ?";
        $stmt = $this->$pdo->prepare($sql);
        $result = $stmt->execute(array($cartid));
        if (!$result) {
            return json_encode(0);
        } else {
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return json_encode($rows);
        }
    }

    // return the store located in the same state as the user's address
    public function get_store_id($address_id) {
        $sql = "select store.id from store inner join address on store.addressid = address.id inner join state on address.stateid = state.id where state.id = (select state.id from address inner join state on address.stateid = state.id where address.id = ?)";
        $stmt = $this->$pdo->prepare($sql);
        $result = $stmt->execute(array($address_id));
        if (!$result) {
            return false;
        } else {
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return $rows[0]['id'];
        }
    }

    // change $this->address_id, $this->cid, $this->cartid
    // $products is an array containing [pid, quantity]
    public function make_payment($cid, $total_amount, $type, $products, $address_id, $cartid) {

        try {
            // $ordered_time = date("Y-m-d H:i:s");
            $store_id = $this->get_store_id($address_id);
            // $store_id = 1;
            $sql = "insert into transac (amount, type, cid, storeid) values (?, ?, ?, ?)";
            $stmt = $this->$pdo->prepare($sql);
            $stmt->execute(array($total_amount, $type, $cid, $store_id));

            $transac_id = $this->$pdo->lastInsertId();
            //pnt = product_in_transac
            $pnt = "insert into product_in_transac (tid, pid, quantity) values (?, ?, ?)";
            //ups = update_stock
            $ups = "update product set stock = stock - ?, sold = sold + ? where id = ?";
            $stmt_pnt = $this->$pdo->prepare($pnt);
            $stmt_ups = $this->$pdo->prepare($ups);
            foreach ($products as $product) {
                $stmt_pnt->execute(array($transac_id, $product[0], $product[1]));
                $stmt_ups->execute(array($product[1], $product[1], $product[0]));
                $dpc = "delete from product_in_cart where cartid = ? and pid = ?";
                $stmt_dpc = $this->$pdo->prepare($dpc);
                $stmt_dpc->execute(array($cartid, $product[0]));
            }
            return json_encode(1);
        } catch(PDOExecption $e) {
            $this->$pdo->rollback();
            return json_encode(0);
        }
        
    }

    public function view_transac($cid) {
        $sql = "select * from transac where cid = ?";
        $stmt = $this->$pdo->prepare($sql);
        $result = $stmt->execute(array($cid));
        if (!$result) {
            return json_encode(0);
        } else {
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return json_encode($rows);
        }
    }

    public function view_product_in_transac($tid) {
        $sql = "select pt.pid, pt.quantity, p.picture, p.name from product_in_transac pt, product p where pt.tid = ? and pt.pid = p.id";
        $stmt = $this->$pdo->prepare($sql);
        $result = $stmt->execute(array($tid));
        if (!$result) {
            return json_encode(0);
        } else {
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return json_encode($rows);
        }
    }

    public function clear_cart($cartid) {
        $sql = "delete from product_in_cart where cartid = ?";
        $stmt = $this->$pdo->prepare($sql);
        $result = $stmt->execute(array($cartid));
        if (!$result) {
            return json_encode(0);
        } else {
            return json_encode(1);
        }
    }

    // remove certain product from cart
    public function remove_from_cart($cartid, $pid) {
        $sql = "delete from product_in_cart where cartid = ? and pid = ?";
        $stmt = $this->$pdo->prepare($sql);
        $result = $stmt->execute(array($cartid, $pid));
        if (!$result) {
            return json_encode(0);
        } else {
            return json_encode(1);
        }
    }

    // update quantity of a certain product in cart by 1
    public function update_from_cart($num, $cartid, $pid) {
        $sql = "update product_in_cart set quantity = ? where cartid = ? and pid = ?";
        $stmt = $this->$pdo->prepare($sql);
        $result = $stmt->execute(array($num, $cartid, $pid));
        if (!$result) {
            return json_encode(0);
        } else {
            return json_encode(1);
        }
    }

    public function remove_zero_quantity($cartid) {
        $sql = "delete from product_in_cart where cartid=? and quantity=0";
        $stmt = $this->$pdo->prepare($sql);
        $stmt->execute(array($cartid));
    }

    public function calculate_total_amount($cartid) {
        $sql = "select sum(quantity*price) as total from product_in_cart inner join product on product_in_cart.pid = product.id where cartid=?";
        $stmt = $this->$pdo->prepare($sql);
        $stmt->execute(array($cartid));
        $rows = $stmt->fetch(PDO::FETCH_ASSOC);
        return $rows['total'];
    }

    public function get_cart($customerid) {
        $sql = "select id from cart where cart.cid = ? limit 1";
        $stmt = $this->$pdo->prepare($sql);
        $stmt->execute(array($customerid));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['id'];
    }

    // return false if product not in cart
    // else return product count
    public function product_exist_in_cart($cartid, $pid) {
        $sql = "select quantity from product_in_cart where cartid = ? and pid = ? ";
        $stmt = $this->$pdo->prepare($sql);
        $stmt->execute(array($cartid, $pid));
        if ($stmt->rowCount() == 0) {
            return false;
        } else {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row['quantity'];
        }
    }

    public function list_product_by_index($index) {
        $index = 30 * $index;
        $sql = "select * from product limit ".$index.", 30";
        $stmt = $this->$pdo->prepare($sql);
        $result = $stmt->execute(array());
        if (!$result) {
            return json_encode(0);
        } else {
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return json_encode($rows);
        }
    }

    public function list_product_parent_category($parent_cate_id) {
        $sql = "select product.id, product.name pname, retailprice, picture, vendor.name vname from product inner join category on product.cateid=category.id inner join parent_category on category.parentid=parent_category.id inner join vendor on vendorid = vendor.id where parent_category.id = ? limit 100";
        $stmt = $this->$pdo->prepare($sql);
        $result = $stmt->execute(array($parent_cate_id));
        if (!$result) {
            return json_encode(0);
        } else {
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return json_encode($rows);
        }
    }

    public function list_product_category($cate_id) {
        $sql = "select p.id, p.name pname, p.retailprice, p.picture, v.name vname from product p inner join category c on p.cateid = c.id inner join vendor v on p.vendorid = v.id where c.id = ? limit 100";
        $stmt = $this->$pdo->prepare($sql);
        $result = $stmt->execute(array($cate_id));
        if (!$result) {
            return json_encode(0);
        } else {
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return json_encode($rows);
        }
    }

    public function product_detail($pid) {
        $sql = "select p.id, p.name as pname, p.year, p.retailprice, p.picture, p.description, v.name as vname from product p inner join vendor v on p.vendorid = v.id where p.id = ?";
        $stmt = $this->$pdo->prepare($sql);
        $result = $stmt->execute(array($pid));
        if (!$result) {
            return json_encode(0);
        } else {
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return json_encode($rows);
        }
    }

    public function product_parent_cate_price_range($parent_cate_id, $lo, $hi) {
        $sql = "select product.id, product.name pname, retailprice, picture, vendor.name vname from product inner join category on product.cateid=category.id inner join parent_category on category.parentid=parent_category.id inner join vendor on vendorid = vendor.id where parent_category.id=? and product.retailprice between ? and ?";
        $stmt = $this->$pdo->prepare($sql);
        $result = $stmt->execute(array($parent_cate_id, $lo, $hi));
        if (!$result) {
            return json_encode(0);
        } else {
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return json_encode($rows);
        }
    }

    public function product_cate_price_range($cate_id, $lo, $hi) {
        $sql = "select p.id, p.name pname, p.retailprice, p.picture, v.name vname from product p inner join category c on p.cateid = c.id inner join vendor v on p.vendorid = v.id where c.id = ? and p.retailprice between ? and ? order by p.retailprice";
        $stmt = $this->$pdo->prepare($sql);
        $result = $stmt->execute(array($cate_id, $lo, $hi));
        if (!$result) {
            return json_encode(0);
        } else {
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return json_encode($rows);
        }
    }

//     public function product_all_price_range($lo, $hi) {
//         $sql = "
// select product.id, product.name, retailprice, picture, vendor.name
// from product inner join vendor on vendorid = vendor.id
// where product.retailprice between ? and ?
//";
//         $stmt = $this->$pdo->prepare($sql);
//         $stmt->execute(array($lo, $hi));
//         $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
//         return $rows;
//     }

    public function search_product_by_name($name) {
        $sql = "select p.id, p.name as pname, p.retailprice, p.picture, v.name as vname from product p inner join vendor v on p.vendorid = v.id where p.name like ?";
        $stmt = $this->$pdo->prepare($sql);
        $name = "%$name%";
        $result = $stmt->execute(array($name));
        if (!$result) {
            return json_encode(0);
        } else {
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return json_encode($rows);
        }
    }

//     public function get_working_store($id) {
//         $sql = "
// select store.name from work_in inner join store on work_in.storeid=store.id
// where staffid=?
//";
//         $stmt = $this->$pdo->prepare($sql);
//         $stmt->execute(array($id));
//     }

    public function sale_profit() {
        $sql = "select id, name, sold, retailprice*sold as sales, (retailprice-vendorprice)*sold as profit from product where sold <> 0 limit 50";
        $stmt = $this->$pdo->prepare($sql);
        $result = $stmt->execute(array());
        if (!$result) {
            return json_encode(0);
        } else {
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return json_encode($rows);
        }
    }

    public function top_selling_cate_by_volume() {
        $sql = "select c.name, sum(p.sold*p.retailprice) as sales, sum(p.sold) as volume from category c inner join product p on c.id = p.cateid group by c.id order by sales desc";
        $stmt = $this->$pdo->prepare($sql);
        $result = $stmt->execute(array());
        if (!$result) {
            return json_encode(0);
        } else {
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return json_encode($rows);
        }
    }

    public function top_selling_cate_by_sales() {
        $sql = "select c.name, sum(p.sold*p.retailprice) as sales, sum(p.sold) as volume from category c inner join product p on c.id = p.cateid group by c.id order by sales desc";
        $stmt = $this->$pdo->prepare($sql);
        $result = $stmt->execute(array());
        if (!$result) {
            return json_encode(0);
        } else {
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return json_encode($rows);
        }
    }

    public function top_region() {
        $sql = "select r.id, r.name, sum(t.amount) as sales from region r, transac t, store s where r.id = s.regionid and t.storeid = s.id group by r.id";
        $stmt = $this->$pdo->prepare($sql);
        $result = $stmt->execute(array());
        if (!$result) {
            return json_encode(0);
        } else {
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return json_encode($rows);
        }
    }

    public function top_business_buyer($pid) {
        $sql = "select customer.id, customer.name cname, busi_cust.category, product.name pname, product_in_transac.quantity from customer inner join busi_cust on customer.id = busi_cust.cid inner join transac on customer.id = transac.cid inner join product_in_transac on transac.id = product_in_transac.tid inner join product on product_in_transac.pid = product.id where product.id = ? order by quantity desc";
        $stmt = $this->$pdo->prepare($sql);
        $result = $stmt->execute(array($pid));
        if (!$result) {
            return json_encode(0);
        } else {
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return json_encode($rows);
        }
    }

    public function remove_customer($cid, $addressid) {
        // $sql = "select addressid from customer where id=?";
        // $stmt = $this->$pdo->prepare($sql);
        // $stmt->execute(array($cid));
        // $addr = $stmt->fetch(PDO::FETCH_ASSOC)['addressid'];

        // $sql = "select id from cart where cid=?";
        // $stmt = $this->$pdo->prepare($sql);
        // $stmt->execute(array($cid));
        // $cartid = $stmt->fetch(PDO::FETCH_ASSOC)['id'];

        $sql = "delete from customer where id = ?";
        $stmt = $this->$pdo->prepare($sql);
        $stmt->execute(array($cid));

        // $sql = "delete from home_cust where cid=?";
        // $stmt = $this->$pdo->prepare($sql);
        // $stmt->execute(array($cid));

        // $sql = "delete from busi_cust where cid=?";
        // $stmt = $this->$pdo->prepare($sql);
        // $stmt->execute(array($cid));

        // $sql = "delete from cart where cid=?";
        // $stmt = $this->$pdo->prepare($sql);
        // $stmt->execute(array($cid));

        // $sql = "delete from address where id = ?";
        // $stmt = $this->$pdo->prepare($sql);
        // $stmt->execute(array($addressid));

        // $sql = "delete from product_in_cart where cartid=?";
        // $stmt = $this->$pdo->prepare($sql);
        // $stmt->execute(array($cartid));

        return json_encode(1);
    }

    public function get_customer_detail($cid, $kind) {
        if ($kind == 0) {
            $sql = "select c.name cname, c.email, c.gai, c.password, h.age, h.gender, h.married, a.street, a.city, a.zipcode, s.name as sname from customer c, home_cust h, address a, state s where c.id = ? and c.id = h.cid and c.addressid = a.id and a.stateid = s.id";
            $stmt = $this->$pdo->prepare($sql);
            $result = $stmt->execute(array($cid));
            if (!$result) {
                return json_encode(0);
            } else {
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                return json_encode($rows);
            }
        } else {
            $sql = "select c.name cname, c.email, c.gai, c.password, b.category, a.street, a.city, a.zipcode, s.name as sname from customer c, busi_cust b, address a, state s where c.id = ? and c.id = b.cid and c.addressid = a.id and a.stateid = s.id";
            $stmt = $this->$pdo->prepare($sql);
            $result = $stmt->execute(array($cid));
            if (!$result) {
                return json_encode(0);
            } else {
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                return json_encode($rows);
            }
        }
    }

    public function update_customer($name, $gai, $cid) {
        $sql = "update customer set name = ?, gai = ? where id = ?";
        $stmt = $this->$pdo->prepare($sql);
        $stmt->execute(array($name, $gai, $cid));
    }

    public function update_address($street, $city, $zipcode, $stateid, $addressid) {
        $sql = "update address set street=?, city=?, zipcode=?, stateid=? where id = ?";
        $stmt = $this->$pdo->prepare($sql);
        $stmt->execute(array($street, $city, $zipcode, $stateid, $addressid));
    }

    public function update_home_cust($age, $gender, $married, $cid) {
        $sql = "update home_cust set age = ?, gender = ?, married = ? where cid = ?";
        $stmt = $this->$pdo->prepare($sql);
        $stmt->execute(array($age, $gender, $married, $cid));
    }

    public function update_busi_cust($category, $cid) {
        $sql = "update busi_cust set category = ? where cid = ?";
        $stmt = $this->$pdo->prepare($sql);
        $stmt->execute(array($category, $cid));
    }

    public function view_product() {
        $sql = "select * from product limit 50";
        $stmt = $this->$pdo->prepare($sql);
        $result = $stmt->execute(array());
        if (!$result) {
            return json_encode(0);
        } else {
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return json_encode($rows);
        }
    }

    public function add_product($name, $description, $year,
    $vendorprice, $retailprice, $stock, $sold, $picture, $cateid, $vendorid) {
        $sql = "insert into product (name, description, year, vendorprice, retailprice, stock, sold, picture, cateid, vendorid) values (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->$pdo->prepare($sql);
        $result = $stmt->execute(array($name, $description, $year, $vendorprice, $retailprice, $stock, 0, $picture, $cateid, $vendorid));
        if (!$result) {
            return json_encode(0);
        } else {
            return json_encode(1);
        }
    }

    public function remove_product($pid) {
        $sql = "delete from product where id = ?";
        $stmt = $this->$pdo->prepare($sql);
        $result = $stmt->execute(array($pid));
        if (!$result) {
            return json_encode(0);
        } else {
            return json_encode(1);
        }
    }

    public function update_product($name, $description, $year,
    $vendorprice, $retailprice, $stock, $sold, $picture, $pid) {
        $sql = "update product set name = ?, description = ?, year = ?, vendorprice = ?, retailprice = ?, stock = ?, sold = ?, picture = ? where id = ?";
        $stmt = $this->$pdo->prepare($sql);
        $result = $stmt->execute(array($name, $description, $year,
        $vendorprice, $retailprice, $stock, $sold, $picture, $pid));
        if (!$result) {
            return json_encode(0);
        } else {
            return json_encode(1);
        }
    }

    public function get_parent_category() {
        $sql = "select * from parent_category";
        $stmt = $this->$pdo->prepare($sql);
        $result = $stmt->execute(array());
        if (!$result) {
            return json_encode(0);
        } else {
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return json_encode($rows);
        }
    }

    public function get_child_category($parent_category) {
        $sql = "select * from category where parentid = ?";
        $stmt = $this->$pdo->prepare($sql);
        $result = $stmt->execute(array($parent_category));
        if (!$result) {
            return json_encode(0);
        } else {
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return json_encode($rows);
        }
    }

    public function view_store() {
        $sql = "select s.id, s.name storename, stf.name mgrname, r.name regionname, sta.name statename, a.city, a.street,a.zipcode from store s,address a, state sta, staff stf, region r where s.addressid = a.id and a.stateid = sta.id and s.mgrid = stf.id and s.regionid = r.id";
        $stmt = $this->$pdo->prepare($sql);
        $result = $stmt->execute(array());
        if (!$result) {
            return json_encode(0);
        } else {
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return json_encode($rows);
        }
    }

    public function update_store($storeid, $name) {
        $sql = "update store set name = ? where id = ?";
        $stmt = $this->$pdo->prepare($sql);
        $result = $stmt->execute(array($name, $storeid));
        if (!$result) {
            return json_encode(0);
        } else {
            return json_encode(1);
        }
    }

    public function remove_store($storeid) {
        $sql = "delete from store where id = ?";
        $stmt = $this->$pdo->prepare($sql);
        $result = $stmt->execute(array($storeid));
        if (!$result) {
            return json_encode(0);
        } else {
            return json_encode(1);
        }
    }

    // return staffs that dont currently work in any stores
    public function select_available_staff() {
        $sql = "select staff.id, staff.name from staff where not exists (select staffid from work_in)";
        $stmt = $this->$pdo->prepare($sql);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $rows;
    }

    // return staffs that dont currently work in any stores
    // that has level less than 4
    public function select_all_available_staff() {
        $sql = "select staff.id, staff.name, staff.level, staff.position from staff where not exists (select staffid from work_in where staffid = staff.id) and staff.level < 4 order by staff.level desc";
        $stmt = $this->$pdo->prepare($sql);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return json_encode($rows);
    }

    public function get_regionid($mgrid) {
        $sql = "select id from region where mgrid = ?";
        $stmt = $this->$pdo->prepare($sql);
        $stmt->execute(array($mgrid));
        $regionid = $stmt->fetch(PDO::FETCH_ASSOC)['id'];
        return $regionid;
    }

    // // should be combined with insert_address
    // // use add_staff right after insert_address
    // public function add_staff($email, $password, $name, $position, $level, $salary) {
    //     $addr_id = $this->$pdo->lastInsertId();
    //     $salt = $this->random_salt();
    //     $salted_password = $this->hash_password($password, $salt);
    //     $addr_id = $this->$pdo->lastInsertId();
    //     $sql = "insert into staff (email, password, salt, name,
    //     position, level, salary, addressid)
    //     values (?, ?, ?, ?, ?, ?, ?, ?)";
    //     $stmt = $this->$pdo->prepare($sql);
    //     $stmt->execute();
    // }

    // should be combined with insert_address
    // use add_store right after insert_address
    public function add_store($name, $regionid, $mgrid, $street, $city, $zipcode, $stateid) {
        $this->insert_address($street, $city, $zipcode, $stateid);
        $addr_id = $this->$pdo->lastInsertId();
        $sql = "insert into store (name, regionid, mgrid, addressid) values (?, ?, ?, ?)";
        $stmt = $this->$pdo->prepare($sql);
        $stmt->execute(array($name, $regionid, $mgrid, $addr_id));
        $store_id = $this->$pdo->lastInsertId();

        $sql = "update staff set position = 'Retail Store Manager', level = 3 where staff.id = ?";
        $stmt = $this->$pdo->prepare($sql);
        $stmt->execute(array($mgrid));

        $sql = "insert into work_in values (?, ?)";
        $stmt = $this->$pdo->prepare($sql);
        $stmt->execute(array($mgrid, $store_id));

        return json_encode(1);
    }

    // set an available staff to manager
    // will change position and level of old and new managers
    // public function change_manager($staffid, $storeid) {
    //     $sql = "select staff.id from staff inner join store
    //     on store.mgrid = staff.id where store.mgrid=?";
    //     $stmt = $this->$pdo->prepare($sql);
    //     $stmt->execute(array($staffid));
    //     $id = $stmt->fetch(PDO::FETCH_ASSOC)['id'];

    //     $sql = "update store set mgrid=? where id=?";
    //     $stmt = $this->$pdo->prepare($sql);
    //     $stmt->execute(array($staffid, $storeid));

    //     $sql = "update staff set position='Retail Store Manager', level=3 where id=?";
    //     $stmt = $this->$pdo->prepare($sql);
    //     $stmt->execute(array($staffid));

    //     $sql = "update staff set position='Retail Management Associate III' where id=?";
    //     $stmt = $this->$pdo->prepare($sql);
    //     $stmt->execute(array($id));
    // }


    // // modify $this->addrid
    // public function remove_staff($staffid) {
    //     if ($this->is_manager($staffid) == false) {
    //         return false;
    //     }

    //     $sql = "delete from staff where id=?";
    //     $stmt = $this->pdo->prepare($sql);
    //     $stmt->execute(array($staffid));

    //     $sql = "delete from work_in where staffid=?";
    //     $stmt = $this->pdo->prepare($sql);
    //     $stmt->execute(array($staffid));

    //     $sql = "delete from address where staffid=?";
    //     $stmt = $this->pdo->prepare($sql);
    //     $stmt->execute(array($this->$addrid));
    // }

    // public function is_manager($staffid) {
    //     $sql = "select from store where mgrid=?";
    //     $stmt = $this->pdo->prepare($sql);
    //     $stmt->execute(array($staffid));
    //     if($stmt->rowCount() == 0) {
    //         return false;
    //     }
    //     return true;
    // }

}

?>

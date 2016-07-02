<?php
    
	/* 
		This is an example class script proceeding secured API
		To use this class you should keep same as query string and function name
		Ex: If the query string value rquest=delete_user Access modifiers doesn't matter but function should be
		     function delete_user(){
				 You code goes here
			 }
		Class will execute the function dynamically;
		
		usage :
		
		    $object->response(output_data, status_code);
			$object->_request	- to get santinized input 	
			
			output_data : JSON (I am using)
			status_code : Send status message for headers
			
		Add This extension for localhost checking :
			Chrome Extension : Advanced REST client Application
			URL : https://chrome.google.com/webstore/detail/hgmloofddffdnphfgcellkdfbfbjeloo
		
		I used the below table for demo purpose.
		
		CREATE TABLE IF NOT EXISTS `users` (
		  `user_id` int(11) NOT NULL AUTO_INCREMENT,
		  `user_fullname` varchar(25) NOT NULL,
		  `user_email` varchar(50) NOT NULL,
		  `user_password` varchar(50) NOT NULL,
		  `user_status` tinyint(1) NOT NULL DEFAULT '0',
		  PRIMARY KEY (`user_id`)
		) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;
 	*/
	
	require_once("Rest.inc.php");
	include_once('db.configuration.php');
	
	class API extends REST {
	
		public $data = "";		
		private $db = NULL;	
		public function __construct(){
			parent::__construct();				// Init parent contructor
			$this->dbConnect();					// Initiate Database connection
		}
		
		/*
		 *  Database connection 
		*/
		private function dbConnect(){

			$this->db = mysql_connect(DB_SERVER,DB_USER,DB_PASSWORD);
			if($this->db)
				mysql_select_db(DB,$this->db);
		}
		
		/*
		 * Public method for access api.
		 * This method dynmically call the method based on the query string
		 *
		 */
		public function processApi(){
			$func = strtolower(trim(str_replace("/","",$_REQUEST['rquest'])));
			if((int)method_exists($this,$func) > 0)
				$this->$func();
			else
				$this->response('',404);				// If the method not exist with in this class, response would be "Page not found".
		}
		
		/* 
		 *	Simple login API
		 *  Login must be POST method
		 *  email : <USER EMAIL>
		 *  pwd : <USER PASSWORD>
		 */
		
		private function login(){
			// Cross validation if the request method is POST else it will return "Not Acceptable" status
			if($this->get_request_method() != "POST"){
				$this->response('',406);
			}
			
			/*$postdata = file_get_contents("php://input");
			$request = json_decode($postdata);	*/	
			
			$email = $this->_request['email'];		
			$password = $this->_request['pwd'];
			
			// Input validations
			if(!empty($email) and !empty($password)){
				if(filter_var($email, FILTER_VALIDATE_EMAIL)){
					
					$sql = mysql_query("SELECT user_id, user_fullname, user_email,user_type,user_status FROM users WHERE user_email = '$email' AND user_password = '".md5($password)."' LIMIT 1", $this->db);
					if(mysql_num_rows($sql) > 0){
						$result = mysql_fetch_array($sql,MYSQL_ASSOC);
						
						// If success everythig is good send header as "OK" and user details
						$this->response($this->json($result), 200);
					}
					$error = array('status' => "Failed", "msg" => "Invalid Email address or Password");
					$this->response($this->json($error), 400);	// If no records "No Content" status
				}
			}
			
			// If invalid inputs "Bad Request" status message and reason
			$error = array('status' => "Failed", "msg" => "Invalid Email address or Password");
			$this->response($this->json($error), 400);
		}

	private function registerUser()
	{
			if($this->get_request_method() != "POST"){
				$this->response('',406);
			}
			$user_row = array();
			$user_error_row=array();
			foreach( $this->_request['users'] as $row )
			{
				$email=$row['email'];
				$sql = mysql_query("SELECT user_id, user_fullname, user_email FROM users WHERE user_email = '$email' LIMIT 1");
				if(mysql_num_rows($sql) == 0)
				{
					$user_row[] = '("'.mysql_real_escape_string($row['name']).'", "'.$row['email'].'","'.md5($row['password']).'","1","'.$row['usertype']['id'].'")';
				}
				else
				{
					$user_error_row[]=$row['email'];
				}
			
			}

			if(sizeof($user_error_row)==0)
			{
				
				mysql_query('INSERT INTO users (user_fullname, user_email,user_password,user_status,user_type) VALUES '.implode(',', $user_row));
				$userlist=$this->getUsers();
				$data = array('status' => "Success", "msg" => "New user added successfully","users"=>$userlist);
				$this->response($this->json($data), 200);
			}
			else
			{
				$msg=implode(',', $user_error_row);
				$msg="[".$msg."]"." Already exists in the system";				
				$data = array('status' => "Error", "msg" => $msg);
				$this->response($this->json($data), 400);
			}

			
	}

	private function updateUser()
	{
			if($this->get_request_method() != "POST"){
				$this->response('',406);
			}
			foreach( $this->_request['users'] as $row )
			{
				$sql='';				
				if(!empty($row['password']));
				{
					
					$sql=$sql.'user_password='."'".md5($row['password'])."'".',';
				}

				$sql=$sql.'user_fullname='."'".$row['name']."'".',user_type='.$row['usertype']['id'];
				$user_id=$row['id'];
				mysql_query("UPDATE users set $sql where user_id=$user_id");		
				
				//mysql_query("UPDATE users set user_fullname=,user_type=")
			}

			$userlist=$this->getUsers();
			$data = array('status' => "Success",'msg'=>"User details updated successfully", "users"=>$userlist);
			$this->response($this->json($data), 200);

	}
		
		private function uploadStock(){			
			if($this->get_request_method() != "POST"){
				$this->response('',406);
			}

			$prod_row = array();
			$prod_history_row=array();
			$update_row=array() ;
			$current_user_id=$this->_request['currentUser'][0]['user_id'];
			$mode=$this->_request['mode'];
			$recordType=$this->_request['recordType'];
			$actionHappened=false;
			if($recordType=='new' || $recordType=='update')
			{
				foreach( $this->_request['products'] as $row )
				{
					$row_qty=(int)abs($row['qty']);
					$category_id=$row['category']['id'];
					$brand_id=$row['brand']['id'];
					$prod_name=mysql_real_escape_string($row['prod_name']);
					$dup_check='SELECT * from products where sku_id="'.$row['sku_id'].'"  LIMIT 1';		
					$dup_count=mysql_query($dup_check,$this->db);
					$dup_row=mysql_fetch_assoc($dup_count);
					if($recordType=='new')
					{
						if(mysql_num_rows($dup_count) == 0)
						{

						$prod_row[] = '("'.mysql_real_escape_string($row['prod_name']).'","'.mysql_real_escape_string($row['sku_id']).'", '.$row_qty.',"'.$current_user_id.'","'.$row['purchase_date'].'",'.$category_id.','.$brand_id.')';
						$prod_history_row[]='("'.mysql_real_escape_string($row['sku_id']).'", '.$row_qty.','.$current_user_id.',"'.$row['purchase_date'].'")';
						}
						else
						{
							$prod_list=$this->productsList();				
							$prod_history=$this->stockHistory();
							$categories=$this->categories();
							$brands=$this->brands();
							$msg='Product Already exists';
							$data = array('status' => "Failure", "msg" => $msg,"prod_list"=>$prod_list,"prod_history"=>$prod_history,"categories"=>$categories,"brands"=>$brands);
							$this->response($this->json($data), 400);
							exit;
						}
					}

					if($recordType=='update')
					{
							if(mysql_num_rows($dup_count) == 0)
							{
								$prod_list=$this->productsList();				
								$prod_history=$this->stockHistory();
								$categories=$this->categories();
								$brands=$this->brands();
								$msg='Product does not exists';
								$data = array('status' => "Failure", "msg" => $msg,"prod_list"=>$prod_list,"prod_history"=>$prod_history,"categories"=>$categories,"brands"=>$brands);
								$this->response($this->json($data), 400);
								exit;
							}
							else
							{
					$total_qty=(int)$dup_row['qty']+$row_qty;
					$sku_id=$row['sku_id'];
					$update_row[]=$row['sku_id'];					
					$purchase_date=$row['purchase_date'];
					mysql_query("update products set prod_name='$prod_name',qty=$total_qty,updated_by=$current_user_id,purchase_date='$purchase_date',category_id=$category_id,brand_id=$brand_id where sku_id='$sku_id'");
					$actionHappened=true;
							$prod_history_row[]='("'.mysql_real_escape_string($row['sku_id']).'", '.$row_qty.','.$current_user_id.',"'.$row['purchase_date'].'")';
							}
					}


					
				}
			}

			else{

			foreach( $this->_request['products'] as $row ) 
			{
				$row_qty=(int)abs($row['qty']);
				$category_id=$row['brand_id'];
				$brand_id=$row['category_id'];
				$prod_name=mysql_real_escape_string($row['prod_name']);
				$dup_check='SELECT * from products where sku_id="'.$row['sku_id'].'"  LIMIT 1';		
				$dup_count=mysql_query($dup_check,$this->db);
				$dup_row=mysql_fetch_assoc($dup_count);				
				if(mysql_num_rows($dup_count) == 0){			
				$prod_row[] = '("'.$prod_name.'","'.mysql_real_escape_string($row['sku_id']).'", '.$row['qty'].',"'.$current_user_id.'","'.$row['purchase_date'].'",'.$category_id.','.$brand_id.')';
				}
				else
				{
					if($mode=="Edit")
					{
						$total_qty=$row_qty;
					}
					else
					{
						$total_qty=(int)$dup_row['qty']+$row_qty;
					}
										
					$sku_id=$row['sku_id'];
					$update_row[]=$row['sku_id'];					
					$purchase_date=$row['purchase_date'];
					mysql_query("update products set qty=$total_qty,updated_by=$current_user_id,purchase_date='$purchase_date' where sku_id='$sku_id'");
					$actionHappened=true;
				}
				
				if($mode!="Edit")
				{
				$prod_history_row[]='("'.mysql_real_escape_string($row['sku_id']).'", '.$row_qty.','.$current_user_id.',"'.$row['purchase_date'].'")';
				}
				

			}

		}


			if(sizeof($prod_row)!=0)
			{
				mysql_query('INSERT INTO products (prod_name,sku_id, qty,upload_by,purchase_date,category_id,brand_id) VALUES '.implode(',', $prod_row));	
				$actionHappened=true;			
			}
			if(sizeof($prod_history_row)!=0)
			{
				mysql_query('INSERT INTO products_stock_history (sku_id, qty,upload_by,purchase_date) VALUES '.implode(',', $prod_history_row));
				$actionHappened=true;
			}

			if($actionHappened)
			{
				$prod_list=$this->productsList();				
				$prod_history=$this->stockHistory();
				$categories=$this->categories();
				$brands=$this->brands();
				$msg='Product uploaded successfully';
				if($mode=='Edit')
				{
					$msg='Product updated successfully';
				}
				if($recordType=="new")
				{
					$msg='Product saved successfully';
				}
				if($recordType=="update")
				{
					$msg='Product updated successfully';
				}

				$data = array('status' => "Success", "msg" => $msg,"prod_list"=>$prod_list,"prod_history"=>$prod_history,"categories"=>$categories,"brands"=>$brands);
				$this->response($this->json($data), 200);
			}
			else
			{
				
					$prod_list=$this->productsList();					
					$prod_history=$this->stockHistory();
					$categories=$this->categories();
					$brands=$this->brands();
					$data = array('status' => "Failed", "msg" => "Error while uploading the file","prod_list"=>$prod_list,"prod_history"=>$prod_history,"categories"=>$categories,"brands"=>$brands);
					$this->response($this->json($data), 400);
				
			}

		}

private function getAllProducts()
{
			if($this->get_request_method() != "GET"){
				$this->response('',406);
			}
				$prod_list=$this->productsList();
				$data = array('status' => "Success", "prod_list"=>$prod_list);
				$this->response($this->json($data), 200);

}

private function getAllSalesList()
{
			if($this->get_request_method() != "GET"){
				$this->response('',406);
			}
				$prod_sales_list=$this->prodSalesList();
				$prod_list=$this->productsList();
				$data = array('status' => "Success", "prod_sales_list"=>$prod_sales_list,"prod_list"=>$prod_list);
				$this->response($this->json($data), 200);

}

private function getAllReturnList()
{
			if($this->get_request_method() != "GET"){
				$this->response('',406);
			}
				$prod_list=$this->productsList();
				$prod_return_list=$this->prodReturnList();
				$data = array('status' => "Success", "prod_list"=>$prod_list,"prod_return_list"=>$prod_return_list);
				$this->response($this->json($data), 200);

}

	private function productsList()
	{
					$prod_list_sql = mysql_query("SELECT p.prod_name,p.id,p.sku_id,p.qty,p.purchase_date,p.updated_at,u.user_fullname as upload_by,us.user_fullname as updated_by,cat.id as category,cat.cat_name,br.id as brand,br.brand_name FROM products p LEFT JOIN users u ON  p.upload_by=u.user_id LEFT JOIN users us ON p.updated_by=us.user_id LEFT JOIN category cat ON cat.id=p.category_id LEFT JOIN brand br ON br.id=p.brand_id");
					$my_prod = array();
					if(mysql_num_rows($prod_list_sql) > 0){
						
						while($row = mysql_fetch_assoc($prod_list_sql)){
						  $my_prod[] = $row;
						  
						}
						
					}

					return $my_prod;
	}

		private function stockHistory()
	{
					$prod_list_sql = mysql_query("SELECT sh.id,sh.sku_id,sh.qty,sh.purchase_date,sh.created_at,u.user_fullname as upload_by FROM products_stock_history sh LEFT JOIN users u ON  sh.upload_by=u.user_id");
					$my_prod = array();
					if(mysql_num_rows($prod_list_sql) > 0){
						
						while($row = mysql_fetch_assoc($prod_list_sql)){
						  $my_prod[] = $row;
						  
						}
						
					}

					return $my_prod;
	}

		private function prodReturnList()
	{
					$prod_list_sql = mysql_query("SELECT pr.id,pr.sku_id,pr.qty,pr.portal,pr.return_date,pr.status,pr.created_at,pr.upload_by as id_upload,pr.remark,u.user_fullname as upload_by,pr.is_sold as isSold,pr.is_damaged as isDamaged FROM prod_return pr LEFT JOIN users u ON  pr.upload_by=u.user_id");
					$my_prod = array();
					if(mysql_num_rows($prod_list_sql) > 0){
						
						while($row = mysql_fetch_assoc($prod_list_sql)){
						  $my_prod[] = $row;
						  
						}
						
					}

					return $my_prod;
	}

		private function prodSalesList()
	{
					$prod_list_sql = mysql_query("SELECT ps.id,ps.sku_id,ps.qty,ps.portal,ps.sales_date,ps.created_at,ps.status,ps.remark,ps.upload_by as id_upload, u.user_fullname as upload_by FROM prod_sales ps LEFT JOIN users u ON  ps.upload_by=u.user_id  order by ps.created_at desc");
					$my_prod = array();
					if(mysql_num_rows($prod_list_sql) > 0){
						
						while($row = mysql_fetch_assoc($prod_list_sql)){
						  $my_prod[] = $row;
						  
						}
						
					}

					return $my_prod;
	}

		private function getAllPortals()
		{
					if($this->get_request_method() != "GET"){
						$this->response('',406);
					}
						$portals=$this->portalList();
						$onlineSalesList=$this->onlineSalesList();						
						$orderStatusList=$this->orderStatusList();
						$shippingStatusList=$this->shippingStatusList();
						$userlist=$this->getUsers();
						$data = array('status' => "Success", "portals"=>$portals,"onlineSalesList"=>$onlineSalesList,"orderStatusList"=>$orderStatusList,"shippingStatusList"=>$shippingStatusList,"userlist"=>$userlist);
						$this->response($this->json($data), 200);
		}

		private function uploadSales(){			
			if($this->get_request_method() != "POST"){
				$this->response('',406);
			}

			$prod_row = array(); 
			$errors=array();
			$current_user_id=$this->_request['currentUser'][0]['user_id'];
			$mode=$this->_request['mode'];
			$actionHappened=false;
			foreach( $this->_request['products'] as $row ) 
			{
				$row_qty=(int)abs($row['qty']);
				$prod_check='SELECT * from products where sku_id="'.$row['sku_id'].'" LIMIT 1';	
				$prod_count=mysql_query($prod_check,$this->db);			
				if(mysql_num_rows($prod_count) == 0)
				{
					$errors[]=$row['sku_id'];		
				}
				else
				{
					if($mode=="Edit")
					{
					
						$sku_id=$row['sku_id'];	
						$row_id=(int)$row['id'];				
						$current_date = date('Y-m-d H:i:s');
						$sales_date=$row['sales_date'];
						$remark=$row['remark'];	
						$portal=$row['portal'];					
						mysql_query("update prod_sales set qty=$row_qty,upload_by=$current_user_id,sales_date='$sales_date',remark='$remark',portal='$portal' where id=$row_id");
						$actionHappened=true;
					}
				}

				if($mode!='Edit')
				{
					$prod_row[] = '("'.mysql_real_escape_string($row['sku_id']).'", '.$row_qty.',"'.$row['portal'].'",'.$current_user_id.',"'.$row['sales_date'].'","'.$row['remark'].'")';
				}

			}

			$errors = array_filter($errors);
			if(!empty($errors))
			{
					$prod_sales_list=$this->prodSalesList();
					$prod_list=$this->productsList();
					$msg="Product [ ".implode(", ", $errors)." ] does not exists";
					$data = array('status' => "Failed", "msg" => $msg,"prod_list"=>$prod_list,"prod_sales_list"=>$prod_sales_list);
					$this->response($this->json($data), 400);
			}

		if(sizeof($prod_row)!=0)
		{
			if(mysql_query('INSERT INTO prod_sales (sku_id, qty,portal,upload_by,sales_date,remark) VALUES '.implode(',', $prod_row)))
			{
				$actionHappened=true;				
			}
		}

		if($actionHappened)
		{
				$msg='Product sales uploaded successfully';
				if($mode=='Edit')
				{
					$msg='Product sales updated successfully';
				}
				$prod_sales_list=$this->prodSalesList();
				$prod_list=$this->productsList();
				$data = array('status' => "Success", "msg" => $msg,"prod_list"=>$prod_list,"prod_sales_list"=>$prod_sales_list);
				$this->response($this->json($data), 200);
		}
		else
		{
				$msg='Error while push data to DB';
				$prod_sales_list=$this->prodSalesList();
				$prod_list=$this->productsList();
				$data = array('status' => "Success", "msg" => $msg,"prod_list"=>$prod_list,"prod_sales_list"=>$prod_sales_list);
				$this->response($this->json($data), 400);
		}
			

		}

		private function uploadReturn(){			
			if($this->get_request_method() != "POST"){
				$this->response('',406);
			}

			$prod_row = array();
			$errors=array();
			$current_user_id=$this->_request['currentUser'][0]['user_id'];
			$mode=$this->_request['mode'];
			$actionHappened=false;
			foreach( $this->_request['products'] as $row ) 
			{
				$row_qty=(int)abs($row['qty']);
				$is_sold=$row['isSold'];
				$is_damaged=$row['isDamaged'];
									
				$prod_check='SELECT * from products where sku_id="'.$row['sku_id'].'" LIMIT 1';	
				$prod_count=mysql_query($prod_check,$this->db);
				if(mysql_num_rows($prod_count) == 0)
				{
					$errors[]=$row['sku_id'];		
				}
				else
				{
					if($mode=="Edit")
					{
						$sku_id=$row['sku_id'];	
						$row_id=(int)$row['id'];				
						$current_date = date('Y-m-d H:i:s');
						$return_date=$row['return_date'];
						$remark=$row['remark'];	
						$portal=$row['portal'];											
						mysql_query("update prod_return set qty=$row_qty,upload_by=$current_user_id,return_date='$return_date',remark='$remark',portal='$portal',is_sold=$is_sold,is_damaged=$is_damaged where id=$row_id");
						$actionHappened=true;
					}
				}

				if($mode!="Edit")
				{
					$prod_row[] = '("'.mysql_real_escape_string($row['sku_id']).'", '.$row_qty.',"'.$row['portal'].'",'.$current_user_id.',"'.$row['return_date'].'","'.$row['remark'].'",'.$is_sold.','.$is_damaged.')';
				}
				

			}

			$errors = array_filter($errors);
			if(!empty($errors))
			{
					$prod_return_list=$this->prodReturnList();
					$prod_list=$this->productsList();
					$msg="Product [ ".implode(", ", $errors)." ] does not exists";
					$data = array('status' => "Success", "msg" => $msg,"prod_list"=>$prod_list,"prod_return_list"=>$prod_return_list);
					$this->response($this->json($data), 400);
			}

		if(sizeof($prod_row)!=0)
		{
			if(mysql_query('INSERT INTO prod_return (sku_id, qty,portal,upload_by,return_date,remark,is_sold,is_damaged) VALUES '.implode(',', $prod_row)))
			{
				$actionHappened=true;
			}
		}


				if($actionHappened)
		{
				$msg='Product sales uploaded successfully';
				if($mode=='Edit')
				{
					$msg='Product sales updated successfully';
				}
				$prod_return_list=$this->prodReturnList();
				$prod_list=$this->productsList();
				$data = array('status' => "Success", "msg" => $msg,"prod_list"=>$prod_list,"prod_return_list"=>$prod_return_list);
				$this->response($this->json($data), 200);
		}
		else
		{
				$msg='Error while push data to DB';
				$prod_return_list=$this->prodReturnList();
				$prod_list=$this->productsList();
				$data = array('status' => "Success", "msg" => $msg,"prod_list"=>$prod_list,"prod_return_list"=>$prod_return_list);
				$this->response($this->json($data), 400);
		}
			

		}

	private function portalList()
	{
					$prod_list_sql = mysql_query("SELECT * from portals order by id");
					$my_prod = array();
					if(mysql_num_rows($prod_list_sql) > 0){
						
						while($row = mysql_fetch_assoc($prod_list_sql)){
						  $my_prod[] = $row;
						  
						}
						
					}

					return $my_prod;
	}
	private function ordersGroupBySku($portal_id)
	{


					$prod_list_sql = mysql_query("SELECT os.*,sum(case when os.order_status=1 or os.order_status=2 then 1 else 0 end) as no_of_order,u.user_fullname,au.user_fullname as assigned_name, po.portal_name,po.id as portal_id,ss.ss_name,ors.os_name,ffy.ff_type_name from online_sales os LEFT JOIN users u on u.user_id=os.upload_by LEFT JOIN portals po ON po.id=os.portal LEFT JOIN shipping_status ss ON ss.id=os.shipping_status LEFT JOIN order_status ors ON ors.id=os.order_status LEFT JOIN full_filement_types ffy ON ffy.id=os.fullfilement_type LEFT JOIN users au ON au.user_id=os.assign_to where os.portal=$portal_id and(os.order_status=1 or os.order_status=2) group by os.sku_id order by os.updated_on DESC");
					$my_prod = array();
					if(mysql_num_rows($prod_list_sql) > 0){
						
						while($row = mysql_fetch_assoc($prod_list_sql)){
						  $my_prod[] = $row;
						  
						}
						
					}

					return $my_prod;

	}

		private function onlineSalesList()
	{
					$prod_list_sql = mysql_query("SELECT os.*,u.user_fullname,au.user_fullname as assigned_name, po.portal_name,po.id as portal_id,ss.ss_name,ors.os_name,ffy.ff_type_name from online_sales os LEFT JOIN users u on u.user_id=os.upload_by LEFT JOIN portals po ON po.id=os.portal LEFT JOIN shipping_status ss ON ss.id=os.shipping_status LEFT JOIN order_status ors ON ors.id=os.order_status LEFT JOIN full_filement_types ffy ON ffy.id=os.fullfilement_type LEFT JOIN users au ON au.user_id=os.assign_to  order by os.invoice_date desc");
					$my_prod = array();
					if(mysql_num_rows($prod_list_sql) > 0){
						
						while($row = mysql_fetch_assoc($prod_list_sql)){
						  $my_prod[] = $row;
						  
						}
						
					}

					return $my_prod;
	}

		private function orderStatusList()
	{
					$order_status_sql = mysql_query("SELECT * FROM order_status");
					$my_prod = array();
					if(mysql_num_rows($order_status_sql) > 0){
						
						while($row = mysql_fetch_assoc($order_status_sql)){
						  $my_prod[] = $row;
						  
						}
						
					}

					return $my_prod;
	}

		private function shippingStatusList()
	{
					$shipping_status_sql = mysql_query("SELECT * FROM shipping_status");
					$my_prod = array();
					if(mysql_num_rows($shipping_status_sql) > 0){
						
						while($row = mysql_fetch_assoc($shipping_status_sql)){
						  $my_prod[] = $row;
						  
						}
						
					}

					return $my_prod;
	}

private function updateStock()
{
		if($this->get_request_method() != "POST"){
				$this->response('',406);
			}

		$current_user_id=$this->_request['currentUser'][0]['user_id'];
		$current_user_type=$this->_request['currentUser'][0]['user_type'];
		$processType=$this->_request['processType'][0];	
		if($processType=="return")
		{
			if($current_user_type==1)
			{			
				$return_query=mysql_query("SELECT sku_id, SUM( qty ) as return_qty,upload_by FROM prod_return where status=0 GROUP BY sku_id;");
				$prod_return_ids=mysql_query("SELECT * FROM prod_return where status=0");
			}
			else
			{
				$return_query=mysql_query("SELECT sku_id, SUM( qty ) as return_qty,upload_by FROM prod_return where status=0 and upload_by=$current_user_id GROUP BY sku_id;");
				$prod_return_ids=mysql_query("SELECT * FROM prod_return where status=0 and upload_by=$current_user_id");
			}

			$return_arry=[];
			$uniq_sku=[];
			while($return_row = mysql_fetch_assoc($return_query))
			{
				$return_arry[$return_row['sku_id']]=$return_row;
				$uniq_sku[]=$return_row['sku_id'];
			}

			$return_ids_arry=[];
			while($prod_return_ids_rows = mysql_fetch_assoc($prod_return_ids))
			{
				$return_ids_arry[]=$prod_return_ids_rows;
			}
			$prod_skud=array_unique($uniq_sku);

			foreach ($prod_skud as $key => $value) 
			{
				$prod_qty_query=mysql_query("SELECT * from products where sku_id='$value' LIMIT 1");
				$prod_qty_row=mysql_fetch_assoc($prod_qty_query);
				$total_qty=$prod_qty_row['qty']+$return_arry[$value]['return_qty'];
				mysql_query("update products set qty=$total_qty where sku_id='$value'");		
			}

			foreach ($return_ids_arry as $key => $return_id)
			{		
				$id=$return_id['id'];
				mysql_query("update prod_return set status=1 where id=$id");	
			}
			$msg='Product return submitted successfully';
			$prod_return_list=$this->prodReturnList();
			$prod_list=$this->productsList();
			$data = array('status' => "Success", "msg" => $msg,"prod_list"=>$prod_list,"prod_return_list"=>$prod_return_list);
			$this->response($this->json($data), 200);

		}

		if($processType=="sales")
		{

			if($current_user_type==1)
			{			
				$sales_query=mysql_query("SELECT sku_id, SUM( qty ) as sales_qty,upload_by FROM prod_sales where status=0 GROUP BY sku_id;");
				$prod_sales_ids=mysql_query("SELECT * FROM prod_sales where status=0");
			}
			else
			{
				$sales_query=mysql_query("SELECT sku_id, SUM( qty ) as sales_qty,upload_by FROM prod_sales where status=0 and upload_by=$current_user_id GROUP BY sku_id;");
				$prod_sales_ids=mysql_query("SELECT * FROM prod_sales where status=0 and upload_by=$current_user_id");
			}
		
		$sales_arry=[];
		$uniq_sku=[];
		while($sales_row = mysql_fetch_assoc($sales_query))
		{
			$sales_arry[$sales_row['sku_id']]=$sales_row;
			$uniq_sku[]=$sales_row['sku_id'];
		}

		$sales_ids_arry=[];
		while($prod_sales_ids_rows = mysql_fetch_assoc($prod_sales_ids))
		{
			$sales_ids_arry[]=$prod_sales_ids_rows;
		}
		
		$prod_skud=array_unique($uniq_sku);
		foreach ($prod_skud as $key => $value) {
			$prod_qty_query=mysql_query("SELECT * from products where sku_id='$value' LIMIT 1");
			$prod_qty_row=mysql_fetch_assoc($prod_qty_query);
			$total_qty=($prod_qty_row['qty']-$sales_arry[$value]['sales_qty']);				
			$current_date = date('Y-m-d H:i:s');
			mysql_query("update products set qty=$total_qty,updated_at='$current_date' where sku_id='$value'");	
		}

		foreach ($sales_ids_arry as $key => $sales_id) {		
			$id=$sales_id['id'];
			mysql_query("update prod_sales set status=1 where id=$id");	
		}
			$msg='Product sales submitted successfully';
			$prod_sales_list=$this->prodSalesList();
			$prod_list=$this->productsList();
			$data = array('status' => "Success", "msg" => $msg,"prod_list"=>$prod_list,"prod_sales_list"=>$prod_sales_list);
			$this->response($this->json($data), 200);

	}

}




private function stockDetails()
{
		if($this->get_request_method() != "GET"){
				$this->response('',406);
			}

		$prod_history=$this->stockHistory();
		$prod_list=$this->productsList();
		$categories=$this->categories();
		$brands=$this->brands();
		$data = array('status' => "Success", "msg" => $msg,"prod_history"=>$prod_history,"prod_list"=>$prod_list,"categories"=>$categories,"brands"=>$brands);
		$this->response($this->json($data), 200);
}

private function categories()
{
		$sql = mysql_query("SELECT * FROM category where status=0", $this->db);
		$result = array();
		if(mysql_num_rows($sql) > 0){
			
			while($rlt = mysql_fetch_array($sql,MYSQL_ASSOC)){
				$result[] = $rlt;
			}
		}
		return $result;
		
}

private function brands()
{
		$sql = mysql_query("SELECT * FROM brand where status=0", $this->db);
		$result = array();
		if(mysql_num_rows($sql) > 0){
			
			while($rlt = mysql_fetch_array($sql,MYSQL_ASSOC)){
				$result[] = $rlt;
			}
		}
		return $result;
		
}
		private function users(){	
			// Cross validation if the request method is GET else it will return "Not Acceptable" status
			if($this->get_request_method() != "GET"){
				$this->response('',406);
			}
			$sql = mysql_query("SELECT u.user_id,u.user_fullname,u.user_email,u.user_status,u.user_type,u.created_at,ut.name FROM users u LEFT JOIN user_types ut ON u.user_type=ut.id where u.user_status=1", $this->db);
			if(mysql_num_rows($sql) > 0){
				$result = array();
				while($rlt = mysql_fetch_array($sql,MYSQL_ASSOC)){
					$result[] = $rlt;
				}
				// If success everythig is good send header as "OK" and return list of users in JSON format
				$userlist=$this->getUsers();				
				$data = array('status' => "Success", "users"=>$userlist);
				$this->response($this->json($data), 200);
			}
			$this->response('',204);	// If no records "No Content" status
		}

	private function getUsers()
	{
			$sql = mysql_query("SELECT u.user_id,u.user_fullname,u.user_email,u.user_status,u.user_type,u.created_at,ut.name FROM users u LEFT JOIN user_types ut ON u.user_type=ut.id where u.user_status=1", $this->db);
			$result = array();
			if(mysql_num_rows($sql) > 0){
				
				while($rlt = mysql_fetch_array($sql,MYSQL_ASSOC)){
					$result[] = $rlt;
				}
			}
			return $result;
			
	}
		
		private function deleteUser(){
			// Cross validation if the request method is DELETE else it will return "Not Acceptable" status
			if($this->get_request_method() != "DELETE"){
				$this->response('',406);
			}
			$id = (int)$this->_request['id'];

			if($id > 0){				
				mysql_query("DELETE FROM users WHERE user_id = $id");
				$userlist=$this->getUsers();
				$success = array('status' => "Success", "msg" => "user details deleted.","users"=>$userlist);
				$this->response($this->json($success),200);
			}else
				$this->response('',204);	// If no records "No Content" status
		}

		private function deleteProdStockHistory(){
			// Cross validation if the request method is DELETE else it will return "Not Acceptable" status
			if($this->get_request_method() != "DELETE"){
				$this->response('',406);
			}
			$id = (int)$this->_request['id'];

			if($id > 0){				
				mysql_query("DELETE FROM products_stock_history WHERE id = $id");
				$prod_history=$this->stockHistory();
				$success = array('status' => "Success", "msg" => "Stock details deleted.","prod_history"=>$prod_history);
				$this->response($this->json($success),200);
			}else
				$this->response('',204);	// If no records "No Content" status
		}

		private function deleteProd(){
			// Cross validation if the request method is DELETE else it will return "Not Acceptable" status
			if($this->get_request_method() != "DELETE"){
				$this->response('',406);
			}
			$id = (int)$this->_request['id'];
			$sku_id = $this->_request['sku_id'];
			if($id > 0){

				mysql_query("DELETE FROM products WHERE id = $id");				
				mysql_query("DELETE FROM products_stock_history WHERE sku_id = '$sku_id'");
				mysql_query("DELETE FROM prod_return WHERE sku_id = '$sku_id'");
				mysql_query("DELETE FROM prod_sales WHERE sku_id = '$sku_id'");
				$prod_list=$this->productsList();
				$prod_history=$this->stockHistory();
				$success = array('status' => "Success", "msg" => "Product details deleted.","prod_list"=>$prod_list,"prod_history"=>$prod_history);
				$this->response($this->json($success),200);
			}else
				$this->response('',204);	// If no records "No Content" status
		}
		
		private function deleteSales(){
			// Cross validation if the request method is DELETE else it will return "Not Acceptable" status
			if($this->get_request_method() != "DELETE"){
				$this->response('',406);
			}
			$id = (int)$this->_request['id'];

			if($id > 0){				
				mysql_query("DELETE FROM prod_sales WHERE id = $id");					
					$prod_sales_list=$this->prodSalesList();
					$prod_list=$this->productsList();
					$msg="Product sales deleted successfully";
					$data = array('status' => "Failed", "msg" => $msg,"prod_list"=>$prod_list,"prod_sales_list"=>$prod_sales_list);
				$this->response($this->json($data),200);
			}else
				$this->response('',204);	// If no records "No Content" status
		}

		private function deleteReturn(){
			// Cross validation if the request method is DELETE else it will return "Not Acceptable" status
			if($this->get_request_method() != "DELETE"){
				$this->response('',406);
			}
			$id = (int)$this->_request['id'];

			if($id > 0){				
				mysql_query("DELETE FROM prod_return WHERE id = $id");					
				$prod_return_list=$this->prodReturnList();
				$prod_list=$this->productsList();
				$msg="Product return deleted successfully";
				$data = array('status' => "Success", "msg" => $msg,"prod_list"=>$prod_list,"prod_return_list"=>$prod_return_list);
				$this->response($this->json($data),200);
			}else
				$this->response('',204);	// If no records "No Content" status
		}
		/*
		 *	Encode array into JSON
		*/

		private function saveOnlineSales()
		{

		try {

			if($this->get_request_method() != "POST"){
				$this->response('',406);
			}			
			$errors=array();
			$current_user_id=$this->_request['currentUser'][0]['user_id'];
			$table_name=str_replace(' ', '_', strtolower($this->_request['portal']['portal_name']));
			$portal_id=$this->_request['portal']['id'];
			$temp_table_name=$table_name.'_temp';
			$columns=$this->_request['columns'];
			$rows=$this->_request['rows'];
			$current_date_time = date('Y-m-d H:i:s');
			$sql='';
			foreach ($columns as $key => $value) {
				$sql.=$value.' varchar(255),';
			}
			
			$sql.='shipping_status varchar (255),';
			$sql.='delivered_date varchar (255),';
			$sql.='order_status varchar (255),';
			$sql.='upload_by varchar (255),';
			$sql.='fullfilement_type varchar (255),';
			$sql.='portal varchar (255),';

			$custom_column=["shipping_status","delivered_date","order_status","upload_by","fullfilement_type","portal"];
			$custom_row=["1","","1",$current_user_id,"1",$portal_id];

			if($portal_id==1) //Flipkart
			{
				$sql.='order_verified_date varchar (255),';
				$custom_column[]="order_verified_date";
				$custom_row[]=$current_date_time;
				$sql.='email_id varchar (255),';
				$custom_column[]="email_id";
				$custom_row[]="customer@flipkart.com";
				$sql.='courier_name varchar (255),';
				$custom_column[]="courier_name";
				$custom_row[]="Courier name";
				$sql.='reference_code varchar (255),';
				$custom_column[]="reference_code";
				$custom_row[]="012345";
				$sql.='manifest_id varchar (255),';
				$custom_column[]="manifest_id";
				$custom_row[]="012345";
			}

			if($portal_id==2) //Snapdeal
			{
				$sql.='quantity varchar (255),';
				$custom_column[]="quantity";
				$custom_row[]="1";
				$sql.='invoice_date varchar (255),';
				$custom_column[]="invoice_date";
				$custom_row[]=$current_date_time;
				$sql.='address1 varchar (255),';
				$custom_column[]="address1";
				$custom_row[]="address1";
				$sql.='address2 varchar (255),';
				$custom_column[]="address2";
				$custom_row[]="address2";
				$sql.='manifest_id varchar (255),';
				$custom_column[]="manifest_id";
				$custom_row[]="012345";
			}

			if($portal_id==3) //ShopClues
			{
				$sql.='order_verified_date varchar (255),';
				$custom_column[]="order_verified_date";
				$custom_row[]=$current_date_time;
				$sql.='ordered_on varchar (255),';
				$custom_column[]="ordered_on";
				$custom_row[]=$current_date_time;
				$sql.='invoice_no varchar (255),';
				$custom_column[]="invoice_no";
				$custom_row[]="012345";
				$sql.='invoice_date varchar (255),';
				$custom_column[]="invoice_date";
				$custom_row[]=$current_date_time;
				$sql.='vat varchar (255),';
				$custom_column[]="vat";
				$custom_row[]=0;
				$sql.='email_id varchar (255),';
				$custom_column[]="email_id";
				$custom_row[]="customer@shopclues.com";
				$sql.='reference_code varchar (255),';
				$custom_column[]="reference_code";
				$custom_row[]="012345";
				$sql.='manifest_id varchar (255),';
				$custom_column[]="manifest_id";
				$custom_row[]="012345";
			}

			if($portal_id==4) //Amazon
			{

				$sql.='manifest_id varchar (255),';
				$custom_column[]="manifest_id";
				$custom_row[]="012345";
				$sql.='tax varchar (255),';
				$custom_column[]="tax";
				$custom_row[]=0;
				$sql.='tracking_id varchar (255),';
				$custom_column[]="tracking_id";
				$custom_row[]="012345";
				$sql.='reference_code varchar (255),';
				$custom_column[]="reference_code";
				$custom_row[]="012345";

			}

			$sql=rtrim($sql,',');
			$data_rows=[];
			foreach ($rows as $row) {				
				$data_rows[]='("'.implode('","', $row).'","'.implode('","', $custom_row).'")';
			}

			$query="CREATE TABLE IF NOT EXISTS ".$temp_table_name.' ('.$sql.')';
			if(!mysql_query($query))
			{				
				 throw new Exception (mysql_error());
			}
			$values=implode(",", $data_rows);
			$insert_sql="INSERT into ".$temp_table_name.' ('.implode(",", $columns).','.implode(",",$custom_column).')'.' VALUES '.$values;
			
			if(!mysql_query($insert_sql))
			{
				throw new Exception (mysql_error());
			}
			
			
			$temp_column_mapping=array();		
			//Flipkart
			$temp_column_mapping['1'] =array("order_id","order_verified_date","STR_TO_DATE(ordered_on, '%d-%M-%y') as ordered_on","sku_code","quantity","invoice_no_","invoice_amount","STR_TO_DATE(invoice_date__mm_dd_yy_,'%m/%d/%Y') as invoice_date__mm_dd_yy_","vat_cst_rate___","buyer_name","ship_to_name","address_line_1","address_line_2","city","state","pin_code","phone_no","email_id","courier_name","tracking_id","reference_code","manifest_id","shipping_status","delivered_date","order_status","upload_by","fullfilement_type","portal");
			//Snapdeal;
			$temp_column_mapping['2'] =array("suborder_id","order_verified_date","order_created_date","sku_code","quantity","invoice_code","selling_price_per_item","invoice_date","tax_amount_percent","shipping_name","shipping_name","address1","address2","city","state","pin_code","mobile_no","email_id","courier","awb_number","reference_code","manifest_id","shipping_status","delivered_date","order_status","upload_by","fullfilement_type","portal");
			//Shopclues
			$temp_column_mapping['3'] =array("order_no_","order_verified_date","ordered_on","prod_sku_code","pieces","invoice_no","declared_value","invoice_date","vat","ship_to","ship_to","address1","address2","city","state","pincode","mobile_number","email_id","carrier_name","awb_no_","reference_code","manifest_id","shipping_status","delivered_date","order_status","upload_by","fullfilement_type","portal");
			//Amazon
			$temp_column_mapping['4'] =array("order_id","purchase_date","purchase_date","sku","quantity_purchased","order_item_id","cod_collectible_amount","purchase_date","tax","buyer_name","buyer_name","ship_address_1","ship_address_2","ship_city","ship_state","ship_postal_code","buyer_phone_number","buyer_email","fulfilled_by","tracking_id","reference_code","manifest_id","shipping_status","delivered_date","order_status","upload_by","fullfilement_type","portal");

			$common_column_mapping=array();
			$common_column_mapping['onlineSales']=array("order_id","order_verified_date","ordered_on","sku_id","quantity","invoice_no","amount","invoice_date","vat","buyer_name","ship_to","address1","address2","city","state","pin_code","mobile_number","email_id","courier_name","tracking_id","reference_code","manifest_id","shipping_status","delivered_date","order_status","upload_by","fullfilement_type","portal");
			
			$temp_query="INSERT INTO online_sales (".implode(",", $common_column_mapping['onlineSales']).") SELECT ".implode(",", $temp_column_mapping[$portal_id])." FROM ".$temp_table_name;
			
			if(!mysql_query($temp_query))
			{
				throw new Exception (mysql_error());
			}

			$temp_drop="DROP TABLE IF EXISTS ".$temp_table_name;
			mysql_query($temp_drop);

			$portals=$this->portalList();
			$onlineSalesList=$this->onlineSalesList();
			$data = array('status' => "Success", "msg" => 'File imported successfully',"portals"=>$portals,"onlineSalesList"=>$onlineSalesList);
			$this->response($this->json($data),200);

			}

			catch (Exception $e) {
			$temp_drop="DROP TABLE IF EXISTS ".$temp_table_name;
			mysql_query($temp_drop);
			$portals=$this->portalList();
			$onlineSalesList=$this->onlineSalesList();
			$data = array('status' => "Failure", "msg" => $e->getMessage(),"portals"=>$portals,"onlineSalesList"=>$onlineSalesList);
			$this->response($this->json($data),400);
			}

		}

private function updateOrderStatus()
{
	try
	{
		if($this->get_request_method() != "POST")
		{
			$this->response('',406);
		}
		$errors=array();
		$error_orderIds=array();
		foreach ($this->_request['orders'] as $order) 
		{
				
				$orderstatus_id=$order['orderstatus_id'];
				$order_id=$order['order_id'];
				$portal_id=$order['portal_id'];
				$SalesQuantity=$order['quantity'];
				$salesSKU=$order['sku_id'];
				$prod_check='SELECT * from products where sku_id="'.$order['sku_id'].'" LIMIT 1';
				$prod_result=mysql_query($prod_check);
				$prod_count=mysql_num_rows($prod_result);
				if($prod_count==0)
				{
					$query_portal='';
					switch ($portal_id) {
						case '1':
							$query_portal='portal_1="'.$salesSKU.'"';
							break;
						case '2':
							$query_portal='portal_2="'.$salesSKU.'"';
							break;
						case '3':
							$query_portal='portal_3="'.$salesSKU.'"';
							break;
						case '4':
							$query_portal='portal_4="'.$salesSKU.'"';
							break;
						default:
							# code...
							break;
					}

					$skuMapping_sql='SELECT * from sku_mappings where '.$query_portal.' LIMIT 1';
					$skuMapping_result=mysql_query($skuMapping_sql);
					$mapping_count=mysql_num_rows($skuMapping_result);
					if($mapping_count==0)
					{
						$errors[]=$order;
						$error_orderIds[]=$order_id;
					}
					else
					{
						$skuMappingRow=mysql_fetch_assoc($skuMapping_result);
						$sku_id=$skuMappingRow['sku_id'];
						$withMapping_sql='SELECT * from products where sku_id="'.$sku_id.'" LIMIT 1';
						$withSkuMapping_result=mysql_query($withMapping_sql);
						$prodCountWithMapping=mysql_num_rows($withSkuMapping_result);
						if($prodCountWithMapping==0)
						{
							$errors[]=$order;
							$error_orderIds[]=$order_id;
						}
						else
						{
							$withSkuMapping_Row=mysql_fetch_assoc($withSkuMapping_result);
							$prod_quantity=$withSkuMapping_Row['qty'];
							$prod_id=$withSkuMapping_Row['id'];
							$finalQty=$prod_quantity-$SalesQuantity;
							$stock_Query="UPDATE products set qty=$finalQty where id=$prod_id";
							if(!mysql_query($stock_Query))
							{
								throw new Exception (mysql_error());
							}

							$sql="UPDATE online_sales set order_status=$orderstatus_id where order_id='$order_id' and portal=$portal_id";						
							if(!mysql_query($sql))
							{
								throw new Exception (mysql_error());
							}
						}

					}

				}

				else
				{
					$prodRow=mysql_fetch_assoc($prod_result);
					$prod_id=$prodRow['id'];
					$finalQty=$prodRow['qty']-$SalesQuantity;
					$stock_Query="UPDATE products set qty=$finalQty where id=$prod_id";
					if(!mysql_query($stock_Query))
					{
						throw new Exception (mysql_error());
					}
					$sql="UPDATE online_sales set order_status=$orderstatus_id where order_id='$order_id' and portal=$portal_id";						
					if(!mysql_query($sql))
					{
						throw new Exception (mysql_error());
					}
				}
				
		
		}

	$errors = array_filter($errors);
	$error_orderIds = array_filter($error_orderIds);	
	$onlineSalesList=$this->onlineSalesList();
	$userlist=$this->getUsers();		
	if(!empty($error_orderIds))
	{
		$msg="Order [".implode(',', $error_orderIds)."] having problem";		
		$data = array('status' => "Success","onlineSalesList"=>$onlineSalesList,"msg" => $msg,"orders"=>$errors);		
			$this->response($this->json($data),400);
	}
	else
	{
		$msg="Order Status updated successfully";
		$data = array('status' => "Success","onlineSalesList"=>$onlineSalesList,"msg" => $msg);		
		$this->response($this->json($data),200);
	}		

	}
	catch (Exception $e) 
	{
			$data = array('status' => "Failure", "msg" => $e->getMessage());
			$this->response($this->json($data),400);		
	}
}

private function assignOrders()
{
	try
	{
		if($this->get_request_method() != "POST")
		{
			$this->response('',406);
		}
		$assignTo=$this->_request['assignto'];

		foreach ($this->_request['orders'] as $orderId)
		{
													
				$sql="UPDATE online_sales set assign_to=$assignTo where id=$orderId";				
				if(!mysql_query($sql))
				{
					throw new Exception (mysql_error());
				}
		}

		$portals=$this->portalList();
		$onlineSalesList=$this->onlineSalesList();						
		$orderStatusList=$this->orderStatusList();
		$shippingStatusList=$this->shippingStatusList();
		$userlist=$this->getUsers();
		$data = array('status' => "Success", "msg" => 'Orders Assigned successfully', "portals"=>$portals,"onlineSalesList"=>$onlineSalesList,"orderStatusList"=>$orderStatusList,"shippingStatusList"=>$shippingStatusList,"userlist"=>$userlist);
		$this->response($this->json($data), 200);		

	}
	catch (Exception $e) 
	{
			$data = array('status' => "Failure", "msg" => $e->getMessage());
			$this->response($this->json($data),400);		
	}
}


private function assignOrder($portal_id,$sku_id,$user_id,$remark)
{
		mysql_query("UPDATE online_sales set assign_to=$user_id,remark='$remark' where portal=$portal_id and sku_id='$sku_id' and (order_status=1 or order_status=2)");

}

private function getAssignOrderDetails(){
	try
	{
		if($this->get_request_method() != "GET")
		{
			$this->response('',406);
		}
		$portals=$this->portalList();
		$portal_id=$this->_request['portal_id'];
		$sku_id=$this->_request['sku_id'];
		$user_id=$this->_request['user_id'];
		$assign=$this->_request['assign'];
		$remark=$this->_request['remark'];
		if($assign!="")
		{
			$this->assignOrder($portal_id,$sku_id,$user_id,$remark);
		}

		if($portal_id!="")
		{
			$ordersGroupBySku=$this->ordersGroupBySku($portal_id);
			$userlist=$this->getUsers();
		}
		else
		{
			$ordersGroupBySku="";
			$userlist="";
		}


	$data = array('status' => "Success", "portals"=>$portals,"ordersGroupBySku"=>$ordersGroupBySku,"userlist"=>$userlist);

		$this->response($this->json($data), 200);
	}

	catch (Exception $e) 
	{
			$data = array('status' => "Failure", "msg" => $e->getMessage());
			$this->response($this->json($data),400);		
	}

}

private function uploadSkuMapping(){
	try
	{
		if($this->get_request_method() != "POST")
		{
			$this->response('',406);
		}
		$skuMapping=array();		
		foreach( $this->_request['skuMapping'] as $row )
		{
			$skuMapping[]='("'.mysql_real_escape_string($row['sku_id']).'","'.mysql_real_escape_string($row['portal_1']).'","'.mysql_real_escape_string($row['portal_2']).'","'.mysql_real_escape_string($row['portal_3']).'","'.mysql_real_escape_string($row['portal_4']).'","'.mysql_real_escape_string($row['remark']).'")';
		}
		
		if(!mysql_query('INSERT INTO sku_mappings (sku_id, portal_1,portal_2,portal_3,portal_4,remark) VALUES '.implode(',', $skuMapping)))
		{
			throw new Exception (mysql_error());
		}
		$skuMappingList=$this->skuMappingList();
		$data = array('status' => "Success", "msg" => "SKU mapping uploaded successfully","skuMappingList"=>$skuMappingList);
		$this->response($this->json($data), 200);
	}

	catch (Exception $e) 
	{
			$data = array('status' => "Failure", "msg" => $e->getMessage());
			$this->response($this->json($data),400);		
	}

}

private function getAllSkuMappings(){

	try
	{
		if($this->get_request_method() != "GET")
		{
			$this->response('',406);
		}
		$skuMappingList=$this->skuMappingList();
		$data = array('status' => "Success", "skuMappingList"=>$skuMappingList);
		$this->response($this->json($data), 200);
	}

	catch (Exception $e) 
	{
			$data = array('status' => "Failure", "msg" => $e->getMessage());
			$this->response($this->json($data),400);		
	}

}


private function skuMappingList()
{
				$skumapping_list_sql = mysql_query("SELECT * from sku_mappings");
				$sku_mapping = array();
				if(mysql_num_rows($skumapping_list_sql) > 0){
					
					while($row = mysql_fetch_assoc($skumapping_list_sql)){
					  $sku_mapping[] = $row;
					  
					}
					
				}

				return $sku_mapping;
}

private function deleteSkuMapping(){

	if($this->get_request_method() != "DELETE"){
	$this->response('',406);
	}
	$id = (int)$this->_request['id'];
	if($id > 0)
	{
		mysql_query("DELETE FROM sku_mappings WHERE id = $id");	
		$skuMappingList=$this->skuMappingList();
		$data = array('status' => "Success", "msg"=>"Mapping deleted successfully","skuMappingList"=>$skuMappingList);
		$this->response($this->json($data), 200);
	}
}

private function stockValidation()
{
	try
	{
		if($this->get_request_method() != "POST")
		{
			$this->response('',406);
		}
		$stockDetails=array();
		foreach( $this->_request['stock'] as $stock ){
		$sku_id=$stock['sku_id'];	
		//$stockDetails[]=$stock;
		$prod_sql='SELECT * from products where sku_id="'.$sku_id.'" LIMIT 1';
		$prod_result=mysql_query($prod_sql);
		$prod_count=mysql_num_rows($prod_result);
		if($prod_count==0)
		{
			$stock['status']="S404";
			$stock['details']='Not found';			
		}
		else
		{
			$prod_row=mysql_fetch_assoc($prod_result);
			if($prod_row['qty']==$stock['qty'])
			{
				$stock['status']="S200";
				$stock['details']='Matched';
			}

			else
			{
				$MisMatchcount=abs($prod_row['qty']-$stock['qty']);
				$stock['status']="S202";				
				$stock['details']='Miss Match: '.$MisMatchcount.' System Qty:'.$prod_row['qty'].' Manual Qty : '.$stock['qty'];			
			}
			
		}
		$stockDetails[]=$stock;
		}
		
		$data = array('status' => "Success",'msg'=>'Stock details uploaded successfully', "stockDetails"=>$stockDetails);
		$this->response($this->json($data), 200);
	}
	catch (Exception $e) 
	{
			$data = array('status' => "Failure", "msg" => $e->getMessage());
			$this->response($this->json($data),400);		
	}

}

private function deleteOnlineSales()
{
	// Cross validation if the request method is DELETE else it will return "Not Acceptable" status
	if($this->get_request_method() != "DELETE"){
	$this->response('',406);
	}
	$id = (int)$this->_request['id'];

	if($id > 0){				
		mysql_query("DELETE FROM online_sales WHERE id = $id");
		$portals=$this->portalList();
		$onlineSalesList=$this->onlineSalesList();						
		$orderStatusList=$this->orderStatusList();
		$shippingStatusList=$this->shippingStatusList();
		$userlist=$this->getUsers();
		$data = array('status' => "Success", "msg" => 'Sales details deleted successfully', "portals"=>$portals,"onlineSalesList"=>$onlineSalesList,"orderStatusList"=>$orderStatusList,"shippingStatusList"=>$shippingStatusList,"userlist"=>$userlist);
		$this->response($this->json($data), 200);
	}else
	$this->response('',204);	// If no records "No Content" status
}

		private function json($data){
			if(is_array($data)){
				return json_encode($data);
			}
		}
	}
	
	// Initiiate Library
	
	$api = new API;
	$api->processApi();
?>

app.constant("DOMAIN","http://localhost/SMS");
app.constant("UPLOAD_STOCK_URL","/api/api.php?rquest=uploadStock");
app.constant("UPLOAD_SALES_URL","/api/api.php?rquest=uploadSales");
app.constant("UPLOAD_RETURN_URL","/api/api.php?rquest=uploadReturn");
app.constant("LOGIN_URL","/api/api.php?rquest=login");
app.constant("PROD_LIST_URL","/api/api.php?rquest=getAllProducts");
app.constant("PROD_SALES_LIST_URL","/api/api.php?rquest=getAllSalesList");
app.constant("PROD_RETURN_LIST_URL","/api/api.php?rquest=getAllReturnList");
app.constant("PROD_STOCK_UPDATE_URL","/api/api.php?rquest=updateStock");
app.constant("REGISTER_USER_URL","/api/api.php?rquest=registerUser");
app.constant("USERS_URL","/api/api.php?rquest=users");
app.constant("UPDATE_USER_URL","/api/api.php?rquest=updateUser");
app.constant("USER_DELETE_URL","/api/api.php?rquest=deleteUser");
app.constant("PROD_DELETE_URL","/api/api.php?rquest=deleteProd");
app.constant("STOCK_HISTORY_DELETE_URL","/api/api.php?rquest=deleteProdStockHistory");
app.constant("DELETE_SALES","/api/api.php?rquest=deleteSales");
app.constant("DELETE_RETURN","/api/api.php?rquest=deleteReturn");
app.constant("STOCK_DETAILS_URL","/api/api.php?rquest=stockDetails");
app.constant("PORTAL","/api/api.php?rquest=getAllPortals");
app.constant("ONLINE_SALES_URL","/api/api.php?rquest=saveOnlineSales");
app.constant("DELETE_ONLINE_SALES_URL","/api/api.php?rquest=deleteOnlineSales");
app.constant("ONLINE_ORDER_STATUS_URL","/api/api.php?rquest=updateOrderStatus");
app.constant("ASSIGN_ORDERS_URL","/api/api.php?rquest=assignOrders");
app.constant("ORDER_ASSIGN_DETAILS_URL","/api/api.php?rquest=getAssignOrderDetails");
app.constant("API_ERROR","Error while processing your request");
app.constant("uerTypes", [{"id": "1","name":"Super Admin"},{"id":"2","name":"Admin"}]);
app.constant('DEFAULT_LOGO', 'images/metaware_logo.png');
app.constant('DEFAULT_INVOICE', {
  tax: 5.00,
  invoice_number: 10,
  customer_info: {
    name: 'Mr. John Doe',
    web_link: 'John Doe Designs Inc.',
    address1: '1 Infinite Loop',
    address2: 'Cupertino, California, US',
    postal: '90210'
  },
  company_info: {
    name: 'Metaware Labs',
    web_link: 'www.metawarelabs.com',
    address1: '123 Yonge Street',
    address2: 'Toronto, ON, Canada',
    postal: 'M5S 1B6'
  },
  items:[
    
  ]
})
app.controller("loginCtrl",function($scope,$location,$http,UPLOAD_STOCK_URL,UPLOAD_SALES_URL,UPLOAD_RETURN_URL,LOGIN_URL,DOMAIN,$timeout){
$scope.loginFailed=false;
$scope.loginClicked=false;
$scope.loading=false;
$scope.validateLogin=function(){
$scope.loginClicked=true;

	if($scope.loginform.$valid)
	{
		var data={"email":$scope.email,"pwd":$scope.password};
    $scope.loading=true;
		$http.post(DOMAIN+LOGIN_URL, data).then(function(data) {        
          window.localStorage.setItem("currentUser", JSON.stringify(data.data));
          window.location.href="index.html";
          $scope.loading=false;
        },function(data){
          $scope.loginFailed=true;
          $scope.msg=data.data.msg;          
          $scope.loading=false;
        });
	}

}

});

app.controller('uploadStock',function($scope,$http,UPLOAD_STOCK_URL,UPLOAD_SALES_URL,UPLOAD_RETURN_URL,LOGIN_URL,PROD_LIST_URL,$timeout,dateService,DOMAIN,API_ERROR,STOCK_HISTORY_DELETE_URL,PROD_DELETE_URL,$filter,STOCK_DETAILS_URL){
$scope.$parent.title="Stock Details";
$scope.notification=false;
$scope.MyFiles=[];
$scope.$parent.loaded=false;
$scope.formSubmitted=false;
$scope.importTab=false; 
$scope.manualTab=true; 
$scope.formSubmitted=false;
$scope.prod={'purchase_date':''};
$scope.recordType='new';
$scope.handler=function(e,files)
{
 
 try{

      var reader=new FileReader();
      reader.onload=function(e)
    {
      try
      {
          var string=reader.result;
          $scope.prodList = {"products":[],"currentUser":[]};
          var retrieveCurrentUser = localStorage.getItem('currentUser');
          $scope.prodList.currentUser.push(JSON.parse(retrieveCurrentUser));
          var csvData=string.split('\n');

          for(var i=1;i<csvData.length;i++)
          {

            var prodDetails=csvData[i].split(',');
            if(prodDetails.length>1)
            {
              var prod_name=prodDetails[0];
              var sku_id=prodDetails[1];
              var qty=prodDetails[2]; 
              var category_id=prodDetails[3]; 
              var brand_id=prodDetails[4];                     
              var purchase_date=prodDetails[5]; 
              var isValidQty=qty%1!=0;
              if(prod_name==""||sku_id==""||qty==""||category_id==""||brand_id==""||purchase_date=="")
              {
                throw "Document having invalid data"; 
              } 

              if(qty<=0 || isValidQty)
              {
                throw "Document having invalid quantity";          
              } 

              if(purchase_date!="" && typeof purchase_date!='undefined')
              {                  
                purchase_date=dateService.dateToDB(purchase_date);
                if(!purchase_date)
                {             
                  throw "Document having invalid date format try with valid format (Ex:DD/MM/YYY)";          
                }
              }
              $scope.prodList.products.push({"prod_name":prod_name,"sku_id":sku_id,"qty":qty,"category_id":category_id,"brand_id":brand_id,"purchase_date":purchase_date});
          
            }

          }

          saveStockUpdateUI();
        }
          catch(error)
          {                
                $scope.$apply(function () {
                $scope.statusCode="400";
                $scope.msg=error;
                $scope.notification=true;
              });
                $("#MyFile").val('');
          }


    }
    var file_ext=files[0].name.split('.').pop();
      if(file_ext.toLowerCase()!="csv"){
        throw 'Only csv format allowed';
      }    
        reader.readAsText(files[0]);

  }

      catch(error){
        $scope.statusCode="400";
        $scope.msg=error;
        $scope.notification=true;
        $("#MyFile").val('');
      }
}
 

  $scope.saveManualEntry=function(){   
    $scope.prodList = {"products":[],"currentUser":[]};
    var retrieveCurrentUser = localStorage.getItem('currentUser');
    $scope.prodList.currentUser.push(JSON.parse(retrieveCurrentUser));
    if($scope.prod['sku_id']!==null && typeof $scope.prod['sku_id']==='object')
    {
      $scope.prod['sku_id']=$scope.prod.sku_id.sku_id;
    }
    var copyOfData=angular.copy($scope.prod);
    copyOfData['purchase_date']=dateService.dateToDB($scope.prod['purchase_date']);
    $scope.prodList.products.push(copyOfData);
    if($scope.editMode)
    {
      $scope.prodList['mode']='Edit';
      $scope.editMode=false;
    }
    else
    {
      if($scope.recordType=='update' ||$scope.recordType=='new')
      {
        $scope.prodList['recordType']=$scope.recordType;
      }
    }
    
      saveStockUpdateUI();
    
    
}

  $scope.reset=function()
  {
    
    $scope.manulForm.$setPristine();
    $scope.manulForm.$setUntouched(); 
    $scope.prod={};   
    $scope.formSubmitted=false;   
  }

  $scope.delete_prod_history=function(prodID){
    
    if(confirm("Are sure want to delete"))
    {
        
        $http.delete(DOMAIN+STOCK_HISTORY_DELETE_URL,{params: {id: prodID}}).then(function(data) {
          $scope.msg=data.data.msg; 
          $scope.statusCode=data.status;
          $scope.prod_history=data.data.prod_history; 
          $scope.notification=true;          
          var table = $('#dataTables-stock-history').DataTable();
          table.destroy();
          $timeout(function() { $('#dataTables-stock-history').DataTable({ "aaSorting": []});}, 0);
        });
    }

  }

  $scope.delete_prod=function(product){

    if(confirm("Are sure want to delete"))
    {
        
          $http.delete(DOMAIN+PROD_DELETE_URL,{params: {id: product.id,sku_id:product.sku_id}}).then(function(data) {
          $scope.msg=data.data.msg; 
          $scope.statusCode=data.status;          
          $scope.productList=data.data.prod_list;
          $scope.prod_history=data.data.prod_history; 
          $scope.notification=true;          
          var table = $('#dataTables-stock').DataTable();
          var table_history = $('#dataTables-stock-history').DataTable();
          table.destroy();
          table_history.destroy();
          $timeout(function() { $('#dataTables-stock').DataTable({ "aaSorting": []});$('#dataTables-stock-history').DataTable({ "aaSorting": []});}, 0);
        });
    }

  }


   $http.get(DOMAIN+STOCK_DETAILS_URL).then(function(data) {           
           $scope.productList=data.data.prod_list;
           $scope.prod_history=data.data.prod_history;
           $scope.brands=data.data.brands;
           $scope.categories=data.data.categories;
           $timeout(function() { $('#dataTables-stock').DataTable({ "aaSorting": []}); $('#dataTables-stock-history').DataTable({ "aaSorting": []});
        $scope.$parent.loaded=true;}, 0);
        },function(data){
        $scope.$parent.loaded=true;
        $scope.notification=true;
        $scope.msg=API_ERROR; 
        $scope.statusCode=400;

        });

$scope.editprod=function(prod){
  $('html, body').animate({scrollTop: $("#myTab").offset().top}, 1000);

$scope.importTab=false; 
$scope.manualTab=true;   
  $scope.editMode=true;
  var masterCopy=angular.copy(prod);
  $scope.prod=masterCopy;
  $scope.prod.purchase_date=$filter('datefilter')($scope.prod.purchase_date); 
  var returnedData = $.grep($scope.categories, function (element, index) {
        return element.id == $scope.prod.category;
    });
  $scope.prod.category=returnedData[0];

  var brandReturnedData = $.grep($scope.brands, function (element, index) {
        return element.id == $scope.prod.brand;
    });
  $scope.prod.brand=brandReturnedData[0]; 

  $timeout(function() {$('.datepicker').datepicker('update');}, 0);
  

  }

function saveStockUpdateUI(){

          $http.post(DOMAIN+UPLOAD_STOCK_URL, $scope.prodList).then(function(data) {
          $scope.statusCode=data.status;
          $scope.msg=data.data.msg;
          $scope.notification=true;           
          $("#MyFile").val('');
          $scope.productList=data.data.prod_list;
          $scope.prod_history=data.data.prod_history;
          $scope.categories=data.data.categories;
          $scope.brands=data.data.brands;
          var table = $('#dataTables-stock').DataTable();
          var history_table = $('#dataTables-stock-history').DataTable();
          table.destroy();
          history_table.destroy();
          $timeout(function() { $('#dataTables-stock').DataTable({ "aaSorting": []});$('#dataTables-stock-history').DataTable({ "aaSorting": []}); 
          }, 0);
          $scope.reset();
          },function(data,status){          
          $scope.statusCode=data.status;
          $scope.msg=data.data.msg;
          $scope.notification=true;           
          $("#MyFile").val('');
          var table = $('#dataTables-stock').DataTable();
          var history_table = $('#dataTables-stock-history').DataTable();
          table.destroy();
          history_table.destroy();
          $timeout(function() { $('#dataTables-stock').DataTable({ "aaSorting": []});$('#dataTables-stock-history').DataTable({ "aaSorting": []}); 
          }, 0);
          });
}


});


app.controller('uploadSales',function($scope,$http,UPLOAD_SALES_URL,PROD_SALES_LIST_URL,$timeout,dateService,DOMAIN,API_ERROR,$filter,DELETE_SALES){
  $scope.notification=false;
  $scope.MyFiles=[];
  $scope.$parent.title="Sales Details";
  $scope.$parent.loaded=false;
  $scope.importTab=true;
  $scope.manualTab=false;
  $scope.recordType="new";
  $scope.currentUser=JSON.parse(localStorage.getItem('currentUser'));
  $scope.handler=function(e,files)
  {
    try
    {
      var reader=new FileReader();
      reader.onload=function(e)
      {
        try
        {
          var string=reader.result;
          $scope.prodList = {"products":[],"currentUser":[]};
          var retrieveCurrentUser = localStorage.getItem('currentUser');
          $scope.prodList.currentUser.push(JSON.parse(retrieveCurrentUser));
          var csvData=string.split('\n');
                for(var i=1;i<csvData.length;i++)
          {
                  var prodDetails=csvData[i].split(',');
                  if(prodDetails.length>1)
              {
                  var sku_id=prodDetails[0];
                  var qty=prodDetails[1];
                  var portal=prodDetails[2];                  
                  var sales_date=prodDetails[3];
                  var remark=prodDetails[4];
                  var isValidQty=qty%1!=0;
                  if(sku_id=="" || qty=="" || portal=="" ||sales_date=="")
                  {
                    throw "Document having invalid data";    
                  }

                  if(qty<=0 || isValidQty)
                  {
                    throw "Document having invalid quantity";          
                  } 
                  if(sales_date!="" && typeof sales_date!='undefined')
                  {                  
                    sales_date=dateService.dateToDB(sales_date);
                    if(!sales_date)
                    {          
                    throw "Document having invalid date format try with valid format (Ex:DD/MM/YYY)";          
                    }
                  }  

                  if(sku_id!="" && qty!="" && portal!="" && sales_date!="")
                  {
                    $scope.prodList.products.push({"sku_id":sku_id,"qty":qty,"portal":portal,"sales_date":sales_date});
                  }
            }
          }

         saveSalesUpdateUI();

        }  
        catch(error)
        {                
          $scope.$apply(function () {
          $scope.statusCode="400";
          $scope.msg=error;
          $scope.notification=true;
          });
          $("#MyFile").val('');
        }
      }

    var file_ext=files[0].name.split('.').pop();
      if(file_ext.toLowerCase()!="csv"){
        throw 'Only csv format allowed';
      } 

      reader.readAsText(files[0]);
 }

      catch(error){
        $scope.statusCode="400";
        $scope.msg=error;
        $scope.notification=true;
        $("#MyFile").val('');
      }

  }


     $http.get(DOMAIN+PROD_SALES_LIST_URL).then(function(data) {           
             $scope.prod_sales_list=data.data.prod_sales_list;
              $scope.prod_list=data.data.prod_list;
              $timeout(function() { $('#dataTables-stock').DataTable({ "aaSorting": []});$scope.$parent.loaded=true; 
          }, 0);
          },function(data){
            $scope.$parent.loaded=true;
            $scope.notification=true;
            $scope.msg=API_ERROR; 
            $scope.statusCode=400;
          });

  $scope.reset=function()
  {
    
    $scope.manulForm.$setPristine();
    $scope.manulForm.$setUntouched(); 
    $scope.prod={};   
    $scope.formSubmitted=false;     
  }

  $scope.editprod=function(prod)
  {
    $('html, body').animate({scrollTop: $("#myTab").offset().top}, 1000);
    $scope.importTab=false; 
    $scope.manualTab=true;  
    $scope.editMode=true;    
    var masterCopy=angular.copy(prod);
    $scope.prod=masterCopy;
    $scope.prod.sales_date=$filter('datefilter')($scope.prod.sales_date);   
    $timeout(function() {$('.datepicker').datepicker('update');}, 0);
  }

  $scope.saveManualEntry=function(){

    $scope.prodList = {"products":[],"currentUser":[]};
    var retrieveCurrentUser = localStorage.getItem('currentUser');
    $scope.prodList.currentUser.push(JSON.parse(retrieveCurrentUser));
    if($scope.prod['sku_id']!==null && typeof $scope.prod['sku_id']==='object')
    {
      $scope.prod['sku_id']=$scope.prod.sku_id.sku_id;
    }
    var copyOfData=angular.copy($scope.prod);
    copyOfData['sales_date']=dateService.dateToDB($scope.prod['sales_date']);
    $scope.prodList.products.push(copyOfData);
    if($scope.editMode)
    {
      $scope.prodList['mode']='Edit';
      $scope.editMode=false;
    } 
    saveSalesUpdateUI();
  }

 $scope.delete_prod=function(product){

    if(confirm("Are sure want to delete"))
    {

          $http.delete(DOMAIN+DELETE_SALES,{params: {id: product.id}}).then(function(data) {
          $scope.msg=data.data.msg; 
          $scope.statusCode=data.status;          
          $scope.prod_sales_list=data.data.prod_sales_list;
          $scope.prod_list=data.data.prod_list;
          $scope.notification=true;          
          var table = $('#dataTables-stock').DataTable();         
          table.destroy();          
          $timeout(function() { $('#dataTables-stock').DataTable({ "aaSorting": []});}, 0);
        });
    }

  }
  function saveSalesUpdateUI()
  {
          $http.post(DOMAIN+UPLOAD_SALES_URL, $scope.prodList).then(function(data) {
           $scope.statusCode=data.status;
           $scope.msg=data.data.msg;
           $scope.notification=true;           
           $("#MyFile").val('');
           $scope.prod_sales_list=data.data.prod_sales_list;
           var table = $('#dataTables-stock').DataTable();
           table.destroy();
            $timeout(function() { $('#dataTables-stock').DataTable({ "aaSorting": []}); 
          }, 0);
          $scope.reset();
          },function(data,status){          
           $scope.statusCode=data.status;
           $scope.msg=data.data.msg;
           $scope.notification=true;           
           $("#MyFile").val('');
           var table = $('#dataTables-stock').DataTable();
           table.destroy();
            $timeout(function() { $('#dataTables-stock').DataTable({ "aaSorting": []}); 
          }, 0);
          });
  }

});

app.controller('uploadReturn',function($scope,$http,UPLOAD_RETURN_URL,PROD_RETURN_LIST_URL,$timeout,dateService,DOMAIN,API_ERROR,$filter,DELETE_RETURN){
$scope.notification=false;
$scope.MyFiles=[];
$scope.$parent.title="Return Details";
$scope.$parent.loaded=false;
$scope.importTab=true;
$scope.manualTab=false;
$scope.currentUser=JSON.parse(localStorage.getItem('currentUser'));
$scope.prod={};
$scope.prod['isSold']=0;   
$scope.prod['isDamaged']=1; 
$scope.handler=function(e,files)
  {
    try
    {

      var reader=new FileReader();
      reader.onload=function(e)
      {
        try
        {
              var string=reader.result;
              $scope.prodList = {"products":[],"currentUser":[]};
              var retrieveCurrentUser = localStorage.getItem('currentUser');
              $scope.prodList.currentUser.push(JSON.parse(retrieveCurrentUser));
              var csvData=string.split('\n');
                for(var i=1;i<csvData.length;i++)
              {
                  var prodDetails=csvData[i].split(',');
                  if(prodDetails.length>1)
                {                  
                  var sku_id=prodDetails[0];
                  var qty=prodDetails[1];
                  var portal=prodDetails[2];                  
                  var return_date=prodDetails[3];
                  var is_sold=prodDetails[4];
                  var is_damaged=prodDetails[5];
                  var remark=prodDetails[6];
                  var isValidQty=qty%1!=0;
                  
                  if(sku_id=="" || qty=="" || portal=="" ||return_date=="")
                  {
                    throw "Document having invalid data";    
                  }

                  if(qty<=0 || isValidQty)
                  {

                    throw "Document having invalid quantity";          
                  }

                  if(return_date!="" && typeof return_date!='undefined')
                  {                  
                    return_date=dateService.dateToDB(return_date);

                    if(!return_date)
                    {                    
                    throw "Document having invalid date format try with valid format (Ex:DD/MM/YYY)";          
                    }
                  }

                  if(sku_id!="" && qty!="" && portal!="" && return_date!="")
                  {
                    $scope.prodList.products.push({"sku_id":sku_id,"qty":qty,"portal":portal,"return_date":return_date,"isSold":is_sold,"isDamaged":is_damaged,"remark":remark});
                  }
                }
              }


             saveReturnUpdateUI();
       }

        catch(error)
        {    
          //alert(error);        
          $scope.$apply(function () {
          $scope.statusCode="400";
          $scope.msg=error;
          $scope.notification=true;
          });
          $("#MyFile").val('');
        }

    }

    var file_ext=files[0].name.split('.').pop();
      if(file_ext.toLowerCase()!="csv"){
        throw 'Only csv format allowed';
      }

      reader.readAsText(files[0]);
    }

  catch(error){
    $scope.statusCode="400";
    $scope.msg=error;
    $scope.notification=true;
    $("#MyFile").val('');
  }
}


   $http.get(DOMAIN+PROD_RETURN_LIST_URL).then(function(data) {           
           $scope.prod_list=data.data.prod_list;
           $scope.prod_return_list=data.data.prod_return_list;
            $timeout(function() { $('#dataTables-stock').DataTable({ "aaSorting": []});$scope.$parent.loaded=true; 
        }, 0);
        },function(data){
        $scope.$parent.loaded=true;
        $scope.notification=true;
        $scope.msg=API_ERROR; 
        $scope.statusCode=400;

        });

  $scope.reset=function()
  {
    
    $scope.manulForm.$setPristine();
    $scope.manulForm.$setUntouched(); 
    $scope.prod={};   
    $scope.formSubmitted=false;
    $scope.prod['isSold']=0;   
    $scope.prod['isDamaged']=1;      
  }

  $scope.editprod=function(prod)
  {
    $('html, body').animate({scrollTop: $("#myTab").offset().top}, 1000);
    $scope.importTab=false; 
    $scope.manualTab=true;  
    $scope.editMode=true;    
    var masterCopy=angular.copy(prod);
    $scope.prod=masterCopy;
    $scope.prod.return_date=$filter('datefilter')($scope.prod.return_date); 
    console.log($scope.prod);  
    $timeout(function() {$('.datepicker').datepicker('update');}, 0);
  }

  $scope.saveManualEntry=function(){

    $scope.prodList = {"products":[],"currentUser":[]};
    var retrieveCurrentUser = localStorage.getItem('currentUser');
    $scope.prodList.currentUser.push(JSON.parse(retrieveCurrentUser));
    if($scope.prod['sku_id']!==null && typeof $scope.prod['sku_id']==='object')
    {
      $scope.prod['sku_id']=$scope.prod.sku_id.sku_id;
    }
    var copyOfData=angular.copy($scope.prod);
    copyOfData['return_date']=dateService.dateToDB($scope.prod['return_date']);
    $scope.prodList.products.push(copyOfData);
    if($scope.editMode)
    {
      $scope.prodList['mode']='Edit';
      $scope.editMode=false;
    } 
    saveReturnUpdateUI();
  }

 $scope.delete_return=function(product){

    if(confirm("Are sure want to delete"))
    {
          $http.delete(DOMAIN+DELETE_RETURN,{params: {id: product.id}}).then(function(data) {
          $scope.msg=data.data.msg; 
          $scope.statusCode=data.status;          
          $scope.prod_return_list=data.data.prod_return_list;
          $scope.prod_list=data.data.prod_list;
          $scope.notification=true;          
          var table = $('#dataTables-stock').DataTable();         
          table.destroy();          
          $timeout(function() { $('#dataTables-stock').DataTable({ "aaSorting": []});}, 0);
        });
    }

  }

function saveReturnUpdateUI()
{

           $http.post(DOMAIN+UPLOAD_RETURN_URL, $scope.prodList).then(function(data) {
                 $scope.statusCode=data.status;
                 $scope.msg=data.data.msg;
                 $scope.notification=true;           
                 $("#MyFile").val('');
                 $scope.prod_list=data.data.prod_list;
                 $scope.prod_return_list=data.data.prod_return_list;
                 var table = $('#dataTables-stock').DataTable();
                 table.destroy();
                  $timeout(function() { $('#dataTables-stock').DataTable({ "aaSorting": []}); 
              }, 0);
                  $scope.reset();
              },function(data,status){          
                 $scope.statusCode=data.status;
                 $scope.msg=data.data.msg;
                 $scope.notification=true;           
                 $("#MyFile").val('');
                 var table = $('#dataTables-stock').DataTable();
                 table.destroy();
                  $timeout(function() { $('#dataTables-stock').DataTable({ "aaSorting": []}); 
              }, 0);
              });
}

});


app.controller('userManager',function($scope,$http,$timeout,dateService,uerTypes,REGISTER_USER_URL,USERS_URL,UPDATE_USER_URL,USER_DELETE_URL,DOMAIN,API_ERROR){
  $scope.notification=false;
  $scope.MyFiles=[];
  $scope.userTypes=uerTypes;
  $scope.isEditForm=false;
  $scope.formSubmitted=false;
  $scope.pwdRequired=true; 
  $scope.$parent.title="User Manager"; 
  $scope.currentUser=JSON.parse(localStorage.getItem('currentUser')); 
   
  $scope.saveUser=function()
  {
    if(!$scope.isEditForm) // New user
    {
      var data={"users":[$scope.user]};
      $http.post(DOMAIN+REGISTER_USER_URL,data).then(function(data) {
        $scope.notification=true;
        $scope.statusCode=data.status;
        $scope.msg=data.data.msg;
        $scope.users=data.data.users; 
        $scope.reset();
        $scope.notification=true;
      },function(data){
        $scope.notification=true;
        $scope.statusCode=data.status;
        $scope.msg=data.data.msg;
      });
    }
    else // update the user
    {
        var data={"users":[$scope.user]};
        $http.post(DOMAIN+UPDATE_USER_URL,data).then(function(data) {
        $scope.notification=true;
        $scope.statusCode=data.status;
        $scope.msg=data.data.msg;        
        $scope.users=data.data.users; 
        $scope.isEditForm=false;  
        $scope.pwdRequired=true;     
        $scope.reset();
        $scope.notification=true;
      },function(data){
        $scope.notification=true;
        $scope.statusCode=data.status;
        $scope.msg=data.data.msg;
      });

      
    }

  }

  $scope.reset=function()
  {
    $scope.userform.$setPristine();
    $scope.userform.$setUntouched();
    $scope.user={};
    $scope.formSubmitted=false;
    $scope.notification=false;    
  }

  $scope.edituser=function(edituser){
    $('html, body').animate({scrollTop: $("#adduser").offset().top}, 1000);
    $scope.notification=false;
    $scope.isEditForm=true; 
    $scope.pwdRequired=false;  
    $scope.userMaster=angular.copy(edituser);
    var returnedData = $.grep(uerTypes, function (element, index) {
        return element.id == edituser.user_type;
    });    
    $scope.user={"id":edituser.user_id,"name":edituser.user_fullname,"email":edituser.user_email,"usertype":returnedData[0]};
    
  }

  $scope.cancel=function()
  {
    $scope.isEditForm=false;
    $scope.formSubmitted=false;
    $scope.pwdRequired=true; 
    $scope.userform.$setPristine();
    $scope.userform.$setUntouched();
    $scope.reset();
  }

  $scope.deleteuser=function(id){
   
    if(confirm("Are sure want to delete"))
    {
        
        $http.delete(DOMAIN+USER_DELETE_URL,{params: {id: id}}).then(function(data) {
          $scope.msg=data.data.msg; 
          $scope.statusCode=data.status;
          $scope.users=data.data.users; 
          $scope.notification=true;
        });
    }
  }

$http.get(DOMAIN+USERS_URL).then(function(data) {
        $scope.users=data.data.users;
        $timeout(function() { $('#dataTables-users').DataTable({ "aaSorting": []});$scope.$parent.loaded=true; 
        }, 0);
      },function(data){
        $scope.$parent.loaded=true;
        $scope.notification=true;
        $scope.msg=API_ERROR; 
        $scope.statusCode=400;

      });

});

app.controller('masterController',function($scope,$location){  
  $scope.getClass = function (path) {
  return ($location.path().substr(0, path.length) === path) ? 'active' : '';  }

  $scope.loader = function (path) {
  
    if($location.path().substr(0, path.length) === path)
    {
      return true;
    }
    else
    {
      return false;
    }

  }

  $scope.logout=function()
  {
    window.localStorage.removeItem("currentUser");
    window.location.href="login.html";
  }
  $scope.title="SMS";
});

app.controller('dashboard',function($scope,$location){  
  $scope.$parent.title="Dashboard";
  $scope.$parent.loaded=true;
});


app.directive('fileChange',['$parse', function($parse){
  return{
    require:'ngModel',
    restrict:'A',
    link:function($scope,element,attrs,ngModel){
      var attrHandler=$parse(attrs['fileChange']);
      var handler=function(e){
        $scope.$apply(function(){
          attrHandler($scope,{$event:e,files:e.target.files});
        });
      };
      element[0].addEventListener('change',handler,false);
    }
  }
}]);

app.factory("dateService",function(){

  return{
      dateToDB:function(inputDate){ 
          try{
            var pattern = /(\d{2})\/(\d{2})\/(\d{4})/;
            if(!pattern.test(inputDate))
            {
              throw "Date format error";
            }            
            var dt = new Date(inputDate.replace(pattern,'$3-$2-$1'));
            var full_date=dt.getFullYear()+'-'+(dt.getMonth() + 1) + '-' + dt.getDate();
            return full_date;
          }
        catch(err)
        {
          return false;
        }
      }

  }
});

 app.factory('Auth', function(){
  
return{    
    isLoggedIn : function(){        
        var currentUser=window.localStorage.getItem("currentUser");
        console.log(currentUser);
        if(currentUser===null||typeof currentUser=='undefined')
        {          
          return false;
        }
        {          
          return true;
        }
    }
  }
});

app.filter('datefilter', function() {
  return function(input, uppercase) {
    input = input || '';
    var out="";    
    var times=input.split(' ');
    if(times.length>1)
    {
      var date=times[0].split('-');
      var HMS=times[1].split(':');
      
      out=date[2]+'/'+date[1]+'/'+date[0]+' '+HMS[0]+':'+HMS[1]+':'+HMS[2];
    }
    else
    {
      var date=input.split('-');
      out=date[2]+'/'+date[1]+'/'+date[0];
    }    
    
    return out;
  };
});

app.controller('DatepickerPopupDemoCtrl', function ($scope) {
  $scope.today = function() {
    $scope.dt = new Date();
  };
  $scope.today();

  $scope.clear = function() {
    $scope.dt = null;
  };

  $scope.inlineOptions = {
    customClass: getDayClass,
    minDate: new Date(),
    showWeeks: true
  };

  $scope.dateOptions = {
    dateDisabled: disabled,
    formatYear: 'yy',
    maxDate: new Date(2020, 5, 22),
    minDate: new Date(),
    startingDay: 1
  };

  // Disable weekend selection
  function disabled(data) {
    var date = data.date,
      mode = data.mode;
    return mode === 'day' && (date.getDay() === 0 || date.getDay() === 6);
  }

  $scope.toggleMin = function() {
    $scope.inlineOptions.minDate = $scope.inlineOptions.minDate ? null : new Date();
    $scope.dateOptions.minDate = $scope.inlineOptions.minDate;
  };

  $scope.toggleMin();

  $scope.open1 = function() {
    $scope.popup1.opened = true;
  };

  $scope.open2 = function() {
    $scope.popup2.opened = true;
  };

  $scope.setDate = function(year, month, day) {
    $scope.dt = new Date(year, month, day);
  };

  $scope.formats = ['dd-MMMM-yyyy', 'yyyy/MM/dd', 'dd.MM.yyyy', 'shortDate'];
  $scope.format = $scope.formats[0];
  $scope.altInputFormats = ['M!/d!/yyyy'];

  $scope.popup1 = {
    opened: false
  };

  $scope.popup2 = {
    opened: false
  };

  var tomorrow = new Date();
  tomorrow.setDate(tomorrow.getDate() + 1);
  var afterTomorrow = new Date();
  afterTomorrow.setDate(tomorrow.getDate() + 1);
  $scope.events = [
    {
      date: tomorrow,
      status: 'full'
    },
    {
      date: afterTomorrow,
      status: 'partially'
    }
  ];

  function getDayClass(data) {
    var date = data.date,
      mode = data.mode;
    if (mode === 'day') {
      var dayToCheck = new Date(date).setHours(0,0,0,0);

      for (var i = 0; i < $scope.events.length; i++) {
        var currentDay = new Date($scope.events[i].date).setHours(0,0,0,0);

        if (dayToCheck === currentDay) {
          return $scope.events[i].status;
        }
      }
    }

    return '';
  }
});

app.controller('onlineSales',function($scope,$http,onlineSalesList,DOMAIN,ONLINE_SALES_URL,$timeout){
$scope.$parent.loaded=true;
$scope.$parent.title="Online Sales Details";
$scope.portals=onlineSalesList.portals;
$scope.onlineSalesList=onlineSalesList.onlineSalesList;
$scope.handler=function(e,files)
{
 
 try{

      var reader=new FileReader();
      reader.onload=function(e)
    {
      try
      {
          var string=reader.result;
          $scope.datas = {"currentUser":[],"columns":[],"rows":[],"portal":$scope.portal};
          var retrieveCurrentUser = localStorage.getItem('currentUser');
          $scope.datas.currentUser.push(JSON.parse(retrieveCurrentUser));
          var csvData=string.split('\n');         
          for(var i=0;i<csvData.length;i++)
          {

            var prodDetails=csvData[i].split(',');
            if(prodDetails.length>1)
            {
              if(i==0)
              {

                for(var j=0;j<prodDetails.length;j++)
                {
                  var column=prodDetails[j].trim().toLowerCase().replace(/[^A-Z0-9]/ig, "_");                  
                  $scope.datas.columns.push(column);
                }  
                
              }
              else
              {
                var rowData=[];
                for(var k=0;k<prodDetails.length;k++)
                {                
                  rowData.push(prodDetails[k]);
                }
                $scope.datas.rows.push(rowData);
              }                      
              
            }
        
          }
          
          saveOnlineSales();
        }
          catch(error)
          {    console.log(error);            
                $scope.$apply(function () {
                $scope.statusCode="400";
                $scope.msg=error;
                $scope.notification=true;
              });
                $("#MyFile").val('');
          }


    }
    var file_ext=files[0].name.split('.').pop();
      if(file_ext.toLowerCase()!="csv"){
        throw 'Only csv format allowed';
      }    
        reader.readAsText(files[0]);

  }

      catch(error)
      {
        console.log(error);
        $scope.statusCode="400";
        $scope.msg=error;
        $scope.notification=true;
        $("#MyFile").val('');
      }
}

  function saveOnlineSales()
  {
    console.log($scope.datas);

     $http.post(DOMAIN+ONLINE_SALES_URL,$scope.datas).then(function(data) {
      $scope.statusCode=data.status;
      $scope.msg=data.data.msg;
      $scope.notification=true;
      $scope.portals=data.data.portals;
      $scope.onlineSalesList=data.data.onlineSalesList;           
      $("#MyFile").val('');
     },function(data){
      
    $scope.statusCode=data.status;
    $scope.msg=data.data.msg;
    $scope.notification=true;
    $scope.portals=data.data.portals;
    $scope.onlineSalesList=data.data.onlineSalesList;            
    $("#MyFile").val('');
                 
     });
  }

  $scope.search=function(){

    if(typeof $scope.onlineSalesMasterCopy=="undefined")
    {
      $scope.onlineSalesMasterCopy=angular.copy($scope.onlineSalesList);
    }
    var filterFromDate=$scope.filterFromDate;
    var filterToDate=$scope.filterToDate;
    var filterPortal=$scope.filterPortal;   
    if(filterFromDate!="" && typeof filterFromDate!="undefined")
    {
      filterFromDate=$scope.parseDate($scope.filterFromDate);
    }
    if(filterToDate!="" && typeof filterToDate!="undefined")
    {
      filterToDate=$scope.parseDate($scope.filterToDate);
    } 
    if(filterFromDate==null ||filterFromDate=="") 
    {
      filterFromDate=undefined;
    }
    if(filterToDate==null ||filterToDate=="")
    { 
      filterToDate=undefined;
    } 
    var filterCase=0;

    if(typeof filterPortal!="undefined" && typeof filterFromDate!="undefined" && typeof filterToDate!="undefined")
    {
      console.log("All Selected");
      filterCase=1;
    }
    else if(typeof filterPortal!="undefined" && typeof filterFromDate=="undefined" && typeof filterToDate=="undefined")
    {
      console.log("port only selected");
      filterCase=2;
    }
    else if(typeof filterPortal=="undefined" && typeof filterFromDate!="undefined" && typeof filterToDate!="undefined")
    {
      console.log("Date range selected");
      filterCase=3
    }
    else if(typeof filterPortal=="undefined" && typeof filterFromDate!="undefined" && typeof filterToDate=="undefined")
    {
      console.log("only from date seleced");
      filterCase=4
    }

    else if(typeof filterPortal!="undefined" && typeof filterFromDate!="undefined" && typeof filterToDate=="undefined")
    {
      console.log("only from date with portal seleced");
      filterCase=5;
    }

    else
    {
      console.log("Nothing seleced");
      filterCase=0;
    }

    
    $scope.onlineSalesList=$scope.onlineSalesMasterCopy;
    var result = [];
    angular.forEach($scope.onlineSalesList, function(item){
      
      var invoice_date=new Date(item.invoice_date);
      
      if(filterCase==1)
      {
            if(invoice_date>=filterFromDate && invoice_date<=filterToDate && item.portal.toLowerCase().indexOf($scope.filterPortal.id) !== -1)
          {
              
            result.push(item);
          }
      }

      if(filterCase==2)
      {

            if(item.portal.toLowerCase().indexOf($scope.filterPortal.id) !== -1)
          {              
            result.push(item);
          } 
      }

      if(filterCase==3)
      {
            if(invoice_date>=filterFromDate && invoice_date<=filterToDate)
          {
              
            result.push(item);
          }
      }

      if(filterCase==4)
      {
           if(invoice_date>=filterFromDate)
          {
              
            result.push(item);
          }
      }

      if(filterCase==5)
      {
          if(invoice_date>=filterFromDate && item.portal.toLowerCase().indexOf($scope.filterPortal.id) !== -1)
          {              
            result.push(item);
          }
        
      }

      if(filterCase==0)
      {
        result.push(item);
      }

     

     /* if(item.portal.toLowerCase().indexOf($scope.filterPortal.id) !== -1)
      {
          console.log(new Date(item.invoice_date));
            result.push(item);
      }*/



     });
    $scope.onlineSalesList=result;
    $scope.dataTableOnlineSales();
  }

$scope.parseDate = function(input){
            var parts = input.split('/');
            var newParts = new Date(parts[2], parts[1]-1, parts[0]); // Note: months are 0-based
            return newParts;
        }

$scope.reset = function(){
            
            $scope.filterPortal=undefined;
            $scope.filterFromDate=undefined;
            $scope.filterToDate=undefined;
            if(typeof $scope.onlineSalesMasterCopy!="undefined")
            {
              $scope.onlineSalesList= $scope.onlineSalesMasterCopy;
            }
            $scope.dataTableOnlineSales();
        }

  $scope.dataTableOnlineSales=function(){

  var table = $('#dataTables-onlineSales').DataTable();
  table.destroy();
  $timeout(function() { $('#dataTables-onlineSales').DataTable({ "aaSorting": []});}, 0);

  }

 $scope.dataTableOnlineSales();

});




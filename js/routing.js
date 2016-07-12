app.config(['$routeProvider',
  function($routeProvider) {
    $routeProvider.
      when('/dashboard', {
        templateUrl: 'dashboard.html',
        controller: 'dashboard'        
      }).
      when('/stock', {
        templateUrl: 'stock_details.html',
        controller: 'uploadStock'
      }).
      when('/sales', {
        templateUrl: 'sales_details.html',
        controller: 'uploadSales'
      }).
      when('/return', {
        templateUrl: 'return_details.html',
        controller: 'uploadReturn'
      }).
      when('/onlineSales', {
        templateUrl: 'online_sales.html',
        controller: 'onlineSales',
        resolve:{onlineSalesList:function($http,DOMAIN,PORTAL){
          return $http.get(DOMAIN+PORTAL).then(function(data) { 
           
            return data.data;
          })
        }}
      }).

      when('/updateStatus', {
        templateUrl: 'update_status.html',
        controller: 'updateStatus',
        resolve:{onlineSalesList:function($http,DOMAIN,PORTAL){
          return $http.get(DOMAIN+PORTAL).then(function(data) { 
           
            return data.data;
          })
        }}
      }).
      when('/assignOrder', {
        templateUrl: 'assign_order.html',
        controller: 'assignOrder',
        resolve:{
          portals:function($http,DOMAIN,ORDER_ASSIGN_DETAILS_URL){
          return $http.get(DOMAIN+ORDER_ASSIGN_DETAILS_URL).then(function(data) {
            return data.data;
          });
        }
        }
      }).

      when('/skuMapping', {
        templateUrl: 'sku_mapping.html',
        controller: 'skuMapping',
        resolve:{
          portals:function($http,DOMAIN,ORDER_ASSIGN_DETAILS_URL){
          return $http.get(DOMAIN+ORDER_ASSIGN_DETAILS_URL).then(function(data) {
            return data.data;
          });
        }
        }
      }).
      when('/validateStock', {
        templateUrl: 'validate_stock.html',
        controller: 'validateStock'
      }).
      when('/users', {
        templateUrl: 'usermanagement.html',
        controller: 'userManager'
      }).
      when('/invoice', {
        templateUrl: 'invoice.html',
        controller: 'invoice'
      }).

      when('/salesReport', {
        templateUrl: 'salesReport.html',
        controller: 'salesReport'        
      }).
      when('/stockReport', {
        templateUrl: 'stockReport.html',
        controller: 'stockReport'        
      }).
      when('/returnReport', {
        templateUrl: 'returnReport.html',
        controller: 'returnReport'        
      }).
      otherwise({
        redirectTo: '/dashboard'
      });
}]);

app.run(['$rootScope', '$location', 'Auth', function ($rootScope, $location, Auth) {
    $rootScope.$on('$routeChangeStart', function (event) {
      
        if (!Auth.isLoggedIn()) {            
            event.preventDefault();
            window.location.href='login.html';
        }        
    });
}]);
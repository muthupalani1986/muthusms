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
      when('/users', {
        templateUrl: 'usermanagement.html',
        controller: 'userManager'
      }).
      when('/invoice', {
        templateUrl: 'invoice.html',
        controller: 'invoice'
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
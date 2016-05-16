// Service for accessing local storage
app.service('LocalStorage', [function() {

  var Service = {};

  // Returns true if there is a logo stored
  var hasLogo = function() {
    return !!localStorage['logo'];
  };

  // Returns a stored logo (false if none is stored)
  Service.getLogo = function() {
    if (hasLogo()) {
      return localStorage['logo'];
    } else {
      return false;
    }
  };

  Service.setLogo = function(logo) {
    localStorage['logo'] = logo;
  };

  // Checks to see if an invoice is stored
  var hasInvoice = function() {
    return !(localStorage['invoice'] == '' || localStorage['invoice'] == null);
  };

  // Returns a stored invoice (false if none is stored)
  Service.getInvoice = function() {
    if (hasInvoice()) {
      return JSON.parse(localStorage['invoice']);
    } else {
      return false;
    }
  };

  Service.setInvoice = function(invoice) {
    localStorage['invoice'] = JSON.stringify(invoice);
  };

  // Clears a stored logo
  Service.clearLogo = function() {
    localStorage['logo'] = '';
  };

  // Clears a stored invoice
  Service.clearinvoice = function() {
    localStorage['invoice'] = '';
  };

  // Clears all local storage
  Service.clear = function() {
    localStorage['invoice'] = '';
    Service.clearLogo();
  };

  return Service;

}]);

app.service('Currency', [function(){

  var service = {};

  service.all = function() {
    return [
      {
        name: 'Canadian Dollar ($)',
        symbol: 'CAD $ '
      },
      {
        name: 'Euro (€)',
        symbol: '€'
      },
      {
        name: 'Indian Rupee (₹)',
        symbol: '₹'
      },
      {
        name: 'Norwegian krone (kr)',
        symbol: 'kr '
      },
      {
        name: 'US Dollar ($)',
        symbol: '$'
      }
    ]
  }

  return service;
  
}]);

app.directive('numbersOnly', function () {
    return {
        require: 'ngModel',
        link: function (scope, element, attr, ngModelCtrl) {
            function fromUser(text) {
                if (text) {
                    var transformedInput = text.replace(/[^0-9]/g, '');

                    if (transformedInput !== text) {
                        ngModelCtrl.$setViewValue(transformedInput);
                        ngModelCtrl.$render();
                    }
                    return transformedInput;
                }
                return undefined;
            }            
            ngModelCtrl.$parsers.push(fromUser);
        }
    };
});

app.directive('uiDatePicker',function($timeout){
  return{
    restrict:'A',  
    scope:{
      ngModel:'='
    },  
    link:function(scope,element,attr){
      $(document).ready(function(){
        $('#'+attr.id).datepicker({format:'dd/mm/yyyy',todayHighlight: true,autoclose: true,clearBtn:true});
      });
      $('#'+attr.id).on('change keyup blur', function() {
        scope.ngModel=$(this).val();
        scope.$apply();
      });

    }
  }
});

app.directive('calendaricon',function($timeout){
  return{
    restrict:'C',
    link:function(scope,element,attr){
      element.on('click',function(){
        var elementId=$(this).closest('.date').find('input[type=text]').attr('id');
        $('#'+elementId).datepicker('show');
      })
    }
  }
});

app.directive('salesLoader',function($http,DOMAIN,PROD_STOCK_UPDATE_URL,$timeout){
  return{
  restrict:'A',
  link:function(scope,element,attr){

   $(document).ready(function(){      
      $('#'+attr.id).on('click', function() {      
      var $this = $(this);
      $this.button('loading');
      var currentUser=JSON.parse(localStorage.getItem('currentUser'));
      var postObject={"currentUser":[],"processType":[]};
      postObject.currentUser.push(currentUser);
      postObject.processType.push(attr.processType);
      $http.post(DOMAIN+PROD_STOCK_UPDATE_URL,postObject).then(function(data) {
       scope.prod_list=data.data.prod_list; 
          if(attr.processType=="sales")
          {          
           scope.prod_sales_list=data.data.prod_sales_list;
          
          }
         if(attr.processType=="return")
         {
           scope.prod_return_list=data.data.prod_return_list;          
         }
           var table=$('#dataTables-stock').DataTable();
           table.destroy();
           $timeout(function() { $('#dataTables-stock').DataTable();}, 0);
           $this.button('reset');

        },function(data){        
        scope.notification=true;
        scope.msg=API_ERROR; 
        scope.statusCode=400;
        });

      });
   });
   
  }
}
});
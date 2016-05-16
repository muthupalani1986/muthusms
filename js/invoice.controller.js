app.controller('invoice', ['$scope', '$http', 'DEFAULT_INVOICE', 'DEFAULT_LOGO', 'LocalStorage', 'Currency','$timeout','$window','$compile','PROD_LIST_URL','DOMAIN',
  function($scope, $http, DEFAULT_INVOICE, DEFAULT_LOGO, LocalStorage, Currency,$timeout,$window,$compile,PROD_LIST_URL,DOMAIN) {

  // Set defaults
  $scope.currencySymbol = '₹';
  $scope.logoRemoved = false;
  $scope.printMode   = false;
  $scope.$parent.title="Invoice";
  $scope.$parent.loaded=true;
  //$scope.countries = [{'id':'1','country':'Abkhazia','capital':'Sokhumi'},{'id':'2','country':'Afghanistan','capital':'Kabul'},{'id':'3','country':'Aland','capital':'Mariehamn'}];

   $http.get(DOMAIN+PROD_LIST_URL).then(function(data) {           
             $scope.countries=data.data.prod_list;            
          });

  (function init() {
    // Attempt to load invoice from local storage

    !function() {
      var invoice = LocalStorage.getInvoice();
      var d = new Date();
      function f(n) { return n < 10 ? '0' + n : n; }
      var random_num = Math.floor(Math.random() * (99999999999 -  10000000000)) + 10000000000;
      DEFAULT_INVOICE.invoice_number = d.getFullYear() + f(d.getMonth()+1) + f(d.getDate()) + random_num;
      $scope.invoice = invoice ? invoice : DEFAULT_INVOICE;
    }();

    // Set logo to the one from local storage or use default
    !function() {
      var logo = LocalStorage.getLogo();
      $scope.logo = logo ? logo : DEFAULT_LOGO;
    }();

    $scope.availableCurrencies = Currency.all();

  })()
  // Adds an item to the invoice's items
  $scope.addItem = function() {
    $scope.invoice.items.push({ qty:0, cost:0, description:"" });
  }

  // Toggle's the logo
  $scope.toggleLogo = function(element) {
    $scope.logoRemoved = !$scope.logoRemoved;
    LocalStorage.clearLogo();
  };

  // Triggers the logo chooser click event
  $scope.editLogo = function() {
    // angular.element('#imgInp').trigger('click');
    document.getElementById('imgInp').click();
  };

  // Remotes an item from the invoice
  $scope.removeItem = function(item) {
    $scope.invoice.items.splice($scope.invoice.items.indexOf(item), 1);
  };

  // Calculates the sub total of the invoice
  $scope.invoiceSubTotal = function() {
    var total = 0.00;
    angular.forEach($scope.invoice.items, function(item, key){
      total += (item.qty * item.cost);
    });
    return total;
  };

  // Calculates the tax of the invoice
  $scope.calculateTax = function() {
    return (($scope.invoice.tax * $scope.invoiceSubTotal())/100);
  };

  // Calculates the grand total of the invoice
  $scope.calculateGrandTotal = function() {
    saveInvoice();
    return $scope.calculateTax() + $scope.invoiceSubTotal();
  };

  // Clears the local storage
  $scope.clearLocalStorage = function() {
    var confirmClear = confirm('Are you sure you would like to clear the invoice?');
    if(confirmClear) {
      LocalStorage.clear();
      setInvoice(DEFAULT_INVOICE);
    }
  };

  // Sets the current invoice to the given one
  var setInvoice = function(invoice) {
    $scope.invoice = invoice;
    saveInvoice();
  };

  // Reads a url
  var readUrl = function(input) {
    if (input.files && input.files[0]) {
      var reader = new FileReader();
      reader.onload = function (e) {
        document.getElementById('company_logo').setAttribute('src', e.target.result);
        LocalStorage.setLogo(e.target.result);
      }
      reader.readAsDataURL(input.files[0]);
    }
  };

  // Saves the invoice in local storage
  var saveInvoice = function() {
    LocalStorage.setInvoice($scope.invoice);
  };

  // Runs on document.ready
  angular.element(document).ready(function () {
    // Set focus
    //document.getElementById('invoice-number').focus();

    // Changes the logo whenever the input changes
    document.getElementById('imgInp').onchange = function() {
      readUrl(this);
    };
  });

$scope.printInfo=function(){

//$timeout($window.print, 0);


   //printElement(document.getElementById("invoice"));
    
    //var modThis = document.querySelector("#printSection .modifyMe");
    //modThis.appendChild(document.createTextNode(" new"));
    
    window.print();


  

}


}]);
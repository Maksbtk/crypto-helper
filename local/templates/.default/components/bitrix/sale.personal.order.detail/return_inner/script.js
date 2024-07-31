$(document).ready(function() {
   $("#copyInBasketBtn").on("click", function() {
       var names = [];
      $("div.pastorder-item-name-prod a").each(function(i) {
          names.push({name: $(this).data('prod'), quantity: 1});
      });
      $("td.quantity-class").each(function(i) {
          names[i].quantity = $(this).text().split(" ")[0];
      });

       $.ajax({
           type: 'POST',
           url: '/ajax/copyInBasket.php',
           data: {products: names},
           success: function(data) {
               if (data) {
                   $("#basket-header span").text(JSON.parse(data).number);
                   window.location.href = "/cart/";
               }
           }
       });
   });
});
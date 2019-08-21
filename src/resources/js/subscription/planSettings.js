
$('#gatewayId').on('change',function(e){
    if($(this).val().length > 0 ){
        $.get(
            '/admin/api/paypal/subscription/'+$(this).val()+'/plans',
            function(){
                console.log(arguments);
            }
        )
    }

})

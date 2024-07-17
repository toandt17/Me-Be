(function($){
    $(document).ready(function(){
        $('.btn_chatZalo').click(function(){
            var form = {
                action: 'zaloprocess',
                click: 1,
            };
            $.post(zalo_obj.ajax_url,form,function(response){
                 // Log the response to the console
                console.log("Response: "+response);
            });

        })
        
    })
})(jQuery);
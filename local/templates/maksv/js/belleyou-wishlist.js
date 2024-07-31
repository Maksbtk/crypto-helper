$(document).ready(function() {

    $('body').on('click', '.js-action-favorite', function(e){
        e.preventDefault();

        const $target = $(this);
        const prodId = $target.data('product-id');
        const action = $target.data('action');
       // const header_item = $('li.user-menu__item-favorites');

        $.ajax({
            url: '/ajax/favorite.php',
            type: "post",
            data: {
                'product_id': prodId,
                'action': action === 'delete-list' ? 'delete' : action
            },
            dataType: 'json',
            success: function (data) {
                if (data.result == 'fail') {
                    console.log('fail')
                    window.location.reload();
                } else {
                    switch (action) {
                        case 'delete':

                            break;
                        case 'delete-list':


                            break;
                        case 'add':

                            break;
                    }

                  
                }
            }
        });
    })

});
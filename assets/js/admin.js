$('.list-group-item').on('click', function(){
    $('.list-group-item').removeClass('active');
    $(this).addClass('active');
    $('.setting-card').addClass('d-none');
    $('.'+$(this).data('card')+'-card').removeClass('d-none');
});


$('#login_slug').on('input', function(){
    $('.login_slug').html($(this).val());
});

$('#register_slug').on('input', function(){
    $('.register_slug').html($(this).val());
});



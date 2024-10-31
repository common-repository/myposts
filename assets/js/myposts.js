jQuery(function ($) {

    $('#myposts-form').validate()

    $(document).on('click', '.myposts-vote', function (e) {
        var el = $(this);
        var id = el.data('id');
        $.post(myposts.ajax_url, {
            action: 'myposts_vote',
            id: id
        }, function (r) {
            if(r == 0) {
                el.closest('.myposts-wrapper').find('.myposts-count').addClass('empty').text('');
            } else {
                el.closest('.myposts-wrapper').find('.myposts-count').removeClass('empty').text(r);
            }
        })
        e.preventDefault();
    })

    var mypostsTimer = null;

    $(document).on('input', '.myposts-form input[name=url]', function() {
        var url = $(this).val();
        clearTimeout(mypostsTimer);
        mypostsTimer = setTimeout(function() {
            $('.myposts-loading').show();
            $.ajax({
                url: myposts.ajax_url,
                type: 'post',
                data: {
                    url: url,
                    action: 'parse_url'
                },
                dataType: 'json',
                success: function(r) {
                    if(r.post_title) {
                        $('.myposts-form [name=title]').val(r.post_title);
                        $('.myposts-form [name=content]').val(r.post_content);
                        $('.myposts-form [name=image]').val(r.image);
                        $('.myposts-form [name=provider]').val(r.provider);
                    } else {
                        alert(myposts.messages.parse_error)
                    }
                    $('.myposts-loading').hide();
                },
                error: function() {
                    alert(myposts.messages.parse_error);
                    $('.myposts-loading').hide();
                }
            })
        }, 1000);
    })
})

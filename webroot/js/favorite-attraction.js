var toggleFavorite = function (btn, attraction_id, csrf_token) {
    $.ajax({
        url: '/attractions/toggle-favorite',
        type: 'POST',
        data: { attraction_id: attraction_id },
        dataType: 'JSON',
        headers: { 'X-CSRF-Token': csrf_token }
    }).done(function (res) {
        if (res.data.id !== null) {
            btn.addClass('fa-bookmark text-green').removeClass('fa-bookmark-o');
            btn.attr('title', 'Remove from favorites');
        } else {
            btn.removeClass('fa-bookmark text-green').addClass('fa-bookmark-o');
            btn.attr('title', 'Add to favorites');
        }
    }).fail(function (res) {
        console.log(res);
    });
}
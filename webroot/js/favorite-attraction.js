/*
 * Favourite toggle on the attraction pages. The button (.btn-favorite) carries the Font Awesome
 * classes itself (fa-bookmark text-green = favourite, fa-bookmark-o = not), which e2e checks.
 * Bound by data attributes: <button class="btn-favorite" data-attraction-id data-url data-label-on data-label-off>.
 */
var toggleFavorite = function (btn, attraction_id, csrf_token) {
    var $btn = $(btn);
    var url = $btn.data('url') || '/attractions/toggle-favorite';
    var headers = {};
    var token = csrf_token || (window.sgUi ? sgUi.csrfToken() : '');
    if (token) headers['X-CSRF-Token'] = token;
    $btn.prop('disabled', true);
    $.ajax({
        url: url,
        type: 'POST',
        data: { attraction_id: attraction_id },
        dataType: 'JSON',
        headers: headers
    }).done(function (res) {
        var on = res.data && res.data.id !== null;
        var label = on ? ($btn.data('label-on') || 'Remove from favorites') : ($btn.data('label-off') || 'Add to favorites');
        $btn.toggleClass('fa-bookmark text-green', on).toggleClass('fa-bookmark-o', !on);
        $btn.attr({ title: label, 'aria-label': label, 'aria-pressed': on ? 'true' : 'false' });
        if (window.sgUi) {
            sgUi.toast(on ? ($btn.data('added') || 'Added to favourites') : ($btn.data('removed') || 'Removed from favourites'), 'success');
        }
    }).fail(function (res) {
        console.log(res);
        if (window.sgUi) sgUi.toast($btn.data('failed') || 'Could not update favourites. Please try again.', 'danger');
    }).always(function () {
        $btn.prop('disabled', false);
    });
};

$(function () {
    $(document).on('click', '.btn-favorite[data-attraction-id]', function () {
        toggleFavorite($(this), $(this).data('attraction-id'));
    });
});

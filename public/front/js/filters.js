$(document).ready(function() {

    // Handle filter checkboxes and sorting dropdown
    $(document).on('change', '.filterAjax, .getsort', function() {
        RefreshFilters("yes");
    });

    // Placeholder hook for price filter UI
    $(document).on('click', '#pricesort', function() {
        RefreshFilters("yes");
    });
});

// Build query and call Ajax
function RefreshFilters(type) {
    var queryStringObject = {};

    // Collect checked filters
    $('.filterAjax:checked').each(function() {
        var name = $(this).attr('name');

        if (!queryStringObject[name]) {
            queryStringObject[name] = [];
        }

        queryStringObject[name].push($(this).val());
    });

    // Sort dropdown
    var sortValue = $('.getsort').val();
    var sortName = $('.getsort').attr('name');

    if (sortValue) {
        queryStringObject[sortName] = sortValue;
    }

    if (type === "yes") {
        filterproducts(queryStringObject);
    } else {
        filterproducts({});
    }
}

// Ajax call to fetch filtered products
function filterproducts(queryStringObject) {
    $('body').css({'overflow': 'hidden'});

    var queryString = "";

    for (var key in queryStringObject) {
        if (!Object.prototype.hasOwnProperty.call(queryStringObject, key)) {
            continue;
        }

        queryString += (queryString === '' ? '?' : '&') + encodeURIComponent(key) + '=';
        var queryValue = Array.isArray(queryStringObject[key])
            ? queryStringObject[key].join("~")
            : queryStringObject[key];
        queryString += encodeURIComponent(queryValue);
    }

    var newurl = window.location.protocol + "//" + window.location.host + window.location.pathname + queryString;

    if (history.pushState) {
        window.history.pushState({path: newurl}, '', newurl);
    }

    var ajaxUrl = newurl.indexOf("?") >= 0 ? (newurl + "&json=") : (newurl + "?json=");

    $.ajax({
        url: ajaxUrl,
        type: 'GET',
        dataType: 'json',
        success: function(resp) {
            $("#appendProducts").html(resp.view);
            document.body.style.overflow = 'scroll';
        },
        error: function() {
            document.body.style.overflow = 'scroll';
        }
    });
}

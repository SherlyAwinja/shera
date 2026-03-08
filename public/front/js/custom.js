$(document).ready(function() {
    /*$('#sortForm').on('change', function(){
        this.form.submit();
    });
    */
   $('#search_input').on('keyup', function () {

        let query = $(this).val();

        if (query.length > 2) {

            $.ajax({
                url: '/search-products',
                method: 'GET',
                data: { q: query },

                success: function (data) {
                    $('#search_result').html(data);
                }
            });

        } else {
            $('#search_result').html('');
        }

    });
});

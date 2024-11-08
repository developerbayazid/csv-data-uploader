jQuery(document).ready(function () {
    jQuery('#frm_csv_upload').on('submit', function(e){
        e.preventDefault();  // Prevent the default form submission

        var formData = new FormData(this);

        jQuery.ajax({
            url: cdu_obj.ajax_url,
            data: formData,
            dataType: 'json',
            method: 'POST',
            processData: false,
            contentType: false,
            success: function (response) {
                jQuery('#show_upload_message').html(response.message).css({
                    color: 'green'
                });
            },
            error: function (jqXHR) {
                // Try to split and parse the concatenated JSON responses
                let rawResponses = jqXHR.responseText.split('}{').map((str, index, arr) => {
                    // Re-add the missing curly braces after splitting
                    if (index === 0) return str + '}';
                    if (index === arr.length - 1) return '{' + str;
                    return '{' + str + '}';
                });

                let uniqueMessages = new Set();
                rawResponses.forEach(response => {
                    try {
                        let jsonResponse = JSON.parse(response);
                        uniqueMessages.add(jsonResponse.message);
                    } catch (e) {
                        console.error('Error parsing JSON:', e);
                    }
                });

                // Display unique messages in red
                jQuery('#show_upload_message').html(
                    Array.from(uniqueMessages).join('<br>')
                ).css({
                    color: 'red'
                });
            }
        });
    });
});

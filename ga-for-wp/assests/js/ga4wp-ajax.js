jQuery(document).ready(function(){
    jQuery('.ga4wp-dismiss-maybelater').click(function(){
        var data = {
                'action': 'ga4wp_hide_review_notice',
                'security': ajax_object.maybelater_nonce,
        };
        jQuery.post(ajax_object.ajax_url, data, function() {
            alert('Thanks for your response!');
            location.reload();
        });
    });
    jQuery('.ga4wp-dismiss-alreadydid').click(function(){
        var data = {
                'action': 'ga4wp_hide_review_notice',
                'security': ajax_object.alreadydid_nonce,
        };
        jQuery.post(ajax_object.ajax_url, data, function() {
            alert('Thanks for your response!');
            location.reload();
        });
    });
});
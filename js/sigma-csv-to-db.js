window.send_to_editor = function (html) {
    var imgurl = jQuery('img', html).attr('src');
    var url = jQuery(html).attr('href');

    var regexp = /\.*uploads(.*)/;
    var matches_array = url.match(regexp);
    path = sigma_admin_vars.sigma_upload_base_dir + matches_array[1]

    // set the url as the value
    jQuery('#csv_file').val(path);
    //tb_remove();
};

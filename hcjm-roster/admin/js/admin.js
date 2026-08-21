/* HCJM Roster — Admin JS: WordPress Media Library uploader */
(function ($) {
    'use strict';

    var mediaFrame;

    $(document).on('click', '.hcjm-upload-btn', function (e) {
        e.preventDefault();
        var $btn     = $(this);
        var targetId = $btn.data('target');
        var previewId= $btn.data('preview');

        mediaFrame = wp.media({
            title:    'Vybrat fotku',
            button:   { text: 'Použít tuto fotku' },
            multiple: false,
            library:  { type: 'image' },
        });

        mediaFrame.on('select', function () {
            var attachment = mediaFrame.state().get('selection').first().toJSON();
            $('#' + targetId).val(attachment.id);
            var thumbUrl = attachment.sizes && attachment.sizes.thumbnail
                ? attachment.sizes.thumbnail.url
                : attachment.url;
            $('#' + previewId).html('<img src="' + thumbUrl + '" class="hcjm-thumb-lg">');
        });

        mediaFrame.open();
    });

    $(document).on('click', '.hcjm-remove-photo', function (e) {
        e.preventDefault();
        var $btn     = $(this);
        var targetId = $btn.data('target');
        var previewId= $btn.data('preview');
        $('#' + targetId).val('0');
        $('#' + previewId).html('');
        $btn.remove();
    });

}(jQuery));

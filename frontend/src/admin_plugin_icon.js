/**
 * Admin script for handling Plugin Icon settings.
 *
 * - Live preview when icon URL changes
 * - Reset to default icon
 * - Upload/select icon via WordPress media library
 */
jQuery(function ($) {
  let mediaUploader = null;
  const buttonText = "<?php echo esc_attr( $button_text ) ?>";
  const mediaTitle = "<?php echo esc_attr( $select_title ) ?>";

  // Plugin Icon handling
  function handlePluginIcon() {
    $("#pwspfwc_plugin_icon").on("input", function () {
      const imgSrc = $(this).val();
      $("#pwspfwc_img_icon").attr("src", imgSrc);
    });

    $("#pwspfwc_btn_reset").on("click", function () {
      const defaultIcon = $(this).data("defaulticon");
      $("#pwspfwc_plugin_icon").val(defaultIcon).trigger("input");
    });

    $("#pwspfwc_btn_upload").on("click", function (e) {
      e.preventDefault();

      if (mediaUploader) {
        mediaUploader.open();
        return;
      }

      mediaUploader = wp.media.frames.file_frame = wp.media({
        title: mediaTitle,
        button: { text: buttonText },
        library: { type: "image" },
        multiple: false
      });

      mediaUploader.on("select", function () {
        const attachment = mediaUploader.state().get("selection").first().toJSON();
        const url = attachment.sizes.thumbnail ? attachment.sizes.thumbnail.url : attachment.url;
        $("#pwspfwc_plugin_icon").val(url).trigger("input");
      });

      mediaUploader.open();
    });
  }

  $(() => handlePluginIcon());
});

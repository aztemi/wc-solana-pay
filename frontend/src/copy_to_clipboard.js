/**
 * <script> tag logic to copy text to clipboard
 *
 * This script is loaded from the PHP code side.
 */

import "./lib/styles/global.styl";

jQuery(function ($) {
  function handleCopyToClipboard() {
    $(".pwspfwc_copy_button").on("click", function () {
      var $button = $(this);
      $button.trigger("blur");
      var textToCopy = $button.data("text");

      if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard
          .writeText(textToCopy)
          .then(function () {
            $button.children("span").toggleClass("pwspfwc_hidden");

            setTimeout(function () {
              $button.children("span").toggleClass("pwspfwc_hidden");
            }, 2000);
          })
          .catch(function (err) {
            console.error("Failed to copy text: ", err);
          });
      }
    });
  }

  $(() => handleCopyToClipboard());
});

/**
 * <script> tag logic on the WC Payments Admin Settings page
 *
 * This script is loaded from the PHP code side.
 */

(function ($$) {
  // Testmode Dropdown Select handling
  function handleTestmodeDropdown() {
    if ("devnet" === $$("select[name*=_network] option").filter(":selected").val()) {
      $$("tr.live_only").hide();
      $$("tr.testmode_only,span.testmode_only").show();
    } else {
      $$("tr.live_only").show();
      $$("tr.testmode_only,span.testmode_only").hide();
    }
  }

  function handleOnchange() {
    handleTestmodeDropdown();
  }

  function init() {
    window.onchange = handleOnchange;
    handleOnchange();
  }

  $$("document").ready(init);
})(jQuery);

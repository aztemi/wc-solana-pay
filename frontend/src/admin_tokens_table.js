/**
 * Admin script for handling Network/Testmode UI visibility.
 *
 * - Shows or hides settings rows depending on the selected network.
 * - If "devnet" is selected → test mode fields are shown.
 * - Otherwise → live-only fields are shown.
 */
jQuery(function ($) {
  // Testmode Dropdown Select handling
  function handleTestmodeDropdown() {
    if ("devnet" === $("select[name*=_network] option").filter(":selected").val()) {
      $("tr.live_only").hide();
      $("tr.testmode_only,span.testmode_only").show();
    } else {
      $("tr.live_only").show();
      $("tr.testmode_only,span.testmode_only").hide();
    }
  }

  $(() => handleTestmodeDropdown());

  window.addEventListener("change", handleTestmodeDropdown);
});

/**
 * <script> tag logic for Currencies table on WC Payments Admin Settings page
 *
 * This script is loaded and the <?php ?> placeholders are replaced from PHP code side.
 */
import BigNumber from "bignumber.js";

(function ($$) {
  const DP = 6; // default decimal places
  let updateAvailable = false;
  const storeCurrency = "<?php echo $base_currency ?>".toLowerCase();
  const updateNotAvailableMsg = "<?php echo $alert_msg ?>".trim();

  async function fetchRequest(url) {
    return fetch(url)
      .then(resp => resp.json())
      .catch(e => {
        console.error(e.message);
        return null;
      });
  }

  // check if store currency is supported for conversion by Coingecko
  async function checkCoingeckoSupport() {
    const url = "https://api.coingecko.com/api/v3/simple/supported_vs_currencies";
    const data = await fetchRequest(url);
    if (data) updateAvailable = data.includes(storeCurrency);
  }

  async function getExchangeRate(token) {
    const url = `https://api.coingecko.com/api/v3/simple/price?ids=${token}&vs_currencies=${storeCurrency}`;
    const data = await fetchRequest(url);
    return data ? data[token][storeCurrency] : 0;
  }

  function getRate(rate) {
    const one = new BigNumber(1);
    const bigRate = new BigNumber(rate);

    return one.dividedBy(bigRate).decimalPlaces(DP, BigNumber.ROUND_CEIL).toNumber();
  }

  function getRateWithFee(rate, fee) {
    const bigRate = new BigNumber(rate);
    const bigFee = new BigNumber(fee);
    const commission = bigRate.multipliedBy(bigFee).dividedBy(100);

    return bigRate.plus(commission).decimalPlaces(DP, BigNumber.ROUND_CEIL).toNumber();
  }

  // Update token rate handling
  function handleUpdateClick() {
    $$("tr.token").each(function () {
      const tr = $$(this);
      tr.on("click", ".dashicons-update", async function () {
        if (!updateAvailable) {
          return alert(updateNotAvailableMsg);
        }
        const icon = $$(this);
        const coingecko = icon.data("coingecko");
        const rate = await getExchangeRate(coingecko);

        if (rate) {
          const exchangeRate = getRate(rate);
          tr.find("td input[name*=rate]").val(exchangeRate);
          handleCurrencyPreview();
        }
      });
    });
  }

  // Enable Testmode checkbox handling
  function handleTestmodeCheckbox() {
    if ("devnet" === $$("select[name*=spfwc_network] option").filter(":selected").val()) {
      $$("tr.live_only").hide();
      $$("tr.testmode_only").show();
    } else {
      $$("tr.live_only").show();
      $$("tr.testmode_only").hide();
    }
  }

  // Currency preview handling
  function handleCurrencyPreview() {
    $$("tr.token").each(function () {
      const tr = $$(this);
      const rate = tr.find("td input[name*=rate]").val();
      const fee = tr.find("td input[name*=fee]").val();
      const label = tr.find("td input[name*=label]").val();
      const val = getRateWithFee(rate, fee);
      tr.find("span.token_preview").text(`${val} ${label}`);
    });
  }

  function handleOnchange() {
    handleTestmodeCheckbox();
    handleCurrencyPreview();
  }

  function init() {
    window.onchange = handleOnchange;
    checkCoingeckoSupport();
    handleUpdateClick();
    handleOnchange();
  }

  $$("document").ready(init);
})(jQuery);

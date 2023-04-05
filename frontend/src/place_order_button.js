/**
 * <script> tag logic for our custom "Place Order" button on Checkout page
 *
 * It shows
 * - "Pay with Solana Pay" express checkout button when our Solana Pay payment method is selected and
 * - default "Place Order" button when other payment methods are selected.
 *
 * This script is loaded and the <?php ?> placeholders are replaced from PHP code side.
 */
(function ($$) {
  let ourBtn, theirBtn;
  const id = "<?php echo $id ?>";
  const msg = "<?php echo $msg ?>";
  const previousOnload = window.onload;
  const previousOnchange = window.onchange;

  function $(selector) {
    return document.querySelector(selector);
  }

  function handleOnclick(evt) {
    const checkoutForm = $$("form.checkout");
    checkoutForm.find(".validate-required:visible :input:visible").trigger("validate").trigger("blur");
    const invalidFields = checkoutForm.find(".validate-required.woocommerce-invalid:visible").length;

    if (invalidFields) {
      $$(".woocommerce-NoticeGroup-checkout, .woocommerce-error, .woocommerce-message").remove();
      const errorMsg = `<div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-checkout"><ul class="woocommerce-error" role="alert"><li>${msg}</li></ul></div>`;
      checkoutForm.get(0)?.scrollIntoView({ behavior: "smooth" });
      checkoutForm.prepend(errorMsg);
    } else {
      // show Solana payment modal
      dispatchEvent(new Event("openmodal"));
    }

    evt.preventDefault();
    evt.stopPropagation();
  }

  function handleOnchange() {
    if (typeof previousOnchange === "function") previousOnchange();

    const ourTemplate = $("#template_our_btn");
    const theirTemplate = $("#template_their_btn");
    const wrapper = $("#place_order_btn_wrapper");
    const selected_payment_method = $("input[name='payment_method']:checked")?.value;

    if (ourTemplate) {
      ourBtn = ourTemplate.content.firstElementChild.cloneNode(true);
      ourBtn.onclick = handleOnclick;
    }
    if (theirTemplate) theirBtn = theirTemplate.content.firstElementChild.cloneNode(true);

    if (wrapper && ourBtn && theirBtn) {
      wrapper.innerHTML = "";
      wrapper.appendChild(selected_payment_method === id ? ourBtn : theirBtn);
    }
  }

  window.onload = function () {
    if (typeof previousOnload === "function") previousOnload();
    window.onchange = handleOnchange;
  };

  handleOnchange();
})(jQuery);

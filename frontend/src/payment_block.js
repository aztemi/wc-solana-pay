/**
 * Client side support for Gutenberg Blocks integration
 */

import { useEffect } from "@wordpress/element";
import { decodeEntities } from "@wordpress/html-entities";
import { getSetting } from "@woocommerce/settings";
import { registerPaymentMethod } from "@woocommerce/blocks-registry";

const id = "wc-solana-pay";
const settings = getSetting(`${id}_data`, {});
const label = decodeEntities(settings.title) || "";
const description = decodeEntities(settings.description) || "";

/** Content  component */
function Content({ activePaymentMethod, eventRegistration, emitResponse }) {
  const { onPaymentSetup } = eventRegistration;
  const { responseTypes } = emitResponse;

  useEffect(() => {
    if (activePaymentMethod !== id) return;

    const unsubscribe = onPaymentSetup(() => {
      return new Promise(async resolve => {
        const error = () => resolve({ type: responseTypes.ERROR });

        const success = (key = "") =>
          resolve({
            type: responseTypes.SUCCESS,
            meta: {
              paymentMethodData: {
                pwspfwc_payment_token: key
              }
            }
          });

        // show Solana payment modal
        const event = new CustomEvent("openmodal", { detail: { success, error } });
        dispatchEvent(event);
      });
    });

    return unsubscribe;
  }, [onPaymentSetup, activePaymentMethod]);

  return <div>{description}</div>;
}

/** Icon component from the icon svg url */
function Icon() {
  return (
    <svg width="86" height="32" style={{ marginRight: "1rem" }}>
      <image xlinkHref={settings.icon} width="86" height="32" />
    </svg>
  );
}

/** Label component */
function Label({ components }) {
  const { PaymentMethodLabel } = components;
  return <PaymentMethodLabel text={label} icon={<Icon />} />;
}

const paymentMethod = {
  name: id,
  label: <Label />,
  content: <Content />,
  edit: <Content />,
  ariaLabel: label,
  canMakePayment: () => true,
  supports: {
    features: settings.supports ?? []
  }
};

registerPaymentMethod(paymentMethod);

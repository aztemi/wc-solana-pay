/**
 * Client side support for Gutenberg Blocks integration
 */

import { decodeEntities } from "@wordpress/html-entities";
import { getSetting } from "@woocommerce/settings";
import { registerPaymentMethod } from "@woocommerce/blocks-registry";

const id = "wc-solana-pay";
const settings = getSetting(`${id}_data`, {});
const icon = decodeEntities(settings.icon) || "";
const label = decodeEntities(settings.title) || "";
const description = decodeEntities(settings.description) || "";

/** Content  component */
function Content() {
  return <div>{description}</div>;
}

/** Icon component from the icon svg url */
function Icon() {
  if (!icon) return;
  return (
    <img src={icon} alt={`${label} icon`} style={{ verticalAlign: "middle", marginRight: "1rem", maxHeight: "2.5rem" }} />
  );
}

/** Label component */
function Label({ components }) {
  const { PaymentMethodLabel } = components;
  return (
    <div>
      <Icon />
      <PaymentMethodLabel text={label} />
    </div>
  );
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

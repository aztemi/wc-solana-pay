# solana-pay-for-woocommerce

Solana Pay payment gateway for WordPress and WooCommerce

<img src="/assets/img/screenshot.jpg" alt="Solana Pay Demo Screenshot on a smartphone" width="320">

This is a payment gateway built for WordPress as a WooCommerce plugin for online stores to accept payments in USDC and SOL through Solana Pay. It was created as an entry to the [Solana Grizzlython Hackathon](https://solana.com/grizzlython).

## Demo Website

Find below an online store for the project. Please, visit to try it out.

- [https://solana-pay-demo.juxdan.io/](https://solana-pay-demo.juxdan.io/)

## Features

Below are some of the major features of this zkApp.

- Support for payments in both USDC and SOL
- "Pay with Solana Pay" smart button integrated on Checkout page
- Payment possible through QR Code scan or connection to browser wallet
- Customizable settings under WooCommerce Payment page

## How to build

1. Clone this git repository and change to the project directory

```bash
git clone https://github.com/aztemi/solana-pay-for-woocommerce.git
cd solana-pay-for-woocommerce
```

2. Install project dependencies

```bash
npm install
```

3. To create a release package, run below commands.

```bash
npm run build --workspaces
npm run makepot
npm run package
```

4. This will get you a zip file ready to be used on a WordPress site. The zip file will be generated under the root folder. Upload and install the zip package on a WP website by following [these instructions](https://www.hostinger.com/tutorials/wordpress/how-to-install-wordpress-plugins).

## Buy me a coffee

Solana Wallet: BFSi8WeoE2bLJtMUpB6KVggJZ4Uv5DavVrVsm5kdrQwY

## License

[GPL-3.0](./LICENSE)

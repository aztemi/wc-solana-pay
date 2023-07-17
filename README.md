# wc-solana-pay

Solana Pay payment gateway for WordPress and WooCommerce

<img src="/assets/img/screenshot.png" alt="Pay with Solana Pay demo screenshot" width="240">

It is a payment gateway for accepting crypto payments in SOL, USDC, USDT and more in online WooCommerce eCommerce stores. This project was originally created as an entry to the [Solana Grizzlython Hackathon](https://solana.com/grizzlython). It has evolved into a full-fledged project that is now available on the [WordPress Plugins](https://wordpress.org/plugins/) site for download and install.

## Demo Website

Try it out for yourself. Below is an online store for the project.

- [https://solana-pay-demo.juxdan.io/](https://solana-pay-demo.juxdan.io/)

## Features

Below are some of the major features of this plugin.

- Solana Pay smart button integrated with the Checkout page for express checkout without redirect.
- Supports payments via QR Code scan and connection to browser wallets.
- Supports payments in SOL, USDC, and USDT. More tokens can easily be added through configuration.
- Customizable settings under WooCommerce Payment page.

## How to build

1. Clone this git repository and change to the project directory

```bash
git clone https://github.com/aztemi/wc-solana-pay.git
cd wc-solana-pay
```

2. Install project dependencies

```bash
npm install
```

3. To create a release package, run below commands.

```bash
npm run build
npm run makepot
npm run package
```

4. This will get you a zip file ready to be used on a WordPress site. The zip file will be generated under the root folder. Upload and install the zip package on a WP website by following [these instructions](https://www.hostinger.com/tutorials/wordpress/how-to-install-wordpress-plugins).

## Buy me a coffee

Solana Wallet: BFSi8WeoE2bLJtMUpB6KVggJZ4Uv5DavVrVsm5kdrQwY

## License

[GPL-3.0](./LICENSE.txt)

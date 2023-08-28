=== Pay with Solana Pay for WooCommerce ===
Contributors: aztemi, t4top
Donate link: https://apps.aztemi.com/wc-solana-pay
Tags: solana pay, solana, woocommerce, payment, web3, crypto, cryptocurrency, sol, usdc, usdt, wallet
Requires at least: 5.2
Tested up to: 6.2.2
Stable tag: 2.1.1
Requires PHP: 7.2
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Pay with Solana Pay for WooCommerce - Fast payment gateway powered by Solana blockchain.

Accept crypto payments in SOL, USDC, USDT and more via QR Code and through all major Solana wallets, including Phantom, Solflare and Backpack.

This is a quick and easy way to add crypto payments to your WooCommerce store and WordPress website. Give your customers a fast, seamless crypto checkout experience and increase your conversion rate.

= Demo Store =

Below is a live demo store. Get free tokens for testing from the [Devnet Faucet](https://apps.aztemi.com/wc-solana-pay/faucet/) and try out the plugin for yourself.

* [https://solana-pay-demo.juxdan.io/](https://solana-pay-demo.juxdan.io/)

= Features =

* Solana Pay smart button integrated with the Checkout page for express checkout without redirect.
* Supports payments via QR Code scan and connection to browser wallets.
* Fast transactions handling and direct payments into the Solana wallet address that you configure.
* Supports payments in SOL, USDC and USDT. More tokens are planned for future releases.

= Benefits for Merchants =

* Immediate cash flow. Payments go directly into your Solana wallet.
* No lock-ups, No redirect, No pay-later intermediaries. Transactions are settled onchain immediately.
* No upfront costs, No upsell. Merchant fee is 0.5% per transaction. We make money only when you do.

== Installation ==

1. Go to **Plugins > Add New**.
2. Search for **Pay with Solana Pay for WooCommerce** plugin.
3. Click on **Install Now** and wait until the plugin is installed successfully.
4. Click on **Activate**. (You can also activate on the **Plugins > Installed Plugins** page).

= Setup Configurations =

1. After activating the plugin, go to **WooCommerce > Settings**. Choose **Payments** tab and select **Pay with Solana Pay**.
2. Select **Enable Pay with Solana Pay** checkbox.
3. Add your Solana wallet address to the **Merchant Wallet Address** field.
4. Choose **Solana Network**. Select **Mainnet-Beta** for the Production Mode. (You can try first in Test Mode by selecting **Devnet**. Get free tokens for testing from the [Devnet Faucet](https://apps.aztemi.com/wc-solana-pay/faucet/).)
5. Enable Solana Tokens you want to accept. You can adjust their **% Commission** to compensate for fluctuations in tokens prices.
6. Click **Save changes**.

= Minimum Requirements =

* WordPress 5.2
* WooCommerce 3.0
* PHP 7.2 with BCMath extension installed

== Frequently Asked Questions ==

= What is Solana Pay? =

Solana Pay is a fast, decentralized, permissionless, and open source payment framework built on Solana blockchain. It is built for immediate transactions with fees that are fractions of a penny, and a net-zero environmental impact. See [Solana Pay](https://solanapay.com/) to learn more.

= Which Solana tokens can be accepted as payment? =

We currently support payments in SOL, USDC and USDT. Support for more tokens are planned to be added in future releases.

= How much does it cost to use? =

**Pay with Solana Pay for WooCommerce** is free to install on your WordPress website. We charge a 0.5% fee per transaction. You only pay when you receive transactions. We make money only when you do.

== Screenshots ==

1. Solana Pay button on the checkout page.
2. Payment popup dialog.
3. Acceptable Solana tokens dropdown list.
4. Admin settings page.

== Source Code ==

This plugin is an open source software with [GPLv3 or later](https://www.gnu.org/licenses/gpl-3.0.html) license. The code is available from our [repository on GitHub](https://github.com/aztemi/wc-solana-pay).

== Changelog ==

= 2.1.1 =
* Handle REST requests and sanitize their payloads using WP APIs.

= 2.1.0 =
* Fix review comments from the WP Plugin Review team.

= 2.0.0 =
* Initial release to the WordPress plugins repository for listing.
* Change plugin name to **Pay with Solana Pay for WooCommerce**.

= 1.0.0 =
* Initial private release

== Upgrade Notice ==

= 2.1.1 =
First public listing on the WordPress plugins repository - No further actions required

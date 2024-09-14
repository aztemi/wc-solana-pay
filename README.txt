=== WC Solana Pay ===
Contributors: aztemi, t4top
Donate link: https://apps.aztemi.com/wc-solana-pay/donate/
Tags: solana pay, solana, payment, web3, crypto, sol, usdt, usdc, pyusd, eurc, euroe, block, blockchain, cryptocurrency, woocommerce, wc, wc solana pay, wc-solana-pay, solanapay
Requires at least: 5.2
Tested up to: 6.6.2
Stable tag: 2.9.0
Requires PHP: 7.2
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

== Description ==

WC Solana Pay - Fast payment gateway powered by Solana blockchain with support for **Block** and **Classic** themes.

Accept crypto payments in **SOL**, **USDT**, **USDC**, **EURC** and more via **QR Code** and through all major Solana wallets, including Phantom, Solflare and Backpack.

This is a quick and easy way to add crypto payments to your WooCommerce store and WordPress website. Give your customers a fast, seamless crypto checkout experience and increase your conversion rate.

[youtube https://www.youtube.com/watch?v=ArqS84jGjE0]

= Demo Store =

Below is a live demo store. Get free tokens for testing from the [Devnet Faucet](https://apps.aztemi.com/wc-solana-pay/faucet/) and try out the plugin for yourself.

* [https://solana-pay-demo.juxdan.io/](https://solana-pay-demo.juxdan.io/)

= Supported Tokens for Payments =

* Solana (SOL)
* Tether USD Stablecoin (USDT)
* Circle USD Stablecoin (USDC)
* PayPal USD Stablecoin (PYUSD)
* Circle EURO Stablecoin (EURC)
* EUROe Stablecoin (EUROe)

= Features =

* Solana Pay smart button integrated with the Checkout page for express checkout without redirect
* Supports payments via QR Code scan and connection to browser wallets
* Fast transactions handling and direct payments into the Solana wallet address that you configure
* Compatible with Gutenberg Block and Classic themes

= Benefits for Merchants =

* Get paid instantly for immediate cash flow. Payments go directly into your Solana wallet.
* No lock-ups, No redirect, No pay-later intermediaries. Transactions are settled onchain immediately.
* No setup fees, No monthly fees, No upsell. Only pay-as-you-go fee of 0.5% per transaction. We make money only when you do.

== Installation ==

1. Go to **Plugins > Add New**.
2. Search for **WC Solana Pay** plugin.
3. Click on **Install Now** and wait until the plugin is installed successfully.
4. Click on **Activate**. (You can also activate on the **Plugins > Installed Plugins** page).

= Setup Configurations =

1. After activating the plugin, go to **WooCommerce > Settings**. Choose **Payments** tab and select **WC Solana Pay**.
2. Select **Enable WC Solana Pay** checkbox.
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

We currently support payments in SOL, USDT, USDC, PYUSD, EURC and EUROe. Support for more tokens are planned to be added in future releases.

= How much does it cost to use? =

**WC Solana Pay** is free to install on your WordPress website. We charge a 0.5% fee per transaction. You only pay when you receive transactions. We make money only when you do.

== Screenshots ==

1. Express checkout button.
2. Payment popup dialog.
3. Dropdown list of Solana tokens acceptable for payments.
4. Admin settings page.
5. Payment details on the backend Order page.
6. Payment options on the Block Editor page.

== Changelog ==

= 2.9.0 :: 2024-09-14 =
* Support for PayPal USD (PYUSD) for payments.

= 2.8.0 :: 2024-07-19 =
* Validate cart content before processing payment to prevent errors related to lack of stock.

= 2.7.0 :: 2024-05-27 =
* Offload payments validation logics to remote and log errors during payments processing

= 2.6.0 :: 2024-04-16 =
* Simplify tokens rates lookup using remote price feed
* Fix 'network mismatch' and 'dapp not trusted' warnings shown by some wallets

= 2.5.0 :: 2024-03-23 =
* Validate email during checkout to prevent "email already registered" error.

= 2.4.1 :: 2024-03-08 =
* Make gateway title name editable in the Admin settings page.
* Support Solana Mobile Wallet Adapter for improved stability on smartphones.

= 2.4.0 =
* Support for EURC and EUROe tokens for payments.
* Compatibility support for Gutenberg and WooCommerce Block themes.

= 2.3.0 =
* Notification bar added to show transactions status and to notify errors.

= 2.2.3 =
* Plugin renamed for naming consistency with its WordPress slug and directory name.

= 2.2.2 =
* Compatibility with WooCommerce High-Performance Order Storage (HPOS).

= 2.2.1 =
* Initial release to the WordPress plugins repository.

== Upgrade Notice ==

= 2.9.0 =
Upgrade to enable PayPal USD (PYUSD) support for payments.

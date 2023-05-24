<?php
/**
 * Add Solana Tokens as custom WC currencies.
 *
 * @package AZTemi\Solana_Pay_for_WC
 */

namespace AZTemi\Solana_Pay_for_WC;

// die if accessed directly
if ( ! defined( 'WPINC' ) ) { die; }

function get_supported_solana_tokens() {
  // List of supported tokens. First token is the default.
  $supported_tokens = array(
    'USDC' => array(
      'symbol'       => 'USDC',
      'name'         => __( 'Circle USD Coin', 'solana-pay-for-wc' ),
      'devnet'       => '4zMMC9srt5Ri5X14GAgXhaHii3GnPAEERYPJgZJDncDU',
      'mainnet-beta' => 'EPjFWdd5AufqSSqeM2qN1xzybapC8G4wEGGkZwyTDt1v',
    ),
    'USDT' => array(
      'symbol'       => 'USD₮',
      'name'         => __( 'Tether USDt', 'solana-pay-for-wc' ),
      'devnet'       => 'EJwZgeZrdC8TXTQbQBoL6bfuAnFUUy1PVCMB4DYPzVaS', // USDT (Saber Devnet)
      'mainnet-beta' => 'Es9vMFrzaCERmJfrF4H2FYD4KCoNkY11McCe8BenwNYB',
    ),
    'SOL' => array(
      'symbol'       => 'SOL',
      'name'         => __( 'Solana', 'solana-pay-for-wc' ),
      'devnet'       => '', // N/A for SOL
      'mainnet-beta' => '', // N/A for SOL
    ),
  );

  static $tokens;
  // Add suffix to token handles to make them unique
  if ( ! isset( $tokens ) ) {
    $suffix = '_SOLANA';
    foreach( $supported_tokens as $k => $v ) {
      $tokens[ $k . $suffix ] = $v;
    }
  }

  return $tokens;
}

$tokens = get_supported_solana_tokens();

add_filter(
  'woocommerce_currencies',
  function( $currencies ) use ( $tokens ) {
    foreach( $tokens as $k => $v ) {
      $currencies[ $k ] = $v['name'];
    }
    ksort( $currencies, SORT_NATURAL|SORT_FLAG_CASE );
    return $currencies;
  }
);

add_filter(
  'woocommerce_currency_symbol',
  function( $symbol, $currency ) use ( $tokens ) {
    if ( array_key_exists( $currency, $tokens ) ) {
      $symbol = $tokens[ $currency ]['symbol'];
    }
    return $symbol;
  },
  10,
  2
);

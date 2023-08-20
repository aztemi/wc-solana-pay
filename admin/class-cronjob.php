<?php
/**
 * A class for registering and de-registering Cron events.
 *
 * @package AZTemi\WC_Solana_Pay
 */

namespace AZTemi\WC_Solana_Pay;

// die if accessed directly
if ( ! defined( 'WPINC' ) ) {
	die;
}


class Cronjob {

	/**
	 * Hook name of cron event action.
	 *
	 * @var string
	 */
	protected $schedule_hook;


	/**
	 * How often the event should recur.
	 *
	 * @var string
	 */
	protected $recurrence;


	public function __construct( $hook, $recurrence = 'hourly' ) {

		$this->schedule_hook = $hook;
		$this->recurrence = $recurrence;

		// register hooks related to cron job
		$this->register_hooks();

	}


	/**
	 * Register hooks for handling cron events
	 */
	private function register_hooks() {

		// register hooks that will activate & deactivate cron events
		register_activation_hook( PLUGIN_FILE, array( $this, 'register_cron_event' ) );
		register_deactivation_hook( PLUGIN_FILE, array( $this, 'deregister_cron_event' ) );

	}


	/**
	 * Register hourly cron event to update token prices.
	 */
	public function register_cron_event() {

		if ( ! wp_next_scheduled( $this->schedule_hook ) ) {
			wp_schedule_event( time(), $this->recurrence, $this->schedule_hook );
		}

	}


	/**
	 * Deregister and clear any scheduled hook.
	 */
	public function deregister_cron_event() {

		wp_clear_scheduled_hook( $this->schedule_hook );

	}

}

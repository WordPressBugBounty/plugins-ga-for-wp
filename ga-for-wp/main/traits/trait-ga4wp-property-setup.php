<?php
/**
 * GA4WP_Property_Setup — post-auth Measurement Protocol provisioning.
 *
 * Available on both free and premium tiers. Fires once in the background
 * (via the ga4wp_run_property_setup cron hook) after a GA4 property with
 * the analytics.edit scope is connected, and can also be triggered manually
 * from the Status page.
 *
 * Custom dimension provisioning (premium only) lives in a separate trait,
 * GA4WP_Custom_Dimension_Setup — see inc/api/ga4wp-premium-management-api__premium_only.php
 * for the premium implementation, or class-ga4wp-auth.php for the free stub.
 */
if ( ! defined( 'ABSPATH' ) ) die;

trait GA4WP_Property_Setup
{
	/**
	 * WP-Cron handler that runs one-time property setup tasks after auth.
	 *
	 * Fires via the `ga4wp_run_property_setup` cron hook once a GA4
	 * property has been connected. If the granted OAuth scopes include
	 * analytics.edit, provisions the Measurement Protocol secret (unless
	 * already completed) and, on the premium tier, registers the custom
	 * dimensions on the property (a no-op stub on the free tier). Does
	 * nothing if no scopes were granted.
	 */
	public function ga4wp_edit_action_scope()
	{
		$granted_scopes = get_option( 'ga4wp_granted_scopes' );
		if ( ! empty( $granted_scopes ) && is_array( $granted_scopes ) ) {
			foreach ( $granted_scopes as $scope ) {
				if ( stripos( $scope, 'analytics.edit' ) !== false ) {
					$m_process_status = get_option( 'measurement_key_process' );
					if ( $m_process_status !== 'completed' && $m_process_status !== 'completed_with_error' ) {
						$this->ga4wp_get_mesurement_key();
					}
					/* Custom dimensions stay premium-only — ga4wp_create_custom_dimension()
					 * is a no-op stub on the free tier (see class-ga4wp-auth.php). */
					$c_process_status = get_option( 'custom_dimension_process' );
					if ( $c_process_status !== 'completed' && $c_process_status !== 'completed_with_error' ) {
						$this->ga4wp_create_custom_dimension();
					}
				}
			}
		}
	}

	/**
	 * Fetch the existing "GA4WP_secret_Key" Measurement Protocol secret for the connected GA4 stream.
	 *
	 * Looks up the data stream's registered secrets via the Management
	 * API and, if a secret named `GA4WP_secret_Key` is found, saves it to
	 * the `measurement_key` option and marks `measurement_key_process` as
	 * completed. If no matching secret exists (or the API call returns
	 * nothing), delegates to ga4wp_create_mesurement_key() to create one.
	 * Does nothing if no GA4 data-stream account id is available.
	 */
	public function ga4wp_get_mesurement_key()
	{
		$account_id = $this->get_ga_account_id();
		if ( ! empty( $account_id ) && ( stripos( $account_id, 'data' ) !== false ) ) {
			$api       = $this->get_google_management_api();
			$responses = $api->get_measurement_protocall( $account_id );
			if ( ! empty( $responses ) ) {
				$ga4_match = false;
				/* Google's Admin API omits the measurementProtocolSecrets field entirely
				 * when a data stream has no secrets yet (proto3 JSON drops empty repeated
				 * fields) — accessing it directly would throw an "undefined property"
				 * warning that corrupts the AJAX JSON response. */
				$secrets = is_object( $responses ) && isset( $responses->measurementProtocolSecrets )
					? $responses->measurementProtocolSecrets
					: array();
				foreach ( $secrets as $response ) {
					if ( ! empty( $response->displayName ) && $response->displayName == 'GA4WP_secret_Key' ) {
						$ga4_match = true;
						update_option( 'measurement_key', $response->secretValue );
						update_option( 'measurement_key_process', 'completed' );
						delete_option( 'measurement_key_error' );
						break;
					}
				}
				if ( ! $ga4_match ) {
					$this->ga4wp_create_mesurement_key();
				}
			} else {
				$this->ga4wp_create_mesurement_key();
			}
		}
	}

	/**
	 * Create a new Measurement Protocol secret for the connected GA4 stream.
	 *
	 * On success, saves the new secret to the `measurement_key` option and
	 * marks `measurement_key_process` as completed. On failure, advances
	 * `measurement_key_process` to 'retry' on the first attempt or
	 * 'completed_with_error' on a subsequent one, and records the API's
	 * last error message (if available) in `measurement_key_error`. Does
	 * nothing if no GA4 data-stream account id is available.
	 */
	public function ga4wp_create_mesurement_key()
	{
		$account_id = $this->get_ga_account_id();
		if ( ! empty( $account_id ) && ( stripos( $account_id, 'data' ) !== false ) ) {
			$api      = $this->get_google_management_api();
			$response = $api->create_measurement_protocall( $account_id );
			if ( ! empty( $response->secretValue ) ) {
				update_option( 'measurement_key', $response->secretValue );
				update_option( 'measurement_key_process', 'completed' );
				delete_option( 'measurement_key_error' );
			} else {
				$process_status = get_option( 'measurement_key_process' );
				if ( empty( $process_status ) ) {
					update_option( 'measurement_key_process', 'retry' );
				} else {
					update_option( 'measurement_key_process', 'completed_with_error' );
				}
				$err = method_exists( $api, 'get_last_error' ) ? $api->get_last_error() : '';
				if ( $err ) {
					update_option( 'measurement_key_error', $err );
				}
			}
		}
	}
}

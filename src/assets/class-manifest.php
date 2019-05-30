<?php
/**
 * File containing an abstract class for holding Assets Manifest functionality.
 *
 * It is used to provide manifest.json file location used with Webpack to fetch correct file locations.
 *
 * @since   0.6.0 Adding multiple methodes for easier extending.
 * @since   0.1.0
 * @package Eightshift_Libs\Assets
 */

namespace Eightshift_Libs\Assets;

use Eightshift_Libs\Core\Service;

/**
 * Abstract class Manifest class.
 *
 * @since 0.7.0 Added Manifest_Data Interface.
 * @since 0.1.0 Init.
 */
abstract class Manifest implements Service, Manifest_Data {

  /**
   * Global variable name contant.
   *
   * @var string
   *
   * @since 0.6.0
   */
  const GLOBAL_VARIABLE_NAME = 'INF_ASSETS_MANIFEST';

  /**
   * Register all hooks.
   *
   * @return void
   *
   * @since 0.6.0 Init.
   */
  public function register() : void {
    add_action( 'init', [ $this, 'register_global_variable' ] );
  }

  /**
   * Define global variable for assets manifest.
   * Used to cache data insinde global variable so you don't fetch manifest.json file on every call.
   *
   * @return void
   *
   * @since 0.6.0 Init.
   */
  public function register_global_variable() : void {
    \define( $this->get_global_variable_name(), $this->get_raw_data() );
  }

  /**
   * Return full path for specific asset from manifest.json
   * This is used for cache busting assets.
   *
   * @param string $key File name key you want to get from manifest.
   * @return string Full path to asset.
   *
   * @since 0.7.0 Changed to non static method.
   * @since 0.6.0 Init
   */
  public function get_assets_manifest_item( $key = null ) : string {
    if ( ! $key ) {
      return '';
    }

    $data = $this->get_decoded_manifest_data();

    $asset = $data->$key ?? '';
    if ( empty( $asset ) ) {
      return '';
    }
    return $this->get_assets_manifest_output_prefix() . $asset;
  }

  /**
   * Set global variable name to store cached data into.
   *
   * @return string
   *
   * @since 0.7.0 Fetching variable name as static.
   * @since 0.6.0 Init.
   */
  protected function get_global_variable_name() : string {
    return static::GLOBAL_VARIABLE_NAME;
  }

  /**
   * Fetches manifest.json data from get_manifest_url() location, parses and returns as a sanitized array.
   * Generally, you would assign this data to a global variable or some helper that is going to be used in the application to fetch assets data.
   *
   * @throws Exception\Missing_Manifest Throws error if manifest is missing.
   *
   * @return string
   *
   * @since 0.1.0
   */
  protected function get_raw_data() : string {

    $manifest = $this->get_manifest_url();
    if ( ! file_exists( $manifest ) ) {
      $error_message = esc_html__( 'manifest.json is missing. Bundle the theme before using it.', 'eightshift-libs' );
      throw Exception\Missing_Manifest::message( $error_message );
    }

    return implode( ' ', file( $manifest ) );
  }

  /**
   * Provide manifest.json url location.
   * If you are using a plugin or a different manifest location provide location with this method.
   *
   * @return string
   *
   * @since 0.6.0 Changed from abstract to predefined.
   * @since 0.1.0
   */
  protected function get_manifest_url() : string {
    return get_template_directory() . '/skin/public/manifest.json';
  }

  /**
   * Return json_decoded manifest data, now you can call items by object key to get the value.
   *
   * @return object Manifest Object.
   *
   * @since 0.7.0 Changed to non static method.
   * @since 0.6.0 Init
   */
  protected function get_decoded_manifest_data() {
    $data = \json_decode( constant( $this->get_global_variable_name() ) );
    if ( ! $data ) {
      return null;
    }

    return $data;
  }

  /**
   * Retrun string as a assets output prefix.
   * Override this if you are using lib for a plugin.
   *
   * @return string
   *
   * @since 0.6.0
   */
  protected function get_assets_manifest_output_prefix() : string {
    return \home_url( '/' );
  }
}

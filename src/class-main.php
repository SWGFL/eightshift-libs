<?php
/**
 * File containing the main theme class
 *
 * @since 1.0.0
 * @package Eightshift_Libs\Core
 */

namespace Eightshift_Libs\Core;

use Eightshift_Libs\Exception;

/**
 * The main start class.
 *
 * This is used to define admin-specific hooks, and
 * theme-facing site hooks.
 *
 * Also maintains the unique identifier of this theme as well as the current
 * version of the theme.
 */
class Main implements Registrable {

  /**
   * Array of instantiated services.
   *
   * @var Service[]
   */
  private $services = [];
  /**
   * Register the plugin with the WordPress system.
   *
   * The register_service method will call the register() method in every service class,
   * which holds the actions and filters - effectively replacing the need to manually add
   * themn in one place.
   *
   * @throws Exception\Invalid_Service If a service is not valid.
   */
  public function register() : void {

    add_action( 'after_setup_theme', [ $this, 'register_services' ] );

    $this->register_assets_manifest_data();
  }
  /**
   * Register the individual services of this plugin.
   *
   * @throws Exception\Invalid_Service If a service is not valid.
   */
  public function register_services() {
    // Bail early so we don't instantiate services twice.
    if ( ! empty( $this->services ) ) {
      return;
    }
    $classes        = $this->get_service_classes();

    $this->services = array_map(
      [ $this, 'instantiate_service' ],
      $classes
    );
    array_walk(
      $this->services,
      function( Service $service ) {
        $service->register();
      }
    );
  }

  /**
   * Provide menifest json url location.
   *
   * @return string
   */
  protected function get_manifest_url() : string {
    return get_template_directory() . '/skin/public/manifest.json';
  }
  /**
   * Register bundled asset manifest
   *
   * @throws Exception\Missing_Manifest Throws error if manifest is missing.
   * @return void
   */
  public function register_assets_manifest_data() {
    // phpcs:disable
    $response = ( file( $this->get_manifest_url() ) );
    // phpcs:enable
    if ( ! $response ) {
      $error_message = esc_html__( 'manifest.json is missing. Bundle the theme before using it.', 'developer-portal' );
      throw Exception\Missing_Manifest::message( $error_message );
    }
    define( 'INF_ASSETS_MANIFEST', implode( ' ', $response ) );
  }
  /**
   * Instantiate a single service.
   *
   * @param string $class Service class to instantiate.
   *
   * @return Service
   * @throws Exception\Invalid_Service If the service is not valid.
   */
  private function instantiate_service( $class ) {
    if ( ! class_exists( $class ) ) {
      throw Exception\Invalid_Service::from_service( $class );
    }
    $service = new $class();
    if ( ! $service instanceof Service ) {
      throw Exception\Invalid_Service::from_service( $service );
    }
    return $service;
  }
  /**
   * Get the list of services to register.
   *
   * A list of classes which contain hooks.
   *
   * @return array<string> Array of fully qualified class names.
   */
  protected function get_service_classes() {
    return [];
  }
}

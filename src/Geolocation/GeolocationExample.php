<?php

/**
 * Class that adds Geolocation detection.
 *
 * @package EightshiftLibs\Geolocation
 */

declare(strict_types=1);

namespace EightshiftLibs\Geolocation;

use EightshiftLibs\Geolocation\AbstractGeolocation;
use Exception;

/**
 * Class Geolocation
 */
class GeolocationExample extends AbstractGeolocation
{
	/**
	 * Register all the hooks
	 */
	public function register(): void
	{
		\add_filter('init', [$this, 'setLocationCookie']);
	}

	/**
	 * Get geolocation cookie name.
	 *
	 * @return string
	 */
	public function getGeolocationCookieName(): string
	{
		return '%cookie_name%';
	}

	/**
	 * Get geolocation executable phar location.
	 *
	 * @throws Exception If file is missing in provided path.
	 *
	 * @return string
	 */
	public function getGeolocationPharLocation(): string
	{
		$path = __DIR__ . \DIRECTORY_SEPARATOR . 'geoip2.phar';

		if (!\file_exists($path)) {
			// translators: %s will be replaced with the phar location.
			throw new Exception(\sprintf(\esc_html__('Missing Geolocation phar on this location %s', 'eightshift-libs'), $path));
		}

		return $path;
	}

	/**
	 * Get geolocation database location.
	 *
	 * @throws Exception If file is missing in provided path.
	 *
	 * @return string
	 */
	public function getGeolocationDbLocation(): string
	{
		$path = __DIR__ . \DIRECTORY_SEPARATOR . 'GeoLite2-Country.mmdb';

		if (!\file_exists($path)) {
			// translators: %s will be replaced with the database location.
			throw new Exception(\sprintf(\esc_html__('Missing Geolocation database on this location %s', 'eightshift-libs'), $path));
		}

		return $path;
	}
}

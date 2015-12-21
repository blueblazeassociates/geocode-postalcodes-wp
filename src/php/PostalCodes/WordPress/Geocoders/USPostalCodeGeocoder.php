<?php

namespace BlueBlazeAssociates\Geocoding\PostalCodes\WordPress\Geocoders;

use BlueBlazeAssociates\Geocoding\PostalCodes\Providers\GoogleMapsProvider;
use BlueBlazeAssociates\Geocoding\GeocodingException;
use BlueBlazeAssociates\Geocoding\LatLon;

/**
 * @author Ed Gifford
 */
class USPostalCodeGeocoder extends \BlueBlazeAssociates\Geocoding\PostalCodes\Geocoders\USPostalCodeGeocoder {
  /**
   *
   */
  public function __construct() {
    parent::__construct( new GoogleMapsProvider() );
  }

  /**
   * @param string $zipcode
   * @param string $provider
   *
   * @return LatLon
   *
   * @throws GeocodingException
   */
  public function geocode( $postal_code ) {
    // Validate ZIP code.
    if ( false == static::validate_postal_code( $postal_code ) ) {
      throw new GeocodingException( 'ZIP code value is invalid: ' . $postal_code );
    }

    // Generate keys for looking up WordPress transients.
    $transient_key_lat = static::get_transient_key_lat( $zipcode );
    $transient_key_lon = static::get_transient_key_lon( $zipcode );

    // See if this ZIP code has been previously geocoded.
    // For some zipcodes (non-US or error in geocoding), we may be storing an error sentinel value.
    $zipcode_lat = get_transient( $transient_key_lat );
    $zipcode_lon = get_transient( $transient_key_lon );

    // Place transient error sentinel value in local variable for easy comparisons.
    $error_sentinel = static::get_error_sentinel();

    // If a geocode operation previously generated an error and stored this in a transient,
    // throw an exception. We can't continue until the error clears.
    if ( $error_sentinel == $zipcode_lat || $error_sentinel == $zipcode_lon ) {
      throw new GeocodingException( 'A previous geocoding error occurred while processing ZIP code: ' . $zipcode );
    }

    // See if the transient lookup succeeded.
    if ( false !== $zipcode_lat && false !== $zipcode_lon) {
      return new LatLon( $zipcode_lat, $zipcode_lon );
    }

    /*
     * OK, there aren't any stored transient values. A lookup will be required.
     */

    // Look up the desired provider.
    $zipcode_geocode_provider = null;
    if ( GeocodingProviders::GOOGLE_MAPS == $provider ) {
      $zipcode_geocode_provider = new GoogleZIPCodeGeocodeProvider();
    }

    // Make sure the provider instantiation worked.
    if ( is_null( $zipcode_geocode_provider ) || ! ( $zipcode_geocode_provider instanceof AbstractZIPCodeGeocodeProvider ) ) {
      throw new GeocodingException( 'Unknown ZIP Code Geocode provider: ' . $provider );
    }

    $latlon = null;
    try {
      $latlon = $zipcode_geocode_provider->geocode( $zipcode );
    } catch ( \Exception $exception ) {
      // If an error occurred, store the error sentinel in the transients.
      // Cache for one day.
      set_transient( $transient_key_lat, $error_sentinel, DAY_IN_SECONDS );
      set_transient( $transient_key_lon, $error_sentinel, DAY_IN_SECONDS );

      // If the caught exception is not a GeocodingException, wrap it up.
      if ( ! ( $exception instanceof GeocodingException ) ) {
        $exception = new GeocodingException( 'An exception occurred during geocoding.', 0, $exception );
      }
      throw $exception;
    }

    // Cache lat and lon as WordPress transients.
    // Cache for one year.
    set_transient( $transient_key_lat, $latlon->getLat(), YEAR_IN_SECONDS );
    set_transient( $transient_key_lon, $latlon->getLon(), YEAR_IN_SECONDS );

    return $latlon;
  }

  /**
   * @param string $postfix
   *
   * @return string
   */
  private static function get_transient_key_lat( $postfix ) {
    return static::get_transient_key( 'lat__' . $postfix );
  }

  /**
   * @param string $postfix
   *
   * @return string
   */
  private static function get_transient_key_lon( $postfix ) {
    return static::get_transient_key( 'lon__' . $postfix );
  }

  /**
   * @param string $postfix
   *
   * @return string
   */
  private static function get_transient_key( $postfix ) {
    return 'blueblaze__geocode_postalcodes_wp__' . $postfix;
  }

  /**
   * @return string
   */
  private static function get_error_sentinel() {
    return 'error';
  }
}

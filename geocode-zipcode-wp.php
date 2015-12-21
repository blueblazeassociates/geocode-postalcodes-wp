<?php
/*
Plugin Name: Blue Blaze Postal Code Geocoder
Description: Provides a postal code geocoder utility.
Author:      Blue Blaze Associates
Author URI:  http://www.blueblazeassociates.com
Version:     0.1.1
*/

if ( ! class_exists( '\BlueBlazeAssociates\Geocoding\PostalCodes\WordPress\Geocoders\USPostalCodeGeocoder' ) ) {
  require './vendor/autoload.php';
}

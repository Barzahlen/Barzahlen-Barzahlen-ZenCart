# Barzahlen Payment Module (Zen Cart)

## Deprecation Notice
This module is no longer maintained and should not be used for new projects. There may be a newer version with support for our [API v2](https://docs.barzahlen.de/api/v2/) in the future.

## User Manual
DE - https://integration.barzahlen.de/de/shopsysteme/zen-cart/nutzerhandbuch  
EN - https://integration.barzahlen.de/en/shopsystems/zen-cart/user-manual

## Current Version
1.2.1

## Changelog

### 1.2.1 (05.06.2015)
* improved payment selection
* updated to Barzahlen PHP SDK v1.1.8

### 1.2.0 (10.11.2014)
* integrated Barzahlen PHP SDK v1.1.7 (Payment & Cancel)
* automatic payment slip cancellation for cancelled orders
* callback returns 200 (OK) only after successful database update
* improved payment selection

### 1.1.8 (11.02.2014)
* plugin check works in PHP versions lower than 5.2 now
* bugfix for refund callbacks
* updated payment selection layout

### 1.1.7 (17.05.2013)
* automatic plugin version check once a week
* small improvements for notification process

### 1.1.6 (17.04.2013)
* rounding amounts to avoid problems with float
* only showing Barzahlen as payment method to German customers
* using PSR2 coding standard

### 1.1.5 (11.03.2013)
* changed image urls to make use of https
* updated cURL certificate bundle

### 1.1.4 (19.02.2013)
* added automatic iso conversion for payment requests

### 1.1.3 (28.01.2013)
* redesigned payment selection and checkout success page

### 1.1.2 (29.11.2012)
* refactored payment process code
* increased reliability of avoiding total order amount miscalculation

### 1.1.1 (12.11.2012)
* renamed log file folder to match default one form Zen Cart v1.5.1
* updated error loggings file destination

### 1.1.0 (30.10.2012)
* implemented new api geocoding feature to get the closest points of sales
* reduced the maximum amount to 999.99 Euros
* small changes in the language files

### 1.0.0 (26.09.2012)
* initial release

## Support
The Barzahlen Team will happily assist you with any problems or questions. Send us an email to support@barzahlen.de or use the contact form at https://integration.barzahlen.de/en/support.

## Copyright
(c) 2015, Cash Payment Solutions GmbH  
https://www.barzahlen.de

## NOTICE OF LICENSE
This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; version 2 of the License

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program; if not, write to the Free Software Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA

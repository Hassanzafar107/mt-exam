# Property Listings

A custom WordPress plugin to manage and display property listings with filters and DataTables integration.

## Features

- Registers a **Custom Post Type (CPT)** for `properties`.
- Custom meta fields for:
  - Agent
  - Price
  - Location
  - Bedrooms
  - Bathrooms
  - Zip Code
  - Address
  - City
  - State
  - Country
- Auto-fills city, state, and country from **ZIP code** using the [Zippopotam.us API](http://www.zippopotam.us/).
- Frontend integration with **Bootstrap 4** and **jQuery DataTables**.
- Export property listings to **CSV**.
- AJAX-powered **price range slider** to filter properties dynamically.
- Shortcode support to embed property tables anywhere.

---

## Installation

1. Download or clone this repository into your WordPress `wp-content/plugins/` directory.
2. Ensure the folder is named `property-listings`.
3. Activate the plugin from the **WordPress Admin > Plugins** page.

---

## Usage

### Shortcode
Add the following shortcode to any post or page to display the property listings table:

```php
[property_table]

Shortcode accepts attributes
Agent and Bedroomss
[property_table agent="Agent 1" bedrooms="3"]



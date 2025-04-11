# Apartment Availability Calendar

A WordPress plugin that displays apartment availability from Google Calendar, making it easy to show your rental property's availability to potential guests.

## Description

The Apartment Availability Calendar plugin connects to Google Calendar to display when your apartments or rental properties are occupied. It's perfect for vacation rental owners, small hotels, or anyone who needs to display property availability on their WordPress website.

### Key Features

- Connect to Google Calendar API to display real-time availability
- Support for multiple apartments/properties with different colors
- Customizable calendar display with legend
- Responsive design that works on all devices
- Simple shortcode implementation
- Easy setup through WordPress admin panel

## Installation

1. Upload the apartment-availability-calendar folder to the /wp-content/plugins/ directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Configure your Google API credentials in Settings > Apartment Availability
4. Use the shortcode [apartment_availability] on any page or post to display the calendar

## Configuration

### Google API Setup

1. Create a project in the Google Cloud Console
2. Enable the Google Calendar API
3. Create OAuth 2.0 credentials (Client ID and Client Secret)
4. Add your website's domain to the authorized JavaScript origins
5. Add the redirect URI: https://your-domain.com/wp-admin/admin-ajax.php?action=aac_google_oauth_callback
6. Enter your API Key, Client ID, and Client Secret in the plugin settings

### Usage

Simply add the shortcode to any page or post:

```plaintext
[apartment_availability]
```

To display multiple months:

```plaintext
[apartment_availability months="2"]
```

## Customization

The plugin allows you to customize:

- Calendar colors for each apartment
- Apartment names
- Number of months to display
- Legend visibility

## Requirements

- WordPress 5.0 or higher
- PHP 7.2 or higher
- Google Calendar API access

## Support

For support or feature requests, please open an issue on GitHub.

## License

This plugin is licensed under the GPL v2 or later.

WordPress Dashboard Messages
============================

Display Messages on the WP Dashboard. Released in 2014 and rewritten from scratch in 2018.

Features
--------
 - Select colorset and dashicon
 - WP Multisite support for network-wide messages

For specific target groups you can combine this plugin with
[WP Access Areas](http://wordpress.org/plugins/wp-access-areas/).

Installation
------------
### Production (using Github Updater – recommended for Multisite)
 - Install [Andy Fragen's GitHub Updater](https://github.com/afragen/github-updater) first.
 - In WP Admin go to Settings / GitHub Updater / Install Plugin. Enter `mcguffin/acf-wp-objects` as a Plugin-URI.

### Development
cd into your plugin directory
```
git clone git@github.com:mcguffin/acf-wp-objects.git
cd acf-wp-objects
npm install
gulp
```


Features
--------
- Edit and style Dashboard messages
- German translation

Plugin-API
----------

##### Filter `dashboard_messages_color_schemes`

Example:
```
function add_a_nice_color( $colors ) {
    $colors['nice'] = array(
        'label' => 'Urgh!',
        'css' => 'font-family:fantasy',
    );
    return $colors;
}
add_filter('dashboard_messages_color_schemes','add_a_nice_color');
```

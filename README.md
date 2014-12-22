WordPress Dashboard Messages
============================

About
-----
Display Messages on the WP Dashboard. Use this to display announcements when other people
log into your blog. 
For specific target groups you can combine this plugin with 
[WP Access Areas](http://wordpress.org/plugins/wp-access-areas/).

Features
--------
- Edit and style Dashboard messages
- German translation

Plugin-API
----------

##### Filter `dashboardmessages_color_schemes`

Example:
```
function add_a_nice_color( $colors ) {
    $colors['nice'] = array(
        'label' => 'Niiiice!!!',
        'background' => '#ff0000',
        'color' => 'rgba(255,128,0,0.5)',
    );
    return $colors;
}
add_filter('dashboardmessages_color_schemes','add_a_nice_color');
```

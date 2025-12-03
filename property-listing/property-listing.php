<?php
/**
 * Plugin Name: Property Listings
 * Description: Custom Post Type for managing property listings.
 * Version: 1.0
 * Author: Hassan Zafar
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Register Custom Post Type: Properties
 */
function pl_register_properties_cpt() {
    $labels = array(
        'name'               => 'Properties',
        'singular_name'      => 'Property',
        'menu_name'          => 'Property Listings',
        'name_admin_bar'     => 'Property',
        'add_new'            => 'Add New Property',
        'add_new_item'       => 'Add New Property',
        'new_item'           => 'New Property',
        'edit_item'          => 'Edit Property',
        'view_item'          => 'View Property',
        'all_items'          => 'All Properties',
        'search_items'       => 'Search Properties',
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'menu_icon'          => 'dashicons-admin-home',
        'show_in_menu'       => true,
        'menu_position'      => 5,
        'supports'           => array( 'title', 'editor', 'thumbnail' ),
        'has_archive'        => true,
        'rewrite'            => array( 'slug' => 'properties' ),
        'show_in_rest'       => true,
    );

    register_post_type( 'properties', $args );
}
add_action( 'init', 'pl_register_properties_cpt' );

//single property template function
add_filter( 'single_template', 'my_properties_single_template' );
function my_properties_single_template( $single_template ) {
    global $post;

    if ( $post->post_type == 'properties' ) {
        $plugin_template = plugin_dir_path( __FILE__ ) . '/single-properties.php';
        if ( file_exists( $plugin_template ) ) {
            return $plugin_template;
        }
    }
    return $single_template;
}

/**
 * Add Meta Boxes
 */
function pl_add_meta_boxes() {
    add_meta_box(
        'pl_property_details',
        'Property Details',
        'pl_render_property_details',
        'properties',
        'normal',
        'high'
    );
}
add_action( 'add_meta_boxes', 'pl_add_meta_boxes' );

/**
 * Render Meta Box Fields
 */
function pl_render_property_details( $post ) {
    $meta = get_post_meta( $post->ID );

    $fields = array(
        'agent'     => 'Agent',
        'price'     => 'Price',
        'location'  => 'Location',
        'bedrooms'  => 'Bedrooms',
        'bathrooms' => 'Bathrooms',
        'zip'       => 'Zip Code',
        'address'   => 'Address',
        'city'      => 'City',
        'state'     => 'State',
        'country'   => 'Country',
    );

    wp_nonce_field( 'pl_save_property', 'pl_property_nonce' );

    echo '<table class="form-table">';
    foreach ( $fields as $key => $label ) {
        $value = isset( $meta[$key][0] ) ? esc_attr( $meta[$key][0] ) : '';
        echo "<tr>
                <th><label for='{$key}'>{$label}</label></th>
                <td><input type='text' id='{$key}' name='{$key}' value='{$value}' class='regular-text'></td>
              </tr>";
    }
    echo '</table>';

    // Add JS for ZIP code API
    ?>
    <script>
    jQuery(document).ready(function($){
        $('#zip').on('blur', function(){
            var zip = $(this).val();
            if(zip){
                $.getJSON('https://api.zippopotam.us/us/' + zip, function(data){
                    if(data && data.places && data.places.length > 0){
                        let city = data.places[0]['place name'];
                        let state = data.places[0]['state abbreviation'];
                        let country = data.country;
                        $('#city').val(data.places[0]['place name']);
                        $('#state').val(data.places[0]['state abbreviation']);
                        $('#country').val(data.country);
                        $('#address').val(city + ', ' + state + ', ' + country);
                    }
                });
            }
        });
    });
    </script>
    <?php
}

/**
 * Save Meta Box Data
 */
function pl_save_property_details( $post_id ) {
    if ( ! isset( $_POST['pl_property_nonce'] ) || ! wp_verify_nonce( $_POST['pl_property_nonce'], 'pl_save_property' ) ) {
        return;
    }

    $fields = array( 'agent', 'price','location', 'bedrooms', 'bathrooms', 'zip', 'address', 'city', 'state', 'country' );

    foreach ( $fields as $field ) {
        if ( isset( $_POST[$field] ) ) {
            update_post_meta( $post_id, $field, sanitize_text_field( $_POST[$field] ) );
        }
    }
}
add_action( 'save_post', 'pl_save_property_details' );

// Enqueue Bootstrap and DataTables for frontend
function hz_enqueue_frontend_scripts() {
    // Check if on a page where the shortcode is used
    if (is_page() || is_single()) {
		wp_enqueue_style('my-plugin-style', plugin_dir_url(__FILE__) . 'assets/css/styles.css');
        wp_enqueue_style( 'bootstrap-css', 'https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css' );
        wp_enqueue_style( 'datatables-css', 'https://cdn.datatables.net/1.10.21/css/jquery.dataTables.min.css' );
        wp_enqueue_style( 'datatables-buttons-css', 'https://cdn.datatables.net/buttons/1.7.1/css/buttons.dataTables.min.css' );

        wp_enqueue_script( 'jquery' );
        wp_enqueue_script( 'bootstrap-js', 'https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js', array( 'jquery' ), null, true );
        wp_enqueue_script( 'datatables-js', 'https://cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js', array( 'jquery' ), null, true );
        wp_enqueue_script( 'datatables-buttons-js', 'https://cdn.datatables.net/buttons/1.7.1/js/dataTables.buttons.min.js', array( 'jquery' ), null, true );
        wp_enqueue_script( 'jszip-js', 'https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js', array( 'jquery' ), null, true );
        wp_enqueue_script( 'buttons-html5-js', 'https://cdn.datatables.net/buttons/1.7.1/js/buttons.html5.min.js', array( 'jquery' ), null, true );

        // Custom script to initialize DataTable
        wp_add_inline_script( 'datatables-js', '
            jQuery(document).ready(function($) {
                var table = $("#propertiesTable").DataTable({
				pageLength: 2,
                    dom: "Bfrtip",
                    buttons: [
                        {
                            extend: "csvHtml5",
                            title: "Books",
                            text: "Export to CSV"
                        }
                    ]
                });
                // Override default global search
                $.fn.dataTable.ext.search.push(function(settings, data, dataIndex) {
                    var search = table.search().toLowerCase();
                    if (!search) return true; // no search, show all

                    var agent = data[1].toLowerCase();    // Agent column
                    var bedrooms = data[4].toLowerCase(); // Bedrooms column
                    var title = data[0].toLowerCase();    // Title column

                    // Match only if search term is in Agent or Bedrooms
                    return (agent.indexOf(search) !== -1 || bedrooms.indexOf(search) !== -1 || title.indexOf(search) !== -1);
                });

                // Redraw on search
                $("#propertiesTable_filter input").unbind().bind("keyup", function() {
                    table.search(this.value).draw();
                });

                // range slider filter 
                function fetchProperties() {
                    let maxPrice = $("#priceMax").val();
                    $("#priceLabel").text(maxPrice);

                    $.ajax({
                    url: hzAjax.ajaxurl,
                    method: "POST",
                    data: {
                        action: "hz_filter_properties",
                        max_price: maxPrice,
                    },
                    success: function (response) {
                        table.clear().rows.add(response).draw();
                    },
                    });
                }

                // Initial load
                fetchProperties();

                // On slider change
                $("#priceMax").on("input change", fetchProperties);
            });
        ' );
         // Custom frontend script for AJAX filter
         wp_enqueue_script('hz-property-filter', plugin_dir_url(__FILE__) . 'assets/js/property-filter.js', array('jquery'), null, true);

         wp_localize_script('hz-property-filter', 'hzAjax', array(
             'ajaxurl' => admin_url('admin-ajax.php'),
         ));
    }
}
add_action( 'wp_enqueue_scripts', 'hz_enqueue_frontend_scripts' );

// Shortcode to display all books in a DataTable with filters
function hz_property_datatable_shortcode( $atts ) {

    global $wpdb;
    // Get min/max price dynamically
    $min_price = (int) $wpdb->get_var("SELECT MIN(meta_value+0) FROM {$wpdb->postmeta} WHERE meta_key='price'");
    $max_price = (int) $wpdb->get_var("SELECT MAX(meta_value+0) FROM {$wpdb->postmeta} WHERE meta_key='price'");



    // Extract attributes and set defaults
    $atts = shortcode_atts( array(
        'agent' => '',
        'bedrooms' => '',
    ), $atts, 'books_table' );

    // Prepare the query args
    $query_args = array(
        'post_type' => 'properties',
        'posts_per_page' => -1,
    );

    // Add filtering conditions
    $meta_query = array();

    if ( ! empty( $atts['agent'] ) ) {
        $meta_query[] = array(
            'key'     => 'agent',
            'value'   => sanitize_text_field( $atts['agent'] ),
            'compare' => 'LIKE',
        );
    }

    if ( ! empty( $atts['bedrooms'] ) ) {
        $meta_query[] = array(
            'key'     => 'bedrooms',
            'value'   => sanitize_text_field( $atts['bedrooms'] ),
            'compare' => 'LIKE',
        );
    }
    //merge query for OR operation
    if ( ! empty( $meta_query ) ) {
        $query_args['meta_query'] = array_merge( array( 'relation' => 'OR' ), $meta_query );
    }

    // Query books
    $properties = get_posts( $query_args );

    ob_start(); // Start output buffering

    ?>
    <div class="wrap hz-custom-wrapper">
        <h1><?php esc_html_e( 'All Properties', 'properties' ); ?></h1>

        <div class="mb-3">
            <label><strong>Filter by Price (up to):</strong></label><br>
            <input type="range" id="priceMax" min="<?php echo $min_price; ?>" max="<?php echo $max_price; ?>" value="<?php echo $max_price; ?>">
            <p>Max Price: <span id="priceLabel"><?php echo $max_price; ?></span></p>
        </div>

        <table id="propertiesTable" class="table table-striped">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Title', 'properties' ); ?></th>
                    <th><?php esc_html_e( 'Agent', 'properties' ); ?></th>
                    <th><?php esc_html_e( 'Price', 'properties' ); ?></th>
                    <th><?php esc_html_e( 'Location', 'properties' ); ?></th>
                    <th><?php esc_html_e( 'Bedrooms', 'properties' ); ?></th>
                    <th><?php esc_html_e( 'Bathrooms', 'properties' ); ?></th>
                    <th><?php esc_html_e( 'Zip Code', 'properties' ); ?></th>
                    <th><?php esc_html_e( 'Address', 'properties' ); ?></th>
                    <th><?php esc_html_e( 'City', 'properties' ); ?></th>
                    <th><?php esc_html_e( 'State', 'properties' ); ?></th>
                    <th><?php esc_html_e( 'Country', 'properties' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $properties as $property ) : 
                    $title = get_post_meta( $property->ID, 'post_title', true );
                    $agent = get_post_meta( $property->ID, 'agent', true );
                    $price = get_post_meta( $property->ID, 'price', true );
                    $location = get_post_meta( $property->ID, 'location', true );
                    $bedrooms = get_post_meta( $property->ID, 'bedrooms', true );
                    $bathrooms = get_post_meta( $property->ID, 'bathrooms', true );
                    $zip = get_post_meta( $property->ID, 'zip', true );
                    $address = get_post_meta( $property->ID, 'address', true );
                    $city = get_post_meta( $property->ID, 'city', true );
                    $state = get_post_meta( $property->ID, 'state', true );
                    $country = get_post_meta( $property->ID, 'country', true );
                ?>
                    <tr>
                        <td><a href="<?php echo esc_url( get_edit_post_link( $property->ID ) ); ?>"><?php echo esc_html( $property->post_title ); ?></a></td>
                        <td><?php echo esc_html( $agent ); ?></td>
                        <td><?php echo esc_html( $price ); ?></td>
                        <td><?php echo esc_html( $location ); ?></td>
                        <td><?php echo esc_html( $bedrooms ); ?></td>
                        <td><?php echo esc_html( $bathrooms ); ?></td>
                        <td><?php echo esc_html( $zip ); ?></td>
                        <td><?php echo esc_html( $address ); ?></td>
                        <td><?php echo esc_html( $city ); ?></td>
                        <td><?php echo esc_html( $state ); ?></td>
                        <td><?php echo esc_html( $country ); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
    return ob_get_clean(); // Return the buffered output
}
add_shortcode( 'property_table', 'hz_property_datatable_shortcode' );

function hz_filter_properties_ajax() {
    $max_price = intval($_POST['max_price']);

    global $wpdb;
    $min_price = (int) $wpdb->get_var("SELECT MIN(meta_value+0) FROM {$wpdb->postmeta} WHERE meta_key='price'");

    $args = array(
        'post_type' => 'properties',
        'posts_per_page' => -1,
        'meta_query' => array(
            array(
                'key' => 'price',
                'value' => array($min_price, $max_price),
                'type' => 'NUMERIC',
                'compare' => 'BETWEEN'
            )
        )
    );

    $query = new WP_Query($args);
    $data = array();

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $data[] = array(
                '<a href="' . get_permalink() . '">' . get_the_title() . '</a>',
                get_post_meta(get_the_ID(), 'agent', true),
                get_post_meta(get_the_ID(), 'price', true),
                get_post_meta(get_the_ID(), 'location', true),
                get_post_meta(get_the_ID(), 'bedrooms', true),
                get_post_meta(get_the_ID(), 'bathrooms', true),
                get_post_meta(get_the_ID(), 'zip', true),
                get_post_meta(get_the_ID(), 'address', true),
                get_post_meta(get_the_ID(), 'city', true),
                get_post_meta(get_the_ID(), 'state', true),
                get_post_meta(get_the_ID(), 'country', true),
            );
        }
    }
    wp_reset_postdata();

    wp_send_json($data);
}

add_action('wp_ajax_hz_filter_properties', 'hz_filter_properties_ajax');
add_action('wp_ajax_nopriv_hz_filter_properties', 'hz_filter_properties_ajax');

<?php
/**
 * Plugin Name: Book Management
 * Description: Assessment task: A custom plugin to manage books.
 * Version: 1.0
 * Author: Hassaan Zafar
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Register Custom Post Type
function bm_register_book_post_type() {
    $args = array(
        'label'               => __( 'Books', 'book-management' ),
        'description'         => __( 'A custom post type for books.', 'book-management' ),
        'labels'              => array(
            'name'               => __( 'Books', 'book-management' ),
            'singular_name'      => __( 'Book', 'book-management' ),
            'add_new'            => __( 'Add New', 'book-management' ),
            'add_new_item'       => __( 'Add New Book', 'book-management' ),
            'edit_item'          => __( 'Edit Book', 'book-management' ),
            'new_item'           => __( 'New Book', 'book-management' ),
            'view_item'          => __( 'View Book', 'book-management' ),
            'search_items'       => __( 'Search Books', 'book-management' ),
            'not_found'          => __( 'No books found', 'book-management' ),
            'not_found_in_trash' => __( 'No books found in Trash', 'book-management' ),
        ),
        'public'              => true,
        'has_archive'         => true,
        'supports'            => array( 'title', 'editor', 'custom-fields' ),
        'rewrite'             => array( 'slug' => 'books' ),
		'show_in_menu' 		  => true,
    );

    register_post_type( 'books', $args );
}
add_action( 'init', 'bm_register_book_post_type' );

// Add custom fields to the post type
function bm_add_book_meta_boxes() {
    add_meta_box( 'bm_book_details', 'Book Details', 'bm_render_book_meta_box', 'books', 'normal', 'high' );
}
add_action( 'add_meta_boxes', 'bm_add_book_meta_boxes' );

function bm_render_book_meta_box( $post ) {
    // Use nonce for verification
    wp_nonce_field( basename( __FILE__ ), 'bm_book_nonce' );

    $author = get_post_meta( $post->ID, '_bm_author', true );
    $publisher = get_post_meta( $post->ID, '_bm_publisher', true );
    $isbn = get_post_meta( $post->ID, '_bm_isbn', true );
    $published_date = get_post_meta( $post->ID, '_bm_published_date', true );
    $zip = get_post_meta( $post->ID, '_bm_zip', true );
    $country = get_post_meta( $post->ID, '_bm_country', true );
    $state = get_post_meta( $post->ID, '_bm_state', true );

    ?>
    <table class="form-table">
        <tr>
            <th><label for="bm_author">Author</label></th>
            <td><input type="text" name="bm_author" value="<?php echo esc_attr( $author ); ?>" /></td>
        </tr>
        <tr>
            <th><label for="bm_publisher">Publisher</label></th>
            <td><input type="text" name="bm_publisher" value="<?php echo esc_attr( $publisher ); ?>" /></td>
        </tr>
        <tr>
            <th><label for="bm_isbn">ISBN</label></th>
            <td><input type="text" name="bm_isbn" value="<?php echo esc_attr( $isbn ); ?>" /></td>
        </tr>
        <tr>
            <th><label for="bm_published_date">Published Date</label></th>
            <td><input type="date" name="bm_published_date" value="<?php echo esc_attr( $published_date ); ?>" /></td>
        </tr>
         <tr>
            <th><label for="bm_zip">Zip</label></th>
            <td>
                <input type="text" name="bm_zip" id="bm_zip" value="<?php echo esc_attr( $zip ); ?>" onchange="fetchLocationData()" />
                <p class="description">Enter ZIP code and it will auto-fill the country and state.</p>
            </td>
        </tr>
        <tr>
            <th><label for="bm_country">Country</label></th>
            <td><input type="text" name="bm_country" id="bm_country" value="<?php echo esc_attr( $country ); ?>" /></td>
        </tr>
        <tr>
            <th><label for="bm_state">State</label></th>
            <td><input type="text" name="bm_state" id="bm_state" value="<?php echo esc_attr( $state ); ?>" /></td>
        </tr>
    </table>
	<script type="text/javascript">
        function fetchLocationData() {
            var zip = document.getElementById('bm_zip').value;
            
            // Check if the zip is not empty
            if (zip !== '') {
                var url = 'https://api.zippopotam.us/us/' + zip;
                
                fetch(url)
                    .then(response => {
                        if (response.ok) {
                            return response.json();
                        } else {
                            throw new Error('Invalid ZIP Code');
                        }
                    })
                    .then(data => {
                        // Extract country and state
                        var country = data['country'];
                        var state = data['places'][0]['state'];

                        // Set the country and state input fields
                        document.getElementById('bm_country').value = country;
                        document.getElementById('bm_state').value = state;
                    })
                    .catch(error => {
                        console.error('Error fetching location data:', error);
                    });
            }
        }
    </script>
    <?php
}

// Save custom fields
function bm_save_book_meta( $post_id ) {
    // Check nonce
    if ( ! isset( $_POST['bm_book_nonce'] ) || ! wp_verify_nonce( $_POST['bm_book_nonce'], basename( __FILE__ ) ) ) {
        return;
    }

    // Save or update post meta
    $fields = array( 'bm_author', 'bm_publisher', 'bm_isbn', 'bm_published_date', 'bm_zip', 'bm_country', 'bm_state' );
    foreach ( $fields as $field ) {
        if ( isset( $_POST[ $field ] ) ) {
            update_post_meta( $post_id, '_' . $field, sanitize_text_field( $_POST[ $field ] ) );
        }
    }
}
add_action( 'save_post', 'bm_save_book_meta' );

// Create custom admin menu
function bm_create_admin_menu() {
    add_menu_page(
        __( 'Book Management', 'book-management' ),
        __( 'Book Management', 'book-management' ),
        'manage_options',
        'book-management',
        'bm_render_all_books_page',
        'dashicons-book',
        6
    );

    add_submenu_page(
        'book-management',
        __( 'All Books', 'book-management' ),
        __( 'All Books', 'book-management' ),
        'manage_options',
        'bm_all_books',
        'bm_render_all_books_page' // Custom render function for All Books
    );

    add_submenu_page(
        'book-management',
        __( 'Add New Book', 'book-management' ),
        __( 'Add New Book', 'book-management' ),
        'manage_options',
        'post-new.php?post_type=books'
    );
}
add_action( 'admin_menu', 'bm_create_admin_menu' );

// Enqueue Bootstrap and DataTables
function bm_enqueue_scripts() {
    wp_enqueue_style( 'bootstrap-css', 'https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css' );
    wp_enqueue_style( 'bootstrap-icons', 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.5.0/font/bootstrap-icons.css' ); // Add this line
    wp_enqueue_style( 'datatables-css', 'https://cdn.datatables.net/1.10.21/css/jquery.dataTables.min.css' );
    wp_enqueue_style( 'datatables-buttons-css', 'https://cdn.datatables.net/buttons/1.7.1/css/buttons.dataTables.min.css' );

    wp_enqueue_script( 'jquery' );
    wp_enqueue_script( 'bootstrap-js', 'https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js', array( 'jquery' ), null, true );
    wp_enqueue_script( 'datatables-js', 'https://cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js', array( 'jquery' ), null, true );
    wp_enqueue_script( 'datatables-buttons-js', 'https://cdn.datatables.net/buttons/1.7.1/js/dataTables.buttons.min.js', array( 'jquery' ), null, true );
    wp_enqueue_script( 'jszip-js', 'https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js', array( 'jquery' ), null, true );
    wp_enqueue_script( 'buttons-html5-js', 'https://cdn.datatables.net/buttons/1.7.1/js/buttons.html5.min.js', array( 'jquery' ), null, true );

    // Custom script to initialize DataTable with the CSV button only
    wp_add_inline_script( 'datatables-js', '
        jQuery(document).ready(function($) {
            $("#booksTable").DataTable({
				pageLength: 3,
                dom: "Bfrtip",
                buttons: [
                    {
                        extend: "csvHtml5",
                        title: "Books",
                        text: "Export to CSV"
                    }
                ]
            });
        });
    ' );
	// Enqueue custom script for deleting books
    wp_enqueue_script( 'bm-custom-js', plugin_dir_url( __FILE__ ) . 'js/delete.js', array( 'jquery' ), null, true );

    // Localize the script with new data
    wp_localize_script( 'bm-custom-js', 'bm_ajax_object', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'confirm_message' => __( 'Are you sure you want to delete this book?', 'book-management' ),
    ) );
}
add_action( 'admin_enqueue_scripts', 'bm_enqueue_scripts' );


// Render the All Books admin page
function bm_render_all_books_page() {
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'All Books', 'book-management' ); ?></h1>
        <table id="booksTable" class="table table-striped">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Title', 'book-management' ); ?></th>
                    <th><?php esc_html_e( 'Author', 'book-management' ); ?></th>
                    <th><?php esc_html_e( 'Publisher', 'book-management' ); ?></th>
                    <th><?php esc_html_e( 'ISBN', 'book-management' ); ?></th>
                    <th><?php esc_html_e( 'Published Date', 'book-management' ); ?></th>
                    <th><?php esc_html_e( 'Zip', 'book-management' ); ?></th>
                    <th><?php esc_html_e( 'Country', 'book-management' ); ?></th>
                    <th><?php esc_html_e( 'State', 'book-management' ); ?></th>
					<th><?php esc_html_e( 'Edit', 'book-management' ); ?></th>
					<th><?php esc_html_e( 'Delete', 'book-management' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Query books
                $books = get_posts( array( 'post_type' => 'books', 'numberposts' => -1 ) );

                foreach ( $books as $book ) {
                    $author = get_post_meta( $book->ID, '_bm_author', true );
                    $publisher = get_post_meta( $book->ID, '_bm_publisher', true );
                    $isbn = get_post_meta( $book->ID, '_bm_isbn', true );
                    $published_date = get_post_meta( $book->ID, '_bm_published_date', true );
                    $zip = get_post_meta( $book->ID, '_bm_zip', true );
                    $country = get_post_meta( $book->ID, '_bm_country', true );
                    $state = get_post_meta( $book->ID, '_bm_state', true );

                    echo '<tr>';
//                     echo '<td>' . esc_html( $book->post_title ) . '</td>';
                    echo '<td><a href="' . esc_url( get_edit_post_link( $book->ID ) ) . '">' . esc_html( $book->post_title ) . '</a></td>';
                    echo '<td>' . esc_html( $author ) . '</td>';
                    echo '<td>' . esc_html( $publisher ) . '</td>';
                    echo '<td>' . esc_html( $isbn ) . '</td>';
                    echo '<td>' . esc_html( $published_date ) . '</td>';
                    echo '<td>' . esc_html( $zip ) . '</td>';
                    echo '<td>' . esc_html( $country ) . '</td>';
                    echo '<td>' . esc_html( $state ) . '</td>';
					echo '<td><a href="' . esc_url( get_edit_post_link( $book->ID ) ) . '" class="btn btn-secondary" style="background-color: transparent; border-color: blue; color: blue;"><i class="bi bi-pencil-square"></i></a></td>';
                    echo '<td><button type="button" class="btn btn-secondary delete-book" data-id="' . esc_attr( $book->ID ) . '" style="background-color: transparent; border-color: red; color: red;"><i class="bi bi-trash"></i></button></td>';
                    echo '</tr>';
                }
                ?>
            </tbody>
        </table>
    </div>
    <?php
}

// Handle AJAX request to delete a book
function bm_delete_book() {
    // Check if the user has permission
    if (!current_user_can('delete_posts')) {
        wp_send_json_error('You do not have permission to delete this book.');
        wp_die();
    }

    // Check if the book ID is set
    if (isset($_POST['book_id'])) {
        $book_id = intval($_POST['book_id']);
        
        // Delete the post
        if (wp_delete_post($book_id, true)) {
            wp_send_json_success();
        } else {
            wp_send_json_error('Failed to delete the book.');
        }
    } else {
        wp_send_json_error('No book ID provided.');
    }

    wp_die();
}
add_action('wp_ajax_delete_book', 'bm_delete_book');

// Enqueue Bootstrap and DataTables for frontend
function bm_enqueue_frontend_scripts() {
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
                $("#booksTable").DataTable({
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
            });
        ' );
    }
}
add_action( 'wp_enqueue_scripts', 'bm_enqueue_frontend_scripts' );


// Shortcode to display all books in a DataTable with filters
function bm_books_datatable_shortcode( $atts ) {
    // Extract attributes and set defaults
    $atts = shortcode_atts( array(
        'author' => '',
        'publisher' => '',
    ), $atts, 'books_table' );

    // Prepare the query args
    $query_args = array(
        'post_type' => 'books',
        'posts_per_page' => -1,
    );

    // Add filtering conditions
    if ( ! empty( $atts['author'] ) ) {
        $query_args['meta_query'][] = array(
            'key' => '_bm_author',
            'value' => sanitize_text_field( $atts['author'] ),
            'compare' => 'LIKE',
        );
    }

    if ( ! empty( $atts['publisher'] ) ) {
        $query_args['meta_query'][] = array(
            'key' => '_bm_publisher',
            'value' => sanitize_text_field( $atts['publisher'] ),
            'compare' => 'LIKE',
        );
    }

    // Query books
    $books = get_posts( $query_args );

    ob_start(); // Start output buffering
    ?>
    <div class="wrap hz-custom-wrapper">
        <h1><?php esc_html_e( 'All Books', 'book-management' ); ?></h1>
        <table id="booksTable" class="table table-striped">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Title', 'book-management' ); ?></th>
                    <th><?php esc_html_e( 'Author', 'book-management' ); ?></th>
                    <th><?php esc_html_e( 'Publisher', 'book-management' ); ?></th>
                    <th><?php esc_html_e( 'ISBN', 'book-management' ); ?></th>
                    <th><?php esc_html_e( 'Published Date', 'book-management' ); ?></th>
                    <th><?php esc_html_e( 'Zip', 'book-management' ); ?></th>
                    <th><?php esc_html_e( 'Country', 'book-management' ); ?></th>
                    <th><?php esc_html_e( 'State', 'book-management' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $books as $book ) : 
                    $author = get_post_meta( $book->ID, '_bm_author', true );
                    $publisher = get_post_meta( $book->ID, '_bm_publisher', true );
                    $isbn = get_post_meta( $book->ID, '_bm_isbn', true );
                    $published_date = get_post_meta( $book->ID, '_bm_published_date', true );
                    $zip = get_post_meta( $book->ID, '_bm_zip', true );
                    $country = get_post_meta( $book->ID, '_bm_country', true );
                    $state = get_post_meta( $book->ID, '_bm_state', true );
                ?>
                    <tr>
                        <td><a href="<?php echo esc_url( get_edit_post_link( $book->ID ) ); ?>"><?php echo esc_html( $book->post_title ); ?></a></td>
                        <td><?php echo esc_html( $author ); ?></td>
                        <td><?php echo esc_html( $publisher ); ?></td>
                        <td><?php echo esc_html( $isbn ); ?></td>
                        <td><?php echo esc_html( $published_date ); ?></td>
                        <td><?php echo esc_html( $zip ); ?></td>
                        <td><?php echo esc_html( $country ); ?></td>
                        <td><?php echo esc_html( $state ); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<!--     <script>
        jQuery(document).ready(function($) {
            $('#booksTable').DataTable({
                dom: 'Bfrtip',
                buttons: [
                    {
                        extend: 'csvHtml5',
                        title: 'Books',
                        text: 'Export to CSV'
                    }
                ]
            });
        });
    </script> -->
    <?php
    return ob_get_clean(); // Return the buffered output
}
add_shortcode( 'books_table', 'bm_books_datatable_shortcode' );
// usage samples
// To show all books: [books_table]
// To filter by author: [books_table author="Author Name"]
// To filter by publisher: [books_table publisher="Publisher Name"]
// To filter by both: [books_table author="Author Name" publisher="Publisher Name"]

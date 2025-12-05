<?php
/**
 * Plugin Name: Exam Management
 * Plugin URI: https://example.com
 * Description: A WordPress plugin for managing students, subjects, exams, terms, and results. Use shortcode [em_top_students] to show content on homepage.
 * Version: 1.4.0
 * Author: Hassaan Zafar
 * Author URI: https://example.com
 * License: GPL-2.0+
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// Register Custom Post Types
function em_register_cpts() {
    // Students
    register_post_type('em_student', [
        'labels' => ['name'=>'Students','singular_name'=>'Student'],
        'public' => true,
        'supports' => ['title', 'editor'],
        'menu_icon' => 'dashicons-groups',
        'show_in_rest' => true
    ]);

    // Subjects
    register_post_type('em_subject', [
        'labels' => ['name'=>'Subjects','singular_name'=>'Subject'],
        'public' => true,
        'supports' => ['title'],
        'menu_icon' => 'dashicons-book-alt',
        'show_in_rest' => true
    ]);

    // Exams
    register_post_type('em_exam', [
        'labels' => ['name'=>'Exams','singular_name'=>'Exam'],
        'public' => true,
        'supports' => ['title','editor'],
        'menu_icon' => 'dashicons-book',
        'taxonomies' => ['em_term'],
        'show_in_rest' => true
    ]);

    // Results
    register_post_type('em_result', [
        'labels' => ['name'=>'Results','singular_name'=>'Result'],
        'public' => true,
        'supports' => ['title'],
        'menu_icon' => 'dashicons-performance',
        'show_in_rest' => true
    ]);
}
add_action('init', 'em_register_cpts');


// Register Academic Terms Taxonomy
function em_register_taxonomy() {
    register_taxonomy('em_term', 'em_exam', [
        'labels'=>['name'=>'Terms','singular_name'=>'Term'],
        'public'=>true,
        'hierarchical'=>false,
        'show_ui'=>true,
        'show_in_rest'=>true
    ]);
}
add_action('init', 'em_register_taxonomy');


// Term Meta: Start/End Dates
function em_add_term_fields() { ?>
    <div class="form-field">
        <label for="em_term_start_date">Start Date</label>
        <input type="date" name="em_term_start_date">
        <p class="description">Start of this academic term.</p>
    </div>
    <div class="form-field">
        <label for="em_term_end_date">End Date</label>
        <input type="date" name="em_term_end_date">
        <p class="description">End of this academic term.</p>
    </div>
<?php }
add_action('em_term_add_form_fields', 'em_add_term_fields');

function em_edit_term_fields($term) {
    $start = get_term_meta($term->term_id,'em_term_start_date',true);
    $end   = get_term_meta($term->term_id,'em_term_end_date',true);
    ?>
    <tr class="form-field">
        <th><label>Start Date</label></th>
        <td><input type="date" name="em_term_start_date" value="<?php echo esc_attr($start); ?>"></td>
    </tr>
    <tr class="form-field">
        <th><label>End Date</label></th>
        <td><input type="date" name="em_term_end_date" value="<?php echo esc_attr($end); ?>"></td>
    </tr>
    <?php
}
add_action('em_term_edit_form_fields', 'em_edit_term_fields');

function em_save_term_meta($term_id) {
    if(isset($_POST['em_term_start_date'])) update_term_meta($term_id,'em_term_start_date',sanitize_text_field($_POST['em_term_start_date']));
    if(isset($_POST['em_term_end_date'])) update_term_meta($term_id,'em_term_end_date',sanitize_text_field($_POST['em_term_end_date']));
}
add_action('created_em_term','em_save_term_meta');
add_action('edited_em_term','em_save_term_meta');

function em_validate_term_dates($term_id) {
    if(!isset($_POST['em_term_start_date']) || !isset($_POST['em_term_end_date'])) return;
    $start = $_POST['em_term_start_date'];
    $end   = $_POST['em_term_end_date'];
    if(strtotime($end) < strtotime($start)){
        delete_term_meta($term_id,'em_term_start_date');
        delete_term_meta($term_id,'em_term_end_date');
        add_action('admin_notices', function(){
            echo '<div class="notice notice-error is-dismissible"><p><strong>Error:</strong> Term End Date cannot be before Start Date.</p></div>';
        });
    }
}
add_action('created_em_term','em_validate_term_dates',20);
add_action('edited_em_term','em_validate_term_dates',20);


// Exam Meta Box: Start/End Datetime + Subject
function em_add_exam_meta_boxes() {
    add_meta_box('em_exam_details','Exam Details','em_render_exam_meta_box','em_exam','normal','high');
}
add_action('add_meta_boxes','em_add_exam_meta_boxes');

function em_render_exam_meta_box($post){
    wp_nonce_field('em_save_exam_meta','em_exam_meta_nonce');
    $start = get_post_meta($post->ID,'em_exam_start_datetime',true);
    $end   = get_post_meta($post->ID,'em_exam_end_datetime',true);
    $subject_id = get_post_meta($post->ID,'em_exam_subject',true);
    $subjects = get_posts(['post_type'=>'em_subject','numberposts'=>-1,'orderby'=>'title','order'=>'ASC']);
    ?>
    <p><label>Start Date & Time</label><br>
        <input type="datetime-local" name="em_exam_start_datetime" value="<?php echo esc_attr($start); ?>">
    </p>
    <p><label>End Date & Time</label><br>
        <input type="datetime-local" name="em_exam_end_datetime" value="<?php echo esc_attr($end); ?>">
    </p>
    <p><label>Subject</label><br>
        <select name="em_exam_subject">
            <option value="">Select Subject</option>
            <?php foreach($subjects as $sub): ?>
                <option value="<?php echo $sub->ID; ?>" <?php selected($subject_id,$sub->ID); ?>><?php echo esc_html($sub->post_title); ?></option>
            <?php endforeach; ?>
        </select>
    </p>
    <?php
}

function em_save_exam_meta($post_id){
    if(!isset($_POST['em_exam_meta_nonce']) || !wp_verify_nonce($_POST['em_exam_meta_nonce'],'em_save_exam_meta')) return;
    if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if(!current_user_can('edit_post',$post_id)) return;

    if(isset($_POST['em_exam_start_datetime'])) update_post_meta($post_id,'em_exam_start_datetime',sanitize_text_field($_POST['em_exam_start_datetime']));
    if(isset($_POST['em_exam_end_datetime'])) update_post_meta($post_id,'em_exam_end_datetime',sanitize_text_field($_POST['em_exam_end_datetime']));
    if(isset($_POST['em_exam_subject'])) update_post_meta($post_id,'em_exam_subject',intval($_POST['em_exam_subject']));
}
add_action('save_post_em_exam','em_save_exam_meta');

function em_validate_exam_dates($post_id){
    if(!isset($_POST['em_exam_start_datetime']) || !isset($_POST['em_exam_end_datetime'])) return;
    $start = $_POST['em_exam_start_datetime'];
    $end   = $_POST['em_exam_end_datetime'];
    if(strtotime($end) < strtotime($start)){
        delete_post_meta($post_id,'em_exam_start_datetime');
        delete_post_meta($post_id,'em_exam_end_datetime');
        add_action('admin_notices', function(){
            echo '<div class="notice notice-error is-dismissible"><p><strong>Error:</strong> Exam End Date cannot be before Start Date.</p></div>';
        });
    }
}
add_action('save_post_em_exam','em_validate_exam_dates',30);

function em_customize_term_metabox(){
    remove_meta_box('tagsdiv-em_term','em_exam','side');
    add_meta_box('tagsdiv-em_term','Academic Term','post_tags_meta_box','em_exam','side','default',['taxonomy'=>'em_term']);
}
add_action('admin_menu','em_customize_term_metabox');


// Results Meta Box: Enter Marks for Students
function em_add_result_meta_boxes() {
    add_meta_box(
        'em_result_details',
        'Result Details',
        'em_render_result_meta_box',
        'em_result',
        'normal',
        'high');
}
add_action('add_meta_boxes','em_add_result_meta_boxes');

function em_render_result_meta_box($post){
    wp_nonce_field('em_save_result_meta','em_result_meta_nonce');

    // Exams
    $exams = get_posts(['post_type'=>'em_exam','numberposts'=>-1,'orderby'=>'title','order'=>'ASC']);
    $selected_exam = get_post_meta($post->ID,'em_result_exam',true);

    echo '<p><label><strong>Select Exam:</strong></label><br>';
    echo '<select name="em_result_exam"><option value="">Select Exam</option>';
    foreach($exams as $exam){
        echo '<option value="'.$exam->ID.'" '.selected($selected_exam,$exam->ID,false).'>'.esc_html($exam->post_title).'</option>';
    }
    echo '</select></p>';

    if($selected_exam){
        $subject_id = get_post_meta($selected_exam,'em_exam_subject',true);
        if($subject_id){
            $students = get_posts([
                'post_type'=>'em_student',
                'numberposts'=>-1,
                'orderby'=>'title',
                'order'=>'ASC'
            ]);
            $saved_marks = get_post_meta($post->ID,'em_result_marks',true);
            if(!is_array($saved_marks)) $saved_marks = [];

            echo '<h4>Enter Marks for Students (0-100)</h4>';
            echo '<table class="widefat"><thead><tr><th>Student</th><th>Marks</th></tr></thead><tbody>';
            foreach($students as $stu){
                $mark = isset($saved_marks[$stu->ID]) ? $saved_marks[$stu->ID] : '';
                echo '<tr><td>'.esc_html($stu->post_title).'</td>';
                echo '<td><input type="number" min="0" max="100" name="em_result_marks['.$stu->ID.']" value="'.esc_attr($mark).'"></td></tr>';
            }
            echo '</tbody></table>';
        } else {
            echo '<p style="color:red;">Selected exam has no subject assigned.</p>';
        }
    }
}

function em_save_result_meta($post_id){
    if(!isset($_POST['em_result_meta_nonce']) || !wp_verify_nonce($_POST['em_result_meta_nonce'],'em_save_result_meta')) return;
    if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if(!current_user_can('edit_post',$post_id)) return;

    if(isset($_POST['em_result_exam'])) update_post_meta($post_id,'em_result_exam',intval($_POST['em_result_exam']));
    if(isset($_POST['em_result_marks']) && is_array($_POST['em_result_marks'])){
        $marks = array_map('intval',$_POST['em_result_marks']);
        update_post_meta($post_id,'em_result_marks',$marks);
    }
}
add_action('save_post_em_result','em_save_result_meta');

// Add custom "All Exams" admin page
function em_add_custom_all_exams_page() {

    add_submenu_page(
        'edit.php?post_type=em_exam',    // Parent menu (Exams)
        'All Exams (Custom)',            // Page title
        'All Exams (Custom)',            // Menu title
        'manage_options',                // Capability
        'em_all_exams_custom',           // Menu slug
        'em_render_all_exams_custom_page' // Callback
    );
}
add_action('admin_menu', 'em_add_custom_all_exams_page');


// Enqueue Bootstrap and DataTables
function mt_enqueue_scripts() {
    wp_enqueue_style( 'bootstrap-css', 'https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css' );
    wp_enqueue_style( 'bootstrap-icons', 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.5.0/font/bootstrap-icons.css' );
    wp_enqueue_style( 'datatables-css', 'https://cdn.datatables.net/1.10.21/css/jquery.dataTables.min.css' );
    wp_enqueue_style( 'datatables-buttons-css', 'https://cdn.datatables.net/buttons/1.7.1/css/buttons.dataTables.min.css' );

    wp_enqueue_script( 'jquery' );
    wp_enqueue_script( 'bootstrap-js', 'https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js', array( 'jquery' ), null, true );
    
    // Core DataTables
    wp_enqueue_script( 'datatables-js', 'https://cdn.datatables.net/1.10.21/js/jquery.dataTables.min.js', array( 'jquery' ), null, true );

    // Buttons
    wp_enqueue_script( 'datatables-buttons-js', 'https://cdn.datatables.net/buttons/1.7.1/js/dataTables.buttons.min.js', array( 'jquery' ), null, true );
    wp_enqueue_script( 'buttons-html5-js', 'https://cdn.datatables.net/buttons/1.7.1/js/buttons.html5.min.js', array( 'jquery' ), null, true );

    // CSV Dependencies
    wp_enqueue_script( 'jszip-js', 'https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js', array(), null, true );

    // PDF Dependencies → REQUIRED
    wp_enqueue_script( 'pdfmake-js', 'https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js', array(), null, true );
    wp_enqueue_script( 'pdfmake-fonts-js', 'https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js', array('pdfmake-js'), null, true );

    // Initialize DataTable with CSV + PDF
    wp_add_inline_script( 'datatables-js', '
        jQuery(document).ready(function($) {
            $("#examsTable").DataTable({
                pageLength: 3,
                order: [],
                dom: "Bfrtip",
                buttons: [
                    {
                        extend: "csvHtml5",
                        title: "Exams",
                        text: "Export to CSV"
                    },
                    {
                        extend: "pdfHtml5",
                        title: "Exams",
                        text: "Export to PDF",
                        orientation: "landscape",
                        pageSize: "A4"
                    }
                ]
            });
        });
    ' );
}

add_action( 'admin_enqueue_scripts', 'mt_enqueue_scripts' );

// Render content for the custom "All Exams"
function em_render_all_exams_custom_page() {
    echo '<div class="wrap">';
    echo '<h1>All Exams (Custom View)</h1>';

    // Fetch exams
    $exams = get_posts([
        'post_type'   => 'em_exam',
        'numberposts' => -1,
        'orderby'     => 'date',
        'order'       => 'DESC'
    ]);

    if (!$exams) {
        echo '<p>No exams found.</p></div>';
        return;
    }

    /** 
     * SORT ORDER REQUIRED:
     * 0 = Ongoing
     * 1 = Upcoming
     * 2 = Past 
     */
    usort($exams, function($a, $b) {
        $now = current_time('timestamp');

        $a_start = strtotime(get_post_meta($a->ID, 'em_exam_start_datetime', true));
        $a_end   = strtotime(get_post_meta($a->ID, 'em_exam_end_datetime', true));

        $b_start = strtotime(get_post_meta($b->ID, 'em_exam_start_datetime', true));
        $b_end   = strtotime(get_post_meta($b->ID, 'em_exam_end_datetime', true));

        // Status priority calculation
        $status = function($start, $end, $now) {
            if ($start <= $now && $end >= $now) {
                return 0; // ongoing
            }
            if ($start > $now) {
                return 1; // upcoming
            }
            return 2; // past
        };

        $a_status = $status($a_start, $a_end, $now);
        $b_status = $status($b_start, $b_end, $now);

        // Sort by status first
        if ($a_status !== $b_status) {
            return $a_status - $b_status;
        }

        // If status is same → for upcoming/past sort by start date asc
        return $a_start - $b_start;
    });

    ?>

    <div class="wrap">
        <table id="examsTable" class="table table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Exam Title</th>
                    <th>Subject</th>
                    <th>Term</th>
                    <th>Start Datetime</th>
                    <th>End Datetime</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>

                <?php foreach ($exams as $exam):

                    $subject_id = get_post_meta($exam->ID, 'em_exam_subject', true);
                    $subject = $subject_id ? get_the_title($subject_id) : '—';

                    $terms = wp_get_post_terms($exam->ID, 'em_term');
                    $term_name = $terms ? $terms[0]->name : '—';

                    $start = get_post_meta($exam->ID, 'em_exam_start_datetime', true);
                    $end   = get_post_meta($exam->ID, 'em_exam_end_datetime', true);

                    $now = current_time('timestamp');
                    $start_ts = strtotime($start);
                    $end_ts = strtotime($end);

                    if ($start_ts <= $now && $end_ts >= $now) {
                        $status = "<span style='color:green'><b>Ongoing</b></span>";
                    } elseif ($start_ts > $now) {
                        $status = "<span style='color:blue'><b>Upcoming</b></span>";
                    } else {
                        $status = "<span style='color:red'><b>Past</b></span>";
                    }

                ?>

                <tr>
                    <td><?= $exam->ID ?></td>
                    <td><?= esc_html($exam->post_title) ?></td>
                    <td><?= esc_html($subject) ?></td>
                    <td><?= esc_html($term_name) ?></td>
                    <td><?= esc_html($start) ?></td>
                    <td><?= esc_html($end) ?></td>
                    <td><?= $status ?></td>
                    <td>
                        <a href="<?= get_edit_post_link($exam->ID) ?>" class="button">Edit</a>
                        <a href="<?= get_permalink($exam->ID) ?>" class="button" target="_blank">View</a>
                    </td>
                </tr>

                <?php endforeach; ?>

            </tbody>
        </table>
    </div>

    <?php
}

//shortcode for top students
function em_top_students_shortcode() {
    $terms = get_terms([
        'taxonomy'   => 'em_term',
        'orderby'    => 'term_id',
        'order'      => 'DESC', // latest term first
        'hide_empty' => false
    ]);

    if (empty($terms)) return "<p>No academic terms found.</p>";

    $output = "<div class='em-top-students-wrapper'>";

    foreach ($terms as $term) {
        $output .= "<h3>{$term->name}</h3>";

        // Get all exams in this term
        $exam_ids = get_objects_in_term($term->term_id, 'em_term');
        if (empty($exam_ids)) {
            $output .= "<p>No exams in this term.</p>";
            continue;
        }

        // Get all results for these exams
        $results = get_posts([
            'post_type'   => 'em_result',
            'numberposts' => -1,
            'post_status' => 'publish',
            'meta_query'  => [
                [
                    'key'     => 'em_result_exam',
                    'value'   => $exam_ids,
                    'compare' => 'IN'
                ]
            ]
        ]);

        if (empty($results)) {
            $output .= "<p>No results for this term.</p>";
            continue;
        }

        // Sum marks per student
        $student_totals = [];
        foreach ($results as $res) {
            $marks = get_post_meta($res->ID, 'em_result_marks', true);
            if (!is_array($marks)) continue;
            foreach ($marks as $student_id => $mark) {
                if (!isset($student_totals[$student_id])) $student_totals[$student_id] = 0;
                $student_totals[$student_id] += intval($mark);
            }
        }

        // Sort by total marks DESC
        arsort($student_totals);

        $top_students = array_slice($student_totals, 0, 3, true);

        if (empty($top_students)) {
            $output .= "<p>No student results found.</p>";
            continue;
        }

        // Render table
        $output .= "<table class='table table-striped em-top-student-table'>
            <thead><tr><th>Student</th><th>Total Marks</th></tr></thead>
            <tbody>";
        foreach ($top_students as $student_id => $total) {
            $student = get_post($student_id);
            $name = $student ? $student->post_title : '—';
            $output .= "<tr><td>".esc_html($name)."</td><td><b>".esc_html($total)."</b></td></tr>";
        }
        $output .= "</tbody></table>";
    }

    $output .= "</div>";

    return $output;
}
add_shortcode('em_top_students', 'em_top_students_shortcode');

// Add Bulk Import Page under "Results"
function em_add_bulk_import_page() {
    add_submenu_page(
        'edit.php?post_type=em_result', // parent menu
        'Import Results',               // page title
        'Import Results',               // menu title
        'manage_options',               // capability
        'em_import_results',            // menu slug
        'em_render_import_results_page' // callback
    );
}
add_action('admin_menu', 'em_add_bulk_import_page');

function em_render_import_results_page() {
    ?>
    <div class="wrap">
        <h1>Import Exam Results (CSV)</h1>
        <form method="post" enctype="multipart/form-data">
            <?php wp_nonce_field('em_import_results_nonce', 'em_import_results_nonce_field'); ?>
            <table class="form-table">
                <tr>
                    <th><label for="em_results_csv">Select CSV File</label></th>
                    <td><input type="file" name="em_results_csv" accept=".csv" required></td>
                </tr>
            </table>
            <?php submit_button('Import Results'); ?>
        </form>
    </div>
    <?php

    // Process CSV if submitted
    if (
        isset($_POST['em_import_results_nonce_field']) &&
        wp_verify_nonce($_POST['em_import_results_nonce_field'], 'em_import_results_nonce') &&
        !empty($_FILES['em_results_csv']['tmp_name'])
    ) {
        em_process_csv($_FILES['em_results_csv']['tmp_name']);
    }
}


function em_process_csv($file_path) {
    $handle = fopen($file_path, "r");

    if (!$handle) {
        echo "<div class='error'><p>Unable to read uploaded file.</p></div>";
        return;
    }

    $row = 0;

    while (($data = fgetcsv($handle, 5000, ",")) !== FALSE) {
        $row++;
        if ($row == 1) continue; // skip header

        list(
            $student_name,
            $exam_name,
            $start_datetime,
            $end_datetime,
            $subject_name,
            $term_name,
            $marks
        ) = $data;

        // STUDENT (by name)
        $student = get_page_by_title($student_name, OBJECT, 'em_student');
        if ($student) {
            $student_id = $student->ID;
        } else {
            $student_id = wp_insert_post([
                'post_title'  => $student_name,
                'post_type'   => 'em_student',
                'post_status' => 'publish'
            ]);
        }

        // SUBJECT
        $subject = get_page_by_title($subject_name, OBJECT, 'em_subject');
        if ($subject) {
            $subject_id = $subject->ID;
        } else {
            $subject_id = wp_insert_post([
                'post_title'  => $subject_name,
                'post_type'   => 'em_subject',
                'post_status' => 'publish'
            ]);
        }

        // TERM
        $term = get_term_by('name', $term_name, 'em_term');
        if ($term) {
            $term_id = $term->term_id;
        } else {
            $term_id = wp_insert_term($term_name, 'em_term');
            $term_id = is_wp_error($term_id) ? 0 : $term_id['term_id'];
        }

        // EXAM
        $exam = get_page_by_title($exam_name, OBJECT, 'em_exam');
        if ($exam) {
            $exam_id = $exam->ID;
        } else {
            $exam_id = wp_insert_post([
                'post_title'  => $exam_name,
                'post_type'   => 'em_exam',
                'post_status' => 'publish'
            ]);
        }

        // Map start/end datetime and subject
        $start_datetime_formatted = date('Y-m-d H:i:s', strtotime($start_datetime));
        $end_datetime_formatted   = date('Y-m-d H:i:s', strtotime($end_datetime));

        update_post_meta($exam_id, 'em_exam_start_datetime', $start_datetime_formatted);
        update_post_meta($exam_id, 'em_exam_end_datetime', $end_datetime_formatted);
        update_post_meta($exam_id, 'em_exam_subject', $subject_id);

        if ($term_id) wp_set_post_terms($exam_id, [$term_id], 'em_term');

        // RESULT (using em_result meta format)
        $existing_result = new WP_Query([
            'post_type' => 'em_result',
            'meta_query' => [
                [
                    'key'   => 'em_result_exam',
                    'value' => $exam_id
                ]
            ],
            'posts_per_page' => -1
        ]);

        // Check if result exists for this exam, otherwise create
        if ($existing_result->have_posts()) {
            // Update marks array for existing result
            $result_post = $existing_result->posts[0];
            $marks_array = get_post_meta($result_post->ID, 'em_result_marks', true);
            if (!is_array($marks_array)) $marks_array = [];
            $marks_array[$student_id] = intval($marks);
            update_post_meta($result_post->ID, 'em_result_marks', $marks_array);
        } else {
            // Create new result post
            wp_insert_post([
                'post_title'  => $student_name . " - " . $exam_name,
                'post_type'   => 'em_result',
                'post_status' => 'publish',
                'meta_input'  => [
                    'em_result_exam'  => $exam_id,
                    'em_result_marks' => [$student_id => intval($marks)]
                ]
            ]);
        }
    }

    fclose($handle);
    echo "<div class='updated'><p>CSV import completed successfully.</p></div>";
}

// Add Admin Menu
add_action('admin_menu', function() {
    add_menu_page(
        'Student Reports',           // Page title
        'Student Reports',           // Menu title
        'manage_options',            // Capability
        'em_student_reports',        // Menu slug
        'em_render_student_reports', // Callback
        'dashicons-chart-bar',       // Icon
        25                           // Position
    );
});

// Render Student Reports Page
function em_render_student_reports() {
    global $wpdb;

    echo '<div class="wrap"><h1>Student Reports</h1>';

    // Get all students
    $students = get_posts([
        'post_type' => 'em_student',
        'numberposts' => -1,
        'orderby' => 'title',
        'order' => 'ASC'
    ]);

    // Get all terms
    $terms = get_terms([
        'taxonomy' => 'em_term',
        'orderby' => 'term_id',
        'order' => 'ASC',
        'hide_empty' => false
    ]);

    if (empty($students) || empty($terms)) {
        echo '<p>No students or terms found.</p></div>';
        return;
    }

    // Table header
    echo '<button id="exportPDF" class="button button-primary" style="margin-bottom:10px;">Export as PDF</button>';
    echo '<table id="studentReportTable" class="widefat striped">';
    echo '<thead><tr><th>Student</th>';

    foreach ($terms as $term) {
        echo '<th>' . esc_html($term->name) . '</th>';
    }

    echo '<th>Average Marks</th></tr></thead><tbody>';

    foreach ($students as $student) {
        echo '<tr>';
        echo '<td>' . esc_html($student->post_title) . '</td>';

        $total_marks_all_terms = 0;
        $term_count = 0;

        foreach ($terms as $term) {
            // Get exams for this term
            $exams = get_posts([
                'post_type' => 'em_exam',
                'numberposts' => -1,
                'tax_query' => [
                    [
                        'taxonomy' => 'em_term',
                        'field' => 'term_id',
                        'terms' => $term->term_id
                    ]
                ]
            ]);

            $marks_in_term = 0;

            foreach ($exams as $exam) {
                $results = get_posts([
                    'post_type' => 'em_result',
                    'numberposts' => -1,
                    'meta_query' => [
                        [
                            'key' => 'em_result_exam',
                            'value' => $exam->ID
                        ]
                    ]
                ]);

                foreach ($results as $result) {
                    $marks_array = get_post_meta($result->ID, 'em_result_marks', true);
                    if (is_array($marks_array) && isset($marks_array[$student->ID])) {
                        $marks_in_term += intval($marks_array[$student->ID]);
                    }
                }
            }

            echo '<td>' . esc_html($marks_in_term) . '</td>';

            $total_marks_all_terms += $marks_in_term;
            $term_count++;
        }

        // Average marks
        $average = $term_count ? round($total_marks_all_terms / $term_count, 2) : 0;
        echo '<td><b>' . esc_html($average) . '</b></td>';

        echo '</tr>';
    }

    echo '</tbody></table></div>';

    //  Enqueue jsPDF for PDF export
    ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script>
        document.getElementById('exportPDF').addEventListener('click', function(){
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF('l', 'pt', 'a4');
            doc.text("Student Report", 40, 40);

            // Collect table data
            const table = document.getElementById('studentReportTable');
            const rows = Array.from(table.querySelectorAll('tr'));
            const data = rows.map(r => Array.from(r.querySelectorAll('th, td')).map(c => c.innerText));

            // AutoTable plugin (optional) for proper table formatting
            if (doc.autoTable) {
                doc.autoTable({ head: [data[0]], body: data.slice(1), startY: 60 });
            } else {
                // fallback: simple text output
                let y = 60;
                data.forEach(row => {
                    doc.text(row.join(' | '), 40, y);
                    y += 20;
                });
            }

            doc.save("student_report.pdf");
        });
    </script>
    <?php
}




<?php
/**
 * Sage includes
 *
 * The $sage_includes array determines the code library included in your theme.
 * Add or remove files to the array as needed. Supports child theme overrides.
 *
 * Please note that missing files will produce a fatal error.
 *
 * @link https://github.com/roots/sage/pull/1042
 */
$sage_includes = [
  'lib/utils.php',                 // Utility functions
  'lib/init.php',                  // Initial theme setup and constants
  'lib/wrapper.php',               // Theme wrapper class
  'lib/conditional-tag-check.php', // ConditionalTagCheck class
  'lib/config.php',                // Configuration
  'lib/assets.php',                // Scripts and stylesheets
  'lib/titles.php',                // Page titles
  'lib/nav.php',                   // Custom nav modifications
  'lib/gallery.php',               // Custom [gallery] modifications
  'lib/extras.php',                // Custom functions
];

foreach ($sage_includes as $file) {
  if (!$filepath = locate_template($file)) {
    trigger_error(sprintf(__('Error locating %s for inclusion', 'sage'), $file), E_USER_ERROR);
  }

  require_once $filepath;
}
unset($file, $filepath);

add_filter('single_template', create_function('$t', 'foreach( (array) get_the_category() as $cat ) { if ( file_exists(TEMPLATEPATH . "/single-{$cat->slug}.php") ) return TEMPLATEPATH . "/single-{$cat->slug}.php"; } return $t;' ));

add_filter('body_class','add_category_to_single');
function add_category_to_single($classes) {
  if (is_single() ) {
    global $post;
    foreach((get_the_category($post->ID)) as $category) {
      // add category slug to the $classes array
      $classes[] = $category->category_nicename;
    }
  }
  // return the $classes array
  return $classes;
}

add_action('wp', 'redirect_visitors');

function redirect_visitors() {
  if (!is_user_logged_in() && is_page('investors')) {
    wp_redirect(wp_login_url(get_permalink(get_page_by_path('investors'))));
    exit;
  }
}

add_action("login_head", "my_login_head");
function my_login_head() {
  echo "
  <style>
  body.login #login h1 a {
    background: url('".get_bloginfo('template_url')."/dist/images/logo2.png') no-repeat scroll center top transparent;
    height: 180px;
    width: 256px;
  }
  </style>
  ";
}

add_action('wp_ajax_my_action', 'my_action_callback');
add_action('wp_ajax_nopriv_my_action', 'my_action_callback');

function my_action_callback() {
  $category = $_POST['category'];
  $year = $_POST['year'];
  $perpage = $_POST['perpage'];
  echo json_encode(get_user_files($category, $year, $perpage));
  die();
}

function human_filesize($bytes, $decimals = 2) {
  $size = array('B','kB','MB','GB','TB','PB','EB','ZB','YB');
  $factor = floor((strlen($bytes) - 1) / 3);
  return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . ' ' . @$size[$factor];
}

function get_paging_info($tot_rows,$pp,$curr_page)
{
  $pages = ceil($tot_rows / $pp); // calc pages

  $data = array(); // start out array
  $data['si']        = ($curr_page * $pp) - $pp; // what row to start at
  $data['pages']     = $pages;                   // add the pages
  $data['curr_page'] = $curr_page;               // Whats the current page

  return $data;
}

function file_cats() {
  global $wpdb;
  return $wpdb->get_results(
    'SELECT DISTINCT cat_name
    FROM wp_wpfb_cats
    JOIN wp_wpfb_files ON wp_wpfb_files.file_category = wp_wpfb_cats.cat_id
    WHERE wp_wpfb_cats.cat_parent <> 0
    ORDER BY cat_name ASC',
  OBJECT);
}

function file_years() {
  global $wpdb;
  return $wpdb->get_results(
    'SELECT DISTINCT YEAR(file_date) AS year
    FROM wp_wpfb_files
    ORDER BY year DESC',
  OBJECT);
}

function get_user_files($category = 0, $year = 0, $perpage = 10, $curr_page = 1) {

  global $wpdb;

  // Get the current users username
  global $current_user;
  get_currentuserinfo();

  // Should we get files by a category
  $category_join = '';
  if($category) {
    $category_join = "JOIN wp_wpfb_cats ON wp_wpfb_cats.cat_id = wp_wpfb_files.file_category AND wp_wpfb_cats.cat_name = '$category'";
  }

  // Should we get files by a specific year
  $year_where = '';
  if($year) {
    $year_where = "AND YEAR(wp_wpfb_files.file_date) = '$year'";
  }

  // Get the current users roles
  $roles_where = '';
  foreach($current_user->roles as $role ) {
    $roles_where .= "OR find_in_set('$role', REPLACE(file_user_roles,'|',',')) <> 0";
  }

  // Select all of the files that meet the following criteria:
  // 1. Everyone is allowed to view the file
  // 2. The current user is explicitly allowed to view the file
  // 3. The user is attached to a role that is allowed to view the file
  $files = $wpdb->get_results(
    "SELECT
      wp_wpfb_files.file_name,
      wp_wpfb_files.file_size,
      wp_wpfb_files.file_path,
      wp_wpfb_files.file_date,
      wp_wpfb_files.file_path
    FROM wp_wpfb_files
    $category_join
    WHERE (find_in_set('_u_$current_user->user_login', REPLACE(file_user_roles,'|',',')) <> 0 OR file_user_roles = '' $roles_where)
    $year_where
    ORDER BY
      wp_wpfb_files.file_path ASC,
      wp_wpfb_files.file_date ASC", 
  OBJECT);

  //echo('<pre>'.$wpdb->last_query.'</pre>');

  $sorted_files = [];

  foreach ($files as $file) {

    // Get the Categories
    $categories = explode('/', $file->file_path);
    $parent_folder_name = count($categories) === 1 ? 'uncategorized' : $categories[0];

    $sorted_files[$parent_folder_name]['files'][] = $file;

    // Get the total file size of all files
    $sorted_files[$parent_folder_name]['size'] =
      array_key_exists('size', $sorted_files[$parent_folder_name]) ?
      $sorted_files[$parent_folder_name]['size'] + intval($file->file_size) :
      intval($file->file_size);

    // Get the last time this project was modified
    $sorted_files[$parent_folder_name]['modified'] =
      array_key_exists('modified', $sorted_files[$parent_folder_name]) ?
      ($sorted_files[$parent_folder_name]['modified'] > $file->file_date ? $sorted_files[$parent_folder_name]['modified'] : $file->file_date) :
      $file->file_date;
  }

  $result = [
    'total' => count($sorted_files),
    'files' => array_slice($sorted_files, ($curr_page - 1) * $perpage, $perpage, true)
  ];

  return $result;
}

function searchfilter($query) {

  if($query->is_search && !is_admin()) {
      $query->set('post_type', array('post','page'));
  }

  return $query;
}

add_filter('pre_get_posts','searchfilter');

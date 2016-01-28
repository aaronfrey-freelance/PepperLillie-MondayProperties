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
  $meta_size = [];
  $meta_modified = [];

  foreach ($files as $file) {

    // Get the Categories
    $categories = explode('/', $file->file_path);

    $parent_folder_name = count($categories) === 1 ? '-99' : $categories[0];
    $parent_subfolder_name = count($categories) >= 3 ? $categories[1] : false;

    if($parent_subfolder_name !== false) {

      $sorted_files[$parent_folder_name][$parent_subfolder_name]['files'][] = $file;

      // Get the total file size of all files
      $sorted_files[$parent_folder_name][$parent_subfolder_name]['size'] =
        array_key_exists('size', $sorted_files[$parent_folder_name][$parent_subfolder_name]) ?
        $sorted_files[$parent_folder_name][$parent_subfolder_name]['size'] + intval($file->file_size) :
        intval($file->file_size);

      // Get the last time this project was modified
      $sorted_files[$parent_folder_name][$parent_subfolder_name]['modified'] =
        array_key_exists('modified', $sorted_files[$parent_folder_name][$parent_subfolder_name]) ?
        ($sorted_files[$parent_folder_name][$parent_subfolder_name]['modified'] > $file->file_date ? $sorted_files[$parent_folder_name][$parent_subfolder_name]['modified'] : $file->file_date) :
        $file->file_date;
    } else {
      $sorted_files[$parent_folder_name][] = $file;
    }

    $meta_size[$parent_folder_name] = array_key_exists($parent_folder_name, $meta_size) ?
      $meta_size[$parent_folder_name] + intval($file->file_size) :
      intval($file->file_size);

    $meta_modified[$parent_folder_name] = array_key_exists($parent_folder_name, $meta_modified) ?
      ($meta_modified[$parent_folder_name] > $file->file_date ? $meta_modified[$parent_folder_name] : $file->file_date) :
      $file->file_date;

  }

  $result = [
    'total' => count($sorted_files),
    'files' => array_slice($sorted_files, ($curr_page - 1) * $perpage, $perpage, true),
    'meta_size' => $meta_size,
    'meta_modified' => $meta_modified
  ];

  return $result;
}

/**
 * Redirect all search requests to the home page
 */
function fb_filter_query($query, $error = true) {

  if(!is_admin() && is_search()) {
    $query->is_search = false;
    $query->query_vars['s'] = false;
    $query->query['s'] = false;

    // to error
    if ($error == true)
      wp_redirect(home_url());
      exit;
    }
  }

add_action('parse_query', 'fb_filter_query');
add_filter('get_search_form', create_function('$a', "return null;"));

/**
 * Remove access to the author pages
 */
function remove_author_pages_page() {
  if(is_author()) {
    wp_redirect(home_url());
    exit;
  }
}

function remove_author_pages_link( $content ) {
  return get_option('home');
}

add_action( 'template_redirect', 'remove_author_pages_page' );
add_filter( 'author_link', 'remove_author_pages_link' );

add_action('admin_menu', 'example_admin_menu');
 
function example_admin_menu() {
    global $submenu;
    $url = site_url('download-report');
    $submenu['tools.php'][] = array('Download File Report', 'manage_options', $url);
}

function print_user_file_report() {

  global $wpdb;
  $file_report = [];
  $current_user_login = '';

  $files = $wpdb->get_results(
    "SELECT wp_users.user_login, wp_users.user_email, wp_wpfb_files.file_path
    FROM wp_users
    JOIN wp_wpfb_files ON wp_wpfb_files.file_user_roles LIKE CONCAT('%', wp_users.user_login, '|', '%')
    ORDER BY user_login, wp_wpfb_files.file_path");

  foreach($files as $file) {
    
    if($current_user_login !== $file->user_login) {
      $file_report[$file->user_login] = [
        'email' => $file->user_email,
        'files' => []
      ];
    }

    $file_path_parts = explode('/', $file->file_path);
    $file_report[$file->user_login]['files'][$file_path_parts[0]][$file_path_parts[1]][] = $file_path_parts[2];
    $current_user_login = $file->user_login;
  }

  /** Error reporting */
  error_reporting(E_ALL);
  ini_set('display_errors', TRUE);
  ini_set('display_startup_errors', TRUE);
  date_default_timezone_set('Europe/London');

  if (PHP_SAPI == 'cli')
    die('This example should only be run from a Web Browser');

  /** Include PHPExcel */
  require_once dirname(__FILE__) . '/Classes/PHPExcel.php';

  PHPExcel_Shared_Font::setAutoSizeMethod(PHPExcel_Shared_Font::AUTOSIZE_METHOD_EXACT);

  // Create new PHPExcel object
  $objPHPExcel = new PHPExcel();

  // Add some data
  $objPHPExcel->setActiveSheetIndex(0)
              ->setCellValue('A1', 'User Login')
              ->setCellValue('B1', 'User Email')
              ->setCellValue('C1', 'Parent Category')
              ->setCellValue('D1', 'Sub Category')
              ->setCellValue('E1', 'File Name');

  $current_row = 2;
  foreach($file_report as $user_login => $user_files) {
    $objPHPExcel->getActiveSheet()->setCellValue('A' . $current_row, $user_login);
    $objPHPExcel->getActiveSheet()->setCellValue('B' . $current_row, $user_files['email']);

    foreach($user_files['files'] as $parent_cat_name => $parent_cats) {
      $objPHPExcel->getActiveSheet()->setCellValue('C' . $current_row, $parent_cat_name);
      foreach($parent_cats as $sub_cat_name => $sub_cats) {
        $objPHPExcel->getActiveSheet()->setCellValue('D' . $current_row, $sub_cat_name);
        foreach($sub_cats as $file) {
          $objPHPExcel->getActiveSheet()->setCellValue('E' . $current_row, $file);
          $current_row = $current_row + 1;
        }
      }
    }
  }

  // Autosize column widths
  $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setAutoSize(true);
  $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setAutoSize(true);
  $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setAutoSize(true);
  $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setAutoSize(true);
  $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setAutoSize(true);

  // Rename worksheet
  $objPHPExcel->getActiveSheet()->setTitle('User File Permissions');

  // Set active sheet index to the first sheet, so Excel opens this as the first sheet
  $objPHPExcel->setActiveSheetIndex(0);

  // Redirect output to a client’s web browser (Excel5)
  header('Content-Type: application/vnd.ms-excel');
  header('Content-Disposition: attachment;filename="user-file-report.xls"');
  header('Cache-Control: max-age=0');
  // If you're serving to IE 9, then the following may be needed
  header('Cache-Control: max-age=1');

  // If you're serving to IE over SSL, then the following may be needed
  header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
  header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
  header ('Cache-Control: cache, must-revalidate'); // HTTP/1.1
  header ('Pragma: public'); // HTTP/1.0

  $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
  $objWriter->save('php://output');

  die();
}
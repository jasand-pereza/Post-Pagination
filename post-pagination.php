<?php 

/*
 * Plugin Name: Post Pagination
 * Plugin URI : https://github.com/jasand-pereza/post-pagination
 * Description: Previous and next links gives a Wordpress user the ability to navigate within the post view of the admin ui.
 * Version 1.0
 * Author: Jasand Pereza
 * Author URI: http://www.jasandpereza.com
 * Licencse: GPLv2
*/



add_action('in_admin_footer', 'PostPagination::load_scripts');
add_action('in_admin_footer', 'PostPagination::get_pagination');
add_filter('manage_posts_columns' , 'PostPagination::add_work_columns');

class PostPagination {
  
  /**
   * load dependencies  
  */
  public static function load_scripts() {
    wp_register_script( 'post-pagination', sprintf('%s/post-pagination/post-pagination.js', WP_PLUGIN_URL),'','', false );
    wp_enqueue_script('post-pagination');
    
    wp_register_style('post-pagination', sprintf('%s/post-pagination/post-pagination.css', WP_PLUGIN_URL),'','', false );
    wp_enqueue_style('post-pagination');
  }
  

  /**
    * get pagination
    * @return string HTML 
   */
  public static function get_pagination() {
    
    global $wpdb;
    $next = null;
    $prev = null;
    $current = (isset($_GET['post'])) ? $_GET['post'] : null;
    $current = (int) $current;
    $filter_orderby = (!empty($_GET['orderby'])) ? $_GET['orderby'] : 'post_date';
    $filter_order = (!empty($_GET['order'])) ? $_GET['order'] : 'DESC';
    $filter_order = strtoupper($filter_order);
    $meta_present = false;
    
    // stop if post id is not present
    if($current == null) return;
    
    //default options
    $next_label = 'Next Post';
    $next_prev = 'Prev Post'; 
    
    // custom options
    $options = self::get_options();
    $labels = $options['labels'];
    $active_elements = $options['active_elements'];
    $next_label = (array_key_exists('next_label', $labels)) ? $labels['next_label'] : $next_label;
    $prev_label = (array_key_exists('prev_label', $labels)) ? $labels['prev_label'] : $prev_label;
    $filter_select = (in_array('filter_select', $active_elements)) ? true : false;
    $page_info = (in_array('page_info', $active_elements)) ? true : false;
        
    $acceptable_non_meta_fitlers = array('title', 'author', 'post_date');
    
    // get post type
    $app_post_type = get_post_type($current);
    $app_post_type = trim($app_post_type);
    
    // check if orderby filter is a meta data filter
    if(!in_array($filter_orderby, $acceptable_non_meta_fitlers)):
     $all = self::filter_by_post_meta($filter_orderby, $filter_order, $wpdb, $app_post_type);
     $all_array = $all['all_array'];
     $all_ids = $all['all_ids'];
     $meta_present = true;
    endif;
    
    // if $all_array is already an array, it's because orderby is a post_meta filter and was set above
    if(!is_array($all_array) || $all_array == null):    
      // do the query    
      $all_ids = $wpdb->get_results("
        SELECT ID, $filter_orderby 
        FROM  $wpdb->posts
        WHERE `post_type` =  '$app_post_type'
        AND `post_status` != 'auto-draft'
        order by $filter_orderby $filter_order", ARRAY_A
      );

      // create an array of only id values with the key being numeric 
      $all_array = array();    
      foreach($all_ids as $item):
        $all_array[] = $item['ID'];
      endforeach;
    endif;
    
    // get current item
    $current_item = array_search($current, $all_array);
    
    // get the index position of the current post
    $cindex = 0;
    foreach($all_array as $item):
      if($item == $current) break;
      $cindex++;
    endforeach;
    
    $current_position = $cindex;
   
    // assign prev and next IDs
    if(!$meta_present):
      $prev = $all_ids[($current_item - 1)]['ID'];
      $next = $all_ids[($current_item + 1)]['ID'];
    else:
      $prev = $all_array[($current_item - 1)];
      $next = $all_array[($current_item + 1)];
    endif;
    
    // get length of all posts in array
    $col_length = count($all_ids)-1;
  
    // if there are no more posts in either direction disable it
    if($current_position >= $col_length) $disable_next = true;
    if($current_position <= 0) $disable_prev = true;
  
    // html
    ob_start(); ?>
      <div id="post-pagination">
        <ul>
          <?php if($filter_select): ?>
            <li>
              <select data-default-selection="<?php echo $filter_orderby; ?>" id="pagination-filter"><?php echo self::get_choices(); ?></select>
            </li>
            <li>|</li>
          <?php endif; ?>
          <li>
            <a href="post.php?post=<?php echo $prev; ?>&amp;action=edit<?php echo '&orderby=' . $filter_orderby . '&order=' . $filter_order; ?>" class="pagination_prev <?php echo ($disable_prev) ? 'disable' : '' ?>"><?php echo $prev_label; ?></a>
          </li>
          <li>|</li>
          <li>
            <a href="post.php?post=<?php echo $next; ?>&amp;action=edit<?php echo '&orderby=' . $filter_orderby . '&order=' . $filter_order; ?>" class="pagination_next <?php echo ($disable_next) ? 'disable' : '' ?>"><?php echo $next_label; ?></a>
          </li>
          <?php if($page_info): ?>
            <li>|</li>
            <li>
              <?php echo $current_position  . ' of ' . $col_length . '.';  ?>
            </li>
          <?php endif; ?>
        <ul>
      </div>
    <?php echo ob_get_clean();
  }
  
  /**
    * If filters exist, call a javascript function for enabling or disabling links 
    * @param array $columns  
    * @return array HTML
   */
  public static function add_work_columns($columns) {
    if(empty($_GET['orderby']) || empty($_GET['order'])) return $columns;
    $filter = array($_GET['order'], $_GET['orderby']);
    $filter = json_encode($filter);
    echo '<script type="text/javascript">jQuery(function(){ post_pagination_change_filter(' . $filter . '); });</script>';
    return $columns;
  }
  
  
  /**
    * Get post meta into an array with post IDs
    * @param string $filter_by_post_meta
    * @param strin $filter_order 
    * @param object $wpdb 
    * @param string $app_post_type
    * @return array 
   */
  public static function filter_by_post_meta($filter_orderby, $filter_order, $wpdb, $app_post_type) {

    $all_ids = $wpdb->get_results("
      SELECT ID
      FROM  $wpdb->posts
      WHERE `post_type` =  '$app_post_type'
      AND `post_status` != 'auto-draft'", ARRAY_A
    );
    
    // get post meta data for each post
    $meta_array = array();
    foreach($all_ids as $result):
      $metas = get_post_meta($result['ID']);
      foreach($metas as $key => $meta_value):
        if($filter_orderby == $key):          
          $meta_array[$result['ID']] = $meta_value[0]; 
          break;
        endif;
      endforeach;
    endforeach;
    
    // sort meta data
    if($filter_order == 'ASC') asort($meta_array);
    if($filter_order == 'DESC') arsort($meta_array);
    
    // create an array of only id values with the key being numeric 
    $all_array = array();    
    foreach($meta_array as $key => $value):
      $all_array[] = $key;
    endforeach;
        
    return array('all_ids'=>$all_ids, 'all_array'=>$all_array);
  }
  
  
  /**
    * Gets filtering/sorting options by post meta
    * @return string
   */
  public static function get_choices() {
    if(is_null($_GET['post'])) return false;
    
    $post_meta = get_post_meta((int)$_GET['post']);
    $post_meta = array_keys($post_meta);
    $excludes = self::exclude_meta_filters();
    $post_meta = self::exclude_filters($excludes, $post_meta);
    
    $defaults = "
         <option disabled = disable>Sort by</option>
         <option selected value=\"post_title\">Post Title</option>
         <option value=\"post_date\">Post Date</option>
         ";
    
    $meta_formated[] = $defaults;
    foreach($post_meta as $key => $meta):
      $meta_formated[] = '<option value="' . $meta . '">' . self::humanize($meta)  . '</option>' . "\r\n \t ";
    endforeach;
    
    return implode('', $meta_formated);
  } 
  
  
  /**
    * Unsets meta data filters from select menu
    * @param array $excludes
    * @param array $post_meta
    * @return array
    */
  public function exclude_filters($excludes, $post_meta) {
    foreach($post_meta as $meta=>$value):
      foreach($excludes as $exclude):
        if(preg_match("/$exclude/i", $value)):
          unset($post_meta[$meta]); 
          break;
        endif;
      endforeach;
     endforeach;
   
    return $post_meta;
  }
  
  
  /**
     * Set array of filters to be excluded
     * @return array
    */
  public function exclude_meta_filters() {
    // exclude anything that contains this array of words
    return array(
      'image',
      'video',
      'id',
      'url',
      'position',
      'body',
      'media',
      'lock',
      'edit',
      'thumb'
      );
  }
  
  
  /**
     * Get options 
     * @return array
    */
  public function get_options() {
    return array(
      'active_elements' => array(
        'filter_select',
        'page_info'
      ),
      'labels' => array(
        'next_label' => 'Next post',
        'prev_label' => 'Prev post'
      )
    );
  }
  
  
  /**
    * Coverts to a human readable string
    * @param string $str
    * @return string
    * @author http://snipplr.com/view/45370/
    */
  public static function humanize($str) {
    $str = trim(strtolower($str));
  	$str = preg_replace('/[^a-z0-9\s+]/', '', $str);
  	$str = preg_replace('/\s+/', ' ', $str);
  	$str = explode(' ', $str);
  	$str = array_map('ucwords', $str);
  	return implode(' ', $str);
  }
}
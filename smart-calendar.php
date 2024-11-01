<?php
/*
Plugin Name: Smart Calendar
Plugin URI: http://www.logicdesign.be/blog/
Description: Ajax calendar for posts 
Author: Sergio García Fernández
Version: 0.1.0
Author URI: http://www.logicdesign.be/
*/
class smart_calendar_widget extends WP_Widget {

	function smart_calendar_widget() {
	// Instantiate the parent object
	parent::WP_Widget( false, 'Smart Calendar Widget' );
	// Load jQuery
	wp_enqueue_script('jquery');
	}
		
	/** Echo the widget content.
	 *
	 * Subclasses should over-ride this function to generate their widget code.
	 *
	 * @param array $args Display arguments including before_title, after_title, before_widget, and after_widget.
	 * @param array $instance The settings for the particular instance of the widget
	 */
	function widget($args, $instance) {
	
		extract( $args );
		
		// Get the div id of the widget
		$widgetid = $args['widget_id'];
		
		$instance     = wp_parse_args( (array)$instance, array( 'title' => __( 'Smart Calendar', 'smart-calendar' ), 'teaser' => __( 0, 'smart-calendar' ) ) );
		$title        = apply_filters( 'widget_title', $instance['title'] );
		$teaser       = apply_filters( 'widget_teaser', $instance['teaser'] );
		
		echo $before_widget;
		
		$load_post_output = $this->load_post(0,$teaser);
		
		if ( $title )
			echo $before_title . stripslashes( $title ).$after_title;
		
		?>
			<script type="text/javascript">

			   function SetDiv(ID,Content) {
					  document.getElementById(ID).innerHTML = Content;
						 return;
				   }
				 
				function file(fichier)
					 {
					 if(window.XMLHttpRequest) // FIREFOX
						  xhr_object = new XMLHttpRequest();
					 else if(window.ActiveXObject) // IE
						  xhr_object = new ActiveXObject("Microsoft.XMLHTTP");
					 else
						  return(false);
					 xhr_object.open("GET", fichier, false);
					 xhr_object.send(null);
					 if(xhr_object.readyState == 4) return(xhr_object.responseText);
					 else return(false);
					 }  
					 
					function updateCal(thismonth, thisyear){
					SetDiv('calendar',file('index.php?sm_request=get_calendar&thismonth='+thismonth+'&thisyear='+thisyear));
					}
					function updatePost(id){
					SetDiv('smart-calendar-post',file('index.php?sm_post=update&id='+id+'&teaser=<?php echo $teaser ?>'));
					}
			</script>
		<?php	
		
		echo '<div id="smart-calendar-header"></div><div class="smart-calendar-content"><div id="smart-calendar-post">'.$load_post_output.'</div></div>';
		$calendar =  $this->smart_get_calendar();
		echo '<div class="smart-calendar-separator"></div><div class="smart-calendar-content"><div id="calendar">'.$calendar.'</div></div><div id="smart-calendar-footer"></div>';
	}
	
	function smart_get_calendar($inputmonth = '', $inputyear = ''){
		global $wpdb;
		global $wp_locale;
		
		// week_begins = 0 stands for Sunday
		$week_begins = intval(get_option('start_of_week'));
		
		if ($inputmonth && $inputyear){
		$thisyear = $inputyear;
		$thismonth = $inputmonth;
		}
		else{
			
			// Let's figure out when we are
			if ( !empty($monthnum) && !empty($year) ) {
				$thismonth = ''.zeroise(intval($monthnum), 2);
				$thisyear = ''.intval($year);
			} elseif ( !empty($w) ) {
				// We need to get the month from MySQL
				$thisyear = ''.intval(substr($m, 0, 4));
				$d = (($w - 1) * 7) + 6; //it seems MySQL's weeks disagree with PHP's
				$thismonth = $wpdb->get_var("SELECT DATE_FORMAT((DATE_ADD('{$thisyear}0101', INTERVAL $d DAY) ), '%m')");
			} elseif ( !empty($m) ) {
				$thisyear = ''.intval(substr($m, 0, 4));
				if ( strlen($m) < 6 )
						$thismonth = '01';
				else
						$thismonth = ''.zeroise(intval(substr($m, 4, 2)), 2);
			} else {
				$thisyear = gmdate('Y', current_time('timestamp'));
				$thismonth = gmdate('m', current_time('timestamp'));
			}
		}
		
		$unixmonth = mktime(0, 0 , 0, $thismonth, 1, $thisyear);
		$last_day = date('t', $unixmonth);
		
		//$current_mkey = array_search($thismonth,$month);
				// Get the next and previous month and year with at least one post
		//$previous = $month[$current_mkey - 1];
				
		
		$previous['year'] = $thisyear;
		$next['year'] = $thisyear;
		
		if ($thismonth >1) $prevnum['month'] = $thismonth - 1;
			else{ 
			$prevnum['month'] = 12;
			$previous['year'] = $thisyear - 1;
			}
		if ($thismonth <12) $nextnum['month'] = $thismonth + 1;
			else{ 
			$nextnum['month'] =  1;
			$next['year'] = $thisyear +1 ;
			}
		
		$previous['month'] = $wp_locale->get_month($prevnum['month']); 
		$next['month'] = $wp_locale->get_month($nextnum['month']); 
		
	        // Get the next and previous month and year with at least one post
			/*
	        $previous = $wpdb->get_row("SELECT MONTH(post_date) AS month, YEAR(post_date) AS year
	                FROM $wpdb->posts
	                WHERE post_date < '$thisyear-$thismonth-01'
	                AND post_type = 'post' AND post_status = 'publish'
	                ORDER BY post_date DESC
	                LIMIT 1");
	        $next = $wpdb->get_row("SELECT MONTH(post_date) AS month, YEAR(post_date) AS year
	                FROM $wpdb->posts
	                WHERE post_date > '$thisyear-$thismonth-{$last_day} 23:59:59'
	                AND post_type = 'post' AND post_status = 'publish'
	                ORDER BY post_date ASC
                    LIMIT 1");*/
		
	/* translators: Calendar caption: 1: month name, 2: 4-digit year */
	$calendar_caption = _x('%1$s %2$s', 'calendar caption');
	$calendar_output = '<div class="table-caption">'.sprintf($calendar_caption, $wp_locale->get_month($thismonth), date('Y', $unixmonth)).'</div><div class="smart-calendar-table"><table class="wp-calendar" id="wp-calendar-'.$thismonth.'"><thead><tr>';

	$myweek = array();

	for ( $wdcount=0; $wdcount<=6; $wdcount++ ) {
		$myweek[] = $wp_locale->get_weekday(($wdcount+$week_begins)%7);
	}

	foreach ( $myweek as $wd ) {
		$day_name = (true == $initial) ? $wp_locale->get_weekday_initial($wd) : $wp_locale->get_weekday_abbrev($wd);
		$wd = esc_attr($wd);
		$calendar_output .= "\n\t\t<th scope=\"col\" title=\"$wd\">$day_name</th>";
	}

	$calendar_output .= '
	</tr>
	</thead>

	<tfoot>
	<tr>';
	
	if ( $previous ) {
		$calendar_output .= "\n\t\t".'<td colspan="3" id="prev"><a onclick="javascript:updateCal('.$prevnum['month'].','.$previous['year'].');return false;" href="?sm_request=get_calendar&thismonth='.$prevnum['month'].'&thisyear='.$previous['year'].'" title="' . esc_attr( sprintf(__('View posts for %1$s %2$s'), $previous['month'], date('Y', mktime(0, 0 , 0, $prevnum['month'], 1, $previous['year'])))) . '" class="sm_agenda_prev" >' ./* $wp_locale->get_month_abbrev($previous['month']) . */'</a></td>';
	}

	$calendar_output .= "\n\t\t".'<td class="pad">&nbsp;</td>';

	if ( $next ) {
		$calendar_output .= "\n\t\t".'<td colspan="3" id="next"><a onclick="javascript:updateCal('.$nextnum['month'].','.$next['year'].');return false;" href="?sm_request=get_calendar&thismonth='.$nextnum['month'].'&thisyear='.$next['year'].'"" title="' . esc_attr( sprintf(__('View posts for %1$s %2$s'), $wp_locale->get_month($next['month']), date('Y', mktime(0, 0 , 0, $nextnum['month'], 1, $next['year']))) ) . '" class="sm_agenda_next">' . /*$wp_locale->get_month_abbrev($wp_locale->get_month($nextnum['month'])) .*/ '</a></td>';
	}

	$calendar_output .= '
	</tr>
	</tfoot>

	<tbody>
	<tr>';

	// Get days with posts
	$dayswithposts = $wpdb->get_results("SELECT DISTINCT DAYOFMONTH(post_date)
		FROM $wpdb->posts WHERE post_date >= '{$thisyear}-{$thismonth}-01 00:00:00'
		AND post_type = 'post' AND post_status = 'publish'
		AND post_date <= '{$thisyear}-{$thismonth}-{$last_day} 23:59:59'", ARRAY_N);
	if ( $dayswithposts ) {
		foreach ( (array) $dayswithposts as $daywith ) {
			$daywithpost[] = $daywith[0];
		}
	} else {
		$daywithpost = array();
	}

	if (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false || stripos($_SERVER['HTTP_USER_AGENT'], 'camino') !== false || stripos($_SERVER['HTTP_USER_AGENT'], 'safari') !== false)
		$ak_title_separator = "\n";
	else
		$ak_title_separator = ', ';

	$ak_titles_for_day = array();
	$ak_post_titles = $wpdb->get_results("SELECT ID, post_title, DAYOFMONTH(post_date) as dom "
		."FROM $wpdb->posts "
		."WHERE post_date >= '{$thisyear}-{$thismonth}-01 00:00:00' "
		."AND post_date <= '{$thisyear}-{$thismonth}-{$last_day} 23:59:59' "
		."AND post_type = 'post' AND post_status = 'publish'"
	);
	if ( $ak_post_titles ) {
		foreach ( (array) $ak_post_titles as $ak_post_title ) {

				$post_title = esc_attr( apply_filters( 'the_title', $ak_post_title->post_title, $ak_post_title->ID ) );
				if ( empty($ak_titles_for_day['day_'.$ak_post_title->dom]) )
					{
					$ak_titles_for_day['day_'.$ak_post_title->dom] = '';
					}
				if ( empty($ak_titles_for_day["$ak_post_title->dom"]) ) // first one
					{
					$ak_titles_for_day["$ak_post_title->dom"] = $post_title;
					$ak_titles_for_day['day_'.$ak_post_title->dom]['id'] = $ak_post_title->ID;
					}
				else
					{
					$ak_titles_for_day["$ak_post_title->dom"] .= $ak_title_separator . $post_title;
					}
		}
	}

	// See how much we should pad in the beginning
	$pad = calendar_week_mod(date('w', $unixmonth)-$week_begins);
	if ( 0 != $pad )
		$calendar_output .= "\n\t\t".'<td colspan="'. esc_attr($pad) .'" class="pad">&nbsp;</td>';

	$daysinmonth = intval(date('t', $unixmonth));
	for ( $day = 1; $day <= $daysinmonth; ++$day ) {
		if ( isset($newrow) && $newrow )
			$calendar_output .= "\n\t</tr>\n\t<tr>\n\t\t";
		$newrow = false;

		if ( $day == gmdate('j', current_time('timestamp')) && $thismonth == gmdate('m', current_time('timestamp')) && $thisyear == gmdate('Y', current_time('timestamp')) )
			$calendar_output .= '<td id="today">';
		else
			$calendar_output .= '<td>';

		if ( in_array($day, $daywithpost) ) // any posts today?
				$calendar_output .= '<a onclick="javascript:updatePost('.$ak_titles_for_day['day_'.$day ]['id'].');return false;" href="' . get_day_link( $thisyear, $thismonth, $day ) . '" title="' . esc_attr( $ak_titles_for_day[ $day ] ) . "\">$day</a>";
		else
			$calendar_output .= $day;
		$calendar_output .= '</td>';

		if ( 6 == calendar_week_mod(date('w', mktime(0, 0 , 0, $thismonth, $day, $thisyear))-$week_begins) )
			$newrow = true;
	}

	$pad = 7 - calendar_week_mod(date('w', mktime(0, 0 , 0, $thismonth, $day, $thisyear))-$week_begins);
	if ( $pad != 0 && $pad != 7 )
		$calendar_output .= "\n\t\t".'<td class="pad" colspan="'. esc_attr($pad) .'">&nbsp;</td>';

	$calendar_output .= "\n\t</tr>\n\t</tbody>\n\t</table></div>";
			
		return ($calendar_output);
	}
	
	function update( $new_instance, $old_instance ) {
		$instance     = $old_instance;
		$instance = wp_parse_args( (array)$instance, array( 'title' => __( 'Smart Calendar', 'smart-calendar' ),'Teaser'=>__('0','smart-calendar')) );

		$instance['title']        = wp_filter_nohtml_kses( $new_instance['title'] );
		$instance['teaser']		  = wp_filter_nohtml_kses( $new_instance['teaser']);
		
		return $instance;
	}

	function form( $instance ) {
		$instance = wp_parse_args( (array)$instance, array( 'title' => __( 'Smart Calendar', 'smart-calendar' ),'Teaser'=>__('0','smart-calendar')) );

		$title        = stripslashes( $instance['title'] );
		$teaser		  = stripslashes( $instance['teaser']);
		?>
<p><label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'smart-calendar' ); ?> <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" /></label></p><p><label for="<?php echo $this->get_field_id( 'teaser' ); ?>"><?php _e( 'Teaser:', 'smart-calendar' ); ?> <input class="widefat" id="<?php echo $this->get_field_id( 'teaser' ); ?>" name="<?php echo $this->get_field_name( 'teaser' ); ?>" type="text" value="<?php echo esc_attr( $teaser ); ?>" /></label></p>
		<?php
	}

function load_post($id=0,$teaser=0){
	if ($id > 0) $my_query = new WP_Query('p='.$id.'&order_by=modified&order=desc');
	else $my_query = new WP_Query('posts_per_page=1&order_by=modified&order=desc');
	$output = '';
	while ($my_query->have_posts()) : $my_query->the_post();
	  $output .= '<h3>'.get_the_title().'</h3>'.'<p>';
	  if ($teaser > 0 && strlen(get_the_content()) > $teaser) $output .= substr(get_the_content(),0,$teaser).'...</p>';
	  else $output .= get_the_content().'</p>';
	  $output .= '<a class="smart-calendar-btn" href="?p='.$id.'">voir article</a>';
	endwhile;
	return $output;
}
}

// Function for handling AJAX requests
function smart_calendar_post_handler() {
    // Check that all parameters have been passed
    if ((isset($_GET['sm_post']) && ($_GET['sm_post'] == 'update')) &&
      isset($_GET['id'])) {

		echo '<div id="smart-calendar-post">';
        // Output the response from your call and exit
		if ( isset($_GET['teaser']) && is_numeric($_GET['teaser']))
        echo smart_calendar_widget::load_post(strip_tags($_GET['id']),$_GET['teaser']);
		else echo smart_calendar_widget::load_post(strip_tags($_GET['id']),0);
		echo '</div>';
        exit();
    } elseif (isset($_GET['sm_post']) && ($_GET['sm_post'] == 'update')) {
        // Otherwise display an error and exit the call
        echo "Error: Unable to display request.".'</div>';
        exit();
    }
}
function sm_request_handler() {
    // Check that all parameters have been passed
    if ((isset($_GET['sm_request']) && ($_GET['sm_request'] == 'get_calendar')) &&
      isset($_GET['thismonth']) && isset($_GET['thisyear']) /*&& isset($_GET['teaser']) && is_numeric($_GET['teaser'])*/) {

		echo '<div id="calendar">';
        // Output the response from your call and exit
        echo smart_calendar_widget::smart_get_calendar(strip_tags($_GET['thismonth']),
          strip_tags($_GET['thisyear']),$_GET['teaser']).'</div>';
        exit();
    } elseif (isset($_GET['sm_request']) && ($_GET['sm_request'] == 'get_calendar')) {
        // Otherwise display an error and exit the call
        echo "Error: Unable to display request.".'</div>';
        exit();
    }
}

// Add the handler to init()
add_action('init', 'sm_request_handler');
// Add the handler to init()
add_action('init', 'smart_calendar_post_handler');
	
function register_smart_calendar_widget() {
	register_widget( 'smart_calendar_widget' );
}
function smart_calendar_stylesheet(){
		if (!is_admin()) {
	    $myStyleUrl = WP_PLUGIN_URL . '/smart-calendar/style/style.css';
        $myStyleFile = WP_PLUGIN_DIR . '/smart-calendar/style/style.css';
        if ( file_exists($myStyleFile) ) {
            wp_register_style('smartCalendarStyleSheet', $myStyleUrl);
            wp_enqueue_style( 'smartCalendarStyleSheet');
        }
		}
}
add_action( 'widgets_init', 'register_smart_calendar_widget' );

add_action('wp_print_styles', 'smart_calendar_stylesheet');

?>
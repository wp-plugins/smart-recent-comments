<?php
/*
Plugin Name: Smart Recent Comments
Plugin URI: http://www.clevelandwebdeveloper.com/wordpress-plugins/smart-recent-comments
Description: When you set up a new blog there aren't any comments to start off with--it takes time until you get your first real person to participate in the discussion. When you use the default 'recent comments' widget in WordPress you are left with a 'recent comments' title in your sidebar and no comments listed under it. This plugin will replace that recent comments widget with a smarter one. The recent comments widget in your sidebar will not show until you start getting comments. Once you get your first comment, the widget will automatically appear so you can show off your latest comments. In all other ways this is exactly the same as the default WordPress recent comments widget.
Version: 1.0
Author: Justin Saad
Author URI: http://www.clevelandwebdeveloper.com
License: GPL2
*/

if (!function_exists('motech_unregister_wp_recent_comments_widget')) {
    function motech_unregister_wp_recent_comments_widget() { 
        unregister_widget('WP_Widget_Recent_Comments');
    }
    add_action('widgets_init', 'motech_unregister_wp_recent_comments_widget', 1);
}


/**
 * Recent_Comments widget class
 *
 * @since 2.8.0
 */
class Motech_Widget_Recent_Comments extends WP_Widget {

	function __construct() {
		$widget_ops = array('classname' => 'widget_recent_comments', 'description' => __( 'The most recent comments' ) );
		parent::__construct('recent-comments', __('Recent Comments'), $widget_ops);
		$this->alt_option_name = 'widget_recent_comments';

		if ( is_active_widget(false, false, $this->id_base) )
			add_action( 'wp_head', array($this, 'recent_comments_style') );

		add_action( 'comment_post', array($this, 'flush_widget_cache') );
		add_action( 'transition_comment_status', array($this, 'flush_widget_cache') );
		
        if(is_admin()){
			add_filter( 'plugin_row_meta', array($this,'plugin_row_links'), 10, 2 );
		}
	}
	
	public function plugin_row_links($links, $file) {
		$plugin = plugin_basename(__FILE__); 
		if ($file == $plugin) // only for this plugin
				return array_merge( $links,
			array( '<a target="_blank" href="http://www.linkedin.com/in/ClevelandWebDeveloper/">' . __('Find me on LinkedIn' ) . '</a>' ),
			array( '<a target="_blank" href="http://twitter.com/ClevelandWebDev">' . __('Follow me on Twitter') . '</a>' )
		);
		return $links;
	}

	function recent_comments_style() {
		if ( ! current_theme_supports( 'widgets' ) // Temp hack #14876
			|| ! apply_filters( 'show_recent_comments_widget_style', true, $this->id_base ) )
			return;
		?>
	<style type="text/css">.recentcomments a{display:inline !important;padding:0 !important;margin:0 !important;}</style>
<?php
	}

	function flush_widget_cache() {
		wp_cache_delete('widget_recent_comments', 'widget');
	}

	function widget( $args, $instance ) {
		global $comments, $comment;
			$cache = wp_cache_get('widget_recent_comments', 'widget');
	
			if ( ! is_array( $cache ) )
				$cache = array();
	
			if ( ! isset( $args['widget_id'] ) )
				$args['widget_id'] = $this->id;
	
			if ( isset( $cache[ $args['widget_id'] ] ) ) {
				echo $cache[ $args['widget_id'] ];
				return;
			}
	
			extract($args, EXTR_SKIP);
			$output = '';
			$title = apply_filters( 'widget_title', empty( $instance['title'] ) ? __( 'Recent Comments' ) : $instance['title'], $instance, $this->id_base );
	
			if ( empty( $instance['number'] ) || ! $number = absint( $instance['number'] ) )
				$number = 5;
	
			$comments = get_comments( apply_filters( 'widget_comments_args', array( 'number' => $number, 'status' => 'approve', 'post_status' => 'publish' ) ) );
			if($comments) {
				$output .= $before_widget;
				if ( $title )
					$output .= $before_title . $title . $after_title;
		
				$output .= '<ul id="recentcomments">';
				if ( $comments ) {
					// Prime cache for associated posts. (Prime post term cache if we need it for permalinks.)
					$post_ids = array_unique( wp_list_pluck( $comments, 'comment_post_ID' ) );
					_prime_post_caches( $post_ids, strpos( get_option( 'permalink_structure' ), '%category%' ), false );
		
					foreach ( (array) $comments as $comment) {
						$output .=  '<li class="recentcomments">' . /* translators: comments widget: 1: comment author, 2: post link */ sprintf(_x('%1$s on %2$s', 'widgets'), get_comment_author_link(), '<a href="' . esc_url( get_comment_link($comment->comment_ID) ) . '">' . get_the_title($comment->comment_post_ID) . '</a>') . '</li>';
					}
				}
				$output .= '</ul>';
				$output .= $after_widget;
		
				echo $output;
			}
			$cache[$args['widget_id']] = $output;
			wp_cache_set('widget_recent_comments', $cache, 'widget');
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['number'] = absint( $new_instance['number'] );
		$this->flush_widget_cache();

		$alloptions = wp_cache_get( 'alloptions', 'options' );
		if ( isset($alloptions['widget_recent_comments']) )
			delete_option('widget_recent_comments');

		return $instance;
	}

	function form( $instance ) {
		$title = isset($instance['title']) ? esc_attr($instance['title']) : '';
		$number = isset($instance['number']) ? absint($instance['number']) : 5;
?>
		<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></p>

		<p><label for="<?php echo $this->get_field_id('number'); ?>"><?php _e('Number of comments to show:'); ?></label>
		<input id="<?php echo $this->get_field_id('number'); ?>" name="<?php echo $this->get_field_name('number'); ?>" type="text" value="<?php echo $number; ?>" size="3" /></p>
<?php
	}
}


function Motech_Widget_Recent_Comments_Init() {
	register_widget('Motech_Widget_Recent_Comments');
}
add_action('widgets_init', 'Motech_Widget_Recent_Comments_Init');


?>
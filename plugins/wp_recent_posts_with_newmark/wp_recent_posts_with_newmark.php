<?php
/*
Plugin Name: wp_recent_posts_with_newmark
Plugin URI: http://www.koikikukan.com/plugins/
Description: 最近の記事一覧に新着マークを表示します。
Version: 0.0.1
Author: Yujiro Araki
Author URI: http://www.koikikukan.com/
*/

class WP_Widget_Recent_Posts_With_NewMark extends WP_Widget {

    function WP_Widget_Recent_Posts_With_NewMark() {
        $widget_ops = array('classname' => 'widget_recent_entries_with_newmark', 'description' => __( "The most recent posts on your site with new mark", 'wp_recent_posts_with_newmark') );
        $this->WP_Widget('recent-posts-with-newmark', __('Recent Posts with New Mark', 'wp_recent_posts_with_newmark'), $widget_ops);
        $this->alt_option_name = 'widget_recent_entries_with_newmark';

        add_action( 'save_post', array(&$this, 'flush_widget_cache') );
        add_action( 'deleted_post', array(&$this, 'flush_widget_cache') );
        add_action( 'switch_theme', array(&$this, 'flush_widget_cache') );
    }

    function widget($args, $instance) {
        $cache = wp_cache_get('widget_recent_posts', 'widget');

        if ( !is_array($cache) )
            $cache = array();

        if ( isset($cache[$args['widget_id']]) ) {
            echo $cache[$args['widget_id']];
            return;
        }

        ob_start();
        extract($args);

        $title = apply_filters('widget_title', empty($instance['title']) ? __('Recent Posts') : $instance['title'], $instance, $this->id_base);
        if ( !$number = (int) $instance['number'] )
            $number = 10;
        else if ( $number < 1 )
            $number = 1;
//        else if ( $number > 15 )
//            $number = 15;

        if ( !$hour = (int) $instance['hour'] )
            $hour = 24;

        if ( !$html = $instance['html'] ) {
              $html = '<span style="color:#e50003">New!!</span>';
        }

        $r = new WP_Query(array('showposts' => $number, 'nopaging' => 0, 'post_status' => 'publish', 'caller_get_posts' => 1));
        if ($r->have_posts()) :
?>
        <?php echo $before_widget; ?>
        <?php if ( $title ) echo $before_title . $title . $after_title; ?>
        <ul>
        <?php  while ($r->have_posts()) : $r->the_post(); ?>
        <li><a href="<?php the_permalink() ?>" title="<?php echo esc_attr(get_the_title() ? get_the_title() : get_the_ID()); ?>"><?php if ( get_the_title() ) the_title(); else the_ID(); ?></a> 

<?php
        $current_date = date('U');
        $entry_date = get_the_time('U');
        $min = ceil(date('U',($current_date - $entry_date))/3600);
        if ($min+8 < $hour) {
            echo $html;
        }
?>
</li>
        <?php endwhile; ?>
        </ul>
        <?php echo $after_widget; ?>
<?php
        // Reset the global $the_post as this query will have stomped on it
        wp_reset_postdata();

        endif;

        $cache[$args['widget_id']] = ob_get_flush();
        wp_cache_set('widget_recent_posts', $cache, 'widget');
    }

    function update( $new_instance, $old_instance ) {
        $instance = $old_instance;
        $instance['title'] = strip_tags($new_instance['title']);
        $instance['number'] = (int) $new_instance['number'];
        $instance['hour'] = $new_instance['hour'];
        $instance['html'] = $new_instance['html'];
        $this->flush_widget_cache();

        $alloptions = wp_cache_get( 'alloptions', 'options' );
        if ( isset($alloptions['widget_recent_entries']) )
            delete_option('widget_recent_entries');

        return $instance;
    }

    function flush_widget_cache() {
        wp_cache_delete('widget_recent_posts', 'widget');
    }

    function form( $instance ) {
        $title = isset($instance['title']) ? esc_attr($instance['title']) : '';
        if ( !isset($instance['number']) || !$number = (int) $instance['number'] )
            $number = 5;
        if ( !isset($instance['hour']) || !$hour = (int) $instance['hour'] )
            $hour = 24;
        if ( !isset($instance['html']) || !$html = $instance['html'] )
//            $html = '&lt;span style=&quot;color:#e50003&quot;&gt;New!!&lt;/span&gt;';
            $html = '<span style="color:#e50003">New!!</span>';
?>
        <p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label>
        <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></p>

        <p><label for="<?php echo $this->get_field_id('number'); ?>"><?php _e('Number of posts to show:'); ?></label>
        <input id="<?php echo $this->get_field_id('number'); ?>" name="<?php echo $this->get_field_name('number'); ?>" type="text" value="<?php echo $number; ?>" size="3" /></p>

        <p><label for="<?php echo $this->get_field_id('hour'); ?>"><?php _e('Time:', 'wp_recent_posts_with_newmark'); ?></label>
        <input id="<?php echo $this->get_field_id('hour'); ?>" name="<?php echo $this->get_field_name('hour'); ?>" type="text" value="<?php echo $hour; ?>" size="3" /></p>

<?php
$html = preg_replace('/&/', '&amp;', $html);
$html = preg_replace('/</', '&lt;', $html);
$html = preg_replace('/>/', '&gt;', $html);
$html = preg_replace('/"/', '&quot;', $html);
?>
        <p><label for="<?php echo $this->get_field_id('html'); ?>"><?php _e('HTML for New Mark:', 'wp_recent_posts_with_newmark'); ?></label>
        <textarea class="widefat" id="<?php echo $this->get_field_id('html'); ?>" name="<?php echo $this->get_field_name('html'); ?>"><?php echo $html; ?></textarea></p>

<?php /*        <p><label for="<?php echo $this->get_field_id('image'); ?>"><?php _e('Image(option):'); ?></label>
        <input class="widefat" id="<?php echo $this->get_field_id('image'); ?>" name="<?php echo $this->get_field_name('image'); ?>" type="text" value="<?php echo $image; ?>" size="10" /></p>
*/ ?>
<?php
    }
}

function WP_Widget_Recent_Posts_With_NewMarkInit() {
    load_plugin_textdomain( 'wp_recent_posts_with_newmark', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
    register_widget('WP_Widget_Recent_Posts_With_NewMark');
}

add_action('widgets_init', 'WP_Widget_Recent_Posts_With_NewMarkInit');


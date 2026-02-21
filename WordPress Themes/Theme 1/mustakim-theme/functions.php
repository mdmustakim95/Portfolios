<?php
class Bootstrap_Nav_Walker extends Walker_Nav_Menu {

    // Start submenu
    public function start_lvl( &$output, $depth = 0, $args = null ) {
        $output .= '<ul class="dropdown-menu">';
    }

    // Start menu item
    public function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {

        $classes = empty( $item->classes ) ? [] : (array) $item->classes;

        // Detect active menu item properly
        $is_active = in_array( 'current-menu-item', $classes ) ||
                     in_array( 'current-menu-parent', $classes ) ||
                     in_array( 'current_page_item', $classes );

        $active_class = $is_active ? ' active' : '';

        $output .= '<li class="nav-item">';

        $atts  = ' class="nav-link' . $active_class . '"';
        $atts .= ! empty( $item->url ) ? ' href="' . esc_attr( $item->url ) . '"' : '';
        $atts .= $is_active ? ' aria-current="page"' : '';

        $output .= '<a' . $atts . '>';
        $output .= esc_html( $item->title );
        $output .= '</a>';
    }

    // End menu item
    public function end_el( &$output, $item, $depth = 0, $args = null ) {
        $output .= '</li>';
    }
}

// Register menu
function theme_register_menus() {
    register_nav_menus(array(
        'primary-menu' => __('Top Menu'),
    ));
}
add_action('after_setup_theme', 'theme_register_menus');


// code for post thumbnail 
add_action( 'after_setup_theme', function() {
    add_theme_support( 'post-thumbnails' );
});


// code to insert brnad logo
add_theme_support('custom-header');

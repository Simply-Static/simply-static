<?php

namespace Simply_Static;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Mpdf\Tag\Option;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Simply Static Search Widget
 *
 * Elementor widget for Simply Static search functionality.
 *
 * @since 1.0.0
 */
class Elementor_Search_Widget extends Widget_Base {

	/**
	 * Get widget name.
	 *
	 * Retrieve search widget name.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return string Widget name.
	 */
	public function get_name() {
		return 'simply-static-search';
	}

	/**
	 * Get widget title.
	 *
	 * Retrieve search widget title.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return string Widget title.
	 */
	public function get_title() {
		return __( 'Search', 'simply-static' );
	}

	/**
	 * Get widget icon.
	 *
	 * Retrieve search widget icon.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return string Widget icon.
	 */
	public function get_icon() {
		return 'eicon-search';
	}

	/**
	 * Get widget categories.
	 *
	 * Retrieve the list of categories the search widget belongs to.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return array Widget categories.
	 */
	public function get_categories() {
		return [ 'simply-static' ];
	}

	/**
	 * Get widget keywords.
	 *
	 * Retrieve the list of keywords the widget belongs to.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return array Widget keywords.
	 */
	public function get_keywords() {
		return [ 'search', 'form', 'find' ];
	}

	/**
	 * Register search widget controls.
	 *
	 * Adds different input fields to allow the user to change and customize the widget settings.
	 *
	 * @since 1.0.0
	 * @access protected
	 */
	protected function register_controls() {

		$this->start_controls_section(
			'content_section',
			[
				'label' => __( 'Content', 'simply-static' ),
				'tab' => Controls_Manager::TAB_CONTENT,
			]
		);

		$this->add_control(
			'placeholder_text',
			[
				'label' => __( 'Placeholder Text', 'simply-static' ),
				'type' => Controls_Manager::TEXT,
				'default' => __( 'Search...', 'simply-static' ),
				'placeholder' => __( 'Enter placeholder text', 'simply-static' ),
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'style_section',
			[
				'label' => __( 'Style', 'simply-static' ),
				'tab' => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_control(
			'input_width',
			[
				'label' => __( 'Input Width', 'simply-static' ),
				'type' => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%' ],
				'range' => [
					'px' => [
						'min' => 100,
						'max' => 1000,
						'step' => 5,
					],
					'%' => [
						'min' => 10,
						'max' => 100,
					],
				],
				'default' => [
					'unit' => '%',
					'size' => 100,
				],
				'selectors' => [
					'{{WRAPPER}} .simply-static-search-input' => 'width: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Render search widget output on the frontend.
	 *
	 * Written in PHP and used to generate the final HTML.
	 *
	 * @since 1.0.0
	 * @access protected
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();

		$placeholder_text = ! empty( $settings['placeholder_text'] ) ? $settings['placeholder_text'] : __( 'Search...', 'simply-static' );
        $options          = Options::instance();
		$search_type      = $options->get('search_type');

        if ( ! $search_type ) {
            $search_type = 'fuse';
        }

        $search_class = 'fuse' === $search_type ? $options->get('fuse_selector') : $options->get('algolia_selector');

        if ( ! $search_class ) {
            $search_class = '.search-field';
        }

        $search_class = explode(' ', $search_class );
        $search_class = end( $search_class );
        if ( $search_class !== null ) {
            $search_class = ltrim( $search_class, '.' );
        }
		?>
		<div class="simply-static-search-widget">
			<div class="simply-static-search-wrapper">
                <input
                    type="search"
                    class="<?php echo esc_attr( $search_class ); ?>"
                    placeholder="<?php echo esc_attr( $placeholder_text ); ?>"
                    value="<?php echo get_search_query(); ?>"
                    name="s"
                    title="<?php echo esc_attr( $placeholder_text ); ?>"
                />

            </div>

		</div>
		<?php
	}

	/**
	 * Render search widget output in the editor.
	 *
	 * Written as a Backbone JavaScript template and used to generate the live preview.
	 *
	 * @since 1.0.0
	 * @access protected
	 */
	protected function content_template() {
		$options     = Options::instance();
		$search_type = $options->get('search_type');

		if ( ! $search_type ) {
			$search_type = 'fuse';
		}

		$search_class = 'fuse' === $search_type ? $options->get('fuse_selector') : $options->get('algolia_selector');

		if ( ! $search_class ) {
			$search_class = '.search-field';
		}

		$search_class = explode(' ', $search_class );
		$search_class = end( $search_class );
		if ( $search_class !== null ) {
			$search_class = ltrim( $search_class, '.' );
		}

		?>
		<#
		var placeholder_text = settings.placeholder_text || '<?php echo __( 'Search...', 'simply-static' ); ?>';
		#>
		<div class="simply-static-search-widget">
			<div class="simply-static-search-wrapper">
                <input
                    type="search"
                    class="<?php echo esc_attr( $search_class ); ?>"
                    placeholder="{{ placeholder_text }}"
                    name="s"
                    title="{{ placeholder_text }}"
                />
            </div>
		</div>
		<?php
	}
}

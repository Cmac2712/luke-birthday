<?php
/**
 * Initializes an addional featured image for use in backend and frontend.
 *
 * @author     ThemeFusion
 * @copyright  (c) Copyright by ThemeFusion
 * @link       https://theme-fusion.com
 * @package    Avada
 * @subpackage Core
 * @since      5.2
 */

if ( ! class_exists( 'Avada_Featured_Image' ) ) {
	/**
	 * Featured image class.
	 */
	class Avada_Featured_Image {

		/**
		 * The class arguments.
		 *
		 * @access private
		 * @var array
		 */
		private $args = [];

		/**
		 * The class defaults.
		 *
		 * @access private
		 * @var array
		 */
		private $defaults = [
			'id'           => 'featured-image-2',
			'post_type'    => 'page',
			'name'         => 'Featured Image 2',
			'label_set'    => 'Set featured image 2',
			'label_remove' => 'Remove featured image 2',
		];

		/**
		 * Constructor.
		 *
		 * @since 5.2
		 * @access public
		 * @param array $args The arguments.
		 * @return void
		 */
		public function __construct( $args ) {
			$this->args                  = wp_parse_args( $args, $this->defaults );
			$this->args['metabox_id']    = $this->args['id'] . '_' . $this->args['post_type'];
			$this->args['post_meta_key'] = 'kd_' . $this->args['metabox_id'] . '_id';

			add_action( 'add_meta_boxes', [ $this, 'add_meta_box' ] );
		}

		/**
		 * Add admin metabox for an additional featured image.
		 *
		 * @since 5.2
		 * @access public
		 * @return void
		 */
		public function add_meta_box() {
			add_meta_box(
				$this->args['metabox_id'],
				$this->args['name'],
				[ $this, 'meta_box_content' ],
				$this->args['post_type'],
				'side',
				'low'
			);
		}

		/**
		 * Output the metabox content.
		 *
		 * @since 5.2
		 * @access public
		 * @global object $post
		 * @return void
		 */
		public function meta_box_content() {
			global $post;

			$image_id = fusion_data()->post_meta( $post_id )->get( $this->args['post_meta_key'] );

			$output            = '';
			$preview_image_css = ' style="display:none;"';
			$remove_image_css  = ' style="display:none;"';

			if ( $image_id ) {
				$preview_image    = wp_get_attachment_image(
					$image_id,
					[ 266, 266 ],
					false,
					[
						'class' => 'fusion-preview-image',
					]
				);
				$remove_image_css = '';
			} else {
				$preview_image     = '<img class="fusion-preview-image" src="" style="display:none;">';
				$preview_image_css = '';
			}

			$preview_image = '<span class="fusion-set-featured-image"' . $preview_image_css . '>' . $this->args['label_set'] . '</span>' . $preview_image;

			$set_image_link          = '<p class="hide-if-no-js">';
				$set_image_link     .= '<a aria-label="' . $this->args['label_set'] . '" href="#" id="' . $this->args['id'] . '" class="fusion_upload_button">';
					$set_image_link .= $preview_image;
				$set_image_link     .= '</a>';
				$set_image_link     .= '<input class="upload_field" id="' . $this->args['post_meta_key'] . '" name="' . Fusion_Data_PostMeta::ROOT . '[' . $this->args['post_meta_key'] . ']" value="' . $image_id . '" type="hidden">';
			$set_image_link         .= '</p>';

			$remove_image_link      = '<p class="hide-if-no-js fusion-remove-featured-image"' . $remove_image_css . '>';
				$remove_image_link .= '<a aria-label="' . $this->args['label_remove'] . '" href="#" id="' . $this->args['id'] . '" class="fusion-remove-image">' . $this->args['label_remove'] . '</a>';
			$remove_image_link     .= '</p>';

			$output = '<div class="fusion-featured-image-meta-box">' . $set_image_link . $remove_image_link . '</div>';

			echo $output; // phpcs:ignore WordPress.Security.EscapeOutput
		}

		/**
		 * Retrieve the ID of the featured image.
		 *
		 * @since 5.2
		 * @static
		 * @access public
		 * @global object $post
		 * @param string $image_id Internal ID of the featured image.
		 * @param string $post_type The post type of the post the featured image belongs to.
		 * @param int    $post_id A custom post ID.
		 * @return int The featured image ID.
		 */
		public static function get_featured_image_id( $image_id, $post_type, $post_id = null ) {
			global $post;

			if ( is_null( $post_id ) ) {
				$post_id = get_the_ID();
			}

			return fusion_data()->post_meta( $post_id )->get( 'kd_' . $image_id . '_' . $post_type . '_id' );
		}
	}
}

if ( ! function_exists( 'fusion_get_featured_image_id' ) ) {
	/**
	 * Get the ID of the featured image.
	 *
	 * @since 5.2
	 * @param int    $image_id  The image ID.
	 * @param string $post_type The post-type.
	 * @param int    $post_id   The post-ID.
	 * @return int
	 */
	function fusion_get_featured_image_id( $image_id, $post_type, $post_id = null ) {
		if ( ! class_exists( 'kdMultipleFeaturedImages' ) ) {
			return 0;
		}
		return kdMultipleFeaturedImages::get_featured_image_id( $image_id, $post_type, $post_id );
	}
}

/* Omit closing PHP tag to avoid "Headers already sent" issues. */

<?php
/**
 * Metabox class
 *
 * @package WordPress
 * @subpackage Wolf Metaboxes
 * @version 1.0.4
 */

defined( 'ABSPATH' ) || exit;

/**
 * Metabox tabs class
 *
 * Create metabox tabs from an array (config/metaboxes.php)
 * Combine all metaboxes sections into tabs panel
 *
 * @package WordPress
 * @subpackage Wolf Metaboxes
 * @version 1.0.4
 */
class Wolf_Metaboxes {

	var $meta = array(); // all metabox data

	var $post_types = array( 'post', 'page', 'product', 'work', 'gallery', 'release', 'event', 'video', 'artist', 'mp-event', 'mp-column' );

	var $context;

	var $priority;

	/**
	 * Constructor
	 */
	public function __construct( $meta = array(), $context  = 'normal', $priority = 'default' ) {

		$this->meta = $meta + $this->meta;
		$this->context = $context;
		$this->priority = $priority;

		$this->post_types = apply_filters( 'wmbox_metabox_post_types', $this->post_types );

		add_action( 'add_meta_boxes', array( $this, 'add_meta' ) );
		add_action( 'save_post', array( $this, 'save' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'scripts' ) );
	}

	/**
	 * Add metaboxes
	 */
	public function add_meta() {

		global $post;
		$post_id = $post->ID;

		add_meta_box(
			'_wolf_metaboxes',
			sprintf( esc_html__( '%s Options', 'wolf-metaboxes' ), $this->get_post_type_name() ),
			array( $this, 'render' ),
			$this->post_types,
			$this->context,
			$this->priority
		);
	}

	/**
	 * Render
	 */
	public function render() {
		global $post;
		$post_id = $post->ID;

		$meta = $this->meta;
		$meta_fields = array();

		$current_post_type = get_post_type( $post_id );

		foreach ( $meta as $k => $v ) {
			if ( is_array( $v['page'] ) ) {
				if ( in_array( $current_post_type, $v['page'] ) ) {
					$meta_fields = $v['metafields'];
				}
			} else {
				if ( $v['page'] == $current_post_type ) {
					$meta_fields = $v['metafields'];
				}
			}
		}

		/* Opening tag */
		echo '<div class="wmbox-metabox-tabs-panel">';

		/* Tabs */
		echo '<ul class="wmbox-metabox-tabs">';
		foreach ( $meta as $key => $value ) {
			$post_types = $value['page'];

			if ( in_array( $current_post_type, $post_types ) ) {
			?>
				<li><a id="wvc-metabox-tab-<?php echo esc_attr( $key ); ?>" href="#<?php echo esc_attr( $key ); ?>"><?php echo sanitize_text_field( $value['title'] ); ?></a></li>
			<?php
			}
		}
		echo '</ul>';

		$i = 0;
		/* Panels */
		foreach ( $meta as $key => $value ) {
			$fields = $value['metafields'];
			$post_types = $value['page'];

			if ( ! in_array( $current_post_type, $post_types ) ) {
				continue;
			}

			$i++
			?>
			<div id="<?php echo esc_attr( $key ); ?>" class="wmbox-metabox-panel">
				<?php
					foreach ( $fields as $field ) {
						$this->render_field( $field ); // render field
					}
				?>
			</div>
			<?php
		}

		/* Closing tag */
		echo '</div><!-- .wolf-metabox-panel -->';
	}

	/**
	 * Render field
	 */
	function render_field( $field = array() ) {

		global $post;
		$post_id = $post->ID;

		$field_id	= sanitize_title( $field['id'] );
		$type     	= ( isset( $field['type'] ) ) ? $field['type'] : 'text';
		$label    	= ( isset( $field['label'] ) ) ? $field['label'] : esc_html__( 'Label', 'wolf-metaboxes' );
		$desc    	= ( isset( $field['desc'] ) ) ? $field['desc'] : '';
		$placeholder   = ( isset( $field['placeholder'] ) ) ? $field['placeholder'] : '';
		$default_value = ( isset( $field['value'] ) ) ? $field['value'] : '';
		$dependency	= ( isset( $field['dependency'] ) ) ? $field['dependency'] : array();
		$class 		= "field option-section-$field_id";
		$data 		= '';

		if ( array() != $dependency ) {
			$class .= ' has-dependency';

			$data .= ' data-dependency-element="' . $dependency['element'] . '"';

			$dependency_value = '[';
			foreach ( $dependency['value'] as $value ) {
				$dependency_value .= '"' . $value . '"';
			}
			$dependency_value .= ']';

			$data .= " data-dependency-values='$dependency_value'";
		}

		/* get value of this field if it exists for this post */
		$meta = ( get_post_meta( $post_id, $field_id, true ) ) ? get_post_meta( $post_id, $field_id, true ) : $default_value;

		// Use nonce for verification
		echo '<input type="hidden" name="custom_meta_box_nonce" value="' . wp_create_nonce( basename( __FILE__ ) ) . '">';

		echo '<div class="' . $this->sanitize_html_classes( $class ) . '" ' . $data . '>';

			echo '<div class="field-label">';
				echo '<label for="' . esc_attr( $field_id ) . '">' . sanitize_text_field( $label ) . '</label>';
			echo '</div>';

			echo '<div class="field-content">';
			// editor
				if ( 'editor' == $type ) {
					wp_editor( $meta, $field_id, $options = array() );
					echo '<br><span class="description">' . wp_kses_post( $desc ) . '</span>';
				// text
				} elseif ( 'text' == $type || 'int' == $type ) {

					echo '<input placeholder="' . esc_attr( $placeholder ) . '" type="text" name="' . esc_attr( $field_id ) . '" id="' . esc_attr( $field_id ) . '" value="' . esc_attr( $meta ) . '" size="30" />
					<br><span class="description">' . wp_kses_post( $desc ) . '</span>';

				// textarea
				} elseif ( 'textarea' == $type ) {
					echo '<textarea name="' . esc_attr( $field_id ) . '" id="' . esc_attr( $field_id ) . '" cols="60" rows="4">' . wp_kses_post( $meta ) . '</textarea>
					<br><span class="description">' . wp_kses_post( $desc ) . '</span>';

				// checkbox
				} elseif ( 'checkbox' == $type ) {
					echo '<input type="checkbox" name="' . esc_attr( $field_id ) . '" id="' . esc_attr( $field_id ) . '" ', ( $meta ) ? ' checked="checked"' : '','/>
					<span class="description">' . wp_kses_post( $desc ) . '</span>';

				// select
				} elseif ( 'select' == $type ) {

					echo '<select name="' . $field_id . '" id="' . $field_id . '">';
					if ( array_keys( $field['choices'] ) != array_keys( array_keys( $field['choices'] ) ) ) {
						foreach ( $field['choices'] as $k => $option) {
							echo '<option', $k == $meta ? ' selected="selected"' : '', ' value="'.$k.'">' . $option . '</option>';
						}
					} else{
						foreach ( $field['choices'] as $option) {
							echo '<option', $option == $meta ? ' selected="selected"' : '', ' value="' . $option . '">' . $option . '</option>';
						}
					}

					echo '</select><br><span class="description">' . $desc . '</span>';

				// colorpicker
				} elseif ( 'colorpicker' == $type ) {

					echo '<input type="text" class="wmbox-colorpicker wmbox-colorpicker-input" name="' . $field_id . '" id="' . $field_id . '" value="' . $meta . '" />
					<br><span class="description">' . $desc . '</span>';

				// file
				} elseif ( 'file' == $type ) {
					$meta_img = get_post_meta( $post_id, $field_id, true );
				?>
				<div>
					<input type="text"  name="<?php echo esc_attr( $field_id ); ?>" id="<?php echo esc_attr( $field_id ); ?>" value="<?php echo esc_url( $meta_img); ?>">
					<br><a href="#" class="button wmbox-reset-file"><?php esc_html_e( 'Clear', 'wolf-metaboxes' ); ?></a>
					<a href="#" class="button wmbox-set-file"><?php esc_html_e( 'Choose a file', 'wolf-metaboxes' ); ?></a>

					<?php if ( $desc ) : ?>
						<br><span class="description"><?php echo wp_kses_post( $desc ); ?></span>
					<?php endif; ?>
				</div>
				<div style="clear:both"></div>
				<?php

				// image
				} elseif ( 'image' == $type ) {
					$meta_img = absint( get_post_meta( $post_id, $field_id, true ) );
					$meta_img_url = esc_url( $this->get_url_from_attachment_id( $meta_img ) );
				?>
				<div>
					<input type="hidden"  name="<?php echo esc_attr( $field_id ); ?>" id="<?php echo esc_attr( $field_id ); ?>" value="<?php echo absint( $meta_img); ?>">
					<img style="max-width:250px;<?php if ( 0 == $meta_img ) echo ' display:none;'; ?>" class="wmbox-img-preview" src="<?php echo esc_url( $meta_img_url ); ?>" alt="<?php echo esc_attr( $field_id ); ?>">
					<br><a href="#" class="button wmbox-reset-img"><?php esc_html_e( 'Clear', 'wolf-metaboxes' ); ?></a>
					<a href="#" class="button wmbox-set-img"><?php esc_html_e( 'Choose an image', 'wolf-metaboxes' ); ?></a>
				</div>

				<div class="clear"></div>
				<?php
				echo '<br><span class="description">' . wp_kses_post( $desc ) . '</span>';

				/*  Background
				-------------------------------------------*/
				} elseif ( 'background' == $type ) {

					$parallax           	= isset( $field['parallax'] ) ? $field['parallax'] : false;
					$bg_meta_color      	= get_post_meta( $post_id, $field_id . '_color', true );
					$bg_meta_repeat     	= get_post_meta( $post_id, $field_id . '_repeat', true );
					$bg_meta_position   	= get_post_meta( $post_id, $field_id . '_position', true );
					$bg_meta_attachment = get_post_meta( $post_id, $field_id . '_attachment', true );
					$bg_meta_size       	= get_post_meta( $post_id, $field_id . '_size', true );
					$bg_meta_parallax   	= get_post_meta( $post_id, $field_id . '_parallax', true );
					$exclude_params 	= isset( $field['exclude_params'] ) ?$field['exclude_params'] : array();

					$img = get_post_meta( $post_id, $field_id . '_img', true );

					if ( is_numeric( $img ) ) {
						$img = absint( get_post_meta( $post_id, $field_id . '_img', true ) );
						$img_url = $this->get_url_from_attachment_id( $img, 'thumbnail' );
					} else {
						$img = esc_url( get_post_meta( $post_id, $field_id . '_img', true ) );
						$img_url = esc_url( $img );
					}

					/* Bg Image */
					if ( ! in_array( 'color', $exclude_params ) ) {
					?>
					<p><?php esc_html_e( 'Background color', 'wolf-metaboxes' ); ?></p>
					<input name="<?php echo esc_attr( $field_id . '_color' ); ?>" name="<?php echo esc_attr( $field_id . '_color' ); ?>" class="wmbox-colorpicker" type="text" value="<?php echo esc_attr( $bg_meta_color ); ?>">
					<br><br>
					<?php
					}
					if ( ! in_array( 'image', $exclude_params ) ) {

					?>
					<p><?php esc_html_e( 'Background image', 'wolf-metaboxes' ); ?></p>
					<div>
						<input type="hidden" name="<?php echo esc_attr( $field_id ); ?>_img" id="<?php echo esc_attr( $field_id ); ?>_img" value="<?php echo esc_attr( $img ); ?>">
						<img style="max-width:250px;<?php if ( ! $img ) echo ' display:none;'; ?>" class="wmbox-img-preview" src="<?php echo esc_url( $img_url ); ?>" alt="<?php echo esc_attr( $field_id ); ?>">
						<br><a href="#" class="button wmbox-reset-bg"><?php esc_html_e( 'Clear', 'wolf-metaboxes' ); ?></a>
						<a href="#" class="button wmbox-set-bg"><?php esc_html_e( 'Choose an image', 'wolf-metaboxes' ); ?></a>
					</div>
					<br><br>
					<?php
					}

					if ( ! in_array( 'repeat', $exclude_params ) ) {
					/* Bg Repeat */
					$options = array(  'no-repeat', 'repeat','repeat-x', 'repeat-y' );

					?>
					<br>
					<p><?php esc_html_e( 'Background repeat', 'wolf-metaboxes' ); ?></p>
					<select name="<?php echo esc_attr( $field_id ) . '_repeat'; ?>" id="<?php echo esc_attr( $field_id ) . '_repeat'; ?>">
						<?php foreach ( $options as $o): ?>
							<option value="<?php echo esc_attr( $o ); ?>" <?php if ( $o == $bg_meta_repeat ) echo 'selected="selected"'; ?>><?php echo esc_attr( $o ); ?></option>
						<?php endforeach; ?>
					</select>
					<?php
					}
					if ( ! in_array( 'position', $exclude_params ) ) {
					/* Bg position */
					$options = array(
						'center center',
						'center top',
						'left top' ,
						'right top' ,
						'center bottom',
						'left bottom' ,
						'right bottom' ,
						'left center' ,
						'right center'
					);

					?>
					<br><br>
					<p><?php esc_html_e( 'Background position', 'wolf-metaboxes' ); ?></p>
					<select name="<?php echo esc_attr( $field_id ) . '_position'; ?>" id="<?php echo esc_attr( $field_id ) . '_position'; ?>">
						<?php foreach ( $options as $o): ?>
							<option value="<?php echo esc_attr( $o ); ?>" <?php if ( $o == $bg_meta_position ) echo 'selected="selected"'; ?>><?php echo esc_attr( $o ); ?></option>
						<?php endforeach; ?>
					</select>
					<?php
					}
					if ( ! in_array( 'size', $exclude_params ) ) {

					/* size
					--------------------*/
					$options = array(
						'cover' => esc_html__( 'cover (resize)', 'wolf-metaboxes' ),
						'normal' => esc_html__( 'normal', 'wolf-metaboxes' ),
						'resize' => esc_html__( 'responsive (hard resize)', 'wolf-metaboxes' ),
					);

					?>
					<br><br>
					<p><?php esc_html_e( 'Background size', 'wolf-metaboxes' ); ?></p>
					<select name="<?php echo esc_attr( $field_id ) . '_size'; ?>" id="<?php echo esc_attr( $field_id ) . '_size'; ?>">
						<?php foreach ( $options as $k => $v ) : ?>
							<option value="<?php echo esc_attr( $k ); ?>" <?php if ( $k == $bg_meta_size ) echo 'selected="selected"'; ?>><?php echo sanitize_text_field( $v ); ?></option>
						<?php endforeach; ?>
					</select>
					<?php
					}
					if ( $parallax ) {
						?>
						<br><br>
						<p><strong><?php esc_html_e( 'Parallax', 'wolf-metaboxes' ); ?></strong></p>
						<input <?php if ( $bg_meta_parallax ) echo 'checked="checked"'; ?> type="checkbox" name="<?php echo esc_attr( $field_id ) . '_parallax'; ?>" id="<?php echo esc_attr( $field_id ) . '_parallax'; ?>">
						<?php
					}

				} elseif ( 'multiple_images' == $type ) {

				?>
				<div><?php
					$attachments = array();
					$thumbnail_url = '';
					$meta = $this->clean_list( $meta );

					if ( is_numeric( $meta ) ) {
						$attachments = array( $meta );
					} else {
						$attachments = explode( ',', $meta );
					}
					$reset_multiple_image_confirm = esc_html__( 'Are you sure to want to reset all images ?', 'wolf-metaboxes' );
					?>
					<div class="wmbox-images-set clearfix">
						<?php
						foreach ( $attachments as $attachment_id ) :

							if ( $attachment_id ) :

								$is_file = $this->get_url_from_attachment_id( absint( $attachment_id ), 'thumbnail' );

								if ( is_numeric( $attachment_id ) && $is_file ) {

									$thumbnail_url = $this->get_url_from_attachment_id( absint( $attachment_id ), 'thumbnail' );

								}
								if ( $thumbnail_url ) {
									?>
									<span class="wmbox-image" data-attachment-id="<?php echo absint( $attachment_id ); ?>">
										<span class="wmbox-remove-img"></span>
										<img src="<?php echo esc_url( $thumbnail_url ); ?>">
									</span>
									<?php
								}
							endif;
						endforeach;
						?>
					</div><!-- .images-set -->
					<div class="clear"></div>
					<br>
					<input type="hidden" name="<?php echo esc_attr( $field_id ); ?>" value="<?php echo esc_attr( $meta ); ?>">
					<a href="#" class="button wmbox-param-reset-all-img"><?php esc_html_e( 'Clear All', 'wolf-metaboxes' ); ?></a>
					<a href="#" class="button wmbox-param-set-multiple-img"><?php esc_html_e( 'Select Images', 'wolf-metaboxes' ); ?></a>
				</div>

				<div class="clear"></div>
				<?php

				/*  Video
				-------------------------------------------*/
				} elseif ( 'video' === $type ) {
					$meta_url = get_post_meta( $post_id, $field_id, true );
					?>
					<div class="wmbox-video-bg-field">
						<input type="text"  name="<?php echo esc_attr( $field_id ); ?>" id="<?php echo esc_attr( $field_id ); ?>" size="30" value="<?php echo esc_url( $meta_url ); ?>">
						<br><a href="#" class="button wmbox-reset-file"><?php esc_html_e( 'Clear', 'wolf-metaboxes' ); ?></a>
						<a href="#" class="button wmbox-set-video-file"><?php esc_html_e( 'Choose a file', 'wolf-metaboxes' ); ?></a>
						<br>
						<?php if ( $desc ) : ?>
							<span class="description"><?php echo sanitize_text_field( $desc ); ?></span>
						<?php endif; ?>
					</div>
					<?php

				/*  Video
				-------------------------------------------*/
				} elseif ( 'font_family' === $type ) {
					/**
					 * Get Google Fonts from Font loader
					 */
					$google_fonts = ( function_exists( 'wvc_get_google_fonts_options' ) ) ? wvc_get_google_fonts_options() : array();

					$font_choices = array(
						'' => '&mdash; ' . esc_html__( 'Default', 'wolf-metaboxes' ) . ' &mdash;',
					);

					foreach ( $google_fonts as $key => $value ) {
						$font_choices[ $key ] = $key;
					}
					?>
					<div>
						<select name="<?php echo esc_attr( $field_id ); ?>" id="<?php echo esc_attr( $field_id ); ?>">
							<?php foreach ( $font_choices as $key => $value): ?>
								<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $key, $meta ); ?>><?php echo sanitize_text_field( $value ); ?></option>
							<?php endforeach ?>
						</select>
					</div>
					<?php if ( $desc ) : ?>
						<span class="description"><?php echo sanitize_text_field( $desc ); ?></span>
					<?php endif; ?>
					<?php

				} //end conditions

			echo '</div>'; // end field content

		echo '</div>'; // end panel
	}

	/**
	 * Save post meta hook
	 */
	public function save( $post_id ) {

		global $post;

		$meta_fields = '';

		// verify nonce
		if ( ( isset( $_POST['wolf_meta_box_nonce'] ) ) && ( ! wp_verify_nonce( $_POST['wolf_meta_box_nonce'], basename( __FILE__ ) ) ) )
			return $post_id;

		// check autosave
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return $post_id;

		// check permissions
		if ( isset( $_POST['post_type'] ) && is_object( $post ) ) {

			$current_post_type = get_post_type( $post->ID );

			if ( 'page' == $_POST['post_type'] ) {
				if ( ! current_user_can( 'edit_page', $post_id ) ) {
					return $post_id;

				} elseif ( ! current_user_can( 'edit_post', $post_id ) ) {
					return $post_id;
				}
			}

			foreach ( $this->meta as $k => $v ) {

				if ( is_array( $v['page'] ) ) {
					$condition = isset( $_POST['post_type'] ) && in_array( $_POST['post_type'], $v['page'] );
				} else {
					$condition = isset( $_POST['post_type'] ) && $_POST['post_type'] === $v['page'];
				}

				if ( $condition ) {

					$meta_fields = $v['metafields'];

					// loop through fields and save the data
					foreach ( $meta_fields as $field ) {

						$field_id = $field['id'];
						$type = $field['type'];
						$meta = get_post_meta( $post_id, $field_id, true );

						if ( 'background' == $type ) {

							$bg_options = array( 'color', 'position', 'repeat', 'attachment', 'size', 'img', 'parallax' );

							foreach ( $bg_options as $s ) {

								$o = $field_id . '_' . $s;

								if ( isset( $_POST[ $o ] ) ) {

									$bg_data = $_POST[ $o ];

									if ( 'img' == $o ) {

										if ( is_numeric( $_POST[ $o ] ) ) {
											$bg_data = esc_url( $_POST[ $o ] );
										} else {
											$bg_data = absint( $_POST[ $o ] );
										}

									} else {
										$data = sanitize_text_field( $bg_data );
									}

									update_post_meta( $post_id, $o, $bg_data );

								} else {

									delete_post_meta( $post_id, $o );
								}
							}
						} // end background

						elseif ( 'font' === $type ) {

							$video_options = array( 'font_color', 'font_name', 'font_weight', 'font_transform', 'font_style', 'font_letter_spacing' );

							foreach ( $video_options as $s ) {

								$o = $field_id . '_' . $s;

								if ( isset( $_POST[ $o ] ) ) {

									update_post_meta( $post_id, $o, $_POST[ $o ] );
								} else {

									delete_post_meta( $post_id, $o );
								}
							}
						} // end font

						else {
							$old = get_post_meta( $post_id, $field_id, true );
							$new = '';

							if ( isset( $_POST[ $field_id ] ) ) {

								if ( 'int' == $type ) {

									$new = absint( $_POST[ $field_id ] );

								} elseif ( 'editor' == $type ) {

									$new = $_POST[ $field_id ];

								} else {

									$new = sanitize_text_field( $_POST[ $field_id ] );
								}
							}

							update_post_meta( $post_id, $field_id, $new );

							// if ( $new && $new != $old ) {

							// 	update_post_meta( $post_id, $field_id, $new );

							// } elseif ( '' == $new && $old ) {

							// 	delete_post_meta( $post_id, $field_id, $old );
							// }
						}
					} // end foreach
				}
			}
		}
	}

	public function get_url_from_attachment_id( $id, $size = 'thumbnail' ) {
		$src = wp_get_attachment_image_src( $id, $size );
		if ( isset( $src[0] ) ) {
			return esc_url( $src[0] );
		}
	}

	/**
	 * Get post type name
	 */
	public function get_post_type_name() {
		$post = get_queried_object();
		$post_type = get_post_type_object( get_post_type( $post ) );

		return $post_type->labels->singular_name;
	}

	/**
	 * Clean a list
	 *
	 * Remove first and last comma of a list and remove spaces before and after separator
	 *
	 * @param string $list
	 * @return string $list
	 */
	public function clean_list( $list, $separator = ',' ) {
		$list = str_replace( array( $separator . ' ', ' ' . $separator ), $separator, $list );
		$list = ltrim( $list, $separator );
		$list = rtrim( $list, $separator );
		return $list;
	}

	/**
	 * sanitize_html_class works just fine for a single class
	 * Some times le wild <span class="blue hedgehog"> appears, which is when you need this function,
	 * to validate both blue and hedgehog,
	 * Because sanitize_html_class doesn't allow spaces.
	 *
	 * @uses sanitize_html_class
	 * @param (mixed: string/array) $class   "blue hedgehog goes shopping" or array("blue", "hedgehog", "goes", "shopping")
	 * @param (mixed) $fallback Anything you want returned in case of a failure
	 * @return (mixed: string / $fallback )
	 */
	public function sanitize_html_classes( $class, $fallback = null ) {
		// Explode it, if it's a string
		if ( is_string( $class ) ) {
			$class = explode( ' ', $class);
		}

		if ( is_array( $class ) && count( $class ) > 0 ) {
			$class = array_unique( array_map( 'sanitize_html_class', $class ) );
			return trim( implode( ' ', $class ) );
		}
		else {
			return trim( sanitize_html_class( $class, $fallback ) );
		}
	}

	/**
	 * Enqueue jquery UI tabs
	 */
	public function scripts() {

		$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
		$version = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? time() : WMBOX_VERSION;

		/* Admin styles */
		wp_enqueue_style( 'wmbox-admin', WMBOX_URI . '/assets/css/admin/admin' . $suffix . '.css', array(), $version );

		/* Admins scripts */
		wp_enqueue_media();
		wp_enqueue_script( 'jquery-ui-tabs' );
		wp_enqueue_script( 'wmbox-admin', WMBOX_URI . '/assets/js/admin/admin.js', array( 'jquery', 'jquery-ui-sortable', 'wp-color-picker' ), $version, true );

		wp_localize_script( 'wmbox-admin', 'WolfMetaboxesAdminParams', array(
			'chooseImage' => esc_html__( 'Select an image', 'wolf-metaboxes' ),
			'chooseMultipleImage' => esc_html__( 'Select a set of images', 'wolf-metaboxes' ),
			'chooseFile' => esc_html__( 'Select a file', 'wolf-metaboxes' ),
			'confirmRemoveAllImages' => esc_html__( 'This will remove the entire image set', 'wolf-metaboxes' ),
		) );

	}
}

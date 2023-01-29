<?php
/**
 * Upgrader Class
 *
 * @author Jegstudio
 * @package zeever
 * @since 1.0.0
 */

namespace Zeever;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Block Pattern Class
 *
 * @package zeever
 */
class Upgrader {
	/**
	 * Class constructor.
	 */
	public function __construct() {
		add_action( 'admin_notices', array( $this, 'notice' ) );
		add_action( 'init', array( $this, 'determine_old_template' ) );
		add_action( 'wp_ajax_themes_upgrader_close', array( $this, 'upgrader_close' ) );
		add_action( 'wp_ajax_themes_upgrader_process', array( $this, 'upgrade_themes' ) );
	}

	/**
	 * Change option to false.
	 */
	public function upgrader_close() {
		update_option( $this->get_option_name(), false );
	}

	/**
	 * Change option to false.
	 */
	public function upgrade_themes() {
		if ( ! current_user_can( 'manage_options' ) ) {
			exit;
		}

		/**
		 * Insert front page
		*/
		$index = get_block_template( wp_get_theme()->template . '//index' );
		$front = get_block_template( wp_get_theme()->template . '//front-page' );

		if ( 'custom' === $front->source ) {
			wp_send_json(
				array(
					'flag'    => false,
					'message' => esc_html__( 'Front Page exist. If you want migrate index, please clear Front Page customization.', 'zeever' ),
				)
			);
			exit;
		}

		wp_insert_post(
			array(
				'post_name'    => 'front-page',
				'post_title'   => esc_html__( 'Front Page', 'zeever' ),
				'post_type'    => 'wp_template',
				'post_status'  => 'publish',
				'post_content' => $index->content,
				'tax_input'    => array(
					'wp_theme' => array( wp_get_theme()->template ),
				),
			)
		);

		$this->upgrader_close();

		wp_send_json(
			array(
				'flag' => true,
			)
		);
		exit;
	}

	/**
	 * Get Option Name.
	 *
	 * @return string.
	 */
	public function get_option_name() {
		return wp_get_theme()->template . '_upgrader_index';
	}

	/**
	 * Check if user using old template. if yes, then insert the flag.
	 */
	public function determine_old_template() {
		if ( $this->assume_old_template() ) {
			$flag = get_option( $this->get_option_name() );

			if ( ! $flag ) {
				add_option( $this->get_option_name(), true );
			}
		}
	}

	/**
	 * Check if we can render the notice.
	 */
	public function assume_old_template() {
		$index = get_block_template( wp_get_theme()->template . '//index' );
		$front = get_block_template( wp_get_theme()->template . '//front-page' );

		return 'custom' === $index->source && 'theme' === $front->source;
	}

	/**
	 * Admin Notice.
	 */
	public function notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$flag  = get_option( $this->get_option_name() );
		$front = get_block_template( wp_get_theme()->template . '//front-page' );

		if ( $flag ) {
			?>
			<div class="notice theme-upgrade-notice">
				<h2><?php esc_html_e( 'Important', 'zeever' ); ?> <?php echo esc_html( wp_get_theme()->name ); ?> <?php esc_html_e( 'Upgrade Notice!', 'zeever' ); ?></h2>
			<?php

			if ( 'custom' === $front->source ) {
				?>
					<p><?php esc_html_e( 'We notice you are upgrading from old theme and also have updated your Front Page. If previously you have issue with data lost on your home page, please proceed with upgrading process below.', 'zeever' ); ?></p>
					<ol>
						<li><a href='<?php echo esc_url( admin_url( 'site-editor.php' ) ); ?>' target="_blank"><?php esc_html_e( 'Please visit site editor page.', 'zeever' ); ?></a></li>
						<li><?php esc_html_e( 'Before resetting, please make sure you back up your changes of Front Page template first.', 'zeever' ); ?></li>
						<li><?php esc_html_e( 'If you sure you are having this issue, you can', 'zeever' ); ?>
							<a target="_blank" href="https://img001.prntscr.com/file/img001/h0PskNncSi2E9OnDRA36nQ.png"><?php esc_html_e( 'reset the Front Page template.', 'zeever' ); ?></a>
							<?php esc_html_e( 'But don\'t reset the Index template.', 'zeever' ); ?>
						</li>
						<li><?php esc_html_e( 'After resetting, please refresh this page.', 'zeever' ); ?></li>
					</ol>
					<div class="themes-upgrade-action">
						<a class='close-notification' href="#"><?php esc_html_e( 'I don\'t have the issue', 'zeever' ); ?></a>
					</div>
				<?php
			} else {
				?>
					<p><?php esc_html_e( 'We notice you are upgrading from old theme. If you have issue with data lost on your front page, please proceed with upgrading process below.', 'zeever' ); ?></p>
					<ol>
						<li><a href='<?php echo esc_url( admin_url( 'site-editor.php' ) ); ?>' target="_blank"><?php esc_html_e( 'Please visit site editor page.', 'zeever' ); ?></a></li>
						<li><?php esc_html_e( 'Check your front page (from top to bottom), if your front page looks different than before, please click "Fix Issue" button below.', 'zeever' ); ?></li>
						<li><?php esc_html_e( 'This issue only happened for Front Page, any change you made elsewhere are still intact.', 'zeever' ); ?></li>					
						<li><?php esc_html_e( 'This process will copy the content of your Index template to your Front Page template. You can always revert back to default front page by ', 'zeever' ); ?>
						<a target="_blank" href="https://img001.prntscr.com/file/img001/h0PskNncSi2E9OnDRA36nQ.png"><?php esc_html_e( 'resetting the template.', 'zeever' ); ?></a>
					</li>
					</ol>
					<div class="themes-upgrade-action">
						<a class='upgrade-themes' href="#"><?php esc_html_e( 'Fix Issue', 'zeever' ); ?></a>
						<a class='close-notification' href="#"><?php esc_html_e( 'I don\'t have the issue', 'zeever' ); ?></a>
					</div>
				<?php
			}
			?>
			</div>
			<style>
				.theme-upgrade-notice {
					padding: 20px;
					border-left-color: #b50000;
				}

				.theme-upgrade-notice h2 {
					margin-top: 5px;
				}

				.themes-upgrade-action {
					display: flex;
					gap: 5px;
					margin-top: 15px;
				}

				.themes-upgrade-action a {
					color: #fff;
					background: #1B67A5;
					border-radius: 3px;
					cursor: pointer;
					padding: 7px 15px;
					text-decoration: none;
				}

				.themes-upgrade-action a.close-notification {
					background: green;
				}
			</style>
			<script>
					(function($) {
						$('.upgrade-themes').on('click', function() {
							var button = $(this);

							button.text('Loading...');
							var request = $.post( ajaxurl, {
								action: 'themes_upgrader_process'
							} );

							request.done(function(response) {
								if (response.flag) {
									alert('Migrating Home Page Success!');
									$('.theme-upgrade-notice').fadeOut();
								} else {
									if (response.message) {
										alert(response.message);
									}

									button.text('<?php esc_html_e( 'Fix Issue', 'zeever' ); ?>');
								}
							})
						});

						$('.close-notification').on('click', function() {
							$.post( ajaxurl, {
								action: 'themes_upgrader_close'
							} );

							$('.theme-upgrade-notice').fadeOut();
						});
					})(jQuery);
			</script>
			<?php
		}
	}
}

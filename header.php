<?php
global $header_settings, $post;

$show_sticky_banner = get_post_meta( $post->ID, 'show_sticky_banner', true );
?>

<?php

/**
 * The header for our theme
 *
 * This is the template that displays all of the <head> section and everything up until <div id="content">
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package Palm_Code
 */

?>
<!doctype html>
<html <?php language_attributes(); ?>>

<head>
	<meta charset="<?php bloginfo('charset'); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="https://gmpg.org/xfn/11">

	<?php wp_head(); ?>
	
	<!-- Google tag (gtag.js) -->
	<script async src="https://www.googletagmanager.com/gtag/js?id=G-YQD2MSD9L2"></script>
	<script>
	  window.dataLayer = window.dataLayer || [];
	  function gtag(){dataLayer.push(arguments);}
	  gtag('js', new Date());

	  gtag('config', 'G-YQD2MSD9L2');
	</script>
</head>

<body <?php body_class(); ?>>
	<?php wp_body_open(); ?>

	<div id="page" class="pc-site">

		<div id="pc-primary-overlay"></div>

		<?php if( $show_sticky_banner ): ?>
			<div class="pc-sticky-banner">
				<div class="pc-container container">
					<div class="pc-sticky-banner-content text-center">
						<?php echo get_post_meta( $post->ID, 'sticky_banner_text', true ); ?>
					</div>
				</div>
			</div>
		<?php endif; ?>

		<!-- Header -->
		<header class="pc-simple-header">
			<div class="pc-container container">

				<div class="row align-items-center flex-row-reverse flex-md-row">
					<div class="col col-md-2">
						<!-- Header Logo -->
						<div class="pc-simple-header-logo">
							<?php
							if (function_exists('the_custom_logo')) {
								the_custom_logo();
							}
							?>
						</div>
						<!-- End Header Logo -->
					</div>

					<div class="col-auto col-md-10">

						<!-- Header Menu -->
						<div class="pc-simple-header-menu">
							<div class="pc-simple-header-menu-desktop d-none d-md-block main-navigation">
								<?php
								wp_nav_menu(
									array(
										'theme_location' => 'main-menu',
										'menu_class'     => 'pc-header-main-menu d-flex align-items-center',
										'container_id'   => 'pc-mainmenu',
									)
								);
								?>
							</div>

							<!-- CTA Menu -->
							<div class="pc-simple-header-cta d-none d-md-block">
								<?php
								for ($i = 1; $i <= PALMCODE_HEADER_CTA_LENGTH; $i++) {
									if (isset($header_settings['cta-' . $i . '-button-text']) && !empty($header_settings['cta-' . $i . '-button-text'])) {
										$button_text = isset($header_settings['cta-' . $i . '-button-text']) && !empty($header_settings['cta-' . $i . '-button-text']) ? $header_settings['cta-' . $i . '-button-text'] : '';
										$button_link = isset($header_settings['cta-' . $i . '-button-link']) && !empty($header_settings['cta-' . $i . '-button-link']) ? $header_settings['cta-' . $i . '-button-link'] : '';
										$button_target = isset($header_settings['cta-' . $i . '-button-target']) && !empty($header_settings['cta-' . $i . '-button-target']) ? $header_settings['cta-' . $i . '-button-target'] : '';
										$button_class = isset($header_settings['cta-' . $i . '-button-class']) && !empty($header_settings['cta-' . $i . '-button-class']) ? $header_settings['cta-' . $i . '-button-class'] : '';

										echo sprintf('<div class="pc-header-cta__button"><a href="%s" target="%s" class="%s w-100">%s</a></div>', esc_url($button_link), esc_attr($button_target), esc_attr($button_class), esc_html($button_text));
									}
								}
								?>
							</div>

							<div class="pc-simple-header-ig d-none d-md-block">
								<?php get_social_media_html(); ?>
							</div>

							<!-- Hamburger Icon -->
							<div class="pc-simple-header-menu-hamburger-mobile d-flex justify-content-end d-md-none">
								<svg width="26" height="22" viewBox="0 0 26 22" fill="none" xmlns="http://www.w3.org/2000/svg">
									<line x1="1" y1="1" x2="24.7143" y2="1" stroke="var(--hamburger-icon-color)" stroke-width="2" stroke-linecap="round"></line>
									<line x1="1" y1="11" x2="24.7143" y2="11" stroke="var(--hamburger-icon-color)" stroke-width="2" stroke-linecap="round"></line>
									<line x1="1" y1="21" x2="24.7143" y2="21" stroke="var(--hamburger-icon-color)" stroke-width="2" stroke-linecap="round"></line>
								</svg>
							</div>

							<!-- Menu Mobile -->
							<div class="pc-simple-header-menu__mobile position-fixed">

								<!-- Close Menu -->
								<div class="pc-simple-header-menu__close">
									<svg width="30" height="30" viewBox="0 0 21 21" fill="none" xmlns="http://www.w3.org/2000/svg">
										<g>
											<line x1="2" y1="18.5858" x2="18.7685" y2="1.81726" stroke="#C19A6B" stroke-width="2" stroke-linecap="round"></line>
											<line x1="2.41421" y1="2" x2="19.1827" y2="18.7685" stroke="#C19A6B" stroke-width="2" stroke-linecap="round"></line>
										</g>
									</svg>
								</div>

								<!-- Mobile menu Logo -->
								<div class="pc-simple-header-logo__mobile d-flex mb-4 justify-content-center align-items-center">
									<?php
										if (function_exists('the_custom_logo')) {
											the_custom_logo();
										}
									?>
								</div>

								<!-- Mobile menu -->
								<?php
								wp_nav_menu(
									array(
										'theme_location' => 'main-menu',
										'menu_class' => 'pc-header-main-menu-mobile',
										'container_id' => 'pc-mainmenu-mobile',
									)
								);
								?>

								<!-- Mobile CTA -->
								<?php
								for ($i = 1; $i <= PALMCODE_HEADER_CTA_LENGTH; $i++) {
									if (isset($header_settings['cta-' . $i . '-button-text']) && !empty($header_settings['cta-' . $i . '-button-text'])) {
										$button_text = isset($header_settings['cta-' . $i . '-button-text']) && !empty($header_settings['cta-' . $i . '-button-text']) ? $header_settings['cta-' . $i . '-button-text'] : '';
										$button_link = isset($header_settings['cta-' . $i . '-button-link']) && !empty($header_settings['cta-' . $i . '-button-link']) ? $header_settings['cta-' . $i . '-button-link'] : '';
										$button_target = isset($header_settings['cta-' . $i . '-button-target']) && !empty($header_settings['cta-' . $i . '-button-target']) ? $header_settings['cta-' . $i . '-button-target'] : '';
										$button_class = isset($header_settings['cta-' . $i . '-button-class']) && !empty($header_settings['cta-' . $i . '-button-class']) ? $header_settings['cta-' . $i . '-button-class'] : '';

										echo sprintf('<div class="pc-header-cta__button pc-header-cta__button-mobile mt-4 mb-4 d-flex"><a href="%s" target="%s" class="%s w-100">%s</a></div>', esc_url($button_link), esc_attr($button_target), esc_attr($button_class), esc_html($button_text));
									}
								}
								?>

								<!-- Mobile Social Account -->
								<div class="pc-simple-header-ig mb-3">
									<?php get_social_media_html(); ?>
								</div>

								<!-- Menu Policy -->
								<?php
								wp_nav_menu(
									array(
										'theme_location' => 'footer-menu',
										'menu_class' => 'pc-header-footer-menu-mobile d-flex p-0 m-0 justify-content-center align-items-center',
										'container_id' => 'pc-footermenu-mobile',
									)
								);
								?>

							</div>
						</div>
						<!-- End Header Menu -->
					</div>
				</div>



			</div>
		</header>
		<!-- End Header -->

		<!-- Floating Coupon Container -->
		<?php if (isset($_GET['dev'])): ?>
		
			<div class="coupon-box-floating">

				<div class="coupon-box-floating-icon"></div>

				<div class="coupon-box-floating-content">
					<h4 class="coupon-box-floating-content-title mb-3">Ein bunter Moment zum Verschenken!</h4>
					<p>
						Du möchtest einen Gutschein verschenken, der genauso individuell ist wie der Mensch, der ihn bekommt?
						Bei uns kannst du Gutscheine in beliebiger Höhe direkt im Laden erwerben. So hast du volle Flexibilität , ganz ohne festen Betrag.
					</p>
					<p>
						<b>So funktioniert’s:</b><br>
						Einfach vorbeikommen, Wunschbetrag nennen und Gutschein mitnehmen. Persönlich, unkompliziert und mit Liebe gemacht.
					</p>
				</div>

			</div>
		
		<?php endif; ?>

		<!-- Start Container -->
		<div class="container pc-container page-content">
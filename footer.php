<?php

/**
 * The template for displaying the footer
 *
 * Contains the closing of the #content div and all content after.
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package Palm_Code
 */
global $footer_settings; ?>

</div>
<!-- End Container -->

<footer class="pc-site-footer">

	<!-- Footer Navigation -->
	<section class="is-site-footer__navigation">
		<div class="pc-container container">
			<div class="row">
				<?php $number_of_footer_widgets = isset($footer_settings['number-footer-widgets']) && !empty($footer_settings['number-footer-widgets']) ? $footer_settings['number-footer-widgets'] : 4; ?>
				<?php for ($i = 1; $i <= $number_of_footer_widgets; $i++) { ?>
					<div class="col"><?php dynamic_sidebar("footer_" . $i); ?></div>
				<?php } ?>
			</div>
		</div>
	</section>

	<?php if (!empty($footer_settings['enable-copyright'])) {
		$copyright_text = !empty($footer_settings['copyright-text']) ? $footer_settings['copyright-text'] : ''; ?>

		<!-- Footer Legal -->
		<section class="pc-site-footer__legal">
			<div class="pc-container container">
				<div class="row">
					<div class="col-6">
						<div class="pc-footer-legal-copyright__text">
							<p><?php echo do_shortcode($copyright_text); ?></p>
						</div>
					</div>
					<div class="col-6"></div>
				</div>
			</div>
		</section>

	<?php } ?>
</footer>

<?php get_template_part('template-parts/footer', 'scroll_to_top'); ?>


</div><!-- #page -->

<?php wp_footer(); ?>

</body>

</html>
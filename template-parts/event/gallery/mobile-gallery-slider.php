<!-- Event Gallery Mobile Only -->
<div class="pc-event-gallery__mobile-wrapper d-md-none mb-4">
    <div class="pc-event-gallery__mobile swiper">
        <div class="swiper-wrapper">
            <?php foreach ( $galleries as $gallery ) { ?>
                <div class="swiper-slide">
                    <?php echo wp_get_attachment_image( $gallery, 'large', false, [ 'class' => 'w-100' ] ); ?>
                </div>
            <?php } ?>
        </div>
        <div class="swiper-pagination position-relative mt-3"></div>
    </div>
</div>
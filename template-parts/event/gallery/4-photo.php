<?php $galleries = $args['galleries']; ?>

<div class="d-none d-md-block">
    <div class="row" id="event-gallery">
        <?php foreach ( $galleries as $gallery ) { ?>
            <div class="col-md-6 img-4">
                <?php echo wp_get_attachment_image( $gallery, 'large' ); ?>
            </div>
        <?php } ?>
    </div>
</div>

<?php include_once PC_CHILD_PATH . '/template-parts/event/gallery/mobile-gallery-slider.php'; ?>
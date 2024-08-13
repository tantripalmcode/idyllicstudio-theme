<?php $galleries = $args['galleries']; ?>

<div class="d-none d-md-block">
    <div class="row" id="event-gallery">
        <div class="col-md-6 img-5 event-gallery-featured">
            <?php echo wp_get_attachment_image( $galleries[0], 'large' ); ?>
        </div>
        
        <div class="col-md-6 img-5 event-gallery-other">
            <div class="d-flex flex-column gap-3">
                <?php $galleries = array_slice( $galleries, 1, 5 ); ?>

                <?php if( $galleries ) { ?>
                    <div class="row">
                        <?php foreach ( $galleries as $gallery ) { ?>
                            <div class="col-md-6">
                                <?php echo wp_get_attachment_image( $gallery, 'large' ); ?>
                            </div>
                        <?php } ?>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>
</div>

<?php include_once PC_CHILD_PATH . '/template-parts/event/gallery/mobile-gallery-slider.php'; ?>
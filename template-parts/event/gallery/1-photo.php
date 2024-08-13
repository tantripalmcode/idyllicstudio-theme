<?php $galleries = $args['galleries']; ?>

<?php if( $galleries ) { ?>
    <div class="row" id="event-gallery">
        <?php foreach ( $galleries as $gallery ) { ?>
            <div class="col-md-12">
                <?php echo wp_get_attachment_image( $gallery, 'large' ); ?>
            </div>
        <?php } ?>
    </div>
<?php } ?>
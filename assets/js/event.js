(function ($) {

    /**
     * Event Gallery Image Swiper
     */
    $(document).ready(function () {
        const $gallery = '.pc-event-gallery__mobile';
        if($($gallery).length > 0){
            const swiper = new Swiper($gallery, {
                slidesPerView: 1,
                spaceBetween: 24,
                autoHeight: true,
                loop: false,
                pagination: {
                    el: $gallery + " .swiper-pagination",
                    clickable: true,
                },
            });
        }
    });

})(jQuery);
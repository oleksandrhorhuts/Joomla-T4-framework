var TF_Slideshow=function(){function e(e){this.id=e,this.selector="#"+this.id,this.thumbs_selector="#thumbs_"+this.id,this.wrapper=document.querySelector(this.selector),this.options=JSON.parse(this.wrapper.dataset.options),this.maybeLoadLightbox(),this.swiper=null,this.thumbsSwiper=null}var t=e.prototype;return t.init=function(){var s=this,o=this.wrapper.querySelector(".autoplay-progress-circle svg")||!1,r=this.wrapper.querySelector(".autoplay-progress-circle span")||!1,i=parseFloat(getComputedStyle(this.wrapper).getPropertyValue("--slideshow-slides-per-view")||1),n=parseFloat(getComputedStyle(this.wrapper).getPropertyValue("--slideshow-space-between-slides")||0);1===i&&(n=0);var e=this.options.transition_effect;1<i&&"slide"!==e&&(e="slide",this.wrapper.querySelector(".swiper-cube-shadow")&&this.wrapper.querySelector(".swiper-cube-shadow").remove());var t={spaceBetween:n,loop:this.options.infinite_loop,effect:e,slidesPerView:i,on:{autoplayTimeLeft:function(e,t,i){s.options.autoplay_progress&&(o.style.setProperty("--progress",1-i),r.textContent=Math.ceil(t/1e3)+"s")},resize:function(){var e=parseFloat(getComputedStyle(s.wrapper).getPropertyValue("--slideshow-slides-per-view")||1),t=parseFloat(getComputedStyle(s.wrapper).getPropertyValue("--slideshow-space-between-slides")||0);i===e&&n===t||(s.thumbsSwiper.destroy(),s.swiper.destroy(),s.init())}}};if(this.options.autoplay&&(t.autoplay={delay:parseInt(this.options.autoplay_delay),disableOnInteraction:!1}),"arrows"===this.options.nav_controls||"arrows_dots"===this.options.nav_controls?t.navigation={nextEl:".swiper-button-next",prevEl:".swiper-button-prev"}:t.navigation={enabled:!1},"dots"===this.options.nav_controls||"arrows_dots"===this.options.nav_controls?t.pagination={el:".swiper-pagination",clickable:!0}:t.pagination={enabled:!1},this.options.keyboard_control&&(t.keyboard={enabled:!0}),this.options.show_thumbnails){var a={spaceBetween:10,slidesPerView:"auto",loop:!0};this.options.show_thumbnails_arrows&&(a.navigation={nextEl:".swiper-button-next",prevEl:".swiper-button-prev"}),this.thumbsSwiper=new Swiper(this.thumbs_selector,a),t.thumbs={swiper:this.thumbsSwiper}}this.swiper=new Swiper(this.selector,t)},t.maybeLoadLightbox=function(){if(this.options.lightbox){var e={selector:".tf-gallery-lightbox-item."+this.id,touchNavigation:!0};GLightbox(e)}},e}(),TF_Slideshows=function(){function e(){this.init()}var t=e.prototype;return t.init=function(){this.getSlideshows().forEach(function(e){new TF_Slideshow(e.id).init()})},t.getSlideshows=function(){return document.querySelectorAll(".nrf-widget.tf-slideshow-wrapper")},e}();document.addEventListener("DOMContentLoaded",function(e){new TF_Slideshows});


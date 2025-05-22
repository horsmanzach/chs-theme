jQuery(document).ready(function($) {
  // Check if Magnific Popup is available
  if (typeof $.magnificPopup === 'undefined') {
    // Load Magnific Popup dynamically if not available
    var script = document.createElement('script');
    script.src = 'https://cdnjs.cloudflare.com/ajax/libs/magnific-popup.js/1.1.0/jquery.magnific-popup.min.js';
    script.onload = function() {
      initLightbox();
    };
    document.head.appendChild(script);
    
    // Also load the CSS
    var link = document.createElement('link');
    link.rel = 'stylesheet';
    link.href = 'https://cdnjs.cloudflare.com/ajax/libs/magnific-popup.js/1.1.0/magnific-popup.min.css';
    document.head.appendChild(link);
  } else {
    initLightbox();
  }
  
  function initLightbox() {
    var $dynamicContainers = $('.usp-dynamic-image-1, .usp-dynamic-image-2, .usp-dynamic-image-3, .usp-dynamic-image-4, .usp-dynamic-image-5');
    
    if ($dynamicContainers.length > 0) {
      console.log('Found dynamic containers:', $dynamicContainers.length);
      
      // Create gallery ID
      var galleryId = 'dynamic-gallery-' + Math.floor(Math.random() * 1000);
      var items = [];
      
      // Collect all images first
      $dynamicContainers.each(function(index) {
        var $container = $(this);
        var $img = $container.find('img');
        
        if ($img.length) {
          console.log('Found image:', $img.attr('src'));
          var imgSrc = $img.attr('src');
          items.push({src: imgSrc});
          
          // Add click handler to image
          $img.css('cursor', 'pointer').on('click', function() {
            console.log('Image clicked, opening gallery at index:', index);
            openGallery(index);
          });
        }
      });
      
      function openGallery(index) {
        console.log('Opening gallery with items:', items);
        $.magnificPopup.open({
          items: items,
          type: 'image',
          gallery: {
            enabled: true
          },
          mainClass: 'mfp-with-zoom',
          callbacks: {
            open: function() {
              $('body').addClass('mfp-active');
            },
            close: function() {
              $('body').removeClass('mfp-active');
            }
          }
        }, index);
      }
    }
  }
});
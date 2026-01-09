<?php
// Exit if accessed directly
if (!defined('ABSPATH')) exit;
?>

<div id="cs-video-popup" style="display:none;">
  <div class="cs-popup-overlay"></div>
  <div class="cs-popup-content">
    <button class="cs-popup-close">
         <?php echo cs_get_svg_icon('times', 'cs-icon cs-icon-times'); ?>
    </button>
    <iframe id="cs-popup-iframe" width="100%" height="400" frameborder="0" allowfullscreen></iframe>
  </div>
</div>

<?php

/**
 * sublayout products
 *
 * @package	VirtueMart
 * @author Max Milbers
 * @link https://virtuemart.net
 * @copyright Copyright (c) 2014 VirtueMart Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL2, see LICENSE.php
 * @version $Id: cart.php 7682 2014-02-26 17:07:20Z Milbo $
 */

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Router\Route;

defined('_JEXEC') or die('Restricted access');
$products_per_row = empty($viewData['products_per_row']) ? 1 : $viewData['products_per_row'];
$currency = $viewData['currency'];
$showRating = $viewData['showRating'];
$verticalseparator = " vertical-separator";
echo shopFunctionsF::renderVmSubLayout('askrecomjs');

$ItemidStr = '';
$Itemid = shopFunctionsF::getLastVisitedItemId();
if (!empty($Itemid)) {
  $ItemidStr = '&Itemid=' . $Itemid;
}

$dynamic = false;

if (vRequest::getInt('dynamic', false) and vRequest::getInt('virtuemart_product_id', false)) {
  $dynamic = true;
}

foreach ($viewData['products'] as $type => $products) {

  $col = 1;
  $nb = 1;
  $row = 1;

  if ($dynamic) {
    $rowsHeight[$row]['product_s_desc'] = 1;
    $rowsHeight[$row]['price'] = 1;
    $rowsHeight[$row]['customfields'] = 1;
    $col = 2;
    $nb = 2;
  } else {
    $rowsHeight = shopFunctionsF::calculateProductRowsHeights($products, $currency, $products_per_row);

    if ((!empty($type) and count($products) > 0) or (count($viewData['products']) > 1 and count($products) > 0)) {
      $productTitle = vmText::_('COM_VIRTUEMART_' . strtoupper($type) . '_PRODUCT'); ?>
      <div class="<?php echo $type ?>-view">
        <h4><?php echo $productTitle ?></h4>
    <?php // Start the Output
    }
  }

  // Calculating Products Per Row
  $cellwidth = ' width' . floor(100 / $products_per_row);

  $BrowseTotalProducts = count($products); ?>

    <div class="row">
      <?php foreach ($products as $product) {
        if (!is_object($product) or empty($product->link)) {
          vmdebug('$product is not object or link empty', $product);
          continue;
        } ?>
        <div class="product vm-col<?php echo ' col-6 col-md-4 col-lg-' . 12 / $products_per_row ?>">
          <div class="spacer product-container" data-vm="product-container">
            <div class="vm-product-media-container">
              <a title="<?php echo $product->product_name ?>" href="<?php echo Route::_($product->link . $ItemidStr); ?>">
                <?php
                echo $product->images[0]->displayMediaThumb('class="browseProductImage"', false);
                ?>
              </a>
              <?php //echo $rowsHeight[$row]['customs'] 
              ?>
              <div class="vm3pr-<?php echo $rowsHeight[$row]['customfields'] ?>">
                <?php echo shopFunctionsF::renderVmSubLayout('addtocart', array('product' => $product, 'rowHeights' => $rowsHeight[$row], 'position' => array('ontop', 'addtocart'))); ?>
              </div>
            </div>

            <div class="vm-product-descr-container-<?php echo $rowsHeight[$row]['product_s_desc'] ?>">
              <h2><?php echo HTMLHelper::link($product->link . $ItemidStr, $product->product_name); ?></h2>
              <?php //echo $rowsHeight[$row]['price'] 
              ?>
              <div class="vm3pr-<?php echo $rowsHeight[$row]['price'] ?>">
                <?php echo shopFunctionsF::renderVmSubLayout('prices', array('product' => $product, 'currency' => $currency)); ?>
                <div class="clear"></div>
              </div>
            </div>

            <?php if (!empty($rowsHeight[$row]['product_s_desc'])) {
            ?>
              <p class="product_s_desc">
                <?php // Product Short Description
                if (!empty($product->product_s_desc)) {
                  echo shopFunctionsF::limitStringByWord($product->product_s_desc, 60, ' ...') ?>
                <?php } ?>
              </p>
            <?php  } ?>

            <div class="vm-product-rating-container">
              <?php echo shopFunctionsF::renderVmSubLayout('rating', array('showRating' => $showRating, 'product' => $product));
              if (VmConfig::get('display_stock', 1)) { ?>
                <span class="vmicon vm2-<?php echo $product->stock->stock_level ?>" title="<?php echo $product->stock->stock_tip ?>"></span>
              <?php }
              echo shopFunctionsF::renderVmSubLayout('stockhandle', array('product' => $product));
              ?>
            </div>



            <div class="vm-details-button">
              <?php // Product Details Button
              $link = empty($product->link) ? $product->canonical : $product->link;
              echo HTMLHelper::link($link . $ItemidStr, vmText::_('COM_VIRTUEMART_PRODUCT_DETAILS'), array('title' => $product->product_name, 'class' => 'product-details'));
              ?>
            </div>
            <?php if ($dynamic) {
              echo vmJsApi::writeJS();
            } ?>
          </div>
        </div>

      <?php } ?>

    </div>
    <?php if ((!empty($type) and count($products) > 0) or (count($viewData['products']) > 1 and count($products) > 0)) { ?>
      </div>
  <?php
      // Do we need a final closing row tag?
      //if ($col != 1) {
      // }
    }
  }

  /*if(vRequest::getInt('dynamic')){
	echo vmJsApi::writeJS();
}*/ ?>
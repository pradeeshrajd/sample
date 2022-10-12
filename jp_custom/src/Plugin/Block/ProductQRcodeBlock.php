<?php

namespace Drupal\jp_custom\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelLow;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Label\Label;
use Endroid\QrCode\Logo\Logo;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeMargin;
use Endroid\QrCode\Writer\PngWriter;

/**
 * Provides a 'ProductQRcodeBlock' block.
 *
 * @Block(
 *   id = "product_qr_code_block",
 *   admin_label = @Translation("Product QR code purchase link block"),
 *   category = @Translation("Product")
 * )
 */
class ProductQRcodeBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

   /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static($configuration, $plugin_id, $plugin_definition);
    $instance->routeMatch = $container->get('current_route_match');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $product = $this->getProduct();
    $dataUri = '';

    if ($product) {
      if ($product->hasField('field_product_purchase_link') 
        && !$product->field_product_purchase_link->isEmpty('field_product_purchase_link')) {
        $value = $product->field_product_purchase_link->getValue();
        $value = reset($value);
        $uri = $value['uri'];

        // Create QR code
        $writer = new PngWriter();

        $qrCode = QrCode::create($uri)
          ->setEncoding(new Encoding('UTF-8'))
          ->setErrorCorrectionLevel(new ErrorCorrectionLevelLow())
          ->setSize(300)
          ->setMargin(10)
          ->setRoundBlockSizeMode(new RoundBlockSizeModeMargin())
          ->setForegroundColor(new Color(0, 0, 0))
          ->setBackgroundColor(new Color(255, 255, 255));

        $result = $writer->write($qrCode);
        
        $dataUri = $result->getDataUri();
      }
    }

    $build = [
      '#theme' => 'product_qr_code',
      '#vars' => [
        'dataUri' => $dataUri,
      ],
    ];

    return $build;
  }

  /**
   * Get the current pages's product node.
   */
  protected function getProduct() {
    $node = $this->routeMatch->getParameter('node');
    if ($node instanceof \Drupal\node\NodeInterface && $node->getType() == 'product') {
      return $node;
    }

    return false;
  }
}
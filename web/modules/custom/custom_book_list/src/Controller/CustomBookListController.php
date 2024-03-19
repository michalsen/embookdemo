<?php

namespace Drupal\custom_book_list\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\image\Entity\ImageStyle;
use Drupal\Core\Url;

/**
 * Controller for the node listing page.
 */
class CustomBookListController extends ControllerBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new CustomBookListController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * Renders the content for the node listing page.
   *
   * @return array
   *   A render array representing the content.
   */
  public function content() {

    // Get the content type from the route parameters.
    $content_type = \Drupal::request()->get('book');

    $node_storage = $this->entityTypeManager->getStorage('node');
    
    // Query all nodes of the specified content type.
    $query = $node_storage->getQuery()
      ->accessCheck(TRUE) // D10 change
      ->condition('type', 'book')
      ->sort('field_published_date', 'DESC'); 
    
    $nids = $query->execute();
    
    $nodes = $node_storage->loadMultiple($nids);
    
    $rows = [];
    foreach ($nodes as $node) {

      // Create the thumbnail image URL.
      $image_fid = $node->get('field_cover_image')->target_id;
      if (!empty($image_fid)) {
         $file = File::load($image_fid);
         $file_uri = $file->getFileUri();
         
         $image_style = ImageStyle::load('thumbnail');

         if ($file) {
          $image_uri = $image_style->buildUri($file_uri);
          $image_url = \Drupal::service('file_url_generator')->generateAbsoluteString($image_uri); // D10 change
         }
      }

      // Create link to book on title
      $linked_title = $node->toLink($node->getTitle())->toString();

      // Create the table rows
      $rows[] = [
        [
          'data' => [
            '#markup' => $image_url ? '<img src="' . $image_url . '">' : '',
          ],
        ],
        $linked_title,
        $node->get('field_author')->value, 
        $node->get('field_published_date')->value
      ];
    }

    // Table Build into an array
    $table = [
      '#type' => 'table',
      '#header' => [
        $this->t('Cover'),
        $this->t('Title'),
        $this->t('Name'),
        $this->t('Year Published'),
      ],
      '#rows' => $rows,
    ];

    // Return the table.
    return $table;

  }

}
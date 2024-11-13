<?php

namespace Drupal\scanner\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Database\Connection;

/**
 * Controller for Search and Replace module.
 */
class ScannerController extends ControllerBase {

  /**
   * Drupal\Core\Datetime\DateFormatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * Drupal\Core\Database\Connection.
   *
   * @var Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Class constructor.
   */
  public function __construct(DateFormatter $dateFormatter, Connection $database) {
    $this->dateFormatter = $dateFormatter;
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
      // Load the service required to construct this class.
      $container->get('date.formatter'),
      $container->get('database'),
    );
  }

  /**
   * Queries the database and builds the results for the "Undo" listing.
   *
   * @return array
   *   A render array (table).
   */
  public function undoListing() {
    $query = $this->database->query('SELECT * from {scanner} WHERE undone = 0');
    $results = $query->fetchAll();
    $header = [
      $this->t('Date'),
      $this->t('Searched'),
      $this->t('Replaced'),
      $this->t('Count'),
      $this->t('Operation'),
    ];
    $rows = [];

    // Build the rows of the table.
    foreach ($results as $result) {
      $undo_link = Link::fromTextAndUrl($this->t('Undo'), Url::fromUri("internal:/admin/content/scanner/undo/$result->undo_id/confirm"))->toString();
      $rows[] = [
        $this->dateFormatter->format($result->time),
        $result->searched,
        $result->replaced,
        $result->count,
        $undo_link,
      ];
    }

    $table = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#attributes' => NULL,
    ];

    return $table;
  }

}

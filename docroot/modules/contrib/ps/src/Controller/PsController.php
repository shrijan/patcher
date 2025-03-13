<?php

namespace Drupal\ps\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\ps\ParagraphsStats;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Returns responses for Paragraphs Stats routes.
 */
class PsController extends ControllerBase {

  /**
   * ParagraphsStats class object.
   *
   * @var \Drupal\ps\ParagraphsStats
   */
  protected $paragraphsStats;

  /**
   * Constructs the controller object.
   *
   * @param \Drupal\ps\ParagraphsStats $paragraphsStats
   *   ParagraphsStats object.
   */
  public function __construct(ParagraphsStats $paragraphsStats) {
    $this->paragraphsStats = $paragraphsStats;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('ps.service')
    );
  }

  /**
   * Builds the response.
   */
  public function showStatsMainReport() {
    return $this->paragraphsStats->showUtilizationReport();
  }

  /**
   * Builds the response.
   */
  public function showStatsDrillDownReport(Request $request, $contentType, $paragraph, $bundle) {
    return $this->paragraphsStats->showUtilizationDrillDown($contentType, $paragraph, $bundle);
  }

  /**
   * Collects and stores paragraph fields usage data.
   */
  public function updateStructure() {
    return $this->paragraphsStats->updateStructure();
  }

  /**
   * Return a CSV file of the rendered data.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The Symfony Response.
   */
  public function exportCsv() {
    return $this->paragraphsStats->exportCsv();
  }

}

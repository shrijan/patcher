<?php

namespace Drupal\ps;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Link;
use Drupal\Core\Pager\PagerManagerInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Render\Markup;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\path_alias\AliasManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * Provides the main functionality of Paragraphs Stats module.
 */
class ParagraphsStats {

  use StringTranslationTrait;

  /**
   * Configuration settings.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected $entityFieldManager;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Current path.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPath;

  /**
   * Alias manager.
   *
   * @var \Drupal\path_alias\AliasManager
   */
  protected $aliasManager;

  /**
   * Pager class.
   *
   * @var \Drupal\Core\Pager\PagerManagerInterface
   */
  protected $pagerManager;

  /**
   * Stored paragraph names.
   *
   * @var array
   */
  protected $paraNames;

  /**
   * Stored the list of all content types.
   *
   * @var array
   */
  protected $bundleTypes;

  /**
   * The database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The dataset for utilization report.
   *
   * @var array
   */
  protected array $utilizationDateSet = [];

  /**
   * Constructs the controller object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Configuration settings.
   * @param \Drupal\Core\Entity\EntityFieldManager $entityFieldManager
   *   Field manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\Core\Path\CurrentPathStack $currentPath
   *   Current path.
   * @param \Drupal\path_alias\AliasManager $aliasManager
   *   Alias manager.
   * @param \Drupal\Core\Pager\PagerManagerInterface $pagerManager
   *   Pager manager.
   * @param \Drupal\Core\Database\Connection $database
   *   A database connection.
   */
  public function __construct(ConfigFactoryInterface $configFactory,
                              EntityFieldManager $entityFieldManager,
                              EntityTypeManagerInterface $entityTypeManager,
                              CurrentPathStack $currentPath,
                              AliasManager $aliasManager,
                              PagerManagerInterface $pagerManager,
                              Connection $database) {
    $this->configFactory = $configFactory;
    $this->entityFieldManager = $entityFieldManager;
    $this->entityTypeManager = $entityTypeManager;
    $this->currentPath = $currentPath;
    $this->aliasManager = $aliasManager;
    $this->pagerManager = $pagerManager;
    $this->database = $database;
    $this->bundleTypes = $this->getAllUsedBundleTypes();
    $this->paraNames = $this->getParaTypes();
  }

  /**
   * Get a list of the paragraph components and return as lookup array.
   *
   * @return array
   *   Machine name => label.
   */
  public function getParaTypes() {
    $paras = paragraphs_type_get_types();
    $names = [];
    foreach ($paras as $machine => $obj) {
      $names[$machine] = $obj->label();
    }
    return $names;
  }

  /**
   * Generate the export link.
   *
   * @return \Drupal\Core\Link
   *   Returns a Link object for the export.
   */
  protected function getExportLink($include_filters = TRUE) {
    $attributes = ['class' => ['button']];
    $params = [];
    $url = Url::fromRoute(
      'ps.export', $params,
      ['attributes' => $attributes]
    );
    return Link::fromTextAndUrl('Export to CSV', $url);
  }

  /**
   * Returns a CSV file with rendered data.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Returns a CSV file for download.
   */
  public function exportCsv() {
    // Package up the data into rows for the CSV.
    $export_data = $this->getUtilizationTabularData();
    // Serve the CSV file.
    return $this->serveExportFile($export_data);
  }

  /**
   * Generates the file and returns the response.
   *
   * @param array $data
   *   A multidimensional array containing the CSV data.
   * @param string $filename
   *   The name of the file to create. Will auto-generate
   *   a filename if none is provided.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Returns a Response object formatted for the CSV.
   */
  protected function serveExportFile(array $data, $filename = NULL) {
    if (empty($filename) || !is_string($filename)) {
      $filename = 'paragraphs-stats-report--' . date('Y-M-d-H-i-s') . '.csv';
    }

    $formatted_data = $this->formatExportData($data);
    if (empty($formatted_data)) {
      // @todo Add in some error handling and possibly watchdog warnings.
      $formatted_data = '';
    }

    // Generate the CSV response to serve up to the browser.
    $response = new Response();
    $disposition = $response->headers->makeDisposition(
      ResponseHeaderBag::DISPOSITION_ATTACHMENT,
      $filename
    );
    $response->headers->set('Content-Disposition', $disposition);
    $response->headers->set('Content-Type', 'text/csv');
    $response->headers->set('Expires', 0);
    $response->headers->set('Content-Transfer-Encoding', 'binary');
    $response->setContent($formatted_data);

    return $response;
  }

  /**
   * Convert a multidimensional array into a string for CSV export purposes.
   *
   * @param array $raw
   *   A multidimensional array containing the CSV data.
   *
   * @return string|bool
   *   Returns the data compacted into a single string for CSV export purposes.
   *   If the raw data was not an array or empty, returns FALSE.
   */
  protected function formatExportData(array $raw) {
    if (!is_array($raw) || empty($raw)) {
      return FALSE;
    }

    $data = [];
    if (!empty($raw['header'])) {
      $data[] = '"' . implode('","', $this->setCsvHyperlink($raw['header'])) . '"';
    }

    foreach ($raw['rows'] as $key => $row) {
      if (is_array($row)) {
        $dataRow = '';
        foreach ($row as $c => $cell) {
          if (is_array($cell)) {
            $cellDate = $cell['data'] ?? '';
          }
          else {
            $cellDate = $cell;
          }
          // Format links.
          $cellDate = $this->setCsvHyperlink($cellDate);
          $dataRow .= sprintf('"%s",', $cellDate);
        }
        $data[] = $dataRow;
      }
    }

    return implode("\n", $data);
  }

  /**
   * Formats HTML A tag in HYPERLINK function to use in CSVs.
   *
   * @return string|null
   *   Formatted string.
   */
  private function setCsvHyperlink($string) {
    return preg_replace('/<a href="(https:\/\/[\w.\/?=]+[\w\-\/?=]+)">([\s\w\[\]\(\)]+)?<\/a>/', '=HYPERLINK(""$1"", ""$2"")', $string);
  }

  /**
   * Return a rendered table ready for output.
   *
   * @return array
   *   Array of data.
   */
  public function showUtilizationReport() {
    $table = $this->formatUtilizationTable();
    // Build report page.
    $btn['export_button'] = [
      '#type' => 'markup',
      '#markup' => $this->t('<div>@val</div>', [
        '@val' => $this->getExportLink()->toString(),
      ]),
    ];
    $btn['run_button'] = [];
    // Only show this button to users w/proper access or superuser.
    $currentUser = \Drupal::currentUser();
    if ($currentUser->id() == 1 || $currentUser->hasPermission('administer paragraphs stats configuration')) {
      $btn['update_button'] = [
        '#type' => 'markup',
        '#markup' => $this->t('<div><a class="button" href="/admin/reports/paragraphs-stats-report/update-structure" onclick="return confirm(\'Update the data structure of the fields to build the report?\')">Update the data structure of the fields to build the report</a></div>'),
      ];
    }

    return [
      $btn,
      $table,
    ];
  }

  /**
   * Format the stored dataset into a rendered table.
   *
   * @return array
   *   Table render array.
   */
  public function formatUtilizationTable() {
    $metaCount = $this->getMetaDataCount();
    if (empty($metaCount)) {
      $table['legend'] = [
        '#type' => 'markup',
        '#markup' => $this->t("<p><b>Please update the data structure to show the report.</b></p>"),
      ];
      return $table;
    }

    // Get the main dataset.
    $tabular = $this->getUtilizationTabularData();

    // Table output.
    $table['table'] = [
      '#type' => 'table',
      '#title' => $this->t('Paragraphs Stats'),
      '#header' => $tabular['header'],
      '#sticky' => TRUE,
      '#attributes' => [
        'id' => 'paragraphs-stats-report',
      ],
      '#rows' => $tabular['rows'],
      '#empty' => $this->t('No components found. <br /> You may need an Admin user to enable content types on the "Settings" tab and click the "Update Report Data" button.'),
    ];
    $table['legend'] = [
      '#type' => 'markup',
      '#markup' => $this->t("<p>Legend:<br><b>n/a</b> - the Paragraph isn't available for the content type.</p>"),
    ];
    return $table;
  }

  /**
   * Returns count of entities.
   *
   * @return array|int
   *   Count value.
   */
  protected function getEntityCount($entity_type, $type) {
    $query = \Drupal::entityQuery($entity_type)
      ->accessCheck(FALSE)
      ->condition('type', $type);
    $result = $query->count()->execute();
    return $result;
  }

  /**
   * Parses the dataset into tabular data.
   *
   * @return array
   *   Returns an array of tabular data.
   */
  protected function getUtilizationTabularData($show_links = TRUE) {
    $rows = [];
    // Prepare Header.
    $header = [
      '__par' => $this->t('Paragraph'),
      '__par_name' => $this->t('Paragraph machine name'),
    ];

    // Get list of content types to report on.
    foreach ($this->bundleTypes as $name => $type) {
      $type_count = $this->getEntityCount($type['type'], $type['bundle']);
      $header_label = sprintf('%s [%s] (%d)', $type['label'], $type['type'], $type_count);
      $link = Url::fromRoute('system.admin_content', ['type' => $type['bundle']])->toString();
      $http_host = \Drupal::request()->getSchemeAndHttpHost();
      $header[$name] = $this->t('<a href="@link">@label</a>', [
        '@link' => $http_host . $link,
        '@label' => $header_label,
      ]);
    }

    // Get the main dataset for the report.
    $sqlCore = $this->getSqlCore();
    $query = "SELECT t.*,
      IF(t.cnt > :ul4, 4, IF(t.cnt > :ul3, 3, IF(t.cnt > :ul2, 2, IF(t.cnt > :ul1, 1, 0)))) as usage_level
      FROM (
        SELECT ti.*, COUNT(*) AS cnt
        FROM (" . $sqlCore . ") ti
        WHERE ti.entity_type IS NOT NULL
          AND ti.is_active <> ''
        GROUP BY ti.type, ti.parent_type, ti.entity_type
        ORDER BY ti.type
      ) t";

    $minMaxRange = $this->getMinMax();
    $this->utilizationDateSet = $this->database->query($query, [
      ':ul4' => $minMaxRange['cnt_4'],
      ':ul3' => $minMaxRange['cnt_3'],
      ':ul2' => $minMaxRange['cnt_2'],
      ':ul1' => $minMaxRange['cnt_min'],
    ])->fetchAll();
    if (!empty($this->utilizationDateSet)) {

      foreach ($this->utilizationDateSet as $rec) {
        $compositeType = $rec->parent_type . ':' . $rec->entity_type;
        $rows[$rec->type]['__par'] = $this->paraNames[$rec->type];
        $rows[$rec->type]['__par_name'] = $rec->type;

        // Prepare a full row.
        foreach ($this->bundleTypes as $cType => $typeData) {
          if (!isset($rows[$rec->type][$cType])) {
            $rows[$rec->type][$cType] = 'todo';
          }
        }

        $rows[$rec->type][$compositeType] = [
          'data' => $show_links ? $this->getDrillDownLink($rec->cnt, $rec->parent_type, $rec->type, $rec->entity_type) : $rec->cnt,
          'class' => ['v-' . $rec->usage_level],
        ];
      }
    }
    // Check empty values.
    foreach ($this->paraNames as $machine => $label) {
      // Add missed paragraphs.
      if (!isset($rows[$machine]['__par'])) {
        $rows[$machine]['__par'] = $label;
        $rows[$machine]['__par_name'] = $machine;
      }
      // Add missed counts for paragraphs.
      foreach ($this->bundleTypes as $name => $typeData) {
        if ((isset($rows[$machine][$name]) && $rows[$machine][$name] == 'todo') || !isset($rows[$machine][$name])) {
          $rows[$machine][$name] = $this->getZeroNa($machine, $typeData['bundle'], $typeData['type']);
        }
      }
    }
    return compact('header', 'rows');
  }

  /**
   * Returns table data for Drill Down report.
   *
   * @param string $paragraph
   *   A machine name of paragraph.
   * @param string $bundle
   *   A machine name of bundle.
   * @param string $type
   *   A machine name of an entity type.
   *
   * @return array
   *   Array of data to build report.
   */
  protected function getDrillDownTable(string $paragraph, string $bundle, string $type = 'node') {
    $paragraph = Xss::filter($paragraph);
    $bundle = Xss::filter($bundle);
    $type = Xss::filter($type);
    $rows = [];
    $total = 0;

    // Get the main dataset for the report.
    $sqlCore = $this->getSqlCore($type);
    $query = "SELECT t.*, COUNT(*) AS occurrence
      FROM (" . $sqlCore . ") t
      WHERE t.entity_type IS NOT NULL
        AND t.is_active <> ''
        AND t.parent_type = :type
        AND t.type = :par
        AND t.entity_type = :bun
      GROUP BY t.parent_id
      ORDER BY occurrence DESC";

    $ddData = $this->database->query($query, [
      ':type' => $type,
      ':par' => $paragraph,
      ':bun' => $bundle,
    ])->fetchAll();
    if (!empty($ddData)) {
      foreach ($ddData as $i => $rec) {
        $fieldName = $rec->parent_field_name ?? '-';
        if ($type == 'node') {
          $alias = $this->aliasManager->getAliasByPath('/node/' . $rec->parent_id);
          $link = $this->t('<a href="@alias">@alias</a>', ['@alias' => $alias]);
          $control = $this->t('<a href="/node/@link/edit">/node/@link/edit</a>', [
            '@link' => $rec->parent_id,
          ]);
        }
        elseif ($type == 'block_content') {
          $alias = $this->aliasManager->getAliasByPath('/block/' . $rec->parent_id);
          $link = $this->t('<a href="@alias">@alias</a>', ['@alias' => $alias]);
          $control = $this->t('<a href="/block/@link">@label</a>', [
            '@link' => $rec->parent_id,
            '@label' => $alias,
          ]);
        }
        elseif ($type == 'paragraph') {
          //$link = $rec->parent_id . ' - todo -' ;
          $link = sprintf('Paragraph ID %s', $rec->parent_id);
          $control = $this->t('<a href="/admin/content/paragraphs/@id/edit">@id/edit</a>', ['@id' => $rec->parent_id]);
        }

        $row = [
          'url' => $link,
          'field' => $fieldName,
          'occurrence' => $rec->occurrence,
          'control' => $control,
        ];
        $total = $total + $rec->occurrence;

        $rows[] = $row;
      }
    }

    $row = [
      'data' => [
        'url' => 'TOTAL',
        'field' => '',
        'occurrence' => $total,
        'control' => '',
      ],
      'class' => ['footer-total'],
    ];

    $rows[] = $row;

    // Prepare Header.
    $header = [
      'url' => $this->t('URL/Title'),
      'field' => $this->t('Field'),
      'occurrence' => $this->t('Occurrence'),
      'control' => $this->t('Edit link'),
    ];
    return compact('header', 'rows');
  }

  /**
   * Show the Drill-Down report page.
   *
   * @return array[]
   *   Array for page render.
   */
  public function showUtilizationDrillDown($contentType, $paragraph, $bundle) {
    $paragraph = Xss::filter($paragraph);
    $bundle = Xss::filter($bundle);
    $contentType = Xss::filter($contentType);
    $table = $this->getDrillDownTable($paragraph, $bundle, $contentType);
    // Table output.
    $page['back'] = [
      '#type' => 'markup',
      '#markup' => '<a href="/admin/reports/paragraphs-stats-report" class="button">< Back to main report</a>',
    ];

    $contentTypeBundle =
      $this->bundleTypes[$contentType . ':' . $bundle]['label'] ?? '-na-';
    $page['header'] = [
      '#type' => 'markup',
      '#markup' => '<p>' . $this->t('Paragraph: <b>:p_label [:p_name]</b><br>Content type (:ct): <b>:b_label [:b_name]</b>.', [
        ':p_name' => $paragraph,
        ':p_label' => $this->paraNames[$paragraph],
        ':ct' => $contentType,
        ':b_name' => $bundle,
        ':b_label' => $contentTypeBundle,
      ]) . '</p>',
    ];
    $page['table'] = [
      '#type' => 'table',
      '#title' => $this->t('Paragraphs Drill Down Report'),
      '#header' => $table['header'],
      '#sticky' => TRUE,
      '#rows' => $table['rows'],
      '#attributes' => [
        'id' => 'paragraphs-stats-report',
      ],
      '#empty' => $this->t('No data.'),
    ];
    return $page;
  }

  /**
   * Return a link for drill-down report.
   *
   * @param string $cnt
   *   Count value.
   * @param string $contentType
   *   A machine name of an entity type.
   * @param string $paragraph_name
   *   A machine name of paragraph.
   * @param string $bundle
   *   A machine name of bundle.
   *
   * @return \Drupal\Core\Render\Markup
   *   A formatted link.
   */
  private function getDrillDownLink(string $cnt, string $contentType, string $paragraph_name, string $bundle) {
    $http_host = \Drupal::request()->getSchemeAndHttpHost();
    return Markup::create('<a href="' . $http_host . '/admin/reports/paragraphs-stats-report/drill-down/' . $contentType . '/' . $paragraph_name . '/' . $bundle . '">' . $cnt . '</a>');
  }

  /**
   * Collects and stores paragraph fields usage data.
   *
   * @return array
   *   Array with notification.
   *
   * @throws \Exception
   */
  public function updateStructure() {
    $this->database->delete('paragraphs_stats_inuse')->execute();
    $types = $this->getAllUsedEntityTypes();
    foreach ($types as $type) {
      $bundles = \Drupal::service('entity_type.bundle.info')
        ->getBundleInfo($type);
      foreach ($bundles as $bundle => $bundle_label) {
        $fields = $this->entityFieldManager->getFieldDefinitions($type, $bundle);
        foreach ($fields as $field_name => $field_definition) {
          if (!empty($field_definition->getTargetBundle()) && $field_definition->getSetting('target_type') == 'paragraph') {
            $handler_settings = $field_definition->getSetting('handler_settings');
            if (isset($handler_settings['target_bundles']) && !empty($handler_settings['target_bundles'])) {
              foreach ($handler_settings['target_bundles'] as $pName => $pLabel) {
                $this->database->insert('paragraphs_stats_inuse')
                  ->fields([
                    'paragraph_name' => $pName,
                    // @todo entity_type
                    'entity_type' => $type,
                    'bundle' => $bundle,
                    'field_name' => $field_name,
                  ])
                  ->execute();
              }
            }
          }
        }
      }
    }
    $page['msg'] = [
      '#type' => 'markup',
      '#markup' => '<p>The metadata structure is updated.</p><a href="/admin/reports/paragraphs-stats-report" class="button">< Back to main report</a>',
    ];
    return $page;
  }

  /**
   * Returns count of rows in the metadata table.
   *
   * @return int
   *   Count value.
   */
  private function getMetaDataCount() {
    $query = "SELECT COUNT(*) AS cnt FROM paragraphs_stats_inuse";
    $res = $this->database->query($query)->fetchCol();
    return $res[0] ?? 0;
  }

  /**
   * Returns the usage status for the paragraph.
   *
   * @param string $paragraph_name
   *   A machine name of paragraph.
   * @param string $bundle
   *   A machine name of bundle.
   * @param string $content_type
   *   A machine name of an entity type.
   *
   * @return array
   *   Data set.
   */
  private function getZeroNa(string $paragraph_name, string $bundle, string $content_type = 'node') {
    $query = "SELECT COUNT(*) AS cnt
      FROM paragraphs_stats_inuse AS ps
      WHERE ps.entity_type = :type
        AND ps.paragraph_name = :par
        AND ps.bundle = :bun
      GROUP BY ps.paragraph_name, ps.bundle";

    $res = $this->database->query($query, [
      ':type' => $content_type,
      ':par' => $paragraph_name,
      ':bun' => $bundle,
    ])->fetchCol();

    if (!empty($res) && isset($res[0])) {
      return [
        'data' => '0',
        'class' => ['v-zero'],
      ];
    }
    else {
      return [
        'data' => 'n/a',
        'class' => ['v-na'],
      ];
    }
  }

  /**
   * Returns min-max values for the paragraphs usage.
   *
   * @return array
   *   Array of max values.
   */
  private function getMinMax() {
    $sqlCore = $this->getSqlCore();
    $query = "SELECT tt.cnt_min, ROUND(tt.cnt_max * .4) AS cnt_2, ROUND(tt.cnt_max * .6) AS cnt_3, ROUND(tt.cnt_max * .8) AS cnt_4, tt.cnt_max
      FROM (
        SELECT MIN(t.cnt) AS cnt_min, MAX(t.cnt) AS cnt_max
        FROM ( 
          SELECT COUNT(*) AS cnt
          FROM (" . $sqlCore . ") t
				WHERE t.entity_type IS NOT NULL
				  AND t.is_active <> ''
				GROUP BY t.type, t.parent_type, t.entity_type
				ORDER BY t.type
        ) t
      ) tt ";
    $res = $this->database->query($query)->fetchAssoc();
    if (!empty($res)) {
      return $res;
    }
    else {
      return [
        'cnt_min' => '0',
        'cnt_2' => '1',
        'cnt_3' => '2',
        'cnt_4' => '3',
        'cnt_max' => '4',
      ];
    }
  }

  /**
   * Returns a list of all entity types used with paragraphs.
   *
   * @return array
   *   Array of types.
   */
  private function getAllUsedEntityTypes() {
    $list = [];
    $query = "SELECT pd.parent_type
      FROM paragraphs_item_field_data AS pd
      WHERE pd.parent_type IS NOT NULL
      GROUP BY pd.parent_type";
    $res = $this->database->query($query)->fetchAll();
    if (!empty($res)) {
      foreach ($res as $i => $rec) {
        $list[] = $rec->parent_type;
      }
    }
    return $list;
  }

  /**
   * Returns a list of parent fields used with paragraphs.
   *
   * @return array
   *   List of fields.
   */
  private function getParentFields($type) {
    $list = [];
    $query = "SELECT p.field_name, 
      CONCAT(p.entity_type, '__', p.field_name) AS src_table_name, 
      CONCAT(p.field_name, '_target_id') AS src_target_id, 
      CONCAT(p.field_name, '_target_revision_id') AS src_target_revision_id
    FROM paragraphs_stats_inuse p
    WHERE p.entity_type = :type
    GROUP BY p.field_name";
    $res = $this->database->query($query, [':type' => $type])->fetchAll();
    if (!empty($res)) {
      return $res;
    }
    return $list;
  }

  /**
   * Returns a list of all entity types used with paragraphs.
   *
   * @return array
   *   List of bundles.
   */
  private function getAllUsedBundleTypes() {
    $list = [];
    $sqlCore = $this->getSqlCore();
    $query = "SELECT t.parent_type, t.entity_type, CONCAT(t.parent_type, ':', t.entity_type) AS composite_type
      FROM (" . $sqlCore . ") t
      WHERE t.entity_type IS NOT NULL
        AND t.is_active <> ''
      GROUP BY composite_type
      ORDER BY composite_type";
    $res = $this->database->query($query)->fetchAll();
    if (!empty($res)) {
      foreach ($res as $i => $rec) {
        $list[$rec->composite_type] = [
          'label' => $rec->entity_type,
          'bundle' => $rec->entity_type,
          'type' => $rec->parent_type,
        ];
      }
    }

    return $list;
  }

  /**
   * Builds main SQL query for the report.
   *
   * @return string
   *   SQL string.
   */
  private function getSqlCore($type = NULL) {
    $sqlSet = [];
    if (empty($type)) {
      $types = $this->getAllUsedEntityTypes();
    }
    else {
      $types = [$type];
    }

    foreach ($types as $key => $type) {
      $sqlPart = $this->getSqlBundle($type);
      if (!empty($sqlPart)) {
        $sqlSet[] = $sqlPart;
      }
    }
    return implode(' UNION ', $sqlSet);
  }

  /**
   * Returns a part of SQL.
   *
   * @return string
   *   SQL string.
   */
  private function getSqlBundle($type) {
    $allowedTypes = [
      'node',
      'paragraph',
      'block_content',
    ];
    $sql = '';
    if (in_array($type, $allowedTypes)) {
      $leftJoin = $this->getSqlLeftJoin($type);
      $sql = "SELECT p.id, p.type, p.status, p.parent_id, p.parent_type, p.parent_field_name, n.type AS entity_type"
        . $leftJoin['select'] . "
          FROM paragraphs_item_field_data AS p
            " . $leftJoin['join'] . "
          WHERE p.parent_type = '" . $type . "'";
    }

    return $sql;
  }

  /**
   * Returns a part of SQL.
   *
   * @return array
   *   Two string to build SQL.
   */
  private function getSqlLeftJoin($type) {
    $sql = $select = '';
    switch ($type) {
      case 'node':
        $sql = " LEFT JOIN node_field_data AS n ON n.nid = p.parent_id AND p.parent_type = 'node' ";
        break;

      case 'paragraph':
        $sql = " LEFT JOIN paragraphs_item_field_data AS n ON n.id = p.parent_id AND p.parent_type = 'paragraph' ";
        break;

      case 'block_content':
        $sql = " LEFT JOIN block_content_field_data AS n ON n.id = p.parent_id AND p.parent_type = 'block_content' ";
        break;
    }

    // Fields ...
    $fields = $this->getParentFields($type);
    foreach ($fields as $i => $rec) {
      $sql .= sprintf('LEFT JOIN %2$s f%1$d ON f%1$d.entity_id = p.parent_id AND f%1$d.%3$s = p.id AND f%1$d.%4$s = p.revision_id ',
        $i + 1,
        $rec->src_table_name,
        $rec->src_target_id,
        $rec->src_target_revision_id
      );
      $select .= sprintf(', f%1$d.bundle', $i + 1);
    }
    $select = empty($select) ? ', 1 AS is_active ' : ", CONCAT_WS('' " . $select . ") AS is_active ";
    return [
      'join' => $sql,
      'select' => $select,
    ];
  }

}

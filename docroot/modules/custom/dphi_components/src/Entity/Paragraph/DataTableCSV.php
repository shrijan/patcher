<?php

namespace Drupal\dphi_components\Entity\Paragraph;

use Drupal\bca\Attribute\Bundle;
use Drupal\dphi_components\Traits\FieldValueTrait;
use Drupal\dphi_components\Traits\PaddingControlTrait;
use Drupal\paragraphs\Entity\Paragraph;

#[Bundle(
  entityType: 'paragraph',
  bundle: 'data_table_csv',
)]
class DataTableCSV extends Paragraph {

  use FieldValueTrait;
  use PaddingControlTrait;

  protected function convertEncoding($value) {
    $value = mb_convert_encoding($value, 'UTF-8', 'Windows-1252');
    $value = str_replace(chr(194).chr(160), ' ', $value);
    if (str_starts_with($value, chr(195).chr(175).chr(194).chr(187).chr(194).chr(191))) {
      // Remove byte order mark
      $value = substr($value, 6);
    }
    return trim($value);
  }

  protected function parse_csv_file($csvfile, $delimiter, $associative = 0) {
    $csv = [];
    $rowcount = 0;
    if (!file_exists($csvfile)){
      return [];
    }
    if (($handle = fopen($csvfile, 'r')) !== false) {
      $header = fgetcsv($handle, 0, $delimiter);
      $header = array_map([$this, 'convertEncoding'], $header);
      $header_colcount = count($header);
      while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
        $row = array_map([$this, 'convertEncoding'], $row);
        $row_colcount = count($row);
        if ($row_colcount == $header_colcount) {
          if ($associative) {
            $entry = array_combine($header, $row);
          } else {
            $entry = $row;
          }
          $csv[] = $entry;
        } else {
          return;
        }
        ++$rowcount;
      }
      fclose($handle);
    } else {
      return;
    }
    return $csv;
  }

  public function getComponent() {
    $filter_1 = $this->getSingleFieldValue('field_filter_1');
    $filter_2 = $this->getSingleFieldValue('field_filter_2');
    $csv = $this->get('field_csv')->entity;
    if ($csv) {
      $uri = $csv->getFileUri();
      $stream_wrapper_manager = \Drupal::service('stream_wrapper_manager')->getViaUri($uri);
      $file_path = $stream_wrapper_manager->realpath();
      if (\Drupal::hasService('csv_helper')) {
        $delimiter = \Drupal::service('csv_helper')->detectDelimiter($file_path);
      } else {
        $delimiter = ',';
      }
      $csv_header = $this->parse_csv_file($file_path, $delimiter, 1);
      $data = $this->parse_csv_file($file_path, $delimiter, 0);
      if (isset($csv_header[0])) {
        $headers = array_keys($csv_header[0]);
        if ($filter_1 > 2) {
          $headers['h1'] = $headers[$filter_1];
        }
        if ($filter_2 > 2) {
          $headers['h2'] = $headers[$filter_2];
        }
        if (isset($headers['h1']) || isset($headers['h2'])) {
          foreach ($data as $key => $values) {
            foreach ($values as $k => $val) {
              if (isset($headers['h1'])) {
                $data[$key]['h1'] = $data[$key][$filter_1];
              }
              if (isset($headers['h2'])) {
                $data[$key]['h2'] = $data[$key][$filter_2];
              }
            }
          }
        }
      } else {
        $headers = [];
      }
    }
    return [
      'id' => $this->id(),
      'data' => $data,
      'headers' => $headers,
      'filter_1' => [
        'index' => $filter_1,
        'name' => $headers[$filter_1] ?? ''
      ],
      'filter_2' => [
        'index' => $filter_2,
        'name' => $headers[$filter_2] ?? ''
      ]
    ];
  }

}

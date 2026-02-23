<?php

namespace Drupal\decision_tool\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides the Decision Tool CSV export page.
 */
class AdminController extends ControllerBase {

  public function content(): array {
    $ids = \Drupal::entityQuery('taxonomy_term')
                             ->condition('status', 1)
                             ->condition('vid', 'decision_tool_collection')
                             ->accessCheck(TRUE)
                             ->execute();
    $collections = array_map(function ($term) {
      return [
        'id'=>$term->id(),
        'value'=>$term->getName()
      ];
    }, Term::loadMultiple($ids));
    return [
      '#theme' => 'admin_export_links',
      '#collections' => $collections
    ];
  }

  protected function exportAsCsv(string $name, array $data): Response {
    $stream = fopen('php://memory', 'rw');
    foreach ($data as $fields) {
      fputcsv($stream, $fields);
    }
    $content = stream_get_contents($stream, offset: 0);
    fclose($stream);

    $response = new Response($content);
    $response->headers->set('Content-Type', 'text/csv');
    $response->headers->set('Content-Disposition', 'attachment; filename="'.$name.'.csv"');
    return $response;
  }

  public function export(): Response {
    $id = \Drupal::request()->request->get('collection');

    $step_titles = [];
    $collection_ids = $id ? [$id] : \Drupal::entityQuery('taxonomy_term')
                                           ->condition('status', 1)
                                           ->condition('vid', 'decision_tool_collection')
                                           ->accessCheck(TRUE)
                                           ->execute();
    foreach (Term::loadMultiple($collection_ids) as $collection) {
      $collection_id = $collection->id();
      foreach ($collection->get('field_steps')->referencedEntities() as $step) {
        if (empty($step_titles[$collection_id])) {
          $step_titles[$collection_id] = [];
        }
        $step_titles[$collection_id][$step->id()] = $step->get('field_title')->getValue()[0]['value'];
      }
    }

    $query = \Drupal::entityQuery('node')
                  ->condition('status', 1)
                  ->condition('type', 'question');
    if ($id) {
      $query->condition('field_collection', $id);
    } else {
      $query->sort('field_collection');
    }
    $ids = $query->accessCheck(TRUE)
                 ->execute();
    $data = [
      [
        'collection', 'step', 'step_title', 'title', 'node_id', 'question', 'response', 'tooltip',
        'answer_text', 'next_question_id', 'next_question_title', 'confirmation_page_id', 'confirmation_page_title'
      ]
    ];
    foreach (Node::loadMultiple($ids) as $question) {
      $collection_id = $question->get('field_collection')[0]->get('target_id')->getValue();
      $step_id = $question->get('field_step')->getValue()[0]['value'];
      $data[] = [
        $collection_id,
        $step_id,
        $step_titles[$collection_id][$step_id],
        $question->get('title')->getString(),
        $question->id(),
        ApiController::stripTags($question->get('field_question')->getValue()[0]['value']),
        $question->get('field_response_title')->getString(),
        $question->get('field_tooltip')->getString()
      ];
      foreach ($question->get('field_answers')->referencedEntities() as $answer) {
        $nextQuestion = $answer->get('field_next_question')->entity;
        $confirmationPage = $answer->get('field_confirmation_page')->entity;
        $data[] = [
          '', '', '', '', '', '', '', '',
          ApiController::stripTags($answer->get('field_text')->getValue()[0]['value']),
          $nextQuestion ? $nextQuestion->id() : null,
          $nextQuestion ? $nextQuestion->get('title')->getString() : null,
          $confirmationPage ? $confirmationPage->id() : null,
          $confirmationPage ? $confirmationPage->get('title')->getString() : null
        ];
      }
    }

    return $this->exportAsCsv('decisionToolQuestions', $data);
  }
}

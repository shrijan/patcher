<?php

namespace Drupal\decision_tool\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Returns responses for Decision Tool API routes.
 */
class ApiController extends ControllerBase {

  public function search(Request $request): JsonResponse {
    $response = new JsonResponse();
    $results = [];
    $type = $request->get('type');
    if ($type == 'step') {
      $id = $request->get('id');
      $question = $id ? Node::load($id) : null;
      if ($question) {
        $collection = $question->get('field_collection')[0]->entity;
        foreach ($collection->get('field_steps')->referencedEntities() as $step) {
          $results[$step->id()] = [
            'title'=>$step->get('field_title')->getValue()[0]['value'],
            'description'=>$step->get('field_description')->getValue()[0]['value']
          ];
        }
      } else {
        $response->setStatusCode(400);
        return $response;
      }
    } else {
      if ($type == 'question') {
        $id = $request->get('id');
        $question = $id ? Node::load($id) : null;
        if ($question) {
          $ids = \Drupal::entityQuery('node')
                        ->condition('type', 'question')
                        ->condition('field_collection', $question->get('field_collection')[0]->target_id)
                        ->accessCheck(TRUE)
                        ->execute();
        } else {
          $response->setStatusCode(400);
          return $response;
        }
      } else {
        // This should not be limited to the collection
        // If it was, pages outside the collection could not become part of it in the first place
        $ids = \Drupal::entityQuery('node')
                      ->condition('type', 'page')
                      ->accessCheck(TRUE)
                      ->execute();
      }
      foreach (Node::loadMultiple($ids) as $node) {
        $results[$node->id()] = [
          'title'=>$node->getTitle()
        ];
      }
    }
    $response->setData($results);
    return $response;
  }

  public function steps(Request $request): JsonResponse {
    $id = $request->get('id');
    $collection = $id ? Term::load($id) : null;

    return new JsonResponse($collection ? array_map(function ($step) {
      return [
        'id'=>$step->id(),
        'text'=>$step->get('field_title')[0]->getValue()['value']
      ];
    }, $collection->get('field_steps')->referencedEntities()) : []);
  }

  public function autocomplete(Request $request): JsonResponse {
    $query = \Drupal::entityQuery('node')
                    ->condition('type', 'question')
                    ->condition('title', $request->get('q'), 'CONTAINS');
    $collection_id = $request->get('collection');
    if ($collection_id != '_none') {
      $query = $query->condition('field_collection', $collection_id);
    }
    $results = [];
    foreach (Node::loadMultiple($query->range(0, 10)
                                      ->accessCheck(TRUE)
                                      ->execute()) as $node) {
      $title = $node->getTitle();
      $results[] = [
        'value'=>$title.' ('.$node->id().')',
        'label'=>$title
      ];
    }
    return new JsonResponse($results);
  }

  public static function stripTags($html) {
    $html = str_replace('&nbsp;', ' ', $html);
    foreach (['span', 'div'] as $tag) {
      $html = preg_replace('/<'.$tag.' class="nsw-(tooltip|toggletip)[^<]+<\/'.$tag.'>/', '', $html);
    }
    return trim(strip_tags($html));
  }

  protected function getQuestion($node, $flowchart=false) {
    $question = $node->get('field_question')->getValue()[0]['value'];
    if ($flowchart) {
      $question = self::stripTags($question);
    }
    $result = [
      'title'=>$node->getTitle(),
      'question'=>$question,
      'responseTrackerTitle'=>$node->get('field_response_title')->getString()
    ];
    $step = Paragraph::load($node->get('field_step')->getValue()[0]['value']);
    $result['step'] = [
      'id'=>$step->id(),
      'title'=>$step->get('field_title')->getValue()[0]['value']
    ];
    $description = $step->get('field_description')->getValue();
    if ($description) {
      $result['step']['description'] = $description[0]['value'];
    }

    $answers = [];
    foreach ($node->get('field_answers')->referencedEntities() as $item) {
      $text = $item->get('field_text')->getValue()[0]['value'];
      $answer = $flowchart ? [
        'text'=>self::stripTags($text)
      ] : [
        'id'=>$item->id(),
        'text'=>$text
      ];
      $entity = $item->get('field_next_question')->entity;
      if ($entity) {
        if ($node->get('field_step')->equals($entity->get('field_step'))) {
          $answer['sameStage'] = true;
        }
      } else {
        $entity = $item->get('field_confirmation_page')->entity;
        if ($entity) {
          $answer['confirmationPage'] = true;
        }
      }
      if ($entity) {
        $link = [
          'id'=>$entity->id()
        ];
        if ($flowchart) {
          $link['node'] = $entity;
        } else {
          $link['title'] = $entity->get('title')[0]->getValue()['value'];
          if (!empty($answer['confirmationPage'])) {
            $link['url'] = $entity->toUrl()->toString();
          }
        }
        $answer['link'] = $link;
      }
      $answers[] = $answer;
    }
    if ($answers) {
      $result['answers'] = $answers;
    }
    return $result;
  }

  protected function getStepsLeft($node, $pastIds = []) {
    $maxStepsLeft = 0;
    $pastIds[] = $node->id();
    foreach ($node->get('field_answers')->referencedEntities() as $answer) {
      $nextQuestion = $answer->get('field_next_question')->entity;
      if ($nextQuestion) {
        if (in_array($nextQuestion->id(), $pastIds)) {
          // Prevent an infinite loop
          continue;
        }

        $sameStage = $node->get('field_step')->equals($nextQuestion->get('field_step'));
        $stepsLeft = ($sameStage ? 0 : 1) + $this->getStepsLeft($nextQuestion, $pastIds);
      } else if ($answer->get('field_confirmation_page')->entity) {
        $stepsLeft = 1;
      } else {
        continue;
      }
      if ($stepsLeft > $maxStepsLeft) {
        $maxStepsLeft = $stepsLeft;
      }
    }
    return $maxStepsLeft;
  }

  public function question(Request $request): JsonResponse {
    $response = new JsonResponse();

    $id = $request->get('id');
    if (!$id) {
      $response->setStatusCode(400);
      return $response;
    }

    $node = Node::load($id);
    if (!$node) {
      $response->setStatusCode(400);
      return $response;
    }

    $type = $node->getType();
    if ($type == 'question') {
      $result = $this->getQuestion($node);
      if (!$node->get('field_tooltip')->isEmpty()) {
        $result['tooltip'] = $node->get('field_tooltip')[0]->value;
      }
      $result['url'] = $node->toUrl()->toString();
      $result['stepsLeft'] = $this->getStepsLeft($node);
      $response->setData($result);
    } else if ($type == 'page') {
      $result = [
        'confirmationPage'=>true
      ];
      if (!$node->get('field_middle_left_section')->entity->get('field_section_content')->isEmpty()) {
        $view = $node->get('field_middle_left_section')->view();
        $result['body'] = \Drupal::service('renderer')->render($view);
      }
      $response->setData($result);
    } else {
      $response->setStatusCode(400);
    }
    return $response;
  }

  protected function getFlowchartItems(&$items, $id, $node) {
    $question = $this->getQuestion($node, true);
    $items[$id] = $question;
    foreach ($question['answers'] ?? [] as $i=>$answer) {
      if (empty($answer['link']['node'])) {
        continue;
      }
      $linkId = $answer['link']['id'];
      if (empty($items[$linkId])) {
        if (!empty($answer['confirmationPage'])) {
          $items[$linkId] = [
            'title'=>$answer['link']['node']->get('title')[0]->getValue()['value'],
            'confirmationPage'=>true
          ];
        } else {
          $this->getFlowchartItems($items, $linkId, $answer['link']['node']);
        }
      }
      unset($items[$id]['answers'][$i]['confirmationPage']);
      unset($items[$id]['answers'][$i]['link']['node']);
    }
  }

  public function flowchart(Request $request): JsonResponse {
    $id = $request->get('id');
    $node = Node::load($id);

    $response = new JsonResponse();
    if ($node && $node->getType() == 'question') {
      $collection = $node->get('field_collection')[0]->entity;
      $steps = array_map(function ($step) {
        return $step->id();
      }, $collection->get('field_steps')->referencedEntities());

      $items = [];
      $this->getFlowchartItems($items, $id, $node);
      $response->setData(compact('steps', 'items'));
    } else {
      $response->setStatusCode(400);
    }
    return $response;
  }

  public function save(Request $request): JsonResponse {
    $json = json_decode($request->getContent());
    $node = Node::load($json->id);

    $response = new JsonResponse();
    if ($node && $node->getType() == 'question') {
      $existingAnswers = $node->get('field_answers')->referencedEntities();
      $newAnswers = [];
      $responseData = [];
      foreach ($json->answers ?? [] as $jsonAnswer) {
        $data = [
          'field_text'=>$jsonAnswer->text,
          'field_next_question'=>null,
          'field_confirmation_page'=>null
        ];
        if (!empty($jsonAnswer->newStep) && !empty($jsonAnswer->newQuestion) && !empty($jsonAnswer->newResponseTrackerTitle)) {
          $link = Node::create([
            'type'=>'question',
            'field_collection'=>$node->get('field_collection')->entity,
            'field_step'=>$jsonAnswer->newStep,
            'title'=>$jsonAnswer->newQuestion,
            'field_question'=>$jsonAnswer->newQuestion,
            'field_response_title'=>$jsonAnswer->newResponseTrackerTitle
          ]);
          $link->save();
          $data['field_next_question'] = $link;

          $responseData['questionId'] = $link->id();
        } else if (!empty($jsonAnswer->link)) {
          $link = Node::load($jsonAnswer->link);
          $type = $link->getType();
          if ($type == 'question') {
            $data['field_next_question'] = $link;
          } else if ($type == 'page') {
            $data['field_confirmation_page'] = $link;
          } else {
            $response->setStatusCode(400);
          }
        }
        if ($jsonAnswer->id) {
          foreach ($existingAnswers as $existingAnswer) {
            if ($existingAnswer->id() == $jsonAnswer->id) {
              // Update
              foreach ($data as $field=>$value) {
                $existingAnswer->set($field, $value);
              }
              $existingAnswer->save();
              $newAnswers[] = $existingAnswer;
            }
          }
        } else {
          // Create
          $data['type'] = 'answer';
          $paragraph = Paragraph::create($data);
          $paragraph->save();
          $newAnswers[] = $paragraph;

          $responseData['answerId'] = $paragraph->id();
        }
      }
      $newAnswerIds = array_map(function ($newAnswer) {
        return $newAnswer->id();
      }, $newAnswers);
      foreach ($existingAnswers as $existingAnswer) {
        if (!in_array($existingAnswer->id(), $newAnswerIds)) {
          // Delete
          $existingAnswer->delete();
        }
      }
      $node->set('field_answers', $newAnswers);

      $node->set('field_step', $json->step);

      foreach ([
        'field_question'=>'question',
        'field_response_title'=>'responseTrackerTitle'
      ] as $field=>$k) {
        $node->set($field, $json->$k ?? null);
      }
      $node->save();

      $response->setData($responseData);
    } else {
      $response->setStatusCode(400);
    }
    return $response;
  }

  protected static function getStartingQuestionIds($collection_id = null) {
    // Get all questions
    $query = \Drupal::entityQuery('node')
                    ->condition('type', 'question');
    if ($collection_id) {
      $query = $query->condition('field_collection', $collection_id);
    }
    $ids = $query->sort('changed', 'DESC')
                 ->accessCheck(TRUE)
                 ->execute();

    // Exclude all questions linked to from other questions
    $paragraph_ids = \Drupal::entityQuery('paragraph')
                         ->condition('type', 'answer')
                         ->accessCheck(TRUE)
                         ->execute();
    foreach (Paragraph::loadMultiple($paragraph_ids) as $paragraph) {
      $link = $paragraph->get('field_next_question')->getValue();
      if ($link) {
        $ids = array_filter($ids, fn($id) => $id != $link[0]['target_id']);
      }
    }
    return $ids;
  }

  public function startingQuestions(Request $request): JsonResponse {
    $collection_id = $request->get('collection');
    $questions = [];
    foreach (Node::loadMultiple(self::getStartingQuestionIds($collection_id)) as $node) {
      $questions[] = [
        'id'=>$node->id(),
        'text'=>$node->getTitle()
      ];
    }
    return new JsonResponse($questions);
  }

  public static function getOrphanQuestionIds() {
    $ids = self::getStartingQuestionIds();

    // Exclude all questions linked to from a page
    $paragraph_ids = \Drupal::entityQuery('paragraph')
                         ->condition('type', 'decision_tool')
                         ->accessCheck(TRUE)
                         ->execute();
    foreach (Paragraph::loadMultiple($paragraph_ids) as $paragraph) {
      $link = $paragraph->get('field_question')->getValue();
      if ($link) {
        $ids = array_filter($ids, fn($id) => $id != $link[0]['target_id']);
      }
    }
    return $ids;
  }

  public function orphan(Request $request): JsonResponse {
    return new JsonResponse([
      'present'=>!empty(self::getOrphanQuestionIds())
    ]);
  }
}

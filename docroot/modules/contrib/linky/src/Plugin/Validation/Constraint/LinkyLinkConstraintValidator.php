<?php

namespace Drupal\linky\Plugin\Validation\Constraint;

use Drupal\link\LinkItemInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates a linky link.
 */
class LinkyLinkConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritDoc}
   */
  public function validate($value, Constraint $constraint) {
    // This validator is applied to the linky 'link' field which has a
    // cardinality of 1, therefore we only need the first item.
    /** @var \Drupal\linky\Plugin\Validation\Constraint\LinkyLinkConstraint $constraint */
    if ((!$item = $value->first()) ||
      !$item instanceof LinkItemInterface) {
      return;
    }
    /** @var \Drupal\Core\Url $url */
    $url = $item->getUrl();
    $uri = $url->getUri();

    // If tel: links are not allowed and one exists, add a violation.
    if (parse_url($uri, PHP_URL_SCHEME) === 'tel') {
      if (empty($constraint->settings['telephone'])) {
        $this->context->buildViolation($constraint->notSupportedMessage)->setParameters(['@uri' => $uri])->atPath('0.uri')->addViolation();
      }
      return;
    }

    // If mailto: links are not allowed and one exists, add a violation.
    if (parse_url($uri, PHP_URL_SCHEME) === 'mailto') {
      if (empty($constraint->settings['email'])) {
        $this->context->buildViolation($constraint->notSupportedMessage)->setParameters(['@uri' => $uri])->atPath('0.uri')->addViolation();
      }
      return;
    }

    // If the link is not external or it is invalid, add a violation.
    if (!$url->isExternal() ||
      !(bool) filter_var($uri, FILTER_VALIDATE_URL)) {
      $this->context->buildViolation($constraint->invalidMessage)->setParameters(['@uri' => $uri])->atPath('0.uri')->addViolation();
    }
  }

}

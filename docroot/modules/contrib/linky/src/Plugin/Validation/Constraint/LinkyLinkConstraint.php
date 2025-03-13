<?php

namespace Drupal\linky\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Constraint for validating a linky link.
 *
 * @Constraint(
 *   id = "LinkyLink",
 *   label = @Translation("Linky link", context = "Validation"),
 * )
 */
class LinkyLinkConstraint extends Constraint {

  /**
   * The settings for the constraint.
   *
   * @var array
   */
  public $settings = [];

  /**
   * The invalid constraint message.
   *
   * @var string
   */
  public $invalidMessage = "The link @uri' is invalid.";

  /**
   * The not supported constraint message.
   *
   * @var string
   */
  public $notSupportedMessage = "The link @uri' is not supported.";

}

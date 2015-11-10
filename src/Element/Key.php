<?php

/**
 * @file
 * Contains \Drupal\key\Element\Key.
 */

namespace Drupal\key\Element;

use Drupal\Core\Render\Element\Select;
use Drupal\key\KeyRepository;

/**
 * Provides a form element for a select-like field containing keys.
 *
 * Properties:
 * - #options: An associative array, where the keys are the returned values
 *   for each option, and the values are the options to be shown in the
 *   drop-down list.
 * - #empty_option: The label that will be displayed to denote no selection.
 * - #empty_value: The value of the option that is used to denote no selection.
 *
 * @FormElement("key")
 */
class Key extends Select {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $info = parent::getInfo();
    $class = get_class($this);
    array_unshift($info['#process'], array($class, 'processKey'));

    return $info;
  }

  /**
   * Processes a key form element.
   *
   * @param array $element
   *   The form element to process. For properties, see
   *   \Drupal\Core\Render\Element\Select.
   * @param FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array
   *   The processed element.
   *
   * @see _form_validate()
   */
  public static function processKey(&$element, FormStateInterface $form_state, &$complete_form) {
    // TODO: Retrieve available keys and define them as options.
    // $element['#options'] = array();

    return $element;
  }

}

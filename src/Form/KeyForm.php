<?php

/**
 * @file
 * Contains Drupal\key\Form\KeyForm.
 */

namespace Drupal\key\Form;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class KeyForm.
 *
 * @package Drupal\key\Form
 */
class KeyForm extends EntityForm {

  /**
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $manager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('plugin.manager.key.key_provider'));
  }

  function __construct(PluginManagerInterface $manager) {
    $this->manager = $manager;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';
    $key_providers = [];
    foreach ($this->manager->getDefinitions() as $plugin_id => $definition) {
      $key_providers[$plugin_id] = (string) $definition['title'];
    }

    /** @var $key \Drupal\key\KeyInterface */
    $key = $this->entity;
    $form['#tree'] = TRUE;
    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $key->label(),
      '#description' => $this->t("Label for the Key."),
      '#required' => TRUE,
    );

    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $key->id(),
      '#machine_name' => array(
        'exists' => '\Drupal\key\Entity\Key::load',
      ),
      '#disabled' => !$key->isNew(),
    );

    $form['key_provider'] = array(
      '#type' => 'select',
      '#title' => $this->t('Key Provider'),
      '#options' => $key_providers,
      '#empty_option' => t('Select Key Provider'),
      '#empty_value' => '',
      '#ajax' => [
        'callback' => [$this, 'getKeyProviderForm'],
        'event' => 'change',
        'wrapper' => 'key-type-form',
      ],
      '#required' => TRUE,
      '#default_value' => $key->getKeyProvider(),
    );

    $form['key_settings'] = [
      '#prefix' => '<div id="key-type-form">',
      '#suffix' => '</div>',
    ];
    if ($this->manager->hasDefinition($key->getKeyProvider())) {
      // @todo compare ids to ensure appropriate plugin values.
      $plugin = $this->manager->createInstance($key->getKeyProvider(), $key->getKeySettings());
      $form['key_settings'] += $plugin->buildConfigurationForm([], $form_state);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $plugin_settings = (new FormState())->setValues($form_state->getValue('key_settings'));
    $plugin = $this->manager->createInstance($form_state->getValue('key_provider'), []);
    $plugin->submitConfigurationForm($form, $plugin_settings);
    $form_state->setValue('key_settings', $plugin->getConfiguration());
    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Only run key settings validation if the form is being submitted
    if ($form_state->isSubmitted()) {
      $plugin_settings = (new FormState())->setValues($form_state->getValue('key_settings'));
      $plugin = $this->manager->createInstance($form_state->getValue('key_provider'), []);
      $plugin->validateConfigurationForm($form, $plugin_settings);
      // Reinject errors from $plugin_settings into $form_state
      foreach ($plugin_settings->getErrors() as $field => $error) {
        $form_state->setErrorByName($field, $error);
      }
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $status = parent::save($form, $form_state);

    if ($status) {
      drupal_set_message($this->t('Saved the %label Key.', array(
        '%label' => $this->entity->label(),
      )));
    }
    else {
      drupal_set_message($this->t('The %label Key was not saved.', array(
        '%label' => $this->entity->label(),
      )));
    }
    $form_state->setRedirectUrl($this->entity->urlInfo('collection'));
  }

  /**
   * AJAX action to retrieve the appropriate key provider into the form.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @return array
   */
  public function getKeyProviderForm(array &$form, FormStateInterface $form_state) {
    return $form['key_settings'];
  }

}

<?php

namespace Drupal\lightning_scheduler\Form;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Url;
use Drupal\lightning_core\Element;
use Drupal\lightning_scheduler\Migrator;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MigrationConfirmationForm extends ConfirmFormBase {

  /**
   * The migration service.
   *
   * @var \Drupal\lightning_scheduler\Migrator
   */
  protected $migrator;

  /**
   * MigrationConfirmationForm constructor.
   *
   * @param \Drupal\lightning_scheduler\Migrator $migrator
   *   The migration service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   (optional) The messenger service.
   */
  public function __construct(Migrator $migrator, MessengerInterface $messenger = NULL) {
    $this->migrator = $migrator;

    if ($messenger) {
      $this->setMessenger($messenger);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('lightning_scheduler.migrator'),
      $container->get('messenger')
    );
  }

  /**
   * Returns the entity types that need to be migrated.
   *
   * @return string[]
   *   The labels of the entity types that need to be migrated, keyed by entity
   *   type ID.
   */
  protected function getMigrations() {
    $map = function (EntityTypeInterface $entity_type) {
      return $entity_type->getPluralLabel();
    };
    return array_map($map, $this->migrator->getMigrationsNeeded());
  }

  /**
   * Performs access check.
   *
   * @return AccessResult
   *   Allowed if the current user is droot (Drupal root).
   */
  public function access() {
    $uid = (int) $this->currentUser()->id();

    // This migration is serious business, so only droot can do it.
    return AccessResult::allowedIf($uid === 1);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'lightning_scheduler_migration_confirm_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $migrations = $this->getMigrations();

    if (empty($migrations)) {
      $this->messenger()->addStatus($this->t('Hey, nice! All migrations are completed.'));
      $form['actions']['#access'] = FALSE;
      return $form;
    }

    $form['purge'] = [
      '#type' => 'details',
      '#collapsible' => TRUE,
      '#title' => $this->t('Purge without migrating'),
      '#description' => $this->t('Purging will allow you to discard existing scheduled transitions for a particular entity type without running the migration. This is useful if you don\'t have any transitions scheduled that you want to migrate. <strong>This will permanently delete scheduled transitions and cannot be undone.</strong>'),
      '#tree' => TRUE,
      'entity_type_id' => [
        '#type' => 'select',
        '#title' => $this->t('Entity type to purge'),
        '#options' => $migrations,
      ],
      'actions' => [
        '#type' => 'actions',
        'purge' => [
          '#type' => 'submit',
          '#value' => $this->t('Purge'),
          '#submit' => [
            '::purge',
          ],
        ],
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to migrate all scheduled transitions?');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    $migrations = $this->getMigrations();

    return $this->t(
      'You are about to migrate scheduled transitions for all @entity_types. This will modify your existing content and may take a long time if you have a huge site with tens of thousands of pieces of content. <strong>You cannot undo this</strong>, so you may want to <strong>back up your database</strong> and <a href="@maintenance_mode">switch to maintenance mode</a> before continuing.',
      [
        '@entity_types' => Element::oxford($migrations),
        '@maintenance_mode' => Url::fromRoute('system.site_maintenance_mode')->toString(),
      ]
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Continue');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromRoute('<front>');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $operations = [];

    $callback = [static::class, 'migrate'];

    foreach (array_keys($this->migrator->getMigrationsNeeded()) as $entity_type_id) {
      foreach ($this->migrator->query($entity_type_id)->execute() as $item) {
        $arguments = [$entity_type_id, $item];
        array_push($operations, [$callback, $arguments]);
      }

      array_push($operations, [
        [static::class, 'complete'],
        [$entity_type_id],
      ]);
    }
    batch_set(['operations' => $operations]);
  }

  /**
   * Batch API callback to migrate a single entity.
   */
  public static function migrate($entity_type_id, \stdClass $item) {
    \Drupal::service('lightning_scheduler.migrator')
      ->migrate($entity_type_id, $item);
  }

  /**
   * Batch API callback to mark an entity type's migration as completed.
   */
  public static function complete($entity_type_id) {
    \Drupal::service('lightning_scheduler.migrator')
      ->completeMigration($entity_type_id);
  }

  /**
   * Submit function to handle purging 1.x base field data.
   *
   * @param array $form
   *   The complete form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current form state.
   */
  public function purge(array &$form, FormStateInterface $form_state) {
    $entity_type_id = $form_state->getValue(['purge', 'entity_type_id']);

    $this->migrator->purge($entity_type_id, 'scheduled_publication');
    $this->migrator->purge($entity_type_id, 'scheduled_moderation_state');

    // Mark the migration as completed.
    static::complete($entity_type_id);

    $message = $this->t('Purged scheduled transitions for @entity_type.', [
      '@entity_type' => $form['purge']['entity_type_id']['#options'][$entity_type_id],
    ]);
    $this->messenger()->addStatus($message);
  }

}

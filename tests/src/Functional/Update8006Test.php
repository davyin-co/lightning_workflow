<?php

namespace Drupal\Tests\lightning_workflow\Functional;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\views\Entity\View;

/**
 * Tests lightning_workflow_update_8006().
 *
 * @group lightning_workflow
 */
class Update8006Test extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    if (str_starts_with(\Drupal::VERSION, '10.')) {
      $core_fixture = 'drupal-9.4.0.bare.standard.php.gz';
    }
    else {
      $core_fixture = 'drupal-8.8.0.bare.standard.php.gz';
    }
    $this->databaseDumpFiles = [
      $this->getDrupalRoot() . '/core/modules/system/tests/fixtures/update/' . $core_fixture,
      __DIR__ . '/../../fixtures/Update8006Test.php.gz',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    /** @var \Drupal\Core\Entity\EntityFieldManagerInterface $field_manager */
    $base_fields = $this->container->get('entity_field.manager')
      ->getBaseFieldDefinitions('content_moderation_state');

    /** @var \Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface $updater */
    $updater = $this->container->get('entity.definition_update_manager');
    $updater->updateFieldStorageDefinition($base_fields['id']);
    $updater->updateFieldStorageDefinition($base_fields['revision_id']);
  }

  /**
   * Tests lightning_workflow_update_8006().
   */
  public function test() {
    /** @var \Drupal\Core\Entity\EntityStorageInterface $storage */
    $storage = $this->container->get('entity_type.manager')->getStorage('view');
    /** @var \Drupal\views\Entity\View $view */
    $view = $storage->load('moderation_history');
    $this->assertInstanceOf(View::class, $view);
    $display = $view->getDisplay('default');
    $this->assertArrayHasKey('moderation_state', $display['display_options']['relationships']);
    $field = $display['display_options']['fields']['moderation_state'];
    $this->assertSame('content_moderation_state_field_revision', $field['table']);
    $this->assertSame('moderation_state', $field['relationship']);
    $this->assertSame('content_moderation_state', $field['entity_type']);
    $this->assertSame('moderation_state', $field['entity_field']);

    $this->runUpdates();

    $storage->resetCache(['moderation_history']);
    $view = $storage->load('moderation_history');
    $this->assertInstanceOf(View::class, $view);
    $display = $view->getDisplay('default');
    $this->assertArrayNotHasKey('moderation_state', $display['display_options']['relationships']);
    $field = $display['display_options']['fields']['moderation_state'];
    $this->assertSame('node_field_revision', $field['table']);
    $this->assertSame('none', $field['relationship']);
    $this->assertSame('node', $field['entity_type']);
    $this->assertArrayNotHasKey('entity_field', $field);
  }

}

<?php

namespace Drupal\Tests\lightning_scheduler\Functional;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Base class for testing migration of old Lightning Scheduler data.
 */
abstract class MigrationTestBase extends UpdatePathTestBase {

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
      __DIR__ . '/../../fixtures/BaseFieldMigrationTest.php.gz',
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
   * Runs a basic test of migrating old Lightning Scheduler data.
   *
   * This doesn't really test that data integrity is preserved, so subclasses
   * should override this method and call it before asserting other things.
   */
  public function test() {
    $this->runUpdates();

    $migrations = $this->container->get('state')->get('lightning_scheduler.migrations');
    $this->assertCount(2, $migrations);
    $this->assertContains('block_content', $migrations);
    $this->assertContains('node', $migrations);

    $assert = $this->assertSession();
    $url = $assert->elementExists('named', ['link', 'migrate your existing content'])->getAttribute('href');

    $this->drupalLogin($this->rootUser);
    $this->drupalGet($url);
    $assert->statusCodeEquals(200);
    $assert->pageTextContains('Migrate scheduled transitions');
    $assert->elementExists('named', ['link', 'switch to maintenance mode']);
  }

  /**
   * Runs post-migration assertions for an entity type.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   *   The storage handler for the entity type.
   */
  protected function postMigration($entity_type_id) {
    // Now that a migration is completed, old base fields will no longer be
    // defined. Therefore, we need to clear the entity field cache in order to
    // properly load the changed content, and there should be pending entity
    // definition updates (the old base fields need to be uninstalled).
    $this->container->get('entity_field.manager')->clearCachedFieldDefinitions();

    $this->assertTrue(
      $this->container->get('entity.definition_update_manager')->needsUpdates()
    );

    return $this->container->get('entity_type.manager')->getStorage($entity_type_id);
  }

}

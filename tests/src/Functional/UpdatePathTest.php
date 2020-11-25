<?php

namespace Drupal\Tests\lightning_workflow\Functional;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drush\TestTraits\DrushTestTrait;

/**
 * Tests Lightning Workflow's database update path.
 *
 * @group lightning_workflow
 * @group lightning
 */
class UpdatePathTest extends UpdatePathTestBase {

  use DrushTestTrait;

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../fixtures/drupal-8.8.0-update-from-1.0.0-rc2.php.gz',
    ];
  }

  /**
   * Tests Lightning Workflow's database update path.
   */
  public function testUpdatePath() {
    $this->runUpdates();
    $this->drush('update:lightning', [], ['yes' => NULL]);
  }

}

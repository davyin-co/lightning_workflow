<?php

namespace Drupal\Tests\lightning_workflow\Functional;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;
use Drupal\views\Entity\View;
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
    if (str_starts_with(\Drupal::VERSION, '10.')) {
      $fixture = __DIR__ . '/../../fixtures/drupal-9.4.0-update-from-1.0.0-rc2.php.gz';
    }
    else {
      $fixture = __DIR__ . '/../../fixtures/drupal-8.8.0-update-from-1.0.0-rc2.php.gz';
    }
    $this->databaseDumpFiles = [$fixture];
  }

  /**
   * Tests Lightning Workflow's database update path.
   */
  public function testUpdatePath() {
    $this->assertNull(View::load('moderated_content'));

    $this->runUpdates();
    $this->drush('update:lightning', [], ['yes' => NULL]);

    $this->assertInstanceOf(View::class, View::load('moderated_content'));
  }

}

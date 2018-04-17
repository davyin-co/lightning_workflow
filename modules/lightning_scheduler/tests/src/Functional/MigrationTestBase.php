<?php

namespace Drupal\Tests\lightning_scheduler\Functional;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

abstract class MigrationTestBase extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../fixtures/BaseFieldMigrationTest.php.gz',
    ];
  }

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

}

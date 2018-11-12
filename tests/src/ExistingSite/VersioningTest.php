<?php

namespace Drupal\Tests\lightning_workflow\ExistingSite;

use Drupal\Tests\lightning_workflow\FixtureContext;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * @group lightning
 * @group lightning_workflow
 */
class VersioningTest extends ExistingSiteBase {

  /**
   * The fixture context.
   *
   * @var \Drupal\Tests\lightning_workflow\FixtureContext
   */
  private $fixtureContext;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->fixtureContext = new FixtureContext($this->container);
    $this->fixtureContext->setUp();
    drupal_flush_all_caches();
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown() {
    $this->fixtureContext->tearDown();
    parent::tearDown();
  }

  /**
   * Tests that the edit form loads the latest revision.
   */
  public function testLatestRevisionIsLoadedByEditForm() {
    $account = $this->createUser([
      'create page content',
      'edit own page content',
      'view latest version',
      'view own unpublished content',
      'use editorial transition create_new_draft',
      'use editorial transition publish',
    ]);
    $this->drupalLogin($account);
    $this->drupalGet('/node/add/page');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->fieldExists('Title')->setValue('Smells Like Teen Spirit');
    $this->assertSession()->selectExists('moderation_state[0][state]')->selectOption('Published');
    $this->assertSession()->buttonExists('Save')->press();
    $this->assertSession()->elementExists('css', 'a[rel="edit-form"]')->click();
    $this->assertSession()->fieldExists('Title')->setValue('Polly');
    $this->assertSession()->selectExists('moderation_state[0][state]')->selectOption('Draft');
    $this->assertSession()->buttonExists('Save')->press();
    $this->assertSession()->elementExists('css', 'a[rel="edit-form"]')->click();
    $this->assertSession()->fieldValueEquals('Title', 'Polly');
  }

}

<?php

namespace Drupal\Tests\lightning_workflow\ExistingSite;

use Drupal\Tests\lightning_workflow\FixtureContext;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\workflows\Entity\Workflow;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * @group lightning
 * @group lightning_workflow
 */
class ContentTypeModerationTest extends ExistingSiteBase {

  use ContentTypeCreationTrait;

  /**
   * The fixture context.
   *
   * @var \Drupal\Tests\lightning_workflow\FixtureContext
   */
  private $fixtureContext;

  /**
   * {@inheritdoc}
   */
  protected function prepareRequest() {
    // The base implementation of this method will set a special cookie
    // identifying the Mink session as a test user agent. For this kind of test,
    // though, we don't need that.
  }

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
   * Tests access to unpublished content.
   */
  public function testUnpublishedAccess() {
    $assert_session = $this->assertSession();

    $this->createNode([
      'type' => 'test',
      'title' => 'Moderation Test 1',
      'promote' => TRUE,
      'moderation_state' => 'review',
    ]);
    $this->visit('/');
    $assert_session->statusCodeEquals(200);
    $assert_session->linkNotExists('Moderation Test 1');

    $account = $this->createUser([
      'access content overview',
      'view any unpublished content',
    ]);
    $this->drupalLogin($account);
    $this->drupalGet('/admin/content');
    $assert_session->statusCodeEquals(200);
    $this->getSession()->getPage()->clickLink('Moderation Test 1');
    $assert_session->statusCodeEquals(200);
  }

  public function testReviewerAccess() {
    $assert_session = $this->assertSession();

    $this->createNode([
      'type' => 'test',
      'title' => 'Version 1',
      'moderation_state' => 'draft',
    ]);

    $account = $this->createUser();
    $account->addRole('test_reviewer');
    $account->save();
    $this->drupalLogin($account);

    $this->drupalGet('/admin/content');
    $assert_session->statusCodeEquals(200);
    $this->getSession()->getPage()->clickLink('Version 1');
    $assert_session->statusCodeEquals(200);
    $assert_session->pageTextContains('Version 1');
  }

  /**
   * @depends testReviewerAccess
   */
  public function testLatestUnpublishedRevisionReviewerAccess() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->createNode([
      'type' => 'test',
      'title' => 'Version 1',
      'moderation_state' => 'draft',
    ]);

    $account = $this->createUser([], NULL, TRUE);
    $this->drupalLogin($account);

    $this->drupalGet('/admin/content');
    $assert_session->statusCodeEquals(200);
    $page->clickLink('Version 1');
    $assert_session->elementExists('css', 'a[rel="edit-form"]')->click();
    $page->fillField('Title', 'Version 2');
    $page->selectFieldOption('moderation_state[0][state]', 'published');
    $page->pressButton('Save');
    $assert_session->elementExists('css', 'a[rel="edit-form"]')->click();
    $page->fillField('Title', 'Version 3');
    $page->selectFieldOption('moderation_state[0][state]', 'draft');
    $page->pressButton('Save');

    $this->drupalLogout();
    $account = $this->createUser();
    $account->addRole('test_reviewer');
    $account->save();
    $this->drupalLogin($account);

    $this->drupalGet('/admin/content');
    $assert_session->statusCodeEquals(200);
    $page->clickLink('Version 2');
    $assert_session->statusCodeEquals(200);
    $assert_session->linkExists('Latest version');
  }

  /**
   * Tests that unmoderated content types have a "create new revision" checkbox.
   */
  public function testCreateNewRevisionCheckbox() {
    $assert_session = $this->assertSession();

    $account = $this->createUser([], NULL, TRUE);
    $this->drupalLogin($account);

    $this->createNode([
      'type' => $this->createContentType()->id(),
      'title' => 'Deft Zebra',
    ]);
    $this->drupalGet('/admin/content');
    $this->getSession()->getPage()->clickLink('Deft Zebra');
    $assert_session->elementExists('css', 'a[rel="edit-form"]')->click();
    $assert_session->fieldExists('Create new revision');
  }

  /**
   * Tests that moderated content does not provide publish/unpublish buttons.
   */
  public function testEnableModerationForContentType() {
    $assert_session = $this->assertSession();

    $node_type = $this->createContentType()->id();

    $account = $this->createUser([
      'administer nodes',
      "create $node_type content",
    ]);
    $this->drupalLogin($account);

    $this->visit("/node/add/$node_type");
    $assert_session->buttonExists('Save');
    $assert_session->checkboxChecked('Published');
    $assert_session->buttonNotExists('Save and publish');
    $assert_session->buttonNotExists('Save as unpublished');

    $workflow = Workflow::load('editorial');
    /** @var \Drupal\content_moderation\Plugin\WorkflowType\ContentModerationInterface $workflow_type */
    $workflow_type = $workflow->getTypePlugin();
    $workflow_type->addEntityTypeAndBundle('node', $node_type);
    $workflow->save();

    $this->getSession()->reload();
    $assert_session->buttonExists('Save');
    $assert_session->fieldNotExists('status[value]');
    $assert_session->buttonNotExists('Save and publish');
    $assert_session->buttonNotExists('Save as unpublished');
  }

  /**
   * Tests that moderated content does not have publish/unpublish actions.
   *
   * @depends testEnableModerationForContentType
   */
  public function testContentOverviewActions() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $account = $this->createUser([], NULL, TRUE);
    $this->drupalLogin($account);

    $this->createNode([
      'type' => 'test',
      'title' => 'Foo',
      'moderation_state' => 'draft',
    ]);
    $this->createNode([
      'type' => 'test',
      'title' => 'Bar',
      'moderation_state' => 'draft',
    ]);
    $this->createNode([
      'type' => 'test',
      'title' => 'Baz',
      'moderation_state' => 'draft',
    ]);

    $this->drupalGet('/admin/content');
    $page->selectFieldOption('moderation_state', 'Draft');

    $assert_session->elementExists('css', '.views-exposed-form .form-actions input[type = "submit"]')
      ->press();

    $assert_session->optionNotExists('Action', 'node_publish_action');
    $assert_session->optionNotExists('Action', 'node_unpublish_action');
  }

}

<?php

namespace Acquia\LightningExtension\Context;

use Behat\Mink\Element\DocumentElement;
use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ElementNotFoundException;
use Behat\Mink\Exception\ExpectationException;
use Drupal\DrupalExtension\Context\DrupalSubContextBase;
use Drupal\node\Entity\NodeType;
use Drupal\workflows\Entity\Workflow;
use Webmozart\Assert\Assert;

/**
 * Contains miscellaneous step definitions for testing moderation UIs.
 *
 * @internal
 *   This is an internal part of Lightning Workflow's testing system and may be
 *   changed or removed at any time without warning. External code should not
 *   extend, instantiate, or use this class in any way! If you want to use the
 *   functionality of this class, you should copy the relevant code into your
 *   own project.
 */
final class ModerationContext extends DrupalSubContextBase {

  use AwaitTrait;

  /**
   * Content types which had moderation enabled during the scenario.
   *
   * @var string[]
   */
  private $moderated = [];

  /**
   * Cleans up after the scenario.
   *
   * @AfterScenario
   */
  public function tearDown() {
    /** @var \Drupal\workflows\WorkflowInterface $workflow */
    $workflow = Workflow::load('editorial');
    Assert::isInstanceOf($workflow, '\Drupal\workflows\WorkflowInterface');

    while ($this->moderated) {
      $workflow->getTypePlugin()->removeEntityTypeAndBundle('node', array_pop($this->moderated));
    }
    $workflow->save();
  }

  /**
   * Opens the moderation sidebar.
   *
   * @When I open the moderation sidebar
   */
  public function openModerationSidebar() {
    $this->assertSession()
      ->elementExists('css', '#toolbar-bar')
      ->clickLink('Tasks');

    $this->awaitElement('.moderation-sidebar-container');
  }

  /**
   * Asserts that the "Tasks" toolbar link indicates a moderation state.
   *
   * @param string $state
   *   The label of the expected moderation state.
   *
   * @Then the current moderation state should be :state
   */
  public function assertCurrentModerationState($state) {
    $assert_session = $this->assertSession();
    $toolbar = $assert_session->elementExists('css', '#toolbar-bar');
    $current_state = $assert_session->elementExists('named', ['link', 'Tasks'], $toolbar)
      ->getAttribute('data-label');
    Assert::same($current_state, $state);
  }

  /**
   * Enables moderation for a content type.
   *
   * @param string $node_type
   *   The machine name of the content type.
   *
   * @When I enable moderation for the :node_type content type
   */
  public function addModerationToContentType($node_type) {
    /** @var \Drupal\node\NodeTypeInterface $node_type */
    $node_type = NodeType::load($node_type);
    $node_type->setThirdPartySetting('lightning_workflow', 'workflow', 'editorial');
    lightning_workflow_node_type_insert($node_type);

    array_push($this->moderated, $node_type->id());
  }

  /**
   * Asserts the presence of the moderation dashboard.
   *
   * @Then I should see a dashboard for moderating content
   */
  public function assertModerationDashboard() {
    $assert = $this->assertSession();

    $assert->elementExists('css', "[data-block-plugin-id='views_block:content_moderation_dashboard_in_review-block_1']");
    $assert->elementExists('css', "[data-block-plugin-id='views_block:content_moderation_dashboard_in_review-block_2']");
    $assert->elementExists('css', "[data-block-plugin-id='moderation_dashboard_activity']");
    $assert->elementExists('css', "[data-block-plugin-id='views_block:moderation_dashboard_recently_created-block_1']");
    $assert->elementExists('css', "[data-block-plugin-id='views_block:content_moderation_dashboard_in_review-block_3']");
    $assert->elementExists('css', "[data-block-plugin-id='views_block:moderation_dashboard_recent_changes-block_1']");
    $assert->elementExists('css', "[data-block-plugin-id='views_block:moderation_dashboard_recent_changes-block_2']");
    $assert->elementExists('css', "[data-block-plugin-id='views_block:moderation_dashboard_recently_created-block_2']");
  }

  /**
   * Autosaves the current form.
   *
   * @When I wait for my work to be autosaved
   */
  public function awaitAutosave() {
    $driver = $this->getSession()->getDriver();

    $element = $this->assertSession()
      ->elementExists('css', '#autosave-notification');

    $is_visible = $element->waitFor(20, function (NodeElement $element) {
      return $element->isVisible();
    });
    if ($is_visible == FALSE) {
      throw new ExpectationException('Expected autosave notification to appear, but it did not.', $driver);
    }

    $is_hidden = $element->waitFor(10, function (NodeElement $element) {
      return $element->isVisible() === FALSE;
    });
    if ($is_hidden == FALSE) {
      throw new ExpectationException('Expected autosave notification to disappear after saving, but it did not.', $driver);
    }
  }

  /**
   * Restores autosaved work.
   *
   * @Then I should be able to restore my work
   */
  public function assertRestoreFromAutosave() {
    /** @var \Behat\Mink\Element\NodeElement $button */
    $button = $this->getSession()
      ->getPage()
      ->waitFor(10, function (DocumentElement $page) {
        return $page->findButton('Resume editing');
      });

    if ($button) {
      $button->press();
    }
    else {
      throw new ElementNotFoundException($this->getSession()->getDriver(), 'button', 'named', 'Resume editing');
    }
  }

}

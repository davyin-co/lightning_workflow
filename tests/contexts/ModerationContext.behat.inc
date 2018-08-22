<?php

namespace Acquia\LightningExtension\Context;

use Drupal\Component\Utility\Random;
use Drupal\DrupalExtension\Context\DrupalSubContextBase;
use Drupal\workflows\StateInterface;
use Webmozart\Assert\Assert;

/**
 * Contains miscellaneous step definitions for testing moderation UIs.
 */
final class ModerationContext extends DrupalSubContextBase {

  use AwaitTrait;

  /**
   * The node created for the test.
   *
   * @var \Drupal\node\NodeInterface
   */
  private $node;

  /**
   * Cleans up after the scenario.
   *
   * @AfterScenario
   */
  public function tearDown() {
    if ($this->node) {
      $this->node->delete();
    }
  }

  /**
   * Returns the workflow type plugin for the current node.
   *
   * @return \Drupal\workflows\WorkflowTypeInterface
   *   The workflow type plugin.
   */
  private function getWorkflow() {
    Assert::notEmpty($this->node);

    /** @var \Drupal\workflows\WorkflowInterface $workflow */
    $workflow = \Drupal::service('content_moderation.moderation_information')
      ->getWorkflowForEntity($this->node);

    Assert::notEmpty($workflow);

    return $workflow->getTypePlugin();
  }

  /**
   * Returns the workflow state ID matching a label.
   *
   * @param string $state_label
   *   The state label.
   *
   * @return string
   *   The state ID.
   */
  private function getStateId($state_label) {
    $states = array_filter($this->getWorkflow()->getStates(), function (StateInterface $state) use ($state_label) {
      return $state->label() === $state_label;
    });
    Assert::count($states, 1);

    return reset($states)->id();
  }

  /**
   * Creates a node in a specific workflow state and visits it.
   *
   * @param string $content_type
   *   The content type of the node to create.
   * @param string $state_label
   *   The label of the workflow state in which to create the node.
   *
   * @When I am viewing a :content_type in the :state_label state
   */
  public function assertAtContentInState($content_type, $state_label) {
    $this->node = entity_create('node', [
      'type' => $content_type,
      'title' => (new Random)->string(),
    ]);
    $current_user = $this->getUserManager()->getCurrentUser();
    if ($current_user) {
      $this->node->setOwnerId($current_user->uid);
    }

    $this->node
      ->set('moderation_state', $this->getStateId($state_label))
      ->save();

    $this->visitPath($this->node->toUrl()->toString());
    $assert = $this->assertSession();
    $assert->statusCodeEquals(200);
    $assert->pageTextContains($this->node->getTitle());
  }

  /**
   * Asserts that the moderation sidebar works.
   *
   * @param string $state_label
   *   The label of the state to which to transition the node.
   *
   * @Then I should be able to transition to the :state_label state without leaving the page
   */
  public function assertInPlaceStateTransition($state_label) {
    Assert::notEmpty($this->node);

    $transition = $this->getWorkflow()
      ->getTransitionFromStateToState($this->node->moderation_state->value, $this->getStateId($state_label));

    $assert = $this->assertSession();
    $toolbar = $assert->elementExists('css', '#toolbar-bar');
    $toolbar->clickLink('Tasks');
    $this->awaitElement('.moderation-sidebar-container')->pressButton($transition->label());

    $assert->pageTextContains('The moderation state has been updated.');
    $current_state = $assert->elementExists('named', ['link', 'Tasks'], $toolbar)->getAttribute('data-label');

    Assert::same($current_state, $transition->to()->label());
  }

}

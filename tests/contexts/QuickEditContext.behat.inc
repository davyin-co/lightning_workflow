<?php

namespace Acquia\LightningExtension\Context;

use Behat\Mink\Exception\ExpectationException;
use Behat\MinkExtension\Context\RawMinkContext;
use Drupal\DrupalDriverManager;
use Drupal\DrupalExtension\Context\DrupalSubContextInterface;

/**
 * Contains step definitions for interacting with Quick Edit.
 *
 * @internal
 *   This is an internal part of Lightning Workflow's testing system and may be
 *   changed or removed at any time without warning. External code should not
 *   extend, instantiate, or use this class in any way! If you want to use the
 *   functionality of this class, you should copy the relevant code into your
 *   own project.
 */
final class QuickEditContext extends RawMinkContext implements DrupalSubContextInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(DrupalDriverManager $drupal) {
  }

  /**
   * Asserts that Quick Edit is enabled for at least one entity on the page.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   *   If Quick Edit is disabled on the current page.
   *
   * @Then Quick Edit should be enabled
   */
  public function assertEnabled() {
    $session = $this->getSession();

    $is_enabled = $session->wait(10000, 'Drupal.quickedit.collections.entities.length > 0');

    if (empty($is_enabled)) {
      throw new ExpectationException('Expected Quick Edit to be enabled, but it is not.', $session->getDriver());
    }
  }

  /**
   * Asserts that Quick Edit is not enabled for any entities on the page.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   *   If Quick Edit is enabled on the current page.
   *
   * @Then Quick Edit should be disabled
   */
  public function assertDisabled() {
    $session = $this->getSession();

    $is_disabled = $session->wait(10000, 'Drupal.quickedit.collections.entities.length === 0');

    if (empty($is_disabled)) {
      throw new ExpectationException('Expected Quick Edit to be disabled, but it is not.', $session->getDriver());
    }
  }

  /**
   * Asserts that a block exists with a Quick Edit contextual link.
   *
   * @param string $plugin
   *   The block plugin ID.
   *
   * @Then I should see a :plugin block with Quick Edit
   */
  public function assertBlock($plugin) {
    $assert = $this->assertSession();
    $links = $assert->elementExists('css', "div[data-block-plugin-id='$plugin'] ul.contextual-links");
    $assert->elementExists('named', ['link', 'Quick edit'], $links);
  }

}

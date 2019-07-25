<?php

namespace Acquia\LightningExtension\Context;

use Behat\MinkExtension\Context\RawMinkContext;
use Drupal\DrupalDriverManager;
use Drupal\DrupalExtension\Context\DrupalSubContextInterface;

/**
 * Contains step definitions for interacting with the Diff module.
 *
 * @internal
 *   This is an internal part of Lightning Workflow's testing system and may be
 *   changed or removed at any time without warning. External code should not
 *   extend, instantiate, or use this class in any way! If you want to use the
 *   functionality of this class, you should copy the relevant code into your
 *   own project.
 */
final class DiffContext extends RawMinkContext implements DrupalSubContextInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(DrupalDriverManager $drupal) {
  }

  /**
   * Compares two revisions of a node.
   *
   * @param string $a
   *   The one-based index of a revision to compare. 1st is oldest.
   * @param string $b
   *   The one-based index of a revision to compare. 1st is oldest.
   *
   * @When I compare the :a and :b revisions
   */
  public function compareRevisions($a, $b) {
    $page = $this->getSession()->getPage();

    $re = '/^[0-9]+(st|nd|rd|th)$/i';

    if (preg_match($re, $a)) {
      $a = substr($a, 0, -2);
    }
    if (preg_match($re, $b)) {
      $b = substr($b, 0, -2);
    }
    $a = ((int) $a) - 1;
    $b = ((int) $b) - 1;

    $page->clickLink('Revisions');

    /** @var \Behat\Mink\Element\NodeElement[] $rows */
    $rows = $page->findAll('css', '.diff-revisions tbody tr');
    $rows = array_reverse($rows);
    $a = $rows[$a]->findField('radios_left')->getValue();
    $b = $rows[$b]->findField('radios_right')->getValue();

    $page->selectFieldOption('radios_left', $a);
    $page->selectFieldOption('radios_right', $b);
    $page->pressButton('Compare');
  }

}

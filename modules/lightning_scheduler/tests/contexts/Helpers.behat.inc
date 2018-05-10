<?php

namespace Acquia\LightningExtension\Context;

use Drupal\Core\Url;
use Drupal\DrupalExtension\Context\DrupalSubContextBase;

/**
 * Contains miscellaneous helpful step definitions.
 *
 * @todo Move this into Lightning Core.
 */
final class Helpers extends DrupalSubContextBase {

  /**
   * The number of screenshots taken during the scenario.
   *
   * @var int
   */
  private $screenshotCount = 0;

  /**
   * Runs cron by invoking the /cron URL.
   *
   * @When I run cron over HTTP
   */
  public function cron() {
    $url = Url::fromRoute('system.cron', [
      'key' => \Drupal::state()->get('system.cron_key'),
    ]);
    $url = $url->toString();
    $url = $this->locatePath($url);

    \Drupal::httpClient()->get($url);
  }

  /**
   * Asserts that a number of elements match a CSS selector.
   *
   * @param string $selector
   *   The selector.
   * @param int $n
   *   The number of elements expected to match the selector.
   *
   * @Then exactly :n element(s) should match :selector
   */
  public function matchExactly($selector, $n) {
    $this->assertSession()
      ->elementsCount('css', $selector, $n);
  }

  /**
   * Guess what this does?
   *
   * @param null $filename
   *   Ignored. The screenshot will be generated one level up from the Drupal
   *   application root.
   * @param null $filepath
   *   Ignored. The screenshot will be named with a counter, prefixed with the
   *   name of the Mink browser.
   *
   * @When I take a screenshot
   */
  public function saveScreenshot($filename = NULL, $filepath = NULL) {
    $filename = sprintf('%s_%d.png', $this->getMinkParameter('browser_name'), ++$this->screenshotCount);
    $filepath = \Drupal::root() . '/../';
    parent::saveScreenshot($filename, $filepath);
  }

}

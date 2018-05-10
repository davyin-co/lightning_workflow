<?php

namespace Acquia\LightningExtension\Context;

use Behat\Mink\Element\ElementInterface;
use Drupal\Core\Url;
use Drupal\DrupalExtension\Context\DrupalSubContextBase;
use Webmozart\Assert\Assert;

class TimeElementContext extends DrupalSubContextBase {

  /**
   * @BeforeScenario
   */
  public function setUp() {
    ini_set('date.timezone', drupal_get_user_timezone());
  }

  /**
   * Converts an interval to a number of seconds.
   *
   * @param $interval
   *   The interval, in the form DAYS:HOURS:MINUTES:SECONDS.
   *
   * @return int
   *   The interval, represented in seconds.
   */
  protected function toSeconds($interval) {
    list ($days, $hours, $minutes, $seconds) = explode(':', $interval, 4);

    Assert::numeric($days);
    Assert::numeric($seconds);
    Assert::numeric($minutes);
    Assert::numeric($hours);

    $seconds = (int) $seconds;
    $minutes = (int) $minutes;
    $hours = (int) $hours;
    $days = (int) $days;

    return $seconds + ($minutes * 60) + ($hours * 3600) + ($days * 86400);
  }

  /**
   * Sets the value of a date element.
   *
   * @param string $field
   *   The field to set.
   * @param string $value
   *   The date to set, in YYYY-MM-DD format.
   *
   * @When I set the :field date to :value
   */
  public function setDate($field, $value) {
    $field = $this->assertSession()->fieldExists($field);
    $field->setValue($value);
    $this->triggerEvent($field, 'change');
  }

  /**
   * Sets the value of a time element.
   *
   * @param string $field
   *   The field to set.
   * @param mixed $value
   *   The time stamp to set, or an arbitrary time in HH:MM:SS format.
   *
   * @When I set the :field time to :value
   */
  public function setTime($field, $value) {
    if (is_numeric($value)) {
      $value = date('H:i:s', $value);
    }

    $field = $this->assertSession()->fieldExists($field);
    $field->setValue($value);
    $this->triggerEvent($field, 'change');
  }

  /**
   * Simulates a React event on an element.
   *
   * @param \Behat\Mink\Element\ElementInterface $element
   *   The element.
   * @param string $event
   *   The event to simulate.
   */
  protected function triggerEvent(ElementInterface $element, $event) {
    $element = $element->getXpath();

    // If React's testing utilities are present, simulate the event so that all
    // values will be updated successfully.
    $js = <<<END
if (ReactTestUtils) {
  const element = document.evaluate('$element', document, null).iterateNext();
  ReactTestUtils.Simulate.$event(element);
}
END;
    $this->getSession()->executeScript($js);
  }

  /**
   * Sets a time field to a point in the future.
   *
   * @param string $field
   *   The field to change.
   * @param string $interval
   *   The interval to set, in the form of DAYS:HOURS:MINUTES:SECONDS.
   *
   * @When I set :field to :interval from now
   */
  public function setFutureTime($field, $interval) {
    $this->setTime($field, time() + $this->toSeconds($interval));
  }

  /**
   * Sets a time field to a point in the past.
   *
   * @param $field
   *   The field to change.
   * @param $interval
   *   The interval to set, in the form of DAYS:HOURS:MINUTES:SECONDS.
   *
   * @When I set :field to :interval ago
   */
  public function setPastTime($field, $interval) {
    $this->setTime($field, time() - $this->toSeconds($interval));
  }

  /**
   * Sets a time field to a certain number of seconds in the future.
   *
   * @param string $field
   *   The field to change.
   * @param int $n
   *   How many seconds in the future to set the time field.
   *
   * @When I set :field to :n second(s) from now
   */
  public function setFutureTimeInSeconds($field, $n) {
    $this->setFutureTime($field, "00:00:00:$n");
  }

  /**
   * Sets a time field to a certain number of seconds in the past.
   *
   * @param string $field
   *   The field to change.
   * @param int $n
   *   How many seconds in the past to set the time field.
   *
   * @When I set :field to :n second(s) ago
   */
  public function setPastTimeInSeconds($field, $n) {
    $this->setPastTime($field, "00:00:00:$n");
  }

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

}

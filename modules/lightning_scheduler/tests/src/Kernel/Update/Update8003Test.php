<?php

namespace Drupal\Tests\lightning_scheduler\Kernel\Update;

use Drupal\KernelTests\KernelTestBase;

/**
 * @group lightning_scheduler
 */
class Update8003Test extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'content_moderation',
    'lightning_scheduler',
    'system',
    'user',
  ];

  /**
   * Tests that the config object is cresated.
   */
  public function testUpdate() {
    // Assert the config object does not already exist.
    $this->assertTrue($this->config('lightning_scheduler.settings')->isNew());

    // Run the update.
    $this->container->get('module_handler')
      ->loadInclude('lightning_scheduler', 'install');
    lightning_scheduler_update_8003();

    // Assert the config object was created.
    $time_step = $this->config('lightning_scheduler.settings')
      ->get('time_step');
    $this->assertSame(60, $time_step);
  }

}

<?php

namespace Drupal\Tests\lightning_workflow\ExistingSite;

use Drupal\user\Entity\Role;
use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * @group lightning
 * @group lightning_workflow
 */
class ModerationSidebarTest extends ExistingSiteBase {

  /**
   * Tests that the given role can use moderation sidebar.
   *
   * @param string $role
   *   The role ID to test.
   *
   * @dataProvider provider
   */
  public function test($role) {
    /** @var \Drupal\Core\Extension\ModuleHandlerInterface $module_handler */
    $module_handler = $this->container->get('module_handler');
    $this->assertTrue($module_handler->moduleExists('toolbar'));
    $this->assertTrue($module_handler->moduleExists('moderation_sidebar'));

    $role = Role::load($role);
    $this->assertTrue($role->hasPermission('access toolbar'));
    $this->assertTrue($role->hasPermission('use moderation sidebar'));

    $user = $this->createUser();
    $user->addRole($role->id());
    $user->save();
    $this->drupalLogin($user);

    $node = $this->createNode([
      'title' => 'Foo Bar',
      'type' => 'page',
    ]);
    $path = $node->toUrl()->toString();
    $this->visit($path);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->linkExists('Tasks');
  }

  /**
   * Data provider for ::test().
   *
   * @return array
   *   The test data.
   */
  public function provider() {
    return [
      ['page_creator'],
      ['page_reviewer'],
    ];
  }

}

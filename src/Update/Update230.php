<?php

namespace Drupal\lightning_workflow\Update;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\lightning_roles\ContentRoleManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @Update("2.3.0")
 */
final class Update230 implements ContainerInjectionInterface {

  /**
   * The module installer.
   *
   * @var \Drupal\Core\Extension\ModuleInstallerInterface
   */
  private $moduleInstaller;

  /**
   * The content role manager.
   *
   * @var \Drupal\lightning_roles\ContentRoleManager
   */
  private $contentRoleManager;

  /**
   * Update230 constructor.
   *
   * @param \Drupal\Core\Extension\ModuleInstallerInterface $module_installer
   *   The module installer.
   * @param \Drupal\lightning_roles\ContentRoleManager
   *   (optional) The content role manager.
   */
  public function __construct(ModuleInstallerInterface $module_installer, ContentRoleManager $content_role_manager = NULL) {
    $this->moduleInstaller = $module_installer;
    $this->contentRoleManager = $content_role_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $content_role_manager = NULL;

    if ($container->get('module_handler')->moduleExists('lightning_roles')) {
      $content_role_manager = $container->get('lightning.content_roles');
    }

    return new static(
      $container->get('module_installer'),
      $content_role_manager
    );
  }

  /**
   * Enables the Moderation Sidebar module.
   *
   * @update
   *
   * @ask Do you want to enable the Moderation Sidebar module? This will also
   * install the Toolbar module and allow reviewers to use it.
   */
  public function enableModerationSidebar() {
    $this->moduleInstaller->install(['moderation_sidebar', 'toolbar']);

    if ($this->contentRoleManager) {
      $this->contentRoleManager
        ->grantPermissions('creator', [
          'use moderation sidebar',
        ])
        ->grantPermissions('reviewer', [
          'access toolbar',
          'use moderation sidebar',
        ]);
    }
  }

}

<?php

namespace Drupal\Tests\lightning_workflow\Functional;

use Drupal\node\NodeInterface;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the moderation_history view.
 *
 * @group lightning_workflow
 */
class ModerationHistoryTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'lightning_workflow',
    'views',
  ];

  /**
   * Tests the moderation_history view for a node with revisions.
   */
  public function testModerationHistory() {
    $assert_session = $this->assertSession();

    // Create a content type with moderation enabled.
    $node_type = $this->createContentType([
      'third_party_settings' => [
        'lightning_workflow' => [
          'workflow' => 'editorial',
        ],
      ],
    ]);

    $user_permissions = [
      'administer nodes',
      'bypass node access',
      'use editorial transition create_new_draft',
      'use editorial transition review',
      'use editorial transition publish',
      'view all revisions',
    ];
    $user_a = $this->createUser($user_permissions, 'userA');
    $user_b = $this->createUser($user_permissions, 'userB');

    $node = $this->createNode([
      'type' => $node_type->id(),
      'title' => 'Foo',
      'moderation_state' => 'draft',
    ]);

    // Make two revisions with two different users.
    $timestamp = (new \DateTime())->getTimestamp();
    $timestamp_a = $timestamp + 10;
    $timestamp_b = $timestamp + 20;
    $this->createRevision($node, $user_a->id(), $timestamp_a, 'review');
    $this->createRevision($node, $user_b->id(), $timestamp_b, 'published');

    $this->drupalLogin($user_a);
    $this->drupalGet('/node/' . $node->id() . '/moderation-history');
    $assert_session->statusCodeEquals(200);
    $date_formatter = $this->container->get('date.formatter');
    $assert_session->pageTextContainsOnce('Set to review on ' . $date_formatter->format($timestamp_a, 'long') . ' by ' . $user_a->getAccountName());
    $assert_session->pageTextContainsOnce('Set to published on ' . $date_formatter->format($timestamp_b, 'long') . ' by ' . $user_b->getAccountName());
  }

  /**
   * Creates a new revision of the given $node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node which should receive a new revision.
   * @param int $user_id
   *   The ID of the user who created the revision.
   * @param int $timestamp
   *   The time that the revision was created.
   * @param string $state
   *   The desired moderation state.
   * @param string $revision_log
   *   The revision log message.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function createRevision(NodeInterface $node, $user_id, $timestamp, $state, $revision_log = 'Created revision.') {
    $node->setNewRevision();
    $node->setRevisionUserId($user_id);
    $node->setRevisionCreationTime($timestamp);
    $node->revision_log = $revision_log;
    $node->moderation_state = $state;
    $node->save();
  }

}

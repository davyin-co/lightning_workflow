<?php

namespace Drupal\Tests\lightning_scheduler\Functional;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\node\NodeInterface;

/**
 * @group lightning
 * @group lightning_workflow
 * @group lightning_scheduler
 */
class PurgeTest extends MigrationTestBase {

  public function test() {
    parent::test();

    $assert = $this->assertSession();
    $assert->pageTextContains('Purge without migrating');
    $assert->optionExists('purge[entity_type_id]', 'block_content');
    $assert->optionExists('purge[entity_type_id]', 'node');
    $assert->fieldExists('purge[entity_type_id]')->setValue('node');
    $assert->buttonExists('Purge')->press();
    $assert->pageTextContains('Purged scheduled transitions for content items.');
    $assert->pageTextNotContains('All migrations are completed.');
    $assert->pageTextContains('You are about to migrate scheduled transitions');
    $assert->optionExists('purge[entity_type_id]', 'block_content');
    $assert->optionNotExists('purge[entity_type_id]', 'node');

    $storage = $this->postMigration('node');

    /** @var NodeInterface $node */
    $node = $storage->load(1);
    $this->assertInstanceOf(NodeInterface::class, $node);
    $this->assertNode($node);
    $this->assertTrue($node->hasTranslation('fr'));
    $this->assertNode($node->getTranslation('fr'));

    // Test the default revision, loaded explicitly.
    $node = $storage->loadRevision(5);
    $this->assertInstanceOf(NodeInterface::class, $node);
    $this->assertNode($node);
    $this->assertTrue($node->hasTranslation('fr'));
    $this->assertNode($node->getTranslation('fr'));

    // Test previous revisions too.
    $node = $storage->loadRevision(4);
    $this->assertInstanceOf(NodeInterface::class, $node);
    $this->assertNode($node);
    $this->assertTrue($node->hasTranslation('fr'));
    $this->assertNode($node->getTranslation('fr'));

    $node = $storage->loadRevision(3);
    $this->assertInstanceOf(NodeInterface::class, $node);
    $this->assertNode($node);
    $this->assertFalse($node->hasTranslation('fr'));

    $node = $storage->loadRevision(2);
    $this->assertInstanceOf(NodeInterface::class, $node);
    $this->assertNode($node);
    $this->assertFalse($node->hasTranslation('fr'));

    $node = $storage->loadRevision(1);
    $this->assertInstanceOf(NodeInterface::class, $node);
    $this->assertTrue($node);
    $this->assertFalse($node->hasTranslation('fr'));
  }

  protected function assertNode(NodeInterface $node) {
    $this->assertFalse($node->hasField('scheduled_publication'));
    $this->assertFalse($node->hasField('scheduled_moderation_state'));

    $this->assertTrue($node->hasField('scheduled_transition_date'));
    $this->assertTrue($node->hasField('scheduled_transition_state'));

    $date = $node->get('scheduled_transition_date');
    $state = $node->get('scheduled_transition_state');

    $this->assertSame(
      FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
      $date
        ->getFieldDefinition()
        ->getFieldStorageDefinition()
        ->getCardinality()
    );
    $this->assertSame(
      FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
      $state
        ->getFieldDefinition()
        ->getFieldStorageDefinition()
        ->getCardinality()
    );

    $this->assertTrue($date->isEmpty());
    $this->assertTrue($state->isEmpty());
  }

}

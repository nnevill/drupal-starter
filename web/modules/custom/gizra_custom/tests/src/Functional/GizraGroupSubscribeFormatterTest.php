<?php

namespace Drupal\Tests\gizra_custom\Functional;

use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\og\Og;
use Drupal\og\OgRoleInterface;
use Drupal\og\Entity\OgRole;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests subscribe and un-subscribe formatter.
 *
 * @group Gizra
 */
class GizraGroupSubscribeFormatterTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['node', 'og', 'gizra_custom'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Test entity group.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $group;

  /**
   * A group bundle name.
   *
   * @var string
   */
  protected $groupBundle;

  /**
   * A non-author user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user1;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create bundle.
    $this->groupBundle = mb_strtolower($this->randomMachineName());

    // Create a node type.
    $node_type = NodeType::create(['type' => $this->groupBundle, 'name' => $this->groupBundle]);
    $node_type->save();

    // Define the bundles as groups.
    Og::groupTypeManager()->addGroup('node', $this->groupBundle);


    $display_repository = $this->container->get('entity_display.repository');
    // Update the displays so that configuration does not change unexpectedly on
    // import.
    $display_repository->getViewDisplay('node', $this->groupBundle, 'default')
      ->setComponent('og_group', [
        'type' => 'gizra_og_group_subscribe',
      ])
      ->save();

    // Create group author user.
    $user = $this->createUser();

    // Create groups.
    $this->group = Node::create([
      'type' => $this->groupBundle,
      'title' => $this->randomString(),
      'uid' => $user->id(),
    ]);
    $this->group->save();

    /** @var \Drupal\og\Entity\OgRole $role */
    $role = OgRole::getRole('node', $this->groupBundle, OgRoleInterface::ANONYMOUS);
    $role
      ->grantPermission('subscribe without approval')
      ->save();

    $this->user1 = $this->drupalCreateUser();
  }

  /**
   * Tests the formatter changes by user and membership.
   */
  public function testFormatter() {
    $this->drupalLogin($this->user1);

    // Subscribe to group.
    $this->drupalGet('node/' . $this->group->id());
    $this->clickLink('Hi ' . $this->user1->label() . ', click here if you would like to subscribe to this group called ' . $this->group->label());
    $this->click('#edit-submit');

    $this->drupalGet('node/' . $this->group->id());
    $this->assertSession()->linkExists('Hi ' . $this->user1->label() . ', click here if you would like to unsubscribe from this group called ' . $this->group->label());
  }

}

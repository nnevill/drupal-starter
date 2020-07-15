<?php

namespace Drupal\Tests\gizra_custom\ExistingSite;

use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Tests subscribe and un-subscribe formatter.
 *
 * @group Gizra
 */
class GizraGroupSubscribeFormatterTest extends ExistingSiteBase {

  /**
   * Tests the formatter changes by user and membership.
   */
  public function testFormatter() {
    // Creating group author.
    $author = $this->createUser([], NULL, TRUE);
    // Creating a user for testing.
    $user = $this->createUser([], NULL, TRUE);

    // Creating group node.
    $node = $this->createNode([
      'type' => 'group',
      'title' => $this->randomString(),
      'uid' => $author->id(),
    ]);
    $node->setPublished()->save();
    // Checking if author uid equals to group node uid.
    $this->assertEquals($author->id(), $node->getOwnerId());

    // Logging in user.
    $this->drupalLogin($user);

    // Go to group page.
    $this->drupalGet($node->toUrl());
    // Checking if it exists.
    $this->assertSession()->statusCodeEquals(200);

    // Checking if link with correct text exists.
    $this->assertSession()->linkExists('Hi ' . $user->label() . ', click here if you would like to subscribe to this group called ' . $node->label());
  }

}

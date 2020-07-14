<?php

namespace Drupal\gizra_custom\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Routing\RedirectDestinationTrait;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\og\Og;
use Drupal\og\OgAccessInterface;
use Drupal\og\OgMembershipInterface;
use Drupal\og\Plugin\Field\FieldFormatter\GroupSubscribeFormatter;
use Drupal\user\EntityOwnerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation for the OG subscribe formatter.
 *
 * @FieldFormatter(
 *   id = "gizra_og_group_subscribe",
 *   label = @Translation("Gizra OG Group subscribe"),
 *   description = @Translation("Display OG Group subscribe and un-subscribe links with some additions."),
 *   field_types = {
 *     "og_group"
 *   }
 * )
 */
class GizraGroupSubscribeFormatter extends GroupSubscribeFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    // Cache by the OG membership state.
    $elements['#cache']['contexts'] = ['og_membership_state'];

    $group = $items->getEntity();
    $entity_type_id = $group->getEntityTypeId();
    $user = $this->entityTypeManager->load(($this->currentUser->id()));

    // Getting user and group names.
    $username = $user->label();
    $group_name = $group->label();


    if (($group instanceof EntityOwnerInterface) && ($group->getOwnerId() == $user->id())) {
      // User is the group manager.
      $elements[0] = [
        '#type' => 'html_tag',
        '#tag' => 'span',
        '#attributes' => [
          'title' => $this->t('You are the group manager'),
          'class' => ['group', 'manager'],
        ],
        '#value' => $this->t('Hi @username. You are the manager of group called @groupname.', ['@username' => $username, '@groupname' => $group_name]),
      ];

      return $elements;
    }

    if (Og::isMemberBlocked($group, $user)) {
      // If user is blocked, they should not be able to apply for
      // membership.
      return $elements;
    }

    if (Og::isMember($group, $user, [OgMembershipInterface::STATE_ACTIVE, OgMembershipInterface::STATE_PENDING])) {
      $link['title'] = $this->t('Hi @username, click here if you would like to unsubscribe from this group called @groupname', ['@username' => $username, '@groupname' => $group_name]);
      $link['url'] = Url::fromRoute('og.unsubscribe', ['entity_type_id' => $entity_type_id, 'group' => $group->id()]);
      $link['class'] = ['unsubscribe'];
    }
    else {
      // If the user is authenticated, set up the subscribe link.
      if ($user->isAuthenticated()) {
        $parameters = [
          'entity_type_id' => $group->getEntityTypeId(),
          'group' => $group->id(),
        ];

        $url = Url::fromRoute('og.subscribe', $parameters);
      }
      else {
        // User is anonymous, link to user login and redirect back to here.
        $url = Url::fromRoute('user.login', [], ['query' => $this->getDestinationArray()]);
      }

      /** @var \Drupal\Core\Access\AccessResult $access */
      if (($access = $this->ogAccess->userAccess($group, 'subscribe without approval', $user)) && $access->isAllowed()) {
        $link['title'] = $this->t('Hi @username, click here if you would like to subscribe to this group called @groupname', ['@username' => $username, '@groupname' => $group_name]);
        $link['class'] = ['subscribe'];
        $link['url'] = $url;
      }
      elseif (($access = $this->ogAccess->userAccess($group, 'subscribe', $user)) && $access->isAllowed()) {
        $link['title'] = $this->t('Hi @username, click here if you would like to request membership to this group called @groupname', ['@username' => $username, '@groupname' => $group_name]);
        $link['class'] = ['subscribe', 'request'];
        $link['url'] = $url;
      }
      else {
        $elements[0] = [
          '#type' => 'html_tag',
          '#tag' => 'span',
          '#attributes' => [
            'title' => $this->t('This is a closed group. Only a group administrator can add you.'),
            'class' => ['group', 'closed'],
          ],
          '#value' => $this->t('Hi @username, this is a closed group. Only a group administrator can add you.', ['@username' => $username]),
        ];

        return $elements;
      }
    }

    if (!empty($link['title'])) {
      $link += [
        'options' => [
          'attributes' => [
            'title' => $link['title'],
            'class' => ['group'] + $link['class'],
          ],
        ],
      ];

      $elements[0] = [
        '#type' => 'link',
        '#title' => $link['title'],
        '#url' => $link['url'],
      ];
    }

    return $elements;
  }

}

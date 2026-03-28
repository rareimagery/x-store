<?php

namespace Drupal\rareimagery_x_activity\Service;

use Abraham\TwitterOAuth\TwitterOAuth;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\social_auth\User\UserManager;
use Drupal\user\UserInterface;

/**
 * Service to sync X profile data and recent posts.
 */
class XActivitySync {

  protected $socialAuthUserManager;
  protected $entityTypeManager;

  public function __construct(
    UserManager $socialAuthUserManager,
    EntityTypeManagerInterface $entityTypeManager
  ) {
    $this->socialAuthUserManager = $socialAuthUserManager;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Refresh all X data for a user.
   */
  public function refreshUserXData(UserInterface $account) {
    $tokens = $this->socialAuthUserManager->getUserTokens($account);
    if (empty($tokens['access_token']) || empty($tokens['access_token_secret'])) {
      return FALSE;
    }

    $connection = new TwitterOAuth(
      \Drupal::config('social_auth_twitter.settings')->get('client_id'),
      \Drupal::config('social_auth_twitter.settings')->get('client_secret'),
      $tokens['access_token'],
      $tokens['access_token_secret']
    );

    // 1. Get full user profile
    $user = $connection->get('account/verify_credentials', [
      'include_entities' => 'false',
      'skip_status' => 'false',
      'include_email' => 'false',
    ]);

    if (!empty($user->errors)) {
      return FALSE;
    }

    // 2. Save profile data
    $account->set('field_x_full_name', $user->name ?? '');
    $account->set('field_x_username', $user->screen_name ?? '');
    $account->set('field_x_bio', $user->description ?? '');
    $account->set('field_x_location', $user->location ?? '');
    $account->set('field_x_verified', !empty($user->verified));
    $account->set('field_x_followers', $user->followers_count ?? 0);
    $account->set('field_x_following', $user->friends_count ?? 0);
    $account->set('field_x_account_created', $user->created_at ?? NULL);

    // 3. Profile picture (high-res)
    if (!empty($user->profile_image_url_https)) {
      $account->set('field_x_profile_picture', $user->profile_image_url_https);
    }

    // 4. Banner image
    if (!empty($user->profile_banner_url)) {
      $account->set('field_x_banner', $user->profile_banner_url);
    }

    // 5. Get last 5 recent posts as official embed HTML
    $tweets = $connection->get('statuses/user_timeline', [
      'count' => 5,
      'tweet_mode' => 'extended',
      'exclude_replies' => TRUE,
      'include_rts' => TRUE,
    ]);

    $embeds = [];
    if (is_array($tweets)) {
      foreach ($tweets as $tweet) {
        $embed = '<blockquote class="twitter-tweet" data-dnt="true" data-theme="dark">' .
                 '<p lang="en" dir="ltr">' . htmlspecialchars($tweet->full_text ?? '') . '</p>' .
                 '&mdash; ' . htmlspecialchars($tweet->user->name) .
                 ' (@' . htmlspecialchars($tweet->user->screen_name) . ') ' .
                 '<a href="https://twitter.com/' . $tweet->user->screen_name . '/status/' . $tweet->id_str . '">' .
                 $tweet->created_at . '</a></blockquote>' .
                 '<script async src="https://platform.twitter.com/widgets.js" charset="utf-8"></script>';
        $embeds[] = $embed;
      }
    }

    $account->set('field_x_recent_posts', implode("\n", $embeds));
    $account->set('field_x_last_sync', time());

    $account->save();

    return TRUE;
  }
}

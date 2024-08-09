<?php

namespace Drupal\Tests\dhl_location_finder\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests for the dhl location finder module.
 *
 * @group dhl
 */
class DhlLocationFinderTests extends BrowserTestBase {
  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = ['dhl_location_finder'];

  /**
   * Theme to install and set as default theme.
   *
   * @var string
   */
  protected $defaultTheme = 'claro';

  /**
   * A simple user.
   *
   * @var \Drupal\user\UserInterface
   */
  private $user;

  /**
   * Perform initial setup tasks that run before every test method.
   */
  public function setUp(): void {
    parent::setUp();
    $this->user = $this->DrupalCreateUser([
      'access dhl config',
      'access dhl application',
    ]);
  }

  /**
   * Tests that the config page can be reached.
   */
  public function testConfigPageExists() {
    // Login.
    $this->drupalLogin($this->user);

    // Generator test:
    $this->drupalGet('admin/structure/dhl-settings');
    $this->assertSession()->statusCodeEquals(200);
  }

}

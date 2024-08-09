<?php

namespace Drupal\Tests\dhl_location_finder\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests for the dhl location finder module.
 * @group dhl
 */
class DhlLocationFinderTests extends BrowserTestBase {
  /**
   * Modules to install
   *
   * @var array
   */
  protected static $modules = array('dhl_location_finder');
  protected $defaultTheme = 'claro';

  // A simple user
  private $user;

  // Perform initial setup tasks that run before every test method.
  public function setUp(): void {
    parent::setUp();
    $this->user = $this->DrupalCreateUser(array(
      'access dhl config',
      'access dhl application',
    ));
  }

  /**
   * Tests that the config page can be reached.
   */
  public function testConfigPageExists() {
    // Login
    $this->drupalLogin($this->user);

    // Generator test:
    $this->drupalGet('admin/structure/dhl-settings');
    $this->assertSession()->statusCodeEquals(200);
  }

}
<?php

namespace Drupal\Tests\dhl_location_finder\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests for the dhl location finder module.
 * @group dhl
 */
class DhlConfigFormTests extends BrowserTestBase {
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
    $this->drupalGet('admin/config/services/dhl-settings');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Tests the config form.
   */
  public function testConfigForm() {
    // Login
    $this->drupalLogin($this->user);

    // Access config page
    $this->drupalGet('admin/config/services/dhl-settings');
    $this->assertSession()->statusCodeEquals(200);
    // Test the form elements exist
    $config = \Drupal::config('dhl_location_finder.settings');
    
    // Test form submission
    $this->submitForm(
      [
        'api_key' => 'demo-key',
      ],
      t('Save configuration'),
    );
    $this->assertSession()->responseContains(
      'The configuration options have been saved.',
      'The form was saved correctly.'
    );
    // Test the new values are there.
    $this->drupalGet('admin/config/services/dhl-settings');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->fieldValueEquals(
      'api_key',
      'demo-key'
    );
  }
}
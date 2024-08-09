<?php

namespace Drupal\dhl_location_finder\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use GuzzleHttp\ClientInterface;
use Drupal\Core\Form\FormBuilderInterface;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Url;

/**
 * Provides route responses for find_location.
 */
class LocationFinder extends ControllerBase implements ContainerInjectionInterface {
  /**
   * The Drupal formBuilder.
   * 
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * DHL api key.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * Constructor for LocationFinder.
   *
   * * @param Drupal\Core\Config\ConfigFactoryInterface $config
   *   A ConfigFactoryInterface object.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   A Guzzle client object.
   * @param \Drupal\Core\Form\FormBuilderInterface $formBuilder
   *   A FormBuilderInterface object
   */
  public function __construct(ConfigFactoryInterface $config, ClientInterface $http_client, FormBuilderInterface $formBuilder) {
    $this->httpClient = $http_client;
    $this->formBuilder = $formBuilder;
    $this->config = $config->get('dhl_location_finder.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('http_client'),
      $container->get('form_builder'),
    );
  }

  /**
   * Returns location finder form and locations result.
   *
   * @return array
   *   result from dhl location finder api
   */
  public function show_locations() {
    $api_key = $this->config->get('api_key');
    if ($api_key == NULL || $api_key == '') {
      $config_url = Url::fromRoute('dhl_location_finder.settings');
      return [
        '#markup' => $this->t("Please add you DHL api key at <a href=':url'>DHL api configuration</a> to access this application. 
        If you do not have access to given link please contact administrator.", [
          ':url' => $config_url->toString()
        ]),
      ];
    }
    $searchform = $this->formBuilder()->getForm('Drupal\dhl_location_finder\Form\LocationFinderForm');
    $build['form'] = $searchform;

    $country = '';
    $city = '';
    $post_code = '';
    
    // Set form submitted values
    if (isset($searchform['container']['country']['#value']) && $searchform['container']['country']['#value'] != NULL) {
      $country = $searchform['container']['country']['#value'];
    }
    if (isset($searchform['container']['city']['#value']) && $searchform['container']['city']['#value'] != NULL) {
      $city = $searchform['container']['city']['#value'];
    }
    if (isset($searchform['container']['post_code']['#value']) && $searchform['container']['post_code']['#value'] != NULL) {
      $post_code = $searchform['container']['post_code']['#value'];
    }

    // Check if values are set if yes then call api
    if ($country != '' && $city != '' && $post_code != '') {
      
      // Code to get country iso2 code
      $countryApi = 'https://public.opendatasoft.com/api/explore/v2.1/catalog/datasets/countries-codes/records';
      $countryQuery = [
        'select' => 'iso2_code',
        'where' => 'label_en like "' . $country . '"',
        'limit' => 1
      ];
      try {
        $request = $this->httpClient->request('GET', $countryApi, ['query' => $countryQuery]);
        $responce = $request->getBody()->getContents();
        $countryCodeResult = Json::decode($responce);
        if (isset($countryCodeResult['total_count']) && $countryCodeResult['total_count'] != 0) {
          // Run DHL location finder api if we found country code
          $countryCode = $countryCodeResult['results'][0]['iso2_code'];
          $apiEndpoint = 'https://api.dhl.com/location-finder/v1/find-by-address';
          $query = [
            'countryCode' => $countryCode,
            'addressLocality' => $city,
            'postalCode' => $post_code
          ];
          try {
            $request = $this->httpClient->request('GET', $apiEndpoint, [
              'query' => $query,
              'headers' => [
                'DHL-API-Key' => $api_key
              ]
            ]);
            $responce = $request->getBody()->getContents();
            $locationsResults = Json::decode($responce);
            $locations = [];
            foreach ($locationsResults['locations'] as $loactionResult) {
              $locationId = substr($loactionResult['url'],11);
              if (strpos($locationId, "-")) {
                $locationRealID = explode("-", $locationId);
                $locationId = $locationRealID[1];
              }
              // Only process if location id is even
              if (strlen($locationId) % 2 == 0) {
                $opningHours = [];
                foreach ($loactionResult['openingHours'] as $dayOfWeek) {
                  $day = substr($dayOfWeek['dayOfWeek'], 18);
                  $opningHours[$day] = $dayOfWeek['opens'] . ' - ' . $dayOfWeek['closes'];
                }
                // Only process if weekends are working
                if (isset($opningHours['Sunday']) && isset($opningHours['Saturday'])) {
                  $locations[] = [
                    'locationName' => $loactionResult['name'],
                    'address' => $loactionResult['place']['address'],
                    'openingHours' => $opningHours
                  ];
                }
              }
            }
            $build['result'] = [
              '#theme' => 'dhl_locations',
              '#locations' => $locations,
              '#empty_value' => $this->t("No offices found of given location. Please change your search parameters to update results.")
            ];
          }
          catch (ClientException | RequestException $e) {
            $build['result'] = [
              '#markup' => $this->t("System encounter an issue. Please check logs for more information.")
            ];
            $this->getLogger('DHL location finder')->error($e->getMessage());
          }
        }
        else {
          $build['result'] = [
            '#markup' => $this->t("System can not find country ':country'. Please check if you have entered correct country.", [
              ':country' => $country
            ])
          ];
        }
      }
      catch (ClientException | RequestException $e) {
        $build['result'] = [
          '#markup' => $this->t("System encounter an issue. Please check logs for more information.")
        ];
        $this->getLogger('DHL location finder')->error($e->getMessage());
      }
    }
    return $build;
  }
  
}
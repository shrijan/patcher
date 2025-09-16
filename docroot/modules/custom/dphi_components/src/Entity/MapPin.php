<?php

namespace Drupal\dphi_components\Entity;

use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\RevisionableContentEntityBase;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\image\Entity\ImageStyle;
use Drupal\dphi_components\MapPinInterface;
use Drupal\user\EntityOwnerTrait;


/**
 * Defines the map pin entity class.
 *
 * @ContentEntityType(
 *   id = "map_pin",
 *   label = @Translation("Map Pin"),
 *   label_collection = @Translation("Map Pins"),
 *   label_singular = @Translation("map pin"),
 *   label_plural = @Translation("map pins"),
 *   label_count = @PluralTranslation(
 *     singular = "@count map pins",
 *     plural = "@count map pins",
 *   ),
 *   bundle_label = @Translation("Map Pin type"),
 *   handlers = {
 *     "list_builder" = "Drupal\dphi_components\MapPinListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\dphi_components\MapPinAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\dphi_components\Form\MapPinForm",
 *       "edit" = "Drupal\dphi_components\Form\MapPinForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *       "delete-multiple-confirm" = "Drupal\Core\Entity\Form\DeleteMultipleForm",
 *       "revision-delete" = \Drupal\Core\Entity\Form\RevisionDeleteForm::class,
 *       "revision-revert" = \Drupal\Core\Entity\Form\RevisionRevertForm::class,
 *     },
 *     "route_provider" = {
 *        "revision" = \Drupal\Core\Entity\Routing\RevisionHtmlRouteProvider::class,
 *        "html" = \Drupal\dphi_components\Routing\MapPinHtmlRouteProvider::class,
 *     }
 *   },
 *   base_table = "map_pin",
 *   data_table = "map_pin_field_data",
 *   revision_table = "map_pin_revision",
 *   revision_data_table = "map_pin_field_revision",
 *   show_revision_ui = TRUE,
 *   translatable = TRUE,
 *   admin_permission = "administer map pin",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "revision_id",
 *     "langcode" = "langcode",
 *     "bundle" = "bundle",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *   },
 *   revision_metadata_keys = {
 *     "revision_user" = "revision_uid",
 *     "revision_created" = "revision_timestamp",
 *     "revision_log_message" = "revision_log",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/map-pin/{map_pin}",
 *     "collection" = "/admin/content/map-pin",
 *     "add-form" = "/admin/content/map-pin/add/{map_pin_type}",
 *     "add-page" = "/admin/content/map-pin/add",
 *     "edit-form" = "/admin/content/map-pin/{map_pin}",
 *     "delete-form" = "/admin/content/map-pin/{map_pin}/delete",
 *     "delete-multiple-form" = "/admin/content/map-pin/delete-multiple",
 *     "revision" = "/admin/content/map-pin/{map_pin}/revision/{map_pin_revision}/view",
 *     "revision-revert-form" = "/admin/content/map-pin/{map_pin}/revision/{map_pin_revision}/revert",
 *     "revision-delete-form" = "/admin/content/map-pin/{map_pin}/revision/{map_pin_revision}/delete",
 *     "version-history" = "/admin/content/map-pin/{map_pin}/revisions",
 *   },
 *   bundle_entity_type = "map_pin_type",
 *   field_ui_base_route = "entity.map_pin_type.edit_form",
 * )
 */
class MapPin extends RevisionableContentEntityBase implements MapPinInterface
{

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage)
  {
    parent::preSave($storage);
    if (!$this->getOwnerId()) {
      // If no owner has been set explicitly, make the anonymous user the owner.
      $this->setOwnerId(0);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type)
  {

    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['label'] = BaseFieldDefinition::create('string')
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setLabel(t('Label'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setRevisionable(TRUE)
      ->setLabel(t('Status'))
      ->setDefaultValue(TRUE)
      ->setSetting('on_label', 'Enabled')
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'settings' => [
          'display_label' => FALSE,
        ],
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'boolean',
        'label' => 'above',
        'weight' => 0,
        'settings' => [
          'format' => 'enabled-disabled',
        ],
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setLabel(t('Author'))
      ->setSetting('target_type', 'user')
      ->setDefaultValueCallback(static::class . '::getDefaultEntityOwner')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ],
        'weight' => 15,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'author',
        'weight' => 15,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Authored on'))
      ->setTranslatable(TRUE)
      ->setDescription(t('The time that the gallery map pin was created.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'timestamp',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setTranslatable(TRUE)
      ->setDescription(t('The time that the gallery map pin was last edited.'));

    return $fields;
  }

  public function formatForFrontEnd()
  {
    // Extract lat and long from the field_lat_long
    [$lat, $long] = explode(', ', $this->get('field_lat_long')->first()->get('value')->getValue());

    // Extract and format data from fields
    $formattedData = [
      'id' => $this->id(),
      'name' => $this->label(),
      'lat_long' => [
        'lat' => $lat,
        'long' => $long,
      ],
      'pin_type' => $this->get('field_pin_type')->first()->get('target_id')->getValue(),
      'short_description' => $this->get('field_short_description')->first()->get('value')->getValue(),
      'suburb' => $this->get('field_suburb')->first()->get('value')->getValue(),
      'postcode' => $this->get('field_postcode')->first()->get('value')->getValue(),
      'indigenous_location_name' => !$this->get('field_indigenous_location_name')->isEmpty() ? $this->get('field_indigenous_location_name')->first()->get('value')->getValue() : '',
      'field_1_value' => $this->get('field_1_value')->first()->get('value')->getValue(),
      'thumbnail_alignment' => $this->get('field_thumbnail_alignment')->first()->get('value')->getValue(),
    ];

    // Optional fields in an array structure
    for ($i = 2; $i <= 7; $i++) {
      $labelField = $this->get("field_{$i}_label");
      $valueField = $this->get("field_{$i}_value");
      if (!$labelField->isEmpty() && !$valueField->isEmpty()) {
        $label = $labelField->first()->get('value')->getValue();
        $value = $valueField->first()->get('value')->getValue();
        $formattedData["field_$i"] = [
          'label' => $label,
          'value' => $value,
        ];
      }
    }

    // Images handling
    if (!$this->get('field_images')->isEmpty()) {
      $formattedData['images'] = [];
      foreach ($this->get('field_images') as $image) {
        $mediaEntity = $image->entity;
        if ($mediaEntity && $mediaEntity->hasField('field_media_image')) {
          $imageField = $mediaEntity->get('field_media_image');
          if ($imageField && !$imageField->isEmpty()) {
            $fileEntity = $imageField->entity;
            if ($fileEntity) {
              // Generate the URL for the original image
              $originalImageUrl = \Drupal::service('file_url_generator')->generateAbsoluteString($fileEntity->getFileUri());

              // Generate URLs for image styles
              $modalImageUrl = ImageStyle::load('map_modal')->buildUrl($fileEntity->getFileUri());
              $thumbnailImageUrl = ImageStyle::load('map_thumbnail')->buildUrl($fileEntity->getFileUri());

              $altText = $imageField->alt ?? '';

              // Add URLs for both styles and alt text to the images array
              $formattedData['images'][] = [
                'original' => $originalImageUrl,
                'modal' => $modalImageUrl,
                'thumbnail' => $thumbnailImageUrl,
                'alt' => $altText,
              ];
            }
          }
        }
      }
    }

    // CTA handling
    if ($ctaEntity = $this->get('field_cta')->entity) {
      $formattedData['cta'] = [
        'target_id' => $ctaEntity->id(),
        'title' => $ctaEntity->label()
      ];
    }

    return $formattedData;
  }

}

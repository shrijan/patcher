# Bundle Class Annotations

Allows bundle classes to be configured using annotations or attributes. This
removes the need to implement `hook_entity_bundle_info_alter()`.

## Example

### Attributes

```php
<?php

declare(strict_types=1);

namespace Drupal\my_module\Entity\Node;

use Drupal\bca\Attribute\Bundle;
use Drupal\Core\StringTranslation\TranslatableMarkup;

#[Bundle(
  entityType: 'node',
  bundle: 'article',
  label: new TranslatableMarkup('Article'),
)]
class Article extends Node { }
```

### Annotations (deprecated)

```php
<?php

declare(strict_types=1);

namespace Drupal\my_module\Entity\Node;

use Drupal\node\Entity\Node;

/**
 * @Bundle(
 *   entity_type = "node",
 *   bundle = "article",
 *   label = @Translation("Article"),
 * )
 */
class Article extends Node { }
```

## Requirements

* PHP 8.1
* Drupal 10.2 or above

# Material Icons

The Material Icons module integrates Google’s Material Icons with Drupal websites. It allows inserting Material Icons from either a field or inside CKEditor.

| f | 1.x | 2.x |
|--|-----|-----|
| Drupal Core | 8 or 9 | >=9.3 or 10   |
| Field Formatter | X    | X   |
| CKEditor 4 | X | X    |
| CKEditor 5 |  | X |


|                   | 1.x                | 2.x                    |
|-------------------|--------------------|------------------------|
| Core              | ^8 &#124;&#124; ^9 | >=9.3 &#124;&#124; ^10 |
| Field Formatter   | Y                  | Y                      |
| CKEditor 4        | Y                  | Y                      |
| CKEditor 5        | N                  | Y                      |

For a full description of the module, visit the [project page](https://www.drupal.org/project/material_icons).

Submit bug reports or feature suggestions, or track changes in the [issue queue](https://www.drupal.org/project/issues/material_icons). 

## Table of contents

- Requirements
- Installation
- Configuration
  - Configuring the Font Familes
  - Using Material Icons as a Field
  - Using Material Icons in CKEditor
- Maintainers

## Requirements

This module requires no modules outside of Drupal core.

## Installation

Install as you would normally install a contributed Drupal module. For further
information, see [Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules).

## Configuration

### Configuring the Font Familes

Go to Configuration > Content Authoring > Material Icons and enable the style packs you need by ticking the checkboxes. The selected style packs will be automatically added to all your website pages as libraries.

### Using Material Icons as a Field

#### Add a Field of Type Material Icons

Go to Structure > [Your entity type] > Manage fields and add a field of the Material Icons type. Next, set the allowed number of values for the field. On the final page of the settings, you can choose to:

- make the field required 
- set the default value(s): the icon(s), the icon style(s), and, optionally, the additional CSS class(es)

#### Configure the Form Display

Go to Structure > [Your entity type] > Manage form display and rearrange the fields based on your needs either by drag-and-dropping them or by changing row weights.

#### Configure the Field Widget

Go to Structure > [Your entity type] > Manage form display and open the field widget settings next to the Material Icons field, which enable you to:

-   allow/disallow style selection
    
-   set the default icon style
    
-   allow/disallow additional CSS classes

#### Add Icons to Entities via the Field

To insert an icon in the field of Material Icons type on the entity editing form, start typing the name of an object/concept under “Icon Name.” As you type, icon options appear in the autocomplete dropdown. Click on the needed icon so it gets selected. You can also select the style family from the “Icon Type” dropdown and add CSS classes. 
     
### Using Material Icons in CKEditor

#### Add the Material Icons Button to the CKEditor Toolbar

Go to Configuration > Content authoring > Text formats and editors, select the text format (Full HTML, Restricted HTML, or Basic HTML), and click “Configure.” Drag the Material Icons button from the “Available buttons” to the “Active toolbar.” Ensure your text format is not limiting HTML, or has the ability to have classes on &lt;i&gt; elements. Save the text format.

#### Add Material Icons to Content in CKEditor

Click on the Materials Icons button on the CKEditor toolbar of the node editing form. Use the dialog box that appears to start typing the name of an object/concept under “Icon Name” field, or follow the link to check out the full list of icons first. As you type, icon options appear in the autocomplete dropdown. Click on the needed icon so it gets selected. You can also select the style family from the “Icon Type” dropdown and add CSS classes. 

## MAINTAINERS

Bryan Sharpe - [B_SHARPE](https://www.drupal.org/u/b_sharpe)

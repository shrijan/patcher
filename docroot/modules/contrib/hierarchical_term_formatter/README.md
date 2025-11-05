# Hierarchical Term Formatter

This module provides hierarchical term formatters for taxonomy reference
fields. In other words, it can display a taxonomy term reference on, say, a
node as "Parent > Child", rather than just "Child".

For a full description of the module, visit the
[project page](https://www.drupal.org/project/hierarchical_term_formatter).

To submit bug reports and feature suggestions, or track changes in the
[issue queue](https://www.drupal.org/project/issues/hierarchical_term_formatter).


## Table of contents

- Requirements
- Installation
- Configuration
- Features
- Maintainers


## Requirements

This module requires no modules outside of Drupal core.


## Installation

Install as you would normally install a contributed Drupal module. For further
information, see
[Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules).


## Configuration

Install and enable the module. Go to Admin » Structure » Content types and
open the Display settings page for a taxonomy term field. (Such fields can
also be available on other entities than nodes.)

Choose the Hierarchical terms formatter and click the cog wheel to configure
the formatter.


## Features
- Uses standard taxonomy term reference fields.
- Displays all parents of the referenced term, as well as the term itself. Can
  also be configured to only show the parents, or just the root parent term.
- Optionally links terms to their term pages.
- Choose from a variety of wrapper elements, or no extra markup.


## Maintainers

 - Aaron Wolfe - [awolfey](https://www.drupal.org/u/awolfey)
 - Ivan Bustos - [ibustos](https://www.drupal.org/u/ibustos)
 - Viktor Holovachek - [AstonVictor](https://www.drupal.org/u/astonvictor)

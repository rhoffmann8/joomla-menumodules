# Menu-Module mapping for Joomla! 2.5.x
When creating a new menu item in Joomla! the steps necessary for adding the desired modules to the item are as follows:
  1. If module is set to "All", skip to the next module,
  2. If module is not set to "All", do the following:
    * Click on module in the module list on the Edit Menu Item page
    * Wait for the Edit Module page to appear in an overlay
    * Locate the new menu item on the module page and check the box
    * Save the module
  3. Repeat the above for every module

This process becomes very tedious for a large amount of modules and the total amount of database writes is equal to the number of modules changed. This component takes the concept of the Menu Assignment panel on the Edit Module page and inverts it -- users can select a Menu Item from a list and assign modules to it as necssary, requiring only one database transaction to do so.

While other more advanced menu-module managers exist, I needed something that met two requirements:
  * Free/open source
  * [KISS](https://en.wikipedia.org/wiki/KISS_principle)

Modules for individual menu items are presented with a simple ON/OFF switch. In addition the module list can be filtered by module name, on/off/changed status, or published/unpublished status. All changes are stored on the UI until the user clicks "Save changes", at which point the changes are written to the Joomla #__modules_menu table.

Under the hood the following decisions are made for each module based on its current and modified state:
  * If the module is currently assigned to all pages and turned off for one, it will be changed to "All pages except" with the menu item in question being the exception.
  * If the module is assigned to "All pages except" and is turned on for the only exception, it will be changed to "All pages".
  * If the menu item is assigned to "Only the pages selected" and is turned off for the only pages it is assigned to, it will be changed to "No pages" (this is the default Joomla behavior).

This package uses the `MenusHelper` class from the `com_menus` component for fetching the initial list of menu items.

The component UI is built with [Backbone.js](http://backbonejs.org/), which is included in the package.

## Installation

  1. Run "build.php" to create component archive
  2. Install archive using Joomla Extension Manager.

## Todo

* Make the "All" -> "All pages except" decision configurable so user can opt for "Selected only" and unchecking only one item
Plugin Development
==================

Note: The plugin API is considered alpha at the moment.

Plugins are useful to extend the core functionalities of Kanboard, adding features, creating themes or changing the default behavior.

Plugin creators should specify explicitly the compatible versions of Kanboard. Internal code of Kanboard may change over the time and your plugin must be tested with new versions.

Directory structure
-------------------

Plugins are stored in the `plugins` subdirectory. An example of a plugin directory structure:

```bash
plugins
└── Budget            <= Plugin name
    ├── Asset         <= Javascript/CSS files
    ├── Controller
    ├── LICENSE       <= Plugin license
    ├── Locale
    │   ├── fr_FR
    │   ├── it_IT
    │   ├── ja_JP
    │   └── zh_CN
    ├── Model
    ├── Plugin.php    <= Plugin registration file
    ├── README.md
    ├── Schema        <= Database migrations
    ├── Template
    └── Test          <= Unit tests
```

Only the registration file `Plugin.php` is required. Other folders are optionals.

The first letter of the plugin name must be capitalized.

Plugin registration file
------------------------

Kanboard will scan the directory `plugins` and load automatically everything under this directory. The file `Plugin.php` is used to load and register the plugin.

Example of `Plugin.php` file (`plugins/Foobar/Plugin.php`):

```php
<?php

namespace Plugin\Foobar;

use Core\PluginBase;

class Plugin extends PluginBase
{
    public function initialize()
    {
        $this->template->hook->attach('layout:head', 'theme:layout/head');
    }
}
```

This file should contains a class `Plugin` defined under the namespace `Plugin\Yourplugin` and extends `Core\PluginBase`.

The only required method is `initialize()`. This method is called for each request when the plugin is loaded.

Plugin methods
--------------

Available methods from `PluginBase`:

- `initialize()`: Executed when the plugin is loaded
- `getClasses()`: Return all classes that should be stored in the dependency injection container
- `on($event, $callback)`: Listen on internal events

Your plugin registration class also inherit from `Core\Base`, that means you can access to all classes and methods of Kanboard easily.

This example will fetch the user #123:

```php
$this->user->getById(123);
```

Template hooks
--------------

Template hooks allow to add new content in existing templates.

Example to add new content in the dashboard sidebar:

```php
$this->template->hook->attach('dashboard:sidebar', 'myplugin:dashboard/sidebar');
```

This call is usually defined in the `initialize()` method. 
The first argument is name of the hook and the second argument is the template name.

Template names prefixed with the plugin name and colon indicate the location of the template.

Example with `myplugin:dashboard/sidebar`:

- `myplugin` is the name of your plugin (lowercase)
- `dashboard/sidebar` is the template name
- On the filesystem, the plugin will be located here: `plugins\Myplugin\Template\dashboard\sidebar.php`
- Templates are written in pure PHP (don't forget to escape data)

Template name without prefix are core templates.

List of template hooks:

- `dashboard:sidebar`
- `config:sidebar`
- `export:sidebar`
- `layout:head`
- `layout:top`
- `layout:bottom`
- `project:dropdown`
- `project-user:sidebar`
- `task:sidebar:information`
- `task:sidebar:actions`
- `user:sidebar:information`
- `user:sidebar:actions`

Other template hooks can be added if necessary, just ask on the issue tracker.

Template overrides
------------------

Any templates defined in the core can be overrided. By example, you can redefine the default layout or change email notifications.

Example of template override:

```php
$this->template->setTemplateOverride('header', 'theme:layout/header');
```

The first argument is the original template name and the second argument the template to use as replacement.

Listen on events
----------------

Kanboard use internal events and your plugin can listen and perform actions on these events.

```php
$this->on('session.bootstrap', function($container) {
    // Do something
});
```

- The first argument is the event name
- The second argument is a PHP callable function (closure or class method)

Extend ACL
----------

Kanboard use a custom access list for privilege separations. Your extension can add new rules:

```php
$this->acl->extend('project_manager_acl', array('mycontroller' => '*'));
```

- The first argument is the ACL name
- The second argument are the new rules
    + Syntax to include only some actions: `array('controller' => array('action1', 'action2'))`
    + Syntax to include all actions of a controller: `array('controller' => '*')`
    + Everything is lowercase

List of ACL:

- `public_acl`: Public access without authentication
- `project_member_acl`: Project member access
- `project_manager_acl`: Project manager access
- `project_admin_acl`: Project Admins
- `admin_acl`: Administrators

Plugin Translations
-------------------

Plugin can be translated in the same way the rest of the application. You must load the translations yourself when the session is created:

```php
$this->on('session.bootstrap', function($container) {
    Translator::load($container['config']->getCurrentLanguage(), __DIR__.'/Locale');
});
```

The translations must be stored in `plugins/Myplugin/Locale/xx_XX/translations.php`.

Dependency Injection Container
------------------------------

Kanboard use Pimple, a simple PHP Dependency Injection Container. However, Kanboard can register any class in the container easily.

Those classes are available everywhere in the application and only one instance is created.

Here an example to register your own models in the container:

```php
public function getClasses()
{
    return array(
        'Plugin\Budget\Model' => array(
            'HourlyRate',
            'Budget',
        )
    );
}
```

Now, if you use a class that extends from `Core\Base`, you can access directly to those class instance:

```php
$this->hourlyRate->remove(123);
$this->budget->getDailyBudgetBreakdown(456);

// It's the same thing as using the container:
$this->container['hourlyRate']->getAll();
```

Keys of the containers are unique across the application. If you override an existing class you will change the default behavior.

Schema migrations
-----------------

Kanboard execute database migrations automatically for you. Migrations must be stored in a folder **Schema** and the filename must be the same as the database driver:

```bash
Schema
├── Mysql.php
├── Postgres.php
└── Sqlite.php
```

Each file contains all migrations, here an example for Sqlite:

```php
<?php

namespace Plugin\Something\Schema;

const VERSION = 1;

function version_1($pdo)
{
    $pdo->exec('CREATE TABLE IF NOT EXISTS something (
        "id" INTEGER PRIMARY KEY,
        "project_id" INTEGER NOT NULL,
        "something" TEXT,
        FOREIGN KEY(project_id) REFERENCES projects(id) ON DELETE CASCADE
    )');
}
```

- The constant `VERSION` is the last version of your schema
- Each function is a migration `version_1()`, `version_2()`, etc...
- A `PDO` instance is passed as first argument
- Everything is executed inside a transaction, if something doesn't work a rollback is performed and the error is displayed to the user

Kanboard will compare the version defined in your schema and the version stored in the database. If the versions are different, Kanboard will execute one by one each migration until to reach the last version.

Examples of plugins
-------------------

- Budget planning: https://github.com/kanboard/plugin-budget
- Theme plugin sample: https://github.com/kanboard/plugin-example-theme

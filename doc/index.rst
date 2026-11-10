TypeScript Bundle For Symfony
=============================

This bundle allows you to compile TypeScript and use it with Symfony's AssetMapper
Component (no Node.js required!).

* Automatically downloads the correct `SWC <https://github.com/swc-project/swc>`_ binary
* Adds a ``typescript:build`` command to compile your TypeScript files
* Automatically compiles your TypeScript files when you run ``asset-map:compile`` command

Installation
------------

Install the bundle:

.. code-block:: terminal

    $ composer require sensiolabs/typescript-bundle

Usage
-----

No configuration is required: by default, the bundle compiles the TypeScript
files of the whole ``assets/`` directory. You can restrict it to the locations
where your TypeScript files are with the ``sensiolabs_typescript.source_dir``
option, which also avoids compiling the packages downloaded in ``assets/vendor/``.

For instance, if your TypeScript code lives in ``assets/typescript/`` directory,
with an ``assets/typescript/app.ts`` entrypoint file, set the option like this:

.. code-block:: yaml

    # config/packages/asset_mapper.yaml
    sensiolabs_typescript:
        source_dir: ['%kernel.project_dir%/assets/typescript']

Then load your TypeScript files in your templates:

.. code-block:: html+twig

    {# templates/base.html.twig #}

    {% block javascripts %}
        <script type="text/javascript" src="{{ asset('typescript/app.ts') }}"></script>
    {% endblock %}

Finally run this command:

.. code-block:: terminal

    # to compile only the TypeScript files
    $ php bin/console typescript:build --watch

    # to compile ALL your assets
    $ php bin/console asset-map:compile

And that's it!

Symfony CLI
~~~~~~~~~~~

If using the `Symfony CLI <https://symfony.com/download>`_, you can add the build
command as a `worker <https://symfony.com/doc/current/setup/symfony_server.html#configuring-workers>`_
to be started whenever you run ``symfony server:start``:

.. code-block:: yaml

    # .symfony.local.yaml
    workers:
        # ...
        typescript:
            cmd: ['symfony', 'console', 'typescript:build', '--watch']

.. tip::

    If running ``symfony server:start`` as a daemon, you can run
    ``symfony server:log`` to tail the output of the worker.

How Does it Work?
-----------------

The first time you run one of the TypeScript commands, the bundle will download
the correct SWC binary for your system into the ``var/`` directory.

When you run ``typescript:build``, that binary is used to compile TypeScript files
into the ``compile_dir`` (default: ``%kernel.project_dir%/var/typescript``) directory. Finally, when the contents of
``assets/typescript/app.ts`` is requested, the bundle swaps the contents of that file with the contents of
the ``compile_dir`` directory.

Configuration
-------------

To see the full config from this bundle, run:

.. code-block:: terminal

    $ php bin/console config:dump sensiolabs_typescript

The main option is ``source_dir``, which defaults to ``[%kernel.project_dir%/assets]``.
This is an array of the directories that will be compiled.

Using a different binary
------------------------

This bundle already installed for you the right SWC binary. However, if you already
have a SWC binary installed on your machine you can instruct the bundle to use
that binary with the ``swc_binary`` option:

.. code-block:: yaml

    # config/packages/asset_mapper.yaml
    sensiolabs_typescript:
        swc_binary: 'node_modules/.bin/swc'

By default, the bundle uses SWC v1.3.92. However, you can specify a different
SWC version to compile your codebase if you need a newer feature or bug fix:

.. code-block:: yaml

    # config/packages/sensiolabs_typescript.yaml
    sensiolabs_typescript:
        swc_version: v1.7.27-nightly-20240911.1

Note that you should remove the existing SWC binary in the download directory (``var`` by default) after switching the ``swc_version``; the download is only triggered if no binary is found in the download directory. Otherwise, the existing binary will still be used.

Configuring the compiler
------------------------

You can configure the SWC compiler by setting the ``swc_config_file`` option to
the the path to your `.swcrc <https://swc.rs/docs/configuration/swcrc>`_ file:

.. code-block:: yaml

    # config/packages/asset_mapper.yaml
    sensiolabs_typescript:
        swc_config_file: '%kernel.project_dir%/.swcrc'

Type definitions of importmap packages
--------------------------------------

SWC strips types without checking them, so a missing type definition only shows
up in your IDE or when running ``tsc``: AssetMapper downloads the JavaScript of
your importmap packages, but not their declaration files, which leads to
``TS2307: Cannot find module '@hotwired/stimulus'`` errors.

Run the following command to download the declaration files of every package in
your ``importmap.php``, at the exact version it pins (requires AssetMapper 6.4 or
higher):

.. code-block:: terminal

    $ php bin/console typescript:types:install

The declaration files come from the package itself when it ships them, or from
its ``@types/*`` package on `DefinitelyTyped <https://github.com/DefinitelyTyped/DefinitelyTyped>`_
otherwise. They are written into the ``types_dir`` directory (default:
``%kernel.project_dir%/var/typescript-types``), along with a
``tsconfig.importmap.json`` file mapping each import name to its declaration
file. Extend it from your ``tsconfig.json``:

.. code-block:: json

    {
        "extends": "./var/typescript-types/tsconfig.importmap.json",
        "compilerOptions": {
            "module": "ESNext",
            "moduleResolution": "bundler",
            "strict": true
        }
    }

Run the command again after each ``importmap:require``, ``importmap:update`` or
``importmap:remove``, and after cloning the project: like ``assets/vendor/``, the
directory is meant to be regenerated rather than committed.

.. note::

    The ``paths`` option is not merged by ``extends``: if your ``tsconfig.json``
    defines its own ``paths``, copy the generated entries into it. Likewise, a
    ``baseUrl`` option changes how the generated relative paths are resolved.

    For a package with no declaration files at all, declare the module in a
    local ``.d.ts`` file, e.g. ``declare module 'some-package';``.

.. tip::

    IDEs check types through the TypeScript language service, which runs on
    Node.js: in PhpStorm, set a Node interpreter in *Languages & Frameworks >
    TypeScript*. Node.js is then only needed by the IDE, never to build or
    deploy your assets. Without it, PhpStorm falls back to its own engine, which
    resolves imports to the JavaScript files of ``assets/vendor/`` and ignores
    these declaration files. Even with the language service, PhpStorm's
    navigation and quick documentation may still open those JavaScript files,
    while errors and autocompletion use the declaration files.

Using TypeScript with Stimulus
------------------------------

`StimulusBundle <https://symfony.com/bundles/StimulusBundle/current/index.html>`_
loads ``*_controller.ts`` files from ``assets/controllers/`` just like
``*_controller.js`` ones, so moving a Stimulus app to TypeScript takes four steps.

1. Compile only the directories holding your TypeScript files:

   .. code-block:: yaml

       # config/packages/sensiolabs_typescript.yaml
       sensiolabs_typescript:
           source_dir: ['%kernel.project_dir%/assets/controllers']

   Compiling the whole ``assets/`` directory also compiles ``assets/vendor/``,
   where the SWC version used by default fails on package directories whose
   name ends with ``.js`` (e.g. ``assets/vendor/chart.js/``).

2. Install the type definitions of your importmap packages, including
   ``@hotwired/stimulus``, and create a ``tsconfig.json`` extending them (see
   `Type definitions of importmap packages`_):

   .. code-block:: terminal

       $ php bin/console typescript:types:install

3. Rename a controller from ``.js`` to ``.ts`` and **delete the** ``.js`` **file**:
   when both exist, StimulusBundle ignores the ``.ts`` one. Files that do not
   need types, such as ``assets/stimulus_bootstrap.js``, can stay in JavaScript.

4. Type the controller. Stimulus creates the target and value properties at
   runtime, so declare them with ``declare`` to let TypeScript know about them
   without emitting any code:

   .. code-block:: typescript

       // assets/controllers/greet_controller.ts
       import { Controller } from '@hotwired/stimulus';

       export default class extends Controller<HTMLElement> {
           static targets = ['name', 'output'];
           static values = { greeting: { type: String, default: 'Hello' } };

           declare readonly nameTarget: HTMLInputElement;
           declare readonly outputTarget: HTMLElement;
           declare readonly hasOutputTarget: boolean;
           declare greetingValue: string;

           greet(event: Event): void {
               event.preventDefault();

               if (this.hasOutputTarget) {
                   this.outputTarget.textContent = `${this.greetingValue}, ${this.nameTarget.value}!`;
               }
           }
       }

   .. code-block:: html+twig

       <form data-controller="greet" data-greet-greeting-value="Bonjour" data-action="greet#greet">
           <input data-greet-target="name">
           <button>Greet</button>
           <p data-greet-target="output"></p>
       </form>

   The generic parameter of ``Controller`` types ``this.element``. See the
   `Stimulus TypeScript reference <https://stimulus.hotwired.dev/reference/using-typescript>`_
   for outlets, classes and the other typing patterns.

Then run ``php bin/console typescript:build --watch`` while developing, as for
any other TypeScript file.

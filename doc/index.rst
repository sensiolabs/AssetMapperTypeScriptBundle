TypeScript Bundle For Symfony
=================

This bundle allows you to compile TypeScript and use it with Symfony's AssetMapper Component
(no Node required!).

- Automatically downloads the correct `SWC <https://github.com/swc-project/swc>`_ binary
- Adds a ``typescript:build`` command to compile your typescript files
- Automatically compiles your typescript files when you run ``asset-map:compile`` command

Installation
------------

Install the bundle:

.. code-block:: terminal

    $ composer require sensiolabs/typescript-bundle

Usage
-----

Start by setting the ``sensiolabs_typescript.source_dir`` option to the list of location where your typescript files are located.

For instance, if your TypeScript code lives in ``assets/typescript`` directory, with a ``assets/typescript/app.ts`` entrypoint file, you could set the option like this:

.. code-block:: yaml

    # config/packages/asset_mapper.yaml
    sensiolabs_typescript:
        source_dir: ['%kernel.project_dir%/assets/typescript']

Then point your TypeScript files in your templates

.. code-block:: html+twig

    {# templates/base.html.twig #}

    {% block javascripts %}
        <script type="text/javascript" src="{{ asset('typescript/app.ts') }}"></script>
    {% endblock %}


Then run the command:

.. code-block:: terminal

    # To compile only the typescript files
    $ php bin/console typescript:build --watch

    # To compile ALL your assets
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

The first time you run one of the TypeScript commands, the bundle will download the correct SWC binary for your system into the ``var`` directory.

When you run ``typescript:build``, that binary is used to compile TypeScript files into a ``var/typescript`` directory. Finally, when the contents of ``assets/typescript/app.ts`` is requested, the bundle swaps the contents of that file with the contents of from ``var/typescript/`` directory.

Configuration
--------------

To see the full config from this bundle, run:

.. code-block:: terminal

    $ php bin/console config:dump sensiolabs_typescript

The main option is ``source_dir`` option, which defaults to ``[%kernel.project_dir%/assets]``. This is an array of directories that will be compiled.

Using a different binary
--------------------------

This bundle already installed for you the right SWC binary. However, if you already have a SWC binary installed on your machine you can instruct the bundle to use that binary, set the ``binary`` option:

.. code-block:: yaml

    sensiolabs_typescript:
        binary: 'node_modules/.bin/swc'

Configuring the compiler
--------------------------

You can configure the SWC compiler by setting the ``config_file`` option to the the path to your [.swcrc](https://swc.rs/docs/configuration/swcrc) file:

.. code-block:: yaml

    sensiolabs_typescript:
        config_file: '%kernel.project_dir%/.swcrc'

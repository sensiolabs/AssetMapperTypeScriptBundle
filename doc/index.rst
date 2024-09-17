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

Start by setting the ``sensiolabs_typescript.source_dir`` option to the list of
locations where your TypeScript files are located.

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
into a ``var/typescript/`` directory. Finally, when the contents of ``assets/typescript/app.ts``
is requested, the bundle swaps the contents of that file with the contents of
the ``var/typescript/`` directory.

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

    # config/packages/asset_mapper.yaml
    sensiolabs_typescript:
        swc_version: v1.7.27-nightly-20240911.1

Note that you should remove the existing SWC binary in the `var` directory after switching the `swc_version`; otherwise, it will use the old binary.

Configuring the compiler
------------------------

You can configure the SWC compiler by setting the ``swc_config_file`` option to
the the path to your `.swcrc <https://swc.rs/docs/configuration/swcrc>`_ file:

.. code-block:: yaml

    # config/packages/asset_mapper.yaml
    sensiolabs_typescript:
        swc_config_file: '%kernel.project_dir%/.swcrc'

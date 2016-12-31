# Simply Static

Here's a basic rundown of what's needed to build everything for Simply Static.

## Things you'll need

- WP-CLI
  http://wp-cli.org/
- Composer
  https://getcomposer.org/download/
- Node.js/npm
  https://nodejs.org/en/
- Grunt
  http://gruntjs.com/getting-started

## Setting up grunt

Make sure to install the packages needed by grunt:

    npm install

## Using Grunt

Grunt is used to build our simply-static.pot file for translations. You can
run it with:

    grunt

## Using Composer

Composer provides several packages containing functionality used by Simply
Static. To install new packages:

    composer install

And to update existing packages:

    composer update

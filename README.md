# Simply Static

## Support

This is the developer site for Simply Static. For support, please visit [the WordPress support forums for Simply Static](https://wordpress.org/support/plugin/simply-static).

## Reporting bugs

If you find an issue, [let us know](https://github.com/codeofconductco/simply-static/issues/new)! We want to make the best static site generator plugin possible, and every bug we crush gets us one step closer.

Here are some things that would help us in your bug report:

1. Step-by-step description of every step you took (screenshots and/or screen recordings are great too)
2. What you expected to happen
3. What actually happened
4. Any error messages you encountered  
    If you're not seeing any error messages but you're having an issue, it could be that they're being squelched by WordPress. You can find code in [this gist](https://gist.github.com/jrfnl/5925642) that will help you enable error messages and display backtraces.
5. WordPress version and other plugins being used
6. Webserver details, including:  
    - Operating system of the server
    - Webserver in use (e.g. Apache, nginx, IIS, etc.)
    - PHP version
    - MySQL version
7. Do you have an educated guess as to what caused the problem?
8. Can you suggest a solution?

## Security issues

**Please do not report security issues here.** Instead, email them to [security@codeofconduct.co](mailto:security@codeofconduct.co) so we can deal with them securely and quickly.

# Development

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

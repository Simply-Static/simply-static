## Run tests

    1. Symlink the plugin directory into a working WordPress installation's `wp-content/plugins` directory.
    2. In this symlinked directory, run `./bin/install-wp-tests.sh DB_NAME DB_USER DB_PASS 127.0.0.1 latest`
        (where DB_NAME, DB_USER, and DB_PASS are the database name, username, and password respectively)
        The database will be automatically created using the username and password provided.
    3. Run `phpunit`

## Installing phpunit and wp-cli

[phpunit](https://phpunit.de/):

    wget https://phar.phpunit.de/phpunit-old.phar
    chmod +x phpunit-old.phar
    sudo mv phpunit-old.phar /usr/local/bin/phpunit

[wp-cli](http://wp-cli.org/#install):

    curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
    chmod +x wp-cli.phar
    sudo mv wp-cli.phar /usr/local/bin/wp

And verify that it works:

    wp --info

## Installing other PHP versions on Ubuntu 14.04

We use phpenv to install alternate versions of PHP to test against. You'll need to have [rbenv](https://github.com/phpenv/phpenv) installed already.

    git clone https://github.com/CHH/phpenv.git
    cd phpenv/bin/
    ./phpenv-install.sh

That will install phpenv to `~/.phpenv`.

----

Open up `~/.bashrc` with your favorite text editor:

    vim ~/.bashrc

Add this after/below your rbenv declaration:

    export PATH="/home/scott/.phpenv/bin:$PATH"
    eval "$(phpenv init -)"

Save that, and then run:

    source ~/.bashrc

----

Install php-build:

    git clone git://github.com/php-build/php-build.git $HOME/.phpenv/plugins/php-build

Install some additional requirements for ubuntu:

    sudo apt-get install libpng12-dev libmcrypt-dev libtidy-dev

And install other PHP versions:

    phpenv install 5.3.29
    phpenv install 5.4.45

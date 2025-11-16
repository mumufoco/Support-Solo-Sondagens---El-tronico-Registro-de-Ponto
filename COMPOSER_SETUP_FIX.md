# Composer Setup Fix - Undefined $argv Variable

## Problem

When installing Composer, you may encounter this PHP warning:

```
PHP Warning: Undefined variable $argv in composer-setup.php on line 14
```

## Root Cause

The `$argv` variable is a PHP superglobal that contains command-line arguments. However, it's only available when:
- PHP is running in CLI (Command Line Interface) mode
- The `register_argc_argv` PHP configuration is enabled

Some PHP installations have `register_argc_argv` disabled by default, causing this warning when Composer's installer tries to access `$argv`.

## Solution 1: Using the Fixed Install Script

The `install-dependencies.sh` script has been updated to automatically handle this issue. Simply run:

```bash
./install-dependencies.sh
```

The script now uses the `-d register_argc_argv=On` flag when running composer-setup.php.

## Solution 2: Manual Composer Installation

If you need to install Composer manually, use this command:

```bash
# Download the installer
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"

# Verify the installer
HASH="$(wget -q -O - https://composer.github.io/installer.sig)"
php -r "if (hash_file('sha384', 'composer-setup.php') === '$HASH') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); exit(1); }"

# Run with register_argc_argv enabled
php -d register_argc_argv=On composer-setup.php --quiet

# Clean up
rm composer-setup.php

# Move to global location (optional)
sudo mv composer.phar /usr/local/bin/composer
```

## Solution 3: Enable register_argc_argv Globally

Edit your `php.ini` file and ensure this setting is enabled:

```ini
register_argc_argv = On
```

After changing php.ini, restart your PHP service:

```bash
# For PHP-FPM
sudo systemctl restart php-fpm

# For Apache
sudo systemctl restart apache2
```

## Verification

After applying the fix, verify Composer is working:

```bash
composer --version
```

You should see output like: `Composer version 2.x.x`

## Technical Details

The fix adds the `-d register_argc_argv=On` runtime configuration flag, which:
- Enables `$argc` and `$argv` variables for that specific PHP execution
- Only affects the composer-setup.php execution
- Doesn't require permanent PHP configuration changes
- Works across all PHP versions and configurations

## Related Files

- `install-dependencies.sh:77` - Contains the fixed composer installation command
- This fix was implemented in commit on branch `claude/fix-composer-setup-warning-01NedH4Ms8iQLTecqkxf9rPx`

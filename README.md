[![Build Status](https://travis-ci.org/Automattic/_s.svg?branch=master)](https://travis-ci.org/Automattic/_s)

_s
===

Hi. I'm a starter theme called `_s`, or `underscores`, if you like. I'm a theme meant for hacking so don't use me as a Parent Theme. Instead try turning me into the next, most awesome, WordPress theme out there. That's what I'm here for.

My ultra-minimal CSS might make me look like theme tartare but that means less stuff to get in your way when you're designing your awesome theme. Here are some of the other more interesting things you'll find here:

* A modern workflow with a pre-made command-line interface to turn your project into a more pleasant experience.
* A just right amount of lean, well-commented, modern, HTML5 templates.
* A custom header implementation in `inc/custom-header.php`. Just add the code snippet found in the comments of `inc/custom-header.php` to your `header.php` template.
* Custom template tags in `inc/template-tags.php` that keep your templates clean and neat and prevent code duplication.
* Some small tweaks in `inc/template-functions.php` that can improve your theming experience.
* A script at `js/navigation.js` that makes your menu a toggled dropdown on small screens (like your phone), ready for CSS artistry. It's enqueued in `functions.php`.
* 2 sample layouts in `sass/layouts/` made using CSS Grid for a sidebar on either side of your content. Just uncomment the layout of your choice in `sass/style.scss`.
Note: `.no-sidebar` styles are automatically loaded.
* Smartly organized starter CSS in `style.css` that will help you to quickly get your design off the ground.
* Full support for `WooCommerce plugin` integration with hooks in `inc/woocommerce.php`, styling override woocommerce.css with product gallery features (zoom, swipe, lightbox) enabled.
* Licensed under GPLv2 or later. :) Use it to make something cool.

Per-instance configuration
---------------

The theme is deployed unchanged to every WP Engine environment. Everything that
differs between instances is read from `wp-config.php` constants (environment
variables work as a fallback) by `inc/ihq-env.php`. Nothing environment-specific
lives in the theme tree, and the site refuses to render a front-end request
until the required constants are set — the error names the missing one.

Add this block to `wp-config.php` on each instance:

```php
// Influencer HQ — per-instance configuration (inc/ihq-env.php)
define( 'IHQ_ENVIRONMENT',          'dev' );   // dev | qa | prod — diagnostics only
define( 'IHQ_API_BASE_URL',         'https://<id>.execute-api.<region>.amazonaws.com/<stage>' );
define( 'IHQ_GAME_PORTAL_BASE_URL', 'https://<portal-host>/av-baccarat' );
define( 'IHQ_INFLUENCER_API_KEY',   '<value of SSM /<stage>/account-api-tf-api/INFLUENCER_HQ_SSO_API_KEY>' );

// Optional — feature is off when absent
define( 'CF_TURNSTILE_SITE_KEY',    '' );
define( 'CF_TURNSTILE_SECRET_KEY',  '' );
define( 'IHQ_ELEVENLABS_API_KEY',   '' );
define( 'IHQ_GENIUS_REFERRALS_API_TOKEN', '' );   // test-form.php only
```

`IHQ_API_BASE_URL` and `IHQ_GAME_PORTAL_BASE_URL` must be absolute `https://` URLs; anything else
is refused at load. WP Engine's `wp-config.php` has no "stop editing" marker — put the block after
the existing `ANAM_API_KEY` block, before the `ABSPATH` define.

| Instance | `IHQ_ENVIRONMENT` | `IHQ_API_BASE_URL` | `IHQ_GAME_PORTAL_BASE_URL` | API key (SSM, per AWS account) |
|---|---|---|---|---|
| influenchqdev.wpenginepowered.com | `dev` | `https://02nvfvonol.execute-api.eu-west-2.amazonaws.com/qc` | `https://qc-game-portal-client-tf-b2c.dev.ae.games/av-baccarat` | `/qc/account-api-tf-api/INFLUENCER_HQ_SSO_API_KEY` — dev account, eu-west-2 |
| influencerhqqa.wpenginepowered.com | `qa` | `https://nxyd4exz24.execute-api.eu-west-2.amazonaws.com/main` | `https://main-game-portal-client-tf-b2c.qa.ae.games/av-baccarat` | `/main/account-api-tf-api/INFLUENCER_HQ_SSO_API_KEY` — QA account, eu-west-2 |
| influencerhq.co | `prod` | `https://0cn4xq456d.execute-api.ap-southeast-1.amazonaws.com/main` | `https://play.bet5games.com/av-baccarat` | `/main/account-api-tf-api/INFLUENCER_HQ_SSO_API_KEY` — bet5 account, ap-southeast-1 |

`IHQ_API_BASE_URL` is the **influencerhq-api** gateway (the proxy), never account-api
directly. The Turnstile keys are shared across instances; each instance's hostname
must be on the widget's allowed-domain list or the challenge fails.

Installation
---------------

### Requirements

`_s` requires the following dependencies:

- [Node.js](https://nodejs.org/)
- [Composer](https://getcomposer.org/)

### Quick Start

Clone or download this repository, change its name to something else (like, say, `megatherium-is-awesome`), and then you'll need to do a six-step find and replace on the name in all the templates.

1. Search for `'_s'` (inside single quotations) to capture the text domain and replace with: `'megatherium-is-awesome'`.
2. Search for `_s_` to capture all the functions names and replace with: `megatherium_is_awesome_`.
3. Search for `Text Domain: _s` in `style.css` and replace with: `Text Domain: megatherium-is-awesome`.
4. Search for <code>&nbsp;_s</code> (with a space before it) to capture DocBlocks and replace with: <code>&nbsp;Megatherium_is_Awesome</code>.
5. Search for `_s-` to capture prefixed handles and replace with: `megatherium-is-awesome-`.
6. Search for `_S_` (in uppercase) to capture constants and replace with: `MEGATHERIUM_IS_AWESOME_`.

Then, update the stylesheet header in `style.css`, the links in `footer.php` with your own information and rename `_s.pot` from `languages` folder to use the theme's slug. Next, update or delete this readme.

### Setup

To start using all the tools that come with `_s`  you need to install the necessary Node.js and Composer dependencies :

```sh
$ composer install
$ npm install
```

### Available CLI commands

`_s` comes packed with CLI commands tailored for WordPress theme development :

- `composer lint:wpcs` : checks all PHP files against [PHP Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/).
- `composer lint:php` : checks all PHP files for syntax errors.
- `composer make-pot` : generates a .pot file in the `languages/` directory.
- `npm run compile:css` : compiles SASS files to css.
- `npm run compile:rtl` : generates an RTL stylesheet.
- `npm run watch` : watches all SASS files and recompiles them to css when they change.
- `npm run lint:scss` : checks all SASS files against [CSS Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/css/).
- `npm run lint:js` : checks all JavaScript files against [JavaScript Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/javascript/).
- `npm run bundle` : generates a .zip archive for distribution, excluding development and system files.

Now you're ready to go! The next step is easy to say, but harder to do: make an awesome WordPress theme. :)

Good luck!

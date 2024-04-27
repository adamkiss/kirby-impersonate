# Kirby Impersonate: Panel actions for impersonating users

This plugin allows impersonating users in your panel and/or frontend, by adding a session mark for "user is being impersonated" and then forcibly calling `kirby()->impersonate()` with the target user.

## Installation

```bash
composer require adamkiss/kirby-impersonate
```

As with all Kirby plugins, downloading zip should also work. As should using git submodules, but I never tested that.

## Usage

If you want to use this plugin only for your panel debugging, just install it, potentially configure who can impersonate/be impersonated and you're done. If you also want to test different frontend availability, you can change where the admin gets redirect after they start impersonation, and the frontend should _just_ work as if it was the user being impersonated.

For notification about ongoing impersonation, you can use `kirby()->user()->isImpersonated()`, and you can use the route `/__impersonate/stop` to stop impersonation. For instance, using following in your template:

```php
<?= kirby()->user()->isImpersonated() ? '<a href="/__impersonate/stop">Stop Impersonation</a>' : ''?>
```

## Configuration

By default, any user with access to users table can impersonate any other user. At the start of the impersonation, window reload happens, and at the end of the impersonation, you'll be redirected to users view in the panel; All of these are configurable via options:

```php
// config.php
return [
	'adamkiss.kirby-impersonate' => [
		'can-impersonate' => function () {
			// who can impersonate? this is a user method,
			// so "$this" is a user who's checked.
			// you have access to role, email, uuid…

			/** @var User $this */
			return true;
		},
		'can-be-impersonated' => function () {
			// who can be target of impersonation? this is a user method,
			// so "$this" is a user who's checked
			// you have access to role, email, uuid…

			/** @var User $this */
			return true;
		},
		
		// Since the start of redirection is in the users area, null just reloads
		// You can also use a string to get a URL, or a Closure with the impersonated user
		// if you need different redirects base on role / email / whatever
		'redirect-after-impersonation-start' => null,

		// Where you're redirected after the impersonation stops
		// this can be only a string
		'redirect-after-impersonation-stop' => kirby()->url('panel') . '/users',    ]
];
```

## License

MIT, (c) 2024 Adam Kiss

See LICENSE.md for more information
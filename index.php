<?php

use Kirby\Cms\App;
use Kirby\Cms\Find;
use Kirby\Cms\Response;
use Kirby\Cms\User;

App::plugin('adamkiss/kirby-impersonate', [
	'options' => [
		'can-impersonate' => function () {
			/** @var User $this */
			return true;
		},
		'can-be-impersonated' => function () {
			/** @var User $this */
			return true;
		},
		'redirect-after-impersonation-start' => null,
		'redirect-after-impersonation-stop' => kirby()->url('panel') . '/users',
	],
    'api' => [
        'routes' => [
			[
				'pattern' => 'impersonate/status',
				'action'  => function () {
					if (!kirby()->user()) {
						kirby()->session()->data()->remove('impersonating');
					}

					return [
						'impersonating' => kirby()->session()?->data()?->get('impersonating', false) ?? false,
					];
				}
			],
			[
				'pattern' => 'impersonate/stop',
				'method' => 'POST',
				'action' => function () {
					kirby()->session()->data()->remove('impersonating');

					return [
						'impersonating' => false
					];
				},
			]

        ],
    ],
	'areas' => [
		'users' => [
			'dropdowns' => [
				'user' => function (string $id){
					$existing = kirby()->core()->area('users')['dropdowns']['user']['options']($id);

					if (!kirby()->user()?->canImpersonate() || !Find::user($id)?->canBeImpersonated()) {
						return $existing;
					}

					return [
						[
							'dialog' => "impersonate/{$id}",
							'icon' => 'impersonate',
							'text' => t('impersonate', 'Impersonate'),
						],
						'-',
						...$existing
					];
				},
			],
			'dialogs' => [
				'impersonate/(:any)' => [
					'load' => function(string $uuid) {
						$user = kirby()->user();
						if (!$user || !$user?->canImpersonate()) {
							return false;
						}
						$impersonated_user = Find::user($uuid);
						if (!$impersonated_user || !$impersonated_user->canBeImpersonated()) {
							return false;
						}
						kirby()->session()->data()->set('impersonating', $impersonated_user->email());

						$redirect = option('adamkiss.kirby-impersonate.redirect-after-impersonation-start');
						if ($redirect) {
							if (is_string($redirect)) {
								return Response::go($redirect);
							}

							if (is_callable($redirect)) {
								return $redirect($impersonated_user);
							}
						}

						return [
							'component' => 'impersonation-emitter',
							'props' => [
								'event' => 'impersonate',
								'payload' => [
									'email' => $impersonated_user->email(),
								]
							]
						];
					},
				],
			],
		]
	],
	'hooks' => [
		'user.logout:after' => function () {
			kirby()->session()->data()->remove('impersonating');
		},
	],
	'routes' => [
		[
			'pattern' => '__impersonate/stop',
			'action' => function () {
				if (!kirby()->user()?->isImpersonated()) {
					return false;
				}

				kirby()->session()->data()->remove('impersonating');
				return Response::go(option('adamkiss.kirby-impersonate.redirect-after-impersonation-stop', kirby()->url('panel') . '/users'));
			},
		],
	],
	'userMethods' => [
		'canImpersonate' => option('adamkiss.kirby-impersonate.can-impersonate'),
		'canBeImpersonated' => option('adamkiss.kirby-impersonate.can-be-impersonated'),
		'isImpersonated' => function () {
			return kirby()->session()?->data()?->get('impersonating') === $this->email();
		},
	]
]);

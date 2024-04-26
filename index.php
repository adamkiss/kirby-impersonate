<?php

use Kirby\Cms\Find;
use Kirby\Cms\User;

\Kirby\Cms\App::plugin('adamkiss/kirby-impersonate', [
	'options' => [
		'can-impersonate' => function () {
			/** @var User $this */
			return true;
		},
		'can-be-impersonated' => function () {
			/** @var User $this */
			return true;
		},
	],
    'api' => [
        'routes' => [
			[
				'pattern' => 'impersonate/status',
				'action'  => function () {
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
	'userMethods' => [
		'canImpersonate' => option('adamkiss.kirby-impersonate.can-impersonate'),
		'canBeImpersonated' => option('adamkiss.kirby-impersonate.can-be-impersonated'),
	]
]);
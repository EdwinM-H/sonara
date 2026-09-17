<?php

namespace App\Services\Assistant;

/**
 * Máquina de estados del Registro Autónomo por Voz.
 *
 * Cada estado define la pregunta a formular, cómo capturar la respuesta
 * y cuál es el siguiente estado. El progreso se almacena por sesión y
 * puede retomarse si el usuario abandona el proceso.
 */
class VoiceAssistantService
{
    public const WELCOME = 'welcome';
    public const FIRST_NAME = 'first_name';
    public const LAST_NAME = 'last_name';
    public const PERSONAL_DESCRIPTION = 'personal_description';
    public const BUSINESS_NAME = 'business_name';
    public const BUSINESS_DESCRIPTION = 'business_description';
    public const CATEGORY = 'category';
    public const SUBCATEGORY = 'subcategory';
    public const TYPE = 'type';
    public const PRICE = 'price';
    public const OFFERINGS = 'offerings';
    public const REGION = 'region';
    public const PROVINCE = 'province';
    public const DISTRICT = 'district';
    public const SCHEDULE = 'schedule';
    public const PHONE = 'phone';
    public const WHATSAPP = 'whatsapp';
    public const EMAIL = 'email';
    public const CONTACT_EXTRA = 'contact_extra';
    public const USERNAME = 'username';
    public const CONFIRMATION = 'confirmation';
    public const DOCUMENTATION = 'documentation';

    public const STEPS = [
        self::WELCOME => [
            'question' => 'Bienvenido a SONARA. Te ayudaré a crear tu cuenta de emprendedor. Vamos paso a paso. Primero, dime tu nombre.',
            'field' => 'first_name',
            'next' => self::FIRST_NAME,
        ],
        self::FIRST_NAME => [
            'question' => 'He registrado tu nombre. Dime tus apellidos.',
            'field' => 'last_name',
            'next' => self::LAST_NAME,
        ],
        self::LAST_NAME => [
            'question' => 'Perfecto. Cuéntame brevemente quién eres y tu experiencia.',
            'field' => 'personal_description',
            'next' => self::PERSONAL_DESCRIPTION,
        ],
        self::PERSONAL_DESCRIPTION => [
            'question' => 'Excelente. ¿Cómo se llama tu emprendimiento o negocio?',
            'field' => 'business_name',
            'next' => self::BUSINESS_NAME,
        ],
        self::BUSINESS_NAME => [
            'question' => '¿Qué productos o servicios ofrece tu emprendimiento? Cuéntame en detalle.',
            'field' => 'business_description',
            'next' => self::BUSINESS_DESCRIPTION,
        ],
        self::BUSINESS_DESCRIPTION => [
            'question' => 'Muy bien. ¿A qué categoría pertenece tu emprendimiento?',
            'field' => 'category',
            'next' => self::CATEGORY,
        ],
        self::CATEGORY => [
            'question' => '¿Tu emprendimiento ofrece productos o servicios?',
            'field' => 'type',
            'next' => self::TYPE,
        ],
        self::TYPE => [
            'question' => '¿Cuál es el precio de tus productos o el rango de precios de tus servicios?',
            'field' => 'price',
            'next' => self::PRICE,
        ],
        self::PRICE => [
            'question' => 'Cuéntame qué productos o servicios específicos ofreces y sus precios.',
            'field' => 'offerings',
            'next' => self::OFFERINGS,
        ],
        self::OFFERINGS => [
            'question' => '¿En qué ciudad o región te ubicas?',
            'field' => 'region',
            'next' => self::REGION,
        ],
        self::REGION => [
            'question' => '¿En qué provincia te ubicas?',
            'field' => 'province',
            'next' => self::PROVINCE,
        ],
        self::PROVINCE => [
            'question' => '¿En qué distrito te ubicas?',
            'field' => 'district',
            'next' => self::DISTRICT,
        ],
        self::DISTRICT => [
            'question' => '¿Cuál es tu horario de atención habitual? Por ejemplo: de lunes a sábado, de 9 de la mañana a 6 de la tarde.',
            'field' => 'schedule',
            'next' => self::SCHEDULE,
        ],
        self::SCHEDULE => [
            'question' => '¿Cuál es tu número de teléfono o celular?',
            'field' => 'phone',
            'next' => self::PHONE,
        ],
        self::PHONE => [
            'question' => '¿Cuál es tu número de WhatsApp? Puede ser el mismo.',
            'field' => 'whatsapp',
            'next' => self::WHATSAPP,
        ],
        self::WHATSAPP => [
            'question' => '¿Cuál es tu correo electrónico?',
            'field' => 'email',
            'next' => self::EMAIL,
        ],
        self::EMAIL => [
            'question' => '¿Deseas agregar alguna red social o información adicional de contacto? Menciona el nombre de la red y tu usuario.',
            'field' => 'contact_extra',
            'next' => self::CONTACT_EXTRA,
        ],
        self::CONTACT_EXTRA => [
            'question' => 'Casi terminamos. Dime el correo o usuario con el que quieres ingresar a tu cuenta (puede ser el mismo correo).',
            'field' => 'username',
            'next' => self::USERNAME,
        ],
        self::USERNAME => [
            'question' => 'He terminado de recopilar tus datos. Verifiquemos todo antes de guardar.',
            'field' => 'confirmation',
            'next' => self::CONFIRMATION,
        ],
        self::CONFIRMATION => [
            'question' => 'Perfecto. Tu cuenta será creada en pocos segundos.',
            'field' => 'documentation',
            'next' => self::DOCUMENTATION,
        ],
    ];

    /** @var array<string, string> */
    public const COMMANDS = [
        'ayuda' => 'Puedes decir: repetir para escuchar de nuevo, volver para regresar, corregir para cambiar el último dato, salir o cancelar para detener el proceso.',
        'repetir' => 'Repetiré la pregunta actual.',
        'volver' => 'Regresaremos al paso anterior.',
        'corregir' => 'Puedes indicarme el dato correcto.',
        'continuar' => 'Continuaremos con el siguiente paso.',
        'cancelar' => 'El proceso fue cancelado. Puedes retomarlo cuando quieras.',
        'salir' => 'El proceso fue cancelado. Puedes retomarlo cuando quieras.',
    ];

    /** @var array<string, string> */
    public const WORDS = [
        'ayuda' => 'help',
        'repetir' => 'repeat',
        'volver' => 'back',
        'atras' => 'back',
        'corregir' => 'fix',
        'continuar' => 'continue',
        'siguiente' => 'continue',
        'guardar' => 'save',
        'salir' => 'exit',
        'cancelar' => 'exit',
    ];

    public function __construct(protected ?string $sessionKey = null)
    {
        $this->sessionKey = $sessionKey ?? 'voice_registration.'.session()->getId();
    }

    public function start(): array
    {
        $session = [
            'state' => self::WELCOME,
            'asked' => false,
            'data' => [],
            'started_at' => now(),
            'updated_at' => now(),
        ];
        cache()->put($this->sessionKey, $session, now()->addDay());

        return $this->reply($session);
    }

    public function state(): string
    {
        return data_get(cache()->get($this->sessionKey, []), 'state', self::WELCOME);
    }

    public function data(): array
    {
        return data_get(cache()->get($this->sessionKey, []), 'data', []);
    }

    public function resume(): array
    {
        $session = cache()->get($this->sessionKey);

        if (! $session) {
            return $this->start();
        }

        return $this->reply($session);
    }

    public function hasSession(): bool
    {
        return (bool) cache()->get($this->sessionKey);
    }

    public function resetSession(): void
    {
        cache()->forget($this->sessionKey);
    }

    public function process(?string $transcript): array
    {
        $session = cache()->get($this->sessionKey);

        if (! $session) {
            return $this->start();
        }

        $trimmed = mb_strtolower(trim((string) $transcript));
        $command = self::WORDS[$trimmed] ?? null;

        if ($trimmed === 'no') {
            $session['asked'] = true;

            return $this->reply($session, 'De acuerdo. Repite la respuesta. Por favor, dime el dato nuevamente.');
        }

        if ($command === 'repeat') {
            $session['asked'] = false;

            return $this->reply($session);
        }

        if ($command === 'back') {
            $session['state'] = $this->previousState($session['state']);
            $session['asked'] = false;

            return $this->reply($session, 'Volvimos al paso anterior.');
        }

        if ($command === 'exit') {
            cache()->forget($this->sessionKey);

            return [
                'type' => 'exited',
                'message' => self::COMMANDS['salir'],
                'session' => null,
            ];
        }

        if ($command === 'help') {
            return [
                'type' => 'message',
                'message' => self::COMMANDS['ayuda'],
                'state' => $session['state'],
            ];
        }

        if ($command === 'fix') {
            $session['asked'] = true;

            return $this->reply($session, 'De acuerdo, dime el dato correcto.');
        }

        if ($command === 'continue' || $command === 'save') {
            $session['asked'] = false;

            return $this->reply($session, 'Antes de continuar necesito que me digas el dato solicitado.');
        }

        // Captura de dato
        $step = self::STEPS[$session['state']] ?? null;
        if (! $step) {
            return $this->finish($session);
        }

        $session['data'][$step['field']] = trim((string) $transcript);
        $session['asked'] = false;
        $session['state'] = $step['next'];
        $session['updated_at'] = now();

        cache()->put($this->sessionKey, $session, now()->addDay());

        return $this->reply($session);
    }

    public function review(): array
    {
        $data = $this->data();

        return [
            'type' => 'review',
            'summary' => [
                'Nombre' => $data['first_name'] ?? null,
                'Apellidos' => $data['last_name'] ?? null,
                'Descripción personal' => $data['personal_description'] ?? null,
                'Emprendimiento' => $data['business_name'] ?? null,
                'Descripción del emprendimiento' => $data['business_description'] ?? null,
                'Categoría' => $data['category'] ?? null,
                'Tipo' => $data['type'] ?? null,
                'Precio' => $data['price'] ?? null,
                'Productos/Servicios' => $data['offerings'] ?? null,
                'Ubicación' => collect([$data['district'], $data['province'], $data['region']])->filter()->implode(', '),
                'Horario' => $data['schedule'] ?? null,
                'Teléfono' => $data['phone'] ?? null,
                'WhatsApp' => $data['whatsapp'] ?? null,
                'Correo' => $data['email'] ?? null,
                'Contacto adicional' => $data['contact_extra'] ?? null,
                'Usuario' => $data['username'] ?? null,
            ],
        ];
    }

    public function nextQuestion(): array
    {
        return $this->reply(cache()->get($this->sessionKey) ?: [
            'state' => self::WELCOME,
            'asked' => false,
            'data' => [],
        ]);
    }

    protected function reply(array $session, ?string $note = null): array
    {
        $step = self::STEPS[$session['state']] ?? null;
        $hadQuestion = (bool) ($session['asked'] ?? false);

        if ($step && $session['asked']) {
            return [
                'type' => 'confirm',
                'state' => $session['state'],
                'field' => $step['field'],
                'question' => $step['question'],
                'message' => $note,
            ];
        }

        if ($step) {
            $session['asked'] = true;
            cache()->put($this->sessionKey, $session, now()->addDay());

            return [
                'type' => 'question',
                'state' => $session['state'],
                'field' => $step['field'],
                'ssml' => $step['question'],
                'message' => $note,
                'is_welcome' => $session['state'] === self::WELCOME,
            ];
        }

        return $this->finish($session);
    }

    protected function finish(array $session): array
    {
        cache()->put($this->sessionKey, $session, now()->addDay());

        return [
            'type' => 'blocked',
            'state' => self::CONFIRMATION,
            'message' => 'Se ha completado la recopilación de datos. Revisa el resumen y confirma para crear tu cuenta.',
            'summary' => $this->review()['summary'],
        ];
    }

    protected function previousState(string $state): string
    {
        $ordered = array_keys(self::STEPS);
        $index = array_search($state, $ordered, true);

        return $index > 0 ? $ordered[$index - 1] : $state;
    }
}
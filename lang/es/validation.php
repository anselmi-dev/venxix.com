<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted' => 'Tenés que aceptar el campo :attribute.',
    'accepted_if' => 'Tenés que aceptar el campo :attribute cuando :other sea :value.',
    'active_url' => 'El campo :attribute no es una URL válida.',
    'after' => 'El campo :attribute tiene que ser una fecha posterior a :date.',
    'after_or_equal' => 'El campo :attribute tiene que ser una fecha posterior o igual a :date.',
    'alpha' => 'El campo :attribute solo puede contener letras.',
    'alpha_dash' => 'El campo :attribute solo puede contener letras, números, guiones y guiones bajos.',
    'alpha_num' => 'El campo :attribute solo puede contener letras y números.',
    'any_of' => 'El campo :attribute no es válido.',
    'array' => 'El campo :attribute tiene que ser una lista.',
    'array_keys' => 'El campo :attribute solo puede contener las siguientes claves: :values.',
    'ascii' => 'El campo :attribute solo puede contener caracteres alfanuméricos y símbolos de un byte.',
    'base64' => 'El campo :attribute tiene que ser una cadena Base64 válida.',
    'before' => 'El campo :attribute tiene que ser una fecha anterior a :date.',
    'before_or_equal' => 'El campo :attribute tiene que ser una fecha anterior o igual a :date.',
    'between' => [
        'array' => 'El campo :attribute tiene que tener entre :min y :max elementos.',
        'file' => 'El campo :attribute tiene que pesar entre :min y :max kilobytes.',
        'numeric' => 'El campo :attribute tiene que estar entre :min y :max.',
        'string' => 'El campo :attribute tiene que tener entre :min y :max caracteres.',
    ],
    'boolean' => 'El campo :attribute tiene que ser verdadero o falso.',
    'can' => 'El campo :attribute contiene un valor no autorizado.',
    'confirmed' => 'La confirmación del campo :attribute no coincide.',
    'contains' => 'Al campo :attribute le falta un valor obligatorio.',
    'current_password' => 'La contraseña es incorrecta.',
    'date' => 'El campo :attribute no es una fecha válida.',
    'date_equals' => 'El campo :attribute tiene que ser una fecha igual a :date.',
    'date_format' => 'El campo :attribute no coincide con el formato :format.',
    'decimal' => 'El campo :attribute tiene que tener :decimal decimales.',
    'declined' => 'Tenés que rechazar el campo :attribute.',
    'declined_if' => 'Tenés que rechazar el campo :attribute cuando :other sea :value.',
    'different' => 'Los campos :attribute y :other tienen que ser distintos.',
    'digits' => 'El campo :attribute tiene que tener :digits dígitos.',
    'digits_between' => 'El campo :attribute tiene que tener entre :min y :max dígitos.',
    'dimensions' => 'Las dimensiones de la imagen del campo :attribute no son válidas.',
    'distinct' => 'El campo :attribute tiene un valor repetido.',
    'doesnt_contain' => 'El campo :attribute no puede contener ninguno de estos valores: :values.',
    'doesnt_end_with' => 'El campo :attribute no puede terminar con ninguno de estos valores: :values.',
    'doesnt_start_with' => 'El campo :attribute no puede empezar con ninguno de estos valores: :values.',
    'email' => 'El campo :attribute tiene que ser una dirección de correo válida.',
    'encoding' => 'El campo :attribute tiene que estar codificado en :encoding.',
    'ends_with' => 'El campo :attribute tiene que terminar con uno de estos valores: :values.',
    'enum' => 'El campo :attribute no es una opción válida.',
    'exists' => 'El campo :attribute no es una opción válida.',
    'extensions' => 'El campo :attribute tiene que tener una de estas extensiones: :values.',
    'file' => 'El campo :attribute tiene que ser un archivo.',
    'filled' => 'El campo :attribute no puede quedar vacío.',
    'gt' => [
        'array' => 'El campo :attribute tiene que tener más de :value elementos.',
        'file' => 'El campo :attribute tiene que pesar más de :value kilobytes.',
        'numeric' => 'El campo :attribute tiene que ser mayor que :value.',
        'string' => 'El campo :attribute tiene que tener más de :value caracteres.',
    ],
    'gte' => [
        'array' => 'El campo :attribute tiene que tener :value elementos o más.',
        'file' => 'El campo :attribute tiene que pesar :value kilobytes o más.',
        'numeric' => 'El campo :attribute tiene que ser mayor o igual que :value.',
        'string' => 'El campo :attribute tiene que tener :value caracteres o más.',
    ],
    'hex_color' => 'El campo :attribute tiene que ser un color hexadecimal válido.',
    'image' => 'El campo :attribute tiene que ser una imagen.',
    'in' => 'El campo :attribute no es una opción válida.',
    'in_array' => 'El campo :attribute tiene que existir en :other.',
    'in_array_keys' => 'El campo :attribute tiene que contener al menos una de estas claves: :values.',
    'integer' => 'El campo :attribute tiene que ser un número entero.',
    'ip' => 'El campo :attribute tiene que ser una dirección IP válida.',
    'ipv4' => 'El campo :attribute tiene que ser una dirección IPv4 válida.',
    'ipv6' => 'El campo :attribute tiene que ser una dirección IPv6 válida.',
    'json' => 'El campo :attribute tiene que ser una cadena JSON válida.',
    'list' => 'El campo :attribute tiene que ser una lista.',
    'lowercase' => 'El campo :attribute tiene que estar en minúsculas.',
    'lt' => [
        'array' => 'El campo :attribute tiene que tener menos de :value elementos.',
        'file' => 'El campo :attribute tiene que pesar menos de :value kilobytes.',
        'numeric' => 'El campo :attribute tiene que ser menor que :value.',
        'string' => 'El campo :attribute tiene que tener menos de :value caracteres.',
    ],
    'lte' => [
        'array' => 'El campo :attribute no puede tener más de :value elementos.',
        'file' => 'El campo :attribute tiene que pesar :value kilobytes o menos.',
        'numeric' => 'El campo :attribute tiene que ser menor o igual que :value.',
        'string' => 'El campo :attribute tiene que tener :value caracteres o menos.',
    ],
    'mac_address' => 'El campo :attribute tiene que ser una dirección MAC válida.',
    'max' => [
        'array' => 'El campo :attribute no puede tener más de :max elementos.',
        'file' => 'El campo :attribute no puede pesar más de :max kilobytes.',
        'numeric' => 'El campo :attribute no puede ser mayor que :max.',
        'string' => 'El campo :attribute no puede tener más de :max caracteres.',
    ],
    'max_digits' => 'El campo :attribute no puede tener más de :max dígitos.',
    'mimes' => 'El campo :attribute tiene que ser un archivo de tipo: :values.',
    'mimetypes' => 'El campo :attribute tiene que ser un archivo de tipo: :values.',
    'min' => [
        'array' => 'El campo :attribute tiene que tener al menos :min elementos.',
        'file' => 'El campo :attribute tiene que pesar al menos :min kilobytes.',
        'numeric' => 'El campo :attribute tiene que ser al menos :min.',
        'string' => 'El campo :attribute tiene que tener al menos :min caracteres.',
    ],
    'min_digits' => 'El campo :attribute tiene que tener al menos :min dígitos.',
    'missing' => 'El campo :attribute no puede estar presente.',
    'missing_if' => 'El campo :attribute no puede estar presente cuando :other sea :value.',
    'missing_unless' => 'El campo :attribute no puede estar presente salvo que :other sea :value.',
    'missing_with' => 'El campo :attribute no puede estar presente cuando :values esté presente.',
    'missing_with_all' => 'El campo :attribute no puede estar presente cuando :values estén presentes.',
    'multiple_of' => 'El campo :attribute tiene que ser múltiplo de :value.',
    'not_in' => 'El campo :attribute no es una opción válida.',
    'not_regex' => 'El formato del campo :attribute no es válido.',
    'numeric' => 'El campo :attribute tiene que ser un número.',
    'password' => [
        'letters' => 'El campo :attribute tiene que contener al menos una letra.',
        'mixed' => 'El campo :attribute tiene que contener al menos una mayúscula y una minúscula.',
        'numbers' => 'El campo :attribute tiene que contener al menos un número.',
        'symbols' => 'El campo :attribute tiene que contener al menos un símbolo.',
        'uncompromised' => 'Esta contraseña apareció en una filtración de datos. Elegí otra.',
    ],
    'present' => 'El campo :attribute tiene que estar presente.',
    'present_if' => 'El campo :attribute tiene que estar presente cuando :other sea :value.',
    'present_unless' => 'El campo :attribute tiene que estar presente salvo que :other sea :value.',
    'present_with' => 'El campo :attribute tiene que estar presente cuando :values esté presente.',
    'present_with_all' => 'El campo :attribute tiene que estar presente cuando :values estén presentes.',
    'prohibited' => 'El campo :attribute no está permitido.',
    'prohibited_if' => 'El campo :attribute no está permitido cuando :other sea :value.',
    'prohibited_if_accepted' => 'El campo :attribute no está permitido cuando se acepta :other.',
    'prohibited_if_declined' => 'El campo :attribute no está permitido cuando se rechaza :other.',
    'prohibited_unless' => 'El campo :attribute no está permitido salvo que :other esté entre :values.',
    'prohibits' => 'El campo :attribute impide que :other esté presente.',
    'regex' => 'El formato del campo :attribute no es válido.',
    'required' => 'El campo :attribute es obligatorio.',
    'required_array_keys' => 'El campo :attribute tiene que contener entradas para: :values.',
    'required_if' => 'El campo :attribute es obligatorio cuando :other es :value.',
    'required_if_accepted' => 'El campo :attribute es obligatorio cuando se acepta :other.',
    'required_if_declined' => 'El campo :attribute es obligatorio cuando se rechaza :other.',
    'required_unless' => 'El campo :attribute es obligatorio salvo que :other esté entre :values.',
    'required_with' => 'El campo :attribute es obligatorio cuando :values está presente.',
    'required_with_all' => 'El campo :attribute es obligatorio cuando :values están presentes.',
    'required_without' => 'El campo :attribute es obligatorio cuando :values no está presente.',
    'required_without_all' => 'El campo :attribute es obligatorio cuando ninguno de :values está presente.',
    'same' => 'El campo :attribute tiene que coincidir con :other.',
    'size' => [
        'array' => 'El campo :attribute tiene que contener :size elementos.',
        'file' => 'El campo :attribute tiene que pesar :size kilobytes.',
        'numeric' => 'El campo :attribute tiene que ser :size.',
        'string' => 'El campo :attribute tiene que tener :size caracteres.',
    ],
    'starts_with' => 'El campo :attribute tiene que empezar con uno de estos valores: :values.',
    'string' => 'El campo :attribute tiene que ser texto.',
    'timezone' => 'El campo :attribute tiene que ser una zona horaria válida.',
    'unique' => 'El campo :attribute ya está en uso.',
    'uploaded' => 'No se pudo subir el campo :attribute.',
    'uppercase' => 'El campo :attribute tiene que estar en mayúsculas.',
    'url' => 'El campo :attribute tiene que ser una URL válida.',
    'ulid' => 'El campo :attribute tiene que ser un ULID válido.',
    'uuid' => 'El campo :attribute tiene que ser un UUID válido.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'email' => [
            'unique' => 'Ya existe una cuenta con este correo.',
        ],
        'last_four' => [
            'digits' => 'Ingresá los últimos 4 dígitos de la tarjeta.',
        ],
        'password' => [
            'confirmed' => 'Las contraseñas no coinciden.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */

    'attributes' => [
        'alias' => 'alias',
        'bank' => 'banco',
        'body' => 'nota',
        'card_id' => 'tarjeta',
        'code' => 'código',
        'currency' => 'moneda',
        'current_password' => 'contraseña actual',
        'email' => 'correo',
        'first_due_date' => 'primer vencimiento',
        'gateway' => 'medio de pago',
        'installment_count' => 'cantidad de cuotas',
        'interval' => 'período de facturación',
        'is_flagged' => 'marca de la nota',
        'last_four' => 'últimos 4 dígitos',
        'merchant' => 'comercio',
        'name' => 'nombre',
        'password' => 'contraseña',
        'password_confirmation' => 'confirmación de la contraseña',
        'preferred_currency' => 'moneda preferida',
        'recovery_code' => 'código de recuperación',
        'row_ids' => 'filas seleccionadas',
        'show_both_currencies' => 'opción de mostrar ambas monedas',
        'reminders_enabled' => 'recordatorios',
        'statement' => 'archivo CSV',
        'total_amount' => 'monto total',
        'type' => 'tipo',
        'usd_uyu_rate' => 'cotización del dólar',
    ],

];

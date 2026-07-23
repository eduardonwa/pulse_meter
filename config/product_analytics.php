<?php

return [
    'events' => [
        // Visita
        'app_opened' => 'visit',
        'engaged_10s' => 'visit',

        // Exploración
        'tab_viewed' => 'exploration',
        'session_type_selected' => 'exploration',
        'setting_changed' => 'exploratio1n',
        'exercise_form_opened' => 'exploration',
        'exercise_form_cancelled' => 'exploration',
        'bpm_changed' => 'exploration',

        // Prueba
        'exercise_edit_abandoned' => 'trial',
        'playback_started' => 'trial',
        'playback_stopped' => 'trial',
        'exercise_completed' => 'trial',

        // Activación
        'exercise_created' => 'activation',
        'exercise_customized' => 'activation',
        'exercise_updated' => 'activation',
        'practice_engaged' => 'activation',
    ],
];
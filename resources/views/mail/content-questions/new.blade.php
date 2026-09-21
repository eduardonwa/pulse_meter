<x-mail::message>
# Nueva pregunta para Dorelog

{{ $question->name ?: 'Una persona' }} buscó una respuesta que todavía no está disponible:

> {{ $question->question }}

Puedes responder directamente a este correo para escribirle a **{{ $question->email }}**.

Idioma de la consulta: {{ strtoupper($question->locale) }}
</x-mail::message>

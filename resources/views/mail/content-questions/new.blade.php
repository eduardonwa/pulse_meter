<x-mail::message>
# Nueva pregunta para Dorelog

{{ $question->name ?: 'Una persona' }} buscó una respuesta que todavía no está disponible:

> {{ $question->question }}

Puedes responder directamente a este correo para escribirle a **{{ $question->email }}**.

<x-mail::button :url="\App\Filament\Resources\ContentQuestions\ContentQuestionResource::getUrl('edit', ['record' => $question])">
Abrir en Filament
</x-mail::button>

Idioma de la consulta: {{ strtoupper($question->locale) }}
</x-mail::message>

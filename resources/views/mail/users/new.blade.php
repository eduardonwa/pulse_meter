<x-mail::message>
# {{ $user->name }}, welcome!

Thanks for signing up for Dorelog, the place where you can take your current guitar, bass or drum exercises and turn them into easy-to-adjust routines, while finding new musical ideas along the way.

The best way to get started is to simply add the exercises you're currently practicing and use them to build your first routine.

Dorelog is independently built and run by me, so when you reply to this email, you're talking directly to the person designing and developing it.

If something feels confusing, off or could work better, let me know... your feedback can directly shape what I work on next!

<strong> Eduardo </strong> <br>
<strong> Creator of Dorelog </strong>

P.S. Reply to this email and tell me: what's your main instrument?

<x-mail::button :url="config('app.url')">
Back to practice
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
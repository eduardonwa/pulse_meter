@php($user = auth()->user())

<x-layouts.dorelog :user="$user">
    <main class="billing-page | container">
        <header class="billing-page__header">
            <p class="uppercase">Account</p>

            <h1>Plans &amp; billing</h1>

            <p>
                Review your DoreLog access and choose between monthly Pro
                or a one-time Lifetime purchase.
            </p>
        </header>
    </main>
</x-layouts.dorelog>
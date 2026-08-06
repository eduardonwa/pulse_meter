@php($user = auth()->user())

<x-layouts.dorelog :user="$user">
    <main class="billing-page | container">
        <x-account.navigation />

        <header class="billing-page__header">
            <p class="uppercase">Account</p>

            <h1>Plans &amp; billing</h1>

            <p>
                Review your DoreLog access and choose between monthly Pro
                or a one-time Lifetime purchase.
            </p>
        </header>
        <section class="billing-summary" aria-labelledby="billing-current-access">
            <p class="uppercase">Current access</p>

            <h2 id="billing-current-access">
                {{ $billingState['name'] }}
            </h2>

            <p>
                {{ $billingState['detail'] }}
            </p>
        </section>
    </main>
</x-layouts.dorelog>

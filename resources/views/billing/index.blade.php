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

        @if ($checkoutNotice)
            <p
                class="billing-notice"
                data-status="{{ $checkoutNotice['status'] }}"
                role="status"
            >
                {{ $checkoutNotice['message'] }}
            </p>
        @endif

        <section class="billing-summary" aria-labelledby="billing-current-access">
            <p class="uppercase">Current access</p>

            <h2 id="billing-current-access">
                {{ $billingState['name'] }}
            </h2>

            <p>
                {{ $billingState['detail'] }}
            </p>
        </section>

        <section class="billing-plans" aria-labelledby="billing-plan-options">
            <header>
                <p class="uppercase">Pro options</p>

                <h2 id="billing-plan-options">
                    Choose how you want Pro
                </h2>
            </header>

            <div class="billing-plans__options">
                <article class="billing-plan">
                    <p class="uppercase">Subscription</p>

                    <h3>Monthly Pro</h3>

                    @if ($monthlyDisplayPrice)
                        <p class="billing-plan__price">
                            {{ $monthlyDisplayPrice }}
                        </p>
                    @endif

                    <p>
                        Recurring Pro access. Your subscription continues
                        until you cancel its renewal.
                    </p>

                    @if ($canPurchaseMonthly)
                        <form
                            method="POST"
                            action="{{ route(
                                'billing.pro.monthly.checkout'
                            ) }}"
                        >
                            @csrf

                            <button class="button" type="submit">
                                Choose Monthly Pro
                            </button>
                        </form>
                    @elseif ($hasMonthlyPro)
                        <p>Current plan</p>
                    @elseif ($hasLifetimePro)
                        <p>Included with Lifetime Pro</p>
                    @else
                        <p>Unavailable while Pro access is active.</p>
                    @endif
                </article>

                <article class="billing-plan">
                    <p class="uppercase">One-time purchase</p>

                    <h3>Lifetime Pro</h3>

                    @if ($lifetimeDisplayPrice)
                        <p class="billing-plan__price">
                            {{ $lifetimeDisplayPrice }}
                        </p>
                    @endif

                    <p>
                        Pay once for Pro access that does not expire.
                    </p>

                    @if ($canPurchaseLifetime)
                        <form
                            method="POST"
                            action="{{ route(
                                'billing.pro.lifetime.checkout'
                            ) }}"
                        >
                            @csrf

                            <button class="button" type="submit">
                                Choose Lifetime Pro
                            </button>
                        </form>
                    @elseif ($hasLifetimePro)
                        <p>Owned</p>
                    @elseif ($hasMonthlyPro)
                        <p>
                            Available after your monthly subscription ends.
                        </p>
                    @else
                        <p>Unavailable while Pro access is active.</p>
                    @endif
                </article>
            </div>
        </section>
    </main>
</x-layouts.dorelog>

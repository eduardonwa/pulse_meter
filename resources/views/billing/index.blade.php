@php($user = auth()->user())

<x-layouts.dorelog :user="$user">
    <main class="account-page | container">
        <x-account.navigation />

        <header class="account-page__header">
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

        <section class="account-page__section | billing-summary" aria-labelledby="billing-current-access">
            <div class="current-access">
                <p class="subheader | uppercase">Current access</p>
    
                <h2 class="heading-3" id="billing-current-access">
                    {{ $billingState['name'] }}
                </h2>
            </div>

            <p class="summary-detail"> {{ $billingState['detail'] }} </p>
        </section>

        <section class="account-page__section | billing-plans" aria-labelledby="billing-plan-options">
            <header>
                <h2 class="heading-3" id="billing-plan-options">
                    Choose how you want Pro
                </h2>
            </header>

            <div class="billing-plans__options">
                <article @class(['billing-plan', 'billing-plan--current' => $hasMonthlyPro ])>
                    <div class="billing-plan__header">
                        <x-heroicon-s-sparkles class="subscription-icon" />

                        <p class="subscription-type subscription-type--monthly | subheader uppercase">
                            {{ $hasMonthlyPro ? 'Current plan' : 'Subscription' }}
                        </p>
                    </div>

                    <h3 class="title title--monthly">Monthly Pro</h3>
                    @if ($monthlyDisplayPrice)
                        <p class="billing-plan__price">
                            {{ $monthlyDisplayPrice }}
                        </p>
                    @endif

                    <p class="billing-plan__description">
                        Recurring Pro access. Your subscription continues
                        until you cancel its renewal.
                    </p>

                    <div class="billing-plan__footer">
                        @if ($canPurchaseMonthly)
                            <form class="billing-plan__action" method="POST" action="{{ route('billing.pro.monthly.checkout') }}">
                                @csrf

                                <button class="button" type="submit">
                                    Choose Monthly Pro
                                </button>
                            </form>
                        @elseif ($hasMonthlyPro)
                            <div class="billing-plan__status">
                                @if ($monthlyManagement)
                                    <p
                                        class="billing-plan__status-detail"
                                        data-status="{{ $monthlyManagement['status'] }}"
                                    >
                                        {{ $monthlyManagement['detail'] }}
                                    </p>
                                @endif
                            </div>

                            <form class="billing-plan__action" method="POST" action="{{ route('billing.portal') }}">
                                @csrf

                                <button class="button" type="submit">
                                    Manage subscription
                                </button>
                            </form>
                        @elseif ($hasLifetimePro)
                            <p class="billing-plan__status">
                                Included with Lifetime Pro
                            </p>
                        @else
                            <p class="billing-plan__status">
                                Unavailable while Pro access is active.
                            </p>
                        @endif
                    </div>
                </article>

                <article @class(['billing-plan', 'billing-plan--current' => $hasLifetimePro ])>
                    <div class="billing-plan__header">
                        <x-heroicon-s-star class="subscription-icon" />
                        <p class="subscription-type subscription-type--lifetime | subheader uppercase">
                            {{ $hasLifetimePro ? 'You own this' : 'One-time payment' }}
                        </p>
                    </div>

                    <h3 class="title title--lifetime">Lifetime Pro</h3>
                    @if ($lifetimeDisplayPrice)
                        <p class="billing-plan__price">
                            {{ $lifetimeDisplayPrice }}
                        </p>
                    @endif

                    <p class="billing-plan__description">
                        Pay once for Pro access that does not expire.
                    </p>

                    <div class="billing-plan__footer">
                        @if ($canPurchaseLifetime)
                            <form class="billing-plan__action" method="POST" action="{{ route('billing.pro.lifetime.checkout') }}">
                                @csrf
    
                                <button class="button" type="submit">
                                    Choose Lifetime Pro
                                </button>
                            </form>
                        @elseif ($hasLifetimePro)
                            <p class="billing-plan__status-label">Owned</p>
                        @elseif ($hasMonthlyPro)
                            <p class="billing-plan__status">
                                Available after your monthly subscription ends.
                            </p>
                        @else
                            <p class="billing-plan__status">Unavailable while Pro access is active.</p>
                        @endif
                    </div>
                </article>
            </div>
        </section>
    </main>
</x-layouts.dorelog>

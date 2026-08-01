@props([
    'routine' => null,
    'routines' => null,
    'usesServerPersistence' => false,
])

@if (
    $usesServerPersistence
    && $routine
    && $routines->isNotEmpty()
)
    <dialog
        class="dialog-shell | pattern-dialog"
        data-variant="pattern-dialog"
        x-ref="routineDialog"
        x-trap.noscroll="isRoutineDialogOpen"
        @close="isRoutineDialogOpen = false"
        @cancel.prevent="closeRoutineDialog()"
        @click.self="closeRoutineDialog()"
    >
        <div class="pattern-dialog__content">
            <header class="heading">
                <button
                    type="button"
                    class="button"
                    data-type="icon"
                    @click="closeRoutineDialog()"
                >
                    <x-heroicon-o-x-circle />
                </button>

                <h2 class="pattern-heading">
                    Choose routine
                </h2>
            </header>

            <div class="pattern-dialog__list">
                @foreach ($routines as $routineOption)
                    <a
                        href="{{ route('welcome', [
                            'routine' => $routineOption['id'],
                        ]) }}"
                        class="button"
                        data-type="outline"
                        @class([
                            'is-selected' =>
                                (int) $routineOption['id']
                                === (int) $routine['id'],
                        ])
                        @if (
                            (int) $routineOption['id']
                            === (int) $routine['id']
                        )
                            aria-current="page"
                        @endif
                    >
                        <strong>
                            {{ $routineOption['name'] }}
                        </strong>

                        <small>
                            {{ $routineOption['steps_count'] }}
                            {{ $routineOption['steps_count'] === 1
                                ? 'exercise'
                                : 'exercises' }}

                            @if ($routineOption['is_default'])
                                · Default
                            @endif
                        </small>
                    </a>
                @endforeach
            </div>
        </div>
    </dialog>
@endif
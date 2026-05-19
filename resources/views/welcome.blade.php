<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>pulse_meter</title>
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div
        x-data="routinePlayer([
            {
                name: 'Alternate Picking',
                bpm: 100,
                mode: 'timer',
                duration_seconds: 5
            },
            {
                name: 'Legato',
                bpm: 80,
                mode: 'timer',
                duration_seconds: 5
            },
            {
                name: 'Sweep Picking',
                bpm: 90,
                mode: 'timer',
                duration_seconds: null
            }
        ])"
        @keydown.window="handleHotKey($event)"
    >
        <div>
            <label for="exercise">
                Ejercicio
                <input type="text" x-model="currentStep.name">
            </label>
        </div>

        <div>
            <label for="bpm">
                <input
                    type="number"
                    min="30"
                    max="300"
                    x-model.number="currentStep.bpm"
                    @change="restartCurrentStep()"
                >
            </label>
        </div>

        <div x-show="currentStep.mode === 'timer'">
            <p>
                Tiempo restante: <span x-text="formattedRemaining"></span>
            </p>

            <label>
                Duración:
                <input type="number" min="0" max="5" x-model.number="durationMinutes">
                <span>m</span>

                <input type="number" min="0" max="59" x-model.number="durationSeconds">
                <span>s</span>
            </label>
        </div>

        <label for="autoadvance">
            <input type="checkbox" x-model="autoAdvance">
            Avanzar al siguiente al terminar
        </label>

        <label for="mode">
            <select x-model="currentStep.mode" @change="changeMode()">
                <option value="timer">Timer</option>
                <option value="manual">Manual</option>
            </select>
        </label>
        
        <button @click="previousStep()">Anterior</button>

        <button @click="toggle()">
            <span x-text="isPlaying ? 'Stop' : 'Start'"></span>
        </button>

        <button @click="nextStep()">Siguiente</button>

        <button 
            @click="openAddStepModal()"
            :disabled="steps.length >= maxSteps"
        >
            Agregar ejercicio
        </button>

        <dialog x-ref="addStepDialog">
            <form @submit.prevent="saveNewStep()">
                <h3>Nuevo ejercicio</h3>

                <label>
                    Nombre
                    <input type="text" x-model="newStep.name">
                </label>

                <label>
                    BPM
                    <input type="number" min="30" max="300" x-model.number="newStep.bpm">
                </label>

                <label>
                    Modo
                    <select x-model="newStep.mode">
                        <option value="timer">Timer</option>
                        <option value="manual">Manual</option>
                    </select>
                </label>

                <div x-show="newStep.mode === 'timer'">
                    <label>
                        Duración
                        <input type="number" min="0" max="5" x-model.number="newStepMinutes">
                        <span>m</span>

                        <input type="number" min="0" max="59" x-model.number="newStepSeconds">
                        <span>s</span>
                    </label>
                </div>

                <button type="submit">Guardar</button>
                <button type="button" @click="$refs.addStepDialog.close()">Cancelar</button>
            </form>
        </dialog>

        <button @click="removeCurrentStep()"
            :disabled="steps.length <= 1"
        >
            Eliminar ejercicio
        </button>

        <p>
            <span x-text="steps.length"></span> / <span x-text="maxSteps"></span> ejercicios
        </p>
    </div>
</body>
</html>
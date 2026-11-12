export async function bootstrap({ needsPractice, loadPractice, startLivewire }) {
    if (needsPractice) {
        performance.mark('dorelog:practice-imports-start')

        await loadPractice()

        performance.mark('dorelog:practice-imports-end')

        performance.measure(
            'dorelog:practice-imports',
            'dorelog:practice-imports-start',
            'dorelog:practice-imports-end'
        )
    }

    startLivewire()

    if (needsPractice) {
        performance.mark('dorelog:livewire-start-returned')

        performance.measure(
            'dorelog:livewire-start',
            'dorelog:practice-imports-end',
            'dorelog:livewire-start-returned'
        )
    }
}
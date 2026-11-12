// The only place that starts Livewire. Practice factories must be registered
// before Alpine scans x-data attributes on the initial page.
export async function bootstrap({ needsPractice, loadPractice, startLivewire }) {
    if (needsPractice) {
        await loadPractice()
    }

    startLivewire()
}

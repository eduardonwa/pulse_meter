// The practice page needs these registrations before Livewire starts Alpine.
// Other pages load app.js directly and do not download the metronome modules.
import './metronome'
import './alphatab-exercises'

// Keep the shared Livewire/Alpine bootstrap as the final dependency.
// Livewire's head-script navigation loads this page-specific entry before
// swapping the new body into place on wire:navigate visits.
import './app'
